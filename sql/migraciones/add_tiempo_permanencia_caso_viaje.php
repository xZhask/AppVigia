<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

function addColIfNotExists($pdo, $table, $col, $def) {
    $cols = $pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$col}'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$col} {$def}");
        echo "Añadida columna {$col} a la tabla {$table}.\n";
    } else {
        echo "Columna {$col} ya existe en {$table}.\n";
    }
}

// Cotejo A44 (Enfermedad de Carrión, pág. 42, 2026-08-18): "Viaje a
// localidades o comunidades vecinas" solo pide 3 campos por fila -- Fecha
// de viaje, Lugar, Tiempo de permanencia -- no el par fecha_salida/
// fecha_retorno + transporte que usan las demás fichas. "Tiempo de
// permanencia" (texto libre, ej. "3 días") no existía en caso_viaje.
addColIfNotExists($pdo, 'caso_viaje', 'tiempo_permanencia', 'VARCHAR(60) NULL AFTER fecha_retorno');
