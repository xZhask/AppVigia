<?php
$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($jsonPath), true);

foreach ($manifiesto['fichas'] ?? $manifiesto as $key => $val) {
    if (is_array($val) && isset($val['enfermedad']) && stripos($val['enfermedad'], 'saramp') !== false) {
        echo "Found B05 key: '$key'\n";
        foreach ($val['secciones'] as $sIdx => $sec) {
            echo "  - Section $sIdx: {$sec['nombre']}\n";
        }
    }
}
