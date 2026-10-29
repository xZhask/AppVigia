<?php
namespace App\Core;

use Throwable;

/**
 * Cliente para consultar los datos de una persona por DNI en RENIEC.
 * Cambiar de proveedor solo debe tocar este archivo: URL y token viven en
 * config.local.php (ver config.php), nunca en el navegador, en git ni
 * incrustados en el código.
 *
 * Solo hace la llamada y clasifica la respuesta. Cuándo se consulta (padrón
 * primero, DNI no encontrados recordados, límite diario, pausa tras cuota
 * agotada) lo decide App\Services\PersonaService, la única que lo llama.
 *
 * Nunca lanza excepciones ni bloquea el registro: cualquier falla devuelve
 * un estado distinto de ENCONTRADO y el usuario sigue digitando a mano.
 */
class ReniecService
{
    public const ENCONTRADO = 'ENCONTRADO';
    public const NO_ENCONTRADO = 'NO_ENCONTRADO';
    // El proveedor rechazó la consulta: cuota del plan agotada (429) o token
    // inválido o revocado (401/403). Repetir solo gasta intentos.
    public const RECHAZADA = 'RECHAZADA';
    // Timeout, error de red o respuesta ilegible: no dice nada del DNI.
    public const FALLA = 'FALLA';

    /** Sección 'reniec' de la configuración, con sus valores por defecto. */
    public static function config(): array
    {
        $config = require __DIR__ . '/../../config/config.php';

        return ($config['reniec'] ?? []) + [
            'url'                         => '',
            'token'                       => '',
            'timeout'                     => 5,
            'limite_diario_usuario'       => 20,
            'recordar_no_encontrado_dias' => 30,
            'pausa_rechazo_horas'         => 6,
        ];
    }

    /** true si hay URL y token: sin ellos los datos se digitan a mano. */
    public static function configurado(): bool
    {
        $reniec = self::config();

        return trim($reniec['url']) !== '' && trim($reniec['token']) !== '';
    }

    /**
     * Consulta un DNI de 8 dígitos. Devuelve ['estado' => ENCONTRADO |
     * NO_ENCONTRADO | RECHAZADA | FALLA, 'datos' => apellidos y nombres o
     * null]. Quien llama debe haber comprobado configurado().
     */
    public static function consultar(string $dni): array
    {
        $falla = ['estado' => self::FALLA, 'datos' => null];
        if (!preg_match('/^\d{8}$/', $dni) || !function_exists('curl_init')) {
            return $falla;
        }

        try {
            $reniec = self::config();
            $timeout = (int) $reniec['timeout'];

            $ch = curl_init(str_replace('{dni}', $dni, trim($reniec['url'])));
            if ($ch === false) {
                return $falla;
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . trim($reniec['token']),
                    'Accept: application/json',
                ],
            ]);

            $respuesta = curl_exec($ch);
            $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errorCurl = curl_error($ch);
            // curl_close() quedó en desuso desde PHP 8.0 (no hace nada: el
            // handle se libera solo al salir de alcance); se omite a propósito.

            if ($respuesta === false || $errorCurl !== '') {
                return $falla;
            }

            // apiperu.dev: 429 {"code":"PLAN_LIMIT_REACHED"} con la cuota del
            // plan agotada; 401 con el token inválido (comprobado el 29/10).
            if (in_array($codigoHttp, [401, 403, 429], true)) {
                error_log("RENIEC: el proveedor rechazó la consulta (HTTP $codigoHttp); se pausan las consultas.");

                return ['estado' => self::RECHAZADA, 'datos' => null];
            }
            if ($codigoHttp === 404) {
                return ['estado' => self::NO_ENCONTRADO, 'datos' => null];
            }
            if ($codigoHttp !== 200) {
                return $falla;
            }

            $datos = json_decode((string) $respuesta, true);
            if (!is_array($datos)) {
                return $falla;
            }

            // Soporte para formato de apiperu.dev/api/dni
            if (isset($datos['success']) && $datos['success'] === true && isset($datos['data'])) {
                $data = $datos['data'];
            } else {
                $data = $datos;
            }

            $apellidoPaterno = trim((string) ($data['apellidoPaterno'] ?? $data['apellido_paterno'] ?? ''));
            $apellidoMaterno = trim((string) ($data['apellidoMaterno'] ?? $data['apellido_materno'] ?? ''));
            $nombres = trim((string) ($data['nombres'] ?? ''));

            if ($apellidoPaterno === '' && $nombres === '') {
                return ['estado' => self::NO_ENCONTRADO, 'datos' => null];
            }

            return ['estado' => self::ENCONTRADO, 'datos' => [
                'apellido_paterno' => $apellidoPaterno !== '' ? $apellidoPaterno : null,
                'apellido_materno' => $apellidoMaterno !== '' ? $apellidoMaterno : null,
                'nombres'          => $nombres !== '' ? $nombres : null,
            ]];
        } catch (Throwable $e) {
            error_log('RENIEC: fallo en la consulta (' . $e->getMessage() . '), se continúa sin bloquear.');

            return $falla;
        }
    }
}
