<?php
/** Variables: $campo, $valor (string), $error (?string), $opciones (array de catalogo_item) */
$nombreCampo = 'campo_' . $campo['id'];
?>
<div class="field">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <div class="control <?= $error ? 'err' : '' ?>">
    <select name="<?= e($nombreCampo) ?>">
      <option value="">Seleccionar…</option>
      <?php foreach ($opciones as $opcion): ?>
        <option value="<?= e($opcion['valor']) ?>" <?= seleccionado($valor, $opcion['valor']) ?>><?= e($opcion['etiqueta']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
