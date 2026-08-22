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

// Cotejo de Enfermedad de Chagas (B57), sección "III. Datos del domicilio":
// "Nombre de zona" no tenía ningún campo equivalente -- ni siquiera
// 'localidad' (conviven sin solaparse en A37.0, que ya usa detalle_domicilio
// completo junto con 'localidad' visible). Hermano de 'tipo_zona' dentro del
// bloque "detalle_domicilio" (opt-in vía enfermedad.detalle_domicilio,
// nuevo valor NOMBRE_ZONA en DETALLE_DOMICILIO_VALIDO).
addColIfNotExists($pdo, 'persona', 'nombre_zona', 'VARCHAR(160) NULL AFTER tipo_zona');
