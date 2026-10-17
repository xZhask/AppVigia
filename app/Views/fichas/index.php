<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Caso;
use App\Models\Persona;

// Listado de fichas (rediseño 2026-10-17): pestañas por estado, periodo por
// semana epidemiológica, filtros que se aplican al cambiar (sin botón
// "Filtrar"), conteos por clasificación que también filtran, chips de los
// filtros activos y orden por columna. Todo va por la URL (GET), así un
// enlace o el botón Atrás devuelven exactamente la misma lista.
$url = function (array $cambios = []) use ($consulta): string {
    $query = queryConPagina($consulta, $cambios);
    return '/casos' . ($query !== '' ? '?' . $query : '');
};
$etiquetasPestana = [
    'ABIERTA'    => 'Abiertas',
    'VALIDACION' => 'En validación',
    'CERRADA'    => 'Cerradas',
    'ANULADA'    => 'Anuladas',
    'TODAS'      => 'Todas',
];
$etiquetasEstado = ['ABIERTA' => 'Abierta', 'VALIDACION' => 'En validación', 'CERRADA' => 'Cerrada'];
// Entrada F: mismo criterio que ver.php -- si la ficha declaró
// unidades_edad, la edad capturada con su unidad manda sobre la derivada de
// fecha_nac. Abreviaturas de una letra para el formato compacto ("35a") que
// ya usaba esta lista; "min" para minutos evita chocar con "m" de meses.
$abreviaturasUnidadEdad = ['ANIOS' => 'a', 'MESES' => 'm', 'DIAS' => 'd', 'HORAS' => 'h', 'MINUTOS' => 'min'];

// Conteos por clasificación: las que tienen fichas en la pestaña, en el orden
// del catálogo, más las elegidas aunque hoy no tengan ninguna.
$codigosConteo = array_values(array_filter(
    array_keys(CATALOGO_CLASIFICACION),
    fn(string $codigo) => isset($porClasificacion[$codigo]) || in_array($codigo, $filtros['clasificaciones'], true)
));
$conClasificacion = function (string $codigo) use ($filtros): array {
    $elegidas = $filtros['clasificaciones'];
    return in_array($codigo, $elegidas, true)
        ? array_values(array_diff($elegidas, [$codigo]))
        : array_merge($elegidas, [$codigo]);
};

$nombreEnfermedad = '';
foreach ($enfermedades as $enfermedadLista) {
    if ((string) $enfermedadLista['id'] === $filtros['enfermedad_id']) {
        $nombreEnfermedad = $enfermedadLista['nombre'];
    }
}
$nombreRed = '';
foreach ($redes as $red) {
    if ((string) $red['id'] === $filtros['red_id']) {
        $nombreRed = $red['nombre'];
    }
}
$nombreEstablecimiento = '';
foreach ($establecimientos as $establecimiento) {
    if ((string) $establecimiento['id'] === (string) $consulta['establecimiento_id']) {
        $nombreEstablecimiento = $establecimiento['nombre'];
    }
}
$ambito = $esRegistrador
    ? (Auth::usuario()['establecimiento_nombre'] ?? 'tu establecimiento')
    : ($nombreEstablecimiento ?: ($nombreRed ?: 'todas las redes y establecimientos'));

$textoPeriodo = match ($filtros['periodo']) {
    'actual'   => 'SE ' . $filtros['semana_actual'] . ' · ' . $filtros['anio_actual'],
    'ultimas4' => 'SE ' . $filtros['se_desde'][1] . '–' . $filtros['semana_actual'] . ' · ' . ($filtros['se_desde'][0] !== $filtros['anio_actual'] ? $filtros['se_desde'][0] . '/' : '') . $filtros['anio_actual'],
    'rango'    => '',
    'todo'     => 'Todas las semanas',
    default    => 'SE 1–' . $filtros['semana_actual'] . ' · ' . $filtros['anio_actual'],
};

