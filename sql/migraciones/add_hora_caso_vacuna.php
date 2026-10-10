<?php
/**
 * Y59.0 (ESAVI, cotejo 2026-10-10): la ficha pide "Fecha de vacunación
 * (Hs:m)". caso_vacuna.fecha es DATE; la hora va en una columna aparte,
 * opt-in por ficha vía columnas_tablas_hija.caso_vacuna ("hora").
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

try {
    $pdo->exec("ALTER TABLE caso_vacuna ADD COLUMN hora TIME NULL AFTER fecha");
    echo "Agregada columna hora a caso_vacuna.\n";
} catch (\PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column name')) {
        echo "La columna hora ya existe en caso_vacuna.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
