<?php
/**
 * Variables: $campo, $valor (string), $error (?string)
 * $campo['config'] (opcional, JSON): {"decimales": true} marca los ~8
 * campos que sí lo necesitan (temperaturas, peso en kg, hemoglobina...);
 * por defecto es entero. El bloqueo real de teclas e/E/./,/+/- lo hace
 * public/js/ficha.js (clases .solo-enteros / .permite-decimales), no
 * este partial -- acá solo se elige la clase correcta.
 * {"minimo": 0} (Z21, 2026-09-13): piso del valor, pintado como min= y
 * exigido también en CasosController::validarCamposDinamicos().
 * {"desconocido": true} (A50, 2026-09-14): casilla "Desconocido", igual que
 * en campos/fecha.php.
 */
$nombreCampo = 'campo_' . $campo['id'];
$configNumero = json_decode($campo['config'] ?? '{}', true) ?: [];
$permiteDecimales = !empty($configNumero['decimales']);
$atributoMinimo = isset($configNumero['minimo']) ? ' min="' . e((string) $configNumero['minimo']) . '"' : '';
$admiteDesconocido = !empty($configNumero['desconocido']);
$esDesconocido = $admiteDesconocido && $valor === VALOR_DESCONOCIDO;
$valorNumero = $esDesconocido ? '' : $valor;
$atributoDesconocido = $admiteDesconocido ? ' data-con-desconocido' . ($esDesconocido ? ' disabled' : '') : '';
?>
<div class="field">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <div class="control mono <?= $error ? 'err' : '' ?>">
    <?php if ($permiteDecimales): ?>
    <input type="number" step="any" inputmode="decimal" class="permite-decimales"<?= $atributoMinimo ?> name="<?= e($nombreCampo) ?>" value="<?= e($valorNumero) ?>"<?= $atributoDesconocido ?>>
    <?php else: ?>
    <input type="number" step="1" inputmode="numeric" pattern="[0-9]*" class="solo-enteros"<?= $atributoMinimo ?> name="<?= e($nombreCampo) ?>" value="<?= e($valorNumero) ?>"<?= $atributoDesconocido ?>>
    <?php endif; ?>
  </div>
<?php if ($admiteDesconocido): ?>
  <label class="sym" style="margin-top:4px"><input type="checkbox" name="<?= e($nombreCampo) ?>" value="<?= VALOR_DESCONOCIDO ?>" data-casilla-desconocido <?= $esDesconocido ? 'checked' : '' ?>> Desconocido</label>
<?php endif; ?>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
