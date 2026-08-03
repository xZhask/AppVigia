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

// PETICION_P35_RUBEOLA_CONGENITA.md ítem 33 (tabla de viajes de la
// madre): el PDF pide "País" y "Localidad/ciudad" como columnas
// separadas. La Fase 5.1 solo agregó semana_gestacion y reusó `pais`
// para ambos conceptos ("Lugar visitado (país o ciudad)") -- se separa
// ahora. Repetible por viaje, igual que semana_gestacion.
addColIfNotExists($pdo, 'caso_viaje', 'localidad', 'VARCHAR(80) NULL AFTER pais');
