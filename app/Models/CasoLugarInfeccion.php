<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoLugarInfeccion extends Model
{
    protected static string $tabla = 'caso_lugar_infeccion';

    /**
     * Reemplaza todas las filas de lugar probable de infección de un caso.
     * $filas ya validada: cada elemento es
     * ['lugar_institucion','localidad_texto','permanencia_dias'].
     * Debe ejecutarse dentro de la transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_lugar_infeccion WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $consulta = $pdo->prepare(
            'INSERT INTO caso_lugar_infeccion (caso_id, lugar_institucion, localidad_texto, permanencia_dias)
             VALUES (:caso, :lugar_institucion, :localidad_texto, :permanencia_dias)'
        );

        foreach ($filas as $fila) {
            $consulta->execute([
                'caso'              => $casoId,
                'lugar_institucion' => $fila['lugar_institucion'],
                'localidad_texto'   => $fila['localidad_texto'],
                'permanencia_dias'  => $fila['permanencia_dias'],
            ]);
        }
    }

    public static function porCaso(int $casoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM caso_lugar_infeccion WHERE caso_id = :caso ORDER BY id'
        );
        $consulta->execute(['caso' => $casoId]);

        return $consulta->fetchAll();
    }
}
