<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;

$mensajeFlash = Flash::obtener();
$rutaActual = $rutaActual ?? '';
$tituloVista = $tituloVista ?? 'Panel';
$usuarioActual = Auth::usuario();

$totalFichas = (int) Database::conexion()->query('SELECT COUNT(*) FROM caso')->fetchColumn();

$anioEpi = (int) date('Y');
$semanaEpi = (int) date('W');

$rolesEtiqueta = [
    'ADMIN'       => 'Administrador',
    'REGISTRADOR' => 'Registrador/a',
];

$navItems = [
    ['ruta' => '',            'etiqueta' => 'Panel',       'crumb' => 'Panel'],
    ['ruta' => 'casos',       'etiqueta' => 'Fichas',      'crumb' => 'Fichas', 'contador' => $totalFichas],
    ['ruta' => 'casos/nuevo', 'etiqueta' => 'Nueva ficha', 'crumb' => 'Nueva ficha'],
    ['ruta' => 'reportes',    'etiqueta' => 'Reportes',    'crumb' => 'Reportes'],
    ['ruta' => 'catalogos/establecimientos', 'etiqueta' => 'Establecimientos', 'crumb' => 'Establecimientos'],
    ['ruta' => 'catalogos/enfermedades',     'etiqueta' => 'Enfermedades',     'crumb' => 'Enfermedades'],
    ['ruta' => 'catalogos/usuarios',         'etiqueta' => 'Usuarios',         'crumb' => 'Usuarios'],
];

$crumbActual = 'Panel';
$coincidenciaExacta = false;
foreach ($navItems as $item) {
    if ($item['ruta'] === $rutaActual) {
        $crumbActual = $item['crumb'];
        $coincidenciaExacta = true;
        break;
    }
}
if (!$coincidenciaExacta) {
    foreach ($navItems as $item) {
        if (str_starts_with($item['ruta'], 'catalogos/') && str_starts_with($rutaActual, $item['ruta'] . '/')) {
            $crumbActual = $item['crumb'];
            break;
        }
    }
}

