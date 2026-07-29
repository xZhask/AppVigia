<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// 1. Obtener ID de Seccion Referencia (ID 2516) y Seccion Hospitalizaciones (ID 2524)
$secRefId = 2516;
$secHospId = 2524;

// Renombrar Seccion 7 a 'Hospitalizaciones'
$pdo->query("UPDATE seccion_def SET nombre = 'Hospitalizaciones' WHERE id = $secHospId");

// Mover campo_14346 (N.° de referencias institucionales) a Seccion Referencia
$pdo->query("UPDATE campo_def SET seccion_id = $secRefId, orden = 10 WHERE id = 14346 OR clave = 'o95_n_de_referencias_institucionales'");

// Eliminar campo 14345 (o95_referida duplicado en seccion 7) si existe
$pdo->query("DELETE FROM campo_def WHERE id = 14345 OR (clave = 'o95_referida' AND seccion_id = $secHospId)");

// 2. Catalogos para Responsable EESS Origen y Institucion Destino
$catRespOrigen = $pdo->query("SELECT id FROM catalogo WHERE nombre = 'O95 Responsable Atención EE.SS. Origen'")->fetchColumn();
if (!$catRespOrigen) {
    $pdo->query("INSERT INTO catalogo (nombre) VALUES ('O95 Responsable Atención EE.SS. Origen')");
    $catRespOrigen = $pdo->lastInsertId();
    $items = ['Médico G-O', 'Médico intensivista', 'Médico residente', 'Médico general', 'Obstetra', 'Enfermera(o)', 'Interno', 'Técnico', 'Otro', 'Desconocido'];
    foreach ($items as $idx => $it) {
        $ord = $idx + 1;
        $pdo->query("INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES ($catRespOrigen, '$it', '$it', $ord)");
    }
}

$catInstDestino = $pdo->query("SELECT id FROM catalogo WHERE nombre = 'O95 Institución Destino Referencia'")->fetchColumn();
if (!$catInstDestino) {
    $pdo->query("INSERT INTO catalogo (nombre) VALUES ('O95 Institución Destino Referencia')");
    $catInstDestino = $pdo->lastInsertId();
    $items = ['EESS IGSS / Gobierno Regional', 'EESS EsSalud', 'EESS SSFFAA / PNP', 'EESS Privado'];
    foreach ($items as $idx => $it) {
        $ord = $idx + 1;
        $pdo->query("INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES ($catInstDestino, '$it', '$it', $ord)");
    }
}

// 3. Crear campos 16148 a 16159 en seccion Referencia si no existen
$newFields = [
    [16148, 'o95_fecha_ingreso_eess_origen', 'Fecha de ingreso al EE.SS. origen de la referencia', 'FECHA', null, 0, null, null],
    [16149, 'o95_hora_ingreso_eess_origen', 'Hora de ingreso al EE.SS. origen de la referencia', 'TEXTO', null, 0, null, null],
    [16150, 'o95_fecha_egreso_eess_origen', 'Fecha de egreso del EE.SS. origen de la referencia', 'FECHA', null, 0, null, null],
    [16151, 'o95_hora_egreso_eess_origen', 'Hora de egreso del EE.SS. origen de la referencia', 'TEXTO', null, 0, null, null],
    [16152, 'o95_demora_referencia_dias', 'Tiempo de demora (días)', 'NUMERO', null, 0, null, null],
    [16153, 'o95_demora_referencia_horas', 'Tiempo de demora (horas)', 'NUMERO', null, 0, null, null],
    [16154, 'o95_responsable_atencion_eess_origen', 'Responsable de la atención en EE.SS. origen', 'SELECT', $catRespOrigen, 0, null, null],
    [16155, 'o95_responsable_eess_origen_otro', 'Especificar otro responsable en EE.SS. origen', 'TEXTO', null, 0, 16154, 'Otro'],
    [16156, 'o95_institucion_destino_referencia', 'Institución destino de la referencia', 'SELECT', $catInstDestino, 0, null, null],
    [16157, 'o95_eess_destino_referencia', 'EE.SS. destino de la referencia', 'TEXTO', null, 0, null, null],
    [16158, 'o95_fecha_ingreso_eess_destino', 'Fecha de ingreso al EE.SS. destino de la referencia', 'FECHA', null, 0, null, null],
    [16159, 'o95_hora_ingreso_eess_destino', 'Hora de ingreso al EE.SS. destino de la referencia', 'TEXTO', null, 0, null, null],
];

foreach ($newFields as $idx => $f) {
    list($fId, $fClave, $fEtiqueta, $fTipo, $fCatId, $fSens, $fDep, $fAct) = $f;
    $exists = $pdo->query("SELECT id FROM campo_def WHERE id = $fId OR clave = '$fClave'")->fetchColumn();
    if (!$exists) {
        $ord = $idx + 11;
        if ($fDep) {
            $pdo->query("INSERT INTO campo_def (id, seccion_id, clave, etiqueta, tipo, catalogo_id, sensible, orden, depende_de, valor_activador) VALUES ($fId, $secRefId, '$fClave', '$fEtiqueta', '$fTipo', " . ($fCatId ? $fCatId : "NULL") . ", $fSens, $ord, $fDep, '$fAct')");
        } else {
            $pdo->query("INSERT INTO campo_def (id, seccion_id, clave, etiqueta, tipo, catalogo_id, sensible, orden) VALUES ($fId, $secRefId, '$fClave', '$fEtiqueta', '$fTipo', " . ($fCatId ? $fCatId : "NULL") . ", $fSens, $ord)");
        }
        echo "Campo $fId ($fClave) creado en Seccion Referencia.\n";
    }
}

