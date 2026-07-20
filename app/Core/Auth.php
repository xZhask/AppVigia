<?php
namespace App\Core;

use App\Models\Usuario;

class Auth
{
    // Hash bcrypt señuelo: sin esto, un correo inexistente devuelve false
    // antes de llamar a password_verify(), y esa diferencia de tiempo deja
    // adivinar por cronometraje qué correos sí están registrados. Se compara
    // contra este hash igual, aunque el resultado nunca pueda ser válido, así
    // el tiempo de respuesta no depende de si el correo existe.
    private const HASH_SENUELO = '$2y$12$OEI7VlDoim5KlyTQFEY6ouu7WghSAU3MePH5DQRK0yI/iHLnZ.TiC';

    public static function intentarLogin(string $email, string $clave): bool
    {
        $fila = Usuario::buscarPorEmail($email);
        $existeYActivo = $fila && (int) $fila['activo'] === 1;
        $hashParaComparar = $existeYActivo ? $fila['password_hash'] : self::HASH_SENUELO;
        $claveValida = password_verify($clave, $hashParaComparar);

        if (!$existeYActivo || !$claveValida) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id'                     => (int) $fila['id'],
            'nombre'                 => $fila['nombre'],
            'email'                  => $fila['email'],
            'rol'                    => $fila['rol'],
            'establecimiento_id'     => $fila['establecimiento_id'] !== null ? (int) $fila['establecimiento_id'] : null,
            'establecimiento_nombre' => $fila['establecimiento_nombre'],
            'persona_id'             => $fila['persona_id'] !== null ? (int) $fila['persona_id'] : null,
            'perfil_incompleto'      => (int) ($fila['perfil_incompleto'] ?? 0),
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
