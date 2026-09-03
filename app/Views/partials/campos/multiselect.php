<?php
/**
 * Variables: $campo, $valor (array de strings seleccionados), $error (?string), $opciones (array de catalogo_item)
 *
 * "Ninguno"/"No" mutuamente excluyente (cotejo B04X, 2026-08-29 y
 * 2026-09-02, pedido del usuario): si el catálogo trae una opción de
 * valor "NINGUNO" o "NO", el chip-select se marca con
 * data-excluyente="<ese valor>" -- ficha.js (aplicarExcluyenteMultiselect())
 * la detecta sola y aplica la exclusión, sin config nueva por campo. "NO"
 * se agregó para b04x_comorbilidades ("No" como 1.ª opción del checklist,
 * PDF ítem 40) -- mismo significado semántico ("ninguna de las demás
 * aplica"), código de catálogo distinto porque la etiqueta real es "No".
 */
$nombreCampo = 'campo_' . $campo['id'];
$seleccionados = is_array($valor) ? $valor : [];
$codigosExcluyentes = ['NINGUNO', 'NO'];
$valorExcluyente = null;
foreach ($opciones as $opcion) {
    if (in_array($opcion['valor'], $codigosExcluyentes, true)) {
        $valorExcluyente = $opcion['valor'];
        break;
    }
}
?>
<div class="field wide">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <div class="chip-select" <?= $valorExcluyente !== null ? 'data-excluyente="' . e($valorExcluyente) . '"' : '' ?>>
    <?php foreach ($opciones as $opcion): ?>
      <label class="chip-option">
        <input type="checkbox" name="<?= e($nombreCampo) ?>[]" value="<?= e($opcion['valor']) ?>" <?= marcado(in_array($opcion['valor'], $seleccionados, true)) ?>>
        <span class="chip"><?= e($opcion['etiqueta']) ?></span>
      </label>
    <?php endforeach; ?>
  </div>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
