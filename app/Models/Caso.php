<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Caso extends Model
{
    protected static string $tabla = 'caso';

    public static function contarTodos(): int
    {
        return (int) Database::conexion()->query('SELECT COUNT(*) FROM caso')->fetchColumn();
    }

    /**
     * Inserta el caso y le asigna un código correlativo derivado de su propio id
     * (F-00001, F-00002...), evitando condiciones de carrera con contadores aparte.
     * Debe ejecutarse dentro de una transacción abierta por el llamador.
     */
    public static function crearConCodigo(array $datos): int
    {
        $id = self::crear($datos + ['codigo' => 'TMP-' . bin2hex(random_bytes(8))]);

        $codigo = sprintf('F-%05d', $id);
        self::actualizar($id, ['codigo' => $codigo]);

        return $id;
    }
}
