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

// Cotejo A44 (Enfermedad de Carrión, pág. 42, 2026-08-18): el PDF pide
// "FUR" (fecha de última regla) junto a "Gestante" y "Edad Gestacional"
// -- semanas_gestacion ya cubre esta última, pero FUR no existía en el
// núcleo compartido. Se ubica junto a sus campos hermanos de gestación,
// no junto a 'direccion'.
addColIfNotExists($pdo, 'persona', 'fur', 'DATE NULL AFTER gestante');
