<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\CampoDef;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$enf = Enfermedad::buscarPorCie10('B05');
$secciones = SeccionDef::porEnfermedad((int) $enf['id']);
foreach ($secciones as $sec) {
    if (trim($sec['nombre']) === 'Cuadro clínico') {
        echo "=== SECCION EN BD: " . $sec['nombre'] . " (ID " . $sec['id'] . ") ===\n";
        $campos = CampoDef::porSeccion((int) $sec['id']);
        foreach ($campos as $c) {
            echo "  - [ID {$c['id']}] '{$c['etiqueta']}' | tipo: {$c['tipo']} | depende: " . ($c['depende_de'] ?? 'null') . " | val_act: " . ($c['valor_activador'] ?? 'null') . "\n";
        }
    }
}
