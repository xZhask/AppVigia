<?php
namespace App\Models;

use App\Core\Database;
use DateTime;

/**
 * Tokens de un solo uso para restablecer contraseña. Igual que con las
 * contraseñas, nunca se guarda el token en claro: solo su hash sha256. El
 * token en claro vive únicamente en el enlace que se envía por correo.
 */
class PasswordResetToken
{
    private const TTL_HORAS = 1;

    public static function crear(int $usuarioId): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiraEn = (new DateTime('+' . self::TTL_HORAS . ' hour'))->format('Y-m-d H:i:s');

        $consulta = Database::conexion()->prepare(
            'INSERT INTO password_reset_token (usuario_id, token_hash, expira_en) VALUES (:uid, :hash, :expira)'
        );
        $consulta->execute(['uid' => $usuarioId, 'hash' => $hash, 'expira' => $expiraEn]);

        return $token;
    }

    /**
     * Devuelve la fila del token si es válido (existe, no usado, no
     * expirado), o null. $token es el valor en claro que llega por la URL.
     */
    public static function validar(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM password_reset_token
              WHERE token_hash = :hash AND usado_en IS NULL AND expira_en > NOW()'
        );
        $consulta->execute(['hash' => $hash]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    public static function marcarUsado(int $id): void
    {
        $consulta = Database::conexion()->prepare(
            'UPDATE password_reset_token SET usado_en = NOW() WHERE id = :id'
        );
        $consulta->execute(['id' => $id]);
    }
}
