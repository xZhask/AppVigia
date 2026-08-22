<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoViaje extends Model
{
    protected static string $tabla = 'caso_viaje';

    /**
     * Reemplaza todas las filas de viajes de un caso. $filas ya validada:
     * cada elemento es ['pais','localidad','fecha_salida','fecha_retorno'] (fechas ISO o null).
     * `pais` se usa como "lugar visitado" libre (nacional o internacional);
     * `distrito_id` (opt-in vía columnas_tablas_hija.caso_viaje, ver A97,
     * pág. 49 del PDF) sí normaliza contra el ubigeo real cuando la ficha lo
     * pide -- las demás fichas que no lo declaran simplemente no lo mandan
     * (queda NULL, mismo comportamiento que antes).
     * Debe ejecutarse dentro de la transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_viaje WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $consulta = $pdo->prepare(
            'INSERT INTO caso_viaje (caso_id, pais, localidad, distrito_id, direccion, fecha_salida, fecha_retorno, tiempo_permanencia, semana_gestacion, transporte_ida, transporte_retorno)
             VALUES (:caso, :pais, :localidad, :distrito_id, :direccion, :fecha_salida, :fecha_retorno, :tiempo_permanencia, :semana_gestacion, :transporte_ida, :transporte_retorno)'
        );

        foreach ($filas as $fila) {
            $consulta->execute([
                'caso'               => $casoId,
                'pais'               => $fila['pais'],
                'localidad'          => $fila['localidad'] ?? null,
                'distrito_id'        => $fila['distrito_id'] ?? null,
                'direccion'          => $fila['direccion'] ?? null,
                'fecha_salida'       => $fila['fecha_salida'],
                'fecha_retorno'      => $fila['fecha_retorno'],
                'tiempo_permanencia' => $fila['tiempo_permanencia'] ?? null,
                'semana_gestacion'   => $fila['semana_gestacion'] ?? null,
                'transporte_ida'     => $fila['transporte_ida'] ?? null,
                'transporte_retorno' => $fila['transporte_retorno'] ?? null,
            ]);
        }
    }

    public static function porCaso(int $casoId): array
    {
        // JOIN a distrito (cotejo B57, 2026-08-21): la vista de solo lectura
        // (fichas/ver.php) necesita el nombre legible, no solo el código de
        // distrito_id crudo -- mismo patrón que Caso::conDetalle().
        $consulta = Database::conexion()->prepare(
            'SELECT cv.*, d.nombre AS distrito_nombre
               FROM caso_viaje cv
          LEFT JOIN distrito d ON d.id = cv.distrito_id
              WHERE cv.caso_id = :caso
           ORDER BY cv.id'
        );
        $consulta->execute(['caso' => $casoId]);

        return $consulta->fetchAll();
    }
}
