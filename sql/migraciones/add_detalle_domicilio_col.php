<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Mismo patrón exacto que unidades_edad: declarativo por ficha en el
// manifiesto, persistido acá como JSON, NULL si la ficha no lo declara.
// Opt-in -- ausente equivale al comportamiento actual (sin estos 6
// campos). Ver PETICION_MAPEO_Y_EDAD.md, "Entrada J acotada al bloque de
// domicilio".
$hasCol = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'detalle_domicilio'")->fetchColumn();
if (!$hasCol) {
    $pdo->query("ALTER TABLE enfermedad ADD COLUMN detalle_domicilio LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(detalle_domicilio)) AFTER unidades_edad");
    echo "Columna detalle_domicilio agregada a la tabla enfermedad.\n";
} else {
    echo "Columna detalle_domicilio ya existe en enfermedad.\n";
}
