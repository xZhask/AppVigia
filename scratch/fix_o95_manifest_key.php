<?php
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['O95'])) {
    echo "Clave O95 encontrada directamente en la raiz!\n";
    $sec = &$manifest['O95']['secciones'][0];
    echo "Primera seccion: '{$sec['nombre']}'\n";
    $sec['campos'][] = [
        'clave' => 'o95_n_de_historia_clinica',
        'etiqueta' => 'N.° de historia clínica',
        'tipo' => 'TEXTO',
        'obligatorio' => 0
    ];
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Guardado con exito.\n";
} else {
    echo "Clave O95 NO encontrada en la raiz.\n";
}
