<?php
/**
 * Plantilla especializada para la Sección: Antecedentes patológicos y obstétricos (Anexo 2) de Muerte Materna (O95).
 */

$campoPatologicos = $campo('o95_antecedentes_patologicos');
$campoPatologicosOtra = $campo('o95_antecedentes_patologicos_otra');
$campoGestaciones = $campo('o95_n_de_gestaciones_previas');
$campoPartos = $campo('o95_n_de_partos');
$campoCesareas = $campo('o95_n_de_cesareas');
$campoAbortos = $campo('o95_n_de_abortos');
$campoNacVivos = $campo('o95_n_de_nacidos_vivos');
$campoNacMuertos = $campo('o95_n_de_nacidos_muertos');
$campoHijosViven = $campo('o95_n_de_hijos_que_viven');
$campoIntergenesicoAnios = $campo('o95_periodo_intergenesico_anios');
$campoIntergenesicoMeses = $campo('o95_periodo_intergenesico_meses');
$campoMetodos = $campo('o95_uso_de_metodo_anticonceptivo_previo');
$campoMetodoAnticonceptivoOtro = $campo('o95_metodo_anticonceptivo_otro');

// I. Patológicos
$valPatologicos = $campoPatologicos['val'];
if (!is_array($valPatologicos)) {
    $valPatologicos = array_filter(array_map('trim', explode(',', (string)$valPatologicos)));
}
$valPatologicosOtra = $campoPatologicosOtra['val'];

// II. Gineco Obstétricos - Valores iniciales por defecto 0 si están vacíos
$valGestaciones = $campoGestaciones['val'] !== '' ? (int) $campoGestaciones['val'] : 0;
$valPartos      = $campoPartos['val'] !== '' ? (int) $campoPartos['val'] : 0;
$valCesareas    = $campoCesareas['val'] !== '' ? (int) $campoCesareas['val'] : 0;
$valAbortos     = $campoAbortos['val'] !== '' ? (int) $campoAbortos['val'] : 0;
$valNacVivos    = $campoNacVivos['val'] !== '' ? (int) $campoNacVivos['val'] : 0;
$valNacMuertos  = $campoNacMuertos['val'] !== '' ? (int) $campoNacMuertos['val'] : 0;
$valHijosViven  = $campoHijosViven['val'] !== '' ? (int) $campoHijosViven['val'] : 0;

$valIntergenesicoAnios = $campoIntergenesicoAnios['val'] !== '' ? (int) $campoIntergenesicoAnios['val'] : 0;
$valIntergenesicoMeses = $campoIntergenesicoMeses['val'] !== '' ? (int) $campoIntergenesicoMeses['val'] : 0;

// III. Anticonceptivos
$valMetodos = $campoMetodos['val'];
if (!is_array($valMetodos)) {
    $valMetodos = array_filter(array_map('trim', explode(',', (string)$valMetodos)));
}
$valMetodoAnticonceptivoOtro = $campoMetodoAnticonceptivoOtro['val'];

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
          <input type="checkbox" name="<?= $campoPatologicos['name'] ?>[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> class="o95PatologiaChk" data-codigo="<?= $cod ?>">
          <span><?= e($lbl) ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <!-- Especificar otra patología -->
    <div id="bloquePatologiaOtraO95" style="margin-top:12px; max-width:400px;" <?= !$esOtraPatologica ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl" style="font-size:0.8rem; font-weight:600;">Especificar otra patología</label>
      <div class="control">
        <input type="text" id="o95PatologiaOtraInput" name="<?= $campoPatologicosOtra['name'] ?>" value="<?= e($valPatologicosOtra) ?>" placeholder="Especificar patología…">
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
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95GestacionesInput" name="<?= $campoGestaciones['name'] ?>" value="<?= $valGestaciones ?>" style="text-align:center; font-weight:700;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Partos:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95PartosInput" name="<?= $campoPartos['name'] ?>" value="<?= $valPartos ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Cesáreas:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95CesareasInput" name="<?= $campoCesareas['name'] ?>" value="<?= $valCesareas ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Abortos:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95AbortosInput" name="<?= $campoAbortos['name'] ?>" value="<?= $valAbortos ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Nacidos vivos:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95NacVivosInput" name="<?= $campoNacVivos['name'] ?>" value="<?= $valNacVivos ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Nacidos muertos:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95NacMuertosInput" name="<?= $campoNacMuertos['name'] ?>" value="<?= $valNacMuertos ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--ink-1);">N.° Hijos que viven:</label>
        <div class="control" style="width:80px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95HijosVivenInput" name="<?= $campoHijosViven['name'] ?>" value="<?= $valHijosViven ?>" style="text-align:center;" class="solo-enteros">
        </div>
      </div>

      <div id="o95IntergenesicoBlock" class="field-inline" style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.85rem; font-weight:700; color:var(--accent-deep);">Período Intergenésico:</label>
        <div class="control" style="width:75px;">
          <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95IntergenesicoAniosInput" name="<?= $campoIntergenesicoAnios['name'] ?>" value="<?= $valIntergenesicoAnios ?>" placeholder="Años" style="text-align:center;" class="solo-enteros">
        </div>
        <span style="font-size:0.85rem;">años</span>
        <div class="control" style="width:75px;">
          <input type="number" min="0" max="11" step="1" inputmode="numeric" pattern="[0-9]*" id="o95IntergenesicoMesesInput" name="<?= $campoIntergenesicoMeses['name'] ?>" value="<?= $valIntergenesicoMeses ?>" placeholder="Meses" style="text-align:center;" class="solo-enteros">
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
          <input type="checkbox" name="<?= $campoMetodos['name'] ?>[]" value="<?= $codM ?>" <?= $checkedM ? 'checked' : '' ?> class="o95MetodoChk" data-codigo="<?= $codM ?>">
          <span><?= e($lblM) ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <!-- Especificar otro método anticonceptivo -->
    <div id="bloqueMetodoAnticonceptivoOtroO95" style="margin-top:12px; max-width:400px;" <?= !$esOtroMetodo ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl" style="font-size:0.8rem; font-weight:600;">Especificar otro método</label>
      <div class="control">
        <input type="text" id="o95MetodoOtroInput" name="<?= $campoMetodoAnticonceptivoOtro['name'] ?>" value="<?= e($valMetodoAnticonceptivoOtro) ?>" placeholder="Especificar método…">
      </div>
    </div>
  </div>
</div>
