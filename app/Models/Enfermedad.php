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

    /**
     * Igual que activas(), agrupadas por `grupo` y con la bandera
     * `tiene_definicion` (si ya tiene al menos una seccion_def propia), para
     * el selector de "Nueva ficha": agrupar por optgroup y avisar cuáles
     * todavía no tienen ficha definida, sin que el usuario abra un
     * formulario vacío.
     */
    public static function activasConDefinicion(): array
    {
        return Database::conexion()->query(
            "SELECT e.*, EXISTS(
                        SELECT 1 FROM seccion_def sd WHERE sd.enfermedad_id = e.id
                    ) AS tiene_definicion
               FROM enfermedad e
              WHERE e.activo = 1
           ORDER BY FIELD(e.familia, 'Inmunoprevenibles', 'Metaxénicas y zoonóticas', 'Transmisión hídrica y alimentaria', 'Materno-perinatal y transmisión vertical', 'Otros eventos bajo vigilancia'),
                    tiene_definicion DESC, 
                    e.nombre_corto"
        )->fetchAll();
    }
}
