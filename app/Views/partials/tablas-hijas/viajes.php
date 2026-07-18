<?php
/**
 * Fila dinámica de viajes del caso (caso_viaje). Variable esperada:
 * $filasViajes (array de ['pais','fecha_salida','fecha_retorno']).
 * `pais` se usa como "lugar visitado" libre (nacional o internacional);
 * no se normaliza contra distrito_id en esta fase.
 */
$filaViaje = function (array $fila = ['pais' => '', 'fecha_salida' => '', 'fecha_retorno' => '']): void {
    $salidaDmy = fechaIsoADmy($fila['fecha_salida'] ?? '') ?: ($fila['fecha_salida'] ?? '');
    $retornoDmy = fechaIsoADmy($fila['fecha_retorno'] ?? '') ?: ($fila['fecha_retorno'] ?? '');
    ?>
  <div class="subrow">
    <div class="fields thirds" style="flex:1">
      <div class="field">
        <label class="fl">Lugar visitado (país o ciudad)</label>
        <div class="control"><input type="text" name="viaje_pais[]" value="<?= e($fila['pais']) ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Fecha de salida</label>
        <div class="control mono"><input type="text" name="viaje_fecha_salida[]" value="<?= e($salidaDmy) ?>" placeholder="dd/mm/aaaa"></div>
      </div>
      <div class="field">
        <label class="fl">Fecha de retorno</label>
        <div class="control mono"><input type="text" name="viaje_fecha_retorno[]" value="<?= e($retornoDmy) ?>" placeholder="dd/mm/aaaa"></div>
      </div>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar viaje" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="subrows" data-lista="viajes">
  <?php foreach ($filasViajes as $fila): $filaViaje($fila); endforeach; ?>
</div>
<template id="plantilla-viajes"><?php $filaViaje(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-viajes" data-lista="viajes" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar viaje
</button>
