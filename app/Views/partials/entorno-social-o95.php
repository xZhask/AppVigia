<?php
/**
 * Plantilla especializada para la Sección 9: Entorno social y comunitario (Anexo 2) de Muerte Materna (O95).
 */

$valIdentSignos = $valoresCampos[14357] ?? '';
$valPersIdent   = $valoresCampos[14358] ?? '';
$valPersIdentOtro = $valoresCampos[16165] ?? '';

$valBuscAyuda   = $valoresCampos[14359] ?? '';
$valDecisAyuda  = $valoresCampos[14360] ?? '';
$valDecisAyudaOtro = $valoresCampos[16166] ?? '';
$valTiemAyudaHoras = isset($valoresCampos[16167]) && $valoresCampos[16167] !== '' ? (int)$valoresCampos[16167] : 0;
$valTiemAyudaMin   = isset($valoresCampos[16168]) && $valoresCampos[16168] !== '' ? (int)$valoresCampos[16168] : 0;

$valDifAcceso     = $valoresCampos[14362] ?? '';
$valDifAccesoOpts = $valoresCampos[14363] ?? [];
if (!is_array($valDifAccesoOpts)) {
    $valDifAccesoOpts = array_filter(array_map('trim', explode(',', (string)$valDifAccesoOpts)));
}
$valDifAccesoOtro  = $valoresCampos[16169] ?? '';
$valTiemLlegEessH  = isset($valoresCampos[16170]) && $valoresCampos[16170] !== '' ? (int)$valoresCampos[16170] : 0;
$valTiemLlegEessM  = isset($valoresCampos[16171]) && $valoresCampos[16171] !== '' ? (int)$valoresCampos[16171] : 0;

$valDifAtenc     = $valoresCampos[14365] ?? '';
$valDifAtencOpts = $valoresCampos[14366] ?? [];
if (!is_array($valDifAtencOpts)) {
    $valDifAtencOpts = array_filter(array_map('trim', explode(',', (string)$valDifAtencOpts)));
}
$valDifAtencOtro  = $valoresCampos[16172] ?? '';
$valTiemAtendH    = isset($valoresCampos[16173]) && $valoresCampos[16173] !== '' ? (int)$valoresCampos[16173] : 0;
$valTiemAtendM    = isset($valoresCampos[16174]) && $valoresCampos[16174] !== '' ? (int)$valoresCampos[16174] : 0;

$valPersonaInfo     = $valoresCampos[14368] ?? '';
$valPersonaInfoOtro = $valoresCampos[16175] ?? '';

$esIdentSignosSi = ($valIdentSignos === '1' || strtoupper((string)$valIdentSignos) === 'SI');
$esBuscAyudaSi   = ($valBuscAyuda === '1' || strtoupper((string)$valBuscAyuda) === 'SI');
$esDifAccesoSi   = ($valDifAcceso === '1' || strtoupper((string)$valDifAcceso) === 'SI');
$esDifAtencSi    = ($valDifAtenc === '1' || strtoupper((string)$valDifAtenc) === 'SI');

$esPersIdentOtro  = (strtoupper((string)$valPersIdent) === 'OTRO');
$esDecisAyudaOtro = (strtoupper((string)$valDecisAyuda) === 'OTRO');
$esDifAccesoOtro  = in_array('OTRO', array_map('strtoupper', $valDifAccesoOpts), true);
$esDifAtencOtro   = in_array('OTRO', array_map('strtoupper', $valDifAtencOpts), true);
$esPersonaInfoOtro= (strtoupper((string)$valPersonaInfo) === 'OTRO');
?>

