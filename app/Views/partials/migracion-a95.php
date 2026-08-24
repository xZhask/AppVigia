<?php
/**
 * Tarjeta "Migración" para A95 (Fiebre amarilla, cotejo 2026-08-22, sección
 * "III. MIGRACION" del PDF, pág. 26): años/meses que reside en el domicilio
 * actual + domicilio anterior condicional + "Listado de localidades..." +
 * "Hubo casos reportados/notificados en los últimos 10 días" (SI/NO/IGN por
 * ítem) + "Cuántas personas viven en su casa" + "¿Viajó los últimos 6
 * meses?" con su tabla caso_viaje condicional. Los últimos 3 vivían en una
 * sección aparte "Antecedentes epidemiológicos" que no corresponde a NINGÚN
 * encabezado del PDF -- 2.ª revisión (2026-08-22, el usuario señaló con una
 * captura que están dentro de "III. MIGRACION", justo antes de "IV. CUADRO
 * CLINICO") -- se movieron acá.
 *
 * Calcado de migracion-b57.php (mismo encabezado I-IV del PDF, cotejo
 * 2026-08-21) para años/meses + domicilio anterior. "Listado de
 * localidades..." y "¿Viajó los últimos 6 meses?" son 2 preguntas
 * independientes en el PDF de A95 (a diferencia de B57, que las funde en
 * una sola) -- por eso ambas se pintan acá con su propio campo_def, sin
 * reusar el mecanismo de B57.
 *
 * Opt-in vía enfermedad.migracion_reciente (mismo mecanismo que B57) -- el
 * llamador (nueva/index.php, fichas/editar.php) decide cuál de los 2
 * partials de migración requerir según el CIE10 activo. El bloque
 * "Domicilio anterior" se muestra/oculta con el mismo JS genérico de B57
 * (ficha.js, actualizarDomicilioAnterior()), que no depende del CIE10.
 * Requiere las mismas variables que ya están en scope: $enfermedad,
 * $valoresFijos, $detalleDomicilio, $resolvedorPara, $filasViajes,
 * $erroresViajes, $columnasViaje (estas 3 últimas, mismo contrato que
 * tablas-hijas/viajes.php).
 *
 * Los campos de esta tarjeta se pintan con campo-dinamico.php envueltos en
 * un closure (mismo motivo que notificacion-fechas-a95.php: no pisar la
 * variable $campo del llamador, el resolvedor AMBIENTE de
 * campos-por-clave.php).
 */
$campoA95Migracion = $resolvedorPara('A95');
$pintarCampoA95Migracion = function (string $clave) use ($campoA95Migracion): void {
    $resuelto = $campoA95Migracion($clave);
    if (!$resuelto['id']) {
        return;
    }
    $campo = $resuelto['campo'];
    $valor = $resuelto['val'];
    $error = $resuelto['err'];
    $opciones = $resuelto['opciones'];
    require __DIR__ . '/campo-dinamico.php';
};
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

<div class="fields" style="margin-top:14px">
  <?php $pintarCampoA95Migracion('a95_localidades_visitadas_en_los_ultimos_10_dias'); ?>
</div>

<div style="margin-top:14px">
  <?php $pintarCampoA95Migracion('a95_casos_reportados_en_los_ultimos_10_dias'); ?>
</div>

<div class="fields thirds" style="margin-top:14px">
  <?php $pintarCampoA95Migracion('a95_cuantas_personas_viven_en_su_casa'); ?>
</div>

<?php
$campoViajoA95 = $campoA95Migracion('a95_viajo_en_los_ultimos_6_meses');
$esViajoInicialA95 = ($campoViajoA95['val'] === '1') || !empty($filasViajes ?? []);
$pintarBooleanoA95 = function (array $resuelto): void {
    $campo = $resuelto['campo'];
    $valor = $resuelto['val'];
    $error = $resuelto['err'];
    require __DIR__ . '/campos/booleano.php';
};
?>
<div class="fields" style="margin-top:14px">
  <div class="field">
    <?php $pintarBooleanoA95($campoViajoA95); ?>
  </div>
</div>
<?php
// [[wrap_a_medida_necesita_js_dedicado]]: este wrap NO se apoya en
// evaluarDependencias() (motor genérico de .dep-wrap) para revelarse --
// requiere su propia función dedicada, actualizarBloqueViajesA95() en
// ficha.js, calcada de actualizarBloqueViajesA370() (mismo bug histórico
// documentado en B05/P35.0/A37.0: el inline "style=display:none" necesario
// contra el "flash" inicial nunca se limpia solo con "hidden").
//
// El margin/padding/border de separación NO puede ir en este div (el que
// tiene class="dep-wrap"): theme.css define ".dep-wrap{display:contents}"
// para que no agregue una caja propia dentro del ".fields" flex del padre,
// y con display:contents el navegador CALCULA esas propiedades pero nunca
// las renderiza (verificado con Playwright: gap real 0px pese a que
// getComputedStyle reportaba 14px/14px/1px). Por eso la separación real va
// en un <div> interno propio, que sí genera caja.
?>
<div id="a95-wrapper-viajes-registrados" class="dep-wrap" data-depende-de="<?= e($campoViajoA95['name']) ?>" data-valor-activador="1" style="<?= !$esViajoInicialA95 ? 'display: none;' : '' ?>" <?= !$esViajoInicialA95 ? 'hidden' : '' ?>>
  <div style="width: 100%; margin-top: 14px; margin-bottom: 18px; border-top: 1px solid var(--line-2); border-bottom: 1px solid var(--line-2); padding: 14px 0;">
    <div class="eyebrow" style="margin-bottom:10px; width:100%; display:block">Si viajó, especificar antecedente de viaje</div>
    <?php require __DIR__ . '/tablas-hijas/viajes.php'; ?>
  </div>
</div>
