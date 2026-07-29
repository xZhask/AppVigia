<?php
/**
 * Plantilla especializada para la Sección 10: Datos comunitarios (Anexo 2) de Muerte Materna (O95).
 */

$valSintomOpts = $valoresCampos[14369] ?? [];
if (!is_array($valSintomOpts)) {
    $valSintomOpts = array_filter(array_map('trim', explode(',', (string)$valSintomOpts)));
}
$valSintomOtro = $valoresCampos[16176] ?? '';

$valManPartOpts = $valoresCampos[14370] ?? [];
if (!is_array($valManPartOpts)) {
    $valManPartOpts = array_filter(array_map('trim', explode(',', (string)$valManPartOpts)));
}
$valManPartOtro = $valoresCampos[16177] ?? '';

$valManPlacOpts = $valoresCampos[14371] ?? [];
if (!is_array($valManPlacOpts)) {
    $valManPlacOpts = array_filter(array_map('trim', explode(',', (string)$valManPlacOpts)));
}
$valManPlacOtro = $valoresCampos[16178] ?? '';

$valTiemDomH = isset($valoresCampos[16179]) && $valoresCampos[16179] !== '' ? (int)$valoresCampos[16179] : 0;
$valTiemDomM = isset($valoresCampos[16180]) && $valoresCampos[16180] !== '' ? (int)$valoresCampos[16180] : 0;

$valTipoEessCercano = $valoresCampos[14373] ?? '';

$esSintomOtro = in_array('OTRO', array_map('strtoupper', $valSintomOpts), true);
$esManPartOtro = in_array('OTRO', array_map('strtoupper', $valManPartOpts), true);
$esManPlacOtro = in_array('OTRO', array_map('strtoupper', $valManPlacOpts), true);

$esManPartNoUso = in_array('NO_SE_USO', array_map('strtoupper', $valManPartOpts), true);
$esManPlacNoUso = in_array('NO_SE_USO', array_map('strtoupper', $valManPlacOpts), true);

// Sugerencia de lugar extrainstitucional
$valLugarDef = $valoresCampos[14300] ?? '';
$valLugarDefUp = strtoupper((string)$valLugarDef);
$esExtrainstitucional = (strpos($valLugarDefUp, 'DOMICILIO') !== false || strpos($valLugarDefUp, 'TRAYECTO') !== false || strpos($valLugarDefUp, 'OTRO') !== false);
?>

