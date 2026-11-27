<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

// Registro PNP de otras enfermedades (2026-11-27): campo "Edad" (número y
// unidad) para escribirla cuando no se conoce la fecha de nacimiento (RENIEC
// no la da). Con fecha, la edad se calcula. EDA e IRA la exigen: NOTI las
// cuenta por grupo de edad. Idempotente. En el servidor, el mismo UPDATE en
// phpMyAdmin.
$pdo = Database::conexion();
$filas = $pdo->exec(
    "UPDATE enfermedad SET unidades_edad = '[\"ANIOS\",\"MESES\",\"DIAS\"]' WHERE cie10 = 'REG-PNP'"
);
echo "REG-PNP: unidades_edad ANIOS, MESES, DIAS ({$filas} fila(s) cambiada(s)).\n";
