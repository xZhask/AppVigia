<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Nuevo orden de secciones para O95:
// Orden 1: Datos del fallecimiento (Anexo 1) [2515]
// Orden 2: Antecedentes patológicos y obstétricos [2520]
// Orden 3: Causas de defunción (Anexo 1) [2517]
// Orden 4: Atención prenatal [2522]
// Orden 5: Complicaciones [2523]
// Orden 6: Referencia (Anexo 1) [2516]
// Orden 7: Referencia y hospitalizaciones [2524]
// Orden 8: Parto o aborto [2525]
// Orden 9: Entorno social y comunitario [2526]
// Orden 10: Datos comunitarios [2527]
// Orden 11: Causas de defunción (Anexo 2) [2528]
// Orden 12: Las cuatro demoras [2529]

$newOrders = [
    2515 => 1,
    2520 => 2,
    2517 => 3,
    2522 => 4,
    2523 => 5,
    2516 => 6,
    2524 => 7,
    2525 => 8,
    2526 => 9,
    2527 => 10,
    2528 => 11,
    2529 => 12
];

foreach ($newOrders as $secId => $ord) {
    $pdo->query("UPDATE seccion_def SET orden = $ord WHERE id = $secId");
}

// Sincronizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    $secByNombre = [];
    foreach ($manifest['fichas']['O95']['secciones'] as $sec) {
        $secByNombre[$sec['nombre']] = $sec;
    }

    $sectionsInOrder = [
        ['Datos del fallecimiento (Anexo 1)', 1],
        ['Antecedentes patológicos y obstétricos', 2],
        ['Causas de defunción (Anexo 1)', 3],
        ['Atención prenatal', 4],
        ['Complicaciones', 5],
        ['Referencia (Anexo 1)', 6],
        ['Referencia y hospitalizaciones', 7],
        ['Parto o aborto', 8],
        ['Entorno social y comunitario', 9],
        ['Datos comunitarios', 10],
        ['Causas de defunción (Anexo 2)', 11],
        ['Las cuatro demoras', 12]
    ];

    $newManifestSecs = [];
    foreach ($sectionsInOrder as $pair) {
        list($nom, $ord) = $pair;
        if (isset($secByNombre[$nom])) {
            $sData = $secByNombre[$nom];
            $sData['orden'] = $ord;
            $newManifestSecs[] = $sData;
        }
    }
    $manifest['fichas']['O95']['secciones'] = $newManifestSecs;
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Orden de secciones actualizado con éxito.\n";
