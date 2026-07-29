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
  $campoGrupoEtnicoO95 = $campo('o95_grupo_etnico');
  $campoPuebloEtnicoO95 = $campo('o95_etnia_pueblo_etnico');
  $campoIdiomaO95 = $campo('o95_idioma');
  $campoIdiomaOtraO95 = $campo('o95_idioma_otra');
  $campoNivelEduO95 = $campo('o95_nivel_educativo');
  $campoEstadoCivilO95 = $campo('o95_estado_civil');
  $campoOcupacionO95 = $campo('o95_ocupacion');
  $campoTipoSeguroO95 = $campo('o95_tipo_de_seguro');
  $campoSeguroOtroO95 = $campo('o95_tipo_de_seguro_otro');
  $campoTipoFichaO95Nucleo = $campo('o95_tipo_de_ficha');
  $valTipoFichaO95 = $valoresFijos['o95_tipo_ficha']
      ?? ($campoTipoFichaO95Nucleo['val'] !== '' ? $campoTipoFichaO95Nucleo['val'] : null)
      ?? $_POST['o95_tipo_ficha']
      ?? 'ANEXO_1';
  $mostrarEtniaO95Anexo2 = ($esO95 && $valTipoFichaO95 === 'ANEXO_2');
  ?>
  <!-- Grupo étnico (O95 - Anexo 2) -->
  <div class="field o95-anexo-2-elem" id="campoGrupoEtnicoO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Grupo étnico</label>
    <div class="control">
      <select id="o95GrupoEtnicoSel" name="<?= $campoGrupoEtnicoO95['name'] ?>" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <?php foreach (['Mestizo', 'Andino', 'Indígena amazónico', 'Afroperuano', 'Asiático descendiente', 'Otro'] as $g): ?>
          <option value="<?= $g ?>" <?= seleccionado($campoGrupoEtnicoO95['val'], $g) ?>><?= $g ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Etnia / Pueblo étnico (O95 - Anexo 2) -->
  <div class="field o95-anexo-2-elem" id="campoPuebloEtnicoO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Etnia / Pueblo étnico</label>
    <div class="control">
      <select id="o95PuebloEtnicoSel" name="<?= $campoPuebloEtnicoO95['name'] ?>" data-nosearch="true" data-valor-actual="<?= e($campoPuebloEtnicoO95['val']) ?>">
        <option value="">Seleccionar…</option>
      </select>
    </div>
  </div>

  <!-- Idioma, Nivel educativo, Estado civil, Ocupación, Tipo de seguro (O95 - Anexo 2) -->
  <div class="field o95-anexo-2-elem" id="campoIdiomaO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Idioma</label>
    <div class="control">
      <select id="o95IdiomaSel" name="<?= $campoIdiomaO95['name'] ?>" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="ESPANOL" <?= seleccionado($campoIdiomaO95['val'], 'ESPANOL') ?>>Español</option>
        <option value="QUECHUA" <?= seleccionado($campoIdiomaO95['val'], 'QUECHUA') ?>>Quechua</option>
        <option value="AYMARA" <?= seleccionado($campoIdiomaO95['val'], 'AYMARA') ?>>Aymara</option>
        <option value="OTRA" <?= seleccionado($campoIdiomaO95['val'], 'OTRA') ?>>Otra</option>
      </select>
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoIdiomaOtraO95" <?= (!$mostrarEtniaO95Anexo2 || $campoIdiomaO95['val'] !== 'OTRA') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Especificar otro idioma</label>
    <div class="control">
      <input type="text" name="<?= $campoIdiomaOtraO95['name'] ?>" value="<?= e($campoIdiomaOtraO95['val']) ?>" placeholder="Especificar idioma…">
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoNivelEduO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Nivel educativo</label>
    <div class="control">
      <select name="<?= $campoNivelEduO95['name'] ?>" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="NINGUNO" <?= seleccionado($campoNivelEduO95['val'], 'NINGUNO') ?>>Ninguno</option>
        <option value="PRIMARIA_INCOMPLETA" <?= seleccionado($campoNivelEduO95['val'], 'PRIMARIA_INCOMPLETA') ?>>Primaria incompleta</option>
        <option value="PRIMARIA_COMPLETA" <?= seleccionado($campoNivelEduO95['val'], 'PRIMARIA_COMPLETA') ?>>Primaria completa</option>
        <option value="SECUNDARIA_INCOMPLETA" <?= seleccionado($campoNivelEduO95['val'], 'SECUNDARIA_INCOMPLETA') ?>>Secundaria incompleta</option>
        <option value="SECUNDARIA_COMPLETA" <?= seleccionado($campoNivelEduO95['val'], 'SECUNDARIA_COMPLETA') ?>>Secundaria completa</option>
        <option value="SUPERIOR_UNIVERSITARIA" <?= seleccionado($campoNivelEduO95['val'], 'SUPERIOR_UNIVERSITARIA') ?>>Superior universitaria</option>
        <option value="SUPERIOR_TECNICA" <?= seleccionado($campoNivelEduO95['val'], 'SUPERIOR_TECNICA') ?>>Superior técnica</option>
        <option value="DESCONOCIDO" <?= seleccionado($campoNivelEduO95['val'], 'DESCONOCIDO') ?>>Desconocido</option>
      </select>
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoEstadoCivilO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Estado civil</label>
    <div class="control">
      <select name="<?= $campoEstadoCivilO95['name'] ?>" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="SOLTERA" <?= seleccionado($campoEstadoCivilO95['val'], 'SOLTERA') ?>>Soltera</option>
        <option value="CASADA" <?= seleccionado($campoEstadoCivilO95['val'], 'CASADA') ?>>Casada</option>
        <option value="CONVIVIENTE" <?= seleccionado($campoEstadoCivilO95['val'], 'CONVIVIENTE') ?>>Conviviente</option>
        <option value="DIVORCIADA" <?= seleccionado($campoEstadoCivilO95['val'], 'DIVORCIADA') ?>>Divorciada</option>
        <option value="SEPARADA" <?= seleccionado($campoEstadoCivilO95['val'], 'SEPARADA') ?>>Separada</option>
        <option value="VIUDA" <?= seleccionado($campoEstadoCivilO95['val'], 'VIUDA') ?>>Viuda</option>
        <option value="DESCONOCIDO" <?= seleccionado($campoEstadoCivilO95['val'], 'DESCONOCIDO') ?>>Desconocido</option>
      </select>
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoOcupacionO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Ocupación</label>
    <div class="control">
      <input type="text" name="<?= $campoOcupacionO95['name'] ?>" value="<?= e($campoOcupacionO95['val']) ?>" placeholder="Especificar ocupación…">
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoTipoSeguroO95" <?= !$mostrarEtniaO95Anexo2 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Tipo de seguro</label>
    <div class="control">
      <select id="o95TipoSeguroSel" name="<?= $campoTipoSeguroO95['name'] ?>" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="SIS" <?= seleccionado($campoTipoSeguroO95['val'], 'SIS') ?>>SIS</option>
        <option value="ESSALUD" <?= seleccionado($campoTipoSeguroO95['val'], 'ESSALUD') ?>>EsSalud</option>
        <option value="PRIVADO" <?= seleccionado($campoTipoSeguroO95['val'], 'PRIVADO') ?>>Privado</option>
        <option value="OTROS" <?= seleccionado($campoTipoSeguroO95['val'], 'OTROS') ?>>Otros</option>
        <option value="NO_TIENE" <?= seleccionado($campoTipoSeguroO95['val'], 'NO_TIENE') ?>>No tiene seguro</option>
      </select>
    </div>
  </div>

  <div class="field o95-anexo-2-elem" id="campoSeguroOtroO95" <?= (!$mostrarEtniaO95Anexo2 || $campoTipoSeguroO95['val'] !== 'OTROS') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Especificar otro seguro</label>
    <div class="control">
      <input type="text" name="<?= $campoSeguroOtroO95['name'] ?>" value="<?= e($campoSeguroOtroO95['val']) ?>" placeholder="Especificar tipo de seguro…">
    </div>
  </div>

  <!-- Etnia / raza (Enfermedades Generales) -->
  <div class="field o95-hide" id="campoEtniaRazaGral" <?= $esO95 ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Etnia / raza</label>
    <div class="control">
      <select id="etniaSel" name="etnia" data-nosearch="true">
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

  <div class="field" id="campoEtniaOtraWrap" hidden style="display:none;">
    <label class="fl">Especificar otra etnia</label>
    <div class="control">
      <input type="text" id="etniaOtraInput" name="etnia_otra" value="<?= e($valoresFijos['etnia_otra'] ?? '') ?>" placeholder="Especificar etnia / raza…" <?= (($valoresFijos['etnia'] ?? '') === 'OTRO') ? '' : 'disabled' ?>>
    </div>
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

