<?php
/**
 * Plantilla especializada para la Sección 6: Complicaciones del embarazo, parto o puerperio actual (Anexo 2) de Muerte Materna (O95).
 */

$valTuvoComp = $valoresCampos[16147] ?? '';

$valCompEmb = $valoresCampos[14342] ?? [];
if (!is_array($valCompEmb)) {
    $valCompEmb = array_filter(array_map('trim', explode(',', (string)$valCompEmb)));
}
$valCompEmbOtro = $valoresCampos[16144] ?? '';

$valCompPart = $valoresCampos[14343] ?? [];
if (!is_array($valCompPart)) {
    $valCompPart = array_filter(array_map('trim', explode(',', (string)$valCompPart)));
}
$valCompPartOtro = $valoresCampos[16145] ?? '';

$valCompPuer = $valoresCampos[14344] ?? [];
if (!is_array($valCompPuer)) {
    $valCompPuer = array_filter(array_map('trim', explode(',', (string)$valCompPuer)));
}
$valCompPuerOtro = $valoresCampos[16146] ?? '';

$esTuvoSi = (strtoupper((string)$valTuvoComp) === 'SI' || $valTuvoComp === '1');
$esEmbOtro = in_array('OTRO', array_map('strtoupper', $valCompEmb), true);
$esPartOtro = in_array('OTRO', array_map('strtoupper', $valCompPart), true);
$esPuerOtro = in_array('OTRO', array_map('strtoupper', $valCompPuer), true);
?>

