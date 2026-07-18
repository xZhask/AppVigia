<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class UnidadPnp extends Model
{
    protected static string $tabla = 'unidad_pnp';

    public static function conUbicacion(): array
    {
        $sql = 'SELECT u.*, d.nombre AS distrito_nombre
                  FROM unidad_pnp u
             LEFT JOIN distrito d ON d.id = u.distrito_id
              ORDER BY u.nombre';

        return Database::conexion()->query($sql)->fetchAll();
    }
}
