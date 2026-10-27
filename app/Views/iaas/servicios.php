<?php
use App\Core\Csrf;
use App\Models\Establecimiento;
use App\Models\IaasNorma;

// Configuración del módulo IAAS (Anexo 2): qué servicios con hospitalización
// tiene el establecimiento y sus salas. Con "sin hospitalización" el menú
// muestra solo Exposición laboral e Indicadores.
$anexo = 'Anexo 2';
$sinHosp = $configuracion && $configuracion['sin_hospitalizacion'];
$conHosp = $configuracion && !$sinHosp;
$salaPorNombre = [];
foreach ($todas as $s) {
    $salaPorNombre[$s['upss'] . '|' . mb_strtolower($s['nombre'])] = $s;
}
require __DIR__ . '/_cabecera.php';
?>
<form method="post" action="/iaas/servicios" class="iaas-servicios" id="formServicios">
  <?= Csrf::campoOculto() ?>

  <?php if ($primeraVez): ?>
    <div class="iaas-intro card">
      <div class="iaas-intro-ico" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V8l7-5 7 5v13"/><path d="M10 21v-5h4v5M12 8v4M10 10h4"/></svg></div>
      <div>
        <h2>Antes de empezar: ¿qué servicios tiene <?= e(Establecimiento::nombreVisible($establecimiento)) ?>?</h2>
        <p>La norma vigila distintos dispositivos y cirugías según el servicio. Con tu respuesta, la ronda muestra solo lo que corresponde a cada sala. Puedes cambiarlo cuando quieras.</p>
      </div>
    </div>
  <?php endif; ?>

  <fieldset class="iaas-modo">
    <legend class="sr-only">¿Tiene servicios con hospitalización?</legend>
    <label class="iaas-modo-op">
      <input type="radio" name="modo" value="con"<?= !$sinHosp ? ' checked' : '' ?>>
      <span><b>Tiene servicios con hospitalización</b><small>UCI, neonatología, gineco-obstetricia, cirugía, traumatología o medicina</small></span>
    </label>
    <label class="iaas-modo-op">
      <input type="radio" name="modo" value="sin"<?= $sinHosp ? ' checked' : '' ?>>
      <span><b>No tiene hospitalización</b><small>Puesto, posta o policlínico: solo registrará la exposición laboral del personal (Anexo 8)</small></span>
    </label>
  </fieldset>

  <div class="iaas-upss-grid" id="gridUpss"<?= $sinHosp ? ' hidden' : '' ?>>
    <?php foreach (IaasNorma::UPSS as $upss => $nombre):
      $activo = !empty($salasPorUpss[$upss]);
      $nombres = $salasPorUpss[$upss] ?? [$nombre];
      $conPacientes = [];
      foreach ($nombres as $n) {
          $s = $salaPorNombre[$upss . '|' . mb_strtolower($n)] ?? null;
          if ($s && ($pacientesActivos[$s['id']] ?? 0) > 0) {
              $conPacientes[] = $n . ' (' . $pacientesActivos[$s['id']] . ')';
          }
      }
    ?>
      <div class="card iaas-upss<?= $activo ? ' on' : '' ?>" data-upss>
        <label class="iaas-upss-cab">
          <input type="checkbox" name="upss[<?= $upss ?>]" value="1"<?= $activo ? ' checked' : '' ?> data-upss-check>
          <span><b><?= e($nombre) ?></b><small><?= e(IaasNorma::UPSS_DESCRIPCION[$upss]) ?></small></span>
        </label>
        <div class="iaas-upss-salas" data-upss-salas<?= $activo ? '' : ' hidden' ?>>
          <label class="fl" for="salas-<?= $upss ?>">Salas <span class="hint">· una por línea</span></label>
          <textarea id="salas-<?= $upss ?>" name="salas[<?= $upss ?>]" rows="<?= max(2, count($nombres)) ?>" maxlength="800"><?= e(implode("\n", $nombres)) ?></textarea>
          <?php if ($conPacientes): ?>
            <div class="hint">Con pacientes sin egreso: <?= e(implode(', ', $conPacientes)) ?>. No se pueden quitar.</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="iaas-form-pie">
    <?php if (!$primeraVez): ?><a class="btn btn-quiet" href="/iaas">Cancelar</a><?php endif; ?>
    <button type="submit" class="btn btn-primary">Guardar servicios</button>
  </div>
</form>
<script src="<?= e(asset('/js/iaas.js')) ?>"></script>
