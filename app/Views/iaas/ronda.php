<?php
use App\Models\IaasNorma;

// Ronda diaria del módulo IAAS (Anexos 4, 6 y 7). HOY por defecto; "Mes"
// muestra la grilla del papel. Ver IaasRondaController::ronda.
$anexo = $vistaMes ? 'Anexos 4, 6 y 7 · vista del mes' : 'Anexos 4, 6 y 7';
$urlRonda = fn(array $p = []) => '/iaas/ronda?' . http_build_query(array_filter($p + ['sala' => $sala['id']], fn($v) => $v !== null && $v !== ''));
$mesActual = substr($fecha, 0, 7);
$acciones = '<nav class="seg seg-periodo iaas-vista" aria-label="Vista">'
    . '<a href="' . e($urlRonda(['fecha' => $fecha !== $hoy ? $fecha : null])) . '"' . (!$vistaMes ? ' class="on" aria-current="page"' : '') . '>Día</a>'
    . '<a href="' . e($urlRonda(['vista' => 'mes', 'mes' => $vistaMes ? sprintf('%04d-%02d', $anio, $mes) : $mesActual])) . '"' . ($vistaMes ? ' class="on" aria-current="page"' : '') . '>Mes</a>'
    . '</nav>';
require __DIR__ . '/_cabecera.php';
?>
<nav class="iaas-salas" aria-label="Salas">
  <?php foreach ($salas as $s):
    $r = $resumen[$s['id']] ?? ['pacientes' => 0, 'sin_marcar' => 0];
    $sel = (int) $s['id'] === (int) $sala['id'];
    $sinDispositivos = !IaasNorma::DISPOSITIVOS_POR_UPSS[$s['upss']];
  ?>
    <a class="iaas-sala<?= $sel ? ' sel' : '' ?>" href="<?= e('/iaas/ronda?' . http_build_query(array_filter(['sala' => $s['id'], 'fecha' => !$vistaMes && $fecha !== $hoy ? $fecha : null, 'vista' => $vistaMes ? 'mes' : null, 'mes' => $vistaMes ? sprintf('%04d-%02d', $anio, $mes) : null]))) ?>"<?= $sel ? ' aria-current="page"' : '' ?>>
      <b><?= e(IaasNorma::nombreSala($s)) ?></b>
      <small>
        <?php if ($sinDispositivos): ?><?= $s['upss'] === 'GINECO_OBSTETRICIA' ? 'Partos del día' : 'Cirugías del día' ?>
        <?php else: ?><?= $r['pacientes'] ?> paciente<?= $r['pacientes'] === 1 ? '' : 's' ?><?php endif; ?>
        <?php if (!$vistaMes && $r['sin_marcar'] > 0): ?><span class="iaas-pend" title="Dispositivos sin marcar este día"><?= $r['sin_marcar'] ?> por marcar</span>
        <?php elseif (!$vistaMes && $r['pacientes'] > 0): ?><span class="iaas-ok" title="Todo marcado este día">✓</span><?php endif; ?>
      </small>
    </a>
  <?php endforeach; ?>
  <a class="iaas-sala iaas-sala-cfg" href="/iaas/servicios" title="Agregar o quitar servicios y salas">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
    <small>Servicios</small>
  </a>
</nav>

<?php require __DIR__ . ($vistaMes ? '/ronda-mes.php' : '/ronda-dia.php'); ?>
<script src="<?= e(asset('/js/iaas.js')) ?>"></script>