// 4. Sincronizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        // Seccion Referencia (orden 6)
        if ($sec['orden'] == 6 || strpos($sec['nombre'], 'Referencia (Anexo 1)') !== false) {
            $manifestRefCampos = [
                ['clave' => 'campo_14309', 'etiqueta' => '¿Referida?', 'tipo' => 'GRUPO_SI_NO', 'requerido' => true, 'sensible' => false],
                ['clave' => 'campo_14310', 'etiqueta' => 'EE.SS. de origen de la referencia', 'tipo' => 'TEXTO', 'requerido' => false, 'sensible' => false, 'depende_de' => '¿Referida?', 'valor_activador' => 'SI'],
                ['clave' => 'campo_16131', 'etiqueta' => 'Departamento de la IPRESS que refiere', 'tipo' => 'SELECT', 'requerido' => false, 'sensible' => false, 'depende_de' => '¿Referida?', 'valor_activador' => 'SI'],
                ['clave' => 'campo_16132', 'etiqueta' => 'Provincia de la IPRESS que refiere', 'tipo' => 'SELECT', 'requerido' => false, 'sensible' => false, 'depende_de' => '¿Referida?', 'valor_activador' => 'SI'],
                ['clave' => 'campo_16133', 'etiqueta' => 'Distrito de la IPRESS que refiere', 'tipo' => 'SELECT', 'requerido' => false, 'sensible' => false, 'depende_de' => '¿Referida?', 'valor_activador' => 'SI'],
                ['clave' => 'o95_n_de_referencias_institucionales', 'etiqueta' => 'N.° de referencias institucionales', 'tipo' => 'NUMERO', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_fecha_ingreso_eess_origen', 'etiqueta' => 'Fecha de ingreso al EE.SS. origen de la referencia', 'tipo' => 'FECHA', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_hora_ingreso_eess_origen', 'etiqueta' => 'Hora de ingreso al EE.SS. origen de la referencia', 'tipo' => 'TEXTO', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_fecha_egreso_eess_origen', 'etiqueta' => 'Fecha de egreso del EE.SS. origen de la referencia', 'tipo' => 'FECHA', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_hora_egreso_eess_origen', 'etiqueta' => 'Hora de egreso del EE.SS. origen de la referencia', 'tipo' => 'TEXTO', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_demora_referencia_dias', 'etiqueta' => 'Tiempo de demora (días)', 'tipo' => 'NUMERO', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_demora_referencia_horas', 'etiqueta' => 'Tiempo de demora (horas)', 'tipo' => 'NUMERO', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_responsable_atencion_eess_origen', 'etiqueta' => 'Responsable de la atención en EE.SS. origen', 'tipo' => 'SELECT', 'requerido' => false, 'sensible' => false, 'opciones' => ['Médico G-O', 'Médico intensivista', 'Médico residente', 'Médico general', 'Obstetra', 'Enfermera(o)', 'Interno', 'Técnico', 'Otro', 'Desconocido']],
                ['clave' => 'o95_responsable_eess_origen_otro', 'etiqueta' => 'Especificar otro responsable en EE.SS. origen', 'tipo' => 'TEXTO', 'requerido' => false, 'sensible' => false, 'depende_de' => 'Responsable de la atención en EE.SS. origen', 'valor_activador' => 'Otro'],
                ['clave' => 'o95_institucion_destino_referencia', 'etiqueta' => 'Institución destino de la referencia', 'tipo' => 'SELECT', 'requerido' => false, 'sensible' => false, 'opciones' => ['EESS IGSS / Gobierno Regional', 'EESS EsSalud', 'EESS SSFFAA / PNP', 'EESS Privado']],
                ['clave' => 'o95_eess_destino_referencia', 'etiqueta' => 'EE.SS. destino de la referencia', 'tipo' => 'TEXTO', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_fecha_ingreso_eess_destino', 'etiqueta' => 'Fecha de ingreso al EE.SS. destino de la referencia', 'tipo' => 'FECHA', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_hora_ingreso_eess_destino', 'etiqueta' => 'Hora de ingreso al EE.SS. destino de la referencia', 'tipo' => 'TEXTO', 'requerido' => false, 'sensible' => false],
            ];
            $sec['campos'] = $manifestRefCampos;
        }

        // Seccion 7: Hospitalizaciones (orden 7)
        if ($sec['orden'] == 7 || strpos($sec['nombre'], 'hospitalizaciones') !== false || strpos($sec['nombre'], 'Hospitalizaciones') !== false) {
            $sec['nombre'] = 'Hospitalizaciones';
            $manifestHospCampos = [
                ['clave' => 'o95_hospitalizaciones_en_la_gestacion_puerperio', 'etiqueta' => '¿Hospitalizaciones en la gestación/puerperio?', 'tipo' => 'BOOLEANO', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_cuantas_hospitalizaciones', 'etiqueta' => 'Cuántas hospitalizaciones', 'tipo' => 'NUMERO', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_requirio_transfusion_de_sangre', 'etiqueta' => '¿Requirió transfusión de sangre?', 'tipo' => 'BOOLEANO', 'requerido' => false, 'sensible' => false],
                ['clave' => 'o95_expansores_plasmaticos', 'etiqueta' => '¿Expansores plasmáticos?', 'tipo' => 'BOOLEANO', 'requerido' => false, 'sensible' => false],
            ];
            $sec['campos'] = $manifestHospCampos;
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Secciones Referencia y Hospitalizaciones configuradas exitosamente.\n";
