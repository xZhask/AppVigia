<?php
$json = json_decode(file_get_contents(__DIR__ . '/../manifiesto_fichas.json'), true);
$b05 = $json['fichas']['B05'];
echo "columnas_contacto para B05:\n";
var_dump($b05['columnas_contacto'] ?? 'NO DEFINIDO');
