<?php
/**
 * Plantilla especializada para la Sección: Atención prenatal (Anexo 2) de Muerte Materna (O95).
 */

$valRecibioApn    = $valoresCampos[14334] ?? '';
$valTrimestre     = $valoresCampos[14335] ?? '';
$valNumApn        = isset($valoresCampos[14336]) && $valoresCampos[14336] !== '' ? (int)$valoresCampos[14336] : 0;
$valNombreEessApn = $valoresCampos[14337] ?? '';
$valCatEessApn    = $valoresCampos[16142] ?? '';
$valResponsable   = $valoresCampos[14341] ?? '';
$valRespOtro      = $valoresCampos[16143] ?? '';

$valVisitasDom    = $valoresCampos[14338] ?? '';
$valNumVisitas    = isset($valoresCampos[14339]) && $valoresCampos[14339] !== '' ? (int)$valoresCampos[14339] : 0;
$valPlanParto     = $valoresCampos[14340] ?? '';

$esApnSi = ($valRecibioApn === '1' || strtoupper((string)$valRecibioApn) === 'SI');
$esVisitasSi = ($valVisitasDom === '1' || strtoupper((string)$valVisitasDom) === 'SI');
$esRespOtro = (strtoupper((string)$valResponsable) === 'OTRO');
?>

<div id="bloqueAtencionPrenatalO95">
  <!-- 1. ATENCIÓN PRENATAL (APN): ¿Recibió APN? (SI / NO) -->
  <div style="margin-bottom:20px;">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Recibió Atención Prenatal (APN)? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14334" value="1" id="o95RecibioApnSi" <?= $esApnSi ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14334" value="0" id="o95RecibioApnNo" <?= (!$esApnSi && $valRecibioApn !== '') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>
  </div>

  <!-- DETALLES DE LA APN (Visible únicamente si ¿Recibió APN? = SÍ) -->
  <div id="bloqueApnDetallesO95" style="margin-bottom:24px; background:var(--surface-2); padding:18px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line);" <?= !$esApnSi ? 'hidden style="display:none;"' : '' ?>>
    <div class="fields pairs" style="margin-bottom:14px;">
      <div class="field">
        <label class="fl">Primera atención (Trimestre)</label>
        <div class="control">
          <select id="o95TrimestreSel" name="campo_14335" data-nosearch="true">
            <option value="">Seleccionar…</option>
            <option value="I" <?= seleccionado($valTrimestre, 'I') ?>>I Trimestre</option>
            <option value="II" <?= seleccionado($valTrimestre, 'II') ?>>II Trimestre</option>
            <option value="III" <?= seleccionado($valTrimestre, 'III') ?>>III Trimestre</option>
          </select>
        </div>
      </div>

      <div class="field">
        <label class="fl">Número de APN</label>
        <div class="control">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95NumApnInput" name="campo_14336" value="<?= $valNumApn ?>" placeholder="0" class="solo-enteros">
        </div>
      </div>
    </div>

    <div class="fields pairs" style="margin-bottom:14px;">
      <div class="field">
        <label class="fl">Nombre del EE.SS. con mayor cantidad de atenciones prenatales</label>
        <div class="control">
          <input type="text" id="o95NombreEessApnInput" name="campo_14337" value="<?= e($valNombreEessApn) ?>" placeholder="Nombre del establecimiento…">
        </div>
      </div>

      <div class="field">
        <label class="fl">Categoría del EE.SS. de APN</label>
        <div class="control">
          <select id="o95CatEessApnSel" name="campo_16142">
            <option value="">Seleccionar categoría…</option>
            <?php foreach (['I-1', 'I-2', 'I-3', 'I-4', 'II-1', 'II-2', 'II-E', 'III-1', 'III-2', 'III-E', 'Desconocido'] as $catItem): ?>
              <option value="<?= $catItem ?>" <?= seleccionado($valCatEessApn, $catItem) ?>><?= $catItem ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="fields pairs">
      <div class="field">
        <label class="fl">Responsable de la APN</label>
        <div class="control">
          <select id="o95ResponsableApnSel" name="campo_14341">
            <option value="">Seleccionar responsable…</option>
            <?php foreach (['Médico G-O', 'Médico residente', 'Médico general', 'Obstetra', 'Enfermera(o)', 'Interno', 'Técnico', 'Otro', 'Desconocido'] as $respItem): ?>
              <option value="<?= $respItem ?>" <?= seleccionado($valResponsable, $respItem) ?>><?= $respItem ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field" id="bloqueResponsableApnOtroO95" <?= !$esRespOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otro responsable</label>
        <div class="control">
          <input type="text" id="o95RespOtroInput" name="campo_16143" value="<?= e($valRespOtro) ?>" placeholder="Especificar profesión/cargo…">
        </div>
      </div>
    </div>
  </div>

  <!-- 2. VISITAS DOMICILIARIAS -->
  <div style="margin-bottom:20px; padding-top:16px; border-top:1px dashed var(--line-2);">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Se realizaron visitas domiciliarias? <span class="req">*</span></label>
    <div class="control-radio-group" style="margin-bottom:12px;">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14338" value="1" id="o95VisitasDomSi" <?= $esVisitasSi ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14338" value="0" id="o95VisitasDomNo" <?= (!$esVisitasSi && $valVisitasDom !== '') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>

    <!-- Número de visitas domiciliarias (Visible únicamente si ¿Visitas domiciliarias? = SÍ) -->
    <div id="bloqueVisitasDomDetallesO95" style="max-width:300px;" <?= !$esVisitasSi ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl">Número de visitas domiciliarias</label>
      <div class="control">
        <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95NumVisitasInput" name="campo_14339" value="<?= $valNumVisitas ?>" placeholder="0" class="solo-enteros">
      </div>
    </div>
  </div>

  <!-- 3. PLAN DE PARTO COMPLETO -->
  <div style="padding-top:16px; border-top:1px dashed var(--line-2);">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Se realizó plan de parto completo? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14340" value="1" <?= ($valPlanParto === '1' || strtoupper((string)$valPlanParto) === 'SI') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14340" value="0" <?= ($valPlanParto === '0' || strtoupper((string)$valPlanParto) === 'NO') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>
  </div>
</div>
