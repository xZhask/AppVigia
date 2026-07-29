<?php
/**
 * Plantilla especializada para la Sección 8: Parto o aborto (Anexo 2) de Muerte Materna (O95).
 */

$valFechaParto     = $valoresCampos[14351] ?? '';
$valFechaPartoDesc = $valoresCampos[16160] ?? '';
$valFechaPartoNA   = $valoresCampos[16161] ?? '';

$valLugarParto     = $valoresCampos[14352] ?? '';
$valLugarPartoEess = $valoresCampos[16162] ?? '';
$valLugarPartoOtro = $valoresCampos[16163] ?? '';

$valTipoParto      = $valoresCampos[14353] ?? '';

$valRespParto      = $valoresCampos[14354] ?? '';
$valRespPartoOtro  = $valoresCampos[16164] ?? '';

$valNecropsia      = $valoresCampos[14355] ?? '';
$valCausaNecropsia = $valoresCampos[14356] ?? '';

$esFechaPartoDesc = ($valFechaPartoDesc === '1' || strtoupper((string)$valFechaPartoDesc) === 'SI');
$esFechaPartoNA   = ($valFechaPartoNA === '1' || strtoupper((string)$valFechaPartoNA) === 'SI');

$esLugarEess = (strtoupper((string)$valLugarParto) === 'EESS' || strtoupper((string)$valLugarParto) === 'EN EESS');
$esLugarOtro = (strtoupper((string)$valLugarParto) === 'OTRO');

$esRespPartoOtro = (strtoupper((string)$valRespParto) === 'OTRO');
$esNecropsiaSi = ($valNecropsia === '1' || strtoupper((string)$valNecropsia) === 'SI');
?>

