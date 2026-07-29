<?php
/**
 * Plantilla especializada para la Sección: Antecedentes patológicos y obstétricos (Anexo 2) de Muerte Materna (O95).
 */

// I. Patológicos
$valPatologicos = $valoresCampos[14324] ?? [];
if (!is_array($valPatologicos)) {
    $valPatologicos = array_filter(array_map('trim', explode(',', (string)$valPatologicos)));
}
$valPatologicosOtra = $valoresCampos[16138] ?? '';

// II. Gineco Obstétricos - Valores iniciales por defecto 0 si están vacíos
$valGestaciones = isset($valoresCampos[14325]) && $valoresCampos[14325] !== '' ? (int)$valoresCampos[14325] : 0;
$valPartos      = isset($valoresCampos[14326]) && $valoresCampos[14326] !== '' ? (int)$valoresCampos[14326] : 0;
$valCesareas    = isset($valoresCampos[14327]) && $valoresCampos[14327] !== '' ? (int)$valoresCampos[14327] : 0;
$valAbortos     = isset($valoresCampos[14328]) && $valoresCampos[14328] !== '' ? (int)$valoresCampos[14328] : 0;
$valNacVivos    = isset($valoresCampos[14329]) && $valoresCampos[14329] !== '' ? (int)$valoresCampos[14329] : 0;
$valNacMuertos  = isset($valoresCampos[14330]) && $valoresCampos[14330] !== '' ? (int)$valoresCampos[14330] : 0;
$valHijosViven  = isset($valoresCampos[14331]) && $valoresCampos[14331] !== '' ? (int)$valoresCampos[14331] : 0;

$valIntergenesicoAnios = isset($valoresCampos[16139]) && $valoresCampos[16139] !== '' ? (int)$valoresCampos[16139] : 0;
$valIntergenesicoMeses = isset($valoresCampos[16140]) && $valoresCampos[16140] !== '' ? (int)$valoresCampos[16140] : 0;

// III. Anticonceptivos
$valMetodos = $valoresCampos[14333] ?? [];
if (!is_array($valMetodos)) {
    $valMetodos = array_filter(array_map('trim', explode(',', (string)$valMetodos)));
}
$valMetodoAnticonceptivoOtro = $valoresCampos[16141] ?? '';

$esOtraPatologica = in_array('OTRA', array_map('strtoupper', $valPatologicos), true);
$esOtroMetodo = in_array('OTRO', array_map('strtoupper', $valMetodos), true);
?>

