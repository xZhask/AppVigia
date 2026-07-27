<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();
$stmt = $pdo->query("SELECT DISTINCT catalogo_id FROM catalogo_item WHERE valor = 'DESCONOCIDO'");
$cids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

echo "Catálogos con 'DESCONOCIDO':\n";
foreach ($cids as $cid) {
    echo "=== Catalogo ID $cid ===\n";
    $s2 = $pdo->prepare("SELECT * FROM catalogo_item WHERE catalogo_id = :cid ORDER BY orden");
    $s2->execute(['cid' => $cid]);
    foreach ($s2->fetchAll() as $it) {
        echo "  - val: '{$it['valor']}' | etiq: '{$it['etiqueta']}'\n";
    }
}
