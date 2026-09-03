<?php
/**
 * Fila dinámica de muestras de laboratorio del caso (caso_muestra). Variables
 * esperadas: $filasMuestras (array de ['tipo_muestra','tipo_prueba',
 * 'recibio_antibiotico','resultado','fecha_toma','fecha_result']; opcionales
 * por columna declarada: 'fecha_envio_eess_red','fecha_envio_red_lrr',
 * 'fecha_envio_lrr_ins' -- cadena EE.SS -> Red/Microred -> LRR -> INS de B01,
 * 2026-08-09, distinta de la 'fecha_envio_ins' genérica que ya usan otras
 * fichas),
 * $opcionesTipoMuestra, $opcionesTipoPrueba, $opcionesResultado (catalogo_item),
 * $opcionesMuestraExtra (overrides declarativos por columna: tipo_muestra,
 * tipo_prueba, resultado_igm, resultado_igg, resultado_pcr, genotipo),
 * $textoLibreMuestra (columnas que se pintan como <input> en vez de <select>),
 * $dependeDeColumnaMuestra (visibilidad de una columna condicionada al valor
 * de otra columna de la misma fila -- capacidad 5, reemplaza al toggle
 * hardcodeado de B05).
 */
$erroresMuestras = $erroresMuestras ?? [];
$columnasMuestra = $columnasMuestra ?? ['tipo_muestra', 'tipo_prueba', 'resultado', 'fecha_toma', 'fecha_result'];
$muestra = fn(string $col) => in_array($col, $columnasMuestra, true);
$esPfa = isset($enfermedad['cie10']) && $enfermedad['cie10'] === 'A80';
$cie10Actual = $enfermedad['cie10'] ?? '';
$esB05 = ($cie10Actual === 'B05');
// "Fecha de obtención" (en vez de "Fecha de toma"): A80, P35.0 y B01 usan
// esa palabra en el PDF para la misma columna fecha_toma (B01, 2026-08-09:
// "Fecha de obtención de 1 muestra"). "Fecha resultado Fiocruz" ($esPfa más
// abajo) sigue siendo específico de A80 -- el PDF de P35.0 no menciona
// Fiocruz.
// B04X (cotejo 2026-09-03, ítem 57 del PDF): "Fecha de obtención de muestra".
$esFechaObtencion = in_array($cie10Actual, ['A80', 'P35.0', 'B01', 'B04X'], true);
// "Fecha emisión resultado INS" (en vez de "Fecha de resultado"): B01
// (2026-08-09) es la primera en pedir esta etiqueta exacta para fecha_result
// -- el PDF (pág. 3, secc. VI) la conecta con la cadena EE.SS -> Red/Microred
// -> LRR -> INS de abajo, no es un resultado local genérico.
$esFechaEmisionResultadoIns = ($cie10Actual === 'B01');
// A95 (2026-08-23, VII. Muestra del PDF): "Fecha de envío" a secas, sin "a
// INS" -- el PDF no menciona INS en absoluto (a diferencia de B05/A80, que
// sí lo hacen explícito).
$esA95 = ($cie10Actual === 'A95');

// opcionesMuestraExtra/textoLibreMuestra: overrides declarativos por ficha
// sobre resultado_igm/resultado_igg/resultado_pcr/genotipo/titulacion
// (PETICION_HC_Y_LABORATORIO.md, Parte 2, Fase D1 "bloque declarativo").
// Ausente = vocabulario completo de siempre, mismo comportamiento que antes
// de esta entrada -- ninguna ficha existente declara overrides todavía.
$opcionesMuestraExtra = $opcionesMuestraExtra ?? [];
$textoLibreMuestra = $textoLibreMuestra ?? [];
$dependeDeColumnaMuestra = $dependeDeColumnaMuestra ?? [];
// unicoPorTipoMuestra (2026-08-23, A95): opt-in -- el <select> de tipo de
// muestra no deja elegir un tipo ya usado por otra fila del mismo caso
// (filas-dinamicas.js, data-unico-por-selector en .subrows más abajo). El
// servidor revalida (CasosController::filasMuestras()), esto es solo UX.
$unicoPorTipoMuestra = $unicoPorTipoMuestra ?? false;

