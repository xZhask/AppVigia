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

// PETICION_P35_RUBEOLA_CONGENITA.md Fase 5.1: la tabla de viajes de la
// madre (ítem 33 del PDF de P35.0) trae "Semana de gestación" además de
// las columnas que caso_viaje ya tenía. Repetible por viaje -- vive en
// caso_viaje, no en campo_def.
addColIfNotExists($pdo, 'caso_viaje', 'semana_gestacion', 'SMALLINT NULL AFTER fecha_retorno');
