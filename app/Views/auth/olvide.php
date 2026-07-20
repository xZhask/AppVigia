<?php
use App\Core\Csrf;

/** Variable: $enviado (bool) */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar contraseña · VIGÍA</title>
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

    <?php if ($enviado): ?>

      <div class="auth-form">
        <h2>Revisa tu correo</h2>
        <p class="lead">
          Si ese correo tiene una cuenta en VIGÍA, te enviamos un enlace para restablecer
          la contraseña. El enlace vence en 1 hora.
        </p>

        <div class="auth-alert-ok">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <circle cx="8" cy="8" r="6.4" stroke="currentColor" stroke-width="1.3"/>
            <path d="M5 8.3 7 10.3 11 5.8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Si no llega en unos minutos, revisa la carpeta de spam antes de solicitarlo de nuevo.</span>
        </div>

        <div class="auth-row" style="justify-content:flex-start">
          <a href="/login" class="auth-link">← Volver a iniciar sesión</a>
        </div>
      </div>

    <?php else: ?>

      <form class="auth-form" method="post" action="/login/olvide">
        <?= Csrf::campoOculto() ?>
        <h2>Recuperar contraseña</h2>
        <p class="lead">Ingresa tu correo institucional. Si tiene una cuenta en VIGÍA, te enviaremos un enlace para restablecer la contraseña.</p>

        <div class="auth-field">
          <label class="fl" for="email">Correo institucional</label>
          <div class="control">
            <svg class="lead" width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
              <rect x="1.5" y="3" width="12" height="9" rx="1.6" stroke="currentColor" stroke-width="1.3"/>
              <path d="m2 4 5.5 4L13 4" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
            </svg>
            <input type="email" id="email" name="email" placeholder="nombre@dirsapol.gob.pe"
                   autocomplete="username" autofocus spellcheck="false" required>
          </div>
        </div>

        <button class="btn btn-primary auth-submit" type="submit" id="submitBtn" data-loading-text="Enviando…">
          <span id="submitLabel">Enviar enlace</span>
          <svg width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
            <path d="M3 7.5h8M7.8 4.3 11 7.5l-3.2 3.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

        <div class="auth-row" style="justify-content:flex-start;margin-top:16px">
          <a href="/login" class="auth-link">← Volver a iniciar sesión</a>
        </div>
      </form>

    <?php endif; ?>
  </section>
</div>

<script src="/js/auth.js"></script>
</body>
</html>
