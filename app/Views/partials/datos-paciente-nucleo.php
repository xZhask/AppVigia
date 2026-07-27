<?php
/**
 * Campos núcleo de "Datos del paciente" con integración perfecta de B05.
 */
$puedeVerEtnia = \App\Core\Auth::tieneRol('ADMIN');
$esB05 = (($enfermedad['cie10'] ?? null) === 'B05');
require __DIR__ . '/datos-paciente-b05-loader.php';
?>
<!-- 1. Celular, Nacionalidad, Localidad -->
<div class="fields thirds" style="margin-top:14px">
  <div class="field">
    <label class="fl">N.° de celular</label>
    <div class="control mono"><input type="text" name="celular" value="<?= e($valoresFijos['celular'] ?? '') ?>" maxlength="20"></div>
  </div>
  <div class="field">
    <label class="fl">Nacionalidad</label>
    <div class="control"><input type="text" name="nacionalidad" value="<?= e($valoresFijos['nacionalidad'] ?? '') ?>"></div>
  </div>
  <div class="field">
    <label class="fl">Localidad</label>
    <div class="control"><input type="text" name="localidad" value="<?= e($valoresFijos['localidad'] ?? '') ?>"></div>
  </div>
  <div class="field wide">
    <label class="fl">Domicilio actual</label>
    <div class="control"><input type="text" name="direccion" value="<?= e($valoresFijos['direccion'] ?? '') ?>"></div>
  </div>
</div>

