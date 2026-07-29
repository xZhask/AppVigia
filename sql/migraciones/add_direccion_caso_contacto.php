<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

try {
    $pdo->exec("ALTER TABLE caso_contacto ADD COLUMN direccion VARCHAR(160) NULL");
    echo "Agregada columna direccion a caso_contacto.\n";
} catch (\PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column name')) {
        echo "La columna direccion ya existe en caso_contacto.\n";
    }
}
