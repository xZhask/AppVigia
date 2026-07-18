<?php
/**
 * Tira de pestañas de navegación entre catálogos relacionados.
 * Variables esperadas: $pestanas (array de ['ruta'=>string,'etiqueta'=>string]).
 * Usa $rutaActual, que ya llega en toda vista renderizada por Controller::vista().
 */
?>
<div class="seg" style="margin-bottom:16px">
  <?php foreach ($pestanas as $pestana): ?>
    <button type="button" class="<?= $pestana['ruta'] === $rutaActual ? 'on' : '' ?>" onclick="location.href='<?= e('/' . $pestana['ruta']) ?>'"><?= e($pestana['etiqueta']) ?></button>
  <?php endforeach; ?>
</div>
