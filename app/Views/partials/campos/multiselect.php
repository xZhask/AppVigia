<?php
/**
 * Variables: $campo, $valor (array de strings seleccionados), $error (?string), $opciones (array de catalogo_item)
 */
$nombreCampo = 'campo_' . $campo['id'];
$seleccionados = is_array($valor) ? $valor : [];
?>
<div class="field wide">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <div class="chip-select">
    <?php foreach ($opciones as $opcion): ?>
      <label class="chip-option">
        <input type="checkbox" name="<?= e($nombreCampo) ?>[]" value="<?= e($opcion['valor']) ?>" <?= marcado(in_array($opcion['valor'], $seleccionados, true)) ?>>
        <span class="chip"><?= e($opcion['etiqueta']) ?></span>
      </label>
    <?php endforeach; ?>
  </div>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
