<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoContacto extends Model
{
    protected static string $tabla = 'caso_contacto';

    /**
     * Reemplaza todas las filas de contactos de un caso. $filas viene ya
     * validada: cada elemento es ['nombres','parentesco','edad','sexo',
     * 'vacunado','fecha_vacunacion','profilaxis','doc','celular']. Las
     * columnas clínicas (edad/sexo/vacunado/fecha_vacunacion/profilaxis) son
     * opcionales: NULL si la ficha no las pide (censo de contactos, punto 6
     * de AUDITORIA_FICHA_DIFTERIA.md). Debe ejecutarse dentro de la
     * transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_contacto WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $consulta = $pdo->prepare(
            'INSERT INTO caso_contacto (caso_id, nombres, parentesco, edad, sexo, vacunado, fecha_vacunacion, profilaxis, doc, celular)
             VALUES (:caso, :nombres, :parentesco, :edad, :sexo, :vacunado, :fecha_vacunacion, :profilaxis, :doc, :celular)'
        );

        foreach ($filas as $fila) {
            $consulta->execute([
                'caso'             => $casoId,
                'nombres'          => $fila['nombres'],
                'parentesco'       => $fila['parentesco'],
                'edad'             => $fila['edad'] ?? null,
                'sexo'             => $fila['sexo'] ?? null,
                'vacunado'         => $fila['vacunado'] ?? null,
                'fecha_vacunacion' => $fila['fecha_vacunacion'] ?? null,
                'profilaxis'       => $fila['profilaxis'] ?? null,
                'doc'              => $fila['doc'],
                'celular'          => $fila['celular'],
            ]);
        }
    }

    public static function porCaso(int $casoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM caso_contacto WHERE caso_id = :caso ORDER BY id'
        );
        $consulta->execute(['caso' => $casoId]);

        return $consulta->fetchAll();
    }
}
