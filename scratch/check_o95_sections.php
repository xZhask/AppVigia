<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->prepare("SELECT * FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') ORDER BY orden ASC");
$stmt->execute();
$secciones = $stmt->fetchAll();

echo "Secciones para O95 (Muerte Materna):\n";
foreach ($secciones as $s) {
    echo "  - ID {$s['id']} | orden: {$s['orden']} | nombre: '{$s['nombre']}'\n";
    $stmtC = $pdo->prepare("SELECT id, clave, etiqueta, tipo FROM campo_def WHERE seccion_id = {$s['id']} ORDER BY orden ASC");
    $stmtC->execute();
    foreach ($stmtC->fetchAll() as $c) {
        echo "      * Field ID {$c['id']} | clave: '{$c['clave']}' | '{$c['etiqueta']}' | tipo: {$c['tipo']}\n";
    }
}
