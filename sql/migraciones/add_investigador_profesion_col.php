<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$hasCol = $pdo->query("SHOW COLUMNS FROM caso LIKE 'investigador_profesion'")->fetchColumn();
if (!$hasCol) {
    $pdo->query("ALTER TABLE caso ADD COLUMN investigador_profesion VARCHAR(100) NULL AFTER investigador_cargo");
    echo "Columna investigador_profesion agregada a la tabla caso.\n";
} else {
    echo "Columna investigador_profesion ya existe en caso.\n";
}
