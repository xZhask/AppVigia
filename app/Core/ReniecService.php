<?php
namespace App\Core;

use Throwable;

/**
 * Cliente para consultar los datos de una persona por DNI en RENIEC.
 * Única interfaz pública: buscarPorDni(). Cambiar de proveedor solo debe
 * tocar este archivo: URL y token viven en config.php, nunca en el
 * navegador ni incrustados en el código.
 *
 * Nunca lanza excepciones ni bloquea el registro: cualquier falla (token
 * sin configurar, timeout, respuesta inválida, DNI no encontrado, cuota
 * agotada) devuelve null y el usuario sigue digitando a mano.
 */
class ReniecService
{
    public static function buscarPorDni(string $dni): ?array
    {
        if (!preg_match('/^\d{8}$/', $dni) || !function_exists('curl_init')) {
            return null;
        }

        try {
            $config = require __DIR__ . '/../../config/config.php';
            $reniec = $config['reniec'] ?? [];
            $url = trim($reniec['url'] ?? '');
            $token = trim($reniec['token'] ?? '');
            $timeout = (int) ($reniec['timeout'] ?? 5);

            if ($url === '' || $token === '') {
                return null; // integración no configurada: se digita a mano
            }

            $ch = curl_init(str_replace('{dni}', $dni, $url));
            if ($ch === false) {
                return null;
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json',
                ],
            ]);

            $respuesta = curl_exec($ch);
            $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errorCurl = curl_error($ch);
            // curl_close() quedó en desuso desde PHP 8.0 (no hace nada: el
            // handle se libera solo al salir de alcance); se omite a propósito.

            if ($respuesta === false || $errorCurl !== '' || $codigoHttp !== 200) {
                return null;
            }

            $datos = json_decode((string) $respuesta, true);
            if (!is_array($datos)) {
                return null;
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
                return null;
            }

            return [
                'apellido_paterno' => $apellidoPaterno !== '' ? $apellidoPaterno : null,
                'apellido_materno' => $apellidoMaterno !== '' ? $apellidoMaterno : null,
                'nombres'          => $nombres !== '' ? $nombres : null,
            ];
        } catch (Throwable $e) {
            error_log('RENIEC: fallo en la consulta (' . $e->getMessage() . '), se continúa sin bloquear.');

            return null;
        }
    }
}
