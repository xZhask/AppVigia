<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class SeccionDef extends Model
{
    protected static string $tabla = 'seccion_def';

    public static function porEnfermedad(int $enfermedadId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM seccion_def WHERE enfermedad_id = :enf ORDER BY orden, id'
        );
        $consulta->execute(['enf' => $enfermedadId]);

        return $consulta->fetchAll();
    }
}
