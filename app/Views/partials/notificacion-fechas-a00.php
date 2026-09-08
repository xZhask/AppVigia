<?php
/**
 * "Código", "Fecha de investigación del caso" y "Captación del caso" para A00
 * (EDA grave / cólera, "I. DATOS GENERALES" de la pág. 50 del PDF). Se pintan
 * en la tarjeta fija "1. Notificación", en el lugar donde otras fichas traen
 * Tipo/Lugar/Clasificación en la captación (notificacion-captacion.php) --
 * mismo criterio que A97/B57/A95/B04X.
 *
 * Por qué "Captación del caso" es un campo_def propio y no el "Tipo de
 * captación" del núcleo: ese select sólo ofrece Activa/Pasiva, y el PDF de
 * A00 pide cuatro opciones (Pasiva / Activa / Vigilancia comunal /
 * Seguimiento de contactos). El bloque genérico queda oculto para A00 (misma
 * lista de nueva/index.php y fichas/editar.php que ya usan otras 11 fichas).
 *
 * "Fecha de notificación" NO está acá: es el campo núcleo fecha_notif que la
 * propia tarjeta ya pinta. DISA / Red / Establecimiento notificante tampoco:
 * son datos constantes del establecimiento ya elegido en "Establecimiento
 * (EESS)" (red_salud.nombre/diresa), mismo criterio que A37.0/A97/B57/A95.
 *
 * Envuelto en una función por el mismo motivo que notificacion-fechas-a97.php:
 * no pisar la variable $campo del llamador (el resolvedor AMBIENTE de
 * campos-por-clave.php), porque este partial se incluye sin condición en cada
 * carga de página aunque A00 no sea la ficha activa.
 */
$campoA00 = $resolvedorPara('A00');
$campoCodigoA00 = $campoA00('a00_codigo');
$campoFechaInvestA00 = $campoA00('a00_fecha_de_investigacion');
$campoCaptacionA00 = $campoA00('a00_captacion_del_caso');

$pintarCampoA00 = function (array $resuelto): void {
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

<div id="notificacionFechasA00Wrap" <?= ($enfermedad['cie10'] ?? null) === 'A00' ? '' : 'hidden style="display:none;"' ?>>
  <div class="fields thirds" style="margin-top:14px">
    <?php $pintarCampoA00($campoCodigoA00); ?>
    <?php $pintarCampoA00($campoFechaInvestA00); ?>
    <?php $pintarCampoA00($campoCaptacionA00); ?>
  </div>
</div>
