<?php
$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$data = json_decode(file_get_contents($jsonPath), true);

foreach ($data['fichas'] as &$ficha) {
    if (($ficha['cie10'] ?? '') === 'P35.0') {
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

        // Shift orden of existing sections
        foreach ($ficha['secciones'] as &$sec) {
            $sec['orden'] = (int)$sec['orden'] + 1;
        }
        array_unshift($ficha['secciones'], $seccionNotif);
        echo "Manifiesto actualizado para P35.0!\n";
        break;
    }
}

file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
