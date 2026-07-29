<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$secId = 2525; // Seccion 8: Parto o aborto

// Crear campos de especificacion y estado para Parto o aborto
$newFields = [
    [16160, 'o95_fecha_parto_desconocida', 'Fecha de parto o aborto desconocida', 'BOOLEANO', null, 0, 14351, 'DESCONOCIDA'],
    [16161, 'o95_fecha_parto_no_aplica', 'Fecha de parto o aborto no aplica', 'BOOLEANO', null, 0, 14351, 'NO_APLICA'],
    [16162, 'o95_lugar_parto_eess_nombre', 'Nombre del EE.SS. del parto o aborto', 'TEXTO', null, 0, 14352, 'EESS'],
    [16163, 'o95_lugar_parto_otro', 'Especificar otro lugar de parto o aborto', 'TEXTO', null, 0, 14352, 'OTRO'],
    [16164, 'o95_responsable_parto_otro', 'Especificar otro responsable del parto o aborto', 'TEXTO', null, 0, 14354, 'OTRO'],
];

foreach ($newFields as $idx => $f) {
    list($fId, $fClave, $fEtiqueta, $fTipo, $fCatId, $fSens, $fDep, $fAct) = $f;
    $exists = $pdo->query("SELECT id FROM campo_def WHERE id = $fId OR clave = '$fClave'")->fetchColumn();
    if (!$exists) {
        $ord = $idx + 10;
        $pdo->query("INSERT INTO campo_def (id, seccion_id, clave, etiqueta, tipo, catalogo_id, sensible, orden, depende_de, valor_activador) VALUES ($fId, $secId, '$fClave', '$fEtiqueta', '$fTipo', " . ($fCatId ? $fCatId : "NULL") . ", $fSens, $ord, $fDep, '$fAct')");
        echo "Campo $fId ($fClave) creado en Seccion Parto o Aborto.\n";
    }
}

// Reconstruir lista exacta de campos del manifiesto desde la BD para la seccion Parto o Aborto (orden 8)
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
        if ($sec['orden'] == 8 || strpos($sec['nombre'], 'Parto o aborto') !== false) {
            $sec['campos'] = $manifestCampos;
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto de Parto o aborto sincronizado con la DB.\n";
