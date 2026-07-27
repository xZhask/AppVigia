<?php
$nombreCampo = 'campo_' . $campo['id'];
$config = json_decode($campo['config'] ?? '{}', true);
$filas = $config['filas'] ?? [];
$columnas = $config['columnas'] ?? [];
$valores = is_array($valor) ? $valor : [];

// Determinar si las columnas son un conjunto cerrado de opciones exclusivas por fila
// (ej. DIM, AUS, NORM, IGN o AUS, PRES, IGN o SI, NO, IGN)
$esSeleccionUnica = true;
if (empty($columnas)) {
    $esSeleccionUnica = false;
} else {
    foreach ($columnas as $col) {
        $colUpper = mb_strtoupper(trim($col));
        if (mb_strlen($colUpper) > 20 || str_contains($colUpper, 'FECHA') || str_contains($colUpper, 'DOSIS') || str_contains($colUpper, 'DIAS') || str_contains($colUpper, 'DÍAS') || str_contains($colUpper, 'DIA') || str_contains($colUpper, 'DÍA') || str_contains($colUpper, 'N.°') || str_contains($colUpper, 'CANTIDAD') || str_contains($colUpper, 'MM')) {
            $esSeleccionUnica = false;
            break;
        }
    }
}
?>
<div class="field wide grupo-si-no-field">
  <label class="fl"><?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?></label>
  <div style="overflow-x: auto; background: var(--surface); border: 1px solid var(--line); border-radius: 9px; padding: 1px; margin-top: 4px;">
    <table style="width: 100%; border-collapse: collapse; min-width: 480px;">
      <thead>
        <tr>
          <th style="font-size: 10.5px; text-transform: uppercase; color: var(--faint); padding: 8px 12px; border-bottom: 1px solid var(--line); text-align: left;">Parámetro / Evaluado</th>
          <?php foreach ($columnas as $col): ?>
            <th style="font-size: 10.5px; text-transform: uppercase; color: var(--faint); padding: 8px 12px; border-bottom: 1px solid var(--line); text-align: center; min-width: 90px;"><?= e($col) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($filas as $fIdx => $fila):
          $valFila = $esSeleccionUnica ? ($valores[$fIdx] ?? ($valores[(string)$fIdx] ?? '')) : null;
          $esFechaFila = str_contains(mb_strtoupper($fila), 'FECHA');
        ?>
          <tr class="grupo-si-no-row">
            <td style="font-size: 12px; font-weight: 500; color: var(--ink); padding: 8px 12px; border-bottom: 1px solid var(--line-2);"><?= e($fila) ?></td>
            <?php foreach ($columnas as $cIdx => $col): ?>
              <td style="padding: 4px 8px; border-bottom: 1px solid var(--line-2); text-align: center;">
                <?php if ($esSeleccionUnica):
                  $isSel = (string)$valFila === (string)$col || (string)$valFila === (string)$cIdx;
                ?>
                  <div class="seg" style="display:inline-flex; width: 100%;">
                    <label class="seg-label <?= $isSel ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer; padding: 4px 8px; border-radius:6px;" title="<?= e($col) ?>">
                      <input type="radio" name="<?= e($nombreCampo) ?>[<?= $fIdx ?>]" value="<?= e($col) ?>" class="sr-only" <?= $isSel ? 'checked' : '' ?>>
                      <?= e($col) ?>
                    </label>
                  </div>
                <?php else: ?>
                  <input type="<?= $esFechaFila ? 'date' : 'text' ?>" name="<?= e($nombreCampo) ?>[<?= $fIdx ?>][<?= $cIdx ?>]" value="<?= e($valores[$fIdx][$cIdx] ?? '') ?>" placeholder="<?= $esFechaFila ? '' : '—' ?>" style="width: 100%; border: 1px solid var(--line); border-radius: 6px; padding: 5px 8px; font-size: 12px; background: var(--paper); color: var(--ink); outline: none;">
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
