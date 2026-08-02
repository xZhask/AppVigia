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

// PENDIENTES.md ítem E: "Referencia para localizar" pasa de ser un
// campo_def propio de B05 (b05_referencia_para_localizar_cerca_de_iglesia_fundo_co)
// al núcleo declarativo compartido -- mismo ancho que 'direccion', su
// campo hermano más cercano.
addColIfNotExists($pdo, 'persona', 'referencia_localizar', 'VARCHAR(160) NULL AFTER direccion');
