<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("SELECT c.*, s.orden as sec_orden, s.nombre as sec_nombre FROM campo_def c JOIN seccion_def s ON c.seccion_id = s.id WHERE s.enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND s.orden = 5");
echo "Campos en DB para O95 Seccion 5:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  - ID {$row['id']} | Clave: '{$row['clave']}' | Etiqueta: '{$row['etiqueta']}' | Tipo: '{$row['tipo']}' | CatalogoID: {$row['catalogo_id']}\n";
}
