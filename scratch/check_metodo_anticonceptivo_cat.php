<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$c14333 = $pdo->query("SELECT catalogo_id FROM campo_def WHERE clave = 'o95_uso_de_metodo_anticonceptivo_previo'")->fetchColumn();

echo "Catalogo Metodo Anticonceptivo (14333): $c14333\n";
if ($c14333) {
    $items = $pdo->query("SELECT id, valor, etiqueta FROM catalogo_item WHERE catalogo_id = $c14333 ORDER BY orden")->fetchAll();
    foreach ($items as $i) echo "  - {$i['valor']} => {$i['etiqueta']}\n";
}
