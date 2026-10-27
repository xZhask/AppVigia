<?php
// Cabecera común del módulo IAAS: título, establecimiento y, para el ADMIN,
// el selector de establecimiento. $anexo (texto) y $acciones (HTML) son opcionales.
use App\Models\Establecimiento;

$nombreEst = $establecimiento ? Establecimiento::nombreVisible($establecimiento) : 'Sin establecimiento';
?>
<div class="page-head iaas-head">
  <div class="iaas-head-txt">
    <div class="eyebrow">IAAS<?= !empty($anexo) ? ' · ' . e($anexo) : '' ?></div>
    <div class="page-title"><?= e($tituloVista) ?></div>
    <div class="page-desc">
      <?php if ($esAdmin && $establecimientos): ?>
        <form method="get" class="iaas-est-sel">
          <label class="sr-only" for="iaasEst">Establecimiento</label>
          <div class="filtro-caja"><select id="iaasEst" name="est" onchange="this.form.submit()">
            <?php if (!$establecimiento): ?><option value="">Elige un establecimiento…</option><?php endif; ?>
            <?php foreach ($establecimientos as $est): ?>
              <option value="<?= (int) $est['id'] ?>"<?= $establecimiento && (int) $establecimiento['id'] === (int) $est['id'] ? ' selected' : '' ?>><?= e(Establecimiento::nombreVisible($est)) ?></option>
            <?php endforeach; ?>
          </select></div>
        </form>
      <?php else: ?>
        <?= e($nombreEst) ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="spacer"></div>
  <?= $acciones ?? '' ?>
</div>
