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
        <div class="field">
          <label class="fl">Tipo de documento <span class="req">*</span></label>
          <div class="control <?= isset($errores['documento']) ? 'err' : '' ?>">
            <select name="tipo_doc">
              <option value="DNI" <?= seleccionado($usuario['tipo_doc'] ?? '', 'DNI') ?>>DNI (RENIEC)</option>
              <option value="CE" <?= seleccionado($usuario['tipo_doc'] ?? '', 'CE') ?>>Carnet de Extranjería</option>
              <option value="PAS" <?= seleccionado($usuario['tipo_doc'] ?? '', 'PAS') ?>>Pasaporte</option>
              <option value="PTP" <?= seleccionado($usuario['tipo_doc'] ?? '', 'PTP') ?>>PTP</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label class="fl">N.° de documento <span class="req">*</span></label>
          <div class="control <?= isset($errores['documento']) ? 'err' : '' ?>">
            <input type="text" name="num_doc" value="<?= e($usuario['num_doc'] ?? '') ?>" required>
          </div>
          <?php if (isset($errores['documento'])): ?><span class="hint err"><?= e($errores['documento']) ?></span><?php endif; ?>
        </div>
        <?php if (!empty($usuario['mostrar_manual'])): ?>
        <div class="field" style="flex-basis:100%;">
          <p class="hint">No se encontró este documento en RENIEC ni en el padrón local. Completa los datos para registrarlo manualmente.</p>
        </div>
        <div class="field">
          <label class="fl">Apellido paterno <span class="req">*</span></label>
          <div class="control">
            <input type="text" name="apellido_paterno" value="<?= e($usuario['apellido_paterno'] ?? '') ?>" required>
          </div>
        </div>
        <div class="field">
          <label class="fl">Apellido materno</label>
          <div class="control">
            <input type="text" name="apellido_materno" value="<?= e($usuario['apellido_materno'] ?? '') ?>">
          </div>
        </div>
        <div class="field">
          <label class="fl">Nombres <span class="req">*</span></label>
          <div class="control">
            <input type="text" name="nombres" value="<?= e($usuario['nombres'] ?? '') ?>" required>
          </div>
        </div>
        <?php endif; ?>
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
              <option value="ADMIN" <?= seleccionado($usuario['rol'], 'ADMIN') ?>>Administrador</option>
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
