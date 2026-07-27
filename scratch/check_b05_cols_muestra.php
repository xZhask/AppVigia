<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\Enfermedad;

$enf = Enfermedad::buscarPorCie10('B05');
echo "Enfermedad B05:\n";
print_r($enf);
