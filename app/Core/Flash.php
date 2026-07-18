<?php
namespace App\Core;

class Flash
{
    public static function set(string $mensaje): void
    {
        $_SESSION['_flash'] = $mensaje;
    }

    public static function obtener(): ?string
    {
        $mensaje = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);

        return $mensaje;
    }
}
