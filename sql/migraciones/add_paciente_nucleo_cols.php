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

addColIfNotExists($pdo, 'persona', 'etnia_otra', 'VARCHAR(100) NULL AFTER etnia');
addColIfNotExists($pdo, 'persona', 'nombre_tutor', 'VARCHAR(160) NULL');
addColIfNotExists($pdo, 'persona', 'celular_tutor', 'VARCHAR(20) NULL');
addColIfNotExists($pdo, 'persona', 'trimestre_gestacion', 'VARCHAR(10) NULL AFTER semanas_gestacion');
