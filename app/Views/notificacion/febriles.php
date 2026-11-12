<?php
// Cumplimiento del registro de febriles (2026-11-11): IPRESS × día de la SE.
// Ver NotificacionSemanalController::febriles() y FebrilesSinCasos::estadoVarios().
use App\Models\FebrilesSinCasos;

$inicio = inicioSemanaEpidemiologica($anio, $semana);
$rangoSe = $inicio->format('d/m') . ' al ' . $inicio->modify('+6 days')->format('d/m/Y');
$enCurso = [$anio, $semana] === [$actual['anio'], $actual['semana']];
$anterior = semanaEpidemiologica($inicio->modify('-7 days')->format('Y-m-d'));
$siguiente = semanaEpidemiologica($inicio->modify('+7 days')->format('Y-m-d'));
$haySiguiente = [$siguiente['anio'], $siguiente['semana']] <= [$actual['anio'], $actual['semana']];
$filtros = ['anio' => $anio, 'semana' => $semana, 'estado' => $filtroEstado, 'red' => $esJefeRed ? '' : ($redId ?? ''), 'q' => $busqueda];
$url = fn(array $cambios = []) => '/notificacion-semanal/febriles?' . http_build_query(array_filter(array_merge($filtros, $cambios), fn($v) => $v !== '' && $v !== null));
$nombresDia = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$hoy = date('Y-m-d');
$tile = function (string $clave, string $etiqueta, ?string $color, int $valor) use ($filtroEstado, $url, $total): string {
    $on = $filtroEstado === $clave;
    return '<a class="card ns-kpi' . ($on ? ' on' : '') . '" href="' . e($url(['estado' => $clave])) . '"' . ($on ? ' aria-current="true"' : '') . '>'
        . '<div class="eyebrow">' . ($color ? '<i style="background:' . $color . '"></i>' : '') . e($etiqueta) . '</div>'
        . '<div class="ns-kpi-v mono">' . $valor . ($clave !== '' ? '<small> / ' . $total . '</small>' : '') . '</div></a>';
};
$celda = function (array $e): array {
    // [clase, contenido, texto para lector y title]
    $tarde = $e['tardia'] ? ', fuera de plazo' : '';
    return match ($e['estado']) {
        'CON'  => ['CON', (string) $e['febriles'], $e['febriles'] . ' febril' . ($e['febriles'] === 1 ? '' : 'es')],
        'SIN'  => ['SIN' . ($e['tardia'] ? ' tarde' : ''), '0', 'sin febriles' . $tarde],
        'ATN'  => ['ATN' . ($e['tardia'] ? ' tarde' : ''), '—', 'sin atención' . ($e['auto'] ? ' (' . mb_strtolower($e['auto']) . ')' : '') . $tarde],
        'PEND' => ['PEND', '!', 'pendiente'],
        default => ['FUT', '', 'aún no llega'],
    };
};
?>

<div class="page-head">
  <div>
    <div class="page-title">Cumplimiento del registro de febriles</div>
    <div class="page-desc">Cada día debe tener sus febriles registrados o notificarse sin febriles · plazo: hasta el día siguiente</div>
  </div>
  <div class="spacer"></div>
  <nav class="ns-semana" aria-label="Semana epidemiológica">
    <a class="ns-flecha" href="<?= e($url(['anio' => $anterior['anio'], 'semana' => $anterior['semana']])) ?>" aria-label="Semana anterior">‹</a>
    <div class="ns-semana-lbl"><b class="mono">SE <?= $semana ?> · <?= $anio ?></b><small><?= $rangoSe ?><?= $enCurso ? ' · en curso' : '' ?></small></div>
    <?php if ($haySiguiente): ?><a class="ns-flecha" href="<?= e($url(['anio' => $siguiente['anio'], 'semana' => $siguiente['semana']])) ?>" aria-label="Semana siguiente">›</a><?php else: ?><span class="ns-flecha off" aria-hidden="true">›</span><?php endif; ?>
  </nav>
</div>

<div class="ns-tabs">
  <a href="<?= e('/notificacion-semanal?' . http_build_query(['anio' => $anio, 'semana' => $semana])) ?>">Cierre semanal</a>
  <a class="on" aria-current="page" href="<?= e($url()) ?>">Febriles por día</a>
</div>

<div class="ns-kpis">
  <?= $tile('', 'Establecimientos', null, $total) ?>
  <?= $tile('D', 'Al día', 'var(--accent)', $conteo['D']) ?>
  <?= $tile('P', 'Con días pendientes', 'var(--s-confirmado)', $conteo['P']) ?>
  <div class="card ns-kpi"><div class="eyebrow"><i style="background:var(--ns-warn)"></i>Días pendientes · fuera de plazo</div><div class="ns-kpi-v mono"><?= $conteo['dias'] ?><small> · <?= $conteo['tardias'] ?></small></div></div>
