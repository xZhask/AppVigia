<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$columnas = [
    "numero_muestra TINYINT(1) DEFAULT 1",
    "fecha_recepcion_ins DATE NULL",
    "resultado_pcr VARCHAR(40) NULL",
    "fecha_result_pcr DATE NULL",
    "genotipo VARCHAR(80) NULL",
    "resultado_igm VARCHAR(40) NULL",
    "fecha_result_igm DATE NULL",
    "resultado_igg VARCHAR(40) NULL",
    "fecha_result_igg DATE NULL",
];

foreach ($columnas as $colDef) {
    $colName = explode(' ', $colDef)[0];
    try {
        $pdo->exec("ALTER TABLE caso_muestra ADD COLUMN $colDef");
        echo "Agregada columna $colName a caso_muestra.\n";
    } catch (\PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column name')) {
            echo "La columna $colName ya existe en caso_muestra.\n";
        } else {
            echo "Error al agregar $colName: " . $e->getMessage() . "\n";
        }
    }
}
