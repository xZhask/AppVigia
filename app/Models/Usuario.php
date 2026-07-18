<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Usuario extends Model
{
    protected static string $tabla = 'usuario';

    public static function buscarPorEmail(string $email): ?array
    {
        $consulta = Database::conexion()->prepare('SELECT * FROM usuario WHERE email = :email');
        $consulta->execute(['email' => $email]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    public static function existeEmail(string $email, ?int $excluirId = null): bool
    {
        $sql = 'SELECT id FROM usuario WHERE email = :email';
        $parametros = ['email' => $email];

        if ($excluirId !== null) {
            $sql .= ' AND id != :id';
            $parametros['id'] = $excluirId;
        }

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        return (bool) $consulta->fetch();
    }

    public static function conEstablecimiento(): array
    {
        $sql = 'SELECT u.*, e.nombre AS establecimiento_nombre
                  FROM usuario u
             LEFT JOIN establecimiento e ON e.id = u.establecimiento_id
              ORDER BY u.nombre';

        return Database::conexion()->query($sql)->fetchAll();
    }
}
