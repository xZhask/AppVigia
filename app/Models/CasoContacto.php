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
     * 'vacunado','fecha_vacunacion','profilaxis','doc','celular',
     * 'fecha_contacto','lugar_contacto','fecha_inicio_erupcion',
     * 'vacunado_72h']. Todas las columnas clínicas son opcionales: NULL si
     * la ficha no las pide (censo de contactos, punto 6 de
     * AUDITORIA_FICHA_DIFTERIA.md; las últimas 4 son de la cadena de
     * transmisión de sarampión, CIERRE_RECARGA_Y_FASE5.md Parte 1.4). Debe
     * ejecutarse dentro de la transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_contacto WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $consulta = $pdo->prepare(
            'INSERT INTO caso_contacto (caso_id, nombres, parentesco, edad, sexo, vacunado, fecha_vacunacion, profilaxis, doc, celular, fecha_contacto, lugar_contacto, fecha_inicio_erupcion, vacunado_72h)
             VALUES (:caso, :nombres, :parentesco, :edad, :sexo, :vacunado, :fecha_vacunacion, :profilaxis, :doc, :celular, :fecha_contacto, :lugar_contacto, :fecha_inicio_erupcion, :vacunado_72h)'
        );

        foreach ($filas as $fila) {
            $consulta->execute([
                'caso'                  => $casoId,
                'nombres'               => $fila['nombres'],
                'parentesco'            => $fila['parentesco'],
                'edad'                  => $fila['edad'] ?? null,
                'sexo'                  => $fila['sexo'] ?? null,
                'vacunado'              => $fila['vacunado'] ?? null,
                'fecha_vacunacion'      => $fila['fecha_vacunacion'] ?? null,
                'profilaxis'            => $fila['profilaxis'] ?? null,
                'doc'                   => $fila['doc'],
                'celular'               => $fila['celular'],
                'fecha_contacto'        => $fila['fecha_contacto'] ?? null,
                'lugar_contacto'        => $fila['lugar_contacto'] ?? null,
                'fecha_inicio_erupcion' => $fila['fecha_inicio_erupcion'] ?? null,
                'vacunado_72h'          => $fila['vacunado_72h'] ?? null,
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
