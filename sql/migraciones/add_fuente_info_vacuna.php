<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

try {
    $pdo->exec("ALTER TABLE caso_vacuna ADD COLUMN fuente_informacion VARCHAR(80) NULL");
    echo "Agregada columna fuente_informacion a caso_vacuna.\n";
} catch (\PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column name')) {
        echo "La columna fuente_informacion ya existe en caso_vacuna.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
