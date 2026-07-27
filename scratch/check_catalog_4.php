<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\CatalogoItem;

$items = CatalogoItem::porCatalogo(4);
echo "Catalog 4 (Tipo de muestra):\n";
foreach ($items as $item) {
    echo "  - valor: '{$item['valor']}' | etiqueta: '{$item['etiqueta']}'\n";
}
