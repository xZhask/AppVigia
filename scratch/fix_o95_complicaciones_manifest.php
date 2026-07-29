<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$secId = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND (nombre LIKE '%Complicaciones%' OR orden = 6)")->fetchColumn();

// Reconstruir lista exacta de campos del manifiesto desde la BD para la seccion 6 de O95
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
        if ($sec['orden'] == 6 || strpos($sec['nombre'], 'Complicaciones') !== false) {
            $sec['campos'] = $manifestCampos;
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto de Complicaciones reconstruido desde DB.\n";
