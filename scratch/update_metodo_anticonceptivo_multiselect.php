<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Cambiar tipo de o95_uso_de_metodo_anticonceptivo_previo a MULTISELECT
$pdo->query("UPDATE campo_def SET tipo = 'MULTISELECT' WHERE clave = 'o95_uso_de_metodo_anticonceptivo_previo'");

// Sincronizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        foreach ($sec['campos'] as &$c) {
            if ($c['clave'] === 'o95_uso_de_metodo_anticonceptivo_previo') {
                $c['tipo'] = 'MULTISELECT';
            }
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "o95_uso_de_metodo_anticonceptivo_previo actualizado a MULTISELECT.\n";
