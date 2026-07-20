<?php
use App\Core\Csrf;

/** Variables: $error (?string), $intentosRestantes (?int), $mensaje (?string) */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión · VIGÍA</title>
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

    <form class="auth-form" method="post" action="/login" autocomplete="on">
      <?= Csrf::campoOculto() ?>
      <h2>Iniciar sesión</h2>
      <p class="lead">Acceso para el personal autorizado de las IPRESS PNP y del Área de Estadística.</p>

      <?php if (!empty($mensaje)): ?>
        <div class="auth-alert-ok">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <circle cx="8" cy="8" r="6.4" stroke="currentColor" stroke-width="1.3"/>
            <path d="M5 8.3 7 10.3 11 5.8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span><?= e($mensaje) ?></span>
        </div>
      <?php endif; ?>

      <!-- Estado de error: genérico. Nunca indica si el correo existe. -->
      <?php if (!empty($error)): ?>
        <div class="auth-alert">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <circle cx="8" cy="8" r="6.4" stroke="currentColor" stroke-width="1.3"/>
            <path d="M8 5v3.6M8 10.8v.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <span>
            <?= e($error) ?>
            <?php if ($intentosRestantes !== null): ?>
              <?php if ($intentosRestantes > 0): ?>
                Te quedan <b><?= (int) $intentosRestantes ?> intento<?= $intentosRestantes === 1 ? '' : 's' ?></b> antes de que la cuenta se bloquee temporalmente.
              <?php else: ?>
                La cuenta quedó bloqueada temporalmente por demasiados intentos fallidos.
              <?php endif; ?>
            <?php endif; ?>
          </span>
        </div>
      <?php endif; ?>

      <div class="auth-field">
        <label class="fl" for="email">Correo institucional</label>
        <div class="control">
          <svg class="lead" width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
            <rect x="1.5" y="3" width="12" height="9" rx="1.6" stroke="currentColor" stroke-width="1.3"/>
            <path d="m2 4 5.5 4L13 4" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
          </svg>
          <input type="email" id="email" name="email" placeholder="nombre@dirsapol.gob.pe"
                 value="<?= e($_POST['email'] ?? '') ?>"
                 autocomplete="username" autofocus spellcheck="false" required>
        </div>
      </div>

      <div class="auth-field">
        <label class="fl" for="password">Contraseña</label>
        <div class="pw-wrap">
          <div class="control">
            <svg class="lead" width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
              <rect x="2.5" y="6.5" width="10" height="7" rx="1.6" stroke="currentColor" stroke-width="1.3"/>
              <path d="M5 6.5V4.8a2.5 2.5 0 0 1 5 0v1.7" stroke="currentColor" stroke-width="1.3"/>
            </svg>
            <input type="password" id="password" name="clave" placeholder="••••••••"
                   autocomplete="current-password" style="padding-right:30px" required>
          </div>
          <button type="button" class="pw-toggle" id="pwBtn" aria-label="Mostrar contraseña">
            <svg width="16" height="16" viewBox="0 0 15 15" fill="none" aria-hidden="true">
              <path d="M1.5 7.5S3.5 3 7.5 3s6 4.5 6 4.5-2 4.5-6 4.5-6-4.5-6-4.5Z" stroke="currentColor" stroke-width="1.2"/>
              <circle cx="7.5" cy="7.5" r="1.8" stroke="currentColor" stroke-width="1.2"/>
            </svg>
          </button>
        </div>
        <span class="hint" id="capsHint" hidden>Bloq Mayús está activado.</span>
      </div>

      <div class="auth-row">
        <span></span>
        <a href="/login/olvide" class="auth-link">¿Olvidaste tu contraseña?</a>
      </div>

      <button class="btn btn-primary auth-submit" type="submit" id="submitBtn" data-loading-text="Verificando…">
        <span id="submitLabel">Ingresar</span>
        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
          <path d="M3 7.5h8M7.8 4.3 11 7.5l-3.2 3.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>

      <p class="auth-note">
        Sistema de uso institucional restringido. Los accesos y las acciones sobre las fichas quedan registrados.
        Si necesitas una cuenta, solicítala al Área de Estadística de la OFIGCS&nbsp;–&nbsp;DIRSAPOL.
      </p>
    </form>
  </section>
</div>

<script src="/js/auth.js"></script>
</body>
</html>
