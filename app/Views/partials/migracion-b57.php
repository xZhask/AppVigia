<?php
/**
 * Tarjeta "Migración" (cotejo B57, sección "IV. Migración" del PDF, pág.
 * 40): años/meses que reside en el domicilio actual + domicilio anterior
 * condicional + listado de localidades visitadas en los últimos 10 días.
 * Opt-in vía enfermedad.migracion_reciente -- el llamador (nueva/index.php,
 * fichas/editar.php) ya envuelve este require en esa condición y decide el
 * número de tarjeta.
 *
 * Extraído de datos-paciente-nucleo.php (2026-08-21, pedido del usuario:
 * Migración no es parte de "Datos del persona", va en su propia tarjeta
 * entre esa y "Antecedentes epidemiológicos") -- requiere las mismas
 * variables que ya están en scope ahí: $enfermedad, $valoresFijos,
 * $detalleDomicilio, $resolvedorPara.
 *
 * El bloque "Domicilio anterior" se muestra/oculta con JS dedicado
 * (ficha.js, actualizarDomicilioAnterior()) según años×12+meses < 6 --
 * revalidado también del lado servidor (CasosController::sanearCamposNucleo()).
 * El bloque de viajes usa el motor genérico .dep-wrap (mismo mecanismo que
 * B05/P35.0/A37.0 en secciones-clinicas.php para su propio toggle de
 * viajes), así que evaluarDependencias() ya lo muestra/oculta sin JS
 * dedicado. El toggle "¿Viajó los últimos 6 meses?" es un campo_def real
 * (b57_viajo_ultimos_6_meses, sección "Antecedentes epidemiológicos" del
 * manifiesto) pintado acá a medida -- excluido de su tarjeta lógica vía
 * $CLAVES_CUBIERTAS_POR_PARTIAL_A_MEDIDA (secciones-clinicas.php).
 */
?>
<div class="fields thirds" data-migracion-bloque>
  <div class="field">
    <label class="fl">Tiempo que reside en domicilio actual (años)</label>
    <div class="control mono"><input type="number" min="0" step="1" id="tiempoResideAnios" name="tiempo_reside_anios" value="<?= e($valoresFijos['tiempo_reside_anios'] ?? '') ?>"></div>
  </div>
  <div class="field">
    <label class="fl">Tiempo que reside en domicilio actual (meses)</label>
    <div class="control mono"><input type="number" min="0" max="11" step="1" id="tiempoResideMeses" name="tiempo_reside_meses" value="<?= e($valoresFijos['tiempo_reside_meses'] ?? '') ?>"></div>
  </div>
</div>

<div id="wrapDomicilioAnterior" data-domicilio-anterior-bloque style="margin-top:14px" hidden>
  <div class="eyebrow" style="margin-bottom:10px">Domicilio anterior <span class="hint">(reside menos de 6 meses en el domicilio actual)</span></div>
  <?php
  (function (array $valoresFijos): void {
      $prefijo = 'domicilio-anterior-ubigeo';
      $nombreCampoDepartamento = 'anterior_departamento_id';
      $nombreCampoProvincia = 'anterior_provincia_id';
      $nombreCampoDistrito = 'anterior_distrito_id';
      $distritoRequerido = false;
      $errorDistrito = null;
      extract(contextoUbigeo($valoresFijos['anterior_distrito_id'] ?? null));
      require __DIR__ . '/selector-ubigeo.php';
  })($valoresFijos);
  ?>
  <?php
  $valoresDetalleAnterior = [
      'tipo_zona'   => $valoresFijos['anterior_tipo_zona'] ?? '',
      'nombre_zona' => $valoresFijos['anterior_nombre_zona'] ?? '',
      'tipo_via'    => $valoresFijos['anterior_tipo_via'] ?? '',
      'nombre_via'  => $valoresFijos['anterior_nombre_via'] ?? '',
      'numero'      => $valoresFijos['anterior_numero'] ?? '',
      'mz_lote'     => $valoresFijos['anterior_mz_lote'] ?? '',
  ];
  (function (string $prefijoNombreCampo, array $valoresDetalle, array $detalleDomicilioPermitido) {
      require __DIR__ . '/detalle-domicilio-campos.php';
  })('anterior_', $valoresDetalleAnterior, $detalleDomicilio);
  ?>
</div>

<div class="eyebrow" style="margin-top:14px;margin-bottom:10px">Listado de localidades que el paciente visitó en los últimos 10 días</div>
<?php
$campoB57Nucleo = $resolvedorPara('B57');
$campoViajo6MesesB57 = $campoB57Nucleo('b57_viajo_ultimos_6_meses');
?>
<div class="fields thirds">
  <div class="field">
    <label class="fl"><?= e($campoViajo6MesesB57['campo']['etiqueta']) ?></label>
    <div class="control">
      <select name="<?= e($campoViajo6MesesB57['name']) ?>" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <?php foreach ($campoViajo6MesesB57['opciones'] as $op): ?>
          <option value="<?= e($op['valor']) ?>" <?= seleccionado($campoViajo6MesesB57['val'], $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>
<div class="dep-wrap" data-depende-de="<?= e($campoViajo6MesesB57['name']) ?>" data-valor-activador="SI" style="margin-top:14px" <?= $campoViajo6MesesB57['val'] === 'SI' ? '' : 'hidden' ?>>
  <?php require __DIR__ . '/tablas-hijas/viajes.php'; ?>
</div>
