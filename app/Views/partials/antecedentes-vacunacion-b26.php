<?php
/**
 * V. ANTECEDENTES DE VACUNACIÓN (considerar solo vacuna SPR) y VII. OBSERVACIONES
 * Exclusivo para Parotiditis con complicaciones (B26)
 */

$esB26Vacuna = (($enfermedad['cie10'] ?? null) === 'B26');

// Este partial se incluye sin condición aunque B26 no sea la ficha activa,
// igual que lugar-probable-infeccion-b26.php -- $campoB26 resuelve siempre
// contra la B26 real, no contra $enfermedad.
$campoB26 = $resolvedorPara('B26');
$campoVacunaSpr = $campoB26('b26_vacunacion_spr');
$campoDosisSpr = $campoB26('b26_dosis_spr');
$campoFechaUltimaDosisSpr = $campoB26('b26_fecha_ultima_dosis_spr');
$campoEessVacunacionSpr = $campoB26('b26_eess_vacunacion_spr');
$campoObservaciones = $campoB26('b26_observaciones');

// Extraer valores existentes
$valVacunaSpr = $campoVacunaSpr['val'];
if ($valVacunaSpr === '1') $valVacunaSpr = 'SI';
if ($valVacunaSpr === '0') $valVacunaSpr = 'NO';

$valDosisSpr = $campoDosisSpr['val'];
$valFechaUltimaDosisSpr = $campoFechaUltimaDosisSpr['val'];
$valEessVacunacionSpr = $campoEessVacunacionSpr['val'];
$valObservaciones = $campoObservaciones['val'] !== '' ? $campoObservaciones['val'] : ($valoresFijos['observaciones'] ?? '');
?>

<div class="card section b26-vacunacion-card" id="cardVacunacionB26" <?= $esB26Vacuna ? '' : 'hidden style="display:none;"' ?>>
  <div class="section-head">
    <span class="section-num">5</span>
    <h3>Antecedentes de vacunación (considerar solo vacuna SPR)</h3>
  </div>
  <div class="section-body">

    <!-- Vacunación con SPR -->
    <div class="field" style="margin-bottom:16px">
      <label class="fl" style="font-weight:600;font-size:14px;color:var(--text-main);margin-bottom:8px">
        Vacunación con SPR
      </label>
      <div class="control" style="margin-top:6px">
        <div style="display:flex;gap:20px;align-items:center" id="wrapVacunaSprB26">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoVacunaSpr['name'] ?>" value="SI" class="radio-vacuna-spr-b26" <?= ($valVacunaSpr === 'SI') ? 'checked' : '' ?>>
            <span>Sí</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoVacunaSpr['name'] ?>" value="NO" class="radio-vacuna-spr-b26" <?= ($valVacunaSpr === 'NO') ? 'checked' : '' ?>>
            <span>No</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Subcampos condicionales de Vacunación SPR == SI -->
    <div class="fields thirds" id="wrapVacunaSprDetalleB26" style="margin-bottom:20px" <?= ($valVacunaSpr === 'SI') ? '' : 'hidden style="display:none;"' ?>>
      <div class="field">
        <label class="fl">N.° de dosis recibida</label>
        <div class="control mono">
          <input type="number" min="1" step="1" name="<?= $campoDosisSpr['name'] ?>" value="<?= e($valDosisSpr) ?>" placeholder="1" class="inp-dias-pos-b26" style="text-align:center">
        </div>
      </div>
      <div class="field">
        <label class="fl">Fecha de última dosis</label>
        <div class="control mono">
          <input type="date" name="<?= $campoFechaUltimaDosisSpr['name'] ?>" value="<?= e($valFechaUltimaDosisSpr) ?>" max="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="field">
        <label class="fl">EE.SS. donde se vacunó</label>
        <div class="control">
          <input type="text" name="<?= $campoEessVacunacionSpr['name'] ?>" value="<?= e($valEessVacunacionSpr) ?>" placeholder="Nombre del EE.SS.…">
        </div>
      </div>
    </div>

    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">

    <!-- Observaciones -->
    <div class="field" style="margin-bottom:0">
      <label class="fl" style="font-weight:600;font-size:14px;color:var(--text-main);margin-bottom:8px">
        Observaciones
      </label>
      <div class="control">
        <textarea name="<?= $campoObservaciones['name'] ?>" rows="3" placeholder="Observaciones o notas adicionales del caso…" style="width:100%;border-radius:8px;padding:10px;border:1px solid var(--line);background:var(--card-bg, rgba(255,255,255,0.02));color:var(--text-main);font-family:inherit;font-size:14px;resize:vertical"><?= e($valObservaciones) ?></textarea>
      </div>
    </div>

  </div>
</div>
