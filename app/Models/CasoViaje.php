<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoViaje extends Model
{
    protected static string $tabla = 'caso_viaje';

    /**
     * Reemplaza todas las filas de viajes de un caso. $filas ya validada:
     * cada elemento es ['pais','fecha_salida','fecha_retorno'] (fechas ISO o null).
     * `pais` se usa como "lugar visitado" libre (nacional o internacional);
     * no se normaliza contra distrito_id en esta fase.
     * Debe ejecutarse dentro de la transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_viaje WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $consulta = $pdo->prepare(
            'INSERT INTO caso_viaje (caso_id, pais, fecha_salida, fecha_retorno)
             VALUES (:caso, :pais, :fecha_salida, :fecha_retorno)'
        );

        foreach ($filas as $fila) {
            $consulta->execute([
                'caso'          => $casoId,
                'pais'          => $fila['pais'],
                'fecha_salida'  => $fila['fecha_salida'],
                'fecha_retorno' => $fila['fecha_retorno'],
            ]);
        }
    }

    public static function porCaso(int $casoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM caso_viaje WHERE caso_id = :caso ORDER BY id'
        );
        $consulta->execute(['caso' => $casoId]);

        return $consulta->fetchAll();
    }
}
