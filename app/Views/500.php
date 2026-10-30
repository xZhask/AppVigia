<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VIGÍA PNP · Error inesperado</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Serif:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" href="<?= e(asset('/favicon.svg')) ?>" type="image/svg+xml">
<link rel="icon" href="<?= e(asset('/favicon-32.png')) ?>" sizes="32x32">
<link rel="apple-touch-icon" href="<?= e(asset('/apple-touch-icon.png')) ?>">
<link rel="stylesheet" href="<?= e(asset('/css/theme.css')) ?>">
</head>
<body>
<div class="view" style="max-width:520px;padding:80px 28px;text-align:center;margin:0 auto">
  <div class="page-title" style="margin-bottom:8px">Ocurrió un error inesperado</div>
  <p class="page-desc" style="margin-bottom:20px">El sistema no pudo completar la acción. El detalle técnico quedó registrado; si el problema persiste, comunícalo al Área de Epidemiología<?= !empty($codigoError) ? ' con este código' : '' ?>.</p>
  <?php if (!empty($codigoError)): ?>
    <div class="error-codigo"><span>Código del error</span><b class="mono"><?= e($codigoError) ?></b></div>
  <?php endif; ?>
  <a class="btn btn-primary" href="/">Volver al panel</a>
</div>
</body>
</html>
