<?php
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas'])) {
    foreach ($manifest['fichas'] as &$f) {
        if (($f['cie10'] ?? '') === 'O95') {
            echo "Ficha O95 encontrada en array 'fichas'!\n";
            $sec = &$f['secciones'][0];
            echo "Primera seccion: '{$sec['nombre']}'\n";
            $sec['campos'][] = [
                'clave' => 'o95_n_de_historia_clinica',
                'etiqueta' => 'N.° de historia clínica',
                'tipo' => 'TEXTO',
                'obligatorio' => 0
            ];
            file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "Guardado con exito.\n";
            break;
        }
    }
}
