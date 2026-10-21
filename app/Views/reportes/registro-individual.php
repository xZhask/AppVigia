<?php
/**
 * "Registro semanal de notificación epidemiológica individual" del Área de
 * Epidemiología PNP (hojas INDIV_TITULAR y FAMILIAR e INDIVIDUAL VIH_SIDA,
 * 2026-10-21). Mismas columnas que el Excel del área; las filas salen de las
 * fichas y registros de la semana (App\Models\FormatoPnp). Descanso médico y
 * fecha de alta salen de "Observaciones adicionales (PNP)" de la ficha.
 */
use App\Models\FormatoPnp;

$queryFiltros = http_build_query([
    'anio'               => $filtros['anio'],
    'semana'             => $filtros['semana'],
    'establecimiento_id' => $filtros['establecimiento_id'] ?? '',
]);
$base = $vih ? '/reportes/registro-individual-vih' : '/reportes/registro-individual';
$titulo = $vih ? FormatoPnp::VIH : FormatoPnp::INDIVIDUAL;
$marca = fn(bool $marcado): string => $marcado ? 'X' : '';
$dato = fn(?string $valor): string => ($valor ?? '') !== '' ? e($valor) : '';
$sinEstablecimiento = !$filtros['establecimiento_id'];
$totalColumnas = $vih ? 25 : 32;

