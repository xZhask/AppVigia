<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\CatalogoItem;

$items = CatalogoItem::porCatalogo(12);
echo "Catalog 12 (Captación del caso):\n";
foreach ($items as $it) {
    echo "  - ID {$it['id']} | valor: '{$it['valor']}' | etiqueta: '{$it['etiqueta']}'\n";
}
