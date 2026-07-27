<?php
$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($jsonPath), true);

$c1 = $manifiesto['fichas']['B05']['secciones'][0]['campos'][0];
echo "Estructura de campo en manifiesto:\n";
print_r($c1);
