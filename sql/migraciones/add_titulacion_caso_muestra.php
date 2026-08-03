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

// PETICION_HC_Y_LABORATORIO.md, Parte 2 (Fase D1, bloque declarativo, punto
// 3): columna de serología que faltaba junto a resultado_igm/resultado_igg
// -- mismo ancho que esas, varchar libre (no es un ENUM cerrado, el PDF de
// P35.0 pide un valor de dilución tipo "1:80", no una lista fija).
addColIfNotExists($pdo, 'caso_muestra', 'titulacion', 'VARCHAR(60) NULL AFTER fecha_result_igg');
