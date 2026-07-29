<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$enf = $pdo->query("SELECT id FROM enfermedad WHERE cie10 = 'B26'")->fetch(PDO::FETCH_ASSOC);
if (!$enf) {
    die("Error: No se encontró la enfermedad B26.\n");
}
$enfId = (int)$enf['id'];

// 1. Verificar si la sección ya existe
$secExist = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = {$enfId} AND nombre = 'Datos de notificación e investigación del caso'")->fetch(PDO::FETCH_ASSOC);

if (!$secExist) {
    // Reordenar las secciones existentes + 1
    $pdo->exec("UPDATE seccion_def SET orden = orden + 1 WHERE enfermedad_id = {$enfId}");

    // Insertar nueva sección con orden = 1
    $st = $pdo->prepare("INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (?, 'Datos de notificación e investigación del caso', 1)");
    $st->execute([$enfId]);
    $secId = (int)$pdo->lastInsertId();
    echo "Sección creada con ID: {$secId}\n";
} else {
    $secId = (int)$secExist['id'];
    echo "Sección ya existía con ID: {$secId}\n";
}

// 2. Campos a agregar en la sección
$camposNuevos = [
    [
        'etiqueta' => 'Código de registro N.°',
        'tipo' => 'TEXTO',
        'orden' => 1
    ],
    [
        'etiqueta' => 'Fecha de consulta',
        'tipo' => 'FECHA',
        'orden' => 2
    ],
    [
        'etiqueta' => 'Fecha de conocimiento local del caso',
        'tipo' => 'FECHA',
        'orden' => 3
    ],
    [
        'etiqueta' => 'Fecha de investigación (visita domiciliaria)',
        'tipo' => 'FECHA',
        'orden' => 4
    ],
    [
        'etiqueta' => 'Fecha de notificación EE.SS. a Red/Microred',
        'tipo' => 'FECHA',
        'orden' => 5
    ],
    [
        'etiqueta' => 'Fecha de notificación Red/Microred a Dirección de Salud',
        'tipo' => 'FECHA',
        'orden' => 6
    ],
    [
        'etiqueta' => 'Fecha de notificación Dirección de Salud a CDC',
        'tipo' => 'FECHA',
        'orden' => 7
    ],
];

function normalizarClave($texto) {
    $c = strtolower(trim($texto));
    $c = strtr($c, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','°'=>'','.'=>'']);
    $c = preg_replace('/[^a-z0-9_]+/', '_', $c);
    return trim($c, '_');
}

foreach ($camposNuevos as $c) {
    $cExist = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = ? AND etiqueta = ?");
    $cExist->execute([$secId, $c['etiqueta']]);
    if (!$cExist->fetch()) {
        $clave = 'b26_' . normalizarClave($c['etiqueta']);
        $st = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden) VALUES (?, ?, ?, ?, ?)");
        $st->execute([$secId, $clave, $c['etiqueta'], $c['tipo'], $c['orden']]);
        echo "Campo agregado: {$c['etiqueta']} (clave: {$clave})\n";
    } else {
        echo "Campo ya existe: {$c['etiqueta']}\n";
    }
}

// 3. Actualizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$json = json_decode(file_get_contents($manifestPath), true);

if (isset($json['fichas']['B26'])) {
    $seccionesExist = $json['fichas']['B26']['secciones'] ?? [];
    $tieneNotif = false;
    foreach ($seccionesExist as $s) {
        if (($s['nombre'] ?? '') === 'Datos de notificación e investigación del caso') {
            $tieneNotif = true;
            break;
        }
    }

    if (!$tieneNotif) {
        $nuevaSecManifest = [
            'nombre' => 'Datos de notificación e investigación del caso',
            'campos' => array_map(function($c) {
                return [
                    'etiqueta' => $c['etiqueta'],
                    'tipo' => $c['tipo']
                ];
            }, $camposNuevos)
        ];
        array_unshift($json['fichas']['B26']['secciones'], $nuevaSecManifest);
        file_put_contents($manifestPath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "manifiesto_fichas.json actualizado para B26.\n";
    }
}
