<?php
/**
 * nucleo_ajustes.fecha_notif_en (2026-10-16): la fecha de notificación del
 * núcleo (caso.fecha_notif, de la que salen la SE, la edad y los duplicados)
 * pintada en el sitio de la fecha de la cadena del PDF que la ficha declara
 * ("Fecha de notificación EE.SS. a Red/Microred"), con la etiqueta de esa
 * fecha. La tarjeta "Notificación" ya no pinta la genérica, y el servidor
 * copia este valor al campo_def (validarCamposDinamicos()).
 *
 * Espera $etiquetaFechaNotif, $valoresFijos y $erroresFijos.
 */
?>
<div class="field">
  <label class="fl"><?= e($etiquetaFechaNotif) ?> <span class="req">*</span></label>
  <div class="control mono <?= isset($erroresFijos['fecha_notif']) ? 'err' : '' ?>">
    <input type="date" id="fechaNotif" name="fecha_notif" value="<?= e($valoresFijos['fecha_notif'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
  </div>
  <?php if (isset($erroresFijos['fecha_notif'])): ?>
    <span class="hint err"><?= e($erroresFijos['fecha_notif']) ?></span>
  <?php endif; ?>
</div>
