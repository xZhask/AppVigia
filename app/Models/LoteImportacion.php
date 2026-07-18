<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class LoteImportacion extends Model
{
    protected static string $tabla = 'lote_importacion';

    public static function todosConDetalle(): array
    {
        $sql = 'SELECT l.*, e.nombre AS enfermedad_nombre, est.nombre AS establecimiento_nombre, u.nombre AS usuario_nombre
                  FROM lote_importacion l
                  JOIN enfermedad e      ON e.id = l.enfermedad_id
                  JOIN establecimiento est ON est.id = l.establecimiento_id
                  JOIN usuario u         ON u.id = l.usuario_id
              ORDER BY l.creado_en DESC';

        return Database::conexion()->query($sql)->fetchAll();
    }
}
