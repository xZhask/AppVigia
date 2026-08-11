<?php
/**
 * Sección núcleo "Investigador" (cierre de las fichas MINSA):
 * quién llenó la ficha, cargo, profesión, teléfono/email y fecha de
 * investigación. Se autocompleta con el usuario en sesión (nombre, email) y
 * la fecha de hoy, pero queda editable porque a veces quien digita no es
 * quien investigó. Teléfono/Email se muestran para todas las fichas (varias
 * PDF los piden, ej. B01 ítems 40-41) aunque no todas los exijan -- mismo
 * criterio que "Fecha de investigación", que tampoco todas piden.
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

  <div class="field">
    <label class="fl">Teléfono</label>
    <div class="control mono">
      <input type="text" name="investigador_telefono" value="<?= e($valoresFijos['investigador_telefono'] ?? '') ?>" placeholder="N.° de teléfono…" maxlength="20">
    </div>
  </div>

  <div class="field">
    <label class="fl">Email</label>
    <div class="control">
      <input type="email" name="investigador_email" value="<?= e($valoresFijos['investigador_email'] ?? '') ?>" placeholder="nombre@dirsapol.gob.pe" maxlength="150">
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
