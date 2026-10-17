<?php
use App\Models\Consolidado;
use App\Models\ListadoMuerteFetalNeonatal;
use App\Models\RegistroBusquedaActivaVih;

// Reportes (rediseño 2026-10-17): pestañas Consolidado | Formatos MINSA;
// tabla cruzada filas × columnas con totales y vista Tabla | Gráfico.
$f = $filtros;
$consulta = [
    'enfermedad_id' => $f['enfermedad_id'] ?? '',
    'filas'         => $f['filas'],
    'columnas'      => $f['columnas'] ?? 'ninguna',
    'periodo'       => $f['periodo'] === 'anio' ? '' : $f['periodo'],
    'anio_desde'    => $f['periodo'] === 'rango' ? $f['desde'][0] : '',
    'se_desde'      => $f['periodo'] === 'rango' ? $f['desde'][1] : '',
    'anio_hasta'    => $f['periodo'] === 'rango' ? $f['hasta'][0] : '',
    'se_hasta'      => $f['periodo'] === 'rango' ? $f['hasta'][1] : '',
    'modo'          => $f['modo'] === 'grafico' ? 'grafico' : '',
];
$url = fn(string $ruta, array $cambios = []) => $ruta . (($q = queryConPagina($consulta, $cambios)) !== '' ? '?' . $q : '');
$variosAnios = $f['desde'][0] !== $f['hasta'][0];
$c = $cruce;
$conColumnas = $f['columnas'] !== null;
$maxCelda = 1;
foreach ($c['filas'] as $fila) {
    $maxCelda = max($maxCelda, $conColumnas ? max(array_map(fn($k) => $c['celdas'][$fila][$k] ?? 0, $c['columnas'])) : ($c['totalFila'][$fila] ?? 0));
}
$sombra = fn(int $v) => $v ? 'background-color:color-mix(in srgb,var(--accent) ' . round(6 + 30 * $v / $maxCelda) . '%,transparent)' : '';
$colorColumna = function (string $k, int $i) use ($f): string {
    if ($f['columnas'] === 'clasificacion') {
        return datosClasificacion($k)['color'];
    }
    return $k === 'SIN_DATO' ? 'var(--faint)' : 'var(--cat' . ($i % 8 + 1) . ')';
};
$intercambiable = $conColumnas && $dimensiones[$f['filas']]['columna'];
?>
<div class="page-head">
  <div>
    <div class="page-title">Reportes</div>
    <div class="page-desc">Consolidados dinámicos para el área, sin redigitar Excel<?= $esRegistrador ? ' · tu establecimiento' : '' ?></div>
  </div>
</div>

<nav class="pestanas reporte-pestanas" aria-label="Tipo de reporte">
  <a class="pestana<?= $pestana === 'consolidado' ? ' on' : '' ?>" href="<?= e($url('/reportes')) ?>">Consolidado</a>
  <a class="pestana<?= $pestana === 'formatos' ? ' on' : '' ?>" href="/reportes?vista=formatos">Formatos MINSA<span class="cnt mono">2</span></a>
</nav>

<?php if ($pestana === 'formatos'): ?>
  <div class="formatos">
    <div class="card formato">
      <span class="codigo-formato mono">Z21 · Planilla</span>
      <h3><?= e(RegistroBusquedaActivaVih::NOMBRE) ?></h3>
      <p>Se arma con las fichas «Gestante con VIH y niño expuesto» del periodo.</p>
      <a class="btn btn-primary" href="/reportes/busqueda-activa-vih">Abrir planilla</a>
    </div>
    <div class="card formato">
      <span class="codigo-formato mono">P96 · Listado semanal</span>
      <h3><?= e(ListadoMuerteFetalNeonatal::NOMBRE) ?></h3>
      <p>Listado semanal de la ficha «Muerte fetal y neonatal», con la notificación de semana sin casos.</p>
      <a class="btn btn-primary" href="/reportes/muerte-fetal-neonatal">Abrir listado</a>
    </div>
  </div>