// attrsDependencia(): data-depende-columna/data-valores-activadores para que
// el JS genérico (ficha.js) sepa qué disparador vigilar dentro de la fila.
// Ausente = el campo siempre se ve, mismo comportamiento que antes de esta
// entrada para las 12 fichas que no declaran ninguna regla.
$attrsDependencia = function (string $col) use ($dependeDeColumnaMuestra): string {
    $regla = $dependeDeColumnaMuestra[$col] ?? null;
    if (!$regla) {
        return '';
    }
    return ' data-depende-columna="' . e($regla['columna']) . '" data-valores-activadores="' . e(implode(',', $regla['valores_activadores'])) . '"';
};

$opcionesResultadoSeroDefecto = [
    'PENDIENTE' => 'Pendiente',
    'POSITIVO' => 'Positivo',
    'NEGATIVO' => 'Negativo',
    'INDETERMINADO' => 'Indeterminado',
];
$opcionesSeroPara = function (string $col) use ($opcionesResultadoSeroDefecto, $opcionesMuestraExtra): array {
    if (empty($opcionesMuestraExtra[$col])) {
        return $opcionesResultadoSeroDefecto;
    }
    return array_intersect_key($opcionesResultadoSeroDefecto, array_flip($opcionesMuestraExtra[$col]));
};

$opcionesGenotipoDefecto = ['D8', 'B3', 'H1', 'D4', 'D9', 'G3', 'A', 'B1', 'C1', 'D1', 'D5', 'D6', 'D7', 'D10', 'D11', 'G1', 'G2', 'H2'];
$genotipoLibre = in_array('genotipo', $textoLibreMuestra, true);
// tipo_prueba texto libre (A95, 2026-08-23): el PDF ("PRUEBA REALIZADA") no
// imprime ningún vocabulario cerrado para esta columna, a diferencia de
// otras fichas con PCR/ELISA estructurados -- mismo mecanismo opt-in que ya
// usa genotipo.
$tipoPruebaLibre = in_array('tipo_prueba', $textoLibreMuestra, true);
$opcionesGenotipo = !empty($opcionesMuestraExtra['genotipo'])
    ? array_values(array_intersect($opcionesGenotipoDefecto, $opcionesMuestraExtra['genotipo']))
    : $opcionesGenotipoDefecto;

// "Agente identificado"/"agente aislado" es TEXTO libre para las demás
// fichas que lo usan (A80: nombre de cepa, sin vocabulario cerrado) -- a
// diferencia de genotipo (select por defecto, texto libre opt-out), acá es
// al revés: solo se vuelve <select> cuando la ficha declara
// opciones.agente_aislado (opt-in), sin tocar el comportamiento de A80.
// A37.0 (VIII. Laboratorio, ítem 64) es la primera en declararlo, con las 4
// especies de Bordetella del PDF -- no hay catálogo compartido de
// "agentes" entre fichas, así que el default vive acá igual que genotipo.
$opcionesAgenteDefecto = [
    'B_PERTUSSIS'      => 'B. pertussis',
    'B_PARAPERTUSSIS'  => 'B. parapertussis',
    'B_HOLMESII'       => 'B. holmesii',
    'BORDETELLA_SP'    => 'Bordetella sp.',
];
$opcionesAgente = !empty($opcionesMuestraExtra['agente_aislado'])
    ? array_intersect_key($opcionesAgenteDefecto, array_flip($opcionesMuestraExtra['agente_aislado']))
    : [];

