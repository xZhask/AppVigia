<?php
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

foreach ($manifest as &$f) {
    if (isset($f['cie10']) && $f['cie10'] === 'O95') {
        echo "O95 encontrado!\n";
        foreach ($f['secciones'] as &$sec) {
            if (($sec['orden'] ?? 0) == 1 || stripos($sec['nombre'] ?? '', 'Anexo 1') !== false) {
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
                    echo "Agregado o95_n_de_historia_clinica a la seccion 1 en manifiesto_fichas.json\n";
                }
            }
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto actualizado y guardado.\n";
