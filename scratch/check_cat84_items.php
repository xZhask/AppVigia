<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("SELECT * FROM catalogo_item WHERE catalogo_id = 84 ORDER BY orden ASC");
echo "Items en catalogo 84:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  - ID {$row['id']} | valor: '{$row['valor']}' | etiqueta: '{$row['etiqueta']}'\n";
}
