<?php
/**
 * "Ficha de notificación de muerte fetal y neonatal" (pág. 28 del PDF, Anexo 1
 * de la R.M. 279-2009/MINSA). Mismas columnas y notas que el papel; las filas
 * salen de las fichas P96 notificadas en la semana (ver
 * App\Models\ListadoMuerteFetalNeonatal). Una semana sin casos se notifica
 * desde acá (notificación negativa).
 */
use App\Core\Csrf;
use App\Models\ListadoMuerteFetalNeonatal;

$queryFiltros = http_build_query([
    'anio'               => $filtros['anio'],
    'semana'             => $filtros['semana'],
    'establecimiento_id' => $filtros['establecimiento_id'] ?? '',
]);
$marca = fn(bool $marcado): string => $marcado ? 'X' : '';
$dato = fn(?string $valor): string => ($valor ?? '') !== '' ? e($valor) : '';
$fechaHora = fn(?string $timestamp): string => $timestamp ? date('d/m/Y H:i', strtotime($timestamp)) : '';
$sinEstablecimiento = !$filtros['establecimiento_id'];
$totalColumnas = 22;
?>
<style>
  .mfn-hoja{padding:22px 24px 26px;color:var(--ink)}
  .mfn-titulo{text-align:center;font-weight:700;font-size:15px;letter-spacing:.02em;margin:0}
  .mfn-subtitulo{text-align:center;font-weight:600;font-size:11.5px;letter-spacing:.03em;color:var(--ink-2);margin:4px 0 16px}
  .mfn-encabezado{display:grid;grid-template-columns:auto minmax(0,1.6fr) auto minmax(0,1fr) auto minmax(0,.7fr);border:1px solid var(--line);border-radius:6px;margin-bottom:16px;font-size:12.5px;overflow:hidden}
  .mfn-encabezado > span{padding:7px 10px;border-bottom:1px solid var(--line);min-width:0}
  .mfn-encabezado > .mfn-rotulo{background:var(--surface-2);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--ink-2);white-space:nowrap}
  .mfn-encabezado > span:nth-last-child(-n+4){border-bottom:0}
  .mfn-tabla{width:100%;border-collapse:collapse}
  .listado-mfn .mfn-tabla thead th,.listado-mfn .mfn-tabla tbody td{border:1px solid var(--line);padding:5px 6px;font-size:11.5px;text-align:center;vertical-align:middle;white-space:normal}
  .listado-mfn .mfn-tabla thead th{text-transform:none;letter-spacing:0;color:var(--ink-2);background:var(--surface-2);font-weight:600;font-size:10.5px}
  .listado-mfn .mfn-tabla thead th.mfn-vertical{writing-mode:vertical-rl;transform:rotate(180deg);white-space:nowrap;padding:8px 3px;min-width:26px}
  .listado-mfn .mfn-tabla tbody td.mfn-izq{text-align:left}
  .listado-mfn .mfn-tabla tbody tr:last-child td{border-bottom:1px solid var(--line)}
  .mfn-marca{font-weight:700}
  .mfn-sub{display:block;font-size:10.5px;color:var(--muted);margin-top:2px}
  .mfn-notas{font-size:10.5px;color:var(--muted);margin:10px 0 0;line-height:1.55}
  .mfn-notas b{color:var(--ink-2)}
  .mfn-aviso{display:flex;flex-wrap:wrap;align-items:center;gap:10px 16px;padding:14px 18px;font-size:13px}
  .mfn-aviso p{margin:0;flex:1 1 320px;color:var(--ink-2)}
  .mfn-aviso form{margin:0}
  .mfn-sin-casos{font-weight:700;letter-spacing:.06em;padding:14px !important}
  @media print{
    @page{size:A4 landscape;margin:8mm}
    body{background:#fff !important}
    .sidebar,.topbar,.no-imprimir{display:none !important}
    .app{display:block !important}
    .view{padding:0 !important;margin:0 !important}
    .listado-mfn{border:0 !important;box-shadow:none !important}
    .mfn-hoja{padding:0}
    .listado-mfn a{color:inherit;text-decoration:none}
    .listado-mfn .mfn-tabla thead th,.listado-mfn .mfn-tabla tbody td{font-size:9.5px;padding:3px 4px}
  }
</style>

<div class="page-head no-imprimir">
  <div>
    <div class="page-title"><?= e(ListadoMuerteFetalNeonatal::NOMBRE) ?></div>
    <div class="page-desc">Listado semanal del establecimiento, armado con las fichas «Muerte fetal y neonatal» (P96) notificadas en la semana</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/reportes">Volver a Reportes</a>
</div>

<div class="card no-imprimir" style="margin-bottom:16px">
  <form method="get" action="/reportes/muerte-fetal-neonatal">
    <div class="report-controls">
      <div class="rc" style="grid-column:span 2">
        <label>Establecimiento notificante</label>
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
</div>

<?php if ($sinEstablecimiento): ?>
  <div class="card no-imprimir mfn-aviso" style="margin-bottom:16px">
    <p>Sin establecimiento elegido el listado reúne todos los que puedes ver y el encabezado queda en blanco. Elige uno para llenarlo o para notificar la semana sin casos.</p>
  </div>
<?php elseif ($negativa && $casos): ?>
  <div class="card no-imprimir mfn-aviso" style="margin-bottom:16px;border-left:3px solid var(--s-confirmado)">
    <p><b>Esta semana se había notificado sin casos</b> (el <?= e($fechaHora($negativa['creado_en'])) ?>, por <?= e($negativa['usuario_nombre']) ?>), pero ya tiene <?= count($casos) ?> caso(s) registrado(s). Esa notificación no se imprime; anúlala para que no quede contradiciendo el listado.</p>
    <?php if ($puedeAnularNegativa): ?>
      <form method="post" action="/reportes/muerte-fetal-neonatal/sin-casos/anular">
        <?= Csrf::campoOculto() ?>
        <input type="hidden" name="anio" value="<?= (int) $filtros['anio'] ?>"><input type="hidden" name="semana" value="<?= (int) $filtros['semana'] ?>"><input type="hidden" name="establecimiento_id" value="<?= (int) $filtros['establecimiento_id'] ?>">
        <button type="submit" class="btn btn-ghost">Anular notificación sin casos</button>
      </form>
    <?php endif; ?>
  </div>
<?php elseif ($negativa): ?>
  <div class="card no-imprimir mfn-aviso" style="margin-bottom:16px;border-left:3px solid var(--accent)">
    <p><b>Semana notificada sin casos</b> el <?= e($fechaHora($negativa['creado_en'])) ?>, por <?= e($negativa['usuario_nombre']) ?>.</p>
    <?php if ($puedeAnularNegativa): ?>
      <form method="post" action="/reportes/muerte-fetal-neonatal/sin-casos/anular" onsubmit="return confirm('¿Anular la notificación de semana sin casos?')">
        <?= Csrf::campoOculto() ?>
        <input type="hidden" name="anio" value="<?= (int) $filtros['anio'] ?>"><input type="hidden" name="semana" value="<?= (int) $filtros['semana'] ?>"><input type="hidden" name="establecimiento_id" value="<?= (int) $filtros['establecimiento_id'] ?>">
        <button type="submit" class="btn btn-ghost">Anular</button>
      </form>
    <?php endif; ?>
  </div>
<?php elseif ($puedeNotificarNegativa): ?>
  <div class="card no-imprimir mfn-aviso" style="margin-bottom:16px">
    <p>No hay muertes fetales ni neonatales notificadas por este establecimiento en la SE <?= (int) $filtros['semana'] ?> de <?= (int) $filtros['anio'] ?>. Si no tuvo casos, notifícalo para que la semana no quede como silenciosa.</p>
    <form method="post" action="/reportes/muerte-fetal-neonatal/sin-casos" onsubmit="return confirm('¿Notificar la SE <?= (int) $filtros['semana'] ?> de <?= (int) $filtros['anio'] ?> sin casos de muerte fetal ni neonatal?')">
      <?= Csrf::campoOculto() ?>
      <input type="hidden" name="anio" value="<?= (int) $filtros['anio'] ?>"><input type="hidden" name="semana" value="<?= (int) $filtros['semana'] ?>"><input type="hidden" name="establecimiento_id" value="<?= (int) $filtros['establecimiento_id'] ?>">
      <button type="submit" class="btn btn-primary">Notificar semana sin casos</button>
    </form>
  </div>
<?php endif; ?>

<div class="card listado-mfn">
  <div class="card-head no-imprimir">
    <div><h3>Formato de notificación</h3><div class="sub"><?= count($casos) ?> caso(s) · SE <?= (int) $filtros['semana'] ?> · <?= (int) $filtros['anio'] ?> (del <?= e(fechaIsoADmy($rangoSemana[0])) ?> al <?= e(fechaIsoADmy($rangoSemana[1])) ?>)</div></div>
    <div class="spacer"></div>
    <a class="btn-quiet" href="/reportes/muerte-fetal-neonatal/exportar?<?= e($queryFiltros) ?>">
      <svg width="15" height="15" viewBox="0 0 15 15"><rect x="2" y="2.5" width="11" height="10" rx="1.5" stroke="currentColor" stroke-width="1.2" fill="none"/><path d="M5.5 5.5l4 4M9.5 5.5l-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Excel
    </a>
    <button type="button" class="btn-quiet" onclick="window.print()">
      <svg width="15" height="15" viewBox="0 0 15 15"><path d="M4 1.5h5l3 3v9H4z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round"/></svg>
      Imprimir / PDF
    </button>
  </div>

  <div class="mfn-hoja">
    <p class="mfn-titulo"><?= e(mb_strtoupper(ListadoMuerteFetalNeonatal::NOMBRE)) ?></p>
    <p class="mfn-subtitulo"><?= e(mb_strtoupper(ListadoMuerteFetalNeonatal::SUBSISTEMA)) ?></p>

    <div class="mfn-encabezado">
      <span class="mfn-rotulo">DISA / DIRESA / GERESA</span><span><?= $dato($encabezado['diresa']) ?></span>
      <span class="mfn-rotulo">Distrito</span><span><?= $dato($encabezado['distrito']) ?></span>
      <span class="mfn-rotulo">Semana epidemiológica</span><span class="mono"><?= (int) $filtros['semana'] ?> · <?= (int) $filtros['anio'] ?></span>
      <span class="mfn-rotulo">Establecimiento notificante</span><span><?= $sinEstablecimiento ? 'Todos' : $dato($encabezado['establecimiento']) ?></span>
      <span class="mfn-rotulo">Responsable</span><span style="grid-column:span 3"><?= $dato($responsable) ?></span>
    </div>

    <div style="overflow-x:auto">
      <table class="mfn-tabla">
        <thead>
          <tr>
            <th rowspan="2" style="white-space:nowrap">N.°</th>
            <th rowspan="2" style="min-width:150px">Apellidos y nombres</th>
            <th rowspan="2" class="mfn-vertical">Sexo (1)</th>
            <th rowspan="2" class="mfn-vertical">Edad gestacional (semanas)</th>
            <th colspan="2">Nacimiento</th>
            <th colspan="2">Muerte</th>
            <th rowspan="2" class="mfn-vertical">Peso al nacer (gramos)</th>
            <th colspan="2">Tipo de muerte</th>
            <th rowspan="2" style="min-width:120px">Causa básica de muerte (2)</th>
            <th rowspan="2" class="mfn-vertical">Diagnóstico CIE10</th>
            <th rowspan="2" class="mfn-vertical">N.° días de estancia hospitalaria *</th>
            <th rowspan="2" class="mfn-vertical">Lugar del parto (3)</th>
            <th colspan="3">Momento de ocurrencia del fallecimiento</th>
            <th rowspan="2" class="mfn-vertical">Lugar de la muerte (4)</th>
            <th colspan="3">Residencia habitual de la madre</th>
          </tr>
          <tr>
            <th class="mfn-vertical">Fecha</th><th class="mfn-vertical">Hora</th>
            <th class="mfn-vertical">Fecha</th><th class="mfn-vertical">Hora</th>
            <th class="mfn-vertical">Fetal</th><th class="mfn-vertical">Neonatal</th>
            <th class="mfn-vertical">Anteparto</th><th class="mfn-vertical">Intra-parto</th><th class="mfn-vertical">Post-parto</th>
            <th>Dpto.</th><th>Prov.</th><th>Distrito</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$casos && $negativa): ?>
            <tr><td colspan="<?= $totalColumnas ?>" class="mfn-sin-casos">SIN CASOS · Notificación negativa del <?= e($fechaHora($negativa['creado_en'])) ?></td></tr>
          <?php elseif (!$casos): ?>
            <tr><td colspan="<?= $totalColumnas ?>" style="color:var(--muted);padding:18px">No hay fichas de muerte fetal o neonatal notificadas en esta semana para este filtro.</td></tr>
          <?php endif; ?>
          <?php foreach ($casos as $indice => $caso): ?>
            <tr>
              <td class="mono"><a href="/casos/<?= (int) $caso['id'] ?>" title="Abrir la ficha <?= e($caso['codigo_ficha']) ?>"><?= $indice + 1 ?></a></td>
              <td class="mfn-izq"><?= e($caso['apellidos_nombres']) ?><?php if ($sinEstablecimiento): ?><span class="mfn-sub"><?= e(capitalizarNombre($caso['establecimiento'])) ?></span><?php endif; ?></td>
              <td><?= $dato($caso['sexo']) ?></td>
              <td class="mono"><?= $dato($caso['edad_gestacional']) ?></td>
              <td class="mono"><?= $dato(fechaIsoADmy($caso['fecha_nacimiento'])) ?></td>
              <td class="mono"><?= $dato($caso['hora_nacimiento']) ?></td>
              <td class="mono"><?= $dato(fechaIsoADmy($caso['fecha_muerte'])) ?></td>
              <td class="mono"><?= $dato($caso['hora_muerte']) ?></td>
              <td class="mono"><?= $dato($caso['peso']) ?></td>
              <td class="mfn-marca"><?= $marca($caso['tipo'] === 'FETAL') ?></td>
              <td class="mfn-marca"><?= $marca($caso['tipo'] === 'NEONATAL') ?></td>
              <td class="mfn-izq"><?= $dato($caso['causa_basica']) ?></td>
              <td class="mono"><?= $dato($caso['cie10']) ?></td>
              <td class="mono"><?= $dato($caso['dias_estancia']) ?></td>
              <td><?= $dato($caso['lugar_parto']) ?></td>
              <td class="mfn-marca"><?= $marca($caso['momento'] === 'ANTEPARTO') ?></td>
              <td class="mfn-marca"><?= $marca($caso['momento'] === 'INTRAPARTO') ?></td>
              <td class="mfn-marca"><?= $marca($caso['momento'] === 'POSTPARTO') ?></td>
              <td><?= $dato($caso['lugar_muerte']) ?></td>
              <td><?= $dato($caso['departamento']) ?></td>
              <td><?= $dato($caso['provincia']) ?></td>
              <td><?= $dato($caso['distrito']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="mfn-notas">
      Anexo 1 de la R.M. 279-2009/MINSA<br>
      * Número de días de estancia hospitalaria: consignar solo para los casos de muerte neonatal.<br>
      <b>(1)</b> SEXO: <b>F</b> = FEMENINO <b>M</b> = MASCULINO<br>
      <b>(2)</b> CAUSA BÁSICA DE MUERTE: Es la entidad que inicia la cadena de acontecimientos que conducen a la muerte fetal o neonatal (<b>CIE X</b>). Solo se anotará una causa que aparece como causa básica en el certificado de defunción<br>
      <b>(3)</b> LUGAR DEL PARTO: Colocar <b>PI</b> cuando es parto institucional y <b>PD</b> cuando sea parto domiciliario<br>
      <b>(4)</b> LUGAR DE LA MUERTE: Consignar <b>ES</b> cuando la muerte ocurrió en un establecimiento de Salud o <b>CC</b> cuando la muerte ocurrió en la comunidad
    </p>
  </div>
</div>
