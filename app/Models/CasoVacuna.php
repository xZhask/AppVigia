<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoVacuna extends Model
{
    protected static string $tabla = 'caso_vacuna';

    /**
     * Reemplaza todas las filas de antecedentes vacunales de un caso. $filas
     * ya validada: cada elemento es ['vacuna','dosis','fecha'] (fecha ISO o null).
     * Debe ejecutarse dentro de la transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_vacuna WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $consulta = $pdo->prepare(
            'INSERT INTO caso_vacuna (caso_id, vacuna, dosis, fecha)
             VALUES (:caso, :vacuna, :dosis, :fecha)'
        );

        foreach ($filas as $fila) {
            $consulta->execute([
                'caso'   => $casoId,
                'vacuna' => $fila['vacuna'],
                'dosis'  => $fila['dosis'],
                'fecha'  => $fila['fecha'],
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
