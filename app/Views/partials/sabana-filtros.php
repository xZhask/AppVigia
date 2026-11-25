<?php
/**
 * Filtros de las sábanas DIRSAPOL (individual y consolidada, 2026-11-24/25):
 * año completo o rango de SE, red y establecimiento. Espera $accion (ruta de
 * la pantalla), $filtros, $redes y $establecimientos. Deja en $queryFiltros
 * la consulta para el enlace de descarga.
 */
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
  .sab-tabla .num{text-align:right}
  .sab-sub{display:block;font-size:11px;color:var(--muted)}
  .sab-nota{font-size:12px;color:var(--muted);padding:10px 18px 16px;margin:0;line-height:1.55}
  @media (max-width:900px){.sab-filtros{grid-template-columns:1fr 1fr}.sab-resumen{grid-template-columns:1fr}}
</style>

<form method="get" action="<?= e($accion) ?>" class="card" style="margin-bottom:16px">
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