<div id="bloquePartoAbortoO95">
  <!-- 1. Fecha de parto o aborto + Opciones Desconocida / No aplica en una sola fila -->
  <div class="fields pairs" style="margin-bottom:16px;">
    <div class="field" style="grid-column: span 1;">
      <label class="fl">Fecha de parto o aborto</label>
      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <div class="control mono" style="flex:1; min-width:150px;">
          <input type="date" id="o95FechaPartoInput" name="campo_14351" value="<?= e($valFechaParto) ?>" max="<?= date('Y-m-d') ?>" <?= ($esFechaPartoDesc || $esFechaPartoNA) ? 'disabled' : '' ?>>
        </div>
        <div style="display:inline-flex; align-items:center; gap:12px; background:var(--surface-2); padding:7px 12px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line);">
          <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-size:0.8125rem; font-weight:600; color:var(--ink); user-select:none;">
            <input type="checkbox" id="o95FechaPartoDesconChk" name="campo_16160" value="1" <?= $esFechaPartoDesc ? 'checked' : '' ?> style="accent-color:var(--accent); width:15px; height:15px;">
            <span>Desconocida</span>
          </label>
          <span style="color:var(--line); margin:0 2px;">|</span>
          <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-size:0.8125rem; font-weight:600; color:var(--ink); user-select:none;">
            <input type="checkbox" id="o95FechaPartoNoAplicaChk" name="campo_16161" value="1" <?= $esFechaPartoNA ? 'checked' : '' ?> style="accent-color:var(--accent); width:15px; height:15px;">
            <span>No aplica</span>
          </label>
        </div>
      </div>
    </div>

    <!-- 2. Lugar de parto o aborto -->
    <div class="field" style="grid-column: span 1;">
      <label class="fl">Lugar de parto o aborto</label>
      <div class="control">
        <select id="o95LugarPartoSel" name="campo_14352">
          <option value="">Seleccionar lugar…</option>
          <option value="DOMICILIO" <?= seleccionado($valLugarParto, 'DOMICILIO') ?>>Domicilio</option>
          <option value="EESS" <?= seleccionado($valLugarParto, 'EESS') ?>>En EE.SS.</option>
          <option value="OTRO" <?= seleccionado($valLugarParto, 'OTRO') ?>>Otro</option>
          <option value="NO_APLICA" <?= seleccionado($valLugarParto, 'NO_APLICA') ?>>No aplica</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Especificaciones del lugar (Nombre EE.SS. / Otro) -->
  <div class="fields pairs" style="margin-bottom:16px;">
    <div class="field" id="bloqueLugarPartoEessO95" <?= !$esLugarEess ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl">Nombre del EE.SS. del parto o aborto</label>
      <div class="control">
        <input type="text" id="o95LugarPartoEessInput" name="campo_16162" value="<?= e($valLugarPartoEess) ?>" placeholder="Nombre del EE.SS.…">
      </div>
    </div>

    <div class="field" id="bloqueLugarPartoOtroO95" <?= !$esLugarOtro ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl">Especificar otro lugar</label>
      <div class="control">
        <input type="text" id="o95LugarPartoOtroInput" name="campo_16163" value="<?= e($valLugarPartoOtro) ?>" placeholder="Especificar lugar…">
      </div>
    </div>
  </div>

  <!-- 3. Tipo de parto y 4. Responsable de la atención -->
  <div class="fields pairs" style="margin-bottom:16px;">
    <!-- Tipo de parto -->
    <div class="field">
      <label class="fl">Tipo de parto</label>
      <div class="control">
        <select id="o95TipoPartoSel" name="campo_14353">
          <option value="">Seleccionar tipo…</option>
          <option value="VAGINAL" <?= seleccionado($valTipoParto, 'VAGINAL') ?>>Vaginal</option>
          <option value="CESAREA" <?= seleccionado($valTipoParto, 'CESAREA') ?>>Cesárea</option>
          <option value="INSTRUMENTADO" <?= seleccionado($valTipoParto, 'INSTRUMENTADO') ?>>Instrumentado</option>
          <option value="DESCONOCIDO" <?= seleccionado($valTipoParto, 'DESCONOCIDO') ?>>Desconocido</option>
          <option value="NO_APLICA" <?= seleccionado($valTipoParto, 'NO_APLICA') ?>>No aplica</option>
        </select>
      </div>
    </div>

    <!-- Responsable de la atención -->
    <div class="field">
      <label class="fl">Responsable de la atención del parto o aborto</label>
      <div class="control">
        <select id="o95RespPartoSel" name="campo_14354">
          <option value="">Seleccionar responsable…</option>
          <option value="MED_G_O" <?= seleccionado($valRespParto, 'MED_G_O') ?>>Méd. G-O</option>
          <option value="MED_INTENSIVISTA" <?= seleccionado($valRespParto, 'MED_INTENSIVISTA') ?>>Méd. intensivista</option>
          <option value="MED_RESIDENTE" <?= seleccionado($valRespParto, 'MED_RESIDENTE') ?>>Méd. residente</option>
          <option value="MED_GENERAL" <?= seleccionado($valRespParto, 'MED_GENERAL') ?>>Méd. general</option>
          <option value="OBSTETRA" <?= seleccionado($valRespParto, 'OBSTETRA') ?>>Obstetra</option>
          <option value="ENFERMERA" <?= seleccionado($valRespParto, 'ENFERMERA') ?>>Enfermera</option>
          <option value="INTERNO" <?= seleccionado($valRespParto, 'INTERNO') ?>>Interno</option>
          <option value="TECNICO" <?= seleccionado($valRespParto, 'TECNICO') ?>>Técnico</option>
          <option value="PARTERA" <?= seleccionado($valRespParto, 'PARTERA') ?>>Partera</option>
          <option value="FAMILIAR" <?= seleccionado($valRespParto, 'FAMILIAR') ?>>Familiar</option>
          <option value="OTRO" <?= seleccionado($valRespParto, 'OTRO') ?>>Otro</option>
          <option value="DESCONOCIDO" <?= seleccionado($valRespParto, 'DESCONOCIDO') ?>>Desconocido</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Especificación de Responsable del parto (Otro) -->
  <div id="bloqueRespPartoOtroO95" style="margin-bottom:16px; max-width:400px;" <?= !$esRespPartoOtro ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Especificar otro responsable</label>
    <div class="control">
      <input type="text" id="o95RespPartoOtroInput" name="campo_16164" value="<?= e($valRespPartoOtro) ?>" placeholder="Especificar profesión/cargo…">
    </div>
  </div>

  <!-- 5. Necropsia (SI / NO) -->
  <div style="margin-bottom:16px; padding-top:16px; border-top:1px dashed var(--line-2);">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Necropsia? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14355" value="1" id="o95NecropsiaSi" <?= $esNecropsiaSi ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14355" value="0" id="o95NecropsiaNo" <?= (!$esNecropsiaSi && $valNecropsia !== '') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>
  </div>

  <!-- Diagnóstico / causa CIE-10 (necropsia) -->
  <div id="bloqueNecropsiaCausaO95" style="margin-top:12px; max-width:500px;" <?= !$esNecropsiaSi ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Diagnóstico / causa CIE-10 (necropsia)</label>
    <div class="control">
      <input type="text" id="o95CausaNecropsiaInput" name="campo_14356" value="<?= e($valCausaNecropsia) ?>" placeholder="Diagnóstico o código CIE-10 de necropsia…">
    </div>
  </div>
</div>
