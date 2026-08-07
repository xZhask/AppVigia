<?php
$nombreCampo = 'campo_' . $campo['id'];
$valores = is_array($valor) ? $valor : [];
$isSi = isset($valores['marcado']) && $valores['marcado'] === 'SI';
$isNo = isset($valores['marcado']) && $valores['marcado'] === 'NO';
$isIgn = isset($valores['marcado']) && $valores['marcado'] === 'IGNORADO';
$respondido = $isSi || $isNo || $isIgn;
$idMatriz = 'si_no_fecha_' . $campo['id'];
// "especificar": true (config del campo_def, ver cargar_fichas.php) marca
// los "Otros" que además necesitan un texto libre -- se guarda junto con
// marcado/fecha en el mismo campo_<id>[] (ver A37.0, corregido 2026-08-06:
// antes era un campo_def de texto suelto siempre visible, sin depender de
// este Sí/No).
$configSiNoFecha = json_decode($campo['config'] ?? '{}', true) ?: [];
$tieneEspecificar = !empty($configSiNoFecha['especificar']);
?>
<div class="field wide grupo-si-no-field si-no-fecha-field" id="<?= $idMatriz ?>" data-campo-id="<?= $campo['id'] ?>">
  <div class="grupo-si-no-row <?= $isSi ? 'is-si' : '' ?> <?= $respondido ? 'respondido' : 'pendiente' ?>" tabindex="-1" style="display:flex; flex-direction:column; justify-content:center; border-bottom:1px solid var(--line-2); min-height:40px; padding:6px 0; transition: border-left 0.15s; border-left: <?= $isSi ? '3px solid var(--accent)' : '3px solid transparent' ?>;">
    
    <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
      <!-- Etiqueta a la izquierda -->
      <span class="row-label" style="font-size: 13.5px; color: <?= $isSi ? 'var(--ink)' : ($respondido ? 'var(--ink-2)' : 'var(--ink)') ?>; font-weight: <?= $isSi ? '500' : 'normal' ?>; flex:1; padding-left:6px;">
        <?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?>
      </span>
      
      <?php
      $cie10Actual = $enfermedad['cie10'] ?? ($GLOBALS['enfermedad']['cie10'] ?? '');
      $permitirIgnorado = !in_array($cie10Actual, ['B05', 'A37.0'], true);
      $anchoSeg = $permitirIgnorado ? '190px' : '130px';
      ?>
      <!-- Control segmentado a la derecha -->
      <div class="seg" style="width: <?= $anchoSeg ?>; flex-shrink:0;">
        <label class="seg-label <?= $isSi ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Sí">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="SI" class="sr-only" <?= $isSi ? 'checked' : '' ?> onchange="var d=this.closest('.grupo-si-no-row').querySelector('.fecha-dep'); if(d) d.style.display = this.checked ? 'block' : 'none';">
          Sí
        </label>
        <label class="seg-label <?= $isNo ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="No">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="NO" class="sr-only" <?= $isNo ? 'checked' : '' ?> onchange="var d=this.closest('.grupo-si-no-row').querySelector('.fecha-dep'); if(d) d.style.display = 'none';">
          No
        </label>
        <?php if ($permitirIgnorado): ?>
        <label class="seg-label <?= $isIgn ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Ignorado">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="IGNORADO" class="sr-only" <?= $isIgn ? 'checked' : '' ?> onchange="var d=this.closest('.grupo-si-no-row').querySelector('.fecha-dep'); if(d) d.style.display = 'none';">
          Ign.
        </label>
        <?php endif; ?>
      </div>
    </div>

    <!-- Campo de fecha (y "especificar", si aplica) abajo -->
    <div class="fecha-dep" style="display: <?= $isSi ? 'block' : 'none' ?>; margin-top:10px; padding-left:6px; padding-right:6px; width:100%;">
      <div style="display:flex; gap:16px; flex-wrap:wrap;">
        <div class="field" style="margin-bottom:0; max-width:240px;">
          <label class="fl" style="font-size:12px; margin-bottom:4px; color:var(--muted)">Fecha día 0 (inicio):</label>
          <div class="control mono <?= $error ? 'err' : '' ?>">
            <input type="date" name="<?= e($nombreCampo) ?>[fecha]" value="<?= e($valores['fecha'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <?php if ($tieneEspecificar): ?>
        <div class="field" style="margin-bottom:0; flex:1; min-width:200px;">
          <label class="fl" style="font-size:12px; margin-bottom:4px; color:var(--muted)">Especificar:</label>
          <div class="control">
            <input type="text" name="<?= e($nombreCampo) ?>[especificar]" value="<?= e($valores['especificar'] ?? '') ?>" placeholder="Especificar…">
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php if ($error): ?><span class="hint err" style="margin-top:8px; display:block;"><?= e($error) ?></span><?php endif; ?>
</div>
<?php // Estilos .seg/.seg-label/.sr-only: ver public/css/campos-dinamicos.css (siempre cargado desde shell.php) ?>
