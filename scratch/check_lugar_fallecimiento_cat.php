<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("SELECT * FROM catalogo_item WHERE catalogo_id = 491 ORDER BY orden, id");
echo "Items del catalogo 491 (Lugar del fallecimiento):\n";
foreach ($stmt->fetchAll() as $item) {
    echo "  - Valor: '{$item['valor']}' => Etiqueta: '{$item['etiqueta']}'\n";
}
