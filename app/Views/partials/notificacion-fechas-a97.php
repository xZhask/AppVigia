<?php
/**
 * "Enfermedad / evento a notificar" para A97 (Dengue/chikungunya/zika/
 * otras arbovirosis/fiebre amarilla, pág. 49). Se pinta en la tarjeta fija
 * "1. Notificación", en el lugar donde otras fichas traen Tipo/Lugar/
 * Clasificación en la captación (notificacion-captacion.php) -- el PDF de
 * A97 no pide ese bloque genérico (no está en `nueva/index.php`, ver el
 * array que oculta notificacionCaptacionWrap), y el usuario pidió mover
 * acá el selector en su lugar (2026-08-12) en vez de dejarlo como su
 * propia tarjeta más abajo.
 *
 * El campo sigue siendo un campo_def normal (a97_enfermedad_evento,
 * sección "Enfermedad / evento a notificar" del manifiesto): de él
 * dependen a97_clasificacion/a97_clasificacion_zika/
 * a97_clasificacion_fiebre_amarilla/a97_otras_arbovirosis_especificar más
 * abajo en la sección clínica "Clasificación". Ese depende_de sigue
 * funcionando igual (apunta al campo_def por ID, no a dónde se pinta).
 */
$campoA97 = $resolvedorPara('A97');
$campoEnfEvento = $campoA97('a97_enfermedad_evento');
?>

<div id="notificacionFechasA97Wrap" <?= ($enfermedad['cie10'] ?? null) === 'A97' ? '' : 'hidden style="display:none;"' ?>>
  <?php if ($campoEnfEvento['id']):
        $campo = $campoEnfEvento['campo'];
        $valor = $campoEnfEvento['val'];
        $error = $campoEnfEvento['err'];
        $opciones = $campoEnfEvento['opciones'];
        ?>
  <div class="fields thirds" style="margin-top:14px">
    <?php require __DIR__ . '/campo-dinamico.php'; ?>
  </div>
  <?php endif; ?>
</div>
