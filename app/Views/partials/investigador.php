<?php
/**
 * Sección núcleo "Investigador" (cierre de las fichas MINSA):
 * quién llenó la ficha, cargo, profesión y fecha de investigación. Se autocompleta con
 * el usuario en sesión y la fecha de hoy, pero queda editable porque a veces
 * quien digita no es quien investigó.
 */

$valProf = $valoresFijos['investigador_profesion'] ?? '';
$profesionesPredefinidas = ['Médico especialista', 'Médico general', 'Obstetra', 'Enfermera', 'Estadístico'];
$esPredefinida = in_array($valProf, $profesionesPredefinidas, true);
$esOtroProf = ($valProf !== '' && !$esPredefinida) || ($valProf === 'Otro');
$valProfSel = $esPredefinida ? $valProf : ($esOtroProf ? 'Otro' : '');
$valProfOtra = $esOtroProf && $valProf !== 'Otro' ? $valProf : ($valoresFijos['investigador_profesion_otra'] ?? '');
?>

<div class="fields quarters">
  <div class="field">
    <label class="fl">Nombres y apellidos de quién investiga</label>
    <div class="control">
      <input type="text" name="investigador_nombre" value="<?= e($valoresFijos['investigador_nombre'] ?? '') ?>" placeholder="Nombres y apellidos…">
    </div>
  </div>

  <div class="field">
    <label class="fl">Cargo</label>
    <div class="control">
      <input type="text" name="investigador_cargo" value="<?= e($valoresFijos['investigador_cargo'] ?? '') ?>" placeholder="Ej: Epidemiólogo, Licenciado…">
    </div>
  </div>

  <div class="field">
    <label class="fl">Profesión</label>
    <div class="control">
      <select id="investigadorProfesionSel" name="investigador_profesion_sel" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="Médico especialista" <?= seleccionado($valProfSel, 'Médico especialista') ?>>Médico especialista</option>
        <option value="Médico general" <?= seleccionado($valProfSel, 'Médico general') ?>>Médico general</option>
        <option value="Obstetra" <?= seleccionado($valProfSel, 'Obstetra') ?>>Obstetra</option>
        <option value="Enfermera" <?= seleccionado($valProfSel, 'Enfermera') ?>>Enfermera</option>
        <option value="Estadístico" <?= seleccionado($valProfSel, 'Estadístico') ?>>Estadístico</option>
        <option value="Otro" <?= seleccionado($valProfSel, 'Otro') ?>>Otro (Especificar)</option>
      </select>
    </div>
  </div>

  <div class="field">
    <label class="fl">Fecha de investigación</label>
    <div class="control mono">
      <input type="date" name="fecha_investigacion" value="<?= e($valoresFijos['fecha_investigacion'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
    </div>
  </div>
</div>

<!-- Especificar otra profesión (condicional) -->
<div class="field" id="bloqueInvestigadorProfesionOtra" style="margin-top:12px; <?= !$esOtroProf ? 'display:none;' : '' ?>" <?= !$esOtroProf ? 'hidden' : '' ?>>
  <label class="fl">Especificar otra profesión</label>
  <div class="control">
    <input type="text" id="investigadorProfesionOtraInput" name="investigador_profesion_otra" value="<?= e($valProfOtra) ?>" placeholder="Especificar profesión…">
  </div>
</div>
