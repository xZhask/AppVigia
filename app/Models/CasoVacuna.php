<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoVacuna extends Model
{
    protected static string $tabla = 'caso_vacuna';

    /**
     * Reemplaza todas las filas de antecedentes vacunales de un caso. $filas
     * ya validada: cada elemento es ['vacuna','dosis','fecha','fabricante',
     * 'lote','via','sitio','fecha_vencimiento','establecimiento'] (fechas ISO
     * o null). Las últimas 6 columnas se agregaron en
     * sql/35_fase5_ampliar_tablas_hija.sql (CIERRE_RECARGA_Y_FASE5.md Parte 2)
     * para poder retirar de campo_def los campos sueltos de vacunación de
     * ESAVI, tos ferina y difteria. Debe ejecutarse dentro de la transacción
     * abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_vacuna WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $consulta = $pdo->prepare(
            'INSERT INTO caso_vacuna (caso_id, vacuna, dosis, fecha, fabricante, lote, via, sitio, fecha_vencimiento, establecimiento)
             VALUES (:caso, :vacuna, :dosis, :fecha, :fabricante, :lote, :via, :sitio, :fecha_vencimiento, :establecimiento)'
        );

        foreach ($filas as $fila) {
            $consulta->execute([
                'caso'              => $casoId,
                'vacuna'            => $fila['vacuna'],
                'dosis'             => $fila['dosis'],
                'fecha'             => $fila['fecha'],
                'fabricante'        => $fila['fabricante'] ?? null,
                'lote'              => $fila['lote'] ?? null,
                'via'               => $fila['via'] ?? null,
                'sitio'             => $fila['sitio'] ?? null,
                'fecha_vencimiento' => $fila['fecha_vencimiento'] ?? null,
                'establecimiento'   => $fila['establecimiento'] ?? null,
            ]);
        }
    }

    public static function porCaso(int $casoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM caso_vacuna WHERE caso_id = :caso ORDER BY id'
        );
        $consulta->execute(['caso' => $casoId]);

        return $consulta->fetchAll();
    }
}
