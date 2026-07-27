<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($jsonPath), true);

$secEpidemio = &$manifiesto['fichas']['B05']['secciones'][5]; // Antecedentes epidemiológicos
echo "Sección en manifiesto: {$secEpidemio['nombre']}\n";

// Consultar los campos de la sección 2776 en BD
$stmt = $pdo->prepare("SELECT * FROM campo_def WHERE seccion_id = 2776 ORDER BY orden ASC");
$stmt->execute();
$camposBd = $stmt->fetchAll();

$nuevosCamposManifest = [];

foreach ($camposBd as $c) {
    $itemManifest = [
        'clave' => $c['clave'],
        'etiqueta' => $c['etiqueta'],
        'tipo' => $c['tipo'],
        'obligatorio' => (bool) $c['obligatorio'],
    ];

    if ($c['catalogo_id']) {
        // Cargar opciones del catálogo como lista de etiquetas (cadenas de texto)
        $sCat = $pdo->prepare("SELECT etiqueta FROM catalogo_item WHERE catalogo_id = :cid ORDER BY orden ASC");
        $sCat->execute(['cid' => $c['catalogo_id']]);
        $opciones = $sCat->fetchAll(\PDO::FETCH_COLUMN);
        $itemManifest['opciones'] = $opciones;
    }

    if (!empty($c['depende_de'])) {
        // Encontrar la etiqueta del campo padre
        $sPadre = $pdo->prepare("SELECT etiqueta FROM campo_def WHERE id = :pid");
        $sPadre->execute(['pid' => $c['depende_de']]);
        $etiqPadre = $sPadre->fetchColumn();

        $itemManifest['depende_de'] = $etiqPadre;
        $itemManifest['valor_activador'] = $c['valor_activador'];
    }

    $nuevosCamposManifest[] = $itemManifest;
}

$secEpidemio['campos'] = $nuevosCamposManifest;

// También remover el campo ID 16083 de la sección Investigación epidemiológica en manifiesto si existía allí
$secInvestigacion = &$manifiesto['fichas']['B05']['secciones'][8]; // Investigación epidemiológica
$camposInvestigacion = [];
foreach ($secInvestigacion['campos'] as $c) {
    if (stripos($c['etiqueta'], 'últimos 30 días') === false) {
        $camposInvestigacion[] = $c;
    }
}
$secInvestigacion['campos'] = $camposInvestigacion;

file_put_contents($jsonPath, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "manifiesto_fichas.json sincronizado con éxito.\n";
