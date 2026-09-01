<?php
/**
 * Variables: $campo, $valor (array de strings seleccionados), $error (?string), $opciones (array de catalogo_item)
 *
 * "Ninguno" mutuamente excluyente (cotejo B04X, 2026-08-29, pedido del
 * usuario): si el catálogo trae una opción de valor "NINGUNO", el
 * chip-select se marca con data-excluyente="NINGUNO" -- ficha.js
 * (aplicarExcluyenteMultiselect()) la detecta sola y aplica la exclusión,
 * sin config nueva por campo.
 */
$nombreCampo = 'campo_' . $campo['id'];
$seleccionados = is_array($valor) ? $valor : [];
$tieneOpcionNinguno = false;
foreach ($opciones as $opcion) {
    if ($opcion['valor'] === 'NINGUNO') {
        $tieneOpcionNinguno = true;
        break;
    }
}
?>
<div class="field wide">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <div class="chip-select" <?= $tieneOpcionNinguno ? 'data-excluyente="NINGUNO"' : '' ?>>
    <?php foreach ($opciones as $opcion): ?>
      <label class="chip-option">
        <input type="checkbox" name="<?= e($nombreCampo) ?>[]" value="<?= e($opcion['valor']) ?>" <?= marcado(in_array($opcion['valor'], $seleccionados, true)) ?>>
        <span class="chip"><?= e($opcion['etiqueta']) ?></span>
      </label>
    <?php endforeach; ?>
  </div>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
