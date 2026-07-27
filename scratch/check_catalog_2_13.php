<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\CatalogoItem;

foreach ([1, 2, 3, 10, 13] as $cid) {
    echo "=== Catalogo ID $cid ===\n";
    $items = CatalogoItem::porCatalogo($cid);
    foreach ($items as $it) {
        echo "  - valor: '{$it['valor']}' | etiqueta: '{$it['etiqueta']}'\n";
    }
}
