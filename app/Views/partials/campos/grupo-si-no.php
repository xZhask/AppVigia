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
$cie10Actual = $enfermedad['cie10'] ?? ($GLOBALS['enfermedad']['cie10'] ?? '');
$esComplicacionesB05 = ($cie10Actual === 'B05' && trim($campo['etiqueta']) === 'Complicaciones');
// A44 (cotejo 2026-08-18, pág. 42 del PDF): "Orientación" solo trae Sí/No
// por ítem -- ( ) ( ), sin una tercera columna de Ignorado/Desconocido.
// B57 (cotejo 2026-08-21, pág. 40 del PDF): "Etapa aguda"/"Etapa crónica"
// (únicos GRUPO_SI_NO de la ficha) traen columnas "SI NO", sin Ignorado.
// A95 (cotejo 2026-08-23, pág. 27 del PDF): "Criterios de confirmación"
// (Laboratorio/Anatomía patológica/Clínica) también trae solo SI/NO.
$permitirIgnorado = ($cie10Actual !== 'B05' || $esComplicacionesB05) && !in_array($cie10Actual, ['A44', 'B57', 'A95'], true);
$etiquetaIgnorado = ($cie10Actual === 'B05') ? 'Desc.' : 'Ign.';
$anchoSeg = $permitirIgnorado ? '190px' : '130px';
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
        <div style="width: <?= $anchoSeg ?>; display:flex; text-align:center;">
           <span style="flex:1">Sí</span>
           <span style="flex:1">No</span>
           <?php if ($permitirIgnorado): ?><span style="flex:1"><?= e($etiquetaIgnorado) ?></span><?php endif; ?>
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
      $esOtros = (in_array(strtoupper($op['valor']), ['OTROS', 'OTRAS'], true) || stripos($op['etiqueta'], 'otro') !== false);
      $placeholderOtros = (stripos($campo['etiqueta'], 'complicac') !== false) ? 'Especifique otras complicaciones…' : 'Especifique otros signos…';
    ?>
      <div class="grupo-si-no-row <?= $isSi ? 'is-si' : '' ?> <?= $val ? 'respondido' : 'pendiente' ?>" id="<?= $rowId ?>" tabindex="-1" style="display:flex; flex-direction:column; justify-content:center; border-bottom:1px solid var(--line-2); min-height:40px; padding:6px 0; padding-left:<?= $esSubgrupo ? '16px' : '0' ?>; transition: border-left 0.15s; border-left: <?= $isSi ? '3px solid var(--accent)' : '3px solid transparent' ?>;">
        <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
          <span class="row-label" style="font-size: 13.5px; color: <?= $isSi ? 'var(--ink)' : ($val ? 'var(--ink-2)' : 'var(--ink)') ?>; font-weight: <?= $isSi ? '500' : 'normal' ?>; flex:1; padding-left:6px;"><?= e($op['etiqueta']) ?></span>
          
          <div class="seg" style="width: <?= $anchoSeg ?>; flex-shrink:0;">
            <label class="seg-label <?= $isSi ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Sí">
              <input type="radio" name="<?= e($nombreCampo) ?>[<?= e($op['valor']) ?>]" value="SI" class="sr-only" <?= $isSi ? 'checked' : '' ?>>
              Sí
            </label>
            <label class="seg-label <?= $isNo ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="No">
              <input type="radio" name="<?= e($nombreCampo) ?>[<?= e($op['valor']) ?>]" value="NO" class="sr-only" <?= $isNo ? 'checked' : '' ?>>
              No
            </label>
            <?php if ($permitirIgnorado): ?>
            <label class="seg-label <?= $isIgn ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="<?= e($etiquetaIgnorado) ?>">
              <input type="radio" name="<?= e($nombreCampo) ?>[<?= e($op['valor']) ?>]" value="IGNORADO" class="sr-only" <?= $isIgn ? 'checked' : '' ?>>
              <?= e($etiquetaIgnorado) ?>
            </label>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($esOtros): ?>
          <div class="otros-especificar-dep" style="display: <?= $isSi ? 'block' : 'none' ?>; margin-top:8px; padding-left:6px; padding-right:6px; width:100%;">
            <div class="control">
              <input type="text" name="<?= e($nombreCampo) ?>[OTROS_ESPECIFICAR]" value="<?= e($valores['OTROS_ESPECIFICAR'] ?? '') ?>" placeholder="<?= e($placeholderOtros) ?>" style="width:100%;">
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if ($error): ?><span class="hint err" style="margin-top:8px; display:block;"><?= e($error) ?></span><?php endif; ?>
</div>
<?php // Estilos .seg/.seg-label/.sr-only: ver public/css/campos-dinamicos.css (siempre cargado desde shell.php) ?>
