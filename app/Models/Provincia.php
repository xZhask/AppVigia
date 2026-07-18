<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Provincia extends Model
{
    protected static string $tabla = 'provincia';

    public static function porDepartamento(string $departamentoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM provincia WHERE departamento_id = :dep ORDER BY nombre'
        );
        $consulta->execute(['dep' => $departamentoId]);

        return $consulta->fetchAll();
    }
}