$filaMuestra = function (array $fila = ['tipo_muestra' => '', 'tipo_prueba' => '', 'recibio_antibiotico' => '', 'resultado' => '', 'fecha_toma' => '', 'fecha_envio_eess_red' => '', 'fecha_envio_red_lrr' => '', 'fecha_envio_lrr_ins' => '', 'fecha_envio_ins' => '', 'fecha_result' => '', 'agente_aislado' => '', 'observaciones' => ''], ?array $error = null) use ($opcionesTipoMuestra, $opcionesTipoPrueba, $opcionesResultado, $muestra, $esPfa, $esFechaObtencion, $esFechaEmisionResultadoIns, $esA95, $esB05, $cie10Actual, $opcionesSeroPara, $opcionesGenotipo, $genotipoLibre, $tipoPruebaLibre, $opcionesAgente, $dependeDeColumnaMuestra, $attrsDependencia): void {
    $errorTipoMuestra = $error['tipo_muestra'] ?? null;
    $errorToma = $error['fecha_toma'] ?? null;
    $errorEnvioEessRed = $error['fecha_envio_eess_red'] ?? null;
    $errorEnvioRedLrr = $error['fecha_envio_red_lrr'] ?? null;
    $errorEnvioLrrIns = $error['fecha_envio_lrr_ins'] ?? null;
    $errorEnvio = $error['fecha_envio_ins'] ?? null;
    $errorResult = $error['fecha_result'] ?? null;

    $tipoActual = $fila['tipo_muestra'] ?? '';
    // visiblePorDependencia(): evalúa depende_de_columna para la columna dada
    // contra el valor YA GUARDADO de su disparador en esta misma fila (render
    // inicial en servidor -- el toggle en vivo lo hace ficha.js leyendo estos
    // mismos data-attrs una vez el usuario cambia el disparador).
    $visiblePorDependencia = function (string $col) use ($dependeDeColumnaMuestra, $fila): bool {
        $regla = $dependeDeColumnaMuestra[$col] ?? null;
        if (!$regla) {
            return true;
        }
        $valorDisparador = (string) ($fila[$regla['columna']] ?? '');
        return in_array($valorDisparador, $regla['valores_activadores'], true);
    };
    ?>
  <div class="subrow b05-subrow-wrapper" style="<?= $esB05 ? 'border-left: 3px solid var(--accent); padding-left: 12px; margin-bottom: 16px;' : '' ?>">
    <div class="fields thirds" style="flex:1">
      <?php if ($esB05): ?>
        <!-- ================= CAMPOS DE LABORATORIO B05 ================= -->
        <!-- 1. Datos de la muestra -->
        <div class="field">
          <label class="fl">Tipo de muestra</label>
          <div class="control">
            <select name="muestra_tipo_muestra[]">
              <option value="">Seleccionar…</option>
              <option value="SUERO" <?= seleccionado($tipoActual, 'SUERO') ?>>Suero</option>
              <option value="HNF_FAR" <?= seleccionado($tipoActual, 'HNF_FAR') ?>>Hisopado nasal y faríngeo</option>
              <option value="ORINA" <?= seleccionado($tipoActual, 'ORINA') ?>>Orina*</option>
            </select>
          </div>
        </div>

        <div class="field">
          <label class="fl">Fecha de toma</label>
          <div class="control mono"><input type="date" name="muestra_fecha_toma[]" value="<?= e($fila['fecha_toma'] ?? '') ?>" max="<?= date('Y-m-d') ?>"></div>
        </div>

        <div class="field">
          <label class="fl">Fecha de envío LRR/INS</label>
          <div class="control mono"><input type="date" name="muestra_fecha_envio_ins[]" value="<?= e($fila['fecha_envio_ins'] ?? '') ?>" max="<?= date('Y-m-d') ?>"></div>
        </div>

        <div class="field">
          <label class="fl">Fecha de recepción INS**</label>
          <div class="control mono"><input type="date" name="muestra_fecha_recepcion_ins[]" value="<?= e($fila['fecha_recepcion_ins'] ?? '') ?>" max="<?= date('Y-m-d') ?>"></div>
        </div>

      <?php else: ?>
        <!-- Formulario estándar para otras enfermedades -->
        <?php if ($muestra('tipo_muestra')): ?>
        <div class="field">
          <label class="fl">Tipo de muestra</label>
          <div class="control <?= $errorTipoMuestra ? 'err' : '' ?>">
            <select name="muestra_tipo_muestra[]">
              <option value="">Seleccionar…</option>
              <?php foreach ($opcionesTipoMuestra as $op): ?>
                <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['tipo_muestra'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($errorTipoMuestra): ?><span class="hint err"><?= e($errorTipoMuestra) ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($muestra('tipo_prueba')): ?>
        <div class="field">
          <label class="fl"><?= $esA95 ? 'Prueba realizada' : 'Tipo de prueba' ?></label>
          <div class="control">
            <?php if ($tipoPruebaLibre): ?>
              <input type="text" name="muestra_tipo_prueba[]" value="<?= e($fila['tipo_prueba'] ?? '') ?>" placeholder="Prueba realizada…">
            <?php else: ?>
              <select name="muestra_tipo_prueba[]">
                <option value="">Seleccionar…</option>
                <?php foreach ($opcionesTipoPrueba as $op): ?>
                  <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['tipo_prueba'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($muestra('resultado')): ?>
        <div class="field">
          <label class="fl">Resultado</label>
          <div class="control">
            <select name="muestra_resultado[]">
              <option value="">Pendiente</option>
              <?php foreach ($opcionesResultado as $op): ?>
                <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['resultado'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($muestra('fecha_toma')): ?>
        <div class="field">
          <label class="fl"><?= $esFechaObtencion ? 'Fecha de obtención' : 'Fecha de toma' ?></label>
          <div class="control mono <?= $errorToma ? 'err' : '' ?>"><input type="date" name="muestra_fecha_toma[]" value="<?= e($fila['fecha_toma'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
          <?php if ($errorToma): ?><span class="hint err"><?= e($errorToma) ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($muestra('fecha_envio_eess_red')): ?>
        <div class="field">
          <label class="fl">Fecha envío EE.SS, Red/Microred</label>
          <div class="control mono <?= $errorEnvioEessRed ? 'err' : '' ?>"><input type="date" name="muestra_fecha_envio_eess_red[]" value="<?= e($fila['fecha_envio_eess_red'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
          <?php if ($errorEnvioEessRed): ?><span class="hint err"><?= e($errorEnvioEessRed) ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($muestra('fecha_envio_red_lrr')): ?>
        <div class="field">
          <label class="fl">Fecha envío Red/Microred a LRR</label>
          <div class="control mono <?= $errorEnvioRedLrr ? 'err' : '' ?>"><input type="date" name="muestra_fecha_envio_red_lrr[]" value="<?= e($fila['fecha_envio_red_lrr'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
          <?php if ($errorEnvioRedLrr): ?><span class="hint err"><?= e($errorEnvioRedLrr) ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($muestra('fecha_envio_lrr_ins')): ?>
        <div class="field">
          <label class="fl">Fecha envío LRR a INS</label>
          <div class="control mono <?= $errorEnvioLrrIns ? 'err' : '' ?>"><input type="date" name="muestra_fecha_envio_lrr_ins[]" value="<?= e($fila['fecha_envio_lrr_ins'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
          <?php if ($errorEnvioLrrIns): ?><span class="hint err"><?= e($errorEnvioLrrIns) ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($muestra('fecha_envio_ins')): ?>
        <div class="field">
          <label class="fl"><?= $esA95 ? 'Fecha de envío' : 'Fecha de envío a INS' ?></label>
          <div class="control mono <?= $errorEnvio ? 'err' : '' ?>"><input type="date" name="muestra_fecha_envio_ins[]" value="<?= e($fila['fecha_envio_ins'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
          <?php if ($errorEnvio): ?><span class="hint err"><?= e($errorEnvio) ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($muestra('fecha_result')): ?>
        <div class="field">
          <label class="fl"><?= $esFechaEmisionResultadoIns ? 'Fecha emisión resultado INS' : ($esPfa ? 'Fecha resultado Fiocruz' : 'Fecha de resultado') ?></label>
          <div class="control mono <?= $errorResult ? 'err' : '' ?>"><input type="date" name="muestra_fecha_result[]" value="<?= e($fila['fecha_result'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
          <?php if ($errorResult): ?><span class="hint err"><?= e($errorResult) ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($muestra('agente_aislado')): ?>
        <div class="field"<?= $attrsDependencia('agente_aislado') ?> style="<?= !$visiblePorDependencia('agente_aislado') ? 'display:none;' : '' ?>">
          <label class="fl"><?= $cie10Actual === 'A37.0' ? 'Agente identificado' : 'Agente aislado' ?></label>
          <div class="control">
            <?php if (!empty($opcionesAgente)): ?>
              <select name="muestra_agente_aislado[]">
                <option value="">Seleccionar…</option>
                <?php foreach ($opcionesAgente as $valOpt => $lblOpt): ?>
                  <option value="<?= e($valOpt) ?>" <?= seleccionado($fila['agente_aislado'] ?? '', $valOpt) ?>><?= e($lblOpt) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="text" name="muestra_agente_aislado[]" value="<?= e($fila['agente_aislado'] ?? '') ?>" placeholder="Agente aislado…">
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($muestra('observaciones')): ?>
        <div class="field">
          <label class="fl">Observaciones</label>
          <div class="control"><input type="text" name="muestra_observaciones[]" value="<?= e($fila['observaciones'] ?? '') ?>" placeholder="Observaciones…"></div>
        </div>
        <?php endif; ?>
        <?php if ($muestra('recibio_antibiotico')): ?>
        <div class="field">
          <label class="fl">¿Recibió antibiótico?</label>
          <div class="control">
            <select name="muestra_recibio_antibiotico[]" data-nosearch="true">
              <option value="">Seleccionar…</option>
              <option value="1" <?= seleccionado($fila['recibio_antibiotico'] ?? '', '1') ?>>Sí</option>
              <option value="0" <?= seleccionado($fila['recibio_antibiotico'] ?? '', '0') ?>>No</option>
            </select>
          </div>
        </div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Serología (PENDIENTES.md ítem C): declarativas por
      columnas_tablas_hija.caso_muestra, ya no hardcodeadas a B05. La
      visibilidad condicional (antes $esSuero/$esPcrGen, hardcodeado) ahora
      sale de columnas_tablas_hija.caso_muestra.depende_de_columna (capacidad
      5) -- data-depende-columna/data-valores-activadores, evaluado en vivo
      por ficha.js contra el <select> de tipo_muestra de la misma fila. -->
      <?php if ($muestra('resultado_pcr')): ?>
        <div class="field"<?= $attrsDependencia('resultado_pcr') ?> style="<?= !$visiblePorDependencia('resultado_pcr') ? 'display:none;' : '' ?>">
          <label class="fl">Resultado PCR</label>
          <div class="control">
            <select name="muestra_resultado_pcr[]">
              <option value="">Seleccionar…</option>
              <?php foreach ($opcionesSeroPara('resultado_pcr') as $valOpt => $lblOpt): ?>
                <option value="<?= $valOpt ?>" <?= seleccionado($fila['resultado_pcr'] ?? '', $valOpt) ?>><?= $lblOpt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($muestra('fecha_result_pcr')): ?>
        <div class="field"<?= $attrsDependencia('fecha_result_pcr') ?> style="<?= !$visiblePorDependencia('fecha_result_pcr') ? 'display:none;' : '' ?>">
          <label class="fl">Fecha resultado PCR</label>
          <div class="control mono"><input type="date" name="muestra_fecha_result_pcr[]" value="<?= e($fila['fecha_result_pcr'] ?? '') ?>" max="<?= date('Y-m-d') ?>"></div>
        </div>
      <?php endif; ?>
      <?php if ($muestra('genotipo')): ?>
        <div class="field"<?= $attrsDependencia('genotipo') ?> style="<?= !$visiblePorDependencia('genotipo') ? 'display:none;' : '' ?>">
          <label class="fl">Genotipo</label>
          <div class="control">
            <?php if ($genotipoLibre): ?>
              <input type="text" name="muestra_genotipo[]" value="<?= e($fila['genotipo'] ?? '') ?>" placeholder="Genotipo…">
            <?php else: ?>
              <select name="muestra_genotipo[]">
                <option value="">Seleccionar…</option>
                <?php foreach ($opcionesGenotipo as $g): ?>
                  <option value="<?= $g ?>" <?= seleccionado($fila['genotipo'] ?? '', $g) ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($muestra('resultado_igm')): ?>
        <div class="field"<?= $attrsDependencia('resultado_igm') ?> style="<?= !$visiblePorDependencia('resultado_igm') ? 'display:none;' : '' ?>">
          <label class="fl">Resultado IgM</label>
          <div class="control">
            <select name="muestra_resultado_igm[]">
              <option value="">Seleccionar…</option>
              <?php foreach ($opcionesSeroPara('resultado_igm') as $valOpt => $lblOpt): ?>
                <option value="<?= $valOpt ?>" <?= seleccionado($fila['resultado_igm'] ?? '', $valOpt) ?>><?= $lblOpt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($muestra('fecha_result_igm')): ?>
        <div class="field"<?= $attrsDependencia('fecha_result_igm') ?> style="<?= !$visiblePorDependencia('fecha_result_igm') ? 'display:none;' : '' ?>">
          <label class="fl">Fecha resultado IgM</label>
          <div class="control mono"><input type="date" name="muestra_fecha_result_igm[]" value="<?= e($fila['fecha_result_igm'] ?? '') ?>" max="<?= date('Y-m-d') ?>"></div>
        </div>
      <?php endif; ?>
      <?php if ($muestra('resultado_igg')): ?>
        <div class="field"<?= $attrsDependencia('resultado_igg') ?> style="<?= !$visiblePorDependencia('resultado_igg') ? 'display:none;' : '' ?>">
          <label class="fl">Resultado IgG</label>
          <div class="control">
            <select name="muestra_resultado_igg[]">
              <option value="">Seleccionar…</option>
              <?php foreach ($opcionesSeroPara('resultado_igg') as $valOpt => $lblOpt): ?>
                <option value="<?= $valOpt ?>" <?= seleccionado($fila['resultado_igg'] ?? '', $valOpt) ?>><?= $lblOpt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($muestra('fecha_result_igg')): ?>
        <div class="field"<?= $attrsDependencia('fecha_result_igg') ?> style="<?= !$visiblePorDependencia('fecha_result_igg') ? 'display:none;' : '' ?>">
          <label class="fl">Fecha resultado IgG</label>
          <div class="control mono"><input type="date" name="muestra_fecha_result_igg[]" value="<?= e($fila['fecha_result_igg'] ?? '') ?>" max="<?= date('Y-m-d') ?>"></div>
        </div>
      <?php endif; ?>
      <?php if ($muestra('titulacion')): ?>
        <div class="field"<?= $attrsDependencia('titulacion') ?> style="<?= !$visiblePorDependencia('titulacion') ? 'display:none;' : '' ?>">
          <label class="fl">Titulación</label>
          <div class="control"><input type="text" name="muestra_titulacion[]" value="<?= e($fila['titulacion'] ?? '') ?>" placeholder="Titulación…"></div>
        </div>
      <?php endif; ?>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar muestra" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="subrows" data-lista="muestras"<?= $unicoPorTipoMuestra ? ' data-unico-por-selector="select[name=\'muestra_tipo_muestra[]\']"' : '' ?>>
  <?php foreach ($filasMuestras as $i => $fila): $filaMuestra($fila, $erroresMuestras[$i] ?? null); endforeach; ?>
</div>
<template id="plantilla-muestras"><?php $filaMuestra(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-muestras" data-lista="muestras" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar muestra
</button>
