<?php
/**
 * "Observaciones adicionales (PNP)" (2026-10-21): columnas del registro
 * semanal individual del Área de Epidemiología PNP que no están en la ficha
 * MINSA. El descanso médico es del efectivo PNP (se muestra con esa
 * condición; ficha.js lo alterna al cambiarla) y la fecha de alta, de
 * cualquier paciente. La omiten las fichas con nucleo_omitidos
 * "observaciones_pnp". Lo guarda CasosController::leerObservacionesPnp().
 *
 * Variables: $valoresFijos, $erroresFijos, $numeroSeccion, $condicionPaciente.
 */
$fechaPnp = function (string $nombre, string $etiqueta, bool $admiteFutura) use ($valoresFijos, $erroresFijos): void {
    $error = $erroresFijos[$nombre] ?? null; ?>
    <div class="field">
      <label class="fl"><?= e($etiqueta) ?></label>
      <div class="control mono <?= $error ? 'err' : '' ?>">
        <input type="date" name="<?= e($nombre) ?>" value="<?= e($valoresFijos[$nombre] ?? '') ?>" min="1900-01-01"<?= $admiteFutura ? '' : ' max="' . date('Y-m-d') . '"' ?>>
      </div>
      <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
    </div>
<?php };
$esEfectivo = ($condicionPaciente ?? '') === 'EFECTIVO';
?>
      <div class="card section">
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Observaciones adicionales (PNP)</h3></div>
        <div class="section-body">
          <div data-solo-efectivo <?= $esEfectivo ? '' : 'hidden' ?>>
            <div class="eyebrow" style="margin-bottom:10px">Descanso médico del efectivo</div>
            <div class="fields halves" style="margin-bottom:16px">
              <?php $fechaPnp('descanso1_inicio', 'Descanso médico 1: desde', false); ?>
              <?php $fechaPnp('descanso1_fin', 'Descanso médico 1: hasta', true); ?>
              <?php $fechaPnp('descanso2_inicio', 'Descanso médico 2: desde', false); ?>
              <?php $fechaPnp('descanso2_fin', 'Descanso médico 2: hasta', true); ?>
            </div>
          </div>
          <div class="fields halves">
            <?php $fechaPnp('fecha_alta', 'Fecha de alta', false); ?>
          </div>
          <span class="hint" data-solo-no-efectivo <?= $esEfectivo ? 'hidden' : '' ?>>El descanso médico se registra solo para efectivos PNP.</span>
        </div>
      </div>
