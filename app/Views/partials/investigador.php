<?php
/**
 * Sección núcleo "Investigador" (cierre de las fichas MINSA):
 * quién llenó la ficha, cargo, profesión, teléfono/email y fecha de
 * investigación. Se autocompleta con el usuario en sesión (nombre, email) y
 * la fecha de hoy, pero queda editable porque a veces quien digita no es
 * quien investigó. Teléfono/Email se muestran para todas las fichas (varias
 * PDF los piden, ej. B01 ítems 40-41) aunque no todas los exijan -- mismo
 * criterio que "Fecha de investigación", que tampoco todas piden.
 *
 * nucleo_ajustes.investigador (A50, 2026-09-14): la ficha declara qué campos
 * lleva la tarjeta y con qué etiqueta (campoInvestigador()); los demás no se
 * pintan. Los `if` van en la columna 0 para que las fichas sin ajuste queden
 * con el mismo HTML de siempre.
 */

$valProf = $valoresFijos['investigador_profesion'] ?? '';
$profesionesPredefinidas = ['Médico especialista', 'Médico general', 'Obstetra', 'Enfermera', 'Estadístico'];
$esPredefinida = in_array($valProf, $profesionesPredefinidas, true);
$esOtroProf = ($valProf !== '' && !$esPredefinida) || ($valProf === 'Otro');
$valProfSel = $esPredefinida ? $valProf : ($esOtroProf ? 'Otro' : '');
$valProfOtra = $esOtroProf && $valProf !== 'Otro' ? $valProf : ($valoresFijos['investigador_profesion_otra'] ?? '');
$etiquetasInvestigador = array_map(fn(string $campoTarjeta): ?string => campoInvestigador($enfermedad, $campoTarjeta), array_combine(array_keys(CAMPOS_INVESTIGADOR), array_keys(CAMPOS_INVESTIGADOR)));
?>

<div class="fields quarters">
<?php if ($etiquetasInvestigador['nombre'] !== null): ?>
  <div class="field">
    <label class="fl"><?= e($etiquetasInvestigador['nombre']) ?></label>
    <div class="control">
      <input type="text" name="investigador_nombre" value="<?= e($valoresFijos['investigador_nombre'] ?? '') ?>" placeholder="Nombres y apellidos…">
    </div>
  </div>
<?php endif; ?>

<?php if ($etiquetasInvestigador['cargo'] !== null): ?>
  <div class="field">
    <label class="fl"><?= e($etiquetasInvestigador['cargo']) ?></label>
    <div class="control">
      <input type="text" name="investigador_cargo" value="<?= e($valoresFijos['investigador_cargo'] ?? '') ?>" placeholder="Ej: Epidemiólogo, Licenciado…">
    </div>
  </div>
<?php endif; ?>

<?php if ($etiquetasInvestigador['profesion'] !== null): ?>
  <div class="field">
    <label class="fl"><?= e($etiquetasInvestigador['profesion']) ?></label>
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
<?php endif; ?>

<?php if ($etiquetasInvestigador['fecha_investigacion'] !== null): ?>
  <div class="field">
    <label class="fl"><?= e($etiquetasInvestigador['fecha_investigacion']) ?></label>
    <div class="control mono">
      <input type="date" name="fecha_investigacion" value="<?= e($valoresFijos['fecha_investigacion'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
    </div>
  </div>
<?php endif; ?>

<?php if ($etiquetasInvestigador['telefono'] !== null): ?>
  <div class="field">
    <label class="fl"><?= e($etiquetasInvestigador['telefono']) ?></label>
    <div class="control mono">
      <input type="text" name="investigador_telefono" value="<?= e($valoresFijos['investigador_telefono'] ?? '') ?>" placeholder="N.° de teléfono…" maxlength="20">
    </div>
  </div>
<?php endif; ?>

<?php if ($etiquetasInvestigador['email'] !== null): ?>
  <div class="field">
    <label class="fl"><?= e($etiquetasInvestigador['email']) ?></label>
    <div class="control">
      <input type="email" name="investigador_email" value="<?= e($valoresFijos['investigador_email'] ?? '') ?>" placeholder="nombre@dirsapol.gob.pe" maxlength="150">
    </div>
  </div>
<?php endif; ?>
</div>

<?php if ($etiquetasInvestigador['profesion'] !== null): ?>
<!-- Especificar otra profesión (condicional) -->
<div class="field" id="bloqueInvestigadorProfesionOtra" style="margin-top:12px; <?= !$esOtroProf ? 'display:none;' : '' ?>" <?= !$esOtroProf ? 'hidden' : '' ?>>
  <label class="fl">Especificar otra profesión</label>
  <div class="control">
    <input type="text" id="investigadorProfesionOtraInput" name="investigador_profesion_otra" value="<?= e($valProfOtra) ?>" placeholder="Especificar profesión…">
  </div>
</div>
<?php endif; ?>
