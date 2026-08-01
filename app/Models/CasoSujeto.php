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
    /**
     * Columnas de datos de caso_sujeto (sin id/caso_id/rol, que se manejan
     * aparte). Debe incluir toda columna que columnasSujeto() pueda declarar
     * (ver COLUMNAS_SUJETO_VALIDAS en cargar_fichas.php, misma lista) más
     * persona_id, que no pasa por el manifiesto -- lo arma el controlador
     * directamente para el rol principal.
     */
    private const COLUMNAS_DATOS = ['persona_id', 'apellidos', 'nombres', 'doc', 'tipo_doc', 'sexo', 'edad', 'fecha_nacimiento', 'nacionalidad', 'ocupacion', 'distrito_id', 'direccion'];

    public static function guardarSujetos(int $casoId, array $sujetos): void
    {
        $db = Database::conexion();

        $stmtLimpiar = $db->prepare('DELETE FROM caso_sujeto WHERE caso_id = :caso');
        $stmtLimpiar->execute(['caso' => $casoId]);

        $marcadores = implode(', ', array_map(fn($c) => ":{$c}", self::COLUMNAS_DATOS));
        $stmtInsert = $db->prepare(
            'INSERT INTO caso_sujeto (caso_id, rol, ' . implode(', ', self::COLUMNAS_DATOS) . ')
             VALUES (:caso, :rol, ' . $marcadores . ')'
        );

        foreach ($sujetos as $rol => $datos) {
            $parametros = ['caso' => $casoId, 'rol' => $rol];
            foreach (self::COLUMNAS_DATOS as $columna) {
                $parametros[$columna] = $datos[$columna] ?? null;
            }
            $stmtInsert->execute($parametros);
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
