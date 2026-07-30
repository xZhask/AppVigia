<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$hasCol = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'nucleo_omitidos'")->fetchColumn();
if (!$hasCol) {
    $pdo->query("ALTER TABLE enfermedad ADD COLUMN nucleo_omitidos LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(nucleo_omitidos)) AFTER columnas_vacuna");
    echo "Columna nucleo_omitidos agregada a la tabla enfermedad.\n";
} else {
    echo "Columna nucleo_omitidos ya existe en enfermedad.\n";
}
