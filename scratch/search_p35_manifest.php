<?php
$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$data = json_decode(file_get_contents($jsonPath), true);

foreach ($data['fichas'] as $i => $f) {
    if (strpos($f['nombre'], 'rub') !== false || strpos($f['cie10'], 'P35') !== false) {
        echo "Ficha $i: ID {$f['id']} | CIE10: '{$f['cie10']}' | Nombre: '{$f['nombre']}'\n";
    }
}
