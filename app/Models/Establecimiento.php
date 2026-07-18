<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Establecimiento extends Model
{
    protected static string $tabla = 'establecimiento';

    public static function conRedYUbicacion(): array
    {
        $sql = 'SELECT es.*, r.nombre AS red_nombre, d.nombre AS distrito_nombre
                  FROM establecimiento es
             LEFT JOIN red_salud r ON r.id = es.red_id
             LEFT JOIN distrito d  ON d.id = es.distrito_id
              ORDER BY es.nombre';

        return Database::conexion()->query($sql)->fetchAll();
    }
}
