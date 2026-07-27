<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\CatalogoItem;

$items = CatalogoItem::porCatalogo(14);
echo "Catalog 14 (Tipo de muestra):\n";
foreach ($items as $item) {
    echo "  - valor: '{$item['valor']}' | etiqueta: '{$item['etiqueta']}'\n";
}
