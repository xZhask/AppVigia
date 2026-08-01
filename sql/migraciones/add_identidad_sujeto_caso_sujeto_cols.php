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

// PETICION_P35_RUBEOLA_CONGENITA.md Fase 2: identidad de sujetos
// secundarios (hoy MADRE en P35.0 y P96) capturada en caso_sujeto, mismo
// patrón que las columnas ya existentes (apellidos/nombres/doc/sexo/
// edad/distrito_id/direccion). tipo_doc es VARCHAR libre (no ENUM): el
// select del paciente ya postea 'OTRO', que no existe en el ENUM de
// persona.tipo_doc -- no se replica esa inconsistencia aquí.
addColIfNotExists($pdo, 'caso_sujeto', 'tipo_doc', "VARCHAR(20) NULL AFTER doc");
addColIfNotExists($pdo, 'caso_sujeto', 'fecha_nacimiento', "DATE NULL AFTER edad");
addColIfNotExists($pdo, 'caso_sujeto', 'nacionalidad', "VARCHAR(60) NULL AFTER fecha_nacimiento");
addColIfNotExists($pdo, 'caso_sujeto', 'ocupacion', "VARCHAR(100) NULL AFTER nacionalidad");
