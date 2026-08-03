<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoMuestra extends Model
{
    protected static string $tabla = 'caso_muestra';

    /**
     * Reemplaza todas las filas de muestras de laboratorio de un caso. $filas
     * ya validada: cada elemento es ['tipo_muestra','tipo_prueba',
     * 'recibio_antibiotico','resultado','fecha_toma','fecha_result'].
     * `recibio_antibiotico` es opcional (NULL si la ficha no lo pide).
     * Debe ejecutarse dentro de la transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_muestra WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $consulta = $pdo->prepare(
            'INSERT INTO caso_muestra (
                caso_id, tipo_muestra, tipo_prueba, recibio_antibiotico, resultado, 
                fecha_toma, fecha_result, fecha_envio_ins, agente_aislado, observaciones,
                numero_muestra, fecha_recepcion_ins, resultado_pcr, fecha_result_pcr,
                genotipo, resultado_igm, fecha_result_igm, resultado_igg, fecha_result_igg,
                titulacion
             ) VALUES (
                :caso, :tipo_muestra, :tipo_prueba, :recibio_antibiotico, :resultado,
                :fecha_toma, :fecha_result, :fecha_envio_ins, :agente_aislado, :observaciones,
                :numero_muestra, :fecha_recepcion_ins, :resultado_pcr, :fecha_result_pcr,
                :genotipo, :resultado_igm, :fecha_result_igm, :resultado_igg, :fecha_result_igg,
                :titulacion
             )'
        );

        foreach ($filas as $fila) {
            $consulta->execute([
                'caso'                => $casoId,
                'tipo_muestra'        => $fila['tipo_muestra'] ?? '',
                'tipo_prueba'         => $fila['tipo_prueba'] ?? '',
                'recibio_antibiotico' => $fila['recibio_antibiotico'] ?? null,
                'resultado'           => $fila['resultado'] ?? '',
                'fecha_toma'          => $fila['fecha_toma'] ?? null,
                'fecha_result'        => $fila['fecha_result'] ?? null,
                'fecha_envio_ins'     => $fila['fecha_envio_ins'] ?? null,
                'agente_aislado'      => $fila['agente_aislado'] ?? null,
                'observaciones'       => $fila['observaciones'] ?? null,
                'numero_muestra'      => $fila['numero_muestra'] ?? 1,
                'fecha_recepcion_ins' => $fila['fecha_recepcion_ins'] ?? null,
                'resultado_pcr'       => $fila['resultado_pcr'] ?? null,
                'fecha_result_pcr'    => $fila['fecha_result_pcr'] ?? null,
                'genotipo'            => $fila['genotipo'] ?? null,
                'resultado_igm'       => $fila['resultado_igm'] ?? null,
                'fecha_result_igm'    => $fila['fecha_result_igm'] ?? null,
                'resultado_igg'       => $fila['resultado_igg'] ?? null,
                'fecha_result_igg'    => $fila['fecha_result_igg'] ?? null,
                'titulacion'          => $fila['titulacion'] ?? null,
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
