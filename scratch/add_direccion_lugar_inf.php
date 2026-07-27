<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

try {
    $pdo->exec("ALTER TABLE caso_lugar_infeccion ADD COLUMN direccion VARCHAR(255) NULL");
    echo "Agregada columna direccion a caso_lugar_infeccion.\n";
} catch (\PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column name')) {
        echo "La columna direccion ya existe en caso_lugar_infeccion.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
