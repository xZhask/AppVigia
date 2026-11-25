<?php
/**
 * Sábana individual de Epidemiología DIRSAPOL (2026-11-24, fase 1 de
 * docs/propuesta-sabanas-dirsapol.md). La pantalla muestra el resumen y una
 * vista previa; la sábana completa, con todas sus columnas, es el Excel.
 */
$limite = 300;
$queryFiltros = http_build_query(array_filter([
    'periodo'            => $filtros['periodo'],
    'anio'               => $filtros['periodo'] === 'anio' ? $filtros['desde'][0] : null,
    'anio_desde'         => $filtros['periodo'] === 'rango' ? $filtros['desde'][0] : null,
    'se_desde'           => $filtros['periodo'] === 'rango' ? $filtros['desde'][1] : null,
    'anio_hasta'         => $filtros['periodo'] === 'rango' ? $filtros['hasta'][0] : null,
    'se_hasta'           => $filtros['periodo'] === 'rango' ? $filtros['hasta'][1] : null,
    'red_id'             => $filtros['red_id'],
    'establecimiento_id' => $filtros['establecimiento_id'],
]));
$contar = function (string $clave) use ($casos): array {
    $conteo = array_count_values(array_map(fn($c) => (string) $c[$clave] !== '' ? (string) $c[$clave] : 'Sin dato', $casos));
    arsort($conteo);
    return $conteo;
};
$porTipo = $contar('tipo_dx');
$porCondicion = $contar('condicion_texto');
$porDiagnostico = array_slice($contar('cie10'), 0, 8, true);
$nombreDiagnostico = array_column($casos, 'diagnostico', 'cie10');
$establecimientosConCasos = count(array_unique(array_column($casos, 'establecimiento')));
$anioActual = (int) date('Y');
$rango = $filtros['periodo'] === 'rango';
?>
<style>
  .sab-filtros{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;padding:16px 18px}
  .sab-filtros .ancho2{grid-column:span 2}
  .sab-filtros .rango-se{display:flex;gap:6px}
  .sab-filtros .rango-se .control{flex:1}
  .sab-pie{display:flex;gap:10px;align-items:center;padding:0 18px 18px}
  .sab-resumen{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
  .sab-resumen .card{padding:14px 18px}
  .sab-resumen ul{list-style:none;margin:8px 0 0;padding:0;font-size:13px}
  .sab-resumen li{display:flex;justify-content:space-between;gap:12px;padding:3px 0;border-bottom:1px dashed var(--line)}
  .sab-resumen li:last-child{border-bottom:0}
  .sab-tabla td{font-size:12.5px;padding:8px 12px;border-bottom:1px solid var(--line);vertical-align:top}
  .sab-tabla th{padding:10px 12px}
  .sab-sub{display:block;font-size:11px;color:var(--muted)}
  .sab-nota{font-size:12px;color:var(--muted);padding:10px 18px 16px;margin:0;line-height:1.55}
  @media (max-width:900px){.sab-filtros{grid-template-columns:1fr 1fr}.sab-resumen{grid-template-columns:1fr}}
</style>

<div class="page-head">
  <div>
    <div class="page-title">Sábana individual de notificación epidemiológica</div>
    <div class="page-desc">Un caso por fila, de todas las IPRESS, para el Área de Epidemiología DIRSAPOL</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/reportes?vista=formatos">Volver a Reportes</a>
</div>

<form method="get" action="/reportes/sabana-individual" class="card" style="margin-bottom:16px">
  <div class="sab-filtros">
    <div>
      <label for="sabPeriodo">Periodo</label>
      <div class="control"><select id="sabPeriodo" name="periodo">
        <option value="anio" <?= $rango ? '' : 'selected' ?>>Año completo</option>
        <option value="rango" <?= $rango ? 'selected' : '' ?>>Rango de semanas</option>
      </select></div>
    </div>
    <div data-periodo="anio" <?= $rango ? 'hidden' : '' ?>>
      <label for="sabAnio">Año</label>
      <div class="control mono"><input id="sabAnio" type="number" name="anio" min="2000" max="<?= $anioActual ?>" value="<?= (int) $filtros['desde'][0] ?>"></div>
    </div>
    <div data-periodo="rango" <?= $rango ? '' : 'hidden' ?>>
      <label>Desde (año · SE)</label>
      <div class="rango-se">
        <div class="control mono"><input type="number" name="anio_desde" min="2000" max="<?= $anioActual ?>" value="<?= (int) $filtros['desde'][0] ?>" aria-label="Año desde"></div>
        <div class="control mono"><input type="number" name="se_desde" min="1" max="53" value="<?= (int) $filtros['desde'][1] ?>" aria-label="SE desde"></div>
      </div>
    </div>
    <div data-periodo="rango" <?= $rango ? '' : 'hidden' ?>>
      <label>Hasta (año · SE)</label>
      <div class="rango-se">
        <div class="control mono"><input type="number" name="anio_hasta" min="2000" max="<?= $anioActual ?>" value="<?= (int) $filtros['hasta'][0] ?>" aria-label="Año hasta"></div>
        <div class="control mono"><input type="number" name="se_hasta" min="1" max="53" value="<?= (int) $filtros['hasta'][1] ?>" aria-label="SE hasta"></div>
      </div>
    </div>
    <div class="<?= $rango ? '' : 'ancho2' ?>" data-ancho-anio>
      <label for="sabRed">Red</label>
      <div class="control"><select id="sabRed" name="red_id">
        <option value="">Todas</option>
        <?php foreach ($redes as $red): ?>
          <option value="<?= (int) $red['id'] ?>" <?= seleccionado((string) ($filtros['red_id'] ?? ''), $red['id']) ?>><?= e($red['nombre']) ?></option>
        <?php endforeach; ?>
      </select></div>
    </div>
    <div class="ancho2">
      <label for="sabEst">Establecimiento</label>
      <div class="control"><select id="sabEst" name="establecimiento_id">
        <option value="">Todos</option>
        <?php foreach ($establecimientos as $establecimiento): ?>
          <option value="<?= (int) $establecimiento['id'] ?>" <?= seleccionado((string) ($filtros['establecimiento_id'] ?? ''), $establecimiento['id']) ?>><?= e($establecimiento['nombre']) ?></option>
        <?php endforeach; ?>
      </select></div>
    </div>
  </div>
  <div class="sab-pie">
    <button type="submit" class="btn btn-primary">Aplicar filtros</button>
    <span class="sab-sub"><?= e($filtros['etiqueta']) ?> · del <?= e(fechaIsoADmy($filtros['rango'][0])) ?> al <?= e(fechaIsoADmy($filtros['rango'][1])) ?></span>
  </div>
</form>

<div class="ns-kpis">
  <div class="card ns-kpi"><div class="eyebrow">Casos</div><div class="ns-kpi-v mono"><?= count($casos) ?></div></div>
  <div class="card ns-kpi"><div class="eyebrow">Establecimientos con casos</div><div class="ns-kpi-v mono"><?= $establecimientosConCasos ?></div></div>
  <div class="card ns-kpi"><div class="eyebrow">Confirmados</div><div class="ns-kpi-v mono"><?= (int) ($porTipo['C'] ?? 0) ?></div></div>
  <div class="card ns-kpi"><div class="eyebrow">Hospitalizados</div><div class="ns-kpi-v mono"><?= count(array_filter($casos, fn($c) => $c['hospitalizado'] === 'SI')) ?></div></div>
</div>

<?php if ($casos): ?>
  <div class="sab-resumen">
    <div class="card"><div class="eyebrow">Tipo de Dx</div><ul>
      <?php foreach ($porTipo as $tipo => $n): ?><li><span><?= e(['P' => 'P · probable', 'C' => 'C · confirmado', 'D' => 'D · descartado'][$tipo] ?? $tipo) ?></span><b class="mono"><?= $n ?></b></li><?php endforeach; ?>
    </ul></div>
    <div class="card"><div class="eyebrow">Condición</div><ul>
      <?php foreach ($porCondicion as $condicion => $n): ?><li><span><?= e($condicion) ?></span><b class="mono"><?= $n ?></b></li><?php endforeach; ?>
    </ul></div>
    <div class="card"><div class="eyebrow">Diagnósticos más frecuentes</div><ul>
      <?php foreach ($porDiagnostico as $cie10 => $n): ?><li><span><span class="mono"><?= e($cie10) ?></span> <?= e(mb_strimwidth((string) ($nombreDiagnostico[$cie10] ?? ''), 0, 42, '…')) ?></span><b class="mono"><?= $n ?></b></li><?php endforeach; ?>
    </ul></div>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-head">
    <div><h3>Vista previa</h3><div class="sub"><?= count($casos) > $limite ? 'Primeros ' . $limite . ' de ' . count($casos) . ' casos' : count($casos) . ' caso(s)' ?> · algunas columnas; el Excel trae las 43</div></div>
    <div class="spacer"></div>
    <a class="btn btn-primary btn-sm" data-descarga href="/reportes/sabana-individual/exportar?<?= e($queryFiltros) ?>">
      <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2.5v7M4 6.5l3 3 3-3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>Descargar Excel</a>
  </div>
  <div style="overflow-x:auto">
    <table class="sab-tabla">
      <thead><tr>
        <th>N.°</th><th>SE</th><th>Establecimiento</th><th>Condición</th><th>Paciente</th><th>Edad · sexo</th>
        <th>CIE-10</th><th>Diagnóstico</th><th>Tipo Dx</th><th>Notificación</th><th>Situación</th>
      </tr></thead>
      <tbody>
        <?php if (!$casos): ?>
          <tr><td colspan="11" style="text-align:center;color:var(--muted);padding:24px">No hay casos notificados en el periodo para este filtro.</td></tr>
        <?php endif; ?>
        <?php foreach (array_slice($casos, 0, $limite) as $i => $c): ?>
          <tr>
            <td class="mono"><a href="/casos/<?= (int) $c['id'] ?>" title="Abrir <?= e($c['codigo']) ?>"><?= $i + 1 ?></a></td>
            <td class="mono"><?= (int) $c['semana'] ?><span class="sab-sub"><?= e($c['mes']) ?></span></td>
            <td><?= e(capitalizarNombre($c['establecimiento'])) ?><span class="sab-sub"><?= e($c['red']) ?></span></td>
            <td><?= e($c['condicion_texto']) ?><?php if ($c['grado'] !== ''): ?><span class="sab-sub"><?= e($c['grado']) ?></span><?php endif; ?></td>
            <td><?= e($c['nombre']) ?></td>
            <td class="mono"><?= e($c['edad']) ?> · <?= e($c['sexo']) ?></td>
            <td class="mono"><?= e($c['cie10']) ?></td>
            <td><?= e(mb_strimwidth($c['diagnostico'], 0, 48, '…')) ?></td>
            <td class="mono"><?= e($c['tipo_dx']) ?></td>
            <td class="mono"><?= e(fechaIsoADmy($c['notificacion'])) ?></td>
            <td><?= e($c['situacion']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="sab-nota">
    Fichas MINSA y registros de otras enfermedades sujetas a vigilancia, no anulados, por semana epidemiológica de notificación.
    EDA, IRA, neumonía, SOB y asma no van uno por uno: se cuentan en el consolidado. VIH/SIDA va con las iniciales del paciente.
    Mes: el del sábado que cierra la SE. Red y establecimiento son los de VIGÍA, hasta que esté la tabla de equivalencias con las macrorregiones del Área.
  </p>
</div>

<script>
  (function () {
    var periodo = document.getElementById('sabPeriodo');
    var red = document.querySelector('[data-ancho-anio]');
    periodo.addEventListener('change', function () {
      document.querySelectorAll('[data-periodo]').forEach(function (el) { el.hidden = el.dataset.periodo !== periodo.value; });
      red.classList.toggle('ancho2', periodo.value === 'anio');
    });
  })();
</script>
