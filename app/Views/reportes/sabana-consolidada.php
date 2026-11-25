<?php
/**
 * Sábana consolidada de EDA/IRA de Epidemiología DIRSAPOL (2026-11-25, fase 2
 * de docs/propuesta-sabanas-dirsapol.md). Cuenta los registros de VIGÍA; la
 * pantalla muestra el resumen y una vista previa, la sábana completa es el
 * Excel.
 */
$limite = 300;
$sumarPor = function (string $clave) use ($filas): array {
    $suma = [];
    foreach ($filas as $fila) {
        $suma[$fila[$clave]] = ($suma[$fila[$clave]] ?? 0) + $fila['casos'];
    }
    arsort($suma);
    return $suma;
};
$casos = array_sum(array_column($filas, 'casos'));
$porDx = $sumarPor('dx');
$porCondicion = $sumarPor('condicion');
$porEstablecimiento = array_slice($sumarPor('establecimiento'), 0, 8, true);
$nombresDx = ['DIARREA' => 'Diarrea aguda', 'DISENTERIA' => 'Disentería', 'COLERA' => 'Cólera', 'IRA' => 'IRA (no neumonía)', 'NEUMONIA' => 'Neumonía', 'SOB' => 'SOB', 'ASMA' => 'Asma'];
?>
<div class="page-head">
  <div>
    <div class="page-title">Sábana consolidada de EDA e IRA</div>
    <div class="page-desc">Registros de VIGÍA contados por semana, establecimiento, condición, diagnóstico y edad, para el Área de Epidemiología DIRSAPOL</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/reportes?vista=formatos">Volver a Reportes</a>
</div>

<?php $accion = '/reportes/sabana-consolidada'; require __DIR__ . '/../partials/sabana-filtros.php'; ?>

<div class="ns-kpis">
  <div class="card ns-kpi"><div class="eyebrow">Casos</div><div class="ns-kpi-v mono"><?= $casos ?></div></div>
  <div class="card ns-kpi"><div class="eyebrow">Establecimientos con casos</div><div class="ns-kpi-v mono"><?= count(array_unique(array_column($filas, 'establecimiento'))) ?></div></div>
  <div class="card ns-kpi"><div class="eyebrow">Hospitalizados</div><div class="ns-kpi-v mono"><?= array_sum(array_column($filas, 'hosp')) ?></div></div>
  <div class="card ns-kpi"><div class="eyebrow">Defunciones</div><div class="ns-kpi-v mono"><?= array_sum(array_column($filas, 'def')) ?></div></div>
</div>

<?php if ($filas): ?>
  <div class="sab-resumen">
    <div class="card"><div class="eyebrow">Diagnóstico</div><ul>
      <?php foreach ($porDx as $dx => $n): ?><li><span><?= e($nombresDx[$dx] ?? $dx) ?></span><b class="mono"><?= $n ?></b></li><?php endforeach; ?>
    </ul></div>
    <div class="card"><div class="eyebrow">Condición</div><ul>
      <?php foreach ($porCondicion as $condicion => $n): ?><li><span><?= e($condicion) ?></span><b class="mono"><?= $n ?></b></li><?php endforeach; ?>
    </ul></div>
    <div class="card"><div class="eyebrow">Establecimientos con más casos</div><ul>
      <?php foreach ($porEstablecimiento as $establecimiento => $n): ?><li><span><?= e(mb_strimwidth(capitalizarNombre((string) $establecimiento), 0, 44, '…')) ?></span><b class="mono"><?= $n ?></b></li><?php endforeach; ?>
    </ul></div>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-head">
    <div><h3>Vista previa</h3><div class="sub"><?= count($filas) > $limite ? 'Primeras ' . $limite . ' de ' . count($filas) . ' filas' : count($filas) . ' fila(s)' ?> · <?= $casos ?> caso(s)</div></div>
    <div class="spacer"></div>
    <a class="btn btn-primary btn-sm" data-descarga href="/reportes/sabana-consolidada/exportar?<?= e($queryFiltros) ?>">
      <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2.5v7M4 6.5l3 3 3-3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>Descargar Excel</a>
  </div>
  <div style="overflow-x:auto">
    <table class="sab-tabla">
      <thead><tr>
        <th>SE</th><th>Establecimiento</th><th>Condición</th><th>Dx</th><th>Edad</th>
        <th class="num">Casos</th><th class="num">Hosp.</th><th class="num">Def.</th><th>Cierre de la SE</th>
      </tr></thead>
      <tbody>
        <?php if (!$filas): ?>
          <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px">No hay casos de EDA, IRA, neumonía, SOB ni asma registrados en el periodo para este filtro.</td></tr>
        <?php endif; ?>
        <?php foreach (array_slice($filas, 0, $limite) as $f): ?>
          <tr>
            <td class="mono"><?= (int) $f['semana'] ?><span class="sab-sub"><?= e($f['mes']) ?> <?= (int) $f['anio'] ?></span></td>
            <td><?= e(capitalizarNombre($f['establecimiento'])) ?><span class="sab-sub"><?= e($f['red']) ?></span></td>
            <td><?= e($f['condicion']) ?></td>
            <td><?= e($f['dx']) ?></td>
            <td class="mono"><?= e($f['edad']) ?></td>
            <td class="mono num"><?= (int) $f['casos'] ?></td>
            <td class="mono num"><?= $f['hosp'] ?: '' ?></td>
            <td class="mono num"><?= $f['def'] ?: '' ?></td>
            <td class="mono"><?= $f['cierre'] ? e(fechaIsoADmy($f['cierre'])) : '<span class="sab-sub">Sin cerrar</span>' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="sab-nota">
    Registros PNP con diagnóstico de EDA, disentería, IRA, neumonía, SOB o asma, y fichas de cólera, no anulados ni descartados, por semana epidemiológica de notificación.
    Los grupos de edad son los del consolidado semanal. Hospitalizados incluye las defunciones intrahospitalarias.
    Red y establecimiento son los de VIGÍA, hasta que esté la tabla de equivalencias con las macrorregiones del Área.
  </p>
</div>
