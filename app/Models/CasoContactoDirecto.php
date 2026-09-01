<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoContactoDirecto extends Model
{
    protected static string $tabla = 'caso_contacto_directo';

    /**
     * Reemplaza todos los contactos directos de un caso (B04X, ítem 35 del
     * PDF -- censo de "Parejas sexuales"/"Domiciliarios"/"Extradomiciliarios",
     * independiente del censo de caso_contacto (ítem 33). $filas: cada
     * elemento es ['nombres','parentesco','celular','doc','grupo_poblacion'],
     * 'grupo_poblacion' como lista de códigos separados por coma. Debe
     * ejecutarse dentro de la transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_contacto_directo WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $consulta = $pdo->prepare(
            'INSERT INTO caso_contacto_directo (caso_id, nombres, parentesco, celular, doc, grupo_poblacion)
             VALUES (:caso, :nombres, :parentesco, :celular, :doc, :grupo_poblacion)'
        );

        foreach ($filas as $fila) {
            $consulta->execute([
                'caso'            => $casoId,
                'nombres'         => $fila['nombres'],
                'parentesco'      => $fila['parentesco'] ?? null,
                'celular'         => $fila['celular'] ?? null,
                'doc'             => $fila['doc'] ?? null,
                'grupo_poblacion' => $fila['grupo_poblacion'] ?? null,
            ]);
        }
    }

    public static function porCaso(int $casoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM caso_contacto_directo WHERE caso_id = :caso ORDER BY id'
        );
        $consulta->execute(['caso' => $casoId]);

        return $consulta->fetchAll();
    }
}
