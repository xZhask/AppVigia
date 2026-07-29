<?php
/**
 * IV. CUADRO CLÍNICO - Exclusivo para Parotiditis con complicaciones (B26)
 * 
 * Incluye:
 * - Fecha de inicio de síntomas
 * - Inflamación de glándulas parótidas (Sí/No -> Fecha inicio, Días duración, Localización: Unilateral/Bilateral)
 * - Inflamación de otras glándulas salivales (Submandibulares, Sublinguales)
 * - Complicaciones (Orquitis, Ooforitis, Pérdida audición, Encefalitis, Meningitis, Otras + Especifique) con fechas
 * - Hospitalización (Sí/No -> EE.SS., Fecha hospitalización, N.° días hospitalizado)
 * - Condición de egreso (Alta médica, Alta voluntaria, Referido -> EE.SS. referido, Fallecido -> Fecha, Causa de muerte)
 */

$esB26Cuadro = (($enfermedad['cie10'] ?? null) === 'B26');

$campoParotidas = $campo('b26_presento_inflamacion_de_glandulas_parotidas');
$campoFechaParotiditis = $campo('b26_fecha_de_inicio_de_parotiditis');
$campoDiasParotiditis = $campo('b26_n_de_dias_de_duracion');
$campoLocalizacion = $campo('b26_localizacion');
$campoSubman = $campo('b26_inflamacion_de_glandulas_submandibulares');
$campoSubling = $campo('b26_inflamacion_de_glandulas_sublinguales');
$campoOrquitis = $campo('b26_orquitis');
$campoOoforitis = $campo('b26_ooforitis');
$campoAudicion = $campo('b26_perdida_de_audicion');
$campoEncefalitis = $campo('b26_encefalitis');
$campoMeningitis = $campo('b26_meningitis');
$campoOtrasComp = $campo('b26_otras');
$campoHosp = $campo('b26_hospitalizacion');
$campoEstablecimientoHosp = $campo('b26_establecimiento');
$campoFechaHosp = $campo('b26_fecha_de_hospitalizacion');
$campoDiasHosp = $campo('b26_n_de_dias');
$campoCondicionEgreso = $campo('b26_condicion_de_egreso');
$campoFechaEgreso = $campo('b26_fecha_de_egreso');
$campoReferidoA = $campo('b26_referido_a');
$campoCausaMuerte = $campo('b26_causa_de_muerte');

// Extraer valores existentes. "Fecha de inicio de sintomas" es un campo fijo
// de caso.fecha_inicio_sintomas (name="fecha_inicio_sintomas" literal, mas
// abajo) -- el campo_def b26_fecha_de_inicio_de_sintomas existe pero ningun
// input postea a el, es un duplicado conceptual sin usar; no se resuelve por
// clave porque nunca fue alcanzable.
$valFechaInicioSintomas = $valoresFijos['fecha_inicio_sintomas'] ?? '';

// Glándulas parótidas
$rawParotidas = $campoParotidas['val'];
$valParotidas = (string) (is_array($rawParotidas) ? ($rawParotidas['valor'] ?? '') : $rawParotidas);
if ($valParotidas === '1') $valParotidas = 'SI';
if ($valParotidas === '0') $valParotidas = 'NO';

$valFechaParotiditis = $campoFechaParotiditis['val'];
$valDiasParotiditis = $campoDiasParotiditis['val'];
$valLocalizacion = $campoLocalizacion['val'];

// Otras glándulas salivales
$rawSubman = $campoSubman['val'];
$valSubman = (string) (is_array($rawSubman) ? ($rawSubman['valor'] ?? '') : $rawSubman);
$rawSubling = $campoSubling['val'];
$valSubling = (string) (is_array($rawSubling) ? ($rawSubling['valor'] ?? '') : $rawSubling);

// Complicaciones (SI_NO_FECHA)
$parserComplicacion = function (array $campoResuelto): array {
    $raw = $campoResuelto['val'];
    if (is_string($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) $raw = $json;
    }
    if (!is_array($raw)) {
        $raw = ['marcado' => ((string)$raw === '1' || (string)$raw === 'SI') ? 'SI' : 'NO'];
    }
    return [
        'marcado'     => ($raw['marcado'] ?? '') === 'SI' || ($raw['marcado'] ?? '') === '1' ? 'SI' : 'NO',
        'fecha'       => $raw['fecha'] ?? '',
        'especifique' => $raw['especifique'] ?? '',
    ];
};

