<?php
require_once __DIR__ . '/../app/Core/Autoload.php';
require_once __DIR__ . '/../app/Core/ayudantes.php';

use App\Models\Enfermedad;
use App\Models\CampoDef;
use App\Models\SeccionDef;

$enfermedad = Enfermedad::buscar(9); // B26
$secciones = SeccionDef::porEnfermedad(9);

echo "SECCIONES OBTENIDAS PARA B26 DE SeccionDef::porEnfermedad(9):\n";
foreach ($secciones as $s) {
    echo "ID: {$s['id']} | orden: {$s['orden']} | nombre: '{$s['nombre']}'\n";
}

$numeroSeccionInicial = 3;
$valoresCampos = [];
$erroresCampos = [];

ob_start();
require __DIR__ . '/../app/Views/partials/secciones-clinicas.php';
$html = ob_get_clean();

echo "\nHTML RENDERIZADO POR secciones-clinicas.php PARA B26:\n";
echo $html;
