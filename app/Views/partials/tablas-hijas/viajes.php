<?php
/**
 * Fila dinámica de viajes del caso (caso_viaje). Variable esperada:
 * $filasViajes (array de ['pais','fecha_salida','fecha_retorno']).
 * `pais` se usa como "lugar visitado" libre (nacional o internacional);
 * no se normaliza contra distrito_id en esta fase.
 */
$erroresViajes = $erroresViajes ?? [];
$filaViaje = function (array $fila = ['pais' => '', 'fecha_salida' => '', 'fecha_retorno' => ''], ?array $error = null): void {
    $errorSalida = $error['fecha_salida'] ?? null;
    $errorRetorno = $error['fecha_retorno'] ?? null;
    ?>
  <div class="subrow">
    <div class="fields thirds" style="flex:1">
      <div class="field">
        <label class="fl">Lugar visitado (país o ciudad)</label>
        <div class="control"><input type="text" name="viaje_pais[]" value="<?= e($fila['pais']) ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Fecha de salida</label>
        <div class="control mono <?= $errorSalida ? 'err' : '' ?>"><input type="date" name="viaje_fecha_salida[]" value="<?= e($fila['fecha_salida'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
        <?php if ($errorSalida): ?><span class="hint err"><?= e($errorSalida) ?></span><?php endif; ?>
      </div>
      <div class="field">
        <label class="fl">Fecha de retorno</label>
        <div class="control mono <?= $errorRetorno ? 'err' : '' ?>"><input type="date" name="viaje_fecha_retorno[]" value="<?= e($fila['fecha_retorno'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
        <?php if ($errorRetorno): ?><span class="hint err"><?= e($errorRetorno) ?></span><?php endif; ?>
      </div>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar viaje" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="subrows" data-lista="viajes">
  <?php foreach ($filasViajes as $i => $fila): $filaViaje($fila, $erroresViajes[$i] ?? null); endforeach; ?>
</div>
<template id="plantilla-viajes"><?php $filaViaje(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-viajes" data-lista="viajes" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar viaje
</button>
