<?php
require __DIR__ . '/../app/Core/Autoload.php';

$manifiesto = json_decode(file_get_contents(__DIR__ . '/../manifiesto_fichas.json'), true);
foreach ($manifiesto['fichas'] as $f) {
    if (stripos($f['enfermedad'], 'Sarampión') !== false) {
        echo "=== FICHA: {$f['enfermedad']} ===\n";
        foreach ($f['secciones'] as $sec) {
            echo "  Seccion: '{$sec['nombre']}'\n";
            if (stripos($sec['nombre'], 'clínico') !== false || stripos($sec['nombre'], 'clinico') !== false) {
                foreach ($sec['campos'] as $c) {
                    echo "    - '{$c['etiqueta']}' | tipo: {$c['tipo']} | depende: " . json_encode($c['depende_de'] ?? null) . " | val_act: " . json_encode($c['valor_activador'] ?? null) . "\n";
                }
            }
        }
    }
}
