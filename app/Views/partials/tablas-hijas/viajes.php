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
    <div class="fields" style="flex:1; display:flex; flex-wrap:wrap; gap:12px">
      <!-- 1. Lugar visitado -->
      <div class="field" style="flex:2; min-width:200px">
        <label class="fl">Lugar visitado (país o ciudad)</label>
        <div class="control"><input type="text" name="viaje_pais[]" value="<?= e($fila['pais'] ?? '') ?>" placeholder="País o ciudad…"></div>
      </div>
      <!-- 2. Fecha de ingreso -->
      <div class="field" style="flex:1; min-width:130px">
        <label class="fl">Fecha de ingreso</label>
        <div class="control mono <?= $errorSalida ? 'err' : '' ?>"><input type="date" name="viaje_fecha_salida[]" value="<?= e($fila['fecha_salida'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
        <?php if ($errorSalida): ?><span class="hint err"><?= e($errorSalida) ?></span><?php endif; ?>
      </div>
      <!-- 3. Transporte ida -->
      <div class="field" style="flex:1; min-width:130px">
        <label class="fl">Transporte ida</label>
        <div class="control">
          <select name="viaje_transporte_ida[]">
            <option value="">Seleccionar…</option>
            <option value="AEREO" <?= seleccionado($fila['transporte_ida'] ?? '', 'AEREO') ?>>Aéreo</option>
            <option value="TERRESTRE" <?= seleccionado($fila['transporte_ida'] ?? '', 'TERRESTRE') ?>>Terrestre</option>
            <option value="MARITIMO" <?= seleccionado($fila['transporte_ida'] ?? '', 'MARITIMO') ?>>Marítimo</option>
            <option value="OTRO" <?= seleccionado($fila['transporte_ida'] ?? '', 'OTRO') ?>>Otro</option>
          </select>
        </div>
      </div>
      <!-- 4. Fecha de salida -->
      <div class="field" style="flex:1; min-width:130px">
        <label class="fl">Fecha de salida</label>
        <div class="control mono <?= $errorRetorno ? 'err' : '' ?>"><input type="date" name="viaje_fecha_retorno[]" value="<?= e($fila['fecha_retorno'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
        <?php if ($errorRetorno): ?><span class="hint err"><?= e($errorRetorno) ?></span><?php endif; ?>
      </div>
      <!-- 5. Transporte retorno -->
      <div class="field" style="flex:1; min-width:130px">
        <label class="fl">Transporte retorno</label>
        <div class="control">
          <select name="viaje_transporte_retorno[]">
            <option value="">Seleccionar…</option>
            <option value="AEREO" <?= seleccionado($fila['transporte_retorno'] ?? '', 'AEREO') ?>>Aéreo</option>
            <option value="TERRESTRE" <?= seleccionado($fila['transporte_retorno'] ?? '', 'TERRESTRE') ?>>Terrestre</option>
            <option value="MARITIMO" <?= seleccionado($fila['transporte_retorno'] ?? '', 'MARITIMO') ?>>Marítimo</option>
            <option value="OTRO" <?= seleccionado($fila['transporte_retorno'] ?? '', 'OTRO') ?>>Otro</option>
          </select>
        </div>
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
