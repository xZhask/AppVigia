<?php
/**
 * Plantilla especializada para la Sección: Atención prenatal (Anexo 2) de Muerte Materna (O95).
 */

$campoRecibioApn = $campo('o95_recibio_apn');
$campoTrimestre = $campo('o95_primera_atencion_trimestre');
$campoNumApn = $campo('o95_numero_de_apn');
$campoNombreEessApn = $campo('o95_ee_ss_con_mayor_cantidad_de_atenciones');
$campoCatEessApn = $campo('o95_categoria_eess_apn');
$campoResponsableApn = $campo('o95_responsable_de_la_apn');
$campoRespApnOtro = $campo('o95_responsable_apn_otro');
$campoVisitasDom = $campo('o95_se_realizaron_visitas_domiciliarias');
$campoNumVisitas = $campo('o95_numero_de_visitas_domiciliarias');
$campoPlanParto = $campo('o95_se_realizo_plan_de_parto_completo');

$valRecibioApn    = $campoRecibioApn['val'];
$valTrimestre     = $campoTrimestre['val'];
$valNumApn        = $campoNumApn['val'] !== '' ? (int) $campoNumApn['val'] : 0;
$valNombreEessApn = $campoNombreEessApn['val'];
$valCatEessApn    = $campoCatEessApn['val'];
$valResponsable   = $campoResponsableApn['val'];
$valRespOtro      = $campoRespApnOtro['val'];

$valVisitasDom    = $campoVisitasDom['val'];
$valNumVisitas    = $campoNumVisitas['val'] !== '' ? (int) $campoNumVisitas['val'] : 0;
$valPlanParto     = $campoPlanParto['val'];

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
        <input type="radio" name="<?= $campoRecibioApn['name'] ?>" value="1" id="o95RecibioApnSi" <?= $esApnSi ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="<?= $campoRecibioApn['name'] ?>" value="0" id="o95RecibioApnNo" <?= (!$esApnSi && $valRecibioApn !== '') ? 'checked' : '' ?> style="accent-color:var(--accent);">
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
          <select id="o95TrimestreSel" name="<?= $campoTrimestre['name'] ?>" data-nosearch="true">
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
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95NumApnInput" name="<?= $campoNumApn['name'] ?>" value="<?= $valNumApn ?>" placeholder="0" class="solo-enteros">
        </div>
      </div>
    </div>

    <div class="fields pairs" style="margin-bottom:14px;">
      <div class="field">
        <label class="fl">Nombre del EE.SS. con mayor cantidad de atenciones prenatales</label>
        <div class="control">
          <input type="text" id="o95NombreEessApnInput" name="<?= $campoNombreEessApn['name'] ?>" value="<?= e($valNombreEessApn) ?>" placeholder="Nombre del establecimiento…">
        </div>
      </div>

      <div class="field">
        <label class="fl">Categoría del EE.SS. de APN</label>
        <div class="control">
          <select id="o95CatEessApnSel" name="<?= $campoCatEessApn['name'] ?>">
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
          <select id="o95ResponsableApnSel" name="<?= $campoResponsableApn['name'] ?>">
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
          <input type="text" id="o95RespOtroInput" name="<?= $campoRespApnOtro['name'] ?>" value="<?= e($valRespOtro) ?>" placeholder="Especificar profesión/cargo…">
        </div>
      </div>
    </div>
  </div>

  <!-- 2. VISITAS DOMICILIARIAS -->
  <div style="margin-bottom:20px; padding-top:16px; border-top:1px dashed var(--line-2);">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Se realizaron visitas domiciliarias? <span class="req">*</span></label>
    <div class="control-radio-group" style="margin-bottom:12px;">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="<?= $campoVisitasDom['name'] ?>" value="1" id="o95VisitasDomSi" <?= $esVisitasSi ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="<?= $campoVisitasDom['name'] ?>" value="0" id="o95VisitasDomNo" <?= (!$esVisitasSi && $valVisitasDom !== '') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>

    <!-- Número de visitas domiciliarias (Visible únicamente si ¿Visitas domiciliarias? = SÍ) -->
    <div id="bloqueVisitasDomDetallesO95" style="max-width:300px;" <?= !$esVisitasSi ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl">Número de visitas domiciliarias</label>
      <div class="control">
        <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95NumVisitasInput" name="<?= $campoNumVisitas['name'] ?>" value="<?= $valNumVisitas ?>" placeholder="0" class="solo-enteros">
      </div>
    </div>
  </div>

  <!-- 3. PLAN DE PARTO COMPLETO -->
  <div style="padding-top:16px; border-top:1px dashed var(--line-2);">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Se realizó plan de parto completo? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="<?= $campoPlanParto['name'] ?>" value="1" <?= ($valPlanParto === '1' || strtoupper((string)$valPlanParto) === 'SI') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="<?= $campoPlanParto['name'] ?>" value="0" <?= ($valPlanParto === '0' || strtoupper((string)$valPlanParto) === 'NO') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>
  </div>
</div>
