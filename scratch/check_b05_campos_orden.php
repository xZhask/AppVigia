<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\CampoDef;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$enf = Enfermedad::buscarPorCie10('B05');
$secciones = SeccionDef::porEnfermedad((int) $enf['id']);
foreach ($secciones as $sec) {
    echo "=== SECCION {$sec['orden']}: {$sec['nombre']} (ID {$sec['id']}) ===\n";
    $campos = CampoDef::porSeccion((int) $sec['id']);
    foreach ($campos as $c) {
        echo "  - [ID {$c['id']}] orden: {$c['orden']} | '{$c['etiqueta']}' | tipo: {$c['tipo']}\n";
    }
}
