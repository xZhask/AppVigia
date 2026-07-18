<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Departamento extends Model
{
    protected static string $tabla = 'departamento';

    public static function todosOrdenados(): array
    {
        return Database::conexion()->query('SELECT * FROM departamento ORDER BY nombre')->fetchAll();
    }
}
