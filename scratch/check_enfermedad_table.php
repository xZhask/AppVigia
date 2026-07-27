<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\Enfermedad;

$enf = Enfermedad::buscarPorCie10('B05');
echo "buscarPorCie10('B05'):\n";
var_dump($enf);

if ($enf) {
    $enfBusqueda = Enfermedad::buscar((int) $enf['id']);
    echo "\nbuscar({$enf['id']}):\n";
    var_dump($enfBusqueda);
}
