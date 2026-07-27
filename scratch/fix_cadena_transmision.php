<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($jsonPath), true);

// Restaurar las 10 secciones exactas de B05 desde la BD
$enf = \App\Models\Enfermedad::buscarPorCie10('B05');
$seccionesBd = \App\Models\SeccionDef::porEnfermedad((int) $enf['id']);

$seccionesManifest = [];

foreach ($seccionesBd as $sec) {
    $camposBd = \App\Models\CampoDef::porSeccion((int) $sec['id']);
    $camposManifest = [];

    foreach ($camposBd as $c) {
        $item = [
            'clave' => $c['clave'],
            'etiqueta' => $c['etiqueta'],
            'tipo' => $c['tipo'],
            'obligatorio' => (bool) $c['obligatorio'],
        ];

        if ($c['catalogo_id']) {
            $sCat = $pdo->prepare("SELECT etiqueta FROM catalogo_item WHERE catalogo_id = :cid ORDER BY orden ASC");
            $sCat->execute(['cid' => $c['catalogo_id']]);
            $item['opciones'] = $sCat->fetchAll(\PDO::FETCH_COLUMN);
        }

        if (!empty($c['depende_de'])) {
            $sPadre = $pdo->prepare("SELECT etiqueta FROM campo_def WHERE id = :pid");
            $sPadre->execute(['pid' => $c['depende_de']]);
            $item['depende_de'] = $sPadre->fetchColumn();
            $item['valor_activador'] = $c['valor_activador'];
        }

        $camposManifest[] = $item;
    }

    $seccionesManifest[] = [
        'nombre' => $sec['nombre'],
        'campos' => $camposManifest
    ];
}

$manifiesto['fichas']['B05']['secciones'] = $seccionesManifest;

file_put_contents($jsonPath, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Todas las secciones de B05 sincronizadas perfectamente desde BD.\n";
