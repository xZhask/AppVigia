<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\CatalogoItem;

$items = CatalogoItem::porCatalogo(456);
echo "Items for Catalogo 456:\n";
var_dump($items);
