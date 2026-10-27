<?php
use App\Core\Csrf;
use App\Models\Establecimiento;
use App\Models\IaasNorma;
use App\Models\Persona;

// Vista del mes (lo incluye ronda.php): la grilla del Anexo 4 por
// dispositivo y, en gineco-obstetricia y cirugía, la planilla de los Anexos
// 6 y 7. Clic en un día con el dispositivo instalado = marcar o desmarcar.
$mesTxt = IaasNorma::nombreMes($mes, $anio);
$primerDia = sprintf('%04d-%02d-01', $anio, $mes);
$mesAnt = date('Y-m', strtotime($primerDia . ' -1 month'));
$mesSig = date('Y-m', strtotime($primerDia . ' +1 month'));
$hayMesSig = $mesSig <= substr($hoy, 0, 7);
$cerrado = $cierre && $cierre['reabierto_en'] === null;
$editable = !$cerrado;
$diaSemana = fn(int $n) => (int) date('w', strtotime(sprintf('%04d-%02d-%02d', $anio, $mes, $n)));
$simbolo = ['E' => '✓', 'T' => '×', 'H' => '→', 'F' => '', '' => ''];
$enc = Establecimiento::encabezadoFormulario((int) $establecimiento['id']);
?>
<div class="iaas-dia-barra">
  <nav class="ns-semana iaas-dia-nav" aria-label="Mes">
    <a class="ns-flecha" href="<?= e($urlRonda(['vista' => 'mes', 'mes' => $mesAnt])) ?>" aria-label="Mes anterior">‹</a>
    <div class="ns-semana-lbl"><b><?= e($mesTxt) ?></b><small><?= $grilla['pacientes'] ?> paciente<?= $grilla['pacientes'] === 1 ? '' : 's' ?> vigilado<?= $grilla['pacientes'] === 1 ? '' : 's' ?></small></div>
    <?php if ($hayMesSig): ?><a class="ns-flecha" href="<?= e($urlRonda(['vista' => 'mes', 'mes' => $mesSig])) ?>" aria-label="Mes siguiente">›</a>
    <?php else: ?><span class="ns-flecha off" aria-hidden="true">›</span><?php endif; ?>
  </nav>
  <?php if ($cerrado): ?>
    <span class="ns-chip con" title="Cerrado por <?= e($cierre['usuario_nombre']) ?>"><i></i>Mes cerrado el <?= e(date('d/m/Y', strtotime($cierre['creado_en']))) ?></span>
  <?php else: ?>
    <span class="ns-chip sin"><i></i>Mes abierto</span>
  <?php endif; ?>
  <div class="spacer"></div>
  <a class="btn btn-quiet ns-btn-sm" href="<?= e('/iaas/indicadores?' . http_build_query(['mes' => sprintf('%04d-%02d', $anio, $mes)])) ?>">Indicadores del mes</a>
  <button type="button" class="btn btn-ghost ns-btn-sm" data-imprimir>Imprimir</button>
</div>

<div class="iaas-print-cab" aria-hidden="true">
  <div class="iaas-print-tit">Vigilancia epidemiológica de infecciones asociadas a la atención de salud</div>
  <div class="iaas-print-sub"><?= $grilla['tablas'] || !$procedimientosSala ? 'Formato de seguimiento de pacientes con factor de riesgo (dispositivos médicos) · Anexo 4' : '' ?></div>
  <div class="iaas-print-datos">
    <span>IPRESS: <b><?= e($enc['establecimiento']) ?></b></span>
    <span>Servicio: <b><?= e(IaasNorma::UPSS[$sala['upss']]) ?></b></span>
    <span>Sala: <b><?= e($sala['nombre']) ?></b></span>
    <span>Mes: <b><?= e($mesTxt) ?></b></span>
    <span>Pacientes vigilados en el mes: <b><?= $grilla['pacientes'] ?></b></span>
  </div>
</div>

<?php if ($dispositivosSala && !$grilla['tablas']): ?>
  <div class="card iaas-vacio"><h2>Sin dispositivos en <?= e(mb_strtolower($mesTxt)) ?></h2><p>En este mes nadie tuvo un dispositivo instalado en esta sala.</p></div>
<?php endif; ?>

<?php foreach ($grilla['tablas'] as $tipo => $filas):
  $totalTipo = array_sum(array_column($filas, 'total'));