<div id="bloqueComplicacionesO95">
  <!-- Encabezado: ¿Tuvo complicaciones? (SI / NO / DESCONOCIDO) -->
  <div style="margin-bottom:20px;">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Tuvo complicaciones? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_16147" value="SI" id="o95TuvoCompSi" <?= $esTuvoSi ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_16147" value="NO" id="o95TuvoCompNo" <?= (strtoupper((string)$valTuvoComp) === 'NO' || $valTuvoComp === '0') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_16147" value="DESCONOCIDO" id="o95TuvoCompDescon" <?= (strtoupper((string)$valTuvoComp) === 'DESCONOCIDO') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>Desconocido</span>
      </label>
    </div>
  </div>

  <!-- GRUPOS DE COMPLICACIONES (Visibles únicamente si ¿Tuvo complicaciones? = SÍ) -->
  <div id="bloqueGruposComplicacionesO95" <?= !$esTuvoSi ? 'hidden style="display:none;"' : '' ?>>
    
    <!-- GRUPO 1: Complicaciones del embarazo -->
    <div style="margin-bottom:24px; padding-top:16px; border-top:1px dashed var(--line-2);">
      <div class="eyebrow" style="margin-bottom:12px; font-weight:700; color:var(--accent-deep); text-transform:uppercase; letter-spacing:0.5px;">
        1. Complicaciones del embarazo
      </div>

      <div class="checkbox-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:10px 16px; background:var(--surface-2); padding:16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line);">
        <?php
        $optsEmb = [
            'NINGUNA' => 'Ninguna complicación',
            'HEMORRAGIA' => 'Hemorragia',
            'PREECLAMPSIA_ECLAMPSIA' => 'Preeclampsia/Eclampsia',
            'SINDROME_HELLP' => 'Síndrome de HELLP',
            'DIABETES_GESTACIONAL' => 'Diabetes gestacional',
            'ABORTO' => 'Aborto',
            'DESNUTRICION' => 'Desnutrición',
            'RPM_MAS_12_HORAS' => 'RPM > 12 horas',
            'EMBARAZO_ECTOPICO' => 'Embarazo ectópico',
            'ITU' => 'Infección del tracto urinario',
            'SEPSIS' => 'Sepsis',
            'OBITO_FETAL' => 'Óbito fetal',
            'ANEMIA' => 'Anemia',
            'OTRO' => 'Otro'
        ];
        foreach ($optsEmb as $cod => $lbl):
            $checked = in_array($cod, $valCompEmb, true) || in_array(strtoupper($lbl), array_map('strtoupper', $valCompEmb), true);
        ?>
          <label class="choice" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:var(--ink);">
            <input type="checkbox" name="campo_14342[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> class="o95CompEmbChk" data-codigo="<?= $cod ?>">
            <span style="<?= $cod === 'NINGUNA' ? 'font-weight:700; color:var(--accent-deep);' : '' ?>"><?= e($lbl) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div id="bloqueCompEmbOtroO95" style="margin-top:12px; max-width:400px;" <?= !$esEmbOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra complicación del embarazo</label>
        <div class="control">
          <input type="text" id="o95CompEmbOtroInput" name="campo_16144" value="<?= e($valCompEmbOtro) ?>" placeholder="Especificar complicación…">
        </div>
      </div>
    </div>

    <!-- GRUPO 2: Complicaciones del parto -->
    <div style="margin-bottom:24px; padding-top:16px; border-top:1px dashed var(--line-2);">
      <div class="eyebrow" style="margin-bottom:12px; font-weight:700; color:var(--accent-deep); text-transform:uppercase; letter-spacing:0.5px;">
        2. Complicaciones del parto
      </div>

      <div class="checkbox-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:10px 16px; background:var(--surface-2); padding:16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line);">
        <?php
        $optsPart = [
            'NINGUNA' => 'Ninguna complicación',
            'HEMORRAGIA' => 'Hemorragia',
            'PREECLAMPSIA_ECLAMPSIA' => 'Preeclampsia/Eclampsia',
            'SINDROME_HELLP' => 'Síndrome de HELLP',
            'TRABAJO_PARTO_PROLONGADO' => 'Trabajo de parto prolongado',
            'PARTO_OBSTRUIDO' => 'Parto obstruido',
            'PARTO_DISTOCICO' => 'Parto distócico',
            'TRABAJO_PARTO_PRECIPITADO' => 'Trabajo de parto precipitado',
            'ALUMBRAMIENTO_INCOMPLETO' => 'Alumbramiento incompleto',
            'OTRO' => 'Otro'
        ];
        foreach ($optsPart as $cod => $lbl):
            $checked = in_array($cod, $valCompPart, true) || in_array(strtoupper($lbl), array_map('strtoupper', $valCompPart), true);
        ?>
          <label class="choice" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:var(--ink);">
            <input type="checkbox" name="campo_14343[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> class="o95CompPartChk" data-codigo="<?= $cod ?>">
            <span style="<?= $cod === 'NINGUNA' ? 'font-weight:700; color:var(--accent-deep);' : '' ?>"><?= e($lbl) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div id="bloqueCompPartOtroO95" style="margin-top:12px; max-width:400px;" <?= !$esPartOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra complicación del parto</label>
        <div class="control">
          <input type="text" id="o95CompPartOtroInput" name="campo_16145" value="<?= e($valCompPartOtro) ?>" placeholder="Especificar complicación…">
        </div>
      </div>
    </div>

    <!-- GRUPO 3: Complicaciones del puerperio -->
    <div style="padding-top:16px; border-top:1px dashed var(--line-2);">
      <div class="eyebrow" style="margin-bottom:12px; font-weight:700; color:var(--accent-deep); text-transform:uppercase; letter-spacing:0.5px;">
        3. Complicaciones del puerperio
      </div>

      <div class="checkbox-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:10px 16px; background:var(--surface-2); padding:16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line);">
        <?php
        $optsPuer = [
            'NINGUNA' => 'Ninguna complicación',
            'HEMORRAGIA' => 'Hemorragia',
            'ATONIA_UTERINA' => 'Atonía uterina',
            'PREECLAMPSIA_ECLAMPSIA' => 'Preeclampsia/Eclampsia',
            'SINDROME_HELLP' => 'Síndrome de HELLP',
            'SEPSIS' => 'Sepsis',
            'ENDOMETRITIS' => 'Endometritis',
            'RETENCION_RESTOS_PLACENTARIOS' => 'Retención de restos placentarios',
            'DEPRESION_POSPARTO' => 'Depresión posparto',
            'OTRO' => 'Otro'
        ];
        foreach ($optsPuer as $cod => $lbl):
            $checked = in_array($cod, $valCompPuer, true) || in_array(strtoupper($lbl), array_map('strtoupper', $valCompPuer), true);
        ?>
          <label class="choice" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:var(--ink);">
            <input type="checkbox" name="campo_14344[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> class="o95CompPuerChk" data-codigo="<?= $cod ?>">
            <span style="<?= $cod === 'NINGUNA' ? 'font-weight:700; color:var(--accent-deep);' : '' ?>"><?= e($lbl) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div id="bloqueCompPuerOtroO95" style="margin-top:12px; max-width:400px;" <?= !$esPuerOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra complicación del puerperio</label>
        <div class="control">
          <input type="text" id="o95CompPuerOtroInput" name="campo_16146" value="<?= e($valCompPuerOtro) ?>" placeholder="Especificar complicación…">
        </div>
      </div>
    </div>

  </div>
</div>