<!-- 3b. Nombre de Madre/Responsable/Tutor y Celular del tutor (En la siguiente fila después de Etnia) -->
<div class="fields halves o95-hide" style="margin-top:14px" <?= $esO95 ? 'hidden style="display:none;"' : '' ?>>
  <div class="field">
    <label class="fl">Nombre de la madre / tutor / responsable</label>
    <div class="control">
      <input type="text" name="nombre_tutor" value="<?= e($valoresFijos['nombre_tutor'] ?? '') ?>" placeholder="Nombre completo de la madre, tutor o responsable…">
    </div>
  </div>
  <div class="field">
    <label class="fl">N.° Celular del tutor / responsable</label>
    <div class="control mono">
      <input type="text" name="celular_tutor" value="<?= e($valoresFijos['celular_tutor'] ?? '') ?>" placeholder="N.° celular del tutor o contacto…" maxlength="20">
    </div>
  </div>
</div>

<!-- 4. ¿Gestante? + Semanas de gestación (General) / Trimestre de gestación (Solo B26) -->
<?php if (!in_array(strtoupper(trim($enfermedad['cie10'] ?? '')), ['A80', 'O95'], true)): ?>
<?php $esB26Gest = (($enfermedad['cie10'] ?? '') === 'B26'); ?>
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
  <div class="field" id="campoSemanasGestacion" <?= $esB26Gest ? 'hidden style="display:none;"' : 'hidden' ?>>
    <label class="fl">Semanas de gestación</label>
    <div class="control mono"><input type="number" min="0" max="45" id="semanasGestacion" name="semanas_gestacion" value="<?= e($valoresFijos['semanas_gestacion'] ?? '') ?>"></div>
  </div>
  <div class="field" id="campoTrimestreGestacion" <?= (!$esB26Gest) ? 'hidden style="display:none;"' : 'hidden' ?>>
    <label class="fl">Trimestre de gestación</label>
    <div class="control">
      <select id="trimestreGestacionSel" name="trimestre_gestacion" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="I" <?= seleccionado($valoresFijos['trimestre_gestacion'] ?? '', 'I') ?>>I Trimestre</option>
        <option value="II" <?= seleccionado($valoresFijos['trimestre_gestacion'] ?? '', 'II') ?>>II Trimestre</option>
        <option value="III" <?= seleccionado($valoresFijos['trimestre_gestacion'] ?? '', 'III') ?>>III Trimestre</option>
      </select>
    </div>
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