// Chips de los filtros activos: cada uno se quita con su ×.
$chips = [];
if ($filtros['q'] !== '') {
    $chips[] = ['Búsqueda: “' . $filtros['q'] . '”', $url(['q' => '', 'page' => ''])];
}
if ($nombreEnfermedad !== '') {
    $chips[] = [$nombreEnfermedad, $url(['enfermedad_id' => '', 'page' => ''])];
}
if ($nombreRed !== '') {
    $chips[] = [$nombreRed, $url(['red_id' => '', 'establecimiento_id' => '', 'page' => ''])];
}
if ($nombreEstablecimiento !== '') {
    $chips[] = [$nombreEstablecimiento, $url(['establecimiento_id' => '', 'page' => ''])];
}
if ($filtros['periodo'] !== 'anio') {
    $chips[] = [
        match ($filtros['periodo']) {
            'actual'   => 'SE actual',
            'ultimas4' => 'Últimas 4 SE',
            'todo'     => 'Todas las semanas',
            default    => 'SE ' . $filtros['se_desde_n'] . '–' . $filtros['se_hasta_n'] . ' · ' . $filtros['anio'],
        },
        $url(['periodo' => '', 'anio' => '', 'se_desde' => '', 'se_hasta' => '', 'page' => '']),
    ];
}
foreach ($filtros['clasificaciones'] as $codigo) {
    $chips[] = [datosClasificacion($codigo)['etiqueta'], $url(['clas' => $conClasificacion($codigo), 'page' => ''])];
}
$limpiarTodo = $url(['q' => '', 'enfermedad_id' => '', 'red_id' => '', 'establecimiento_id' => '', 'periodo' => '', 'anio' => '', 'se_desde' => '', 'se_hasta' => '', 'clas' => [], 'page' => '']);

$encabezadoOrden = function (string $clave, string $etiqueta) use ($filtros, $url): string {
    $activa = $filtros['orden'] === $clave;
    $porDefectoDesc = in_array($clave, ['fecha', 'codigo'], true);
    $siguiente = $activa ? ($filtros['dir'] === 'asc' ? 'desc' : 'asc') : ($porDefectoDesc ? 'desc' : 'asc');
    $flecha = $activa ? ($filtros['dir'] === 'asc' ? '↑' : '↓') : '↕';
    $aria = $activa ? ($filtros['dir'] === 'asc' ? 'ascending' : 'descending') : 'none';
    return '<th class="th-orden' . ($activa ? ' activa' : '') . '" aria-sort="' . $aria . '"><a href="'
        . e($url(['orden' => $clave === 'fecha' ? '' : $clave, 'dir' => $siguiente, 'page' => ''])) . '">'
        . e($etiqueta) . '<span class="flecha" aria-hidden="true">' . $flecha . '</span></a></th>';
};
?>
<div class="page-head">
  <div>
    <div class="page-title">Fichas registradas</div>
    <div class="page-desc"><?= number_format($porPestana['TODAS'], 0, ',', ' ') . ' ' . ($porPestana['TODAS'] === 1 ? 'ficha' : 'fichas') ?> · <?= e($ambito) ?></div>
  </div>
  <div class="spacer"></div>
  <?php if ($hayFichas): ?>
  <a class="btn btn-ghost" href="<?= e('/casos/exportar' . substr($url(['page' => '']), 6)) ?>" title="Descarga en CSV las fichas de esta lista, con los filtros aplicados">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 2.5v7M4 6.5l3 3 3-3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Exportar
  </a>
  <?php endif; ?>
  <a class="btn btn-ghost" href="/casos/importar">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 9.5v-7M4 5.5l3-3 3 3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Importar Excel
  </a>
  <a class="btn btn-primary" href="/casos/nuevo">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    Nueva ficha
  </a>
</div>

<?php if (!$hayFichas): ?>
  <?php
  $icono = '<svg width="19" height="19" viewBox="0 0 19 19" fill="none"><rect x="3" y="2.5" width="13" height="14" rx="2" stroke="currentColor" stroke-width="1.4"/><path d="M6.5 7h6M6.5 10h6M6.5 13h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';
  $mensaje = 'Todavía no se ha registrado ninguna ficha. Empieza por notificar el primer caso de una enfermedad bajo vigilancia.';
  $accionTexto = 'Registrar la primera ficha';
  $accionHref = '/casos/nuevo';
  require __DIR__ . '/../partials/estado-vacio.php';
  ?>
