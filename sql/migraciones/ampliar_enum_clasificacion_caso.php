<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// caso.clasificacion es un ENUM real en MySQL, no un varchar: cualquier valor
// que CATALOGO_CLASIFICACION (app/Core/ayudantes.php) ofrezca y el ENUM no
// tenga se rechaza al guardar (sql_mode trae STRICT_TRANS_TABLES en este
// entorno, asi que es un error duro, no un truncado silencioso).
//
// 1) COMPATIBLE -- cotejo de EDA grave / colera (A00), "VI. CLASIFICACION"
//    de la pag. 51: el PDF ofrece Sospechoso / Probable / Confirmado /
//    Compatible / Caso descartado. Es la primera ficha que pide "Compatible".
//
// 2) DIRECTA/INDIRECTA/INCIDENTAL/POR_DETERMINAR -- BUG PREEXISTENTE hallado
//    durante ese mismo cotejo (2026-09-07): O95 (muerte materna) declara esos
//    4 valores en enfermedad.opciones_clasificacion desde su propio cotejo,
//    y CATALOGO_CLASIFICACION los ofrece, pero nunca se agregaron al ENUM
//    -- guardar un caso O95 con cualquiera de ellos falla. Se incluyen aca
//    porque ampliar un ENUM no puede romper ninguna fila existente (los 4
//    valores viejos siguen siendo validos y ningun dato cambia), y dejar el
//    ENUM a medias volveria a morder a la siguiente ficha.
$pdo->exec(
    "ALTER TABLE caso MODIFY clasificacion
     ENUM('SOSPECHOSO','PROBABLE','CONFIRMADO','COMPATIBLE','DESCARTADO',
          'DIRECTA','INDIRECTA','INCIDENTAL','POR_DETERMINAR')
     COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SOSPECHOSO'"
);

echo "caso.clasificacion ampliado: " . $pdo->query("SHOW COLUMNS FROM caso LIKE 'clasificacion'")->fetch()['Type'] . "\n";
