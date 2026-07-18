<?php
/** Variables: $campo, $valor (string dd/mm/aaaa), $error (?string) */
$nombreCampo = 'campo_' . $campo['id'];
?>
<div class="field">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <div class="control mono <?= $error ? 'err' : '' ?>">
    <input type="text" name="<?= e($nombreCampo) ?>" value="<?= e($valor) ?>" placeholder="dd/mm/aaaa">
  </div>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
