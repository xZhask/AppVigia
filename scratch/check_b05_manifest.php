<?php
$manifiesto = json_decode(file_get_contents(__DIR__ . '/../manifiesto_fichas.json'), true);
foreach ($manifiesto['fichas'] as $f) {
    if (stripos($f['enfermedad'], 'Sarampión') !== false) {
        echo "ENFERMEDAD: {$f['enfermedad']} (CIE10: {$f['cie10']})\n";
        foreach ($f['secciones'] as $s) {
            echo "  SECCION: {$s['nombre']}\n";
            foreach ($s['campos'] as $c) {
                echo "    - '{$c['etiqueta']}' (tipo: {$c['tipo']})\n";
            }
        }
    }
}