?>
<style>
  .pnp-hoja{padding:22px 24px 26px;color:var(--ink)}
  .pnp-membrete{display:flex;justify-content:space-between;gap:16px;font-size:11.5px;font-weight:700;letter-spacing:.04em;color:var(--ink-2);margin-bottom:10px}
  .pnp-titulo{text-align:center;font-weight:700;font-size:15px;letter-spacing:.02em;margin:0 0 16px}
  .pnp-encabezado{display:grid;grid-template-columns:auto minmax(0,1.6fr) auto minmax(0,1fr);border:1px solid var(--line);border-radius:6px;margin-bottom:16px;font-size:12.5px;overflow:hidden}
  .pnp-encabezado > span{padding:7px 10px;border-bottom:1px solid var(--line);min-width:0}
  .pnp-encabezado > .pnp-rotulo{background:var(--surface-2);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--ink-2);white-space:nowrap}
  .pnp-encabezado > span:nth-last-child(-n+4){border-bottom:0}
  .pnp-tabla{width:100%;border-collapse:collapse}
  .listado-pnp .pnp-tabla thead th,.listado-pnp .pnp-tabla tbody td{border:1px solid var(--line);padding:5px 6px;font-size:11.5px;text-align:center;vertical-align:middle;white-space:normal}
  .listado-pnp .pnp-tabla thead th{text-transform:none;letter-spacing:0;color:var(--ink-2);background:var(--surface-2);font-weight:600;font-size:10.5px}
  .listado-pnp .pnp-tabla thead th.pnp-vertical{writing-mode:vertical-rl;transform:rotate(180deg);white-space:nowrap;padding:8px 3px;min-width:24px}
  .listado-pnp .pnp-tabla tbody td.pnp-izq{text-align:left}
  .pnp-marca{font-weight:700}
  .pnp-sub{display:block;font-size:10.5px;color:var(--muted);margin-top:2px}
  .pnp-notas{font-size:10.5px;color:var(--muted);margin:10px 0 0;line-height:1.55}
  .pnp-firmas{display:flex;justify-content:space-around;gap:24px;margin-top:48px;font-size:11px;color:var(--ink-2)}
  .pnp-firmas span{border-top:1px solid var(--ink-2);padding-top:6px;min-width:220px;text-align:center}
  .pnp-aviso{padding:14px 18px;font-size:13px;color:var(--ink-2)}
  @media (max-width:640px){.pnp-encabezado{grid-template-columns:minmax(0,auto) minmax(0,1fr)}.pnp-encabezado > .pnp-rotulo{white-space:normal}.pnp-encabezado > span:nth-last-child(-n+4){border-bottom:1px solid var(--line)}.pnp-firmas{flex-direction:column;align-items:center}}
  @media print{
    @page{size:A4 landscape;margin:8mm}
    body{background:#fff !important}
    .sidebar,.topbar,.no-imprimir{display:none !important}
    .app{display:block !important}
    .view{padding:0 !important;margin:0 !important}
    .listado-pnp{border:0 !important;box-shadow:none !important}
    .pnp-hoja{padding:0}
    .listado-pnp a{color:inherit;text-decoration:none}
    .listado-pnp .pnp-tabla thead th,.listado-pnp .pnp-tabla tbody td{font-size:8.5px;padding:2px 3px}
  }
</style>

<div class="page-head no-imprimir">
  <div>
    <div class="page-title"><?= e($titulo) ?></div>
    <div class="page-desc">Formato del Área de Epidemiología PNP, armado con <?= $vih ? 'las fichas de VIH/SIDA y gestante con VIH / niño expuesto' : 'las fichas MINSA y los registros de otras enfermedades' ?> notificados en la semana</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/reportes?vista=formatos">Volver a Reportes</a>
</div>

<div class="card no-imprimir" style="margin-bottom:16px">
  <form method="get" action="<?= e($base) ?>">
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
  <div class="card no-imprimir pnp-aviso" style="margin-bottom:16px">Sin establecimiento elegido el registro reúne todos los que puedes ver y el encabezado queda en blanco.</div>
<?php endif; ?>

<div class="card listado-pnp">
  <div class="card-head no-imprimir">
    <div><h3>Formato de notificación</h3><div class="sub"><?= count($casos) ?> caso(s) · SE <?= (int) $filtros['semana'] ?> · <?= (int) $filtros['anio'] ?> (del <?= e(fechaIsoADmy($rangoSemana[0])) ?> al <?= e(fechaIsoADmy($rangoSemana[1])) ?>)</div></div>
    <div class="spacer"></div>
    <div class="reporte-acciones">
      <a class="btn btn-ghost btn-sm" href="<?= e($base) ?>/exportar?<?= e($queryFiltros) ?>">
        <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2.5v7M4 6.5l3 3 3-3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>Excel</a>
      <button type="button" class="btn btn-ghost btn-sm" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg>Imprimir / PDF</button>
    </div>
  </div>

  <div class="pnp-hoja">
    <div class="pnp-membrete"><span>POLICÍA NACIONAL DEL PERÚ<br>DIRECCIÓN DE SALUD POLICIAL</span><span>ÁREA DE EPIDEMIOLOGÍA</span></div>
    <p class="pnp-titulo"><?= e(mb_strtoupper($titulo)) ?></p>

    <div class="pnp-encabezado">
      <span class="pnp-rotulo">Código de establecimiento</span><span class="mono"><?= $dato($encabezado['codigo']) ?></span>
      <span class="pnp-rotulo">Semana de notificación</span><span class="mono"><?= (int) $filtros['semana'] ?> · <?= (int) $filtros['anio'] ?></span>
      <span class="pnp-rotulo">Cabecera de red</span><span><?= $dato($encabezado['red']) ?></span>
      <span class="pnp-rotulo">DIRESA</span><span><?= $dato($encabezado['diresa']) ?></span>
      <span class="pnp-rotulo">Establecimiento</span><span><?= $sinEstablecimiento ? 'Todos' : $dato($encabezado['establecimiento']) ?></span>
      <span class="pnp-rotulo">Tipo de establecimiento</span><span class="mono"><?= $dato($encabezado['categoria']) ?></span>
    </div>

    <div style="overflow-x:auto">
      <table class="pnp-tabla">
        <thead>
          <tr>
            <th rowspan="2">N.°</th>
            <th colspan="3">Situación</th>
            <th rowspan="2" style="min-width:<?= $vih ? 70 : 150 ?>px"><?= $vih ? 'Iniciales del paciente' : 'Apellidos y nombres' ?></th>
            <th colspan="2">Edad</th>
            <th rowspan="2" class="pnp-vertical">N.° CIP</th>
            <th rowspan="2" class="pnp-vertical">N.° DNI</th>
            <th colspan="3">Lugar probable de infección</th>
            <th rowspan="2" style="min-width:110px">Dirección de residencia</th>
            <th rowspan="2" class="pnp-vertical">Diagnóstico CIE-10</th>
            <th colspan="3">Tipo de Dx</th>
            <th colspan="3">Protegido (vacuna)</th>
            <th colspan="3">Fecha de</th>
            <th colspan="2">Ficha de investig.</th>
            <th colspan="2">Tipo de vigilancia</th>
            <?php if (!$vih): ?>
              <th rowspan="2" style="min-width:90px">Unidad de origen</th>
              <th rowspan="2" style="min-width:80px">Situación del paciente</th>
              <th colspan="2">Descanso médico 1</th>
              <th colspan="2">Descanso médico 2</th>
              <th rowspan="2" class="pnp-vertical">Fecha de alta</th>
            <?php endif; ?>
          </tr>
          <tr>
            <th class="pnp-vertical">Grado</th><th class="pnp-vertical">ACT/RET</th><th class="pnp-vertical">F</th>
            <th>M</th><th>F</th>
            <th>Provincia</th><th>Distrito</th><th>Localidad</th>
            <th>P</th><th>C</th><th>D</th>
            <th>SI</th><th>NO</th><th>IG</th>
            <th class="pnp-vertical">Inicio de síntomas</th><th class="pnp-vertical">Difusión</th><th class="pnp-vertical">Notificación</th>
            <th>SI</th><th>NO</th>
            <th class="pnp-vertical">Vig. pasiva</th><th class="pnp-vertical">Bús. activa</th>
            <?php if (!$vih): ?><th class="pnp-vertical">Inicio</th><th class="pnp-vertical">Término</th><th class="pnp-vertical">Inicio</th><th class="pnp-vertical">Término</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (!$casos): ?>
            <tr><td colspan="<?= $totalColumnas ?>" style="color:var(--muted);padding:18px">No hay casos notificados en esta semana para este filtro.</td></tr>
          <?php endif; ?>
          <?php foreach ($casos as $indice => $caso): ?>
            <tr>
              <td class="mono"><a href="/casos/<?= (int) $caso['id'] ?>" title="Abrir <?= e($caso['codigo']) ?> · <?= e($caso['enfermedad']) ?>"><?= $indice + 1 ?></a></td>
              <td><?= $dato($caso['grado']) ?></td>
              <td><?= $dato($caso['act_ret']) ?></td>
              <td class="pnp-marca"><?= $marca($caso['familiar']) ?></td>
              <td class="pnp-izq"><?= e($caso['nombre']) ?><?php if ($sinEstablecimiento): ?><span class="pnp-sub"><?= e(capitalizarNombre($caso['establecimiento'])) ?></span><?php endif; ?></td>
              <td class="mono"><?= $caso['sexo'] === 'M' ? $dato($caso['edad']) : '' ?></td>
              <td class="mono"><?= $caso['sexo'] === 'F' ? $dato($caso['edad']) : '' ?></td>
              <td class="mono"><?= $dato($caso['cip']) ?></td>
              <td class="mono"><?= $dato($caso['dni']) ?></td>
              <td><?= $dato($caso['lugar_provincia']) ?></td>
              <td><?= $dato($caso['lugar_distrito']) ?></td>
              <td><?= $dato($caso['lugar_localidad']) ?></td>
              <td class="pnp-izq"><?= $dato($caso['direccion']) ?></td>
              <td class="mono"><?= $dato($caso['cie10']) ?></td>
              <td class="pnp-marca"><?= $marca($caso['tipo_dx'] === 'P') ?></td>
              <td class="pnp-marca"><?= $marca($caso['tipo_dx'] === 'C') ?></td>
              <td class="pnp-marca"><?= $marca($caso['tipo_dx'] === 'D') ?></td>
              <td class="pnp-marca"><?= $marca($caso['protegido'] === 'SI') ?></td>
              <td class="pnp-marca"><?= $marca($caso['protegido'] === 'NO') ?></td>
              <td class="pnp-marca"><?= $marca($caso['protegido'] === 'IG') ?></td>
              <td class="mono"><?= $dato(fechaIsoADmy($caso['inicio_sintomas'])) ?></td>
              <td class="mono"><?= $dato(fechaIsoADmy($caso['difusion'])) ?></td>
              <td class="mono"><?= $dato(fechaIsoADmy($caso['notificacion'])) ?></td>
              <td class="pnp-marca"><?= $marca($caso['investigacion'] === 'SI') ?></td>
              <td class="pnp-marca"><?= $marca($caso['investigacion'] === 'NO') ?></td>
              <td class="pnp-marca"><?= $marca($caso['vigilancia'] === 'PAS') ?></td>
              <td class="pnp-marca"><?= $marca($caso['vigilancia'] === 'ACT') ?></td>
              <?php if (!$vih): ?>
                <td><?= $dato($caso['unidad']) ?></td>
                <td><?= $dato($caso['situacion']) ?></td>
                <td class="mono"><?= $dato(fechaIsoADmy($caso['descanso1_inicio'])) ?></td>
                <td class="mono"><?= $dato(fechaIsoADmy($caso['descanso1_fin'])) ?></td>
                <td class="mono"><?= $dato(fechaIsoADmy($caso['descanso2_inicio'])) ?></td>
                <td class="mono"><?= $dato(fechaIsoADmy($caso['descanso2_fin'])) ?></td>
                <td class="mono"><?= $dato(fechaIsoADmy($caso['fecha_alta'])) ?></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="pnp-notas">
      Tipo de diagnóstico: <b>P</b>: probable · <b>C</b>: confirmado · <b>D</b>: descartado. F: familiar (derechohabiente).
      Difusión: fecha en que se hace saber al área de epidemiología del establecimiento (la de notificación).
    </p>
    <div class="pnp-firmas"><span>Firma y sello de la persona que notifica</span><span>Director o jefe del establecimiento</span></div>
  </div>
</div>
