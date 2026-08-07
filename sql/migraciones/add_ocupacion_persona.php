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

// Cotejo A37.0 (Tos ferina, ítem 17): "Ocupación" pasa de ser un campo_def
// ad-hoc repetido en B05, O95 (Anexo 2) y Y07 (persona agredida) al núcleo
// declarativo compartido -- mismo ancho que 'localidad', su campo hermano
// más cercano. Se ubica junto a Etnia/Pueblo étnico (misma fila del PDF en
// varias fichas), no junto a 'direccion'.
addColIfNotExists($pdo, 'persona', 'ocupacion', 'VARCHAR(120) NULL AFTER pueblo_etnico');
