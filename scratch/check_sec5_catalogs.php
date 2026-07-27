<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$cats = [91 => 'Idioma', 92 => 'Nivel educativo', 93 => 'Estado civil', 94 => 'Tipo de seguro'];

foreach ($cats as $catId => $nombre) {
    echo "Catalogo ID {$catId} ({$nombre}):\n";
    $stmt = $pdo->prepare("SELECT valor, etiqueta FROM catalogo_item WHERE catalogo_id = ? ORDER BY orden, id");
    $stmt->execute([$catId]);
    foreach ($stmt->fetchAll() as $item) {
        echo "  - '{$item['valor']}' => '{$item['etiqueta']}'\n";
    }
}
