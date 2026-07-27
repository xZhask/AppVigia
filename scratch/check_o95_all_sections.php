<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->prepare("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') ORDER BY orden ASC");
$stmt->execute();
$secciones = $stmt->fetchAll();

echo "Secciones de O95 (Muerte Materna):\n";
foreach ($secciones as $s) {
    echo "  Section ID {$s['id']} | orden {$s['orden']} | nombre: '{$s['nombre']}'\n";
}
