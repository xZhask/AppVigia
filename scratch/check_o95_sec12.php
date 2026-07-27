<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->prepare("SELECT c.id, c.clave, c.etiqueta, c.tipo, s.nombre as seccion FROM campo_def c JOIN seccion_def s ON c.seccion_id = s.id WHERE s.enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND s.orden IN (1, 2) ORDER BY s.orden, c.orden");
$stmt->execute();
$campos = $stmt->fetchAll();

echo "Campos en Secciones 1 y 2 de O95:\n";
foreach ($campos as $c) {
    echo "  - ID {$c['id']} | clave: '{$c['clave']}' | '{$c['etiqueta']}' | tipo: {$c['tipo']} | seccion: '{$c['seccion']}'\n";
}
