<?php
$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($jsonPath), true);

$manifiesto['fichas']['B05']['columnas_contacto'] = [
    'fecha_contacto',
    'lugar_contacto',
    'edad',
    'direccion',
    'celular',
    'vacunado_72h',
    'fecha_vacunacion',
    'fecha_inicio_erupcion'
];

file_put_contents($jsonPath, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Guardadas columnas_contacto para B05 en manifiesto_fichas.json.\n";
