<?php
/**
 * Fila dinámica de antecedentes vacunales del caso (caso_vacuna). Variable
 * esperada: $filasVacunas (array de ['vacuna','dosis','fecha','fabricante',
 * 'lote','via','sitio','fecha_vencimiento','establecimiento']). Las últimas
 * 6 columnas se agregaron para poder retirar de campo_def los campos
 * sueltos de vacunación de ESAVI, tos ferina y difteria
 * (CIERRE_RECARGA_Y_FASE5.md Parte 2) -- se muestran siempre, aunque una
 * ficha no las necesite todas, mismo criterio que contactos.php.
 */
$erroresVacunas = $erroresVacunas ?? [];
$filaVacuna = function (array $fila = [
    'vacuna' => '', 'dosis' => '', 'fecha' => '', 'fabricante' => '', 'lote' => '',
    'via' => '', 'sitio' => '', 'fecha_vencimiento' => '', 'establecimiento' => '',
], ?array $error = null): void {
    $errorFecha = $error['fecha'] ?? null;
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
        <div class="control mono <?= $errorFecha ? 'err' : '' ?>"><input type="date" name="vacuna_fecha[]" value="<?= e($fila['fecha'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
        <?php if ($errorFecha): ?><span class="hint err"><?= e($errorFecha) ?></span><?php endif; ?>
      </div>
      <div class="field">
        <label class="fl">Fabricante</label>
        <div class="control"><input type="text" name="vacuna_fabricante[]" value="<?= e($fila['fabricante'] ?? '') ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Lote</label>
        <div class="control mono"><input type="text" name="vacuna_lote[]" value="<?= e($fila['lote'] ?? '') ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Vía</label>
        <div class="control"><input type="text" name="vacuna_via[]" value="<?= e($fila['via'] ?? '') ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Sitio</label>
        <div class="control"><input type="text" name="vacuna_sitio[]" value="<?= e($fila['sitio'] ?? '') ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Fecha de vencimiento</label>
        <div class="control mono"><input type="date" name="vacuna_fecha_vencimiento[]" value="<?= e($fila['fecha_vencimiento'] ?? '') ?>"></div>
      </div>
      <div class="field">
        <label class="fl">EE.SS. que vacunó</label>
        <div class="control"><input type="text" name="vacuna_establecimiento[]" value="<?= e($fila['establecimiento'] ?? '') ?>"></div>
      </div>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar vacuna" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="subrows" data-lista="vacunas">
  <?php foreach ($filasVacunas as $i => $fila): $filaVacuna($fila, $erroresVacunas[$i] ?? null); endforeach; ?>
</div>
<template id="plantilla-vacunas"><?php $filaVacuna(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-vacunas" data-lista="vacunas" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar antecedente vacunal
</button>
