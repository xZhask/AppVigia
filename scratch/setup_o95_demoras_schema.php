<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$secId = 2529; // Seccion 11: Las cuatro demoras

// Crear campo_16182 (o95_observaciones_demoras)
$exists = $pdo->query("SELECT id FROM campo_def WHERE id = 16182 OR clave = 'o95_observaciones_demoras'")->fetchColumn();
if (!$exists) {
    $pdo->query("INSERT INTO campo_def (id, seccion_id, clave, etiqueta, tipo, catalogo_id, sensible, orden) VALUES (16182, $secId, 'o95_observaciones_demoras', 'Observaciones: Anote información adicional relevante', 'TEXTO', NULL, 0, 10)");
    echo "Campo 16182 (o95_observaciones_demoras) creado en Seccion Demoras.\n";
}

// Reconstruir lista exacta de campos del manifiesto desde la BD para Las cuatro demoras (orden 11)
$dbCampos = $pdo->query("SELECT id, clave, etiqueta, tipo, catalogo_id, depende_de, valor_activador FROM campo_def WHERE seccion_id = $secId ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$manifestCampos = [];
foreach ($dbCampos as $c) {
    $item = [
        'clave' => $c['clave'],
        'etiqueta' => $c['etiqueta'],
        'tipo' => $c['tipo'],
        'requerido' => false,
        'sensible' => false,
    ];
    if ($c['catalogo_id']) {
        $opcs = $pdo->query("SELECT etiqueta FROM catalogo_item WHERE catalogo_id = {$c['catalogo_id']} ORDER BY orden")->fetchAll(PDO::FETCH_COLUMN);
        $item['opciones'] = array_values($opcs);
    }
    if ($c['depende_de']) {
        $depEtiqueta = $pdo->query("SELECT etiqueta FROM campo_def WHERE id = {$c['depende_de']}")->fetchColumn();
        $item['depende_de'] = $depEtiqueta;
        $item['valor_activador'] = $c['valor_activador'];
    }
    $manifestCampos[] = $item;
}

$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        if ($sec['orden'] == 11 || strpos($sec['nombre'], 'demoras') !== false) {
            $sec['campos'] = $manifestCampos;
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto de Las cuatro demoras sincronizado con la DB.\n";
