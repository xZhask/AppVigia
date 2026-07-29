<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Obtener catalogos e items de los 4 campos de Complicaciones
$campos = [16147, 14342, 14343, 14344];

$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        if ($sec['orden'] == 6 || strpos($sec['nombre'], 'Complicaciones') !== false) {
            foreach ($sec['campos'] as &$c) {
                $fStmt = $pdo->prepare("SELECT id, catalogo_id FROM campo_def WHERE clave = ?");
                $fStmt->execute([$c['clave']]);
                $fRow = $fStmt->fetch(PDO::FETCH_ASSOC);

                if ($fRow && $fRow['catalogo_id']) {
                    $items = $pdo->query("SELECT etiqueta FROM catalogo_item WHERE catalogo_id = {$fRow['catalogo_id']} ORDER BY orden")->fetchAll(PDO::FETCH_COLUMN);
                    $c['opciones'] = array_values($items);
                }
                if ($c['clave'] === 'o95_complicaciones_embarazo_otro') {
                    $c['depende_de'] = 'Complicaciones del embarazo';
                }
                if ($c['clave'] === 'o95_complicaciones_parto_otro') {
                    $c['depende_de'] = 'Complicaciones del parto';
                }
                if ($c['clave'] === 'o95_complicaciones_puerperio_otro') {
                    $c['depende_de'] = 'Complicaciones del puerperio';
                }
            }
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Sincronizacion de catalogos y depende_de completada.\n";
