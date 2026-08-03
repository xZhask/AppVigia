<?php
/**
 * Variables: $campo, $valor (string), $error (?string)
 * $campo['config'] (opcional, JSON): {"decimales": true} marca los ~8
 * campos que sí lo necesitan (temperaturas, peso en kg, hemoglobina...);
 * por defecto es entero. El bloqueo real de teclas e/E/./,/+/- lo hace
 * public/js/ficha.js (clases .solo-enteros / .permite-decimales), no
 * este partial -- acá solo se elige la clase correcta.
 */
$nombreCampo = 'campo_' . $campo['id'];
$configNumero = json_decode($campo['config'] ?? '{}', true) ?: [];
$permiteDecimales = !empty($configNumero['decimales']);
?>
<div class="field">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <div class="control mono <?= $error ? 'err' : '' ?>">
    <?php if ($permiteDecimales): ?>
    <input type="number" step="any" inputmode="decimal" class="permite-decimales" name="<?= e($nombreCampo) ?>" value="<?= e($valor) ?>">
    <?php else: ?>
    <input type="number" step="1" inputmode="numeric" pattern="[0-9]*" class="solo-enteros" name="<?= e($nombreCampo) ?>" value="<?= e($valor) ?>">
    <?php endif; ?>
  </div>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
