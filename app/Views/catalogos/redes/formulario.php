<?php
use App\Core\Csrf;

$esEdicion = !empty($red['id']);
$accion = $esEdicion ? '/catalogos/redes/' . (int) $red['id'] : '/catalogos/redes';
?>
<div class="page-head">
  <div>
    <div class="page-title"><?= $esEdicion ? 'Editar red de salud' : 'Nueva red de salud' ?></div>
  </div>
</div>

<div class="card section">
  <div class="section-body">
    <form method="post" action="<?= e($accion) ?>">
      <?= Csrf::campoOculto() ?>
      <div class="fields">
        <div class="field">
          <label class="fl">Nombre <span class="req">*</span></label>
          <div class="control <?= isset($errores['nombre']) ? 'err' : '' ?>">
            <input type="text" name="nombre" value="<?= e($red['nombre']) ?>" required>
          </div>
          <?php if (isset($errores['nombre'])): ?><span class="hint err"><?= e($errores['nombre']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">DIRESA / DIRIS</label>
          <div class="control">
            <input type="text" name="diresa" value="<?= e($red['diresa']) ?>">
          </div>
        </div>
      </div>

      <div style="display:flex;gap:10px;margin-top:20px">
        <button class="btn btn-primary" type="submit">
          <svg width="14" height="14" viewBox="0 0 14 14"><path d="M2.5 7.5 6 11l5.5-6.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?= $esEdicion ? 'Guardar cambios' : 'Registrar red' ?>
        </button>
        <a class="btn btn-ghost" href="/catalogos/redes">Cancelar</a>
      </div>
    </form>
  </div>
</div>
