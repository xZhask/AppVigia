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
}
