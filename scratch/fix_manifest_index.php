<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($jsonPath), true);

$secIndex = null;
foreach ($manifiesto['fichas']['B05']['secciones'] as $idx => $sec) {
    if (stripos($sec['nombre'], 'clasificaci') !== false) {
        $secIndex = $idx;
        echo "Encontrada sección Clasificación final en índice $idx: {$sec['nombre']}\n";
    }
}

if ($secIndex !== null) {
    $secClasif = &$manifiesto['fichas']['B05']['secciones'][$secIndex];
    
    // Consultar los campos de la sección 2779 en BD
    $stmt = $pdo->prepare("SELECT * FROM campo_def WHERE seccion_id = 2779 ORDER BY orden ASC");
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
            $sCat = $pdo->prepare("SELECT etiqueta FROM catalogo_item WHERE catalogo_id = :cid ORDER BY orden ASC");
            $sCat->execute(['cid' => $c['catalogo_id']]);
            $opciones = $sCat->fetchAll(\PDO::FETCH_COLUMN);
            $itemManifest['opciones'] = $opciones;
        }

        if (!empty($c['depende_de'])) {
            $sPadre = $pdo->prepare("SELECT etiqueta FROM campo_def WHERE id = :pid");
            $sPadre->execute(['pid' => $c['depende_de']]);
            $etiqPadre = $sPadre->fetchColumn();

            $itemManifest['depende_de'] = $etiqPadre;
            $itemManifest['valor_activador'] = $c['valor_activador'];
        }

        $nuevosCamposManifest[] = $itemManifest;
    }

    $secClasif['campos'] = $nuevosCamposManifest;

    file_put_contents($jsonPath, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "manifiesto_fichas.json corregido y guardado exitosamente.\n";
}
