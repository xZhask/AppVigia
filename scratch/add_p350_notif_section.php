<?php
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::conexion();

$enf = $pdo->query("SELECT id FROM enfermedad WHERE cie10 = 'P35.0'")->fetch();
$enfId = (int) $enf['id'];

$secNotif = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = $enfId AND nombre = 'Datos de notificación e investigación del caso'")->fetch();
$secId = (int) $secNotif['id'];

$campos = [
    ['p35_0_codigo_de_registro_n', 'Código de registro N.°', 'TEXTO'],
    ['p35_0_fecha_de_consulta', 'Fecha de consulta', 'FECHA'],
    ['p35_0_fecha_de_conocimiento_local_del_caso', 'Fecha de conocimiento local del caso', 'FECHA'],
    ['p35_0_fecha_de_investigacion_visita_domiciliaria', 'Fecha de investigación (visita domiciliaria)', 'FECHA'],
    ['p35_0_fecha_de_notificacion_eess_a_red_microred', 'Fecha de notificación EE.SS. a Red/Microred', 'FECHA'],
    ['p35_0_fecha_notif_red_microred_a_direccion_salud', 'Fecha de notificación Red/Microred a Dirección de Salud', 'FECHA'],
    ['p35_0_fecha_notif_direccion_salud_a_cdc', 'Fecha de notificación Dirección de Salud a CDC', 'FECHA'],
];

$orden = 1;
foreach ($campos as $c) {
    $clave = $c[0];
    $etiqueta = $c[1];
    $tipo = $c[2];

    $exist = $pdo->query("SELECT id FROM campo_def WHERE seccion_id = $secId AND clave = '$clave'")->fetch();
    if (!$exist) {
        $stmt = $pdo->prepare("INSERT INTO campo_def (seccion_id, orden, clave, etiqueta, tipo, obligatorio) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$secId, $orden, $clave, $etiqueta, $tipo]);
        echo "  Añadido campo $clave (ID " . $pdo->lastInsertId() . ")\n";
    } else {
        echo "  Campo $clave ya existe (ID {$exist['id']})\n";
    }
    $orden++;
}

echo "Proceso completado para P35.0.\n";
