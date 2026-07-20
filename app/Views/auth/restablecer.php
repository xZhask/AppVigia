<?php
use App\Core\Csrf;

/** Variables: $token (string), $valido (bool), $errores (array) */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Restablecer contraseña · VIGÍA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Serif:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/theme.css">
<link rel="stylesheet" href="/css/dark.css">
<script>
  (function(){
    var t = document.cookie.match(/(^|; )theme=([^;]+)/);
    t = t ? t[2] : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
  })();
</script>
</head>
<body>

<div class="auth">

  <?php require __DIR__ . '/../partials/auth-brand.php'; ?>

  <!-- ============ FORMULARIO ============ -->
  <section class="auth-form-side">

    <div class="auth-tools">
      <button class="icon-btn" id="themeToggle" aria-label="Cambiar tema" title="Cambiar tema">
        <svg class="ic-moon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M14 10.5a6.5 6.5 0 0 1-8.5-8.5A7 7 0 1 0 14 10.5Z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <svg class="ic-sun" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" style="display:none"><circle cx="8" cy="8" r="3.5" stroke="currentColor" stroke-width="1.4"/><path d="M8 2V1M8 15v-1M2 8H1M15 8h-1M4 4l-.5-.5M12.5 12.5l-.5-.5M4 12l-.5.5M12.5 3.5l-.5.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      </button>
    </div>

    <?php if (!$valido): ?>

      <div class="auth-form">
        <h2>Enlace no disponible</h2>
        <p class="lead">Este enlace de restablecimiento ya se usó, venció o no es válido. Solicita uno nuevo.</p>

        <div class="auth-alert">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <circle cx="8" cy="8" r="6.4" stroke="currentColor" stroke-width="1.3"/>
            <path d="M8 5v3.6M8 10.8v.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <span>Los enlaces de restablecimiento son de un solo uso y vencen 1 hora después de solicitarlos.</span>
        </div>

        <a href="/login/olvide" class="btn btn-primary auth-submit" style="text-decoration:none">
          Solicitar un enlace nuevo
        </a>
      </div>

    <?php else: ?>

      <form class="auth-form" method="post" action="/login/restablecer">
        <?= Csrf::campoOculto() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <h2>Elige una contraseña nueva</h2>
        <p class="lead">Mínimo 8 caracteres. Úsala solo para tu cuenta institucional de VIGÍA.</p>

        <?php if (!empty($errores['general'])): ?>
          <div class="auth-alert">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
              <circle cx="8" cy="8" r="6.4" stroke="currentColor" stroke-width="1.3"/>
              <path d="M8 5v3.6M8 10.8v.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span><?= e($errores['general']) ?></span>
          </div>
        <?php endif; ?>

        <div class="auth-field">
          <label class="fl" for="password">Contraseña nueva</label>
          <div class="pw-wrap">
            <div class="control <?= isset($errores['clave']) ? 'err' : '' ?>">
              <svg class="lead" width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
                <rect x="2.5" y="6.5" width="10" height="7" rx="1.6" stroke="currentColor" stroke-width="1.3"/>
                <path d="M5 6.5V4.8a2.5 2.5 0 0 1 5 0v1.7" stroke="currentColor" stroke-width="1.3"/>
              </svg>
              <input type="password" id="password" name="clave" placeholder="Mínimo 8 caracteres"
                     autocomplete="new-password" style="padding-right:30px" required>
            </div>
            <button type="button" class="pw-toggle" id="pwBtn" aria-label="Mostrar contraseña">
              <svg width="16" height="16" viewBox="0 0 15 15" fill="none" aria-hidden="true">
                <path d="M1.5 7.5S3.5 3 7.5 3s6 4.5 6 4.5-2 4.5-6 4.5-6-4.5-6-4.5Z" stroke="currentColor" stroke-width="1.2"/>
                <circle cx="7.5" cy="7.5" r="1.8" stroke="currentColor" stroke-width="1.2"/>
              </svg>
            </button>
          </div>
          <?php if (!empty($errores['clave'])): ?><span class="hint err"><?= e($errores['clave']) ?></span><?php endif; ?>
          <span class="hint" id="capsHint" hidden>Bloq Mayús está activado.</span>
        </div>

        <div class="auth-field">
          <label class="fl" for="password_confirmar">Confirmar contraseña</label>
          <div class="control <?= isset($errores['clave_confirmar']) ? 'err' : '' ?>">
            <svg class="lead" width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
              <rect x="2.5" y="6.5" width="10" height="7" rx="1.6" stroke="currentColor" stroke-width="1.3"/>
              <path d="M5 6.5V4.8a2.5 2.5 0 0 1 5 0v1.7" stroke="currentColor" stroke-width="1.3"/>
            </svg>
            <input type="password" id="password_confirmar" name="clave_confirmar" placeholder="Repite la contraseña"
                   autocomplete="new-password" required>
          </div>
          <?php if (!empty($errores['clave_confirmar'])): ?><span class="hint err"><?= e($errores['clave_confirmar']) ?></span><?php endif; ?>
        </div>

        <button class="btn btn-primary auth-submit" type="submit" id="submitBtn" data-loading-text="Guardando…">
          <span id="submitLabel">Guardar contraseña</span>
          <svg width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
            <path d="M3 7.5h8M7.8 4.3 11 7.5l-3.2 3.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </form>

    <?php endif; ?>
  </section>
</div>

<script src="/js/auth.js"></script>
</body>
</html>
