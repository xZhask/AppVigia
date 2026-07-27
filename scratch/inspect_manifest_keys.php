<?php
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

echo "Claves principales en manifiesto_fichas.json:\n";
foreach (array_keys($manifest) as $k) {
    echo "  - '{$k}'\n";
}
