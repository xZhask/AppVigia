<?php
/**
 * Campos núcleo de "Datos del paciente" con integración perfecta de B05 y O95.
 */
$esB05 = (($enfermedad['cie10'] ?? null) === 'B05');

// nucleo_omitidos (Petición 2, sesión "núcleo declarativo"): reemplaza las
// condiciones por CIE-10 sueltas que este partial tenía para decidir qué
// campos del núcleo compartido (columnas fijas de persona/caso, no
// campo_def) pide cada ficha. Declarado en el manifiesto por ficha,
// persistido en enfermedad.nucleo_omitidos por cargar_fichas.php. Default
// "se muestran todos" si la ficha no declara nada.
$nucleoOmitidos = [];
if (!empty($enfermedad['nucleo_omitidos'])) {
    $decodificado = json_decode($enfermedad['nucleo_omitidos'], true);
    $nucleoOmitidos = is_array($decodificado) ? $decodificado : [];
}
$nucleoOmite = fn(string $campoNucleo): bool => in_array($campoNucleo, $nucleoOmitidos, true);

// unidades_edad (entrada F, PETICION_MAPEO_Y_EDAD.md Parte 2): al revés que
// nucleo_omitidos, es opt-in -- el bloque de "Edad" con unidad solo aparece
// si la ficha lo declara. No sustituye a Fecha de nacimiento (fuera de este
// partial, en el shell): el PDF pide los dos como ítems separados.
$unidadesEdad = [];
if (!empty($enfermedad['unidades_edad'])) {
    $decodificadoUnidadesEdad = json_decode($enfermedad['unidades_edad'], true);
    $unidadesEdad = is_array($decodificadoUnidadesEdad) ? $decodificadoUnidadesEdad : [];
}
$etiquetasUnidadEdad = ['ANIOS' => 'Años', 'MESES' => 'Meses', 'DIAS' => 'Días', 'HORAS' => 'Horas', 'MINUTOS' => 'Minutos'];

// detalle_domicilio (Entrada J acotada al bloque de domicilio,
// PETICION_MAPEO_Y_EDAD.md): mismo criterio que unidades_edad -- opt-in,
// cada campo aparece solo si la ficha lo declara. Detalle de dirección
// dentro del distrito ya resuelto por distrito_id (selector-ubigeo.php);
// no reemplaza a "Domicilio actual" (direccion), que sigue existiendo para
// las fichas que piden una dirección única sin desglose (Y59.0, B05).
$detalleDomicilio = [];
if (!empty($enfermedad['detalle_domicilio'])) {
    $decodificadoDetalleDomicilio = json_decode($enfermedad['detalle_domicilio'], true);
    $detalleDomicilio = is_array($decodificadoDetalleDomicilio) ? $decodificadoDetalleDomicilio : [];
}
$tieneDetalleDomicilio = fn(string $campo): bool => in_array($campo, $detalleDomicilio, true);

require __DIR__ . '/datos-paciente-b05-loader.php';
?>
<?php if (!empty($unidadesEdad)): ?>
<!-- 0. Edad con unidad (solo fichas que lo declaran vía unidades_edad) -->
<div class="fields thirds" data-edad-unidad-bloque style="margin-top:14px">
  <div class="field">
    <label class="fl">Edad</label>
    <div class="control mono"><input type="number" name="edad_valor" min="0" step="1" value="<?= e($valoresFijos['edad_valor'] ?? '') ?>"></div>
  </div>
  <div class="field">
    <label class="fl">Unidad</label>
    <div class="control">
      <select name="edad_unidad" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <?php foreach ($unidadesEdad as $unidad): ?>
          <option value="<?= e($unidad) ?>" <?= seleccionado($valoresFijos['edad_unidad'] ?? '', $unidad) ?>><?= e($etiquetasUnidadEdad[$unidad] ?? $unidad) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>
<?php endif; ?>
<!-- 1. Celular, Nacionalidad, Localidad -->
<div class="fields thirds" style="margin-top:14px">
  <div class="field" data-nucleo-campo="celular" <?= $nucleoOmite('celular') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">N.° de celular</label>
    <div class="control mono"><input type="text" name="celular" value="<?= e($valoresFijos['celular'] ?? '') ?>" maxlength="20"></div>
  </div>
  <div class="field" data-nucleo-campo="nacionalidad" <?= $nucleoOmite('nacionalidad') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Nacionalidad</label>
    <div class="control"><input type="text" name="nacionalidad" value="<?= e($valoresFijos['nacionalidad'] ?? '') ?>"></div>
  </div>
  <div class="field" data-nucleo-campo="localidad" <?= $nucleoOmite('localidad') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Localidad</label>
    <div class="control"><input type="text" name="localidad" value="<?= e($valoresFijos['localidad'] ?? '') ?>"></div>
  </div>
  <div class="field wide" data-nucleo-campo="direccion" <?= $nucleoOmite('direccion') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Domicilio actual</label>
    <div class="control"><input type="text" name="direccion" value="<?= e($valoresFijos['direccion'] ?? '') ?>"></div>
  </div>
  <div class="field wide" data-nucleo-campo="referencia_localizar" <?= $nucleoOmite('referencia_localizar') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Referencia para localizar <span class="hint">(a la altura de o cerca de: Iglesia, fundo, comercio, etc.)</span></label>
    <div class="control"><input type="text" name="referencia_localizar" value="<?= e($valoresFijos['referencia_localizar'] ?? '') ?>" placeholder="Referencia para localizar…"></div>
  </div>
