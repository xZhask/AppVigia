<?php
namespace App\Models;

use App\Core\Database;

/**
 * Registro y bloqueo temporal de intentos de inicio de sesión. Cuenta por
 * correo y por IP por separado (alguien puede probar muchos correos desde
 * una misma IP, o el mismo correo desde IPs distintas) para que ninguna de
 * las dos rutas sirva de atajo.
 */
class LoginIntento
{
    public const MAX_INTENTOS = 5;
    private const VENTANA_MINUTOS = 15;

    public static function registrar(string $email, string $ip, bool $exitoso): void
    {
        $consulta = Database::conexion()->prepare(
            'INSERT INTO login_intento (email, ip, exitoso) VALUES (:email, :ip, :exitoso)'
        );
        $consulta->execute([
            'email'   => mb_strtolower($email),
            'ip'      => $ip,
            'exitoso' => $exitoso ? 1 : 0,
        ]);
    }

    public static function estaBloqueado(string $email, string $ip): bool
    {
        return self::fallosRecientes('email', mb_strtolower($email)) >= self::MAX_INTENTOS
            || self::fallosRecientes('ip', $ip) >= self::MAX_INTENTOS;
    }

    /**
     * Cuántos intentos quedan antes del bloqueo temporal. Usa el mayor de
     * los dos contadores (correo, IP), que es el que realmente determina
     * cuándo se bloqueará el siguiente intento.
     */
    public static function intentosRestantes(string $email, string $ip): int
    {
        $usados = max(
            self::fallosRecientes('email', mb_strtolower($email)),
            self::fallosRecientes('ip', $ip)
        );

        return max(0, self::MAX_INTENTOS - $usados);
    }

    private static function fallosRecientes(string $columna, string $valor): int
    {
        // $columna nunca viene del usuario: siempre 'email' o 'ip', literal
        // en las llamadas de esta misma clase.
        $sql = "SELECT COUNT(*) FROM login_intento
                 WHERE $columna = :valor AND exitoso = 0
                   AND fecha > (NOW() - INTERVAL " . self::VENTANA_MINUTOS . " MINUTE)";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute(['valor' => $valor]);

        return (int) $consulta->fetchColumn();
    }
}
