<?php
namespace App\Models;

use App\Core\Database;

class CasoSujeto
{
    /**
     * Guarda o actualiza los sujetos de un caso.
     * $sujetos es un array asociativo donde la clave es el rol (ej. 'CASO_INDICE', 'MADRE')
     * y el valor es un array con los datos del sujeto -- incluye opcionalmente
     * 'distrito_id'/'direccion' (PENDIENTES_POST_FASE5.md punto 4: residencia
     * habitual de la madre en muerte fetal y neonatal).
     */
    public static function guardarSujetos(int $casoId, array $sujetos): void
    {
        $db = Database::conexion();

        $stmtLimpiar = $db->prepare('DELETE FROM caso_sujeto WHERE caso_id = :caso');
        $stmtLimpiar->execute(['caso' => $casoId]);

        $stmtInsert = $db->prepare(
            'INSERT INTO caso_sujeto (caso_id, persona_id, rol, apellidos, nombres, doc, sexo, edad, distrito_id, direccion)
             VALUES (:caso, :persona, :rol, :apellidos, :nombres, :doc, :sexo, :edad, :distrito_id, :direccion)'
        );

        foreach ($sujetos as $rol => $datos) {
            $stmtInsert->execute([
                'caso'        => $casoId,
                'rol'         => $rol,
                'persona'     => $datos['persona_id'] ?? null,
                'apellidos'   => $datos['apellidos'] ?? null,
                'nombres'     => $datos['nombres'] ?? null,
                'doc'         => $datos['doc'] ?? null,
                'sexo'        => $datos['sexo'] ?? null,
                'edad'        => $datos['edad'] ?? null,
                'distrito_id' => $datos['distrito_id'] ?? null,
                'direccion'   => $datos['direccion'] ?? null,
            ]);
        }
    }

    /**
     * Obtiene todos los sujetos de un caso indexados por su rol.
     */
    public static function porCaso(int $casoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM caso_sujeto WHERE caso_id = :caso'
        );
        $consulta->execute(['caso' => $casoId]);

        $sujetos = [];
        foreach ($consulta->fetchAll() as $fila) {
            $sujetos[$fila['rol']] = $fila;
        }

        return $sujetos;
    }
}
