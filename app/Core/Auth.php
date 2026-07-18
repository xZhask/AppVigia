<?php
namespace App\Core;

use App\Models\Usuario;

class Auth
{
    public static function intentarLogin(string $email, string $clave): bool
    {
        $fila = Usuario::buscarPorEmail($email);

        if (!$fila || (int) $fila['activo'] !== 1) {
            return false;
        }

        if (!password_verify($clave, $fila['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id'                 => (int) $fila['id'],
            'nombre'             => $fila['nombre'],
            'email'              => $fila['email'],
            'rol'                => $fila['rol'],
            'establecimiento_id' => $fila['establecimiento_id'] !== null ? (int) $fila['establecimiento_id'] : null,
        ];

        return true;
    }

    public static function cerrarSesion(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function estaAutenticado(): bool
    {
        return isset($_SESSION['usuario']);
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function tieneRol(string ...$roles): bool
    {
        $usuario = self::usuario();
        return $usuario !== null && in_array($usuario['rol'], $roles, true);
    }

    public static function exigirRol(string ...$roles): void
    {
        if (!self::tieneRol(...$roles)) {
            http_response_code(403);
            require __DIR__ . '/../Views/403.php';
            exit;
        }
    }
}
