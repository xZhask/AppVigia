<?php
$agrupaciones = [
    'establecimiento' => 'Establecimiento',
    'red'             => 'Red',
    'semana'          => 'Semana epidemiológica',
    'clasificacion'   => 'Clasificación',
    'categoria_pnp'   => 'Categoría PNP',
    'nivel'           => 'Nivel de grado PNP',
];
$queryFiltros = http_build_query([
    'enfermedad_id' => $enfermedadId ?? '',
    'agrupar_por'   => $agrupacion,
    'anio_desde'    => $rangoSe['anio_desde'],
    'se_desde'      => $rangoSe['se_desde'],
    'anio_hasta'    => $rangoSe['anio_hasta'],
    'se_hasta'      => $rangoSe['se_hasta'],
]);
$dotsClasificacion = ['SOSPECHOSO' => 'dot-sos', 'PROBABLE' => 'dot-pro', 'CONFIRMADO' => 'dot-con', 'DESCARTADO' => 'dot-des'];
$etiquetasClasificacion = ['SOSPECHOSO' => 'Sospechoso', 'PROBABLE' => 'Probable', 'CONFIRMADO' => 'Confirmado', 'DESCARTADO' => 'Descartado'];
$coloresClasificacion = ['SOSPECHOSO' => 'var(--s-sospechoso)', 'PROBABLE' => 'var(--s-probable)', 'CONFIRMADO' => 'var(--s-confirmado)', 'DESCARTADO' => 'var(--s-descartado)'];
$maxClasificacion = !empty($distribucionClasificacion) ? max($distribucionClasificacion) : 0;
?>
<div class="page-head">
  <div>
    <div class="page-title">Reportes</div>
    <div class="page-desc">Consolidados dinámicos para el área — sin re-digitar Excel</div>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <form method="get" action="/reportes">
    <div class="report-controls">
      <div class="rc">
        <label>Enfermedad</label>
        <div class="control">
          <select name="enfermedad_id">
            <option value="">Todas</option>
            <?php foreach ($enfermedades as $enf): ?>
              <option value="<?= (int) $enf['id'] ?>" <?= seleccionado($enfermedadId ?? '', $enf['id']) ?>><?= e($enf['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="rc">
        <label>Agrupar por</label>
        <div class="control">
          <select name="agrupar_por">
            <?php foreach ($agrupaciones as $valor => $etiqueta): ?>
              <option value="<?= $valor ?>" <?= seleccionado($agrupacion, $valor) ?>><?= $etiqueta ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="rc">
        <label>Desde</label>
        <div class="control mono" style="gap:6px">
          <input type="number" name="se_desde" min="1" max="53" value="<?= (int) $rangoSe['se_desde'] ?>" style="width:44px" title="Semana epidemiológica">
          ·
          <input type="number" name="anio_desde" min="2000" max="2100" value="<?= (int) $rangoSe['anio_desde'] ?>" style="width:64px" title="Año">
        </div>
      </div>
      <div class="rc">
        <label>Hasta</label>
        <div class="control mono" style="gap:6px">
          <input type="number" name="se_hasta" min="1" max="53" value="<?= (int) $rangoSe['se_hasta'] ?>" style="width:44px" title="Semana epidemiológica">
          ·
          <input type="number" name="anio_hasta" min="2000" max="2100" value="<?= (int) $rangoSe['anio_hasta'] ?>" style="width:64px" title="Año">
        </div>
      </div>
    </div>
    <div style="padding:0 18px 18px">
      <button type="submit" class="btn btn-primary">Aplicar filtros</button>
    </div>
  </form>
</div>

<?php if (empty($filas)): ?>
  <?php
  $icono = '<svg width="19" height="19" viewBox="0 0 19 19" fill="none"><path d="M3 15V7M7 15V4M11 15v-5M15 15V3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
  $mensaje = 'No hay fichas para los filtros seleccionados. Ajusta la enfermedad o el rango de semanas epidemiológicas e intenta de nuevo.';
  $accionTexto = '';
  $accionHref = '';
  require __DIR__ . '/../partials/estado-vacio.php';
  ?>
<?php else: ?>
  <div class="card table-card">
    <div class="card-head">
      <div><h3><?= $etiquetaColumna ?></h3><div class="sub">SE <?= (int) $rangoSe['se_desde'] ?> · <?= (int) $rangoSe['anio_desde'] ?> – SE <?= (int) $rangoSe['se_hasta'] ?> · <?= (int) $rangoSe['anio_hasta'] ?></div></div>
      <div class="spacer"></div>
      <a class="btn-quiet" href="/reportes/exportar?<?= $queryFiltros ?>">
        <svg width="15" height="15" viewBox="0 0 15 15"><rect x="2" y="2.5" width="11" height="10" rx="1.5" stroke="currentColor" stroke-width="1.2" fill="none"/><path d="M5.5 5.5l4 4M9.5 5.5l-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        Excel
      </a>
      <button type="button" class="btn-quiet" onclick="window.print()">
        <svg width="15" height="15" viewBox="0 0 15 15"><path d="M4 1.5h5l3 3v9H4z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round"/></svg>
        PDF
      </button>
    </div>
    <div class="report-out">
      <div style="overflow-x:auto">
      <table>
        <thead><tr><th><?= $etiquetaColumna ?></th><th style="text-align:right">Sospech.</th><th style="text-align:right">Probable</th><th style="text-align:right">Confirm.</th><th style="text-align:right">Descart.</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
          <?php foreach ($filas as $fila): ?>
            <tr>
              <td class="pt-name"><?= e($agrupacion === 'clasificacion' ? ($etiquetasClasificacion[$fila['etiqueta']] ?? $fila['etiqueta']) : $fila['etiqueta']) ?></td>
              <td class="mono" style="text-align:right"><?= (int) $fila['sospechoso'] ?></td>
              <td class="mono" style="text-align:right"><?= (int) $fila['probable'] ?></td>
              <td class="mono" style="text-align:right"><?= (int) $fila['confirmado'] ?></td>
              <td class="mono" style="text-align:right"><?= (int) $fila['descartado'] ?></td>
              <td class="mono" style="text-align:right"><b><?= (int) $fila['total'] ?></b></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <div class="report-side">
        <div class="eyebrow">Distribución por clasificación</div>
        <div class="mini-bars">
          <?php foreach ($distribucionClasificacion as $clasificacion => $total): ?>
            <div class="mb-row">
              <div class="mb-top"><span><?= $etiquetasClasificacion[$clasificacion] ?></span><span class="mono"><?= (int) $total ?></span></div>
              <span class="cd-track"><span class="cd-fill" style="width:<?= $maxClasificacion > 0 ? round($total / $maxClasificacion * 100) : 0 ?>%;background:<?= $coloresClasificacion[$clasificacion] ?>"></span></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line-2);font-size:12.5px;color:var(--muted)">
          Total del periodo: <b class="mono" style="color:var(--ink)"><?= (int) $totalPeriodo ?></b> fichas · <?= $totalEstablecimientos ?> establecimiento<?= $totalEstablecimientos === 1 ? '' : 's' ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
