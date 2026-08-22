<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Cotejo de Enfermedad de Chagas (B57), sección "IV. Migración": booleano
// simple (no JSON, a diferencia de unidades_edad/detalle_domicilio) porque
// activa un bloque cerrado completo (años/meses + domicilio anterior), no
// una lista de sub-campos elegibles uno por uno -- mismo criterio que
// tablas_hijas.caso_viaje. Opt-in, default 0.
$hasCol = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'migracion_reciente'")->fetchColumn();
if (!$hasCol) {
    $pdo->query("ALTER TABLE enfermedad ADD COLUMN migracion_reciente TINYINT(1) NOT NULL DEFAULT 0 AFTER detalle_domicilio");
    echo "Columna migracion_reciente agregada a la tabla enfermedad.\n";
} else {
    echo "Columna migracion_reciente ya existe en enfermedad.\n";
}
