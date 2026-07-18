<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoMuestra extends Model
{
    protected static string $tabla = 'caso_muestra';

    /**
     * Reemplaza todas las filas de muestras de laboratorio de un caso. $filas
     * ya validada: cada elemento es
     * ['tipo_muestra','tipo_prueba','resultado','fecha_toma','fecha_result'].
     * Debe ejecutarse dentro de la transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_muestra WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $consulta = $pdo->prepare(
            'INSERT INTO caso_muestra (caso_id, tipo_muestra, tipo_prueba, resultado, fecha_toma, fecha_result)
             VALUES (:caso, :tipo_muestra, :tipo_prueba, :resultado, :fecha_toma, :fecha_result)'
        );

        foreach ($filas as $fila) {
            $consulta->execute([
                'caso'         => $casoId,
                'tipo_muestra' => $fila['tipo_muestra'],
                'tipo_prueba'  => $fila['tipo_prueba'],
                'resultado'    => $fila['resultado'],
                'fecha_toma'   => $fila['fecha_toma'],
                'fecha_result' => $fila['fecha_result'],
            ]);
        }
    }

    public static function porCaso(int $casoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM caso_muestra WHERE caso_id = :caso ORDER BY id'
        );
        $consulta->execute(['caso' => $casoId]);

        return $consulta->fetchAll();
    }
}
