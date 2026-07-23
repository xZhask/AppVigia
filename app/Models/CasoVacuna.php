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
     * 'lote','via','sitio','fecha_vencimiento','establecimiento','adyuvante']
     * (fechas ISO o null). `vacuna`/`dosis`/`via`/`sitio`/`adyuvante` guardan
     * el `valor` de un catalogo_item compartido (PENDIENTES_POST_FASE5.md
     * punto 2), no texto libre -- mismo patrón que caso_muestra. Debe
     * ejecutarse dentro de la transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_vacuna WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $consulta = $pdo->prepare(
            'INSERT INTO caso_vacuna (caso_id, vacuna, dosis, fecha, fabricante, lote, via, sitio, fecha_vencimiento, establecimiento, adyuvante)
             VALUES (:caso, :vacuna, :dosis, :fecha, :fabricante, :lote, :via, :sitio, :fecha_vencimiento, :establecimiento, :adyuvante)'
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
                'adyuvante'         => $fila['adyuvante'] ?? null,
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
