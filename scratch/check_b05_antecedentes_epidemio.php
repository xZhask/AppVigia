<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\CampoDef;
use App\Models\CatalogoItem;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$enf = Enfermedad::buscarPorCie10('B05');
$secciones = SeccionDef::porEnfermedad((int) $enf['id']);

foreach ($secciones as $sec) {
    if (stripos($sec['nombre'], 'antecedentes epidemi') !== false || stripos($sec['nombre'], 'epidemiol') !== false) {
        echo "=== SECCION ID {$sec['id']}: {$sec['nombre']} (orden {$sec['orden']}) ===\n";
        $campos = CampoDef::porSeccion((int) $sec['id']);
        foreach ($campos as $c) {
            echo "  - [ID {$c['id']}] orden: {$c['orden']} | '{$c['etiqueta']}' | tipo: {$c['tipo']} | cat_id: {$c['catalogo_id']} | dep: {$c['depende_de']} => val: {$c['valor_activador']}\n";
            if ($c['catalogo_id']) {
                $items = CatalogoItem::porCatalogo((int) $c['catalogo_id']);
                foreach ($items as $it) {
                    echo "      * valor: '{$it['valor']}' | etiqueta: '{$it['etiqueta']}'\n";
                }
            }
        }
    }
}