<?php else: ?>
  <form method="get" action="/reportes" class="card reporte-filtros" id="filtrosReporte">
    <?php if ($f['modo'] === 'grafico'): ?><input type="hidden" name="modo" value="grafico"><?php endif; ?>
    <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true">Aplicar</button>
    <div class="rf">
      <label for="rfEnf">Enfermedad</label>
      <div class="filtro-caja"><select id="rfEnf" name="enfermedad_id" data-auto>
        <option value="">Todas las enfermedades</option>
        <?php foreach ($enfermedades as $enf): ?>
          <option value="<?= (int) $enf['id'] ?>" <?= seleccionado((string) ($f['enfermedad_id'] ?? ''), $enf['id']) ?>><?= e($enf['nombre']) ?> · <?= e($enf['cie10']) ?></option>
        <?php endforeach; ?>
      </select></div>
    </div>
    <div class="rf rf-cruce">
      <div>
        <label for="rfFilas">Filas</label>
        <div class="filtro-caja"><select id="rfFilas" name="filas" data-auto>
          <?php foreach ($dimensiones as $clave => $dim): if ($clave === $f['columnas']) continue; ?>
            <option value="<?= $clave ?>" <?= seleccionado($f['filas'], $clave) ?>><?= e($dim['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select></div>
      </div>
      <a class="intercambiar<?= $intercambiable ? '' : ' deshabilitado' ?>" <?= $intercambiable ? 'href="' . e($url('/reportes', ['filas' => $f['columnas'], 'columnas' => $f['filas']])) . '"' : 'aria-disabled="true"' ?>
         title="<?= $intercambiable ? 'Intercambiar filas y columnas' : e('«' . $dimensiones[$f['filas']]['etiqueta'] . '» tiene demasiados valores para usarse como columnas') ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 4v16M3 16l4 4 4-4M17 20V4M13 8l4-4 4 4"/></svg>
        <span class="sr-only">Intercambiar filas y columnas</span>
      </a>
      <div>
        <label for="rfCols">Columnas</label>
        <div class="filtro-caja"><select id="rfCols" name="columnas" data-auto>
          <option value="ninguna" <?= $conColumnas ? '' : 'selected' ?>>Ninguna (solo total)</option>
          <?php foreach ($dimensiones as $clave => $dim): if (!$dim['columna'] || $clave === $f['filas']) continue; ?>
            <option value="<?= $clave ?>" <?= seleccionado((string) $f['columnas'], $clave) ?>><?= e($dim['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select></div>
      </div>
    </div>
    <div class="rf">
      <span class="rf-label">Periodo</span>
      <div class="rf-periodo">
        <?php if ($f['periodo'] !== 'anio'): ?><input type="hidden" name="periodo" value="<?= e($f['periodo']) ?>"><?php endif; ?>
        <div class="seg-periodo" role="group" aria-label="Periodo">
          <?php foreach (['4' => '4 SE', '12' => '12 SE', 'anio' => 'Año', 'rango' => 'Rango'] as $valor => $etiqueta): ?>
            <button type="submit" name="periodo" value="<?= $valor ?>"<?= $f['periodo'] === (string) $valor ? ' class="on" aria-pressed="true"' : ' aria-pressed="false"' ?>><?= $etiqueta ?></button>
          <?php endforeach; ?>
        </div>
        <?php if ($f['periodo'] === 'rango'): ?>
          <span class="rango-se">
            SE <input type="number" name="se_desde" min="1" max="53" value="<?= $f['desde'][1] ?>" aria-label="Desde la SE" data-auto>
            · <input type="number" name="anio_desde" min="2000" max="2100" value="<?= $f['desde'][0] ?>" class="rango-anio" aria-label="Año desde" data-auto>
            a SE <input type="number" name="se_hasta" min="1" max="53" value="<?= $f['hasta'][1] ?>" aria-label="Hasta la SE" data-auto>
            · <input type="number" name="anio_hasta" min="2000" max="2100" value="<?= $f['hasta'][0] ?>" class="rango-anio" aria-label="Año hasta" data-auto>
          </span>
        <?php else: ?>
          <span class="periodo-txt mono"><?= e($f['etiquetaPeriodo']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <div class="card reporte-resultado">
    <div class="reporte-cab">
      <div>
        <h2><?= e($titulo) ?></h2>
        <p><?= number_format($c['total'], 0, ',', ' ') ?> ficha<?= $c['total'] === 1 ? '' : 's' ?> · excluye fichas anuladas<?= $esRegistrador ? ' · solo tu establecimiento' : '' ?></p>
      </div>
      <div class="spacer"></div>
      <div class="reporte-acciones">
        <nav class="seg-periodo vista-modo" aria-label="Vista">
          <a href="<?= e($url('/reportes', ['modo' => ''])) ?>" class="<?= $f['modo'] === 'tabla' ? 'on' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 4v16"/></svg>Tabla</a>
          <a href="<?= e($url('/reportes', ['modo' => 'grafico'])) ?>" class="<?= $f['modo'] === 'grafico' ? 'on' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 20V10M11 20V4M17 20v-7"/></svg>Gráfico</a>
        </nav>
        <a class="btn btn-ghost btn-sm" href="<?= e($url('/reportes/exportar', ['modo' => ''])) ?>">
          <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2.5v7M4 6.5l3 3 3-3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>Excel</a>
        <button type="button" class="btn btn-ghost btn-sm" onclick="window.print()">
          <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2.5v7M4 6.5l3 3 3-3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>PDF</button>
      </div>
    </div>

    <?php if ($c['total'] === 0): ?>
      <div class="reporte-vacio"><b>No hay fichas para estos filtros</b>Prueba con otra enfermedad o amplía el rango de semanas.</div>
    <?php elseif ($f['modo'] === 'grafico'): ?>
      <?php
      $datosGrafico = [
          'tipo'     => $piramide ? 'piramide' : ($f['filas'] === 'se' ? 'semanas' : 'barras'),
          'filas'    => array_map(fn($fila) => [
              'etiqueta' => Consolidado::etiqueta($f['filas'], $fila, $variosAnios),
              'total'    => $c['totalFila'][$fila] ?? 0,
              'partes'   => $conColumnas ? array_map(fn($k) => $c['celdas'][$fila][$k] ?? 0, $c['columnas']) : [$c['totalFila'][$fila] ?? 0],
          ], $c['filas']),
          'series'   => $conColumnas
              ? array_map(fn($k, $i) => ['etiqueta' => Consolidado::etiqueta($f['columnas'], $k), 'color' => $colorColumna($k, $i)], $c['columnas'], array_keys($c['columnas']))
              : [['etiqueta' => 'Casos', 'color' => 'var(--accent)']],
          'piramide' => $piramide,
          'nombreFila' => $dimensiones[$f['filas']]['etiqueta'],
      ];
      ?>
      <div class="grafico-reporte" id="graficoReporte" role="img" aria-label="<?= e($titulo) ?>"></div>
      <script type="application/json" id="datosGrafico"><?= json_encode($datosGrafico, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
      <?php if ($piramide && !$conColumnas): ?><p class="nota-grafico">Por etapa de vida, el gráfico se muestra como pirámide por sexo.</p><?php endif; ?>
      <script src="<?= e(asset('/js/grafico-reporte.js')) ?>"></script>
    <?php else: ?>
      <div class="tabla-cruzada-wrap">
        <table class="tabla-cruzada">
          <thead><tr>
            <th><?= e($dimensiones[$f['filas']]['etiqueta']) ?></th>
            <?php if ($conColumnas): foreach ($c['columnas'] as $i => $k): ?>
              <th class="n"><span class="col-cab"><i style="background:<?= $colorColumna($k, $i) ?>"></i><?= e(Consolidado::etiqueta($f['columnas'], $k)) ?></span></th>
            <?php endforeach; endif; ?>
            <th class="n">Total</th><th class="n">%</th>
          </tr></thead>
          <tbody>
            <?php foreach ($c['filas'] as $fila): $totalFila = $c['totalFila'][$fila] ?? 0; $etiquetaFila = Consolidado::etiqueta($f['filas'], $fila, $variosAnios); ?>
              <tr>
                <th title="<?= e($etiquetaFila) ?>"><?= e($etiquetaFila) ?></th>
                <?php if ($conColumnas): foreach ($c['columnas'] as $k): $v = $c['celdas'][$fila][$k] ?? 0; ?>
                  <td class="<?= $v ? '' : 'cero' ?>" style="<?= $sombra($v) ?>"><?= $v ?></td>
                <?php endforeach; endif; ?>
                <td class="total<?= $totalFila ? '' : ' cero' ?>" style="<?= $conColumnas ? '' : $sombra($totalFila) ?>"><?= $totalFila ?></td>
                <td class="<?= $totalFila ? '' : 'cero' ?>"><?= $c['total'] ? number_format($totalFila / $c['total'] * 100, 1, ',', '') : '0,0' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot><tr>
            <th>Total</th>
            <?php if ($conColumnas): foreach ($c['columnas'] as $k): ?><td><?= $c['totalColumna'][$k] ?? 0 ?></td><?php endforeach; endif; ?>
            <td><?= $c['total'] ?></td><td>100,0</td>
          </tr></tfoot>
        </table>
      </div>
    <?php endif; ?>
  </div>
  <script src="<?= e(asset('/js/reportes.js')) ?>"></script>
<?php endif; ?>