<div id="bloqueEntornoSocialO95">

  <!-- 1. ¿Identificaron signos de peligro? -->
  <div style="margin-bottom:20px;">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Identificaron signos de peligro? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14357" value="1" id="o95IdentSignosSi" <?= $esIdentSignosSi ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14357" value="0" id="o95IdentSignosNo" <?= (!$esIdentSignosSi && $valIdentSignos !== '') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>
  </div>

  <div id="bloqueIdentificaronSignosO95" style="margin-bottom:20px; background:var(--surface-2); padding:16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line);" <?= !$esIdentSignosSi ? 'hidden style="display:none;"' : '' ?>>
    <div class="fields pairs">
      <div class="field">
        <label class="fl">Persona que identificó los signos de peligro</label>
        <div class="control">
          <select id="o95PersonaIdentificoSel" name="campo_14358">
            <option value="">Seleccionar persona…</option>
            <option value="ELLA_MISMA" <?= seleccionado($valPersIdent, 'ELLA_MISMA') ?>>Ella misma</option>
            <option value="PAREJA" <?= seleccionado($valPersIdent, 'PAREJA') ?>>Pareja</option>
            <option value="FAMILIAR" <?= seleccionado($valPersIdent, 'FAMILIAR') ?>>Familiar</option>
            <option value="OTRO" <?= seleccionado($valPersIdent, 'OTRO') ?>>Otro</option>
          </select>
        </div>
      </div>
      <div class="field" id="bloquePersonaIdentificoOtroO95" <?= !$esPersIdentOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra persona</label>
        <div class="control">
          <input type="text" name="campo_16165" value="<?= e($valPersIdentOtro) ?>" placeholder="Especificar persona…">
        </div>
      </div>
    </div>
  </div>

  <!-- 2. ¿Buscaron ayuda? -->
  <div style="margin-bottom:20px; padding-top:16px; border-top:1px dashed var(--line-2);">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Buscaron ayuda? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14359" value="1" id="o95BuscaronAyudaSi" <?= $esBuscAyudaSi ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14359" value="0" id="o95BuscaronAyudaNo" <?= (!$esBuscAyudaSi && $valBuscAyuda !== '') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>
  </div>

  <div id="bloqueBuscaronAyudaO95" style="margin-bottom:20px; background:var(--surface-2); padding:16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line);" <?= !$esBuscAyudaSi ? 'hidden style="display:none;"' : '' ?>>
    <div class="fields pairs" style="margin-bottom:14px;">
      <div class="field">
        <label class="fl">Quién tomó la decisión de buscar ayuda</label>
        <div class="control">
          <select id="o95DecisionBuscarAyudaSel" name="campo_14360">
            <option value="">Seleccionar persona…</option>
            <option value="ELLA_MISMA" <?= seleccionado($valDecisAyuda, 'ELLA_MISMA') ?>>Ella misma</option>
            <option value="PAREJA" <?= seleccionado($valDecisAyuda, 'PAREJA') ?>>Pareja</option>
            <option value="FAMILIAR" <?= seleccionado($valDecisAyuda, 'FAMILIAR') ?>>Familiar</option>
            <option value="OTRO" <?= seleccionado($valDecisAyuda, 'OTRO') ?>>Otro</option>
          </select>
        </div>
      </div>
      <div class="field" id="bloqueDecisionBuscarAyudaOtroO95" <?= !$esDecisAyudaOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra persona</label>
        <div class="control">
          <input type="text" name="campo_16166" value="<?= e($valDecisAyudaOtro) ?>" placeholder="Especificar persona…">
        </div>
      </div>
    </div>

    <!-- Tiempo que demoró en buscar ayuda (Horas y Minutos) -->
    <div class="field">
      <label class="fl">Tiempo que demoró en buscar ayuda desde el inicio de sus molestias</label>
      <div class="fields pairs" style="margin-top:6px; max-width:380px;">
        <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
          <div class="control" style="width:90px;">
            <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95TiempoBuscarAyudaHorasInput" name="campo_16167" value="<?= $valTiemAyudaHoras ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
          </div>
          <span style="font-size:0.875rem;">Horas</span>
        </div>
        <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
          <div class="control" style="width:90px;">
            <input type="number" min="0" max="59" step="1" inputmode="numeric" pattern="[0-9]*" id="o95TiempoBuscarAyudaMinutosInput" name="campo_16168" value="<?= $valTiemAyudaMin ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
          </div>
          <span style="font-size:0.875rem;">Minutos</span>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. ¿Tuvo dificultad con el acceso a servicios de salud? -->
  <div style="margin-bottom:20px; padding-top:16px; border-top:1px dashed var(--line-2);">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Tuvo dificultad con el acceso a servicios de salud? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14362" value="1" id="o95DificultadAccesoSi" <?= $esDifAccesoSi ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14362" value="0" id="o95DificultadAccesoNo" <?= (!$esDifAccesoSi && $valDifAcceso !== '') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>
  </div>

  <div id="bloqueDificultadAccesoO95" style="margin-bottom:20px; background:var(--surface-2); padding:16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line);" <?= !$esDifAccesoSi ? 'hidden style="display:none;"' : '' ?>>
    <div style="margin-bottom:14px;">
      <label class="fl" style="font-weight:600;">Dificultades de acceso (Multiselección)</label>
      <div class="checkbox-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:10px 16px; margin-top:8px;">
        <?php
        $optsAcceso = [
            'INACCESIBILIDAD_GEOGRAFICA' => 'Inaccesibilidad geográfica',
            'DISTANCIA' => 'Distancia',
            'TRANSPORTE' => 'Transporte',
            'CREENCIAS_COSTUMBRES' => 'Creencias / Costumbres',
            'OTRO' => 'Otro'
        ];
        foreach ($optsAcceso as $cod => $lbl):
            $checked = in_array($cod, $valDifAccesoOpts, true) || in_array(strtoupper($lbl), array_map('strtoupper', $valDifAccesoOpts), true);
        ?>
          <label class="choice" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:var(--ink);">
            <input type="checkbox" name="campo_14363[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> class="o95DifAccesoChk" data-codigo="<?= $cod ?>">
            <span><?= e($lbl) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="bloqueDificultadAccesoOtroO95" style="margin-bottom:14px; max-width:400px;" <?= !$esDifAccesoOtro ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl">Especificar otra dificultad de acceso</label>
      <div class="control">
        <input type="text" name="campo_16169" value="<?= e($valDifAccesoOtro) ?>" placeholder="Especificar dificultad…">
      </div>
    </div>

    <!-- Tiempo hasta llegar al EE.SS. (Horas y Minutos) -->
    <div class="field">
      <label class="fl">Tiempo que demoró desde el inicio de sus molestias hasta llegar al EE.SS.</label>
      <div class="fields pairs" style="margin-top:6px; max-width:380px;">
        <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
          <div class="control" style="width:90px;">
            <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95TiempoLlegarEessHorasInput" name="campo_16170" value="<?= $valTiemLlegEessH ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
          </div>
          <span style="font-size:0.875rem;">Horas</span>
        </div>
        <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
          <div class="control" style="width:90px;">
            <input type="number" min="0" max="59" step="1" inputmode="numeric" pattern="[0-9]*" id="o95TiempoLlegarEessMinutosInput" name="campo_16171" value="<?= $valTiemLlegEessM ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
          </div>
          <span style="font-size:0.875rem;">Minutos</span>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. ¿Tuvo dificultades para ser atendida en el EE.SS.? -->
  <div style="margin-bottom:20px; padding-top:16px; border-top:1px dashed var(--line-2);">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Tuvo dificultades para ser atendida en el EE.SS.? <span class="req">*</span></label>
    <div class="control-radio-group">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14365" value="1" id="o95DificultadAtencionSi" <?= $esDifAtencSi ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>SÍ</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="campo_14365" value="0" id="o95DificultadAtencionNo" <?= (!$esDifAtencSi && $valDifAtenc !== '') ? 'checked' : '' ?> style="accent-color:var(--accent);">
        <span>NO</span>
      </label>
    </div>
  </div>

  <div id="bloqueDificultadAtencionO95" style="margin-bottom:20px; background:var(--surface-2); padding:16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line);" <?= !$esDifAtencSi ? 'hidden style="display:none;"' : '' ?>>
    <div style="margin-bottom:14px;">
      <label class="fl" style="font-weight:600;">Dificultades de atención (Multiselección)</label>
      <div class="checkbox-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:10px 16px; margin-top:8px;">
        <?php
        $optsAtencion = [
            'ECONOMICAS' => 'Económicas',
            'IDIOMA' => 'Idioma',
            'ADMINISTRATIVAS_TRAMITES' => 'Administrativas / Trámites',
            'DEMORA_EN_ATENCION' => 'Demora en atención',
            'MALA_ATENCION' => 'Mala atención',
            'OTRO' => 'Otro'
        ];
        foreach ($optsAtencion as $cod => $lbl):
            $checked = in_array($cod, $valDifAtencOpts, true) || in_array(strtoupper($lbl), array_map('strtoupper', $valDifAtencOpts), true);
        ?>
          <label class="choice" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:var(--ink);">
            <input type="checkbox" name="campo_14366[]" value="<?= $cod ?>" <?= $checked ? 'checked' : '' ?> class="o95DifAtencChk" data-codigo="<?= $cod ?>">
            <span><?= e($lbl) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="bloqueDificultadAtencionOtroO95" style="margin-bottom:14px; max-width:400px;" <?= !$esDifAtencOtro ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl">Especificar otra dificultad de atención</label>
      <div class="control">
        <input type="text" name="campo_16172" value="<?= e($valDifAtencOtro) ?>" placeholder="Especificar dificultad…">
      </div>
    </div>

    <!-- Tiempo hasta ser atendida (Horas y Minutos) -->
    <div class="field">
      <label class="fl">Tiempo que demoró desde que llegó al EE.SS. hasta que fue atendida</label>
      <div class="fields pairs" style="margin-top:6px; max-width:380px;">
        <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
          <div class="control" style="width:90px;">
            <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95TiempoHastaAtendidaHorasInput" name="campo_16173" value="<?= $valTiemAtendH ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
          </div>
          <span style="font-size:0.875rem;">Horas</span>
        </div>
        <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
          <div class="control" style="width:90px;">
            <input type="number" min="0" max="59" step="1" inputmode="numeric" pattern="[0-9]*" id="o95TiempoHastaAtendidaMinutosInput" name="campo_16174" value="<?= $valTiemAtendM ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
          </div>
          <span style="font-size:0.875rem;">Minutos</span>
        </div>
      </div>
    </div>
  </div>

  <!-- 5. Persona que brindó la información -->
  <div style="padding-top:16px; border-top:1px dashed var(--line-2);">
    <div class="fields pairs">
      <div class="field">
        <label class="fl">Persona que brindó información y relación con la fallecida <span class="req">*</span></label>
        <div class="control">
          <select id="o95PersonaBrindoInfoSel" name="campo_14368">
            <option value="">Seleccionar persona…</option>
            <option value="MADRE" <?= seleccionado($valPersonaInfo, 'MADRE') ?>>Madre</option>
            <option value="PADRE" <?= seleccionado($valPersonaInfo, 'PADRE') ?>>Padre</option>
            <option value="PAREJA" <?= seleccionado($valPersonaInfo, 'PAREJA') ?>>Pareja</option>
            <option value="FAMILIAR" <?= seleccionado($valPersonaInfo, 'FAMILIAR') ?>>Familiar</option>
            <option value="PARTERA" <?= seleccionado($valPersonaInfo, 'PARTERA') ?>>Partera</option>
            <option value="VECINO" <?= seleccionado($valPersonaInfo, 'VECINO') ?>>Vecino</option>
            <option value="OTRO" <?= seleccionado($valPersonaInfo, 'OTRO') ?>>Otro</option>
          </select>
        </div>
      </div>

      <div class="field" id="bloquePersonaBrindoInfoOtroO95" <?= !$esPersonaInfoOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otra persona</label>
        <div class="control">
          <input type="text" name="campo_16175" value="<?= e($valPersonaInfoOtro) ?>" placeholder="Especificar relación/parentesco…">
        </div>
      </div>
    </div>
  </div>

</div>
