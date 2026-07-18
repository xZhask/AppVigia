<?php
use App\Core\Csrf;

$esEdicion = !empty($establecimiento['id']);
$accion = $esEdicion ? '/catalogos/establecimientos/' . (int) $establecimiento['id'] : '/catalogos/establecimientos';
$prefijoUbigeo = 'est-ubigeo';
?>
<div class="page-head">
  <div>
    <div class="page-title"><?= $esEdicion ? 'Editar establecimiento' : 'Nuevo establecimiento' ?></div>
    <div class="page-desc">Datos del establecimiento tal como aparecen en el padrón RENIPRESS</div>
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
            <input type="text" name="nombre" value="<?= e($establecimiento['nombre']) ?>" required>
          </div>
          <?php if (isset($errores['nombre'])): ?><span class="hint err"><?= e($errores['nombre']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">Código RENIPRESS</label>
          <div class="control mono">
            <input type="text" name="cod_renipress" value="<?= e($establecimiento['cod_renipress']) ?>">
          </div>
        </div>
        <div class="field">
          <label class="fl">Red de salud</label>
          <div class="control">
            <select name="red_id">
              <option value="">Sin asignar</option>
              <?php foreach ($redes as $red): ?>
                <option value="<?= (int) $red['id'] ?>" <?= seleccionado($establecimiento['red_id'], $red['id']) ?>><?= e($red['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="field">
          <label class="fl">Institución <span class="req">*</span></label>
          <div class="control <?= isset($errores['institucion']) ? 'err' : '' ?>">
            <select name="institucion">
              <option value="FFAA_SANIDAD" <?= seleccionado($establecimiento['institucion'], 'FFAA_SANIDAD') ?>>Sanidad FFAA</option>
              <option value="MINSA" <?= seleccionado($establecimiento['institucion'], 'MINSA') ?>>MINSA</option>
              <option value="ESSALUD" <?= seleccionado($establecimiento['institucion'], 'ESSALUD') ?>>EsSalud</option>
              <option value="PRIVADO" <?= seleccionado($establecimiento['institucion'], 'PRIVADO') ?>>Privado</option>
            </select>
          </div>
        </div>
      </div>

      <div class="eyebrow" style="margin:20px 0 12px">Ubicación</div>
      <?php $prefijo = $prefijoUbigeo; require __DIR__ . '/../../partials/selector-ubigeo.php'; ?>

      <div style="margin-top:16px">
        <label class="sym"><input type="checkbox" name="activo" <?= marcado($establecimiento['activo']) ?>> Establecimiento activo</label>
      </div>

      <div style="display:flex;gap:10px;margin-top:20px">
        <button class="btn btn-primary" type="submit">
          <svg width="14" height="14" viewBox="0 0 14 14"><path d="M2.5 7.5 6 11l5.5-6.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?= $esEdicion ? 'Guardar cambios' : 'Registrar establecimiento' ?>
        </button>
        <a class="btn btn-ghost" href="/catalogos/establecimientos">Cancelar</a>
      </div>
    </form>
  </div>
</div>
