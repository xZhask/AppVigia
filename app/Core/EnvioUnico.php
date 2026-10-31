<?php
namespace App\Core;

/**
 * Token de un solo uso para los formularios que crean registros (2026-10-31:
 * doble clic en "Registrar ficha" creaba dos casos). El formulario lleva un
 * token nuevo; al crear, el controlador lo marca con el destino del registro
 * creado, y un segundo envío con el mismo token se redirige ahí sin volver a
 * guardar. Funciona porque PHP bloquea la sesión durante cada petición: el
 * segundo envío espera a que termine el primero y ya ve el token marcado.
 *
 * Complementa a public/js/envio-unico.js (bloqueo del botón en el navegador),
 * que no alcanza a un reintento de red ni a un doble Enter muy rápido.
 */
class EnvioUnico
{
    private const CLAVE_SESION = '_envios';
    private const CAMPO = 'envio_token';
    private const MAX_TOKENS = 30;

    public static function campoOculto(): string
    {
        $token = bin2hex(random_bytes(16));
        $tokens = $_SESSION[self::CLAVE_SESION] ?? [];
        $tokens[$token] = null;
        // Uno por formulario abierto: se conservan los más recientes.
        $_SESSION[self::CLAVE_SESION] = array_slice($tokens, -self::MAX_TOKENS, null, true);

        return '<input type="hidden" name="' . self::CAMPO . '" value="' . $token . '">';
    }

    /**
     * Si este envío repite uno que ya creó su registro, avisa y redirige al
     * registro creado (termina la petición). Si no, no hace nada.
     */
    public static function cortarSiRepetido(): void
    {
        $previo = $_SESSION[self::CLAVE_SESION][self::tokenRecibido()] ?? null;
        if ($previo === null) {
            return;
        }
        Flash::set($previo['mensaje']);
        header('Location: ' . $previo['destino']);
        exit;
    }

    /** Marca el token del envío actual con el registro que creó. */
    public static function registrado(string $destino, string $mensaje): void
    {
        $token = self::tokenRecibido();
        if ($token !== '' && array_key_exists($token, $_SESSION[self::CLAVE_SESION] ?? [])) {
            $_SESSION[self::CLAVE_SESION][$token] = ['destino' => $destino, 'mensaje' => $mensaje];
        }
    }

    private static function tokenRecibido(): string
    {
        $token = $_POST[self::CAMPO] ?? '';
        return is_string($token) ? $token : '';
    }
}
