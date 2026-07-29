<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$c14324 = $pdo->query("SELECT catalogo_id FROM campo_def WHERE clave = 'o95_antecedentes_patologicos'")->fetchColumn();

echo "Catalogo Antecedentes patologicos (14324): $c14324\n";
if ($c14324) {
    $items = $pdo->query("SELECT id, valor, etiqueta FROM catalogo_item WHERE catalogo_id = $c14324 ORDER BY orden")->fetchAll();
    foreach ($items as $i) echo "  - {$i['valor']} => {$i['etiqueta']}\n";
}
