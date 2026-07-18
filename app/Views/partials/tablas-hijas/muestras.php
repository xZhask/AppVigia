<?php
/**
 * Fila dinámica de muestras de laboratorio del caso (caso_muestra). Variables
 * esperadas: $filasMuestras (array de
 * ['tipo_muestra','tipo_prueba','resultado','fecha_toma','fecha_result']),
 * $opcionesTipoMuestra, $opcionesTipoPrueba, $opcionesResultado (catalogo_item).
 */
$filaMuestra = function (array $fila = ['tipo_muestra' => '', 'tipo_prueba' => '', 'resultado' => '', 'fecha_toma' => '', 'fecha_result' => '']) use ($opcionesTipoMuestra, $opcionesTipoPrueba, $opcionesResultado): void {
    $tomaDmy = fechaIsoADmy($fila['fecha_toma'] ?? '') ?: ($fila['fecha_toma'] ?? '');
    $resultDmy = fechaIsoADmy($fila['fecha_result'] ?? '') ?: ($fila['fecha_result'] ?? '');
    ?>
  <div class="subrow">
    <div class="fields thirds" style="flex:1">
      <div class="field">
        <label class="fl">Tipo de muestra</label>
        <div class="control">
          <select name="muestra_tipo_muestra[]">
            <option value="">Seleccionar…</option>
            <?php foreach ($opcionesTipoMuestra as $op): ?>
              <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['tipo_muestra'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field">
        <label class="fl">Tipo de prueba</label>
        <div class="control">
          <select name="muestra_tipo_prueba[]">
            <option value="">Seleccionar…</option>
            <?php foreach ($opcionesTipoPrueba as $op): ?>
              <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['tipo_prueba'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field">
        <label class="fl">Resultado</label>
        <div class="control">
          <select name="muestra_resultado[]">
            <option value="">Pendiente</option>
            <?php foreach ($opcionesResultado as $op): ?>
              <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['resultado'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field">
        <label class="fl">Fecha de toma</label>
        <div class="control mono"><input type="text" name="muestra_fecha_toma[]" value="<?= e($tomaDmy) ?>" placeholder="dd/mm/aaaa"></div>
      </div>
      <div class="field">
        <label class="fl">Fecha de resultado</label>
        <div class="control mono"><input type="text" name="muestra_fecha_result[]" value="<?= e($resultDmy) ?>" placeholder="dd/mm/aaaa"></div>
      </div>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar muestra" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="subrows" data-lista="muestras">
  <?php foreach ($filasMuestras as $fila): $filaMuestra($fila); endforeach; ?>
</div>
<template id="plantilla-muestras"><?php $filaMuestra(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-muestras" data-lista="muestras" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar muestra
</button>
