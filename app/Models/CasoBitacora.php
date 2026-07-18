<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoBitacora extends Model
{
    protected static string $tabla = 'caso_bitacora';

    public static function registrar(int $casoId, ?int $usuarioId, string $accion, ?string $detalle = null): void
    {
        self::crear([
            'caso_id'    => $casoId,
            'usuario_id' => $usuarioId,
            'accion'     => $accion,
            'detalle'    => $detalle,
        ]);
    }

    public static function porCaso(int $casoId): array
    {
        $sql = 'SELECT b.*, u.nombre AS usuario_nombre
                  FROM caso_bitacora b
             LEFT JOIN usuario u ON u.id = b.usuario_id
                 WHERE b.caso_id = :caso
              ORDER BY b.fecha DESC, b.id DESC';

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute(['caso' => $casoId]);

        return $consulta->fetchAll();
    }
}
