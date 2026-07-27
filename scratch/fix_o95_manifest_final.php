<?php
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    echo "Ficha O95 encontrada en \$manifest['fichas']['O95']!\n";
    $sec = &$manifest['fichas']['O95']['secciones'][0];
    echo "Primera seccion: '{$sec['nombre']}'\n";
    
    $existe = false;
    foreach ($sec['campos'] as $c) {
        if (($c['clave'] ?? '') === 'o95_n_de_historia_clinica') {
            $existe = true;
            break;
        }
    }
    if (!$existe) {
        $sec['campos'][] = [
            'clave' => 'o95_n_de_historia_clinica',
            'etiqueta' => 'N.° de historia clínica',
            'tipo' => 'TEXTO',
            'obligatorio' => 0
        ];
        echo "Agregado o95_n_de_historia_clinica a la seccion 1 en el manifiesto.\n";
    }
    
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Manifiesto actualizado y guardado.\n";
} else {
    echo "NO se encontro la clave O95 en \$manifest['fichas']['O95']\n";
}
