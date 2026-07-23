<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CampoDef extends Model
{
    protected static string $tabla = 'campo_def';

    public static function porSeccion(int $seccionId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM campo_def WHERE seccion_id = :sec ORDER BY orden, id'
        );
        $consulta->execute(['sec' => $seccionId]);

        return $consulta->fetchAll();
    }

    /**
     * Todos los campos de todas las secciones de una enfermedad, indexados por id.
     * Útil para revalidar en el servidor sin confiar en lo que envía el cliente.
     */
    public static function porEnfermedad(int $enfermedadId): array
    {
        $sql = 'SELECT cd.*, sd.depende_de AS seccion_depende_de, sd.valor_activador AS seccion_valor_activador
                  FROM campo_def cd
                  JOIN seccion_def sd ON sd.id = cd.seccion_id
                 WHERE sd.enfermedad_id = :enf
                 ORDER BY sd.orden, sd.id, cd.orden, cd.id';

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute(['enf' => $enfermedadId]);

        $campos = [];
        foreach ($consulta->fetchAll() as $campo) {
            $campos[(int) $campo['id']] = $campo;
        }

        return $campos;
    }
}
