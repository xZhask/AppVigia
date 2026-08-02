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

// PETICION_HC_Y_LABORATORIO.md, Parte 1: "N.° de historia clínica" pasa de
// campo_def por ficha (P35.0, O95 -- este último hardcodeado en
// nueva/index.php y editar.php) al núcleo declarativo. Solo para el grupo
// que lo pide en el bloque de IDENTIDAD del PDF (junto a Tipo/N.° de
// documento): P35.0, O95, A44. A37.0/B05/Y59.0/A80/A00 lo piden dentro de
// su bloque de HOSPITALIZACIÓN -- pregunta distinta del PDF con la misma
// etiqueta, fuera de este alcance a propósito (ver PENDIENTES.md).
addColIfNotExists($pdo, 'persona', 'n_historia_clinica', 'VARCHAR(30) NULL');
