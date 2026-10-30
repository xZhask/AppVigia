<?php
/**
 * "Registro semanal de notificación epidemiológica consolidada" del Área de
 * Epidemiología PNP (hoja CONSOLIDADA TITULAR, 2026-10-21): EDA y cólera, IRA
 * con asma y SOB, y neumonía de 5 años a más, contados de los casos
 * registrados en la semana (App\Models\ConsolidadoPnp).
 */
use App\Models\ConsolidadoPnp;

$queryFiltros = http_build_query([
    'anio'               => $filtros['anio'],
    'semana'             => $filtros['semana'],
    'establecimiento_id' => $filtros['establecimiento_id'] ?? '',
]);
$dato = fn(?string $valor): string => ($valor ?? '') !== '' ? e($valor) : '';
$sinEstablecimiento = !$filtros['establecimiento_id'];
$columnas = ConsolidadoPnp::columnas();
$titulos = [
    'eda'      => 'Consolidado semanal de casos de EDA y cólera',
    'ira'      => 'Consolidado semanal de IRA, asma y SOB',
    'neumonia' => 'Neumonía (5 años a más)',
];

// Una tabla del formato: cabecera de 2 o 3 niveles, una fila por situación,
// provincia y distrito de residencia, y la fila de totales.
$tabla = function (string $nombre) use ($columnas, $datos, $titulos, $dato): void {
    $grupos = $columnas[$nombre];
    // reset() pide una variable: encadenarlo deja un NOTICE en cada carga.
    $primerGrupo = reset($grupos);
    $tresNiveles = is_array(reset($primerGrupo));
    $claves = ConsolidadoPnp::clavesDe($grupos);
    $niveles = $tresNiveles ? 3 : 2;
    $totales = array_fill_keys($claves, 0);
    ?>
    <h4 class="cpn-subtitulo"><?= e(mb_strtoupper($titulos[$nombre])) ?></h4>
    <div style="overflow-x:auto">
      <table class="cpn-tabla">
        <thead>
          <tr>
            <th colspan="2">Situac.</th>
            <th colspan="2"><?= $nombre === 'eda' ? 'Procedencia de los casos' : 'Residencia' ?></th>
            <?php foreach ($grupos as $grupo => $contenido): ?>
              <th colspan="<?= count(ConsolidadoPnp::clavesDe($contenido)) ?>"><?= e($grupo) ?></th>
            <?php endforeach; ?>
          </tr>
          <?php if ($tresNiveles): ?>
          <tr>
            <th rowspan="2">T</th><th rowspan="2">F</th><th rowspan="2">Provincia</th><th rowspan="2">Distrito</th>
            <?php foreach ($grupos as $contenido): foreach ($contenido as $subgrupo => $cols): ?>
              <th colspan="<?= count($cols) ?>"><?= e($subgrupo) ?></th>
            <?php endforeach; endforeach; ?>
          </tr>
          <tr>
            <?php foreach ($grupos as $contenido): foreach ($contenido as $cols): foreach (array_keys($cols) as $etiqueta): ?>
              <th class="cpn-edad"><?= e($etiqueta) ?></th>
            <?php endforeach; endforeach; endforeach; ?>
          </tr>
          <?php else: ?>
          <tr>
            <th>T</th><th>F</th><th>Provincia</th><th>Distrito</th>
            <?php foreach ($grupos as $cols): foreach (array_keys($cols) as $etiqueta): ?>
              <th class="cpn-edad"><?= e($etiqueta) ?></th>
            <?php endforeach; endforeach; ?>
          </tr>
          <?php endif; ?>
        </thead>
        <tbody>
          <?php if (!$datos[$nombre]): ?>
            <tr><td colspan="<?= 4 + count($claves) ?>" class="cpn-vacio">Sin casos en la semana.</td></tr>
          <?php endif; ?>
          <?php foreach ($datos[$nombre] as $fila): ?>
            <tr>
              <td class="cpn-marca"><?= $fila['situacion'] === 'T' ? 'X' : '' ?></td>
              <td class="cpn-marca"><?= $fila['situacion'] === 'F' ? 'X' : '' ?></td>
              <td class="cpn-izq"><?= $dato($fila['provincia']) ?></td>
              <td class="cpn-izq"><?= $dato($fila['distrito']) ?></td>
              <?php foreach ($claves as $clave): $n = $fila['n'][$clave] ?? 0; $totales[$clave] += $n; ?>
                <td class="mono<?= $n ? ' cpn-con' : '' ?>"><?= $n ?: '' ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
          <?php if ($datos[$nombre]): ?>
            <tr class="cpn-total">
              <td colspan="4" class="cpn-izq">Total</td>
              <?php foreach ($claves as $clave): ?><td class="mono"><?= $totales[$clave] ?: '' ?></td><?php endforeach; ?>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
<?php };
?>
<style>
  .cpn-hoja{padding:22px 24px 26px;color:var(--ink)}
  .cpn-membrete{display:flex;justify-content:space-between;gap:16px;font-size:11.5px;font-weight:700;letter-spacing:.04em;color:var(--ink-2);margin-bottom:10px}
  .cpn-titulo{text-align:center;font-weight:700;font-size:15px;letter-spacing:.02em;margin:0 0 16px}
  .cpn-subtitulo{font-size:12px;font-weight:700;letter-spacing:.04em;color:var(--ink-2);margin:22px 0 8px}
  .cpn-encabezado{display:grid;grid-template-columns:auto minmax(0,1.6fr) auto minmax(0,1fr);border:1px solid var(--line);border-radius:6px;margin-bottom:6px;font-size:12.5px;overflow:hidden}
  .cpn-encabezado > span{padding:7px 10px;border-bottom:1px solid var(--line);min-width:0}
  .cpn-encabezado > .cpn-rotulo{background:var(--surface-2);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--ink-2);white-space:nowrap}
  .cpn-encabezado > span:nth-last-child(-n+4){border-bottom:0}
  .cpn-tabla{width:100%;border-collapse:collapse}
  .listado-cpn .cpn-tabla thead th,.listado-cpn .cpn-tabla tbody td{border:1px solid var(--line);padding:4px 5px;font-size:11px;text-align:center;vertical-align:middle}
  .listado-cpn .cpn-tabla thead th{text-transform:none;letter-spacing:0;color:var(--ink-2);background:var(--surface-2);font-weight:600;font-size:10.5px}
  .listado-cpn .cpn-tabla thead th.cpn-edad{font-weight:500;white-space:nowrap}
  .listado-cpn .cpn-tabla tbody td.cpn-izq{text-align:left}
  .cpn-marca{font-weight:700}
  .cpn-con{font-weight:700;color:var(--ink)}
  .cpn-total td{background:var(--surface-2);font-weight:700}
  .cpn-vacio{color:var(--muted);padding:12px !important}
  .cpn-notas{font-size:10.5px;color:var(--muted);margin:12px 0 0;line-height:1.55}
  .cpn-firmas{display:flex;justify-content:space-around;gap:24px;margin-top:48px;font-size:11px;color:var(--ink-2)}
  .cpn-firmas span{border-top:1px solid var(--ink-2);padding-top:6px;min-width:220px;text-align:center}
  .cpn-aviso{padding:14px 18px;font-size:13px;color:var(--ink-2)}
  @media (max-width:640px){.cpn-encabezado{grid-template-columns:minmax(0,auto) minmax(0,1fr)}.cpn-encabezado > .cpn-rotulo{white-space:normal}.cpn-encabezado > span:nth-last-child(-n+4){border-bottom:1px solid var(--line)}.cpn-firmas{flex-direction:column;align-items:center}}
  @media print{
    @page{size:A4 landscape;margin:8mm}
    body{background:#fff !important}
    .sidebar,.topbar,.no-imprimir{display:none !important}
    .app{display:block !important}
    .view{padding:0 !important;margin:0 !important}
    .listado-cpn{border:0 !important;box-shadow:none !important}
    .cpn-hoja{padding:0}
    .listado-cpn .cpn-tabla thead th,.listado-cpn .cpn-tabla tbody td{font-size:8px;padding:2px}
  }
</style>

<div class="page-head no-imprimir">
  <div>
    <div class="page-title"><?= e(ConsolidadoPnp::NOMBRE) ?></div>
    <div class="page-desc">Formato del Área de Epidemiología PNP, contado de los casos de EDA, cólera, IRA, neumonía, SOB y asma registrados en la semana</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/reportes?vista=formatos">Volver a Reportes</a>
</div>

<div class="card no-imprimir" style="margin-bottom:16px">
  <form method="get" action="/reportes/consolidado-pnp">
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
        <label>Semana de notificación</label>
        <div class="control mono"><input type="number" name="semana" min="1" max="53" value="<?= (int) $filtros['semana'] ?>"></div>
      </div>
    </div>
    <div style="padding:0 18px 18px">
      <button type="submit" class="btn btn-primary">Aplicar filtros</button>
    </div>
  </form>
</div>

<?php if ($sinEstablecimiento): ?>
  <div class="card no-imprimir cpn-aviso" style="margin-bottom:16px">Sin establecimiento elegido el consolidado reúne todos los que puedes ver y el encabezado queda en blanco.</div>
<?php endif; ?>

<div class="card listado-cpn">
  <div class="card-head no-imprimir">
    <div><h3>Formato consolidado</h3><div class="sub"><?= (int) $datos['total'] ?> caso(s) contado(s) · SE <?= (int) $filtros['semana'] ?> · <?= (int) $filtros['anio'] ?> (del <?= e(fechaIsoADmy($rangoSemana[0])) ?> al <?= e(fechaIsoADmy($rangoSemana[1])) ?>)</div></div>
    <div class="spacer"></div>
    <div class="reporte-acciones">
      <a class="btn btn-ghost btn-sm" href="/reportes/consolidado-pnp/exportar?<?= e($queryFiltros) ?>">
        <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2.5v7M4 6.5l3 3 3-3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>Excel</a>
      <button type="button" class="btn btn-ghost btn-sm" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg>Imprimir / PDF</button>
    </div>
  </div>

  <div class="cpn-hoja">
    <div class="cpn-membrete"><span>POLICÍA NACIONAL DEL PERÚ<br>DIRECCIÓN DE SANIDAD POLICIAL</span><span>OFICINA DE EPIDEMIOLOGÍA</span></div>
    <p class="cpn-titulo"><?= e(mb_strtoupper(ConsolidadoPnp::NOMBRE)) ?></p>

    <div class="cpn-encabezado">
      <span class="cpn-rotulo">Código de establecimiento</span><span class="mono"><?= $dato($encabezado['codigo']) ?></span>
      <span class="cpn-rotulo">Semana de notificación</span><span class="mono"><?= (int) $filtros['semana'] ?> · <?= (int) $filtros['anio'] ?></span>
      <span class="cpn-rotulo">Cabecera de red</span><span><?= $dato($encabezado['red']) ?></span>
      <span class="cpn-rotulo">Tipo de establecimiento</span><span class="mono"><?= $dato($encabezado['categoria']) ?></span>
      <span class="cpn-rotulo">Establecimiento</span><span><?= $sinEstablecimiento ? 'Todos' : $dato($encabezado['establecimiento']) ?></span>
      <span class="cpn-rotulo">DIRESA</span><span><?= $dato($encabezado['diresa']) ?></span>
    </div>

    <?php $tabla('eda'); ?>
    <?php $tabla('ira'); ?>
    <?php $tabla('neumonia'); ?>

    <p class="cpn-notas">
      T: titular (efectivo PNP) · F: familiar (derechohabiente) · sin marca: particular. Procedencia: provincia y distrito de residencia del paciente.<br>
      Se cuentan los casos no descartados de la semana: los del registro de otras enfermedades por su diagnóstico CIE-10 y los sospechosos de cólera de la ficha A00. En un menor de 2 meses toda neumonía cuenta como grave.<br>
      Neumonía: incluye neumonía, lobar, basal, atípica, bacteriana, viral, bronconeumonía y neumonía fulminante (CIE-10 J12-J18).
    </p>
    <div class="cpn-firmas"><span>Firma y sello de la persona que notifica</span><span>Director o jefe del establecimiento</span></div>
  </div>
</div>