</div>

<!-- 1b. Detalle de domicilio (Entrada J acotada, opt-in vía detalle_domicilio).
     Igual que nucleo_omitidos, cada campo se renderiza siempre y se
     oculta/muestra con "hidden" (no con un if de PHP que lo omita del
     DOM) para que el fetch de cambio de ficha (más abajo, datos.detalleDomicilio)
     pueda mostrarlo sin necesitar que ya existiera en el HTML inicial. -->
<div class="fields thirds" style="margin-top:14px" data-detalle-domicilio-bloque>
  <div class="field" data-detalle-domicilio-campo="TIPO_ZONA" <?= $tieneDetalleDomicilio('TIPO_ZONA') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Tipo de zona</label>
    <div class="control">
      <select name="tipo_zona" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="URBANO" <?= seleccionado($valoresFijos['tipo_zona'] ?? '', 'URBANO') ?>>Urbano</option>
        <option value="PERIURBANO" <?= seleccionado($valoresFijos['tipo_zona'] ?? '', 'PERIURBANO') ?>>Periurbano</option>
        <option value="RURAL" <?= seleccionado($valoresFijos['tipo_zona'] ?? '', 'RURAL') ?>>Rural</option>
      </select>
    </div>
  </div>
  <div class="field" data-detalle-domicilio-campo="TIPO_VIA" <?= $tieneDetalleDomicilio('TIPO_VIA') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Tipo de vía <span class="hint">(Avenida, Calle, Jirón, etc.)</span></label>
    <div class="control"><input type="text" name="tipo_via" value="<?= e($valoresFijos['tipo_via'] ?? '') ?>"></div>
  </div>
  <div class="field" data-detalle-domicilio-campo="NOMBRE_VIA" <?= $tieneDetalleDomicilio('NOMBRE_VIA') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Nombre de vía</label>
    <div class="control"><input type="text" name="nombre_via" value="<?= e($valoresFijos['nombre_via'] ?? '') ?>"></div>
  </div>
  <div class="field" data-detalle-domicilio-campo="NUMERO" <?= $tieneDetalleDomicilio('NUMERO') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Nro.</label>
    <div class="control"><input type="text" name="numero" value="<?= e($valoresFijos['numero'] ?? '') ?>"></div>
  </div>
  <div class="field" data-detalle-domicilio-campo="MZ_LOTE" <?= $tieneDetalleDomicilio('MZ_LOTE') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Mz./Lote</label>
    <div class="control"><input type="text" name="mz_lote" value="<?= e($valoresFijos['mz_lote'] ?? '') ?>"></div>
  </div>
  <div class="field" data-detalle-domicilio-campo="TIEMPO_RESIDENCIA" <?= $tieneDetalleDomicilio('TIEMPO_RESIDENCIA') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Tiempo de residencia</label>
    <div class="control"><input type="text" name="tiempo_residencia" value="<?= e($valoresFijos['tiempo_residencia'] ?? '') ?>"></div>
  </div>
</div>

<!-- 2. Debajo de Domicilio actual / Referencia para localizar: Tipo de localidad (B05) -->
<div class="b05-field-wrap" <?= $esB05 ? '' : 'hidden' ?> style="margin-top:14px">
  <?php if ($b05['tipoLocalidad']['name']): ?>
  <div class="fields thirds">
    <div class="field">
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
  </div>
  <?php endif; ?>
</div>