<div id="bloqueAntecedentesPatologicosObstetricosO95">
  <!-- A. ANTECEDENTES PATOLÓGICOS (Multiselección) -->
  <div style="margin-bottom:24px;">
    <div class="eyebrow" style="margin-bottom:12px; font-weight:700; color:var(--accent-deep); text-transform:uppercase; letter-spacing:0.5px;">
      Antecedentes Patológicos
    </div>
    
    <div class="checkbox-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:10px 16px; background:var(--surface-2); padding:16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line-2);">
      <?php
      $patologias = [
          'NINGUNO' => 'Ninguno',
          'HTA_CRONICA' => 'Hipertensión crónica',
          'DIABETES' => 'Diabetes mellitus',
          'CARDIOPATIAS' => 'Cardiopatías',
          'ENF_RENAL' => 'Enfermedad renal',
          'NEOPLASIAS' => 'Neoplasias',
          'ENF_HEPATICA' => 'Enfermedad hepática',
          'TUBERCULOSIS' => 'Tuberculosis',
          'ITS_VIH_SIDA' => 'ITS/VIH/SIDA',
          'ALCOHOLISMO' => 'Alcoholismo',
          'DROGADICCION' => 'Drogadicción',
          'VIOLENCIA_GENERO' => 'Violencia de género',
          'TABAQUISMO' => 'Tabaquismo',
          'DESNUTRICION_CRONICA' => 'Desnutrición crónica',
          'OTRA' => 'OTRA',
          'DESCONOCIDO' => 'Desconocido'
      ];
      foreach ($patologias as $cod => $lbl):
          $checked = in_array($cod, $valPatologicos, true) || in_array(strtoupper($lbl), array_map('strtoupper', $valPatologicos), true);
      ?>
        <label class="choice" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:var(--ink-1);">
          <input type="checkbox" name="campo_14324[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> class="o95PatologiaChk" data-codigo="<?= $cod ?>">
          <span><?= e($lbl) ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <!-- Especificar otra patología -->
    <div id="bloquePatologiaOtraO95" style="margin-top:12px; max-width:400px;" <?= !$esOtraPatologica ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl" style="font-size:0.8rem; font-weight:600;">Especificar otra patología</label>
      <div class="control">
        <input type="text" id="o95PatologiaOtraInput" name="campo_16138" value="<?= e($valPatologicosOtra) ?>" placeholder="Especificar patología…">
      </div>
    </div>
  </div>

  <!-- B. ANTECEDENTES GINECO OBSTÉTRICOS -->
  <div style="margin-bottom:24px; padding-top:16px; border-top:1px dashed var(--line-2);">
    <div class="eyebrow" style="margin-bottom:6px; font-weight:700; color:var(--accent-deep); text-transform:uppercase; letter-spacing:0.5px;">
      Antecedentes Gineco Obstétricos
    </div>
    <div style="font-size:0.8rem; color:var(--ink-3); margin-bottom:14px;">(Gestaciones anteriores sin incluir el embarazo actual)</div>

    <!-- Mensaje de Validación / Advertencia -->
    <div id="o95GinecoValidacionAlerta" class="info-callout" style="display:none; background:rgba(239,68,68,0.1); border:1px solid var(--danger, #ef4444); border-radius:var(--radius-sm, 8px); padding:10px 14px; margin-bottom:12px; color:var(--danger, #ef4444); font-size:0.85rem;"></div>

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:center; background:var(--surface-2); padding:16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line-2);">
      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:700; color:var(--accent-deep);">N.° Gestaciones previas:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95GestacionesInput" name="campo_14325" value="<?= $valGestaciones ?>" style="text-align:center; font-weight:700;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Partos:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95PartosInput" name="campo_14326" value="<?= $valPartos ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Cesáreas:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95CesareasInput" name="campo_14327" value="<?= $valCesareas ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Abortos:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95AbortosInput" name="campo_14328" value="<?= $valAbortos ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Nacidos vivos:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95NacVivosInput" name="campo_14329" value="<?= $valNacVivos ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Nacidos muertos:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95NacMuertosInput" name="campo_14330" value="<?= $valNacMuertos ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Hijos que viven:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95HijosVivenInput" name="campo_14331" value="<?= $valHijosViven ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div id="o95IntergenesicoBlock" class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:700; color:var(--accent-deep);">Período Intergenésico:</label>
        <div class="control" style="width:75px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95IntergenesicoAniosInput" name="campo_16139" value="<?= $valIntergenesicoAnios ?>" placeholder="Años" style="text-align:center;" class="solo-enteros">
        </div>
        <span style="font-size:0.85rem;">años</span>
        <div class="control" style="width:75px;">
          <input type="number" min="0" max="11" step="1" inputmode="numeric" pattern="[0-9]*" id="o95IntergenesicoMesesInput" name="campo_16140" value="<?= $valIntergenesicoMeses ?>" placeholder="Meses" style="text-align:center;" class="solo-enteros">
        </div>
        <span style="font-size:0.85rem;">meses</span>
      </div>
    </div>
  </div>

  <!-- D. USO DE MÉTODO ANTICONCEPTIVO (Multiselección) -->
  <div style="padding-top:16px; border-top:1px dashed var(--line-2);">
    <div class="eyebrow" style="margin-bottom:4px; font-weight:700; color:var(--accent-deep); text-transform:uppercase; letter-spacing:0.5px;">
      Uso de Método Anticonceptivo
    </div>
    <div style="font-size:0.8rem; color:var(--ink-3); margin-bottom:12px;">(Previo al embarazo actual)</div>

    <div class="checkbox-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:10px 16px; background:var(--surface-2); padding:16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line-2);">
      <?php
      $metodos = [
          'NO_USO' => 'No usó',
          'HORMONAL' => 'Hormonal',
          'DIU' => 'DIU',
          'BARRERA' => 'Barrera',
          'QUIRURGICO' => 'Quirúrgico',
          'ABSTINENCIA_PERIODICA' => 'Abstinencia periódica',
          'OTRO' => 'Otro',
          'DESCONOCIDO' => 'Desconocido'
      ];
      foreach ($metodos as $codM => $lblM):
          $checkedM = in_array($codM, $valMetodos, true) || in_array(strtoupper($lblM), array_map('strtoupper', $valMetodos), true);
      ?>
        <label class="choice" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:var(--ink-1);">
          <input type="checkbox" name="campo_14333[]" value="<?= $codM ?>" <?= $checkedM ? 'checked' : '' ?> class="o95MetodoChk" data-codigo="<?= $codM ?>">
          <span><?= e($lblM) ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <!-- Especificar otro método anticonceptivo -->
    <div id="bloqueMetodoAnticonceptivoOtroO95" style="margin-top:12px; max-width:400px;" <?= !$esOtroMetodo ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl" style="font-size:0.8rem; font-weight:600;">Especificar otro método</label>
      <div class="control">
        <input type="text" id="o95MetodoOtroInput" name="campo_16141" value="<?= e($valMetodoAnticonceptivoOtro) ?>" placeholder="Especificar método…">
      </div>
    </div>
  </div>
</div>
