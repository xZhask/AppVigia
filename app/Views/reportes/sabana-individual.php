<?php
/**
 * Sábana individual de Epidemiología DIRSAPOL (2026-11-24, fase 1 de
 * docs/propuesta-sabanas-dirsapol.md). La pantalla muestra el resumen y una
 * vista previa; la sábana completa, con todas sus columnas, es el Excel.
 */
$limite = 300;
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
?>
<div class="page-head">
  <div>
    <div class="page-title">Sábana individual de notificación epidemiológica</div>
    <div class="page-desc">Un caso por fila, de todas las IPRESS, para el Área de Epidemiología DIRSAPOL</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/reportes?vista=formatos">Volver a Reportes</a>
</div>

<?php $accion = '/reportes/sabana-individual'; require __DIR__ . '/../partials/sabana-filtros.php'; ?>

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

