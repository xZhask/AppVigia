<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$c14314 = $pdo->query("SELECT catalogo_id FROM campo_def WHERE clave = 'o95_causa_generica'")->fetchColumn();
$c14315 = $pdo->query("SELECT catalogo_id FROM campo_def WHERE clave = 'o95_clasificacion_inicial'")->fetchColumn();

echo "Catalogo Causa generica (14314): $c14314\n";
if ($c14314) {
    $items = $pdo->query("SELECT valor, etiqueta FROM catalogo_item WHERE catalogo_id = $c14314 ORDER BY orden")->fetchAll();
    foreach ($items as $i) echo "  - {$i['valor']} => {$i['etiqueta']}\n";
}

echo "Catalogo Clasificacion inicial (14315): $c14315\n";
if ($c14315) {
    $items = $pdo->query("SELECT valor, etiqueta FROM catalogo_item WHERE catalogo_id = $c14315 ORDER BY orden")->fetchAll();
    foreach ($items as $i) echo "  - {$i['valor']} => {$i['etiqueta']}\n";
}
