<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// febriles_sin_casos.tipo (decisión de Epidemiología, 2026-11-11): además de
// "no hubo febriles", una IPRESS de nivel I puede notificar que el día no
// hubo atención (los domingos y feriados nacionales ya figuran así solos;
// esto cubre los días no laborables decretados). Nivel II y III reportan
// todos los días.
$existe = $pdo->query("SHOW COLUMNS FROM febriles_sin_casos LIKE 'tipo'")->fetchColumn();
if (!$existe) {
    $pdo->exec("ALTER TABLE febriles_sin_casos ADD COLUMN tipo enum('SIN_FEBRILES','SIN_ATENCION') NOT NULL DEFAULT 'SIN_FEBRILES' AFTER fecha");
    echo "Columna febriles_sin_casos.tipo agregada.\n";
} else {
    echo "Columna febriles_sin_casos.tipo ya existe.\n";
}
