<div class="page-head">
  <div>
    <div class="page-title">Completar Perfil</div>
    <p style="color:var(--faint);margin:4px 0 0">Para continuar, necesitas enlazar tu cuenta con tu documento de identidad.</p>
  </div>
</div>

<div class="card section" style="max-width:540px;">
  <div class="section-body">
    <form method="post" action="/perfil/completar">
      <?= \App\Core\Csrf::campoOculto() ?>

      <div class="fields">
        <div class="field">
          <label class="fl">Tipo de documento <span class="req">*</span></label>
          <div class="control">
            <select name="tipo_doc" id="tipoDoc" data-nosearch="true">
              <option value="DNI" <?= seleccionado($valores['tipo_doc'] ?? 'DNI', 'DNI') ?>>DNI (RENIEC)</option>
              <option value="CE" <?= seleccionado($valores['tipo_doc'] ?? '', 'CE') ?>>Carnet de Extranjería</option>
              <option value="PAS" <?= seleccionado($valores['tipo_doc'] ?? '', 'PAS') ?>>Pasaporte</option>
              <option value="PTP" <?= seleccionado($valores['tipo_doc'] ?? '', 'PTP') ?>>PTP</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label class="fl">N.° de documento <span class="req">*</span></label>
          <div class="control <?= isset($errores['documento']) ? 'err' : '' ?>">
            <input type="text" name="num_doc" id="numDoc" value="<?= e($valores['num_doc'] ?? '') ?>" required autocomplete="off" placeholder="Ingresa tu N.° de documento">
          </div>
          <?php if (isset($errores['documento'])): ?>
            <span class="hint err"><?= e($errores['documento']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="hint" style="margin-top:1rem;">
        Si tu documento es DNI, los datos se buscarán automáticamente en RENIEC.
        Para otros tipos de documento, se verificará el padrón local del sistema.
      </div>

      <?php if (!empty($mostrarManual)): ?>
      <div class="fields" style="margin-top:1rem;border-top:1px solid var(--line);padding-top:1rem;">
        <div class="field" style="flex-basis:100%;">
          <p class="hint">No encontramos tu documento en RENIEC ni en el padrón local. Completa tus datos para registrarte.</p>
        </div>
        <div class="field">
          <label class="fl">Apellido paterno <span class="req">*</span></label>
          <div class="control">
            <input type="text" name="apellido_paterno" value="<?= e($valores['apellido_paterno'] ?? '') ?>" required>
          </div>
        </div>
        <div class="field">
          <label class="fl">Apellido materno</label>
          <div class="control">
            <input type="text" name="apellido_materno" value="<?= e($valores['apellido_materno'] ?? '') ?>">
          </div>
        </div>
        <div class="field">
          <label class="fl">Nombres <span class="req">*</span></label>
          <div class="control">
            <input type="text" name="nombres" value="<?= e($valores['nombres'] ?? '') ?>" required>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div style="display:flex;gap:10px;margin-top:20px">
        <button class="btn btn-primary" type="submit">
          <svg width="14" height="14" viewBox="0 0 14 14"><path d="M2.5 7.5 6 11l5.5-6.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Enlazar y Completar Perfil
        </button>
      </div>
    </form>
  </div>
</div>
