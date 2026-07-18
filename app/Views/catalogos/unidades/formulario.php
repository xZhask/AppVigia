<?php
use App\Core\Csrf;

$esEdicion = !empty($unidad['id']);
$accion = $esEdicion ? '/catalogos/unidades/' . (int) $unidad['id'] : '/catalogos/unidades';
$prefijo = 'unidad-ubigeo';
?>
<div class="page-head">
  <div>
    <div class="page-title"><?= $esEdicion ? 'Editar unidad PNP' : 'Nueva unidad PNP' ?></div>
  </div>
</div>

<div class="card section">
  <div class="section-body">
    <form method="post" action="<?= e($accion) ?>">
      <?= Csrf::campoOculto() ?>
      <div class="fields">
        <div class="field wide">
          <label class="fl">Nombre <span class="req">*</span></label>
          <div class="control <?= isset($errores['nombre']) ? 'err' : '' ?>">
            <input type="text" name="nombre" value="<?= e($unidad['nombre']) ?>" required>
          </div>
          <?php if (isset($errores['nombre'])): ?><span class="hint err"><?= e($errores['nombre']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">Tipo</label>
          <div class="control">
            <input type="text" name="tipo" value="<?= e($unidad['tipo']) ?>" placeholder="Comisaría, Dirección, Escuela…">
          </div>
        </div>
      </div>

      <div class="eyebrow" style="margin:20px 0 12px">Ubicación</div>
      <?php require __DIR__ . '/../../partials/selector-ubigeo.php'; ?>

      <div style="margin-top:16px">
        <label class="sym"><input type="checkbox" name="activo" <?= marcado($unidad['activo']) ?>> Unidad activa</label>
      </div>

      <div style="display:flex;gap:10px;margin-top:20px">
        <button class="btn btn-primary" type="submit">
          <svg width="14" height="14" viewBox="0 0 14 14"><path d="M2.5 7.5 6 11l5.5-6.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?= $esEdicion ? 'Guardar cambios' : 'Registrar unidad' ?>
        </button>
        <a class="btn btn-ghost" href="/catalogos/unidades">Cancelar</a>
      </div>
    </form>
  </div>
</div>
