<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\CatalogoItem;

$items = CatalogoItem::porCatalogo(8);
echo "Items for Catalogo 8:\n";
var_dump($items);
