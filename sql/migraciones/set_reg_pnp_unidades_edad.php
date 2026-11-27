<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

// Registros PNP (2026-11-27): campo "Edad" (número y unidad) en el registro
// de otras enfermedades y en el de febriles, para escribirla cuando no se
// conoce la fecha de nacimiento (RENIEC no la da). Con fecha, la edad se
// calcula de ella. Fecha o edad es obligatoria en los dos: se cuentan por
// grupo de edad. Idempotente. En el servidor, el mismo UPDATE en phpMyAdmin.
$pdo = Database::conexion();
$filas = $pdo->exec(
    "UPDATE enfermedad SET unidades_edad = '[\"ANIOS\",\"MESES\",\"DIAS\"]' WHERE cie10 IN ('REG-PNP', 'REG-FEB')"
);
echo "REG-PNP y REG-FEB: unidades_edad ANIOS, MESES, DIAS ({$filas} fila(s) cambiada(s)).\n";
