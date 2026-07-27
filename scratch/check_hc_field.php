<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("SELECT c.id, c.clave, c.etiqueta, c.tipo, s.nombre as seccion, e.cie10 FROM campo_def c JOIN seccion_def s ON c.seccion_id = s.id JOIN enfermedad e ON s.enfermedad_id = e.id WHERE c.etiqueta LIKE '%historia%' OR c.clave LIKE '%historia%' OR c.etiqueta LIKE '%h.c%'");
echo "Campos relacionados a historia clinica:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  - [{$row['cie10']}] Field ID {$row['id']} | clave: '{$row['clave']}' | '{$row['etiqueta']}' (seccion: '{$row['seccion']}')\n";
}