$puedeVerCatalogos = Auth::tieneRol('ADMIN');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VIGÍA · <?= htmlspecialchars($tituloVista) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Serif:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/theme.css">
</head>
<body<?= $mensajeFlash !== null ? ' data-flash="' . e($mensajeFlash) . '"' : '' ?>>
<div class="app">

  <!-- ============ SIDEBAR ============ -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M1 12.5 4 12.5 6 7 8.5 15 11 4 13 10.5 17 10.5" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div>
        <div class="brand-name">VIGÍA</div>
        <div class="brand-sub">Vigilancia · DIRSAPOL</div>
      </div>
    </div>

    <nav class="nav">
      <div class="nav-label">Operación</div>
      <a class="nav-item<?= $rutaActual === '' ? ' active' : '' ?>" href="/">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none"><path d="M2 9.5 5 9.5 7 5 9.5 13 12 3 14 8.5 15.5 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Panel
      </a>
      <a class="nav-item<?= $rutaActual === 'casos' ? ' active' : '' ?>" href="/casos">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none"><rect x="2.5" y="2" width="12" height="13" rx="2" stroke="currentColor" stroke-width="1.4"/><path d="M5.5 6h6M5.5 9h6M5.5 12h3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
        Fichas
        <span class="count mono"><?= number_format($totalFichas, 0, ',', ' ') ?></span>
      </a>
      <a class="nav-item<?= $rutaActual === 'casos/nuevo' ? ' active' : '' ?>" href="/casos/nuevo">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none"><path d="M8.5 3.5v10M3.5 8.5h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Nueva ficha
      </a>
      <a class="nav-item<?= $rutaActual === 'reportes' ? ' active' : '' ?>" href="/reportes">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none"><path d="M3 14V7M6.5 14V4M10 14v-5M13.5 14V3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Reportes
      </a>

      <?php if ($puedeVerCatalogos): ?>
      <div class="nav-label">Catálogos</div>
      <a class="nav-item<?= $rutaActual === 'catalogos/establecimientos' || str_starts_with($rutaActual, 'catalogos/establecimientos/') || $rutaActual === 'catalogos/redes' || str_starts_with($rutaActual, 'catalogos/redes/') ? ' active' : '' ?>" href="/catalogos/establecimientos">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none"><path d="M8.5 2 3 5v9h11V5L8.5 2Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M7 14v-3h3v3" stroke="currentColor" stroke-width="1.4"/></svg>
        Establecimientos
      </a>
      <a class="nav-item<?= $rutaActual === 'catalogos/enfermedades' || str_starts_with($rutaActual, 'catalogos/enfermedades/') ? ' active' : '' ?>" href="/catalogos/enfermedades">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none"><circle cx="8.5" cy="8.5" r="6" stroke="currentColor" stroke-width="1.4"/><path d="M8.5 5.5v6M5.5 8.5h6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
        Enfermedades
      </a>
      <a class="nav-item<?= $rutaActual === 'catalogos/usuarios' || str_starts_with($rutaActual, 'catalogos/usuarios/') || $rutaActual === 'catalogos/grados' || str_starts_with($rutaActual, 'catalogos/grados/') || $rutaActual === 'catalogos/unidades' || str_starts_with($rutaActual, 'catalogos/unidades/') ? ' active' : '' ?>" href="/catalogos/usuarios">
        <svg width="17" height="17" viewBox="0 0 17 17" fill="none"><circle cx="8.5" cy="6" r="2.6" stroke="currentColor" stroke-width="1.4"/><path d="M3.5 14c0-2.6 2.2-4.2 5-4.2S13.5 11.4 13.5 14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
        Usuarios
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-foot">
      <div class="avatar"><?= e(iniciales($usuarioActual['nombre'] ?? '')) ?></div>
      <div class="who"><?= e($usuarioActual['nombre'] ?? '') ?><small><?= e($rolesEtiqueta[$usuarioActual['rol'] ?? ''] ?? '') ?></small></div>
      <form method="post" action="/logout" style="margin-left:auto">
        <?= Csrf::campoOculto() ?>
        <button class="icon-btn" type="submit" title="Cerrar sesión" style="background:transparent;border-color:var(--sidebar-line);color:var(--sidebar-text)">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 2.5H3.5a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1H6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><path d="M13 8H6.5M13 8l-2.5-2.3M13 8l-2.5 2.3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </form>
    </div>
  </aside>

  <!-- ============ MAIN ============ -->
  <div class="main">
    <header class="topbar">
      <button class="icon-btn menu-toggle" id="menuToggle" aria-label="Menú">
        <svg width="16" height="16" viewBox="0 0 16 16"><path d="M2 4h12M2 8h12M2 12h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      </button>
      <div class="crumb"><b><?= htmlspecialchars($crumbActual) ?></b> · vigilancia epidemiológica</div>
      <div class="topbar-right">
        <div class="se-badge"><span class="se-dot"></span> Semana <b class="mono">SE&nbsp;<?= $semanaEpi ?></b> · <?= $anioEpi ?></div>
        <button class="icon-btn" aria-label="Alertas" disabled title="Disponible en una próxima fase">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 6.5a4 4 0 0 1 8 0c0 3.5 1.2 4.5 1.2 4.5H2.8S4 10 4 6.5Z" stroke="currentColor" stroke-width="1.3"/><path d="M6.5 13a1.5 1.5 0 0 0 3 0" stroke="currentColor" stroke-width="1.3"/></svg>
        </button>
      </div>
    </header>

    <main class="view">
      <?= $contenido ?>
    </main>

  </div>
</div>

<div class="toast" id="toast"></div>

<script src="/js/shell.js"></script>
<script src="/js/ubigeo.js"></script>
<script src="/js/filtro-tabla.js"></script>
<script src="/js/filas-dinamicas.js"></script>
<script src="/js/ficha.js"></script>
</body>
</html>
