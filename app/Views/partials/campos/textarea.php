<?php
/** Variables: $campo, $valor (string|array), $error (?string) */
$nombreCampo = 'campo_' . $campo['id'];
$esArrayVal = is_array($valor);
$txtVal = $esArrayVal ? ($valor['descripcion'] ?? '') : (string) $valor;
$nameAttr = $esArrayVal ? $nombreCampo . '[descripcion]' : $nombreCampo;
?>
<div class="field wide">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <textarea class="textarea-control <?= $error ? 'err' : '' ?>" name="<?= e($nameAttr) ?>" rows="3"><?= e($txtVal) ?></textarea>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
