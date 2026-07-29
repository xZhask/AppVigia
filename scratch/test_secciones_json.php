<?php
require_once __DIR__ . '/../app/Core/Autoload.php';
require_once __DIR__ . '/../app/Core/ayudantes.php';

use App\Models\Enfermedad;
use App\Models\CampoDef;

$enfermedad = Enfermedad::buscar(9);
echo "CIE10: {$enfermedad['cie10']}\n";
echo "usa_lugar_infeccion: {$enfermedad['usa_lugar_infeccion']}\n";
