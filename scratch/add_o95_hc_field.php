<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['O95']['secciones'])) {
    foreach ($manifest['O95']['secciones'] as &$sec) {
        if (($sec['orden'] ?? 0) == 1 || ($sec['nombre'] ?? '') == 'Anexo 1 — Notificación inmediata') {
            $existe = false;
            foreach ($sec['campos'] as $c) {
                if (($c['clave'] ?? '') === 'o95_n_de_historia_clinica' || ($c['etiqueta'] ?? '') == 'N.° de historia clínica') {
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
                echo "Campo 'o95_n_de_historia_clinica' agregado a la seccion 1 en manifiesto_fichas.json\n";
            }
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto guardado correctamente.\n";
