<?php
/**
 * Campos núcleo de "Datos del paciente" que se repiten en casi todas las
 * fichas MINSA (AUDITORIA_FICHA_DIFTERIA.md, punto 2): celular, nacionalidad,
 * dirección, localidad, etnia y gestante/semanas de gestación. Van en
 * persona/caso, no en la definición de cada ficha.
 *
 * Etnia es un dato sensible: se captura porque el formato oficial lo exige,
 * pero se excluye de listados y exportaciones (igual que otros campos
 * `sensible = 1`), por eso su valor se guarda aparte y solo lo ve ADMIN.
 *
 * Gestante solo aplica si sexo = F, y semanas de gestación solo si
 * gestante = Sí — el toggle vive en ficha.js (son campos núcleo, no
 * campo_def, así que no usan el motor genérico de dependencias).
 *
 * Variable esperada: $valoresFijos con celular, nacionalidad, direccion,
 * localidad, etnia, gestante, semanas_gestacion.
 */
$puedeVerEtnia = \App\Core\Auth::tieneRol('ADMIN');
?>
<div class="fields thirds" style="margin-top:14px">
  <div class="field">
    <label class="fl">N.° de celular</label>
    <div class="control mono"><input type="text" name="celular" value="<?= e($valoresFijos['celular']) ?>" maxlength="20"></div>
  </div>
  <div class="field">
    <label class="fl">Nacionalidad</label>
    <div class="control"><input type="text" name="nacionalidad" value="<?= e($valoresFijos['nacionalidad']) ?>"></div>
  </div>
  <div class="field">
    <label class="fl">Localidad</label>
    <div class="control"><input type="text" name="localidad" value="<?= e($valoresFijos['localidad']) ?>"></div>
  </div>
  <div class="field wide">
    <label class="fl">Domicilio actual</label>
    <div class="control"><input type="text" name="direccion" value="<?= e($valoresFijos['direccion']) ?>"></div>
  </div>
  <?php if ($puedeVerEtnia): ?>
  <div class="field">
    <label class="fl">Etnia / raza</label>
    <div class="control">
      <select name="etnia" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="MESTIZO" <?= seleccionado($valoresFijos['etnia'], 'MESTIZO') ?>>Mestizo</option>
        <option value="ANDINO" <?= seleccionado($valoresFijos['etnia'], 'ANDINO') ?>>Andino</option>
        <option value="ASIATICO_DESCENDIENTE" <?= seleccionado($valoresFijos['etnia'], 'ASIATICO_DESCENDIENTE') ?>>Asiático descendiente</option>
        <option value="AFRODESCENDIENTE" <?= seleccionado($valoresFijos['etnia'], 'AFRODESCENDIENTE') ?>>Afrodescendiente</option>
        <option value="INDIGENA_AMAZONICO" <?= seleccionado($valoresFijos['etnia'], 'INDIGENA_AMAZONICO') ?>>Indígena amazónico</option>
        <option value="OTRO" <?= seleccionado($valoresFijos['etnia'], 'OTRO') ?>>Otro</option>
      </select>
    </div>
    <span class="hint">Dato sensible: no aparece en listados ni exportaciones</span>
  </div>
  <?php endif; ?>
  <div class="field" id="campoGestante">
    <label class="fl">¿Gestante?</label>
    <div class="control">
      <select id="gestanteSel" name="gestante" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="1" <?= seleccionado($valoresFijos['gestante'], '1') ?>>Sí</option>
        <option value="0" <?= seleccionado($valoresFijos['gestante'], '0') ?>>No</option>
      </select>
    </div>
  </div>
  <div class="field" id="campoSemanasGestacion">
    <label class="fl">Semanas de gestación</label>
    <div class="control mono"><input type="number" min="0" max="45" id="semanasGestacion" name="semanas_gestacion" value="<?= e($valoresFijos['semanas_gestacion']) ?>"></div>
  </div>
</div>
