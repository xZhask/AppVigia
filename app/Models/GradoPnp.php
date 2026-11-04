<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class GradoPnp extends Model
{
    protected static string $tabla = 'grado_pnp';

    /**
     * Cadete y Alumno (2026-11-04): no son grados sino el tipo de la condición
     * "Personal en formación". Siguen en esta tabla por su abreviatura y nivel.
     */
    public static function esDeFormacion(array $grado): bool
    {
        return in_array($grado['nivel'] ?? '', ['CADETE', 'ALUMNO'], true);
    }

    /**
     * Grados que se ofrecen a un efectivo. Empleado civil salió de la lista el
     * 2026-11-04 (pedido del usuario); su fila sigue por quien ya lo tuviera.
     */
    public static function esDelEscalafon(array $grado): bool
    {
        return !self::esDeFormacion($grado) && ($grado['nivel'] ?? '') !== 'EMPLEADO_CIVIL';
    }

    public static function buscarPorAbreviatura(string $abreviatura): ?array
    {
        $consulta = Database::conexion()->prepare('SELECT * FROM grado_pnp WHERE abreviatura = :abrev');
        $consulta->execute(['abrev' => $abreviatura]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }
}
