<?php
/**
 * Variables: $campo, $valor ('1'|'0'|''), $error (?string)
 * A diferencia de los demás tipos, no se envuelve en .field/.control: usa el
 * patrón .chip-option/.chip (mismo componente ya usado por "Clasificación
 * del caso", ver clasificacion-chips.php) en vez del checklist .sym de
 * casillas -- pedido del usuario, 2026-08-19: el checklist de "Signos y
 * síntomas" (10-15+ ítems en varias fichas) resultaba tedioso de escanear y
 * marcar como lista de casillas. El contenedor .chip-select lo arma quien
 * itera los campos de la sección.
 */
$nombreCampo = 'campo_' . $campo['id'];
?>
<label class="chip-option">
  <input type="checkbox" name="<?= e($nombreCampo) ?>" value="1" <?= marcado($valor === '1') ?>>
  <span class="chip"><?= e($campo['etiqueta']) ?></span>
</label>
