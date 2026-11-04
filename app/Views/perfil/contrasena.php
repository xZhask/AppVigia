<?php
$campoClave = function (string $nombre, string $etiqueta, string $autocompletar, ?string $ayuda = null) use ($errores): void {
    $error = $errores[$nombre] ?? null; ?>
  <div class="field" style="grid-column:1/-1">
    <label class="fl" for="<?= e($nombre) ?>"><?= e($etiqueta) ?> <span class="req">*</span></label>
    <div class="pw-wrap">
      <div class="control <?= $error ? 'err' : '' ?>">
        <input type="password" id="<?= e($nombre) ?>" name="<?= e($nombre) ?>" autocomplete="<?= e($autocompletar) ?>" style="padding-right:30px" required>
      </div>
      <button type="button" class="pw-toggle" data-mostrar-clave="<?= e($nombre) ?>" aria-label="Mostrar contraseña">
        <svg width="16" height="16" viewBox="0 0 15 15" fill="none" aria-hidden="true">
          <path d="M1.5 7.5S3.5 3 7.5 3s6 4.5 6 4.5-2 4.5-6 4.5-6-4.5-6-4.5Z" stroke="currentColor" stroke-width="1.2"/>
          <circle cx="7.5" cy="7.5" r="1.8" stroke="currentColor" stroke-width="1.2"/>
        </svg>
      </button>
    </div>
    <?php if ($error): ?><span class="hint err"><?= e($error) ?></span>
    <?php elseif ($ayuda): ?><span class="hint"><?= e($ayuda) ?></span><?php endif; ?>
  </div>
<?php }; ?>
<div class="page-head">
  <div>
    <div class="page-title">Cambiar contraseña</div>
    <p style="color:var(--faint);margin:4px 0 0">Si la contraseña te la entregó el área, cámbiala por una que solo tú conozcas.</p>
  </div>
</div>

<div class="card section" style="max-width:480px;">
  <div class="section-body">
    <form method="post" action="/perfil/contrasena">
      <?= \App\Core\Csrf::campoOculto() ?>
      <div class="fields">
        <?php
        $campoClave('clave_actual', 'Contraseña actual', 'current-password');
        $campoClave('clave', 'Contraseña nueva', 'new-password', 'Al menos 8 caracteres. No uses tu número de documento.');
        $campoClave('clave_confirmar', 'Repite la contraseña nueva', 'new-password');
        ?>
      </div>
      <div style="display:flex;gap:10px;margin-top:20px">
        <button class="btn btn-primary" type="submit">Guardar contraseña</button>
        <a class="btn btn-quiet" href="/">Cancelar</a>
      </div>
    </form>
  </div>
</div>
<script src="<?= e(asset('/js/mostrar-clave.js')) ?>"></script>
