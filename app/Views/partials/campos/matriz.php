<?php
$nombreCampo = 'campo_' . $campo['id'];
$config = json_decode($campo['config'] ?? '{}', true);
$filas = $config['filas'] ?? [];
$columnas = $config['columnas'] ?? [];
$valores = is_array($valor) ? $valor : [];
?>
<div class="field wide">
  <label class="fl"><?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?></label>
  <div style="overflow-x: auto; background: var(--surface); border: 1px solid var(--line); border-radius: 9px; padding: 1px;">
    <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
      <thead>
        <tr>
          <th style="font-size: 10.5px; text-transform: uppercase; color: var(--faint); padding: 8px 12px; border-bottom: 1px solid var(--line); text-align: left;">Parámetro</th>
          <?php foreach ($columnas as $col): ?>
            <th style="font-size: 10.5px; text-transform: uppercase; color: var(--faint); padding: 8px 12px; border-bottom: 1px solid var(--line); text-align: center;"><?= e($col) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($filas as $fIdx => $fila): ?>
          <tr>
            <td style="font-size: 12px; font-weight: 500; color: var(--ink); padding: 8px 12px; border-bottom: 1px solid var(--line-2);"><?= e($fila) ?></td>
            <?php foreach ($columnas as $cIdx => $col): ?>
              <td style="padding: 4px 8px; border-bottom: 1px solid var(--line-2);">
                <input type="text" name="<?= e($nombreCampo) ?>[<?= $fIdx ?>][<?= $cIdx ?>]" value="<?= e($valores[$fIdx][$cIdx] ?? '') ?>" style="width: 100%; border: 1px solid var(--line); border-radius: 6px; padding: 6px; font-size: 12px; background: var(--paper); outline: none;">
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
