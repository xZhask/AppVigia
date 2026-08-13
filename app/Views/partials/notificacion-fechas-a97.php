<?php
/**
 * "Enfermedad / evento a notificar", "Subsistema de vigilancia" y "Fecha
 * de investigación" para A97 (Dengue/chikungunya/zika/otras arbovirosis/
 * fiebre amarilla, pág. 49). Se pintan en la tarjeta fija "1. Notificación",
 * en el lugar donde otras fichas traen Tipo/Lugar/Clasificación en la
 * captación (notificacion-captacion.php) -- el PDF de A97 no pide ese
 * bloque genérico (no está en `nueva/index.php`, ver el array que oculta
 * notificacionCaptacionWrap), y el usuario pidió mover acá los 3 campos
 * en vez de dejarlos en sus propias tarjetas más abajo (a97_enfermedad_evento:
 * 2026-08-12; a97_subsistema_de_vigilancia/a97_fecha_de_investigacion:
 * 2026-08-13).
 *
 * Los 3 siguen siendo campo_def normales (secciones "Enfermedad / evento a
 * notificar" y "Subsistema de vigilancia" del manifiesto): de
 * a97_enfermedad_evento dependen a97_clasificacion/a97_clasificacion_zika/
 * a97_clasificacion_fiebre_amarilla/a97_otras_arbovirosis_especificar más
 * abajo en la sección clínica "Clasificación". Ese depende_de sigue
 * funcionando igual (apunta al campo_def por ID, no a dónde se pinta).
 *
 * Envuelto en una función para no pisar la variable $campo del llamador
 * (el resolvedor AMBIENTE de campos-por-clave.php) con la fila de
 * campo_def que exige el contrato de campo-dinamico.php -- este partial se
 * incluye sin condición en cada carga de página (aunque A97 no sea la
 * ficha activa), así que pisar $campo acá rompía con un 500 cualquier
 * ficha que después volviera a llamar $campo() (B05, A37.0, B01, A35,
 * A33, P35.0, O95) -- mismo bug, mismo motivo, que ya tenían
 * cuadro-clinico-b26.php/lugar-probable-infeccion-b26.php/
 * notificacion-fechas-o95.php (ver el comentario más largo ahí).
 */
$campoA97 = $resolvedorPara('A97');
$campoEnfEvento = $campoA97('a97_enfermedad_evento');
$campoSubsistema = $campoA97('a97_subsistema_de_vigilancia');
$campoFechaInvest = $campoA97('a97_fecha_de_investigacion');

$pintarCampoA97 = function (array $resuelto): void {
    if (!$resuelto['id']) {
        return;
    }
    $campo = $resuelto['campo'];
    $valor = $resuelto['val'];
    $error = $resuelto['err'];
    $opciones = $resuelto['opciones'];
    require __DIR__ . '/campo-dinamico.php';
};
?>

<div id="notificacionFechasA97Wrap" <?= ($enfermedad['cie10'] ?? null) === 'A97' ? '' : 'hidden style="display:none;"' ?>>
  <div class="fields thirds" style="margin-top:14px">
    <?php $pintarCampoA97($campoEnfEvento); ?>
    <?php $pintarCampoA97($campoSubsistema); ?>
    <?php $pintarCampoA97($campoFechaInvest); ?>
  </div>
</div>
