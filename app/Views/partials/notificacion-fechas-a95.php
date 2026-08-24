<?php
/**
 * "Fecha conocimiento local / Fecha investigación / Fecha conocimiento DISA /
 * Fecha conocimiento nacional" para A95 (Fiebre amarilla, pág. 26, encabezado
 * de la ficha). Se pintan en la tarjeta fija "1. Notificación", en el lugar
 * donde otras fichas traen Tipo/Lugar/Clasificación en la captación
 * (notificacion-captacion.php) -- mismo criterio que B57 (cotejo 2026-08-20,
 * pág. 40 del PDF: encabezado casi idéntico al de A95): "Notificación
 * Regular/Búsqueda Activa" mapea 1 a 1 con "Tipo de captación" (se
 * conserva), solo "Lugar"/"Clasificación en la captación" se ocultan --
 * "Código" (COGIGO del encabezado) se pinta en el espacio que deja libre
 * "Lugar de captación" (ver notificacion-captacion.php). DISA/Nombre del
 * establecimiento/UTES-UBAS-ZONADIS-RED no se capturan como campo_def: son
 * datos del establecimiento ya elegido (mismo criterio que A37.0/A97/B57).
 *
 * Envuelto en una función por el mismo motivo que notificacion-fechas-b57.php:
 * no pisar la variable $campo del llamador (el resolvedor AMBIENTE de
 * campos-por-clave.php), este partial se incluye sin condición en cada
 * carga de página aunque A95 no sea la ficha activa.
 */
$campoA95Notif = $resolvedorPara('A95');
$campoConocimientoLocalA95 = $campoA95Notif('a95_fecha_conocimiento_local');
$campoFechaInvestA95 = $campoA95Notif('a95_fecha_de_investigacion');
$campoConocimientoDisaA95 = $campoA95Notif('a95_fecha_conocimiento_disa');
$campoConocimientoNacionalA95 = $campoA95Notif('a95_fecha_conocimiento_nacional');

$pintarCampoA95Notif = function (array $resuelto): void {
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

<div id="notificacionFechasA95Wrap" <?= ($enfermedad['cie10'] ?? null) === 'A95' ? '' : 'hidden style="display:none;"' ?>>
  <div class="fields" style="margin-top:14px">
    <?php $pintarCampoA95Notif($campoConocimientoLocalA95); ?>
    <?php $pintarCampoA95Notif($campoFechaInvestA95); ?>
    <?php $pintarCampoA95Notif($campoConocimientoDisaA95); ?>
    <?php $pintarCampoA95Notif($campoConocimientoNacionalA95); ?>
  </div>
</div>
