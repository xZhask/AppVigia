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

// Hallazgo del cotejo de B04X (2026-08-29): esta BD local no tenía 9
// columnas de caso_contacto que sql/01_esquema_actual.sql y
// CasosController::COLUMNAS_TABLA_HIJA_VALIDAS['caso_contacto'] ya daban
// por existentes (fecha_contacto, lugar_contacto, fecha_inicio_erupcion,
// vacunado_72h, dosis_recibidas, fecha_colecta_heces, fecha_envio,
// fecha_resultado, resultado_aislamiento) -- se agregaron a la
// documentación/código en algún momento sin una migración correspondiente
// acá. Sin esto, cualquier ficha que declare esas columnas en
// columnas_tablas_hija.caso_contacto (varias ya lo hacen) fallaría al
// guardar un contacto con esos datos. Definiciones copiadas tal cual del
// CREATE TABLE de sql/01_esquema_actual.sql.
addColIfNotExists($pdo, 'caso_contacto', 'fecha_contacto', 'DATE NULL AFTER celular');
addColIfNotExists($pdo, 'caso_contacto', 'lugar_contacto', 'VARCHAR(160) NULL AFTER fecha_contacto');
addColIfNotExists($pdo, 'caso_contacto', 'fecha_inicio_erupcion', 'DATE NULL AFTER lugar_contacto');
addColIfNotExists($pdo, 'caso_contacto', 'vacunado_72h', "ENUM('SI','NO','DESCONOCIDO') NULL AFTER fecha_inicio_erupcion");
addColIfNotExists($pdo, 'caso_contacto', 'dosis_recibidas', 'VARCHAR(30) NULL AFTER vacunado_72h');
addColIfNotExists($pdo, 'caso_contacto', 'fecha_colecta_heces', 'DATE NULL AFTER dosis_recibidas');
addColIfNotExists($pdo, 'caso_contacto', 'fecha_envio', 'DATE NULL AFTER fecha_colecta_heces');
addColIfNotExists($pdo, 'caso_contacto', 'fecha_resultado', 'DATE NULL AFTER fecha_envio');
addColIfNotExists($pdo, 'caso_contacto', 'resultado_aislamiento', 'VARCHAR(120) NULL AFTER fecha_resultado');
