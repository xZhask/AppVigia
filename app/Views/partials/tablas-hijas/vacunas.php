<?php
/**
 * Fila dinámica de antecedentes vacunales del caso (caso_vacuna). Variable
 * esperada: $filasVacunas (array de ['vacuna','dosis','fecha']).
 */
$filaVacuna = function (array $fila = ['vacuna' => '', 'dosis' => '', 'fecha' => '']): void {
    $fechaDmy = fechaIsoADmy($fila['fecha'] ?? '') ?: ($fila['fecha'] ?? '');
    ?>
  <div class="subrow">
    <div class="fields thirds" style="flex:1">
      <div class="field">
        <label class="fl">Vacuna</label>
        <div class="control"><input type="text" name="vacuna_nombre[]" value="<?= e($fila['vacuna']) ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Dosis</label>
        <div class="control"><input type="text" name="vacuna_dosis[]" value="<?= e($fila['dosis']) ?>" placeholder="1ra, 2da, refuerzo…"></div>
      </div>
      <div class="field">
        <label class="fl">Fecha de aplicación</label>
        <div class="control mono"><input type="text" name="vacuna_fecha[]" value="<?= e($fechaDmy) ?>" placeholder="dd/mm/aaaa"></div>
      </div>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar vacuna" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="subrows" data-lista="vacunas">
  <?php foreach ($filasVacunas as $fila): $filaVacuna($fila); endforeach; ?>
</div>
<template id="plantilla-vacunas"><?php $filaVacuna(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-vacunas" data-lista="vacunas" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar antecedente vacunal
</button>
