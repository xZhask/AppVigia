<?php
/** Variables: $campo, $valor (string), $error (?string), $opciones (array de catalogo_item) */
$nombreCampo = 'campo_' . $campo['id'];
$es3OpcionesSiNoDesc = (count($opciones) === 3 && isset($opciones[0]['valor'], $opciones[1]['valor'], $opciones[2]['valor']) && $opciones[0]['valor'] === 'SI' && $opciones[1]['valor'] === 'NO');
?>
<?php if ($es3OpcionesSiNoDesc): ?>
<div class="field wide grupo-si-no-field" style="margin-top:6px; margin-bottom:8px;">
  <div class="grupo-si-no-row <?= $valor === 'SI' ? 'is-si' : ($valor ? 'respondido' : 'pendiente') ?>" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line-2); min-height:40px; padding:4px 0;">
    <span class="row-label" style="font-size: 13.5px; color: var(--ink); flex:1; padding-left:6px;">
      <?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?>
    </span>
    <div class="seg" style="width: 190px; flex-shrink:0;">
      <label class="seg-label <?= $valor === 'SI' ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Sí">
        <input type="radio" name="<?= e($nombreCampo) ?>" value="SI" class="sr-only" <?= $valor === 'SI' ? 'checked' : '' ?>>
        Sí
      </label>
      <label class="seg-label <?= $valor === 'NO' ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="No">
        <input type="radio" name="<?= e($nombreCampo) ?>" value="NO" class="sr-only" <?= $valor === 'NO' ? 'checked' : '' ?>>
        No
      </label>
      <label class="seg-label <?= (in_array($valor, ['DESCONOCIDO', 'IGNORADO', '99'], true)) ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Desconocido">
        <input type="radio" name="<?= e($nombreCampo) ?>" value="<?= e($opciones[2]['valor']) ?>" class="sr-only" <?= (in_array($valor, ['DESCONOCIDO', 'IGNORADO', '99'], true)) ? 'checked' : '' ?>>
        Desc.
      </label>
    </div>
  </div>
  <?php if ($error): ?><span class="hint err" style="margin-top:4px; display:block;"><?= e($error) ?></span><?php endif; ?>
</div>
<?php else: ?>
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
<?php endif; ?>
