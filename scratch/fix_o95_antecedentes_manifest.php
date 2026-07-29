<?php
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        foreach ($sec['campos'] as &$c) {
            if ($c['clave'] === 'o95_fase_del_puerperio_en_que_fallecio') {
                $c['depende_de'] = 'Momento del fallecimiento';
                $c['valor_activador'] = 'Puerperio';
            }
            if ($c['clave'] === 'o95_idioma_otra') {
                $c['depende_de'] = 'Idioma';
                $c['valor_activador'] = 'OTRA';
            }
            if ($c['clave'] === 'o95_tipo_de_seguro_otro') {
                $c['depende_de'] = 'Tipo de seguro';
                $c['valor_activador'] = 'OTROS';
            }
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto sincronizado con etiquetas exactas.\n";