<!-- 3. Etnia / raza + Pueblo étnico o etnia + Ocupación (¡AL LADO DE ETNIA / RAZA EN LA MISMA FILA!) -->
<div class="fields thirds" style="margin-top:14px">
  <?php
  $cie10Actual = strtoupper(trim($enfermedad['cie10'] ?? ''));
  $esO95 = ($cie10Actual === 'O95');
  // Este bloque se renderiza sin condición aunque O95 no sea la ficha
  // activa (mismo motivo que notificacion-fechas-b26.php, ver ese
  // archivo): $campoO95Nucleo resuelve siempre contra la O95 real, no
  // contra $enfermedad. Nombre propio (no shadow de $campo global): este
  // archivo es compartido por las 24 fichas, no exclusivo de O95.
  $campoO95Nucleo = $resolvedorPara('O95');
  $campoGrupoEtnicoO95 = $campoO95Nucleo('o95_grupo_etnico');
  $campoPuebloEtnicoO95 = $campoO95Nucleo('o95_etnia_pueblo_etnico');
  $campoIdiomaO95 = $campoO95Nucleo('o95_idioma');
  $campoIdiomaOtraO95 = $campoO95Nucleo('o95_idioma_otra');
  $campoNivelEduO95 = $campoO95Nucleo('o95_nivel_educativo');
  $campoEstadoCivilO95 = $campoO95Nucleo('o95_estado_civil');
  $campoOcupacionO95 = $campoO95Nucleo('o95_ocupacion');
  $campoTipoSeguroO95 = $campoO95Nucleo('o95_tipo_de_seguro');
  $campoSeguroOtroO95 = $campoO95Nucleo('o95_tipo_de_seguro_otro');
  $campoTipoFichaO95Nucleo = $campoO95Nucleo('o95_tipo_de_ficha');
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
  <div class="field" id="campoEtniaRazaGral" data-nucleo-campo="etnia" <?= $nucleoOmite('etnia') ? 'hidden style="display:none;"' : '' ?>>
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

  <div class="field" data-nucleo-campo="pueblo_etnico" <?= $nucleoOmite('pueblo_etnico') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Pueblo étnico o etnia</label>
    <div class="control">
      <!-- Cascada de Etnia / raza (núcleo, #etniaSel) -> Pueblo étnico, mismo
           mecanismo y mismo mapa que O95 (ver MAPA_GRUPO_ETNICO en ficha.js).
           Arranca vacío a propósito: se repuebla en JS según #etniaSel. -->
      <select id="b05PuebloEtnicoSel" name="pueblo_etnico" data-nosearch="true" data-valor-actual="<?= e($valoresFijos['pueblo_etnico'] ?? '') ?>">
        <option value="">Seleccionar…</option>
      </select>
    </div>
  </div>

  <div class="field" data-nucleo-campo="ocupacion" <?= $nucleoOmite('ocupacion') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Ocupación</label>
    <div class="control"><input type="text" name="ocupacion" value="<?= e($valoresFijos['ocupacion'] ?? '') ?>" placeholder="Ocupación…"></div>
  </div>
</div>

<!-- 3b. Nombre de Madre/Responsable/Tutor y Celular del tutor (En la siguiente fila después de Etnia) -->
<div class="fields halves" style="margin-top:14px">
  <div class="field" data-nucleo-campo="nombre_tutor" <?= $nucleoOmite('nombre_tutor') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Nombre de la madre / tutor / responsable</label>
    <div class="control">
      <input type="text" name="nombre_tutor" value="<?= e($valoresFijos['nombre_tutor'] ?? '') ?>" placeholder="Nombre completo de la madre, tutor o responsable…">
    </div>
  </div>
  <div class="field" data-nucleo-campo="celular_tutor" <?= $nucleoOmite('celular_tutor') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">N.° Celular del tutor / responsable</label>
    <div class="control mono">
      <input type="text" name="celular_tutor" value="<?= e($valoresFijos['celular_tutor'] ?? '') ?>" placeholder="N.° celular del tutor o contacto…" maxlength="20">
    </div>
  </div>
</div>

<!-- 4. ¿Gestante? + Semanas de gestación (General) / Trimestre de gestación (B26/B01) -->
<?php
// B26 (cotejo 2026-07-30) y B01 (cotejo 2026-08-08, ítem 20 del PDF) piden
// "Trimestre de gestación" (I/II/III) en vez de "Semanas de gestación"
// para la gestante propia del caso.
$esTrimestreGestacion = in_array($enfermedad['cie10'] ?? '', ['B26', 'B01'], true);
?>
<div class="fields thirds" data-nucleo-campo="gestante" style="margin-top:14px" <?= $nucleoOmite('gestante') ? 'hidden style="display:none;"' : '' ?>>
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
  <div class="field" id="campoSemanasGestacion" <?= $esTrimestreGestacion ? 'hidden style="display:none;"' : 'hidden' ?>>
    <label class="fl">Semanas de gestación</label>
    <div class="control mono"><input type="number" min="0" max="45" id="semanasGestacion" name="semanas_gestacion" value="<?= e($valoresFijos['semanas_gestacion'] ?? '') ?>"></div>
  </div>
  <div class="field" id="campoTrimestreGestacion" <?= (!$esTrimestreGestacion) ? 'hidden style="display:none;"' : 'hidden' ?>>
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
