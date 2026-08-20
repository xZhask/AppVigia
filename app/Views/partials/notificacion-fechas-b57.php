<?php
/**
 * "Fecha conocimiento local / Fecha investigación / Fecha conocimiento DISA /
 * Fecha conocimiento nacional" para B57 (Enfermedad de Chagas, pág. 40,
 * encabezado de la ficha). Se pintan en la tarjeta fija "1. Notificación",
 * en el lugar donde otras fichas traen Tipo/Lugar/Clasificación en la
 * captación (notificacion-captacion.php) -- mismo criterio que A97
 * (notificacion-fechas-a97.php), pero B57 sí conserva "Tipo de captación"
 * (mapea con "Notificación Regular/Búsqueda Activa" del PDF, ver
 * notificacion-captacion.php), solo "Lugar"/"Clasificación en la
 * captación" se ocultan. DISA/Nombre del establecimiento/UTES-UBAS-ZONADIS-RED
 * no se capturan como campo_def: son datos del establecimiento ya elegido
 * (mismo criterio que A37.0/A97).
 *
 * Envuelto en una función por el mismo motivo que notificacion-fechas-a97.php:
 * no pisar la variable $campo del llamador (el resolvedor AMBIENTE de
 * campos-por-clave.php), este partial se incluye sin condición en cada
 * carga de página aunque B57 no sea la ficha activa.
 */
$campoB57 = $resolvedorPara('B57');
$campoConocimientoLocal = $campoB57('b57_fecha_conocimiento_local');
$campoFechaInvestB57 = $campoB57('b57_fecha_de_investigacion');
$campoConocimientoDisa = $campoB57('b57_fecha_conocimiento_disa');
$campoConocimientoNacional = $campoB57('b57_fecha_conocimiento_nacional');

$pintarCampoB57 = function (array $resuelto): void {
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

<div id="notificacionFechasB57Wrap" <?= ($enfermedad['cie10'] ?? null) === 'B57' ? '' : 'hidden style="display:none;"' ?>>
  <div class="fields" style="margin-top:14px">
    <?php $pintarCampoB57($campoConocimientoLocal); ?>
    <?php $pintarCampoB57($campoFechaInvestB57); ?>
    <?php $pintarCampoB57($campoConocimientoDisa); ?>
    <?php $pintarCampoB57($campoConocimientoNacional); ?>
  </div>
</div>
