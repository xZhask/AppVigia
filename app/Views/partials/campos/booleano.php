<?php
/**
 * Variables: $campo, $valor ('1'|'0'|''), $error (?string)
 * A diferencia de los demás tipos, no se envuelve en .field/.control: usa el
 * patrón .sym del mockup (checklist de signos y síntomas). El contenedor
 * .sym-grid lo arma quien itera los campos de la sección.
 */
$nombreCampo = 'campo_' . $campo['id'];
?>
<label class="sym">
  <input type="checkbox" name="<?= e($nombreCampo) ?>" value="1" <?= marcado($valor === '1') ?>>
  <?= e($campo['etiqueta']) ?>
</label>
