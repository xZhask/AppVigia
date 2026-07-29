<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$secId = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND (nombre LIKE '%Complicaciones%' OR orden = 6)")->fetchColumn();

if (!$secId) {
    die("Sección no encontrada.\n");
}

// 1. Crear catalogo para o95_tuvo_complicaciones
$catTuvo = $pdo->query("SELECT id FROM catalogo WHERE nombre = 'O95 Tuvo Complicaciones'")->fetchColumn();
if (!$catTuvo) {
    $pdo->query("INSERT INTO catalogo (nombre) VALUES ('O95 Tuvo Complicaciones')");
    $catTuvo = $pdo->lastInsertId();
    $pdo->query("INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES ($catTuvo, 'SI', 'Sí', 1), ($catTuvo, 'NO', 'No', 2), ($catTuvo, 'DESCONOCIDO', 'Desconocido', 3)");
}

// 2. Insertar campo o95_tuvo_complicaciones (ID 16147) si no existe
$exists16147 = $pdo->query("SELECT id FROM campo_def WHERE id = 16147 OR clave = 'o95_tuvo_complicaciones'")->fetchColumn();
if (!$exists16147) {
    $pdo->query("INSERT INTO campo_def (id, seccion_id, clave, etiqueta, tipo, catalogo_id, sensible, orden) VALUES (16147, $secId, 'o95_tuvo_complicaciones', '¿Tuvo complicaciones?', 'SELECT', $catTuvo, 0, 1)");
    echo "Campo 16147 (o95_tuvo_complicaciones) creado.\n";
}

// 3. Crear/insertar campos de especificacion (16144, 16145, 16146)
$specifyFields = [
    [16144, 'o95_complicaciones_embarazo_otro', 'Especificar otra complicación del embarazo', 14342],
    [16145, 'o95_complicaciones_parto_otro', 'Especificar otra complicación del parto', 14343],
    [16146, 'o95_complicaciones_puerperio_otro', 'Especificar otra complicación del puerperio', 14344],
];

foreach ($specifyFields as $sf) {
    list($fId, $fClave, $fEtiqueta, $fPadre) = $sf;
    $exists = $pdo->query("SELECT id FROM campo_def WHERE id = $fId OR clave = '$fClave'")->fetchColumn();
    if (!$exists) {
        $pdo->query("INSERT INTO campo_def (id, seccion_id, clave, etiqueta, tipo, sensible, orden, depende_de, valor_activador) VALUES ($fId, $secId, '$fClave', '$fEtiqueta', 'TEXTO', 0, 10, $fPadre, 'Otro')");
        echo "Campo $fId ($fClave) creado.\n";
    }
}

// 4. Agregar NINGUNA a catalogos de embarazo, parto, puerperio si no existe
$catEmb = $pdo->query("SELECT catalogo_id FROM campo_def WHERE id = 14342")->fetchColumn();
$catPart = $pdo->query("SELECT catalogo_id FROM campo_def WHERE id = 14343")->fetchColumn();
$catPuer = $pdo->query("SELECT catalogo_id FROM campo_def WHERE id = 14344")->fetchColumn();

foreach ([$catEmb, $catPart, $catPuer] as $cId) {
    if ($cId) {
        $hasNinguna = $pdo->query("SELECT id FROM catalogo_item WHERE catalogo_id = $cId AND valor = 'NINGUNA'")->fetchColumn();
        if (!$hasNinguna) {
            // Insertar NINGUNA en la posicion 1
            $pdo->query("UPDATE catalogo_item SET orden = orden + 1 WHERE catalogo_id = $cId");
            $pdo->query("INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES ($cId, 'NINGUNA', 'Ninguna complicación', 1)");
        }
    }
}

// 5. Sincronizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        if ($sec['orden'] == 6 || strpos($sec['nombre'], 'Complicaciones') !== false) {
            $hasTuvo = false;
            foreach ($sec['campos'] as $c) {
                if ($c['clave'] === 'o95_tuvo_complicaciones') $hasTuvo = true;
            }
            if (!$hasTuvo) {
                array_unshift($sec['campos'], [
                    'clave' => 'o95_tuvo_complicaciones',
                    'etiqueta' => '¿Tuvo complicaciones?',
                    'tipo' => 'SELECT',
                    'requerido' => false,
                    'sensible' => false,
                    'opciones' => ['Sí', 'No', 'Desconocido']
                ]);
            }
            // Agregar opc NINGUNA en manifiesto
            foreach ($sec['campos'] as &$c) {
                if (in_array($c['clave'], ['o95_complicaciones_del_embarazo', 'o95_complicaciones_del_parto', 'o95_complicaciones_del_puerperio'])) {
                    if (!in_array('Ninguna complicación', $c['opciones']) && !in_array('NINGUNA', $c['opciones'])) {
                        array_unshift($c['opciones'], 'Ninguna complicación');
                    }
                }
            }
            // Agregar specify fields si no existen
            $specifyManifest = [
                ['o95_complicaciones_embarazo_otro', 'Especificar otra complicación del embarazo', 'Complicaciones del embarazo'],
                ['o95_complicaciones_parto_otro', 'Especificar otra complicación del parto', 'Complicaciones del parto'],
                ['o95_complicaciones_puerperio_otro', 'Especificar otra complicación del puerperio', 'Complicaciones del puerperio'],
            ];
            foreach ($specifyManifest as $sm) {
                $hasSm = false;
                foreach ($sec['campos'] as $c) {
                    if ($c['clave'] === $sm[0]) $hasSm = true;
                }
                if (!$hasSm) {
                    $sec['campos'][] = [
                        'clave' => $sm[0],
                        'etiqueta' => $sm[1],
                        'tipo' => 'TEXTO',
                        'requerido' => false,
                        'sensible' => false,
                        'depende_de' => $sm[2],
                        'valor_activador' => 'Otro'
                    ];
                }
            }
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto y DB sincronizados para Complicaciones.\n";
