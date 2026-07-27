<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\CatalogoItem;

$items = CatalogoItem::porCatalogo(11);
echo "Items for Catalogo 11:\n";
var_dump($items);
