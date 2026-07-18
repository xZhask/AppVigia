<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Distrito extends Model
{
    protected static string $tabla = 'distrito';

    public static function porProvincia(string $provinciaId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM distrito WHERE provincia_id = :prov ORDER BY nombre'
        );
        $consulta->execute(['prov' => $provinciaId]);

        return $consulta->fetchAll();
    }
}
