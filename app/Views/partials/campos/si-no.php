<?php
/**
 * Pregunta booleana simple (Sí/No, sin fecha dependiente ni lista de
 * sub-ítems) -- variante de si-no-fecha.php sin el bloque de fecha.
 * Pedido del usuario (cotejo B55, 2026-08-25): BOOLEANO (campos/booleano.php)
 * usa el patrón .chip-option pensado para checklists de varios ítems
 * ("Síntomas"); para una pregunta suelta tipo "¿Existen otras personas con
 * lesiones similares...?" un solo chip resultaba ambiguo (no se distinguía
 * Sí de No a simple vista). Reutiliza el mismo control segmentado .seg/
 * .seg-label ya usado por si-no-fecha.php/grupo-si-no.php.
 * "ignorado": true (config del campo_def) agrega una 3.ª opción -- por
 * defecto son solo Sí/No, fiel al "Si ( ) NO ( )" literal del PDF de B55.
 */
$nombreCampo = 'campo_' . $campo['id'];
$valores = is_array($valor) ? $valor : [];
$isSi = isset($valores['marcado']) && $valores['marcado'] === 'SI';
$isNo = isset($valores['marcado']) && $valores['marcado'] === 'NO';
$isIgn = isset($valores['marcado']) && $valores['marcado'] === 'IGNORADO';
$respondido = $isSi || $isNo || $isIgn;
$idCampo = 'si_no_' . $campo['id'];
$configSiNo = json_decode($campo['config'] ?? '{}', true) ?: [];
$permitirIgnorado = !empty($configSiNo['ignorado']);
$anchoSeg = $permitirIgnorado ? '190px' : '130px';
?>
<div class="field wide grupo-si-no-field si-no-field" id="<?= $idCampo ?>" data-campo-id="<?= $campo['id'] ?>">
  <div class="grupo-si-no-row <?= $isSi ? 'is-si' : '' ?> <?= $respondido ? 'respondido' : 'pendiente' ?>" tabindex="-1" style="display:flex; flex-direction:column; justify-content:center; border-bottom:1px solid var(--line-2); min-height:40px; padding:6px 0; transition: border-left 0.15s; border-left: <?= $isSi ? '3px solid var(--accent)' : '3px solid transparent' ?>;">
    <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
      <span class="row-label" style="font-size: 13.5px; color: <?= $isSi ? 'var(--ink)' : ($respondido ? 'var(--ink-2)' : 'var(--ink)') ?>; font-weight: <?= $isSi ? '500' : 'normal' ?>; flex:1; padding-left:6px;">
        <?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?>
      </span>

      <div class="seg" style="width: <?= $anchoSeg ?>; flex-shrink:0;">
        <label class="seg-label <?= $isSi ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Sí">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="SI" class="sr-only" <?= $isSi ? 'checked' : '' ?>>
          Sí
        </label>
        <label class="seg-label <?= $isNo ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="No">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="NO" class="sr-only" <?= $isNo ? 'checked' : '' ?>>
          No
        </label>
        <?php if ($permitirIgnorado): ?>
        <label class="seg-label <?= $isIgn ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Ignorado">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="IGNORADO" class="sr-only" <?= $isIgn ? 'checked' : '' ?>>
          Ign.
        </label>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php if ($error): ?><span class="hint err" style="margin-top:8px; display:block;"><?= e($error) ?></span><?php endif; ?>
</div>
<?php // Estilos .seg/.seg-label/.sr-only: ver public/css/campos-dinamicos.css (siempre cargado desde shell.php) ?>