?>
  <section class="card iaas-mes">
    <div class="tarjeta-cab iaas-mes-cab">
      <div><h3><span class="iaas-sigla"><?= e(IaasNorma::DISPOSITIVOS_SIGLA[$tipo]) ?></span><?= e(IaasNorma::DISPOSITIVOS[$tipo]) ?></h3>
        <div class="sub"><?= count($filas) ?> episodio<?= count($filas) === 1 ? '' : 's' ?> · <b class="mono"><?= $totalTipo ?></b> días de exposición en el mes</div></div>
    </div>
    <div class="iaas-grilla-wrap">
      <table class="iaas-grilla">
        <thead>
          <tr>
            <th class="fijo c1" scope="col">Cama</th>
            <th class="fijo c2" scope="col">Paciente</th>
            <?php for ($n = 1; $n <= $grilla['dias']; $n++): $w = $diaSemana($n); $f = sprintf('%04d-%02d-%02d', $anio, $mes, $n); ?>
              <th scope="col" class="dia<?= $w === 0 || $w === 6 ? ' fds' : '' ?><?= $f === $hoy ? ' hoy' : '' ?>"><?= $n ?></th>
            <?php endfor; ?>
            <th scope="col" class="n">Total días</th>
            <th scope="col" class="cen">Egreso</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filas as $fila): $i = $fila['internacion']; $d = $fila['dispositivo']; ?>
            <tr>
              <td class="fijo c1 mono"><?= e($i['n_cama'] ?? '') ?: '—' ?></td>
              <th class="fijo c2" scope="row"><span class="iaas-g-nom" title="<?= e(Persona::nombreCompleto($i)) ?>"><?= e(Persona::nombreCompleto($i)) ?></span>
                <small><?= e(implode(' · ', array_filter([
                    $i['sexo'],
                    $neonatal ? ($i['peso_nacer_g'] ? (int) $i['peso_nacer_g'] . ' g' : 'sin peso') : edadLegible($i['fecha_nac'], $i['fecha_ingreso'], true),
                    'ingr. ' . date('d/m', strtotime($i['fecha_ingreso'])),
                    $i['historia_clinica'] ? 'HC ' . $i['historia_clinica'] : null,
                ]))) ?></small></th>
              <?php foreach ($fila['celdas'] as $n => $c):
                $clase = 'c ' . ($c['sim'] ?: 'v') . ($diaSemana($n + 1) === 0 || $diaSemana($n + 1) === 6 ? ' fds' : '');
                $contenido = ($c['cont'] ? '<span class="cont">*</span>' : '') . '<span class="s">' . $simbolo[$c['sim']] . '</span>' . ($c['cod'] !== '' ? '<sup>' . e($c['cod']) . '</sup>' : '');
                $titulo = date('d/m', strtotime($c['fecha'])) . ': ' . ['E' => 'expuesto', 'T' => 'IAAS en tratamiento (no suma)', 'H' => 'hospitalizado sin el dispositivo', 'F' => 'falta marcar', '' => ''][$c['sim']];
              ?>
                <?php if ($c['editable'] && $editable): ?>
                  <td class="<?= $clase ?>"><button type="button" data-celda data-disp="<?= (int) $d['id'] ?>" data-fecha="<?= e($c['fecha']) ?>" data-on="<?= in_array($c['sim'], ['E', 'T'], true) ? '1' : '0' ?>" title="<?= e($titulo) ?> · clic para <?= in_array($c['sim'], ['E', 'T'], true) ? 'quitar la marca' : 'marcar' ?>" aria-label="<?= e($titulo) ?>"><?= $contenido ?></button></td>
                <?php else: ?>
                  <td class="<?= $clase ?>"<?= $titulo !== date('d/m', strtotime($c['fecha'])) . ': ' ? ' title="' . e($titulo) . '"' : '' ?>><?= $contenido ?></td>
                <?php endif; ?>
              <?php endforeach; ?>
              <td class="n mono tot" data-total><?= $fila['total'] ?></td>
              <td class="cen mono"><?= $i['fecha_egreso'] && $i['fecha_egreso'] <= $grilla['fin'] ? e($i['condicion_egreso'] . ' ' . date('d/m', strtotime($i['fecha_egreso']))) : '' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><th class="fijo c1"></th><th class="fijo c2" scope="row" colspan="1">Total de días</th><td colspan="<?= $grilla['dias'] ?>"></td><td class="n mono tot" data-total-tipo><?= $totalTipo ?></td><td></td></tr>
        </tfoot>
      </table>
    </div>
  </section>
