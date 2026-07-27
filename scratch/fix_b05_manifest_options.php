<?php
$manifiestoPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($manifiestoPath), true);

foreach ($manifiesto['fichas'] as &$f) {
    if (stripos($f['enfermedad'], 'Sarampión') !== false) {
        foreach ($f['secciones'] as &$sec) {
            if (trim($sec['nombre']) === 'Cuadro clínico') {
                foreach ($sec['campos'] as &$c) {
                    if (stripos($c['etiqueta'], 'vacuna') !== false) {
                        $c['catalogo_id'] = 145;
                        $c['opciones'] = [
                            ['valor' => 'SI', 'etiqueta' => 'Sí'],
                            ['valor' => 'NO', 'etiqueta' => 'No'],
                            ['valor' => 'DESCONOCIDO', 'etiqueta' => 'Desconocido']
                        ];
                    }
                }
            }
        }
    }
}

file_put_contents($manifiestoPath, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto actualizado con opciones.\n";
