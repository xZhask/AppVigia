<?php
use App\Core\Csrf;

$esEdicion = !empty($usuario['id']);
$accion = $esEdicion ? '/catalogos/usuarios/' . (int) $usuario['id'] : '/catalogos/usuarios';
$manual = !empty($usuario['mostrar_manual']);
// Nombre ya resuelto (edición, o el formulario devuelto con errores).
$nombrePersona = !$manual ? trim((string) ($usuario['nombre_persona'] ?? $usuario['nombre'] ?? '')) : '';
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
            <select name="tipo_doc" id="usuarioTipoDoc">
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
            <input type="text" name="num_doc" id="usuarioNumDoc" value="<?= e($usuario['num_doc'] ?? '') ?>" required autocomplete="off">
          </div>
          <?php if (isset($errores['documento'])): ?><span class="hint err"><?= e($errores['documento']) ?></span><?php endif; ?>
        </div>
        <?php // Apellidos y nombres del documento (2026-11-09): se ven antes de guardar, para confirmar que es la persona. ?>
        <div class="field usuario-persona-campo">
          <label class="fl">Apellidos y nombres</label>
          <div class="usuario-persona <?= $nombrePersona !== '' ? 'ok' : '' ?>" id="usuarioPersona" aria-live="polite"
               data-excluir="<?= $esEdicion ? (int) $usuario['id'] : '' ?>" data-inicial="<?= e($nombrePersona) ?>"
               data-doc-inicial="<?= e(($usuario['tipo_doc'] ?? '') . '|' . ($usuario['num_doc'] ?? '')) ?>">
            <?= $nombrePersona !== '' ? '<b>' . e($nombrePersona) . '</b>' : ($manual ? 'No se encontró en RENIEC ni en el padrón local. Completa los apellidos y nombres abajo.' : 'Ingresa el documento para ver a quién corresponde.') ?>
          </div>
        </div>
        <div class="field" data-manual <?= $manual ? '' : 'hidden' ?>>
          <label class="fl">Apellido paterno <span class="req">*</span></label>
          <div class="control">
            <input type="text" name="apellido_paterno" value="<?= e($usuario['apellido_paterno'] ?? '') ?>" data-requerido <?= $manual ? 'required' : '' ?>>
          </div>
        </div>
        <div class="field" data-manual <?= $manual ? '' : 'hidden' ?>>
          <label class="fl">Apellido materno</label>
          <div class="control">
            <input type="text" name="apellido_materno" value="<?= e($usuario['apellido_materno'] ?? '') ?>">
          </div>
        </div>
        <div class="field" data-manual <?= $manual ? '' : 'hidden' ?>>
          <label class="fl">Nombres <span class="req">*</span></label>
          <div class="control">
            <input type="text" name="nombres" value="<?= e($usuario['nombres'] ?? '') ?>" data-requerido <?= $manual ? 'required' : '' ?>>
          </div>
        </div>
        <div class="field">
          <label class="fl">Correo electrónico</label>
          <div class="control mono <?= isset($errores['email']) ? 'err' : '' ?>">
            <input type="email" name="email" value="<?= e($usuario['email']) ?>">
          </div>
          <?php if (isset($errores['email'])): ?><span class="hint err"><?= e($errores['email']) ?></span>
          <?php else: ?><span class="hint">Opcional y personal: no puede repetirse, porque sirve para recuperar la contraseña. El correo del área va en el establecimiento (Correo de la IPRESS).</span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">Rol <span class="req">*</span></label>
          <div class="control <?= isset($errores['rol']) ? 'err' : '' ?>">
            <select name="rol">
              <option value="REGISTRADOR" <?= seleccionado($usuario['rol'], 'REGISTRADOR') ?>>Registrador/a</option>
              <option value="JEFE_RED" <?= seleccionado($usuario['rol'], 'JEFE_RED') ?>>Jefe/a de red</option>
              <option value="ADMIN" <?= seleccionado($usuario['rol'], 'ADMIN') ?>>Administrador</option>
            </select>
          </div>
        </div>
        <div class="field" data-solo-jefe-red <?= $usuario['rol'] === 'JEFE_RED' ? '' : 'hidden' ?>>
          <label class="fl">Red <span class="req">*</span></label>
          <div class="control <?= isset($errores['red_id']) ? 'err' : '' ?>">
            <select name="red_id">
              <option value="">Sin asignar</option>
              <?php foreach (\App\Models\RedSalud::todos('nombre') as $red): ?>
                <option value="<?= (int) $red['id'] ?>" <?= seleccionado($usuario['red_id'] ?? '', $red['id']) ?>><?= e($red['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <span class="hint">Verá las cifras y el cumplimiento de esa red, sin abrir fichas.</span>
          <?php if (isset($errores['red_id'])): ?><span class="hint err"><?= e($errores['red_id']) ?></span><?php endif; ?>
        </div>
        <div class="field" data-no-jefe-red <?= $usuario['rol'] === 'JEFE_RED' ? 'hidden' : '' ?>>
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
<script src="<?= e(asset('/js/usuario-rol.js')) ?>"></script>
<script src="<?= e(asset('/js/usuario-documento.js')) ?>"></script>
