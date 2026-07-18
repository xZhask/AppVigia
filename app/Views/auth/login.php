<?php
use App\Core\Csrf;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VIGÍA · Iniciar sesión</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Serif:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/theme.css">
</head>
<body style="background:var(--paper);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px">
  <div style="width:100%;max-width:380px">
    <div style="display:flex;align-items:center;gap:11px;justify-content:center;margin-bottom:22px">
      <div class="brand-mark">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M1 12.5 4 12.5 6 7 8.5 15 11 4 13 10.5 17 10.5" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div>
        <div class="brand-name" style="color:var(--ink)">VIGÍA</div>
        <div class="brand-sub" style="color:var(--muted)">Vigilancia · DIRSAPOL</div>
      </div>
    </div>

    <div class="card">
      <div class="section-body">
        <div class="page-title" style="font-size:20px;margin-bottom:4px">Iniciar sesión</div>
        <div class="page-desc" style="margin-bottom:20px">Ingresa con tu cuenta institucional del Área de Estadística</div>

        <?php if (!empty($error)): ?>
          <div class="dupe">
            <span class="di"><svg width="17" height="17" viewBox="0 0 17 17"><path d="M8.5 1.5 16 15H1L8.5 1.5Z" stroke="currentColor" stroke-width="1.3" fill="none" stroke-linejoin="round"/><path d="M8.5 6.5v3.5M8.5 12.3v.1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></span>
            <div class="body"><b>No se pudo iniciar sesión.</b> <?= e($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="post" action="/login">
          <?= Csrf::campoOculto() ?>
          <div class="field" style="margin-bottom:14px">
            <label class="fl">Correo electrónico</label>
            <div class="control">
              <input type="email" name="email" required autofocus placeholder="nombre@dirsapol.gob.pe" value="<?= e($_POST['email'] ?? '') ?>">
            </div>
          </div>
          <div class="field" style="margin-bottom:20px">
            <label class="fl">Contraseña</label>
            <div class="control">
              <input type="password" name="clave" required placeholder="••••••••">
            </div>
          </div>
          <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">
            <svg width="14" height="14" viewBox="0 0 14 14"><path d="M2.5 7.5 6 11l5.5-6.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Ingresar
          </button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
