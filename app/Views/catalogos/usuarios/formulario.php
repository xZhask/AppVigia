<?php
use App\Core\Csrf;

$esEdicion = !empty($usuario['id']);
$accion = $esEdicion ? '/catalogos/usuarios/' . (int) $usuario['id'] : '/catalogos/usuarios';
?>
<div class="page-head">
  <div>
    <div class="page-title"><?= $esEdicion ? 'Editar usuario' : 'Nuevo usuario' ?></div>
  </div>
</div>

<div class="card section">
  <div class="section-body">
    <form method="post" action="<?= e($accion) ?>">
      <?= Csrf::campoOculto() ?>
      <div class="fields">
        <div class="field wide">
          <label class="fl">Nombre completo <span class="req">*</span></label>
          <div class="control <?= isset($errores['nombre']) ? 'err' : '' ?>">
            <input type="text" name="nombre" value="<?= e($usuario['nombre']) ?>" required>
          </div>
          <?php if (isset($errores['nombre'])): ?><span class="hint err"><?= e($errores['nombre']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">Correo electrónico <span class="req">*</span></label>
          <div class="control mono <?= isset($errores['email']) ? 'err' : '' ?>">
            <input type="email" name="email" value="<?= e($usuario['email']) ?>" required>
          </div>
          <?php if (isset($errores['email'])): ?><span class="hint err"><?= e($errores['email']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">Rol <span class="req">*</span></label>
          <div class="control <?= isset($errores['rol']) ? 'err' : '' ?>">
            <select name="rol">
              <option value="REGISTRADOR" <?= seleccionado($usuario['rol'], 'REGISTRADOR') ?>>Registrador/a</option>
              <option value="EPIDEMIOLOGO" <?= seleccionado($usuario['rol'], 'EPIDEMIOLOGO') ?>>Epidemiólogo/a</option>
              <option value="ADMIN" <?= seleccionado($usuario['rol'], 'ADMIN') ?>>Administrador</option>
              <option value="LECTOR" <?= seleccionado($usuario['rol'], 'LECTOR') ?>>Lector/a</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label class="fl">Establecimiento</label>
          <div class="control <?= isset($errores['establecimiento_id']) ? 'err' : '' ?>">
            <select name="establecimiento_id">
              <option value="">Sin asignar</option>
              <?php foreach ($establecimientos as $est): ?>
                <option value="<?= (int) $est['id'] ?>" <?= seleccionado($usuario['establecimiento_id'], $est['id']) ?>><?= e($est['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <span class="hint">Obligatorio para el rol Registrador/a</span>
          <?php if (isset($errores['establecimiento_id'])): ?><span class="hint err"><?= e($errores['establecimiento_id']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">Contraseña <?= $esEdicion ? '' : '<span class="req">*</span>' ?></label>
          <div class="control <?= isset($errores['clave']) ? 'err' : '' ?>">
            <input type="password" name="clave" placeholder="<?= $esEdicion ? 'Dejar en blanco para no cambiarla' : 'Mínimo 8 caracteres' ?>">
          </div>
          <?php if (isset($errores['clave'])): ?><span class="hint err"><?= e($errores['clave']) ?></span><?php endif; ?>
        </div>
      </div>

      <div style="margin-top:16px">
        <label class="sym"><input type="checkbox" name="activo" <?= marcado($usuario['activo']) ?>> Usuario activo</label>
      </div>

      <div style="display:flex;gap:10px;margin-top:20px">
        <button class="btn btn-primary" type="submit">
          <svg width="14" height="14" viewBox="0 0 14 14"><path d="M2.5 7.5 6 11l5.5-6.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?= $esEdicion ? 'Guardar cambios' : 'Registrar usuario' ?>
        </button>
        <a class="btn btn-ghost" href="/catalogos/usuarios">Cancelar</a>
      </div>
    </form>
  </div>
</div>
