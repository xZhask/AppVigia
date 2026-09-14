<?php
/**
 * Variables: $campo, $valor (string aaaa-mm-dd), $error (?string)
 * $campo['config'] (opcional, JSON): {"desconocido": true} (A50, 2026-09-14)
 * agrega la casilla "Desconocido" del PDF. Marcada, ficha.js deshabilita la
 * fecha y viaja la casilla, con el mismo name: se guarda VALOR_DESCONOCIDO.
 */
$nombreCampo = 'campo_' . $campo['id'];
$admiteDesconocido = !empty((json_decode($campo['config'] ?? '{}', true) ?: [])['desconocido']);
$esDesconocido = $admiteDesconocido && $valor === VALOR_DESCONOCIDO;
?>
<div class="field">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <div class="control mono <?= $error ? 'err' : '' ?>">
    <input type="date" name="<?= e($nombreCampo) ?>" value="<?= e($esDesconocido ? '' : $valor) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"<?= $admiteDesconocido ? ' data-con-desconocido' . ($esDesconocido ? ' disabled' : '') : '' ?>>
  </div>
<?php if ($admiteDesconocido): ?>
  <label class="sym" style="margin-top:4px"><input type="checkbox" name="<?= e($nombreCampo) ?>" value="<?= VALOR_DESCONOCIDO ?>" data-casilla-desconocido <?= $esDesconocido ? 'checked' : '' ?>> Desconocido</label>
<?php endif; ?>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