<?php else: ?>
  <nav class="pestanas" aria-label="Estado de la ficha">
    <?php foreach ($etiquetasPestana as $clave => $etiqueta): ?>
      <a class="pestana<?= $pestana === $clave ? ' on' : '' ?>" href="<?= e($url(['pestana' => $clave === 'ABIERTA' ? '' : $clave, 'page' => ''])) ?>"<?= $pestana === $clave ? ' aria-current="page"' : '' ?>>
        <?= $etiqueta ?><span class="cnt mono"><?= number_format($porPestana[$clave], 0, ',', ' ') ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="card table-card listado-card">
    <form method="get" action="/casos" class="listado-filtros" id="filtrosListado">
      <?php // Enter en el buscador envía con este botón (sin name), no con "SE actual", el primero de los de periodo. ?>
      <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true">Aplicar</button>
      <?php // El periodo vigente va antes de sus botones: el que se pulse llega después y manda. ?>
      <?php if ($consulta['periodo'] !== ''): ?><input type="hidden" name="periodo" value="<?= e($consulta['periodo']) ?>"><?php endif; ?>
      <?php foreach (['pestana', 'orden', 'dir'] as $oculto): ?>
        <?php if ($consulta[$oculto] !== ''): ?><input type="hidden" name="<?= $oculto ?>" value="<?= e((string) $consulta[$oculto]) ?>"><?php endif; ?>
      <?php endforeach; ?>
      <?php foreach ($filtros['clasificaciones'] as $codigo): ?>
        <input type="hidden" name="clas[]" value="<?= e($codigo) ?>">
      <?php endforeach; ?>
      <div class="filtros-fila<?= $esRegistrador ? ' sin-red' : '' ?>">
        <label class="search">
          <svg width="15" height="15" viewBox="0 0 15 15"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.4" fill="none"/><path d="m10 10 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
          <input type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Paciente, DNI o N.° de ficha" aria-label="Buscar" autocomplete="off" data-auto-busqueda>
        </label>
        <div class="filtro-caja">
        <select name="enfermedad_id" aria-label="Enfermedad" data-auto>
          <option value="">Enfermedad: todas</option>
          <?php foreach ($enfermedades as $enf): ?>
            <option value="<?= (int) $enf['id'] ?>" <?= seleccionado($filtros['enfermedad_id'], $enf['id']) ?>><?= e($enf['nombre']) ?><?= !empty($enf['cie10']) ? ' · ' . e($enf['cie10']) : '' ?></option>
          <?php endforeach; ?>
        </select>
        </div>
        <?php if (!$esRegistrador): ?>
          <div class="filtro-caja">
          <select name="red_id" aria-label="Red" data-auto data-limpia="establecimiento_id">
            <option value="">Red: todas</option>
            <?php foreach ($redes as $red): ?>
              <option value="<?= (int) $red['id'] ?>" <?= seleccionado($filtros['red_id'], $red['id']) ?>><?= e($red['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          </div>
          <div class="filtro-caja">
          <select name="establecimiento_id" aria-label="Establecimiento" data-auto>
            <option value="">Establecimiento: todos</option>
            <?php foreach ($establecimientos as $establecimiento): ?>
              <option value="<?= (int) $establecimiento['id'] ?>" <?= seleccionado((string) $consulta['establecimiento_id'], $establecimiento['id']) ?>><?= e($establecimiento['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          </div>
        <?php endif; ?>
      </div>
      <div class="filtros-periodo">
        <span class="plabel">Periodo</span>
        <div class="seg-periodo" role="group" aria-label="Periodo por semana epidemiológica">
          <?php foreach (['actual' => 'SE actual', 'ultimas4' => 'Últimas 4 SE', 'anio' => 'Este año', 'rango' => 'Rango de SE', 'todo' => 'Todo'] as $valor => $etiqueta): ?>
            <button type="submit" name="periodo" value="<?= $valor ?>"<?= $filtros['periodo'] === $valor ? ' class="on" aria-pressed="true"' : ' aria-pressed="false"' ?>><?= $etiqueta ?></button>
          <?php endforeach; ?>
        </div>
        <?php if ($filtros['periodo'] === 'rango'): ?>
          <span class="rango-se">
            SE <input type="number" name="se_desde" min="1" max="53" value="<?= (int) $filtros['se_desde_n'] ?>" aria-label="Desde la SE" data-auto>
            a <input type="number" name="se_hasta" min="1" max="53" value="<?= (int) $filtros['se_hasta_n'] ?>" aria-label="Hasta la SE" data-auto>
            · <input type="number" name="anio" min="2000" max="<?= (int) $filtros['anio_actual'] ?>" value="<?= (int) $filtros['anio'] ?>" aria-label="Año" class="rango-anio" data-auto>
          </span>
        <?php else: ?>
          <span class="periodo-txt mono"><?= e($textoPeriodo) ?></span>
        <?php endif; ?>
      </div>
    </form>

    <?php if ($codigosConteo || $chips): ?>
      <div class="listado-conteos">
        <?php foreach ($codigosConteo as $codigo): $datos = datosClasificacion($codigo); $elegida = in_array($codigo, $filtros['clasificaciones'], true); ?>
          <a class="conteo<?= $elegida ? ' on' : '' ?>" href="<?= e($url(['clas' => $conClasificacion($codigo), 'page' => ''])) ?>" aria-pressed="<?= $elegida ? 'true' : 'false' ?>" title="<?= $elegida ? 'Quitar este filtro' : 'Mostrar solo esta clasificación' ?>">
            <span class="dot <?= $datos['dot'] ?>"></span><?= e($datos['etiqueta']) ?> <b class="mono"><?= $porClasificacion[$codigo] ?? 0 ?></b>
          </a>
        <?php endforeach; ?>
        <?php if ($chips): ?>
          <?php if ($codigosConteo): ?><span class="conteos-sep" aria-hidden="true"></span><?php endif; ?>
          <?php foreach ($chips as [$etiqueta, $href]): ?>
            <span class="filtro-activo"><?= e(mb_strlen($etiqueta) > 40 ? mb_substr($etiqueta, 0, 38) . '…' : $etiqueta) ?><a href="<?= e($href) ?>" aria-label="Quitar filtro <?= e($etiqueta) ?>" title="Quitar filtro">×</a></span>
          <?php endforeach; ?>
          <a class="limpiar-todo" href="<?= e($limpiarTodo) ?>">Limpiar todo</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="tabla-desplazable">
    <table class="tabla-fichas">
      <thead><tr>
        <?= $encabezadoOrden('codigo', 'N.° ficha') ?>
        <?= $encabezadoOrden('paciente', 'Paciente') ?>
        <?= $encabezadoOrden('enfermedad', 'Enfermedad') ?>
        <th>Establecimiento</th>
        <?= $encabezadoOrden('fecha', 'Notificación') ?>
        <?= $encabezadoOrden('clasificacion', 'Clasificación') ?>
        <th>Estado</th>
        <th aria-label="Acciones"></th>
      </tr></thead>
      <tbody>
        <?php if (empty($fichas)): ?>
          <tr class="sin-filas"><td colspan="8">
            <b>No hay fichas con estos filtros</b>
            <?php if ($chips): ?>Prueba con otro periodo o <a href="<?= e($limpiarTodo) ?>">limpia los filtros</a>.<?php else: ?>Prueba con otra pestaña u otro periodo.<?php endif; ?>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($fichas as $ficha):
          $clasificacion = (string) $ficha['clasificacion'];
          $c = $clasificacion !== '' ? datosClasificacion($clasificacion) : null; // incluye O95/A00/Z21, no solo las 4 genéricas
          $pendiente = !$ficha['anulado'] && $ficha['estado'] === 'ABIERTA'
              && ($clasificacion === '' || in_array($clasificacion, Caso::CLASIFICACIONES_NO_FINALES, true));
          $unidadesEdadDeclaradasFicha = [];
          if (!empty($ficha['enfermedad_unidades_edad'])) {
              $decodificadoUnidadesEdadFicha = json_decode($ficha['enfermedad_unidades_edad'], true);
              $unidadesEdadDeclaradasFicha = is_array($decodificadoUnidadesEdadFicha) ? $decodificadoUnidadesEdadFicha : [];
          }
          if (!empty($unidadesEdadDeclaradasFicha)) {
              $edadCompacta = ($ficha['edad_valor'] !== null && !empty($ficha['edad_unidad']))
                  ? $ficha['edad_valor'] . ($abreviaturasUnidadEdad[$ficha['edad_unidad']] ?? '')
                  : null;
          } else {
              $edadCompacta = edadLegible($ficha['fecha_nac'], $ficha['edad_fecha_referencia'] ?? null, true);
          }
          $nombrePaciente = Persona::nombreCompletoPnp($ficha);
        ?>
          <tr class="fila-ficha<?= $ficha['anulado'] ? ' anulada' : '' ?>" data-href="/casos/<?= (int) $ficha['id'] ?>" tabindex="0">
            <td class="mono nro"><a href="/casos/<?= (int) $ficha['id'] ?>"><?= e($ficha['codigo']) ?></a></td>
            <td>
              <div class="pt-name trunc" title="<?= e($nombrePaciente) ?>"><?= e($nombrePaciente) ?></div>
              <div class="pt-doc"><?= e(documentoParaMostrar($ficha['tipo_doc'], $ficha['num_doc'], true)) ?> · <?= e($ficha['sexo'] ?? '—') ?><?= $edadCompacta !== null ? ' · ' . $edadCompacta : '' ?></div>
            </td>
            <td><div class="trunc" title="<?= e($ficha['enfermedad_nombre']) ?>"><?= e($ficha['enfermedad_nombre']) ?></div><div class="cell-sub mono"><?= e($ficha['cie10'] ?? '—') ?></div></td>
            <td><div class="trunc trunc-eess" title="<?= e($ficha['establecimiento_nombre']) ?>"><?= e(nombreEnOracion($ficha['establecimiento_nombre'])) ?></div><div class="cell-sub"><?= e($ficha['red_nombre'] ?? '—') ?></div></td>
            <td class="nowrap"><div class="mono se">SE <?= (int) $ficha['semana_epi'] ?></div><div class="cell-sub mono"><?= e(fechaIsoADmy($ficha['fecha_notif'])) ?></div></td>
            <td><?php if ($c): ?><span class="badge-clas" style="--c:<?= $c['color'] ?>"><span class="dot"></span><?= e($c['etiqueta']) ?></span><?php else: ?><span class="state">—</span><?php endif; ?></td>
            <td class="nowrap">
              <span class="estado-txt"><?= $ficha['anulado'] ? 'Anulada' : e($etiquetasEstado[$ficha['estado']] ?? $ficha['estado']) ?><?php if ($pendiente): ?><span class="punto-pendiente" title="Sin clasificación final"></span><?php endif; ?></span>
            </td>
            <td>
              <div class="row-actions">
                <?php if ($ficha['puede_editar']): ?>
                  <a class="ra" title="Editar" aria-label="Editar <?= e($ficha['codigo']) ?>" href="/casos/<?= (int) $ficha['id'] ?>/editar">
                    <svg width="15" height="15" viewBox="0 0 15 15"><path d="M10 2.5 12.5 5 5 12.5 2 13l.5-3L10 2.5Z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round"/></svg>
                  </a>
                <?php endif; ?>
                <button type="button" class="ra" title="Más acciones" aria-label="Más acciones para <?= e($ficha['codigo']) ?>" aria-haspopup="menu"
                        data-menu-ficha data-id="<?= (int) $ficha['id'] ?>" data-codigo="<?= e($ficha['codigo']) ?>"
                        data-editar="<?= $ficha['puede_editar'] ? '1' : '' ?>" data-anular="<?= $puedeAnular && !$ficha['anulado'] ? '1' : '' ?>">
                  <svg width="15" height="15" viewBox="0 0 15 15" fill="currentColor"><circle cx="3" cy="7.5" r="1.3"/><circle cx="7.5" cy="7.5" r="1.3"/><circle cx="12" cy="7.5" r="1.3"/></svg>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <div class="table-foot">
      <span>Mostrando <?= $total === 0 ? 0 : (($pagina - 1) * $porPagina + 1) ?>–<?= min($pagina * $porPagina, $total) ?> de <?= number_format($total, 0, ',', ' ') ?> <?= $total === 1 ? 'ficha' : 'fichas' ?></span>
      <?php if ($totalPaginas > 1): ?>
        <nav class="pager" aria-label="Páginas">
          <a class="pg" href="<?= e($url(['page' => max(1, $pagina - 1)])) ?>" aria-label="Página anterior">‹</a>
          <?php for ($p = max(1, $pagina - 2); $p <= min($totalPaginas, $pagina + 2); $p++): ?>
            <a class="pg <?= $p === $pagina ? 'on' : '' ?>" href="<?= e($url(['page' => $p])) ?>"<?= $p === $pagina ? ' aria-current="page"' : '' ?>><?= $p ?></a>
          <?php endfor; ?>
          <a class="pg" href="<?= e($url(['page' => min($totalPaginas, $pagina + 1)])) ?>" aria-label="Página siguiente">›</a>
        </nav>
      <?php endif; ?>
    </div>
  </div>

  <div class="menu-ficha" id="menuFicha" role="menu" hidden>
    <a role="menuitem" data-accion="ver" href="#">Ver detalle</a>
    <a role="menuitem" data-accion="editar" href="#">Editar</a>
    <button type="button" role="menuitem" data-accion="anular" class="peligro">Anular ficha…</button>
  </div>
  <?php if ($puedeAnular): ?>
    <form method="post" id="formAnularFicha" hidden>
      <?= Csrf::campoOculto() ?>
      <input type="hidden" name="motivo" value="">
    </form>
  <?php endif; ?>
  <script src="<?= e(asset('/js/listado-fichas.js')) ?>"></script>
<?php endif; ?>
