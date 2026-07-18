<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Enfermedad extends Model
{
    protected static string $tabla = 'enfermedad';

    public static function activas(): array
    {
        return Database::conexion()->query(
            'SELECT * FROM enfermedad WHERE activo = 1 ORDER BY nombre'
        )->fetchAll();
    }
}
