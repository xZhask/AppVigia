<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->prepare("SELECT c.id, c.clave, c.etiqueta, c.tipo, s.nombre as seccion FROM campo_def c JOIN seccion_def s ON c.seccion_id = s.id WHERE s.enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') ORDER BY s.orden, c.orden");
$stmt->execute();
$campos = $stmt->fetchAll();

echo "Todos los campos de O95:\n";
foreach ($campos as $c) {
    if (stripos($c['etiqueta'], 'historia') !== false || stripos($c['etiqueta'], 'h.c') !== false || stripos($c['etiqueta'], 'clinica') !== false || stripos($c['etiqueta'], 'numero') !== false || stripos($c['etiqueta'], 'n.') !== false || stripos($c['etiqueta'], 'ficha') !== false || stripos($c['etiqueta'], 'expediente') !== false) {
        echo "  -> MATCH: Field ID {$c['id']} | clave: '{$c['clave']}' | '{$c['etiqueta']}' | seccion: '{$c['seccion']}'\n";
    }
}
