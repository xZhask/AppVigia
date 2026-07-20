<?php
namespace App\Core;

use Throwable;

/**
 * Envío de correo mínimo, vía mail() de PHP. Mismo patrón que
 * ReniecService: si no hay remitente configurado (o mail() no puede
 * confirmar el envío, algo normal en un entorno de desarrollo sin MTA), no
 * lanza error ni bloquea al llamador — solo registra el intento en el log.
 * Quien llama a este servicio (p. ej. "olvidé mi contraseña") no debe
 * comportarse distinto según el resultado: eso filtraría información.
 */
class MailService
{
    public static function enviar(string $para, string $asunto, string $cuerpoTexto): bool
    {
        try {
            $config = require __DIR__ . '/../../config/config.php';
            $desde = trim($config['mail']['desde'] ?? '');

            if ($desde === '') {
                error_log("MailService: sin remitente configurado, no se envía a $para. Asunto: $asunto");
                return false;
            }

            $cabeceras = "From: $desde\r\nContent-Type: text/plain; charset=UTF-8";
            $asuntoCodificado = '=?UTF-8?B?' . base64_encode($asunto) . '?=';
            $enviado = @mail($para, $asuntoCodificado, $cuerpoTexto, $cabeceras);

            if (!$enviado) {
                error_log("MailService: mail() no pudo enviar a $para.");
            }

            return $enviado;
        } catch (Throwable $e) {
            error_log('MailService: fallo al enviar (' . $e->getMessage() . ').');
            return false;
        }
    }
}