<?php endforeach; ?>

<?php if ($grilla['tablas']): ?>
  <?php require __DIR__ . '/_leyenda.php'; ?>
<?php endif; ?>

<?php if ($procedimientosSala):
  // Columnas como el papel: cada procedimiento y, a su lado, sus infecciones (desde las fichas).
  $columnas = [];
  foreach ($procedimientosSala as $tipo) {
      $columnas[] = ['tipo' => $tipo, 'clave' => null, 'tit' => 'N.º de ' . mb_strtolower(IaasNorma::PROCEDIMIENTOS[$tipo])];
      if ($tipo === 'PARTO_VAGINAL' || $tipo === 'CESAREA') {
          $columnas[] = ['tipo' => $tipo, 'clave' => 'END|' . $tipo, 'tit' => 'Endometritis pos ' . ($tipo === 'CESAREA' ? 'cesárea' : 'parto vaginal')];
      }
      if ($tipo !== 'PARTO_VAGINAL') {
          $columnas[] = ['tipo' => $tipo, 'clave' => 'ISQ|' . $tipo, 'tit' => 'ISQ pos ' . mb_strtolower(IaasNorma::PROCEDIMIENTO_SINGULAR[$tipo])];
      }
  }
  $totales = array_fill(0, count($columnas), 0);
?>
  <section class="card iaas-mes iaas-anexo67">
    <div class="tarjeta-cab iaas-mes-cab">
      <div><h3><?= $sala['upss'] === 'GINECO_OBSTETRICIA' ? 'Formato de la vigilancia de IAAS · Gineco-obstetricia' : 'Formato de la vigilancia de IAAS · Cirugía' ?></h3>
        <div class="sub"><?= $sala['upss'] === 'GINECO_OBSTETRICIA' ? 'Anexo 6' : 'Anexo 7' ?> · las infecciones salen de las fichas IAAS, en la fecha del procedimiento · «—» = día sin registrar</div></div>
    </div>
    <div class="iaas-grilla-wrap">
      <table class="iaas-tabla-proc">
        <thead><tr><th scope="col">Día</th><?php foreach ($columnas as $col): ?><th scope="col" class="n<?= $col['clave'] ? ' inf' : '' ?>"><?= e($col['tit']) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php for ($n = 1; $n <= $grilla['dias']; $n++):
            $f = sprintf('%04d-%02d-%02d', $anio, $mes, $n);
            $registro = $procedimientosMes[$f] ?? null;
          ?>
            <tr class="<?= $f > $hoy ? 'fut' : '' ?>">
              <th scope="row" class="mono"><a href="<?= e($urlRonda(['fecha' => $f !== $hoy ? $f : null])) ?>" title="Abrir la ronda de ese día"><?= $n ?></a></th>
              <?php foreach ($columnas as $k => $col):
                if ($col['clave']) {
                    $v = $infeccionesMes[$f][$col['clave']] ?? 0;
                    $totales[$k] += $v;
                    echo '<td class="n mono inf' . ($v ? ' pos' : '') . '">' . ($f > $hoy ? '' : $v) . '</td>';
                } else {
                    $v = $registro[$col['tipo']] ?? null;
                    $totales[$k] += (int) $v;
                    echo '<td class="n mono">' . ($f > $hoy ? '' : ($v === null ? '<span class="sinreg" title="Sin registrar">—</span>' : $v)) . '</td>';
                }
              endforeach; ?>
            </tr>
          <?php endfor; ?>
        </tbody>
        <tfoot><tr><th scope="row">Total</th><?php foreach ($totales as $t): ?><td class="n mono"><?= $t ?></td><?php endforeach; ?></tr></tfoot>
      </table>
    </div>
  </section>
<?php endif; ?>

<div class="iaas-print-firma" aria-hidden="true"><span>Firma y sello del responsable</span></div>

<?php if ($editable && $grilla['tablas']): ?>
  <form id="formCelda" method="post" action="/iaas/ronda/marcar" hidden>
    <?= Csrf::campoOculto() ?>
    <input type="hidden" name="dispositivo_id"><input type="hidden" name="fecha"><input type="hidden" name="marcado">
    <input type="hidden" name="volver" value="<?= e($urlRonda(['vista' => 'mes', 'mes' => sprintf('%04d-%02d', $anio, $mes)])) ?>">
  </form>
<?php endif; ?>