$compOrquitis = $parserComplicacion($campoOrquitis);
$compOoforitis = $parserComplicacion($campoOoforitis);
$compAudicion  = $parserComplicacion($campoAudicion);
$compEncefalitis = $parserComplicacion($campoEncefalitis);
$compMeningitis = $parserComplicacion($campoMeningitis);
$compOtras     = $parserComplicacion($campoOtrasComp);

// Hospitalización
$rawHosp = $campoHosp['val'] !== '' ? $campoHosp['val'] : ($valoresFijos['hospitalizado'] ?? '');
$valHosp = (string) (is_array($rawHosp) ? ($rawHosp['valor'] ?? '') : $rawHosp);
if ($valHosp === '1') $valHosp = 'SI';
if ($valHosp === '0') $valHosp = 'NO';

$valEstablecimientoHosp = $campoEstablecimientoHosp['val'];
$valFechaHosp = $campoFechaHosp['val'];
$valDiasHosp = $campoDiasHosp['val'];

// Condición de egreso
$valCondicionEgreso = $campoCondicionEgreso['val'];
$valFechaEgreso = $campoFechaEgreso['val'];
$valReferidoA = $campoReferidoA['val'];
$valCausaMuerte = $campoCausaMuerte['val'];
?>

<div class="card section b26-cuadro-clinico-card" id="cardCuadroClinicoB26" <?= $esB26Cuadro ? '' : 'hidden style="display:none;"' ?>>
  <div class="section-head">
    <span class="section-num">4</span>
    <h3>Cuadro clínico</h3>
  </div>
  <div class="section-body">

    <!-- Fecha de inicio de síntomas -->
    <div class="fields halves" style="margin-bottom:18px">
      <div class="field">
        <label class="fl">Fecha de inicio de síntomas <span class="req">*</span></label>
        <div class="control mono">
          <input type="date" id="fechaInicioSintomasB26" name="fecha_inicio_sintomas" value="<?= e($valFechaInicioSintomas) ?>" max="<?= date('Y-m-d') ?>" required>
        </div>
      </div>
    </div>

    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">

    <!-- ¿Presentó inflamación de glándulas parótidas? -->
    <div class="field" style="margin-bottom:16px">
      <label class="fl" style="font-weight:600;font-size:14px;color:var(--text-main);margin-bottom:8px">
        ¿Presentó inflamación de glándulas parótidas?
      </label>
      <div class="control" style="margin-top:6px">
        <div style="display:flex;gap:20px;align-items:center" id="wrapParotidasB26">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoParotidas['name'] ?>" value="SI" class="radio-parotidas-b26" <?= ($valParotidas === 'SI') ? 'checked' : '' ?>>
            <span>Sí</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoParotidas['name'] ?>" value="NO" class="radio-parotidas-b26" <?= ($valParotidas === 'NO') ? 'checked' : '' ?>>
            <span>No</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Subcampos condicionales de Inflamación de Parótidas == SI -->
    <div class="fields thirds" id="wrapParotidasDetalleB26" style="margin-bottom:20px" <?= ($valParotidas === 'SI') ? '' : 'hidden style="display:none;"' ?>>
      <div class="field">
        <label class="fl">Fecha de inicio de parotiditis</label>
        <div class="control mono">
          <input type="date" id="fechaInicioParotiditisB26" name="<?= $campoFechaParotiditis['name'] ?>" value="<?= e($valFechaParotiditis) ?>" max="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="field">
        <label class="fl">N.º de días de duración</label>
        <div class="control mono">
          <input type="number" min="1" step="1" name="<?= $campoDiasParotiditis['name'] ?>" value="<?= e($valDiasParotiditis) ?>" placeholder="1" class="inp-dias-pos-b26" style="text-align:center">
        </div>
      </div>
      <div class="field">
        <label class="fl">Localización</label>
        <div class="control" style="margin-top:8px">
          <div style="display:flex;gap:16px;align-items:center">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:13.5px">
              <input type="radio" name="<?= $campoLocalizacion['name'] ?>" value="UNILATERAL" <?= (strtoupper($valLocalizacion) === 'UNILATERAL') ? 'checked' : '' ?>>
              <span>Unilateral</span>
            </label>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:13.5px">
              <input type="radio" name="<?= $campoLocalizacion['name'] ?>" value="BILATERAL" <?= (strtoupper($valLocalizacion) === 'BILATERAL') ? 'checked' : '' ?>>
              <span>Bilateral</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">

    <!-- Inflamación de otras glándulas salivales -->
    <div class="eyebrow" style="margin-bottom:12px">Inflamación de otras glándulas salivales</div>
    <div class="fields halves" style="margin-bottom:20px">
      <div class="field">
        <label class="fl">Glándulas submandibulares</label>
        <div class="control" style="margin-top:6px">
          <div style="display:flex;gap:20px;align-items:center">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
              <input type="radio" name="<?= $campoSubman['name'] ?>" value="1" <?= ($valSubman === '1' || $valSubman === 'SI') ? 'checked' : '' ?>>
              <span>Sí</span>
            </label>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
              <input type="radio" name="<?= $campoSubman['name'] ?>" value="0" <?= ($valSubman === '0' || $valSubman === 'NO') ? 'checked' : '' ?>>
              <span>No</span>
            </label>
          </div>
        </div>
      </div>
      <div class="field">
        <label class="fl">Glándulas sublinguales</label>
        <div class="control" style="margin-top:6px">
          <div style="display:flex;gap:20px;align-items:center">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
              <input type="radio" name="<?= $campoSubling['name'] ?>" value="1" <?= ($valSubling === '1' || $valSubling === 'SI') ? 'checked' : '' ?>>
              <span>Sí</span>
            </label>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
              <input type="radio" name="<?= $campoSubling['name'] ?>" value="0" <?= ($valSubling === '0' || $valSubling === 'NO') ? 'checked' : '' ?>>
              <span>No</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">

    <!-- Complicaciones -->
    <div class="eyebrow" style="margin-bottom:12px">Complicaciones (marcar si presentó y especificar fecha de inicio)</div>
    
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:16px;margin-bottom:20px">
      
      <!-- Orquitis -->
      <div class="box-complicacion-b26" style="padding:12px;border:1px solid var(--line);border-radius:8px;background:var(--card-bg, rgba(255,255,255,0.02))">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600">
          <input type="checkbox" name="<?= $campoOrquitis['name'] ?>[marcado]" value="SI" class="chk-complicacion-b26" <?= ($compOrquitis['marcado'] === 'SI') ? 'checked' : '' ?>>
          <span>Orquitis</span>
        </label>
        <div class="wrap-fecha-complicacion" style="margin-top:10px;<?= ($compOrquitis['marcado'] === 'SI') ? '' : 'display:none;' ?>">
          <label class="fl" style="font-size:12px">Fecha de inicio de Orquitis</label>
          <div class="control mono">
            <input type="date" name="<?= $campoOrquitis['name'] ?>[fecha]" value="<?= e($compOrquitis['fecha']) ?>" max="<?= date('Y-m-d') ?>" class="inp-fecha-complicacion-b26" <?= ($compOrquitis['marcado'] === 'SI') ? '' : 'disabled' ?>>
          </div>
        </div>
      </div>

      <!-- Ooforitis -->
      <div class="box-complicacion-b26" style="padding:12px;border:1px solid var(--line);border-radius:8px;background:var(--card-bg, rgba(255,255,255,0.02))">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600">
          <input type="checkbox" name="<?= $campoOoforitis['name'] ?>[marcado]" value="SI" class="chk-complicacion-b26" <?= ($compOoforitis['marcado'] === 'SI') ? 'checked' : '' ?>>
          <span>Ooforitis</span>
        </label>
        <div class="wrap-fecha-complicacion" style="margin-top:10px;<?= ($compOoforitis['marcado'] === 'SI') ? '' : 'display:none;' ?>">
          <label class="fl" style="font-size:12px">Fecha de inicio de Ooforitis</label>
          <div class="control mono">
            <input type="date" name="<?= $campoOoforitis['name'] ?>[fecha]" value="<?= e($compOoforitis['fecha']) ?>" max="<?= date('Y-m-d') ?>" class="inp-fecha-complicacion-b26" <?= ($compOoforitis['marcado'] === 'SI') ? '' : 'disabled' ?>>
          </div>
        </div>
      </div>

      <!-- Pérdida de audición -->
      <div class="box-complicacion-b26" style="padding:12px;border:1px solid var(--line);border-radius:8px;background:var(--card-bg, rgba(255,255,255,0.02))">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600">
          <input type="checkbox" name="<?= $campoAudicion['name'] ?>[marcado]" value="SI" class="chk-complicacion-b26" <?= ($compAudicion['marcado'] === 'SI') ? 'checked' : '' ?>>
          <span>Pérdida de audición</span>
        </label>
        <div class="wrap-fecha-complicacion" style="margin-top:10px;<?= ($compAudicion['marcado'] === 'SI') ? '' : 'display:none;' ?>">
          <label class="fl" style="font-size:12px">Fecha de inicio de Pérdida de audición</label>
          <div class="control mono">
            <input type="date" name="<?= $campoAudicion['name'] ?>[fecha]" value="<?= e($compAudicion['fecha']) ?>" max="<?= date('Y-m-d') ?>" class="inp-fecha-complicacion-b26" <?= ($compAudicion['marcado'] === 'SI') ? '' : 'disabled' ?>>
          </div>
        </div>
      </div>

      <!-- Encefalitis -->
      <div class="box-complicacion-b26" style="padding:12px;border:1px solid var(--line);border-radius:8px;background:var(--card-bg, rgba(255,255,255,0.02))">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600">
          <input type="checkbox" name="<?= $campoEncefalitis['name'] ?>[marcado]" value="SI" class="chk-complicacion-b26" <?= ($compEncefalitis['marcado'] === 'SI') ? 'checked' : '' ?>>
          <span>Encefalitis</span>
        </label>
        <div class="wrap-fecha-complicacion" style="margin-top:10px;<?= ($compEncefalitis['marcado'] === 'SI') ? '' : 'display:none;' ?>">
          <label class="fl" style="font-size:12px">Fecha de inicio de Encefalitis</label>
          <div class="control mono">
            <input type="date" name="<?= $campoEncefalitis['name'] ?>[fecha]" value="<?= e($compEncefalitis['fecha']) ?>" max="<?= date('Y-m-d') ?>" class="inp-fecha-complicacion-b26" <?= ($compEncefalitis['marcado'] === 'SI') ? '' : 'disabled' ?>>
          </div>
        </div>
      </div>

      <!-- Meningitis -->
      <div class="box-complicacion-b26" style="padding:12px;border:1px solid var(--line);border-radius:8px;background:var(--card-bg, rgba(255,255,255,0.02))">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600">
          <input type="checkbox" name="<?= $campoMeningitis['name'] ?>[marcado]" value="SI" class="chk-complicacion-b26" <?= ($compMeningitis['marcado'] === 'SI') ? 'checked' : '' ?>>
          <span>Meningitis</span>
        </label>
        <div class="wrap-fecha-complicacion" style="margin-top:10px;<?= ($compMeningitis['marcado'] === 'SI') ? '' : 'display:none;' ?>">
          <label class="fl" style="font-size:12px">Fecha de inicio de Meningitis</label>
          <div class="control mono">
            <input type="date" name="<?= $campoMeningitis['name'] ?>[fecha]" value="<?= e($compMeningitis['fecha']) ?>" max="<?= date('Y-m-d') ?>" class="inp-fecha-complicacion-b26" <?= ($compMeningitis['marcado'] === 'SI') ? '' : 'disabled' ?>>
          </div>
        </div>
      </div>

      <!-- Otras -->
      <div class="box-complicacion-b26" style="padding:12px;border:1px solid var(--line);border-radius:8px;background:var(--card-bg, rgba(255,255,255,0.02))">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600">
          <input type="checkbox" name="<?= $campoOtrasComp['name'] ?>[marcado]" value="SI" class="chk-complicacion-b26" <?= ($compOtras['marcado'] === 'SI') ? 'checked' : '' ?>>
          <span>Otras (especificar)</span>
        </label>
        <div class="wrap-fecha-complicacion" style="margin-top:10px;<?= ($compOtras['marcado'] === 'SI') ? '' : 'display:none;' ?>">
          <div style="margin-bottom:8px">
            <label class="fl" style="font-size:12px">Especifique complicación</label>
            <div class="control">
              <input type="text" name="<?= $campoOtrasComp['name'] ?>[especifique]" value="<?= e($compOtras['especifique']) ?>" placeholder="Especifique…" class="inp-fecha-complicacion-b26" <?= ($compOtras['marcado'] === 'SI') ? '' : 'disabled' ?>>
            </div>
          </div>
          <div>
            <label class="fl" style="font-size:12px">Fecha de inicio</label>
            <div class="control mono">
              <input type="date" name="<?= $campoOtrasComp['name'] ?>[fecha]" value="<?= e($compOtras['fecha']) ?>" max="<?= date('Y-m-d') ?>" class="inp-fecha-complicacion-b26" <?= ($compOtras['marcado'] === 'SI') ? '' : 'disabled' ?>>
            </div>
          </div>
        </div>
      </div>

    </div>

    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">

    <!-- Hospitalización -->
    <div class="field" style="margin-bottom:16px">
      <label class="fl" style="font-weight:600;font-size:14px;color:var(--text-main);margin-bottom:8px">
        Hospitalización
      </label>
      <div class="control" style="margin-top:6px">
        <div style="display:flex;gap:20px;align-items:center" id="wrapHospitalizacionB26">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoHosp['name'] ?>" value="SI" class="radio-hospitalizacion-b26" <?= ($valHosp === 'SI') ? 'checked' : '' ?>>
            <span>Sí</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoHosp['name'] ?>" value="NO" class="radio-hospitalizacion-b26" <?= ($valHosp === 'NO') ? 'checked' : '' ?>>
            <span>No</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Subcampos condicionales de Hospitalización == SI -->
    <div class="fields thirds" id="wrapHospitalizacionDetalleB26" style="margin-bottom:20px" <?= ($valHosp === 'SI') ? '' : 'hidden style="display:none;"' ?>>
      <div class="field">
        <label class="fl">Nombre del EE.SS.</label>
        <div class="control">
          <input type="text" name="<?= $campoEstablecimientoHosp['name'] ?>" value="<?= e($valEstablecimientoHosp) ?>" placeholder="Nombre del establecimiento…">
        </div>
      </div>
      <div class="field">
        <label class="fl">Fecha de hospitalización</label>
        <div class="control mono">
          <input type="date" id="fechaHospitalizacionB26" name="<?= $campoFechaHosp['name'] ?>" value="<?= e($valFechaHosp) ?>" max="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="field">
        <label class="fl">N.° de días hospitalizado</label>
        <div class="control mono">
          <input type="number" min="1" step="1" name="<?= $campoDiasHosp['name'] ?>" value="<?= e($valDiasHosp) ?>" placeholder="1" class="inp-dias-pos-b26" style="text-align:center">
        </div>
      </div>
    </div>

    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">

    <!-- Condición de egreso del paciente -->
    <div class="field" style="margin-bottom:16px">
      <label class="fl" style="font-weight:600;font-size:14px;color:var(--text-main);margin-bottom:8px">
        Condición de egreso del paciente
      </label>
      <div class="control" style="margin-top:6px">
        <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap" id="wrapEgresoB26">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoCondicionEgreso['name'] ?>" value="ALTA_MEDICA" class="radio-egreso-b26" <?= ($valCondicionEgreso === 'ALTA_MEDICA') ? 'checked' : '' ?>>
            <span>Alta médica</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoCondicionEgreso['name'] ?>" value="ALTA_VOLUNTARIA" class="radio-egreso-b26" <?= ($valCondicionEgreso === 'ALTA_VOLUNTARIA') ? 'checked' : '' ?>>
            <span>Alta voluntaria</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoCondicionEgreso['name'] ?>" value="REFERIDO" class="radio-egreso-b26" <?= ($valCondicionEgreso === 'REFERIDO') ? 'checked' : '' ?>>
            <span>Referido</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoCondicionEgreso['name'] ?>" value="FALLECIDO" class="radio-egreso-b26" <?= ($valCondicionEgreso === 'FALLECIDO') ? 'checked' : '' ?>>
            <span>Fallecido</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Fecha de egreso (Siempre visible) -->
    <div class="fields halves" style="margin-bottom:16px">
      <div class="field">
        <label class="fl">Fecha de egreso</label>
        <div class="control mono">
          <input type="date" id="fechaEgresoB26" name="<?= $campoFechaEgreso['name'] ?>" value="<?= e($valFechaEgreso) ?>" max="<?= date('Y-m-d') ?>">
        </div>
      </div>
    </div>

    <!-- Subcampos condicionales de Condición de Egreso: Referido -->
    <div class="fields" id="wrapEgresoReferidoB26" style="margin-bottom:18px" <?= ($valCondicionEgreso === 'REFERIDO') ? '' : 'hidden style="display:none;"' ?>>
      <div class="field">
        <label class="fl">*Referido a (EESS de referencia)</label>
        <div class="control">
          <input type="text" name="<?= $campoReferidoA['name'] ?>" value="<?= e($valReferidoA) ?>" placeholder="Nombre del EE.SS. de referencia…">
        </div>
      </div>
    </div>

    <!-- Subcampos condicionales de Condición de Egreso: Fallecido -->
    <div class="fields" id="wrapEgresoFallecidoB26" style="margin-bottom:18px" <?= ($valCondicionEgreso === 'FALLECIDO') ? '' : 'hidden style="display:none;"' ?>>
      <div class="field">
        <label class="fl">Causa de muerte</label>
        <div class="control">
          <input type="text" name="<?= $campoCausaMuerte['name'] ?>" value="<?= e($valCausaMuerte) ?>" placeholder="Causa de muerte…">
        </div>
      </div>
    </div>

  </div>
</div>
