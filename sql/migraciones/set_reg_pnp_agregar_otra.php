<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

// Registro PNP de otras enfermedades (2026-11-26): "Registrar y agregar otra"
// (nucleo_ajustes.registrar_y_agregar_otra, el mismo de P96 y febriles). EDA
// e IRA se cargan una tras otra: el siguiente registro abre con el mismo
// establecimiento, fecha de notificación y grupo (IRA, diarrea aguda...).
// Idempotente: JSON_SET no duplica la clave. En el servidor, el mismo UPDATE
// en phpMyAdmin.
$pdo = Database::conexion();
$filas = $pdo->exec(
    "UPDATE enfermedad
        SET nucleo_ajustes = JSON_SET(COALESCE(nucleo_ajustes, '{}'), '$.registrar_y_agregar_otra', TRUE)
      WHERE cie10 = 'REG-PNP'"
);
echo "REG-PNP: registrar_y_agregar_otra activado ({$filas} fila(s) cambiada(s)).\n";
echo $pdo->query("SELECT nucleo_ajustes FROM enfermedad WHERE cie10 = 'REG-PNP'")->fetchColumn() . "\n";
