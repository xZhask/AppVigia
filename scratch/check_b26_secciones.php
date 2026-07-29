<?php
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::conexion();
$secciones = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = 9 ORDER BY orden")->fetchAll();
echo "SECCIONES B26 (ID 9):\n";
foreach ($secciones as $s) {
    echo "ID: {$s['id']} | orden: {$s['orden']} | nombre: '{$s['nombre']}'\n";
}
