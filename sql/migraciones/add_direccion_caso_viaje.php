<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// A97 (pág. 49 del PDF, ítem 21 "¿Dónde estuvo en las últimas dos semanas?"):
// la tabla de lugares visitados pide Dirección además de Departamento/
// Provincia/Distrito (columna ya existente, distrito_id, nunca expuesta en
// pantalla) y País/fechas (ya existentes). Se agrega acá en vez de crear
// una tabla nueva porque A97 ya declara tablas_hijas.caso_viaje=true.
$hasCol = $pdo->query("SHOW COLUMNS FROM caso_viaje LIKE 'direccion'")->fetchColumn();
if (!$hasCol) {
    $pdo->exec("ALTER TABLE caso_viaje ADD COLUMN direccion VARCHAR(255) DEFAULT NULL AFTER localidad");
    echo "Columna direccion agregada a caso_viaje.\n";
} else {
    echo "Columna direccion ya existe en caso_viaje.\n";
}
