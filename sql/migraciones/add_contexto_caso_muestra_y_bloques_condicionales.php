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

// Capacidad 6 (PETICION_HC_Y_LABORATORIO.md, Parte 2, ítem 43 de P35.0):
// "contexto" distingue las filas del bloque inicial (ítem 42, NULL = mismo
// comportamiento de siempre para las 13 fichas existentes) de las del
// segundo bloque condicional (ítem 43: "seguimiento", solo en casos
// confirmados de SRC) dentro de la MISMA tabla caso_muestra -- evita
// mezclar el conteo de numero_muestra de un bloque con el del otro.
addColIfNotExists($pdo, 'caso_muestra', 'contexto', "VARCHAR(20) NULL AFTER caso_id");

// bloques_condicionales: mismo patrón que columnas_contacto/columnas_muestra
// (JSON, NULL explícito si la ficha no declara nada) -- ver validarManifiesto()
// en cargar_fichas.php y CasosController::resolverBloquesCondicionales().
addColIfNotExists($pdo, 'enfermedad', 'bloques_condicionales', "LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(`bloques_condicionales`))");
