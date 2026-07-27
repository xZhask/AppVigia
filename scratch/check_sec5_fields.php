<?php
$manifest = json_decode(file_get_contents(__DIR__ . '/../manifiesto_fichas.json'), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as $sec) {
        if (($sec['orden'] ?? 0) == 5 || stripos($sec['nombre'] ?? '', 'Datos básicos adicionales') !== false) {
            echo "Seccion 5: {$sec['nombre']}\n";
            foreach ($sec['campos'] as $c) {
                echo "  - {$c['clave']}: {$c['etiqueta']} ({$c['tipo']})\n";
            }
        }
    }
}
