<?php
/**
 * "Formato de registro diario para la vigilancia de febriles" del Área de
 * Epidemiología PNP (hoja FEBRILES, 2026-10-21). Las filas salen del registro
 * de febriles (App\Models\FormatoPnp::febriles); se puede ver un día o toda
 * la semana.
 */
use App\Models\FormatoPnp;

$queryFiltros = http_build_query([
    'anio'               => $filtros['anio'],
    'semana'             => $filtros['semana'],
    'establecimiento_id' => $filtros['establecimiento_id'] ?? '',
    'dia'                => $dia ?? '',
]);
$marca = fn(bool $marcado): string => $marcado ? 'X' : '';
$dato = fn(?string $valor): string => ($valor ?? '') !== '' ? e($valor) : '';
$sinEstablecimiento = !$filtros['establecimiento_id'];
$dias = [];
for ($d = new DateTimeImmutable($rangoSemana[0]); $d->format('Y-m-d') <= $rangoSemana[1]; $d = $d->modify('+1 day')) {
    $dias[] = $d->format('Y-m-d');
}
$nombresDia = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
?>
<style>
  .feb-hoja{padding:22px 24px 26px;color:var(--ink)}
  .feb-titulo{text-align:center;font-weight:700;font-size:15px;letter-spacing:.02em;margin:0 0 16px}
  .feb-encabezado{display:grid;grid-template-columns:auto minmax(0,1.6fr) auto minmax(0,1fr);border:1px solid var(--line);border-radius:6px;margin-bottom:16px;font-size:12.5px;overflow:hidden}
  .feb-encabezado > span{padding:7px 10px;border-bottom:1px solid var(--line);min-width:0}
  .feb-encabezado > .feb-rotulo{background:var(--surface-2);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--ink-2);white-space:nowrap}
  .feb-encabezado > span:nth-last-child(-n+4){border-bottom:0}
  .feb-tabla{width:100%;border-collapse:collapse}
  .listado-feb .feb-tabla thead th,.listado-feb .feb-tabla tbody td{border:1px solid var(--line);padding:5px 6px;font-size:11.5px;text-align:center;vertical-align:middle}
  .listado-feb .feb-tabla thead th{text-transform:none;letter-spacing:0;color:var(--ink-2);background:var(--surface-2);font-weight:600;font-size:10.5px}
  .listado-feb .feb-tabla tbody td.feb-izq{text-align:left}
  .feb-sub{display:block;font-size:10.5px;color:var(--muted);margin-top:2px}
  .feb-temp-alta{color:var(--s-confirmado);font-weight:700}
  .feb-dias{display:flex;flex-wrap:wrap;gap:6px;padding:0 18px 16px}
  .feb-dias a{padding:5px 10px;border:1px solid var(--line);border-radius:999px;font-size:12px;color:var(--ink-2);text-decoration:none}
  .feb-dias a.on{background:var(--accent-soft);border-color:var(--accent);color:var(--accent-deep);font-weight:600}
  .feb-firmas{display:flex;justify-content:space-around;gap:24px;margin-top:48px;font-size:11px;color:var(--ink-2)}
  .feb-firmas span{border-top:1px solid var(--ink-2);padding-top:6px;min-width:220px;text-align:center}
  @media (max-width:640px){.feb-encabezado{grid-template-columns:minmax(0,auto) minmax(0,1fr)}.feb-encabezado > .feb-rotulo{white-space:normal}.feb-encabezado > span:nth-last-child(-n+4){border-bottom:1px solid var(--line)}.feb-firmas{flex-direction:column;align-items:center}}
  @media print{
    @page{size:A4 landscape;margin:8mm}
    body{background:#fff !important}
    .sidebar,.topbar,.no-imprimir{display:none !important}
    .app{display:block !important}
    .view{padding:0 !important;margin:0 !important}
    .listado-feb{border:0 !important;box-shadow:none !important}
    .feb-hoja{padding:0}
    .listado-feb a{color:inherit;text-decoration:none}
  }
</style>

<div class="page-head no-imprimir">
  <div>
    <div class="page-title"><?= e(FormatoPnp::FEBRILES) ?></div>
    <div class="page-desc">Formato del Área de Epidemiología PNP, armado con el registro de febriles de la semana</div>
  </div>
  <div class="spacer"></div>
  <?php if ($febrilesId): ?><a class="btn btn-primary" href="/casos/nuevo?enfermedad_id=<?= (int) $febrilesId ?>">Registrar febril</a><?php endif; ?>
  <a class="btn btn-ghost" href="/reportes?vista=formatos">Volver a Reportes</a>
</div>

<div class="card no-imprimir" style="margin-bottom:16px">
  <form method="get" action="/reportes/febriles">
    <div class="report-controls">
      <div class="rc" style="grid-column:span 2">
        <label>Establecimiento</label>
        <div class="control">
          <select name="establecimiento_id" <?= $puedeElegirEstablecimiento ? '' : 'disabled' ?>>
            <?php if ($puedeElegirEstablecimiento): ?><option value="">Todos</option><?php endif; ?>
            <?php foreach ($establecimientos as $establecimiento): ?>
              <option value="<?= (int) $establecimiento['id'] ?>" <?= seleccionado($filtros['establecimiento_id'] ?? '', $establecimiento['id']) ?>><?= e($establecimiento['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="rc">
        <label>Año</label>
        <div class="control mono"><input type="number" name="anio" min="2000" max="<?= (int) date('Y') + 1 ?>" value="<?= (int) $filtros['anio'] ?>"></div>
      </div>
      <div class="rc">
        <label>Semana epidemiológica</label>
        <div class="control mono"><input type="number" name="semana" min="1" max="53" value="<?= (int) $filtros['semana'] ?>"></div>
      </div>
    </div>
    <div style="padding:0 18px 18px">
      <button type="submit" class="btn btn-primary">Aplicar filtros</button>
    </div>
  </form>
  <nav class="feb-dias" aria-label="Día">
    <?php $enlace = fn(?string $d): string => '/reportes/febriles?' . http_build_query(['anio' => $filtros['anio'], 'semana' => $filtros['semana'], 'establecimiento_id' => $filtros['establecimiento_id'] ?? '', 'dia' => $d ?? '']); ?>
    <a class="<?= $dia === null ? 'on' : '' ?>" href="<?= e($enlace(null)) ?>">Toda la semana</a>
    <?php foreach ($dias as $d): ?>
      <a class="<?= $dia === $d ? 'on' : '' ?>" href="<?= e($enlace($d)) ?>"><?= $nombresDia[(int) date('w', strtotime($d))] ?> <?= e(date('d/m', strtotime($d))) ?></a>
    <?php endforeach; ?>
  </nav>
</div>

<div class="card listado-feb">
  <div class="card-head no-imprimir">
    <div><h3>Formato de registro</h3><div class="sub"><?= count($casos) ?> febril(es) · <?= $dia ? e(fechaIsoADmy($dia)) : 'SE ' . (int) $filtros['semana'] . ' · ' . (int) $filtros['anio'] . ' (del ' . e(fechaIsoADmy($rangoSemana[0])) . ' al ' . e(fechaIsoADmy($rangoSemana[1])) . ')' ?></div></div>
    <div class="spacer"></div>
    <div class="reporte-acciones">
      <a class="btn btn-ghost btn-sm" href="/reportes/febriles/exportar?<?= e($queryFiltros) ?>">
        <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2.5v7M4 6.5l3 3 3-3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>Excel</a>
      <button type="button" class="btn btn-ghost btn-sm" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg>Imprimir / PDF</button>
    </div>
  </div>

  <div class="feb-hoja">
    <p class="feb-titulo"><?= e(mb_strtoupper(FormatoPnp::FEBRILES)) ?></p>
    <div class="feb-encabezado">
      <span class="feb-rotulo">Dirección de salud</span><span>DIRSAPOL</span>
      <span class="feb-rotulo">Semana N.°</span><span class="mono"><?= (int) $filtros['semana'] ?> · <?= (int) $filtros['anio'] ?><?= $dia ? ' · ' . e(fechaIsoADmy($dia)) : '' ?></span>
      <span class="feb-rotulo">Red de salud</span><span><?= $dato($encabezado['red']) ?></span>
      <span class="feb-rotulo">Establecimiento</span><span><?= $sinEstablecimiento ? 'Todos' : $dato($encabezado['establecimiento']) ?></span>
    </div>

    <div style="overflow-x:auto">
      <table class="feb-tabla">
        <thead>
          <tr>
            <th rowspan="2">N.°</th>
            <th colspan="3">Condición del usuario</th>
            <th rowspan="2">H.CL</th>
            <th rowspan="2">Fecha</th>
            <th rowspan="2" style="min-width:160px">Apellidos y nombres</th>
            <th rowspan="2">DNI</th>
            <th colspan="2">Edad</th>
            <th rowspan="2" style="min-width:120px">Dirección</th>
            <th rowspan="2">Distrito de residencia</th>
            <th rowspan="2">Temperatura</th>
            <th rowspan="2" style="min-width:120px">Dx CIE-10</th>
            <th rowspan="2">Servicio</th>
          </tr>
          <tr><th>Grado</th><th>ACT/RET</th><th>F</th><th>M</th><th>F</th></tr>
        </thead>
        <tbody>
          <?php if (!$casos): ?>
            <tr><td colspan="16" style="color:var(--muted);padding:18px">No hay febriles registrados para este filtro.</td></tr>
          <?php endif; ?>
          <?php foreach ($casos as $indice => $caso): ?>
            <tr>
              <td class="mono"><a href="/casos/<?= (int) $caso['id'] ?>" title="Abrir el registro <?= e($caso['codigo']) ?>"><?= $indice + 1 ?></a></td>
              <td><?= $dato($caso['grado']) ?></td>
              <td><?= $dato($caso['act_ret']) ?></td>
              <td style="font-weight:700"><?= $marca($caso['familiar']) ?></td>
              <td class="mono"><?= $dato($caso['historia']) ?></td>
              <td class="mono"><?= $dato(fechaIsoADmy($caso['fecha'])) ?></td>
              <td class="feb-izq"><?= e($caso['nombre']) ?><?php if ($sinEstablecimiento): ?><span class="feb-sub"><?= e(capitalizarNombre($caso['establecimiento'])) ?></span><?php endif; ?></td>
              <td class="mono"><?= $dato($caso['dni']) ?></td>
              <td class="mono"><?= $caso['sexo'] === 'M' ? $dato($caso['edad']) : '' ?></td>
              <td class="mono"><?= $caso['sexo'] === 'F' ? $dato($caso['edad']) : '' ?></td>
              <td class="feb-izq"><?= $dato($caso['direccion']) ?></td>
              <td><?= $dato($caso['distrito']) ?></td>
              <td class="mono<?= (float) $caso['temperatura'] >= 38 ? ' feb-temp-alta' : '' ?>"><?= $caso['temperatura'] !== '' ? e(number_format((float) $caso['temperatura'], 1)) . ' °C' : '' ?></td>
              <td class="feb-izq"><b class="mono"><?= $dato($caso['cie10']) ?></b><?php if ($caso['diagnostico'] !== ''): ?><span class="feb-sub"><?= e($caso['diagnostico']) ?></span><?php endif; ?></td>
              <td><?= $dato($caso['servicio']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="feb-firmas"><span>Firma y sello de la persona que notifica</span><span>Director o jefe del establecimiento</span></div>
  </div>
</div>
