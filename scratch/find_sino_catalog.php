<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("SELECT c.id as cat_id, c.nombre as cat_nombre, ci.valor, ci.etiqueta FROM catalogo c JOIN catalogo_item ci ON ci.catalogo_id = c.id WHERE ci.valor IN ('SI', 'NO', 'IGNORADO', 'DESCONOCIDO') ORDER BY c.id, ci.orden");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cats = [];
foreach ($rows as $r) {
    $cats[$r['cat_id']]['nombre'] = $r['cat_nombre'];
    $cats[$r['cat_id']]['items'][] = $r['valor'] . ' (' . $r['etiqueta'] . ')';
}

foreach ($cats as $id => $info) {
    echo "Catalogo ID $id ({$info['nombre']}): " . implode(', ', $info['items']) . "\n";
}
