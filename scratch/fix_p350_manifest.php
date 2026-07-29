<?php
$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$data = json_decode(file_get_contents($jsonPath), true);

$p350Key = null;
foreach ($data['fichas'] as $key => $ficha) {
    if (($ficha['cie10'] ?? '') === 'P35.0' || $key === 'P35.0') {
        $p350Key = $key;
        break;
    }
}

echo "Clave encontrada para P35.0: " . var_export($p350Key, true) . "\n";

if ($p350Key !== null) {
    $seccionNotif = [
        'nombre' => 'Datos de notificación e investigación del caso',
        'orden' => 1,
        'campos' => [
            ['clave' => 'p35_0_codigo_de_registro_n', 'etiqueta' => 'Código de registro N.°', 'tipo' => 'TEXTO', 'obligatorio' => false],
            ['clave' => 'p35_0_fecha_de_consulta', 'etiqueta' => 'Fecha de consulta', 'tipo' => 'FECHA', 'obligatorio' => false],
            ['clave' => 'p35_0_fecha_de_conocimiento_local_del_caso', 'etiqueta' => 'Fecha de conocimiento local del caso', 'tipo' => 'FECHA', 'obligatorio' => false],
            ['clave' => 'p35_0_fecha_de_investigacion_visita_domiciliaria', 'etiqueta' => 'Fecha de investigación (visita domiciliaria)', 'tipo' => 'FECHA', 'obligatorio' => false],
            ['clave' => 'p35_0_fecha_de_notificacion_eess_a_red_microred', 'etiqueta' => 'Fecha de notificación EE.SS. a Red/Microred', 'tipo' => 'FECHA', 'obligatorio' => false],
            ['clave' => 'p35_0_fecha_notif_red_microred_a_direccion_salud', 'etiqueta' => 'Fecha de notificación Red/Microred a Dirección de Salud', 'tipo' => 'FECHA', 'obligatorio' => false],
            ['clave' => 'p35_0_fecha_notif_direccion_salud_a_cdc', 'etiqueta' => 'Fecha de notificación Dirección de Salud a CDC', 'tipo' => 'FECHA', 'obligatorio' => false],
        ]
    ];

    // Check if section already exists in manifest
    $exists = false;
    foreach ($data['fichas'][$p350Key]['secciones'] as $s) {
        if ($s['nombre'] === 'Datos de notificación e investigación del caso') {
            $exists = true;
            break;
        }
    }

    if (!$exists) {
        foreach ($data['fichas'][$p350Key]['secciones'] as &$sec) {
            $sec['orden'] = (int)$sec['orden'] + 1;
        }
        array_unshift($data['fichas'][$p350Key]['secciones'], $seccionNotif);
        file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Manifiesto actualizado para P35.0 en clave $p350Key!\n";
    }
}
