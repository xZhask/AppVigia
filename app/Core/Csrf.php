<?php
namespace App\Core;

class Csrf
{
    private const CLAVE_SESION = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::CLAVE_SESION])) {
            $_SESSION[self::CLAVE_SESION] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CLAVE_SESION];
    }

    public static function campoOculto(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token()) . '">';
    }

    public static function valido(?string $tokenRecibido): bool
    {
        if (empty($_SESSION[self::CLAVE_SESION]) || empty($tokenRecibido)) {
            return false;
        }

        return hash_equals($_SESSION[self::CLAVE_SESION], $tokenRecibido);
    }
}
