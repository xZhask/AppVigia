<?php
$nombreCampo = 'campo_' . $campo['id'];
$valores = is_array($valor) ? $valor : [];
$isSi = isset($valores['marcado']) && $valores['marcado'] === 'SI';
$isNo = isset($valores['marcado']) && $valores['marcado'] === 'NO';
$isIgn = isset($valores['marcado']) && $valores['marcado'] === 'IGNORADO';
$respondido = $isSi || $isNo || $isIgn;
$idMatriz = 'si_no_fecha_' . $campo['id'];
?>
<div class="field wide grupo-si-no-field si-no-fecha-field" id="<?= $idMatriz ?>" data-campo-id="<?= $campo['id'] ?>">
  <div class="grupo-si-no-row <?= $isSi ? 'is-si' : '' ?> <?= $respondido ? 'respondido' : 'pendiente' ?>" tabindex="-1" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line-2); min-height:40px; padding-left:0; transition: border-left 0.15s; border-left: <?= $isSi ? '3px solid var(--accent)' : '3px solid transparent' ?>;">
    
    <!-- Etiqueta a la izquierda -->
    <span class="row-label" style="font-size: 13.5px; color: <?= $isSi ? 'var(--ink)' : ($respondido ? 'var(--ink-2)' : 'var(--ink)') ?>; font-weight: <?= $isSi ? '500' : 'normal' ?>; flex:1; padding-left:6px;">
      <?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?>
    </span>
    
    <div style="display:flex; align-items:center; gap:16px;">
      <!-- Control segmentado al centro -->
      <div class="seg" style="width: 190px; flex-shrink:0;">
        <label class="seg-label <?= $isSi ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Sí">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="SI" class="sr-only" <?= $isSi ? 'checked' : '' ?> onchange="this.closest('.grupo-si-no-row').querySelector('.fecha-dep').style.display = this.checked ? 'block' : 'none'">
          Sí
        </label>
        <label class="seg-label <?= $isNo ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="No">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="NO" class="sr-only" <?= $isNo ? 'checked' : '' ?> onchange="this.closest('.grupo-si-no-row').querySelector('.fecha-dep').style.display = 'none'">
          No
        </label>
        <label class="seg-label <?= $isIgn ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Ignorado">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="IGNORADO" class="sr-only" <?= $isIgn ? 'checked' : '' ?> onchange="this.closest('.grupo-si-no-row').querySelector('.fecha-dep').style.display = 'none'">
          Ign.
        </label>
      </div>

      <!-- Campo de fecha a la derecha -->
      <div class="control mono fecha-dep <?= $error ? 'err' : '' ?>" style="width: 140px; display: <?= $isSi ? 'block' : 'none' ?>;">
        <input type="date" name="<?= e($nombreCampo) ?>[fecha]" value="<?= e($valores['fecha'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
      </div>
    </div>
  </div>
  <?php if ($error): ?><span class="hint err" style="margin-top:8px; display:block;"><?= e($error) ?></span><?php endif; ?>
</div>

<style>
/* Los estilos compartidos de .grupo-si-no-field ya están definidos en grupo-si-no.php */
/* Si solo hay campos SI_NO_FECHA, se repiten aquí para asegurar que carguen */
.si-no-fecha-field .sr-only {
  position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0;
}
.si-no-fecha-field .seg-label {
  border:0; background:none; font-size:12px; color:var(--muted); padding:4px 0; border-radius:6px; font-weight:500;
  display: inline-block;
  user-select: none;
}
.si-no-fecha-field .seg-label.on {
  background:#fff; color:var(--ink); box-shadow:var(--shadow-soft);
}
html[data-theme="dark"] .si-no-fecha-field .seg-label.on {
  background: var(--line); color: var(--ink); box-shadow: var(--shadow-soft);
}
.si-no-fecha-field .grupo-si-no-row:focus-within {
  background: var(--paper);
}
.si-no-fecha-field .grupo-si-no-row.has-error {
  background: rgba(230, 57, 70, 0.05);
  border: 1px solid var(--s-confirmado);
}
@media (max-width: 639px) {
  .si-no-fecha-field .grupo-si-no-row {
    flex-direction: column;
    align-items: stretch !important;
    padding: 8px 0;
  }
  .si-no-fecha-field .grupo-si-no-row > div {
    flex-direction: column;
    align-items: stretch !important;
  }
  .si-no-fecha-field .seg {
    width: 100% !important;
    margin-top: 8px;
  }
  .si-no-fecha-field .fecha-dep {
    width: 100% !important;
    margin-top: 8px;
  }
  .si-no-fecha-field .grupo-si-no-row { border-left: none !important; }
  .si-no-fecha-field .grupo-si-no-row.is-si { border-left: 3px solid var(--accent) !important; }
}
</style>
