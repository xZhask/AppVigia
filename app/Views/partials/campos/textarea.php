<?php
/** Variables: $campo, $valor (string), $error (?string) */
$nombreCampo = 'campo_' . $campo['id'];
?>
<div class="field wide">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <textarea class="textarea-control <?= $error ? 'err' : '' ?>" name="<?= e($nombreCampo) ?>" rows="3"><?= e($valor) ?></textarea>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
