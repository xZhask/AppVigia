<?php
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::conexion();

// Remove notification section added to P35.0
$secP35 = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = 17 AND nombre = 'Datos de notificación e investigación del caso'")->fetch();
if ($secP35) {
    $secId = $secP35['id'];
    $pdo->exec("DELETE FROM campo_def WHERE seccion_id = $secId");
    $pdo->exec("DELETE FROM seccion_def WHERE id = $secId");
    $pdo->exec("UPDATE seccion_def SET orden = orden - 1 WHERE enfermedad_id = 17");
    echo "Sección de notificación removida de P35.0 en la BD.\n";
}

// Revert manifiesto_fichas.json for P35.0
$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$data = json_decode(file_get_contents($jsonPath), true);

if (isset($data['fichas']['P35.0'])) {
    $secciones = &$data['fichas']['P35.0']['secciones'];
    foreach ($secciones as $k => $s) {
        if ($s['nombre'] === 'Datos de notificación e investigación del caso') {
            unset($secciones[$k]);
            echo "Sección removida del manifiesto para P35.0\n";
            break;
        }
    }
    $secciones = array_values($secciones);
    foreach ($secciones as $idx => &$sec) {
        $sec['orden'] = $idx + 1;
    }
    file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

echo "Reversión completada.\n";
