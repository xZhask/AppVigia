<?php
/**
 * EDAs e IRAs para NOTI (CDC-MINSA), 2026-11-26. Las tablas tienen la forma
 * de los formularios semanales de NOTI ("Notificación EDAS" y "Notificación
 * IRAS") para copiar cada casilla; las calcula ConsolidadoPnp::noti() desde
 * los registros PNP de la semana. Una hoja por lugar probable de infección.
 */
use App\Models\ConsolidadoPnp;

$queryFiltros = http_build_query([
    'anio'               => $filtros['anio'],
    'semana'             => $filtros['semana'],
    'establecimiento_id' => $filtros['establecimiento_id'] ?? '',
]);
$sinEstablecimiento = !$filtros['establecimiento_id'];
$numero = function (array $tabla, string $fila, string $columna): string {
    $n = (int) ($tabla[$fila][$columna] ?? 0);
    return $n > 0 ? '<b>' . $n . '</b>' : '<span class="noti-cero">0</span>';
};
?>
<style>
  .noti-hoja{padding:20px 22px 24px}
  .noti-lugar{margin:22px 0 10px;font-size:13px;font-weight:600;color:var(--ink)}
  .noti-lugar:first-of-type{margin-top:4px}
  .noti-bloque{margin-bottom:28px}
  .noti-lugar small{display:block;font-weight:400;font-size:12px;color:var(--muted);margin-top:2px}
  .noti-edas{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
  .noti-tabla{width:100%;border-collapse:collapse;font-size:12.5px}
  .noti-tabla caption{text-align:left;font-weight:600;font-size:13px;padding:0 0 8px;color:var(--ink)}
  .noti-tabla th,.noti-tabla td{border:1px solid var(--line);padding:6px 8px;text-align:center}
  .noti-tabla thead th{background:var(--surface-2);font-size:10.5px;font-weight:600;color:var(--ink-2);text-transform:none;letter-spacing:0;white-space:normal}
  .noti-tabla tbody th{text-align:left;font-weight:600;white-space:nowrap;background:var(--surface-2)}
  .noti-tabla td.sin-casilla{background:repeating-linear-gradient(135deg,transparent 0 6px,var(--surface-2) 6px 7px)}
  .noti-tabla td{font-family:"IBM Plex Mono",monospace;font-size:13px}
  .noti-cero{color:var(--faint)}
  .noti-avisos{margin:0 0 16px;padding:12px 16px}
  .noti-avisos ul{margin:6px 0 0;padding-left:18px;font-size:13px}
  .noti-nota{font-size:12px;color:var(--muted);margin:14px 0 0;line-height:1.55}
  @media (max-width:900px){.noti-edas{grid-template-columns:1fr}}
  @media print{
    @page{size:A4 portrait;margin:10mm}
    .sidebar,.topbar,.no-imprimir{display:none !important}
    .app{display:block !important}
    .view{padding:0 !important}
    .noti-card{border:0 !important;box-shadow:none !important}
    .noti-lugar{break-before:auto}
    .noti-bloque{break-inside:avoid}
  }
</style>

<div class="page-head no-imprimir">
  <div>
    <div class="page-title">EDAs e IRAs para NOTI</div>
    <div class="page-desc">Las casillas de los formularios semanales de NOTI (CDC-MINSA), calculadas con los registros de EDA e IRA de la semana, para copiarlas</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/reportes?vista=formatos">Volver a Reportes</a>
</div>

<div class="card no-imprimir" style="margin-bottom:16px">
  <form method="get" action="/reportes/noti">
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
        <label>Semana</label>
        <div class="control mono"><input type="number" name="semana" min="1" max="53" value="<?= (int) $filtros['semana'] ?>"></div>
      </div>
    </div>
    <div style="padding:0 18px 18px">
      <button type="submit" class="btn btn-primary">Aplicar filtros</button>
    </div>
  </form>
</div>

<?php if ($sinEstablecimiento): ?>
  <div class="card no-imprimir noti-avisos">En NOTI se notifica por establecimiento: elige uno. Sin establecimiento, las tablas suman todos los que puedes ver.</div>
<?php endif; ?>

<?php if ($datos['sin_gravedad'] || $datos['sin_edad']): ?>
  <div class="card no-imprimir noti-avisos" style="border-color:var(--ns-warn, #c2410c)">
    <b>Faltan registros en las tablas: corrígelos antes de copiar a NOTI.</b>
    <ul>
      <?php if ($datos['sin_gravedad']): ?>
        <li>Neumonía en menor de 5 años sin indicar si es grave:
          <?php foreach ($datos['sin_gravedad'] as $i => $caso): ?><?= $i ? ', ' : '' ?><a href="/casos/<?= (int) $caso['id'] ?>/editar"><?= e($caso['codigo']) ?></a><?php endforeach; ?>.</li>
      <?php endif; ?>
      <?php if ($datos['sin_edad']): ?>
        <li>Sin edad ni fecha de nacimiento:
          <?php foreach ($datos['sin_edad'] as $i => $caso): ?><?= $i ? ', ' : '' ?><a href="/casos/<?= (int) $caso['id'] ?>/editar"><?= e($caso['codigo']) ?></a><?php endforeach; ?>.</li>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card noti-card">
  <div class="card-head no-imprimir">
    <div><h3><?= e($sinEstablecimiento ? 'Todos los establecimientos' : capitalizarNombre($encabezado['establecimiento'])) ?></h3>
      <div class="sub">SE <?= (int) $filtros['semana'] ?> · <?= (int) $filtros['anio'] ?> (del <?= e(fechaIsoADmy($rangoSemana[0])) ?> al <?= e(fechaIsoADmy($rangoSemana[1])) ?>) · <?= (int) $datos['total'] ?> atención(es)</div></div>
    <div class="spacer"></div>
    <div class="reporte-acciones">
      <a class="btn btn-ghost btn-sm" data-descarga href="/reportes/noti/exportar?<?= e($queryFiltros) ?>">
        <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2.5v7M4 6.5l3 3 3-3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>Excel</a>
      <button type="button" class="btn btn-ghost btn-sm" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg>Imprimir / PDF</button>
    </div>
  </div>

  <div class="noti-hoja">
    <?php if (!$datos['lugares']): ?>
      <p style="color:var(--muted);margin:8px 0">No hay EDAs ni IRAs registradas en esta semana para este filtro. En NOTI se notifica la semana en cero.</p>
    <?php endif; ?>

    <?php foreach ($datos['lugares'] as $lugar): ?>
      <div class="noti-bloque">
        <p class="noti-lugar">Lugar probable de infección: <?= e($lugar['nombre'] ?: 'no registrado') ?>
          <small><?= $lugar['nombre'] ? 'Departamento / provincia / distrito, como en NOTI.' : 'Estos registros no tienen lugar probable de infección: en NOTI elige el que corresponda.' ?> <?= (int) $lugar['total'] ?> atención(es).</small></p>

        <div class="noti-edas">
          <?php foreach (['acuosa' => 'Diarrea acuosa', 'disenterica' => 'Diarrea disentérica'] as $tipo => $titulo): ?>
            <table class="noti-tabla">
              <caption><?= e($titulo) ?></caption>
              <thead><tr><th>Edad</th><th>N.º de casos</th><th>N.º de hospitalizaciones</th><th>N.º de defunciones</th></tr></thead>
              <tbody>
                <?php foreach (ConsolidadoPnp::NOTI_EDADES_EDA as $clave => $etiqueta): ?>
                  <tr><th><?= e($etiqueta) ?></th>
                    <?php foreach (['casos', 'hosp', 'def'] as $medida): ?><td><?= $numero($lugar['eda'], $clave, "$tipo.$medida") ?></td><?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endforeach; ?>
        </div>

        <div style="overflow-x:auto">
          <table class="noti-tabla">
            <caption>IRAs</caption>
            <thead>
              <tr><th rowspan="2">Edad</th><th rowspan="2">IRAS</th><th colspan="5">Neumonías</th><th rowspan="2">SOB/ASMA</th></tr>
              <tr><th>Neum. no grave</th><th>Neum. grave</th><th>Hosp.</th><th>Def. intrahospitalarias</th><th>Def. extrahospitalarias</th></tr>
            </thead>
            <tbody>
              <?php foreach (ConsolidadoPnp::NOTI_FILAS_IRA as $clave => [$etiqueta, $columnas]): ?>
                <tr><th><?= e($etiqueta) ?></th>
                  <?php foreach (array_keys(ConsolidadoPnp::NOTI_COLUMNAS_IRA) as $columna): ?>
                    <?php if (in_array($columna, $columnas, true)): ?><td><?= $numero($lugar['ira'], $clave, $columna) ?></td><?php else: ?><td class="sin-casilla"></td><?php endif; ?>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>

    <p class="noti-nota">
      <b>Etnia, grupo étnico y procedencia</b> no se registran en VIGÍA: complétalos en NOTI.
      Cuenta los registros PNP de la semana con diagnóstico de diarrea acuosa (A01–A09 salvo A03), disentería (A03, A06.0), IRA, neumonía, SOB o asma, sin los descartados ni los anulados.
      Las fichas de cólera no van aquí: se notifican en la notificación individual.
      En menores de 2 meses toda neumonía es grave. Hosp. y defunciones son de las neumonías; Hosp. incluye las defunciones intrahospitalarias.
      <?php if ($datos['sin_casilla']): ?>
        NOTI no tiene casilla para <?= e(implode(', ', array_map(fn($k, $n) => $n . ' ' . ['IRA' => 'IRA', 'SOB' => 'SOB', 'ASMA' => 'asma'][$k], array_keys($datos['sin_casilla']), $datos['sin_casilla']))) ?> de 5 años a más: van en el consolidado semanal PNP.
      <?php endif; ?>
    </p>
  </div>
</div>
