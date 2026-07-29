<?php
/**
 * Plantilla especializada para la Sección 7: Hospitalizaciones (Anexo 2) de Muerte Materna (O95).
 */

$campoHospGestPuer = $campo('o95_hospitalizaciones_en_la_gestacion_puerperio');
$campoCuantasHosp = $campo('o95_cuantas_hospitalizaciones');
$campoTransfusion = $campo('o95_requirio_transfusion_de_sangre');
$campoExpansores = $campo('o95_expansores_plasmaticos');

$valHospGestPuer = $campoHospGestPuer['val'];
$valCuantasHosp  = $campoCuantasHosp['val'] !== '' ? (int) $campoCuantasHosp['val'] : 0;
$valTransfusion  = $campoTransfusion['val'];
$valExpansores   = $campoExpansores['val'];

$esHospSi = ($valHospGestPuer === '1' || strtoupper((string)$valHospGestPuer) === 'SI');
?>

<div id="bloqueHospitalizacionesO95">
  <!-- 1. ¿Hospitalizaciones en la gestación/puerperio? (SI / NO) -->
  <div style="margin-bottom:20px;">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Hospitalizaciones en la gestación/puerperio? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="<?= $campoHospGestPuer['name'] ?>" value="1" id="o95HospGestSi" <?= $esHospSi ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="<?= $campoHospGestPuer['name'] ?>" value="0" id="o95HospGestNo" <?= (!$esHospSi && $valHospGestPuer !== '') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>
  </div>

  <!-- Detalle: Cuántas hospitalizaciones (Visible únicamente si ¿Hospitalizaciones? = SÍ) -->
  <div id="bloqueCuantasHospO95" style="margin-bottom:24px; max-width:300px;" <?= !$esHospSi ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Cuántas hospitalizaciones</label>
    <div class="control">
      <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95CuantasHospInput" name="<?= $campoCuantasHosp['name'] ?>" value="<?= $valCuantasHosp ?>" placeholder="0" class="solo-enteros">
    </div>
  </div>

  <!-- 2. ¿Requirió transfusión de sangre? -->
  <div style="margin-bottom:20px; padding-top:16px; border-top:1px dashed var(--line-2);">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Requirió transfusión de sangre? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="<?= $campoTransfusion['name'] ?>" value="1" <?= ($valTransfusion === '1' || strtoupper((string)$valTransfusion) === 'SI') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="<?= $campoTransfusion['name'] ?>" value="0" <?= ($valTransfusion === '0' || strtoupper((string)$valTransfusion) === 'NO') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>
  </div>

  <!-- 3. ¿Expansores plasmáticos? -->
  <div style="padding-top:16px; border-top:1px dashed var(--line-2);">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Expansores plasmáticos? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="<?= $campoExpansores['name'] ?>" value="1" <?= ($valExpansores === '1' || strtoupper((string)$valExpansores) === 'SI') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="<?= $campoExpansores['name'] ?>" value="0" <?= ($valExpansores === '0' || strtoupper((string)$valExpansores) === 'NO') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>
  </div>
</div>
