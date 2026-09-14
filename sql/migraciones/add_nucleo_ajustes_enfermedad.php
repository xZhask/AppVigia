<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// enfermedad.nucleo_ajustes (P96, muerte fetal y neonatal, 2026-09-13):
// ajustes de la tarjeta de identidad y del caso que una ficha declara en el
// manifiesto y que no son omitir/incluir un campo -- "sin_documento" (la
// persona puede no tener documento: un óbito fetal), "nombres_opcionales",
// "fallecido" (todo caso de la ficha es una defunción), "titulo_persona" y
// "titulo_residencia". Objeto JSON; cargar_fichas.php valida sus claves y lo
// persiste. NULL = la ficha no declara nada, comportamiento de siempre.
$hasCol = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'nucleo_ajustes'")->fetchColumn();
if (!$hasCol) {
    $pdo->query('ALTER TABLE enfermedad ADD COLUMN nucleo_ajustes LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(nucleo_ajustes)) AFTER nucleo_condicional');
    echo "Columna nucleo_ajustes agregada a la tabla enfermedad.\n";
} else {
    echo "Columna nucleo_ajustes ya existe en enfermedad.\n";
}
