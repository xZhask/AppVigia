<?php
/**
 * Plantilla especializada para la Sección 10: Datos comunitarios (Anexo 2) de Muerte Materna (O95).
 */

$campoSintomOpts = $campo('o95_sintomatologia_o_molestias');
$campoSintomOtro = $campo('o95_sintomatologia_otro');
$campoManPartOpts = $campo('o95_maniobras_usadas_durante_el_parto');
$campoManPartOtro = $campo('o95_maniobras_parto_otro');
$campoManPlacOpts = $campo('o95_maniobras_usadas_para_retirar_la_placenta');
$campoManPlacOtro = $campo('o95_maniobras_placenta_otro');
$campoTiemDomH = $campo('o95_tiempo_domicilio_eess_horas');
$campoTiemDomM = $campo('o95_tiempo_domicilio_eess_minutos');
$campoTipoEessCercano = $campo('o95_tipo_de_establecimiento_mas_cercano');

$valSintomOpts = $campoSintomOpts['val'];
if (!is_array($valSintomOpts)) {
    $valSintomOpts = array_filter(array_map('trim', explode(',', (string)$valSintomOpts)));
}
$valSintomOtro = $campoSintomOtro['val'];

$valManPartOpts = $campoManPartOpts['val'];
if (!is_array($valManPartOpts)) {
    $valManPartOpts = array_filter(array_map('trim', explode(',', (string)$valManPartOpts)));
}
$valManPartOtro = $campoManPartOtro['val'];

$valManPlacOpts = $campoManPlacOpts['val'];
if (!is_array($valManPlacOpts)) {
    $valManPlacOpts = array_filter(array_map('trim', explode(',', (string)$valManPlacOpts)));
}
$valManPlacOtro = $campoManPlacOtro['val'];

$valTiemDomH = $campoTiemDomH['val'] !== '' ? (int) $campoTiemDomH['val'] : 0;
$valTiemDomM = $campoTiemDomM['val'] !== '' ? (int) $campoTiemDomM['val'] : 0;

$valTipoEessCercano = $campoTipoEessCercano['val'];

$esSintomOtro = in_array('OTRO', array_map('strtoupper', $valSintomOpts), true);
$esManPartOtro = in_array('OTRO', array_map('strtoupper', $valManPartOpts), true);
$esManPlacOtro = in_array('OTRO', array_map('strtoupper', $valManPlacOpts), true);

$esManPartNoUso = in_array('NO_SE_USO', array_map('strtoupper', $valManPartOpts), true);
$esManPlacNoUso = in_array('NO_SE_USO', array_map('strtoupper', $valManPlacOpts), true);

// Sugerencia de lugar extrainstitucional. Antes leía $valoresCampos[14300],
// que es v99_aseguradora (de la ficha V99, no de O95: ver MAPA_IDS_CAMPOS.md
// "14300 | O95 | ID de otra ficha") -- para un caso real de O95 esa clave
// nunca tiene valor, así que la sugerencia jamás se activaba por esta vía.
// Se corrige para leer el campo real de esta misma ficha.
$valLugarDef = $campo('o95_lugar_del_fallecimiento')['val'];
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
            <input type="checkbox" name="<?= $campoSintomOpts['name'] ?>[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> class="o95SintomChk" data-codigo="<?= $cod ?>">
            <span><?= e($lbl) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div id="bloqueSintomatologiaOtroO95" style="margin-top:10px; max-width:450px;" <?= !$esSintomOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra sintomatología</label>
        <div class="control">
          <input type="text" name="<?= $campoSintomOtro['name'] ?>" value="<?= e($valSintomOtro) ?>" placeholder="Especificar sintomatología…">
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
            <input type="checkbox" name="<?= $campoManPartOpts['name'] ?>[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?> class="o95ManPartChk" data-codigo="<?= $cod ?>">
            <span style="<?= $disabled ? 'opacity:0.5;' : '' ?>"><?= e($lbl) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div id="bloqueManiobrasPartoOtroO95" style="margin-top:10px; max-width:450px;" <?= !$esManPartOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra maniobra durante el parto</label>
        <div class="control">
          <input type="text" name="<?= $campoManPartOtro['name'] ?>" value="<?= e($valManPartOtro) ?>" placeholder="Especificar maniobra…">
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
            <input type="checkbox" name="<?= $campoManPlacOpts['name'] ?>[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?> class="o95ManPlacChk" data-codigo="<?= $cod ?>">
            <span style="<?= $disabled ? 'opacity:0.5;' : '' ?>"><?= e($lbl) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div id="bloqueManiobrasPlacentaOtroO95" style="margin-top:10px; max-width:450px;" <?= !$esManPlacOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra maniobra para retirar placenta</label>
        <div class="control">
          <input type="text" name="<?= $campoManPlacOtro['name'] ?>" value="<?= e($valManPlacOtro) ?>" placeholder="Especificar maniobra…">
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
              <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95TiempoDomicilioEessHorasInput" name="<?= $campoTiemDomH['name'] ?>" value="<?= $valTiemDomH ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
            </div>
            <span style="font-size:0.875rem;">Horas</span>
          </div>
          <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
            <div class="control" style="width:90px;">
              <input type="number" min="0" max="59" step="1" inputmode="numeric" pattern="[0-9]*" id="o95TiempoDomicilioEessMinutosInput" name="<?= $campoTiemDomM['name'] ?>" value="<?= $valTiemDomM ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
            </div>
            <span style="font-size:0.875rem;">Minutos</span>
          </div>
        </div>
      </div>

      <!-- 5. Tipo de establecimiento más cercano -->
      <div class="field">
        <label class="fl">Tipo de establecimiento más cercano</label>
        <div class="control">
          <select id="o95TipoEessCercanoSel" name="<?= $campoTipoEessCercano['name'] ?>">
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
