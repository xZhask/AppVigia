<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("SELECT id, nombre FROM catalogo WHERE nombre LIKE '%etni%' OR nombre LIKE '%raza%' OR nombre LIKE '%grupo%'");
echo "Catalogos relacionados a etnia:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  - Catalogo ID {$row['id']}: '{$row['nombre']}'\n";
    $stmtItems = $pdo->prepare("SELECT valor, etiqueta FROM catalogo_item WHERE catalogo_id = ? ORDER BY orden, id");
    $stmtItems->execute([$row['id']]);
    foreach ($stmtItems->fetchAll() as $item) {
        echo "      * {$item['valor']} => {$item['etiqueta']}\n";
    }
}