<div id="bloqueDatosComunitariosO95">
  
  <!-- Nota de encabezado oficial -->
  <div style="font-size:0.8125rem; color:var(--muted); font-style:italic; margin-bottom:14px; background:var(--surface-2); padding:8px 12px; border-radius:var(--radius-sm, 8px); border-left:3px solid var(--accent);">
    <strong>VI. DATOS COMUNITARIOS:</strong> Llenar solo en caso de Muerte Materna Extrainstitucional y Casos Especiales*.
  </div>

  <!-- Toggle / Indicador de Caso Extrainstitucional -->
  <div style="margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:12px; background:var(--surface-2); padding:10px 14px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line);">
    <span style="font-size:0.875rem; font-weight:600; color:var(--ink);">¿Se trata de un caso extrainstitucional o caso especial?</span>
    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600; color:var(--accent-deep);">
      <input type="checkbox" id="o95HabilitarDatosComunitariosChk" value="1" <?= $esExtrainstitucional ? 'checked' : '' ?> style="accent-color:var(--accent); width:16px; height:16px;">
      <span>Habilitar sección</span>
    </label>
  </div>

  <div id="bloqueContenidoComunitarioO95" <?= !$esExtrainstitucional ? 'hidden style="display:none;"' : '' ?>>
    
    <!-- 1. Sintomatología o molestias -->
    <div style="margin-bottom:20px;">
      <label class="fl" style="font-weight:700; color:var(--accent-deep);">Sintomatología o molestias (Multiselección)</label>
      <div class="checkbox-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:10px 16px; margin-top:8px;">
        <?php
        $optsSintom = [
            'SANGRADO' => 'Sangrado',
            'PERDIDA_DE_LIQUIDO' => 'Pérdida de líquido',
            'DOLOR' => 'Dolor',
            'SENSACION_DE_ALZA_TERMICA' => 'Sensación de alza térmica',
            'NAUSEAS_Y_VOMITOS' => 'Náuseas y vómitos',
            'CONVULSIONES' => 'Convulsiones',
            'DEBILIDAD' => 'Debilidad',
            'ANSIEDAD' => 'Ansiedad',
            'PERDIDA_ALTERACION_DEL_ESTADO_DE_CONCIENCIA' => 'Pérdida/alteración del estado de conciencia',
            'CEFALEA' => 'Cefalea',
            'OTRO' => 'Otro'
        ];
        foreach ($optsSintom as $cod => $lbl):
            $checked = in_array($cod, $valSintomOpts, true) || in_array(strtoupper($lbl), array_map('strtoupper', $valSintomOpts), true);
        ?>
          <label class="choice" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:var(--ink);">
            <input type="checkbox" name="campo_14369[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> class="o95SintomChk" data-codigo="<?= $cod ?>">
            <span><?= e($lbl) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div id="bloqueSintomatologiaOtroO95" style="margin-top:10px; max-width:450px;" <?= !$esSintomOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra sintomatología</label>
        <div class="control">
          <input type="text" name="campo_16176" value="<?= e($valSintomOtro) ?>" placeholder="Especificar sintomatología…">
        </div>
      </div>
    </div>

    <!-- 2. Maniobras usadas durante el parto -->
    <div style="margin-bottom:20px; padding-top:16px; border-top:1px dashed var(--line-2);">
      <label class="fl" style="font-weight:700; color:var(--accent-deep);">Maniobras usadas durante el parto</label>
      <div class="checkbox-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:10px 16px; margin-top:8px;">
        <?php
        $optsManPart = [
            'NO_SE_USO' => 'No se usó',
            'MANTEO' => 'Manteo',
            'ACOMODO' => 'Acomodo',
            'MASAJES' => 'Masajes',
            'OTRO' => 'Otro'
        ];
        foreach ($optsManPart as $cod => $lbl):
            $checked = in_array($cod, $valManPartOpts, true) || in_array(strtoupper($lbl), array_map('strtoupper', $valManPartOpts), true);
            $disabled = ($esManPartNoUso && $cod !== 'NO_SE_USO');
        ?>
          <label class="choice" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:var(--ink);">
            <input type="checkbox" name="campo_14370[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?> class="o95ManPartChk" data-codigo="<?= $cod ?>">
            <span style="<?= $disabled ? 'opacity:0.5;' : '' ?>"><?= e($lbl) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div id="bloqueManiobrasPartoOtroO95" style="margin-top:10px; max-width:450px;" <?= !$esManPartOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra maniobra durante el parto</label>
        <div class="control">
          <input type="text" name="campo_16177" value="<?= e($valManPartOtro) ?>" placeholder="Especificar maniobra…">
        </div>
      </div>
    </div>

    <!-- 3. Maniobras usadas para retirar placenta -->
    <div style="margin-bottom:20px; padding-top:16px; border-top:1px dashed var(--line-2);">
      <label class="fl" style="font-weight:700; color:var(--accent-deep);">Maniobras usadas para retirar la placenta</label>
      <div class="checkbox-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:10px 16px; margin-top:8px;">
        <?php
        $optsManPlac = [
            'NO_SE_USO' => 'No se usó',
            'MANTEO' => 'Manteo',
            'ACOMODO' => 'Acomodo',
            'MASAJES' => 'Masajes',
            'OTRO' => 'Otro'
        ];
        foreach ($optsManPlac as $cod => $lbl):
            $checked = in_array($cod, $valManPlacOpts, true) || in_array(strtoupper($lbl), array_map('strtoupper', $valManPlacOpts), true);
            $disabled = ($esManPlacNoUso && $cod !== 'NO_SE_USO');
        ?>
          <label class="choice" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:var(--ink);">
            <input type="checkbox" name="campo_14371[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?> class="o95ManPlacChk" data-codigo="<?= $cod ?>">
            <span style="<?= $disabled ? 'opacity:0.5;' : '' ?>"><?= e($lbl) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div id="bloqueManiobrasPlacentaOtroO95" style="margin-top:10px; max-width:450px;" <?= !$esManPlacOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra maniobra para retirar placenta</label>
        <div class="control">
          <input type="text" name="campo_16178" value="<?= e($valManPlacOtro) ?>" placeholder="Especificar maniobra…">
        </div>
      </div>
    </div>

    <!-- 4. Tiempo estimado del domicilio al EE.SS. más cercano (Vía usual) y 5. Tipo de establecimiento -->
    <div class="fields pairs" style="padding-top:16px; border-top:1px dashed var(--line-2);">
      <!-- Tiempo estimado (Horas y Minutos) -->
      <div class="field">
        <label class="fl">Tiempo estimado del domicilio al EE.SS. más cercano (Vía usual)</label>
        <div class="fields pairs" style="margin-top:6px; max-width:380px;">
          <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
            <div class="control" style="width:90px;">
              <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95TiempoDomicilioEessHorasInput" name="campo_16179" value="<?= $valTiemDomH ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
            </div>
            <span style="font-size:0.875rem;">Horas</span>
          </div>
          <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
            <div class="control" style="width:90px;">
              <input type="number" min="0" max="59" step="1" inputmode="numeric" pattern="[0-9]*" id="o95TiempoDomicilioEessMinutosInput" name="campo_16180" value="<?= $valTiemDomM ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
            </div>
            <span style="font-size:0.875rem;">Minutos</span>
          </div>
        </div>
      </div>

      <!-- 5. Tipo de establecimiento más cercano -->
      <div class="field">
        <label class="fl">Tipo de establecimiento más cercano</label>
        <div class="control">
          <select id="o95TipoEessCercanoSel" name="campo_14373">
            <option value="">Seleccionar tipo…</option>
            <option value="PUESTO_DE_SALUD" <?= seleccionado($valTipoEessCercano, 'PUESTO_DE_SALUD') ?>>Puesto de Salud</option>
            <option value="CENTRO_DE_SALUD" <?= seleccionado($valTipoEessCercano, 'CENTRO_DE_SALUD') ?>>Centro de Salud</option>
            <option value="HOSPITAL" <?= seleccionado($valTipoEessCercano, 'HOSPITAL') ?>>Hospital</option>
          </select>
        </div>
      </div>
    </div>

  </div>

</div>
