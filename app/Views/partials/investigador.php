<?php
/**
 * Sección núcleo "Investigador" (cierre de casi todas las fichas MINSA):
 * quién llenó la ficha, cargo y fecha de investigación. Se autocompleta con
 * el usuario en sesión y la fecha de hoy, pero queda editable porque a veces
 * quien digita no es quien investigó. Variable esperada: $valoresFijos con
 * las claves investigador_nombre, investigador_cargo, fecha_investigacion.
 */
?>
<div class="fields thirds">
  <div class="field">
    <label class="fl">Investigador / responsable</label>
    <div class="control"><input type="text" name="investigador_nombre" value="<?= e($valoresFijos['investigador_nombre']) ?>"></div>
  </div>
  <div class="field">
    <label class="fl">Cargo</label>
    <div class="control"><input type="text" name="investigador_cargo" value="<?= e($valoresFijos['investigador_cargo']) ?>"></div>
  </div>
  <div class="field">
    <label class="fl">Fecha de investigación</label>
    <div class="control mono"><input type="date" name="fecha_investigacion" value="<?= e($valoresFijos['fecha_investigacion']) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
  </div>
</div>