</div>

<div class="card ns-tabla-card">
  <div class="tarjeta-cab ns-pad">
    <div><h3>Establecimientos</h3>
      <div class="sub">Las IPRESS que deben días, arriba<?= FebrilesSinCasos::OBLIGATORIA_DESDE > $inicio->format('Y-m-d') ? ' · obligatorio desde el ' . e(fechaIsoADmy(FebrilesSinCasos::OBLIGATORIA_DESDE)) . ' (SE 46)' : '' ?></div></div>
    <div class="spacer"></div>
    <a class="btn btn-ghost ns-btn-sm" data-descarga href="<?= e(str_replace('/notificacion-semanal/febriles?', '/notificacion-semanal/febriles/exportar?', $url())) ?>">
      <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2.5v7M4 6.5l3 3 3-3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>Excel</a>
  </div>
  <form class="ns-filtros" method="get" action="/notificacion-semanal/febriles">
    <input type="hidden" name="anio" value="<?= $anio ?>"><input type="hidden" name="semana" value="<?= $semana ?>">
    <?php if ($filtroEstado !== ''): ?><input type="hidden" name="estado" value="<?= e($filtroEstado) ?>"><?php endif; ?>
    <div class="filtro-caja ns-buscar"><input type="search" name="q" value="<?= e($busqueda) ?>" placeholder="Buscar establecimiento o red" aria-label="Buscar establecimiento"></div>
    <?php if ($redes): ?>
    <div class="filtro-caja"><select name="red" aria-label="Red" onchange="this.form.submit()">
      <option value="">Todas las redes</option>
      <?php foreach ($redes as $red): ?><option value="<?= (int) $red['id'] ?>" <?= seleccionado((string) $redId, $red['id']) ?>><?= e($red['nombre']) ?></option><?php endforeach; ?>
    </select></div>
    <?php endif; ?>
    <span class="spacer"></span>
    <span class="ns-nota"><?= count($filas) ?> de <?= $total ?></span>
  </form>
  <div class="fc-leyenda">
    <span><i class="fc CON">3</i>Con febriles (cantidad)</span><span><i class="fc SIN">0</i>Sin febriles</span><span><i class="fc ATN">—</i>Sin atención (nivel I)</span>
    <span><i class="fc PEND">!</i>Pendiente</span><span><i class="fc SIN tarde">0</i>Fuera de plazo</span>
  </div>
  <div class="ns-desplazable">
  <table class="ns-est fc-tabla">
    <thead><tr><th>Establecimiento</th>
      <?php foreach ($dias as $d): ?><th class="fc-dia<?= $d === $hoy ? ' hoy' : '' ?>"><?= $nombresDia[(int) date('w', strtotime($d))] ?><small><?= date('d/m', strtotime($d)) ?></small></th><?php endforeach; ?>
      <th class="n">Pend.</th></tr></thead>
    <tbody>
      <?php if (!$filas): ?>
        <tr><td colspan="<?= count($dias) + 2 ?>" class="ns-vacio">Sin establecimientos con este filtro.</td></tr>
      <?php endif; ?>
      <?php foreach ($filas as $fila): ?>
        <tr>
          <td><div class="pt-name"><?= e(nombreEnOracion($fila['nombre'])) ?></div><div class="cell-sub"><?= e($fila['red_nombre'] ?? 'Sin red') ?><?= $fila['categoria'] ? ' · ' . e($fila['categoria']) : '' ?></div></td>
          <?php foreach ($dias as $d): [$clase, $contenido, $texto] = $celda($fila['dias'][$d]); $etiqueta = $nombresDia[(int) date('w', strtotime($d))] . ' ' . date('d/m', strtotime($d)) . ': ' . $texto; ?>
            <td class="fc-dia<?= $d === $hoy ? ' hoy' : '' ?>">
              <?php if ($clase === 'FUT'): ?>
                <span class="sr-only"><?= e($etiqueta) ?></span>
              <?php elseif (!$esJefeRed): ?>
                <a class="fc <?= $clase ?>" href="<?= e('/reportes/febriles?' . http_build_query(['anio' => $anio, 'semana' => $semana, 'establecimiento_id' => $fila['id'], 'dia' => $d])) ?>" title="<?= e($etiqueta) ?>" aria-label="<?= e($etiqueta) ?>"><?= e($contenido) ?></a>
              <?php else: ?>
                <span class="fc <?= $clase ?>" title="<?= e($etiqueta) ?>" role="img" aria-label="<?= e($etiqueta) ?>"><?= e($contenido) ?></span>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
          <td class="n mono"><?= $fila['pendientes'] ?: '—' ?><?php if ($fila['tardias']): ?><div class="ns-tardias"><?= $fila['tardias'] ?> tarde</div><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
