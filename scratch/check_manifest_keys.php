<?php
$json = json_decode(file_get_contents(__DIR__ . '/../manifiesto_fichas.json'), true);
foreach ($json['fichas'] as $f) {
    echo "CIE10: {$f['cie10']} | Nombre: {$f['nombre']}\n";
}
