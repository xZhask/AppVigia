<?php
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

foreach ($manifest as $key => $val) {
    if (is_array($val) && isset($val['cie10']) && $val['cie10'] === 'O95') {
        echo "Encontrado O95 en key '{$key}'\n";
        print_r($val['secciones'][0]);
    }
}
