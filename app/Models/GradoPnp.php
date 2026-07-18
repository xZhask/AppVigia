<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class GradoPnp extends Model
{
    protected static string $tabla = 'grado_pnp';

    public static function buscarPorAbreviatura(string $abreviatura): ?array
    {
        $consulta = Database::conexion()->prepare('SELECT * FROM grado_pnp WHERE abreviatura = :abrev');
        $consulta->execute(['abrev' => $abreviatura]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }
}
