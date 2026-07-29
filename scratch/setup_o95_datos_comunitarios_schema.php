<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$secId = 2527; // Seccion 10: Datos comunitarios

// Crear nuevos campos de especificacion y desglose de tiempo para Datos comunitarios
$newFields = [
    [16176, 'o95_sintomatologia_otro', 'Especificar otra sintomatología o molestia', 'TEXTO', null, 0, 14369, 'OTRO'],
    [16177, 'o95_maniobras_parto_otro', 'Especificar otra maniobra durante el parto', 'TEXTO', null, 0, 14370, 'OTRO'],
    [16178, 'o95_maniobras_placenta_otro', 'Especificar otra maniobra para retirar placenta', 'TEXTO', null, 0, 14371, 'OTRO'],
    [16179, 'o95_tiempo_domicilio_eess_horas', 'Tiempo del domicilio al EE.SS. (horas)', 'NUMERO', null, 0, null, null],
    [16180, 'o95_tiempo_domicilio_eess_minutos', 'Tiempo del domicilio al EE.SS. (minutos)', 'NUMERO', null, 0, null, null],
];

foreach ($newFields as $idx => $f) {
    list($fId, $fClave, $fEtiqueta, $fTipo, $fCatId, $fSens, $fDep, $fAct) = $f;
    $exists = $pdo->query("SELECT id FROM campo_def WHERE id = $fId OR clave = '$fClave'")->fetchColumn();
    if (!$exists) {
        $ord = $idx + 20;
        $pdo->query("INSERT INTO campo_def (id, seccion_id, clave, etiqueta, tipo, catalogo_id, sensible, orden, depende_de, valor_activador) VALUES ($fId, $secId, '$fClave', '$fEtiqueta', '$fTipo', " . ($fCatId ? $fCatId : "NULL") . ", $fSens, $ord, " . ($fDep ? $fDep : "NULL") . ", " . ($fAct ? "'$fAct'" : "NULL") . ")");
        echo "Campo $fId ($fClave) creado en Seccion Datos Comunitarios.\n";
    }
}

// Reconstruir lista exacta de campos del manifiesto desde la BD para la seccion Datos comunitarios (orden 10)
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
        if ($sec['orden'] == 10 || strpos($sec['nombre'], 'Datos comunitarios') !== false) {
            $sec['campos'] = $manifestCampos;
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto de Datos comunitarios sincronizado con la DB.\n";
