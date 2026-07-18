<?php
namespace App\Models;

use App\Core\Database;

class CasoValor
{
    /**
     * Guarda un valor por cada campo dinámico respondido. $valores va indexado
     * por campo_def_id => valor (string).
     */
    public static function guardarTodos(int $casoId, array $valores): void
    {
        if (empty($valores)) {
            return;
        }

        $consulta = Database::conexion()->prepare(
            'INSERT INTO caso_valor (caso_id, campo_def_id, valor) VALUES (:caso, :campo, :valor)'
        );

        foreach ($valores as $campoDefId => $valor) {
            $consulta->execute([
                'caso'  => $casoId,
                'campo' => $campoDefId,
                'valor' => $valor,
            ]);
        }
    }

    public static function eliminarPorCaso(int $casoId): void
    {
        $consulta = Database::conexion()->prepare('DELETE FROM caso_valor WHERE caso_id = :caso');
        $consulta->execute(['caso' => $casoId]);
    }

    /**
     * Valores ya guardados de un caso, indexados por campo_def_id => valor (string).
     */
    public static function porCaso(int $casoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT campo_def_id, valor FROM caso_valor WHERE caso_id = :caso'
        );
        $consulta->execute(['caso' => $casoId]);

        $valores = [];
        foreach ($consulta->fetchAll() as $fila) {
            $valores[(int) $fila['campo_def_id']] = $fila['valor'];
        }

        return $valores;
    }
}
