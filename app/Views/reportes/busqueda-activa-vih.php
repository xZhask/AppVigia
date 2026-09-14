<?php
/**
 * "Formulario de registro de casos de gestantes con VIH y niños nacidos
 * expuestos al VIH identificados por búsqueda activa institucional" (pág. 15
 * del PDF). Mismas columnas y notas que el papel; los datos salen de las
 * fichas Z21 (ver App\Models\RegistroBusquedaActivaVih).
 */
use App\Models\RegistroBusquedaActivaVih;

$servicios = RegistroBusquedaActivaVih::SERVICIOS;
$ordenServiciosResumen = RegistroBusquedaActivaVih::ORDEN_SERVICIOS_RESUMEN;
$hayServicioSinDato = ($resumen['GESTANTE']['servicio']['SIN_DATO'] + $resumen['NINO']['servicio']['SIN_DATO']) > 0;
$hayNotificacionSinDato = ($resumen['GESTANTE']['notificado']['SIN_DATO'] + $resumen['NINO']['notificado']['SIN_DATO']) > 0;
$queryFiltros = http_build_query([
    'establecimiento_id' => $filtros['establecimiento_id'] ?? '',
    'desde'              => $filtros['desde'],
    'hasta'              => $filtros['hasta'],
]);
$instituciones = ['MINSA' => 'MINSA', 'ESSALUD' => 'EsSalud', 'FFAA_SANIDAD' => 'FFAA/FFPP', 'PRIVADO' => 'Privado'];
$institucionMarcada = $encabezado['institucion'] !== '' ? ($instituciones[$encabezado['institucion']] ?? 'Otro') : null;
$marca = fn(bool $marcado): string => $marcado ? 'X' : '';
$dato = fn(?string $valor): string => ($valor ?? '') !== '' ? e($valor) : '<span class="rba-vacio">—</span>';
?>
<style>
  .rba-hoja{padding:22px 24px 26px;color:var(--ink)}
  .rba-titulo{text-align:center;font-weight:700;font-size:13.5px;letter-spacing:.02em;line-height:1.4;margin:0 auto 18px;max-width:760px}
  .rba-encabezado{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px 18px;margin-bottom:18px;font-size:12.5px}
  .rba-encabezado .rba-dato{display:flex;flex-direction:column;gap:3px;border-bottom:1px solid var(--line);padding-bottom:5px;min-width:0}
  .rba-encabezado .rba-dato > span:first-child{font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:var(--faint);font-weight:600}
  .rba-instituciones{display:flex;flex-wrap:wrap;gap:4px 10px}
  .rba-instituciones .rba-casilla{display:inline-block;width:13px;height:13px;border:1px solid var(--muted);border-radius:2px;text-align:center;line-height:12px;font-size:10px;font-weight:700;margin-right:3px;vertical-align:-2px}
  .rba-bloque{display:grid;grid-template-columns:minmax(0,1fr) 190px;gap:16px;margin-bottom:18px;align-items:start}
  .rba-periodo{border:1px solid var(--line);border-radius:8px;padding:10px 12px;font-size:12.5px;display:flex;flex-direction:column;gap:6px}
  .rba-periodo strong{font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:var(--faint)}
  .rba-tabla{width:100%;border-collapse:collapse}
  .registro-ba .rba-tabla thead th,.registro-ba .rba-tabla tbody td{border:1px solid var(--line);padding:6px 7px;font-size:12px;text-align:center;vertical-align:middle;white-space:normal}
  .registro-ba .rba-tabla thead th{text-transform:none;letter-spacing:0;color:var(--ink-2);background:var(--surface-2);font-weight:600;font-size:11.5px}
  .registro-ba .rba-tabla tbody td.rba-izq{text-align:left}
  .registro-ba .rba-tabla tbody tr:last-child td{border-bottom:1px solid var(--line)}
  .rba-marca{font-weight:700}
  .registro-ba .rba-tabla thead th.rba-nowrap{white-space:nowrap}
  .rba-vacio{color:var(--faint)}
  .rba-texto{font-size:12.5px;margin:0 0 8px;color:var(--ink-2)}
  .rba-notas{font-size:11px;color:var(--muted);margin:10px 0 0;line-height:1.5}
  .rba-aviso{font-size:12px;color:var(--muted);margin:0 0 14px}
  @media (max-width:900px){
    .rba-encabezado{grid-template-columns:repeat(2,minmax(0,1fr))}
    .rba-bloque{grid-template-columns:1fr}
  }
  @media print{
    @page{size:A4 landscape;margin:10mm}
    body{background:#fff !important}
    .sidebar,.topbar,.no-imprimir{display:none !important}
    .app{display:block !important}
    .view{padding:0 !important;margin:0 !important}
    .registro-ba{border:0 !important;box-shadow:none !important}
    .rba-hoja{padding:0}
    .registro-ba a{color:inherit;text-decoration:none}
  }
</style>

<div class="page-head no-imprimir">
  <div>
    <div class="page-title"><?= e(RegistroBusquedaActivaVih::NOMBRE) ?></div>
    <div class="page-desc">Se arma con las fichas «Gestante con VIH y niño expuesto» (Z21) notificadas en el periodo</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/reportes">Volver a Reportes</a>
</div>

<div class="card no-imprimir" style="margin-bottom:16px">
  <form method="get" action="/reportes/busqueda-activa-vih">
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
        <label>Desde (fecha de notificación)</label>
        <div class="control mono"><input type="date" name="desde" value="<?= e($filtros['desde']) ?>"></div>
      </div>
      <div class="rc">
        <label>Hasta</label>
        <div class="control mono"><input type="date" name="hasta" value="<?= e($filtros['hasta']) ?>"></div>
      </div>
    </div>
    <div style="padding:0 18px 18px">
      <button type="submit" class="btn btn-primary">Aplicar filtros</button>
    </div>
  </form>
</div>

<div class="card registro-ba">
  <div class="card-head no-imprimir">
    <div><h3>Formato de registro</h3><div class="sub"><?= count($casos) ?> caso(s) · <?= e(fechaIsoADmy($filtros['desde'])) ?> – <?= e(fechaIsoADmy($filtros['hasta'])) ?></div></div>
    <div class="spacer"></div>
    <a class="btn-quiet" href="/reportes/busqueda-activa-vih/exportar?<?= e($queryFiltros) ?>">
      <svg width="15" height="15" viewBox="0 0 15 15"><rect x="2" y="2.5" width="11" height="10" rx="1.5" stroke="currentColor" stroke-width="1.2" fill="none"/><path d="M5.5 5.5l4 4M9.5 5.5l-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Excel
    </a>
    <button type="button" class="btn-quiet" onclick="window.print()">
      <svg width="15" height="15" viewBox="0 0 15 15"><path d="M4 1.5h5l3 3v9H4z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round"/></svg>
      Imprimir / PDF
    </button>
  </div>

  <div class="rba-hoja">
    <div class="rba-titulo"><?= e(mb_strtoupper(RegistroBusquedaActivaVih::NOMBRE)) ?></div>

    <?php if (!$filtros['establecimiento_id']): ?>
      <p class="rba-aviso no-imprimir">Sin establecimiento elegido el formulario reúne todos los que puedes ver y el encabezado queda en blanco; elige uno para llenarlo.</p>
    <?php endif; ?>

    <div class="rba-encabezado">
      <div class="rba-dato"><span>DISA/DIRESA/GERESA</span><span><?= $dato($encabezado['diresa']) ?></span></div>
      <div class="rba-dato"><span>Red</span><span><?= $dato($encabezado['red']) ?></span></div>
      <div class="rba-dato"><span>Microrred</span><span title="El padrón de establecimientos no registra microrred"><?= $dato('') ?></span></div>
      <div class="rba-dato"><span>Institución</span>
        <span class="rba-instituciones">
          <?php foreach (['MINSA', 'EsSalud', 'FFAA/FFPP', 'Privado', 'Otro'] as $opcionInstitucion): ?>
            <span><span class="rba-casilla"><?= $marca($institucionMarcada === $opcionInstitucion) ?></span><?= e($opcionInstitucion) ?></span>
          <?php endforeach; ?>
        </span>
      </div>
      <div class="rba-dato" style="grid-column:span 2"><span>Establecimiento de salud</span><span><?= $filtros['establecimiento_id'] ? $dato($encabezado['establecimiento']) : 'Todos' ?></span></div>
      <div class="rba-dato"><span>Departamento</span><span><?= $dato($encabezado['departamento']) ?></span></div>
      <div class="rba-dato"><span>Provincia</span><span><?= $dato($encabezado['provincia']) ?></span></div>
      <div class="rba-dato"><span>Distrito</span><span><?= $dato($encabezado['distrito']) ?></span></div>
    </div>

    <div class="rba-bloque">
      <div style="overflow-x:auto">
        <table class="rba-tabla">
          <thead>
            <tr>
              <th rowspan="2">Total de casos identificados</th>
              <th rowspan="2">Total</th>
              <th colspan="<?= 3 + ($hayServicioSinDato ? 1 : 0) ?>">Según servicio de captación</th>
              <th colspan="<?= 2 + ($hayNotificacionSinDato ? 1 : 0) ?>">Según estado de notificación al sistema de vigilancia</th>
            </tr>
            <tr>
              <?php foreach ($ordenServiciosResumen as $codigoServicio): ?><th><?= e($servicios[$codigoServicio]['larga']) ?></th><?php endforeach; ?>
              <?php if ($hayServicioSinDato): ?><th>Sin dato</th><?php endif; ?>
              <th>Notificados</th><th>No notificados</th>
              <?php if ($hayNotificacionSinDato): ?><th>Sin dato</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach (['GESTANTE' => 'Gestantes con VIH', 'NINO' => 'Niños nacidos expuestos'] as $tipoResumen => $etiquetaResumen): $filaResumen = $resumen[$tipoResumen]; ?>
              <tr>
                <td class="rba-izq"><?= e($etiquetaResumen) ?></td>
                <td class="mono"><strong><?= (int) $filaResumen['total'] ?></strong></td>
                <?php foreach ($ordenServiciosResumen as $codigoServicio): ?><td class="mono"><?= (int) $filaResumen['servicio'][$codigoServicio] ?></td><?php endforeach; ?>
                <?php if ($hayServicioSinDato): ?><td class="mono"><?= (int) $filaResumen['servicio']['SIN_DATO'] ?></td><?php endif; ?>
                <td class="mono"><?= (int) $filaResumen['notificado']['SI'] ?></td>
                <td class="mono"><?= (int) $filaResumen['notificado']['NO'] ?></td>
                <?php if ($hayNotificacionSinDato): ?><td class="mono"><?= (int) $filaResumen['notificado']['SIN_DATO'] ?></td><?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="rba-periodo">
        <strong>Periodo de búsqueda activa</strong>
        <span>Desde: <span class="mono"><?= e(fechaIsoADmy($filtros['desde'])) ?></span></span>
        <span>Hasta: <span class="mono"><?= e(fechaIsoADmy($filtros['hasta'])) ?></span></span>
      </div>
    </div>

    <p class="rba-texto">Registrar en la tabla siguiente todos los casos identificados en el periodo:</p>
    <div style="overflow-x:auto">
      <table class="rba-tabla">
        <thead>
          <tr>
            <th rowspan="2" class="rba-nowrap">N.°</th>
            <th rowspan="2">Código del paciente</th>
            <th rowspan="2">N.° de Historia clínica</th>
            <th rowspan="2">Edad</th>
            <th rowspan="2">Tipo de edad<sup>1</sup></th>
            <th rowspan="2">Sexo</th>
            <th colspan="3">Servicio</th>
            <th colspan="4">Clasificación de caso<sup>2</sup></th>
            <th rowspan="2">Fecha de defunción<sup>3</sup> (dd/mm/aaaa)</th>
            <th colspan="2">Notificado</th>
            <th rowspan="2">Observaciones</th>
          </tr>
          <tr>
            <?php foreach ($servicios as $servicio): ?><th><?= e($servicio['corta']) ?></th><?php endforeach; ?>
            <th>1</th><th>2</th><th>3</th><th>4</th>
            <th>Sí</th><th>No</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($casos)): ?>
            <tr><td colspan="17" style="color:var(--muted);padding:18px">No hay fichas Z21 notificadas en el periodo para este filtro.</td></tr>
          <?php endif; ?>
          <?php foreach ($casos as $indice => $caso): ?>
            <tr>
              <td class="mono"><a href="/casos/<?= (int) $caso['id'] ?>" title="Abrir la ficha <?= e($caso['codigo_ficha']) ?>"><?= $indice + 1 ?></a></td>
              <td class="mono"><?= $dato($caso['codigo']) ?></td>
              <td class="mono"><?= $dato($caso['historia_clinica']) ?></td>
              <td class="mono"><?= $caso['edad'] !== null ? (int) $caso['edad'] : $dato('') ?></td>
              <td><?= $dato($caso['tipo_edad']) ?></td>
              <td><?= $dato($caso['sexo']) ?></td>
              <?php foreach (array_keys($servicios) as $codigoServicio): ?><td class="rba-marca"><?= $marca($caso['servicio'] === $codigoServicio) ?></td><?php endforeach; ?>
              <?php foreach ([1, 2, 3, 4] as $numeroClasificacion): ?><td class="rba-marca"><?= $marca($caso['clasificacion'] === $numeroClasificacion) ?></td><?php endforeach; ?>
              <td class="mono"><?= $dato(fechaIsoADmy($caso['fecha_defuncion'])) ?></td>
              <td class="rba-marca"><?= $marca($caso['notificado'] === 'SI') ?></td>
              <td class="rba-marca"><?= $marca($caso['notificado'] === 'NO') ?></td>
              <td class="rba-izq"><?= e($caso['observaciones']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="rba-notas"><sup>1</sup> Tipo de edad: días (d), meses (m), años (a); <sup>2</sup> Clasificación de caso: Gestante con VIH (1), Aborto (2), Mortinato (3), Niño nacido expuesto al VIH (4); <sup>3</sup> Fecha de defunción de gestante, niño expuesto, de aborto o mortinato.</p>
  </div>
</div>
