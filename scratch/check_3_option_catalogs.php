<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();
$stmt = $pdo->query("SELECT * FROM catalogo_def");
$cats = $stmt->fetchAll();

echo "Catálogos registrados en DB:\n";
foreach ($cats as $c) {
    echo "  - ID {$c['id']}: '{$c['nombre']}' | '{$c['descripcion']}'\n";
    $s2 = $pdo->prepare("SELECT * FROM catalogo_item WHERE catalogo_id = :cid ORDER BY orden");
    $s2->execute(['cid' => $c['id']]);
    foreach ($s2->fetchAll() as $it) {
        echo "      * val: '{$it['valor']}' | etiq: '{$it['etiqueta']}'\n";
    }
}
