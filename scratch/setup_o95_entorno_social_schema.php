<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$secId = 2526; // Seccion 9: Entorno social y comunitario

// Crear nuevos campos de especificacion y desglose de tiempo para Entorno Social
$newFields = [
    [16165, 'o95_persona_identifico_otro', 'Especificar otra persona que identificó signos', 'TEXTO', null, 0, 14358, 'OTRO'],
    [16166, 'o95_decision_buscar_ayuda_otro', 'Especificar otra persona que tomó la decisión de buscar ayuda', 'TEXTO', null, 0, 14360, 'OTRO'],
    [16167, 'o95_tiempo_buscar_ayuda_horas', 'Tiempo en buscar ayuda (horas)', 'NUMERO', null, 0, 14359, 'SI'],
    [16168, 'o95_tiempo_buscar_ayuda_minutos', 'Tiempo en buscar ayuda (minutos)', 'NUMERO', null, 0, 14359, 'SI'],
    [16169, 'o95_dificultad_acceso_otro', 'Especificar otra dificultad de acceso', 'TEXTO', null, 0, 14363, 'OTRO'],
    [16170, 'o95_tiempo_llegar_eess_horas', 'Tiempo hasta llegar al EE.SS. (horas)', 'NUMERO', null, 0, 14362, 'SI'],
    [16171, 'o95_tiempo_llegar_eess_minutos', 'Tiempo hasta llegar al EE.SS. (minutos)', 'NUMERO', null, 0, 14362, 'SI'],
    [16172, 'o95_dificultad_atencion_otro', 'Especificar otra dificultad de atención', 'TEXTO', null, 0, 14366, 'OTRO'],
    [16173, 'o95_tiempo_hasta_atendida_horas', 'Tiempo hasta ser atendida (horas)', 'NUMERO', null, 0, 14365, 'SI'],
    [16174, 'o95_tiempo_hasta_atendida_minutos', 'Tiempo hasta ser atendida (minutos)', 'NUMERO', null, 0, 14365, 'SI'],
    [16175, 'o95_persona_brindo_info_otro', 'Especificar otra persona que brindó información', 'TEXTO', null, 0, 14368, 'OTRO'],
];

foreach ($newFields as $idx => $f) {
    list($fId, $fClave, $fEtiqueta, $fTipo, $fCatId, $fSens, $fDep, $fAct) = $f;
    $exists = $pdo->query("SELECT id FROM campo_def WHERE id = $fId OR clave = '$fClave'")->fetchColumn();
    if (!$exists) {
        $ord = $idx + 20;
        $pdo->query("INSERT INTO campo_def (id, seccion_id, clave, etiqueta, tipo, catalogo_id, sensible, orden, depende_de, valor_activador) VALUES ($fId, $secId, '$fClave', '$fEtiqueta', '$fTipo', " . ($fCatId ? $fCatId : "NULL") . ", $fSens, $ord, $fDep, '$fAct')");
        echo "Campo $fId ($fClave) creado en Seccion Entorno Social.\n";
    }
}

// Reconstruir lista exacta de campos del manifiesto desde la BD para la seccion Entorno social (orden 9)
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
        if ($sec['orden'] == 9 || strpos($sec['nombre'], 'Entorno social') !== false) {
            $sec['campos'] = $manifestCampos;
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto de Entorno social y comunitario sincronizado con la DB.\n";
