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

// Entrada F (PETICION_MAPEO_Y_EDAD.md, Parte 2): al menos 9 fichas piden
// "Edad" con una unidad distinta de años (meses/días/horas/minutos), sobre
// todo para menores de 1 año -- P35.0 es la que originó la entrada. Va en
// `caso`, no en `persona`: es una foto al momento de esta notificación, no
// un atributo permanente del paciente (mismo criterio que
// fecha_inicio_sintomas). No deriva de ni sustituye a persona.fecha_nac --
// coexiste, porque el PDF pide los dos como ítems separados.
// edad_unidad es un ENUM superconjunto de las 5 unidades posibles; qué
// subconjunto ofrece cada ficha lo decide enfermedad.unidades_edad (ver
// add_unidades_edad_col.php), no esta columna.
addColIfNotExists($pdo, 'caso', 'edad_valor', "SMALLINT UNSIGNED NULL AFTER fecha_inicio_sintomas");
addColIfNotExists($pdo, 'caso', 'edad_unidad', "ENUM('ANIOS','MESES','DIAS','HORAS','MINUTOS') NULL AFTER edad_valor");
