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

// Cotejo de Viruela del mono/Mpox (B04X), ítem 33 del PDF ("Coloque el N°
// según tipo de exposición*"): checklist de 6 códigos (1. Por contacto
// íntimo ... 6. Otro) por cada contacto registrado -- se guarda como lista
// de códigos separados por coma (ej. "1,4"), igual de simple que otras
// columnas de texto libre de caso_contacto; no se normaliza a una tabla
// aparte porque son como máximo 6 valores fijos, sin datos adicionales por
// código. "tipo_exposicion_otro" es el texto libre de "6. Otro".
addColIfNotExists($pdo, 'caso_contacto', 'tipo_exposicion', 'VARCHAR(30) NULL AFTER lugar_contacto');
addColIfNotExists($pdo, 'caso_contacto', 'tipo_exposicion_otro', 'VARCHAR(160) NULL AFTER tipo_exposicion');
