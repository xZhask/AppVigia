<?php
/** Variables: $campo, $valor (string aaaa-mm-dd), $error (?string) */
$nombreCampo = 'campo_' . $campo['id'];
?>
<div class="field">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <div class="control mono <?= $error ? 'err' : '' ?>">
    <input type="date" name="<?= e($nombreCampo) ?>" value="<?= e($valor) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
  </div>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
