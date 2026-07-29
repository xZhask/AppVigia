<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Sincronizar manifiesto_fichas.json para que depende_de use la etiqueta del padre
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        if ($sec['orden'] == 5 || strpos($sec['nombre'], 'prenatal') !== false) {
            foreach ($sec['campos'] as &$c) {
                if ($c['clave'] === 'o95_responsable_apn_otro') {
                    $c['depende_de'] = 'Responsable de la APN';
                }
            }
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto actualizado a la etiqueta del padre.\n";
