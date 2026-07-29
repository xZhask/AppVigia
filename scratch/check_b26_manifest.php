<?php
$json = json_decode(file_get_contents(__DIR__ . '/../manifiesto_fichas.json'), true);
print_r(array_keys($json['fichas']['B26'] ?? []));
print_r($json['fichas']['B26']['secciones'] ?? []);