<!-- 2. Debajo de Domicilio actual: Referencia para localizar y Tipo de localidad (B05) -->
<div class="b05-field-wrap" <?= $esB05 ? '' : 'hidden' ?> style="margin-top:14px">
  <div class="fields" style="display:flex;gap:16px;align-items:flex-start">
    <?php if ($b05['referenciaLoc']['name']): ?>
    <div class="field" style="flex:2">
      <label class="fl">Referencia para localizar <span class="hint">(a la altura de o cerca de: Iglesia, fundo, comercio, etc.)</span></label>
      <div class="control">
        <input type="text" name="<?= $b05['referenciaLoc']['name'] ?>" value="<?= e($b05['referenciaLoc']['val']) ?>" placeholder="Referencia para localizar…">
      </div>
    </div>
    <?php endif; ?>

    <?php if ($b05['tipoLocalidad']['name']): ?>
    <div class="field" style="flex:1">
      <label class="fl">Tipo de localidad</label>
      <div class="control">
        <select name="<?= $b05['tipoLocalidad']['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach ($b05['tipoLocalidad']['opciones'] as $op): ?>
            <option value="<?= e($op['valor']) ?>" <?= seleccionado($b05['tipoLocalidad']['val'], $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- 3. Etnia / raza + Pueblo étnico o etnia + Ocupación (¡AL LADO DE ETNIA / RAZA EN LA MISMA FILA!) -->
<div class="fields thirds" style="margin-top:14px">
  <?php if ($puedeVerEtnia): ?>
  <div class="field">
    <label class="fl">Etnia / raza</label>
    <div class="control">
      <select name="etnia" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="MESTIZO" <?= seleccionado($valoresFijos['etnia'] ?? '', 'MESTIZO') ?>>Mestizo</option>
        <option value="ANDINO" <?= seleccionado($valoresFijos['etnia'] ?? '', 'ANDINO') ?>>Andino</option>
        <option value="ASIATICO_DESCENDIENTE" <?= seleccionado($valoresFijos['etnia'] ?? '', 'ASIATICO_DESCENDIENTE') ?>>Asiático descendiente</option>
        <option value="AFRODESCENDIENTE" <?= seleccionado($valoresFijos['etnia'] ?? '', 'AFRODESCENDIENTE') ?>>Afrodescendiente</option>
        <option value="INDIGENA_AMAZONICO" <?= seleccionado($valoresFijos['etnia'] ?? '', 'INDIGENA_AMAZONICO') ?>>Indígena amazónico</option>
        <option value="OTRO" <?= seleccionado($valoresFijos['etnia'] ?? '', 'OTRO') ?>>Otro</option>
      </select>
    </div>
    <span class="hint">Dato sensible: no aparece en exportaciones</span>
  </div>
  <?php endif; ?>

  <?php if ($b05['puebloEtnico']['name']): ?>
  <div class="field b05-elem" <?= $esB05 ? '' : 'hidden' ?>>
    <label class="fl">Pueblo étnico o etnia</label>
    <div class="control">
      <input type="text" name="<?= $b05['puebloEtnico']['name'] ?>" value="<?= e($b05['puebloEtnico']['val']) ?>" placeholder="Pueblo étnico / etnia…">
    </div>
  </div>
  <?php endif; ?>

  <?php if ($b05['ocupacion']['name']): ?>
  <div class="field b05-elem" <?= $esB05 ? '' : 'hidden' ?>>
    <label class="fl">Ocupación</label>
    <div class="control">
      <input type="text" name="<?= $b05['ocupacion']['name'] ?>" value="<?= e($b05['ocupacion']['val']) ?>" placeholder="Ocupación…">
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- 4. ¿Gestante? + Semanas de gestación -->
<?php if (($enfermedad['cie10'] ?? null) !== 'A80'): ?>
<div class="fields thirds" style="margin-top:14px">
  <div class="field" id="campoGestante" hidden>
    <label class="fl">¿Gestante?</label>
    <div class="control">
      <select id="gestanteSel" name="gestante" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="1" <?= seleccionado($valoresFijos['gestante'] ?? '', '1') ?>>Sí</option>
        <option value="0" <?= seleccionado($valoresFijos['gestante'] ?? '', '0') ?>>No</option>
      </select>
    </div>
  </div>
  <div class="field" id="campoSemanasGestacion" hidden>
    <label class="fl">Semanas de gestación</label>
    <div class="control mono"><input type="number" min="0" max="45" id="semanasGestacion" name="semanas_gestacion" value="<?= e($valoresFijos['semanas_gestacion'] ?? '') ?>"></div>
  </div>
</div>
<?php endif; ?>

<!-- 5. Debajo de Gestante / Semanas: Lugar probable de parto + Menor de edad / Tutor (B05) -->
<div class="b05-field-wrap" <?= $esB05 ? '' : 'hidden' ?>>
  <?php if ($b05['lugarParto']['name']): ?>
  <div class="field wide" id="wrapLugarPartoB05" style="margin-top:12px" <?= ($valoresFijos['gestante'] ?? '') === '1' ? '' : 'hidden' ?>>
    <label class="fl" style="color:var(--accent-deep, #0284c7)">Lugar probable de parto <span class="hint">(solo para gestantes)</span></label>
    <div class="control">
      <input type="text" name="<?= $b05['lugarParto']['name'] ?>" value="<?= e($b05['lugarParto']['val']) ?>" placeholder="Lugar probable de parto…">
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($b05['esMenorEdad']['name'])): ?>
  <div style="margin-top:16px;padding-top:14px;border-top:1px dashed var(--line)">
    <label class="sym" style="font-weight:600;color:var(--ink-1)">
      <input type="checkbox" id="chkEsMenorEdadB05" name="<?= $b05['esMenorEdad']['name'] ?>" value="1" <?= marcado(($b05['esMenorEdad']['val'] ?? '') === '1') ?>>
      ¿Es menor de edad? (Indicar datos de la madre o tutor)
    </label>

    <div id="wrapTutorB05" class="fields thirds" style="margin-top:12px;display:none" hidden>
      <?php if (!empty($b05['docTutor']['name'])): ?>
      <div class="field">
        <label class="fl">N.° Doc. Identidad de madre o tutor</label>
        <div class="control mono">
          <input type="text" name="<?= $b05['docTutor']['name'] ?>" value="<?= e($b05['docTutor']['val']) ?>" placeholder="Documento de identidad…">
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($b05['nombreTutor']['name'])): ?>
      <div class="field">
        <label class="fl">Nombre de madre o tutor</label>
        <div class="control">
          <input type="text" name="<?= $b05['nombreTutor']['name'] ?>" value="<?= e($b05['nombreTutor']['val']) ?>" placeholder="Nombre completo…">
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($b05['telefonoTutor']['name'])): ?>
      <div class="field">
        <label class="fl">Teléfono de madre o tutor</label>
        <div class="control mono">
          <input type="text" name="<?= $b05['telefonoTutor']['name'] ?>" value="<?= e($b05['telefonoTutor']['val']) ?>" placeholder="Teléfono de contacto…">
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
