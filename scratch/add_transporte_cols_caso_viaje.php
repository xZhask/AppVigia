<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

try {
    $pdo->exec("ALTER TABLE caso_viaje ADD COLUMN transporte_ida VARCHAR(40) NULL");
    echo "Agregada columna transporte_ida a caso_viaje.\n";
} catch (\PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column name')) {
        echo "La columna transporte_ida ya existe en caso_viaje.\n";
    }
}

try {
    $pdo->exec("ALTER TABLE caso_viaje ADD COLUMN transporte_retorno VARCHAR(40) NULL");
    echo "Agregada columna transporte_retorno a caso_viaje.\n";
} catch (\PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column name')) {
        echo "La columna transporte_retorno ya existe en caso_viaje.\n";
    }
}
