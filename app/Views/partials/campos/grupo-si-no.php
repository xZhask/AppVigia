<?php
$nombreCampo = 'campo_' . $campo['id'];
$valores = is_array($valor) ? $valor : [];
$esSubgrupo = $esSubgrupo ?? false;
$idMatriz = 'grupo_si_no_' . $campo['id'];
$hayOpciones = count($opciones) > 0;
$totalOpciones = count($opciones);
$respondidas = 0;
foreach ($opciones as $op) {
    if (!empty($valores[$op['valor']])) $respondidas++;
}
?>
<div class="field wide grupo-si-no-field" id="<?= $idMatriz ?>" data-campo-id="<?= $campo['id'] ?>">
  <?php if (!$esSubgrupo): ?>
    <div class="eyebrow" style="display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:10; background:var(--surface); padding-top:12px; margin-bottom:8px;">
      <div>
        <?= e($campo['etiqueta']) ?>
        <?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?>
      </div>
      <?php if ($hayOpciones): ?>
        <div style="display:flex; align-items:center; gap: 16px;">
          <button type="button" class="btn btn-quiet btn-marcar-no" style="<?= $respondidas === $totalOpciones ? 'display:none;' : '' ?>">
            Marcar los pendientes como No
          </button>
          <span class="mono faint contador-grupo" data-total="<?= $totalOpciones ?>" style="<?= $respondidas === $totalOpciones ? 'color:var(--accent)' : '' ?>">
            <span class="respondidas"><?= $respondidas ?></span> / <?= $totalOpciones ?>
          </span>
        </div>
      <?php endif; ?>
    </div>
    
    <?php if ($hayOpciones): ?>
      <div style="display:flex; justify-content:flex-end; padding-right: 2px; margin-bottom:4px; font-size:12px; font-weight:500; color:var(--muted);">
        <div style="width: 190px; display:flex; text-align:center;">
           <span style="flex:1">Sí</span>
           <span style="flex:1">No</span>
           <span style="flex:1">Ign.</span>
        </div>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <!-- Subgrupo -->
    <div class="eyebrow" style="margin-top:24px; margin-bottom:8px; padding-left:16px;">
      <?= e($campo['etiqueta']) ?>
      <?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?>
    </div>
  <?php endif; ?>

  <div class="grupo-si-no-matriz" style="display:flex; flex-direction:column; gap:0;">
    <?php foreach ($opciones as $op): 
      $val = $valores[$op['valor']] ?? '';
      $isSi = $val === 'SI';
      $isNo = $val === 'NO';
      $isIgn = $val === 'IGNORADO';
      $rowId = 'row_' . $campo['id'] . '_' . $op['valor'];
    ?>
      <div class="grupo-si-no-row <?= $isSi ? 'is-si' : '' ?> <?= $val ? 'respondido' : 'pendiente' ?>" id="<?= $rowId ?>" tabindex="-1" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line-2); min-height:40px; padding-left:<?= $esSubgrupo ? '16px' : '0' ?>; transition: border-left 0.15s; border-left: <?= $isSi ? '3px solid var(--accent)' : '3px solid transparent' ?>;">
        <span class="row-label" style="font-size: 13.5px; color: <?= $isSi ? 'var(--ink)' : ($val ? 'var(--ink-2)' : 'var(--ink)') ?>; font-weight: <?= $isSi ? '500' : 'normal' ?>; flex:1; padding-left:6px;"><?= e($op['etiqueta']) ?></span>
        
        <div class="seg" style="width: 190px; flex-shrink:0;">
          <label class="seg-label <?= $isSi ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Sí">
            <input type="radio" name="<?= e($nombreCampo) ?>[<?= e($op['valor']) ?>]" value="SI" class="sr-only" <?= $isSi ? 'checked' : '' ?>>
            Sí
          </label>
          <label class="seg-label <?= $isNo ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="No">
            <input type="radio" name="<?= e($nombreCampo) ?>[<?= e($op['valor']) ?>]" value="NO" class="sr-only" <?= $isNo ? 'checked' : '' ?>>
            No
          </label>
          <label class="seg-label <?= $isIgn ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Ignorado">
            <input type="radio" name="<?= e($nombreCampo) ?>[<?= e($op['valor']) ?>]" value="IGNORADO" class="sr-only" <?= $isIgn ? 'checked' : '' ?>>
            Ign.
          </label>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if ($error): ?><span class="hint err" style="margin-top:8px; display:block;"><?= e($error) ?></span><?php endif; ?>
</div>

<style>
/* Estilos embebidos para GRUPO_SI_NO respetando la regla de no tocar theme.css */
.grupo-si-no-field .sr-only {
  position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0;
}
.grupo-si-no-field .seg-label {
  border:0; background:none; font-size:12px; color:var(--muted); padding:4px 0; border-radius:6px; font-weight:500;
  display: inline-block;
  user-select: none;
}
.grupo-si-no-field .seg-label.on {
  background:#fff; color:var(--ink); box-shadow:var(--shadow-soft);
}
html[data-theme="dark"] .grupo-si-no-field .seg-label.on {
  background: var(--line); color: var(--ink); box-shadow: var(--shadow-soft);
}
.grupo-si-no-row:focus-within {
  background: var(--paper);
}
.grupo-si-no-row.has-error {
  background: rgba(230, 57, 70, 0.05); /* s-confirmado transparent */
  border: 1px solid var(--s-confirmado);
}
@media (max-width: 639px) {
  .grupo-si-no-field .grupo-si-no-row {
    flex-direction: column;
    align-items: stretch !important;
    padding: 8px 0;
  }
  .grupo-si-no-field .seg {
    width: 100% !important;
    margin-top: 8px;
  }
  .grupo-si-no-field .grupo-si-no-row { border-left: none !important; }
  .grupo-si-no-field .grupo-si-no-row.is-si { border-left: 3px solid var(--accent) !important; }
}
</style>
