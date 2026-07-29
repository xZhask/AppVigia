<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') ORDER BY orden");
$secciones = $stmt->fetchAll();

echo "SECCIONES O95:\n";
foreach ($secciones as $s) {
    echo "Sec ID {$s['id']} (Orden {$s['orden']}): {$s['nombre']}\n";
    $stmtC = $pdo->query("SELECT id, clave, etiqueta, tipo, orden FROM campo_def WHERE seccion_id = {$s['id']} ORDER BY orden");
    $campos = $stmtC->fetchAll();
    foreach ($campos as $c) {
        echo "   - [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']} ({$c['tipo']})\n";
    }
}
