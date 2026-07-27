<?php
/**
 * Campos núcleo de "Datos del paciente" con integración perfecta de B05 y O95.
 */
$puedeVerEtnia = \App\Core\Auth::tieneRol('ADMIN');
$esB05 = (($enfermedad['cie10'] ?? null) === 'B05');
$esO95 = (($enfermedad['cie10'] ?? null) === 'O95');
require __DIR__ . '/datos-paciente-b05-loader.php';
?>
<!-- 1. Celular, Nacionalidad, Localidad -->
<div class="fields thirds" style="margin-top:14px">
  <div class="field o95-hide" <?= $esO95 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">N.° de celular</label>
    <div class="control mono"><input type="text" name="celular" value="<?= e($valoresFijos['celular'] ?? '') ?>" maxlength="20"></div>
  </div>
  <div class="field o95-hide" <?= $esO95 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Nacionalidad</label>
    <div class="control"><input type="text" name="nacionalidad" value="<?= e($valoresFijos['nacionalidad'] ?? '') ?>"></div>
  </div>
  <div class="field o95-hide" <?= $esO95 ? 'hidden style="display:none;"' : '' ?>>
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
  <?php
  $cie10Actual = strtoupper(trim($enfermedad['cie10'] ?? ''));
  $esO95 = ($cie10Actual === 'O95');
  $valTipoFichaO95 = $valoresFijos['o95_tipo_ficha'] ?? $valoresCampos[14300] ?? $_POST['o95_tipo_ficha'] ?? 'ANEXO_1';
  $mostrarEtniaO95Anexo2 = ($esO95 && $valTipoFichaO95 === 'ANEXO_2');
  ?>
  <!-- Grupo étnico (O95 - Anexo 2) -->
  <div class="field o95-anexo-2-elem" id="campoGrupoEtnicoO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Grupo étnico</label>
    <div class="control">
      <select id="o95GrupoEtnicoSel" name="campo_16110" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <?php foreach (['Mestizo', 'Andino', 'Indígena amazónico', 'Afroperuano', 'Asiático descendiente', 'Otro'] as $g): ?>
          <option value="<?= $g ?>" <?= seleccionado($valoresCampos[16110] ?? '', $g) ?>><?= $g ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Etnia / Pueblo étnico (O95 - Anexo 2) -->
  <div class="field o95-anexo-2-elem" id="campoPuebloEtnicoO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Etnia / Pueblo étnico</label>
    <div class="control">
      <select id="o95PuebloEtnicoSel" name="campo_16111" data-nosearch="true" data-valor-actual="<?= e($valoresCampos[16111] ?? '') ?>">
        <option value="">Seleccionar…</option>
      </select>
    </div>
  </div>

  <!-- Idioma, Nivel educativo, Estado civil, Ocupación, Tipo de seguro (O95 - Anexo 2) -->
  <div class="field o95-anexo-2-elem" id="campoIdiomaO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Idioma</label>
    <div class="control">
      <select id="o95IdiomaSel" name="campo_14316" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="ESPANOL" <?= seleccionado($valoresCampos[14316] ?? '', 'ESPANOL') ?>>Español</option>
        <option value="QUECHUA" <?= seleccionado($valoresCampos[14316] ?? '', 'QUECHUA') ?>>Quechua</option>
        <option value="AYMARA" <?= seleccionado($valoresCampos[14316] ?? '', 'AYMARA') ?>>Aymara</option>
        <option value="OTRA" <?= seleccionado($valoresCampos[14316] ?? '', 'OTRA') ?>>Otra</option>
      </select>
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoIdiomaOtraO95" <?= (!$mostrarEtniaO95Anexo2 || ($valoresCampos[14316] ?? '') !== 'OTRA') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Especificar otro idioma</label>
    <div class="control">
      <input type="text" name="campo_16113" value="<?= e($valoresCampos[16113] ?? '') ?>" placeholder="Especificar idioma…">
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoNivelEduO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Nivel educativo</label>
    <div class="control">
      <select name="campo_14317" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="NINGUNO" <?= seleccionado($valoresCampos[14317] ?? '', 'NINGUNO') ?>>Ninguno</option>
        <option value="PRIMARIA_INCOMPLETA" <?= seleccionado($valoresCampos[14317] ?? '', 'PRIMARIA_INCOMPLETA') ?>>Primaria incompleta</option>
        <option value="PRIMARIA_COMPLETA" <?= seleccionado($valoresCampos[14317] ?? '', 'PRIMARIA_COMPLETA') ?>>Primaria completa</option>
        <option value="SECUNDARIA_INCOMPLETA" <?= seleccionado($valoresCampos[14317] ?? '', 'SECUNDARIA_INCOMPLETA') ?>>Secundaria incompleta</option>
        <option value="SECUNDARIA_COMPLETA" <?= seleccionado($valoresCampos[14317] ?? '', 'SECUNDARIA_COMPLETA') ?>>Secundaria completa</option>
        <option value="SUPERIOR_UNIVERSITARIA" <?= seleccionado($valoresCampos[14317] ?? '', 'SUPERIOR_UNIVERSITARIA') ?>>Superior universitaria</option>
        <option value="SUPERIOR_TECNICA" <?= seleccionado($valoresCampos[14317] ?? '', 'SUPERIOR_TECNICA') ?>>Superior técnica</option>
        <option value="DESCONOCIDO" <?= seleccionado($valoresCampos[14317] ?? '', 'DESCONOCIDO') ?>>Desconocido</option>
      </select>
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoEstadoCivilO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Estado civil</label>
    <div class="control">
      <select name="campo_14318" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="SOLTERA" <?= seleccionado($valoresCampos[14318] ?? '', 'SOLTERA') ?>>Soltera</option>
        <option value="CASADA" <?= seleccionado($valoresCampos[14318] ?? '', 'CASADA') ?>>Casada</option>
        <option value="CONVIVIENTE" <?= seleccionado($valoresCampos[14318] ?? '', 'CONVIVIENTE') ?>>Conviviente</option>
        <option value="DIVORCIADA" <?= seleccionado($valoresCampos[14318] ?? '', 'DIVORCIADA') ?>>Divorciada</option>
        <option value="SEPARADA" <?= seleccionado($valoresCampos[14318] ?? '', 'SEPARADA') ?>>Separada</option>
        <option value="VIUDA" <?= seleccionado($valoresCampos[14318] ?? '', 'VIUDA') ?>>Viuda</option>
        <option value="DESCONOCIDO" <?= seleccionado($valoresCampos[14318] ?? '', 'DESCONOCIDO') ?>>Desconocido</option>
      </select>
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoOcupacionO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Ocupación</label>
    <div class="control">
      <input type="text" name="campo_16112" value="<?= e($valoresCampos[16112] ?? '') ?>" placeholder="Especificar ocupación…">
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoTipoSeguroO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Tipo de seguro</label>
    <div class="control">
      <select id="o95TipoSeguroSel" name="campo_14319" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="SIS" <?= seleccionado($valoresCampos[14319] ?? '', 'SIS') ?>>SIS</option>
        <option value="ESSALUD" <?= seleccionado($valoresCampos[14319] ?? '', 'ESSALUD') ?>>EsSalud</option>
        <option value="PRIVADO" <?= seleccionado($valoresCampos[14319] ?? '', 'PRIVADO') ?>>Privado</option>
        <option value="OTROS" <?= seleccionado($valoresCampos[14319] ?? '', 'OTROS') ?>>Otros</option>
        <option value="NO_TIENE" <?= seleccionado($valoresCampos[14319] ?? '', 'NO_TIENE') ?>>No tiene seguro</option>
      </select>
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoSeguroOtroO95" <?= (!$mostrarEtniaO95Anexo2 || ($valoresCampos[14319] ?? '') !== 'OTROS') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Especificar otro seguro</label>
    <div class="control">
      <input type="text" name="campo_16114" value="<?= e($valoresCampos[16114] ?? '') ?>" placeholder="Especificar tipo de seguro…">
    </div>
  </div>

  <!-- Etnia / raza (Enfermedades Generales) -->
  <div class="field o95-hide" id="campoEtniaRazaGral" <?= $esO95 ? 'hidden style="display:none;"' : '' ?>>
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
<?php if (!in_array(strtoupper(trim($enfermedad['cie10'] ?? '')), ['A80', 'O95'], true)): ?>
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
