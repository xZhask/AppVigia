<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->prepare("SELECT id, clave, etiqueta FROM campo_def WHERE seccion_id = 2778 ORDER BY orden ASC");
$stmt->execute();
$campos = $stmt->fetchAll();

echo "Field IDs in Section 2778:\n";
foreach ($campos as $c) {
    echo "  - ID {$c['id']} | clave: '{$c['clave']}' | name: 'campo_{$c['id']}' | etiqueta: '{$c['etiqueta']}'\n";
}
