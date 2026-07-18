<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Paciente extends Model
{
    protected static string $tabla = 'paciente';

    public static function buscarPorDocumento(string $tipoDoc, string $numDoc): ?array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM paciente WHERE tipo_doc = :tipo AND num_doc = :num'
        );
        $consulta->execute(['tipo' => $tipoDoc, 'num' => $numDoc]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }
}
