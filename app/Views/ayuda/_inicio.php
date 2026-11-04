<?php
// Marco común de las guías. Espera $guiaTitulo, $guiaDescripcion, $guiaFecha,
// $guiaActual e $indice. Las capturas se toman con datos de prueba.
$captura = function (string $archivo, string $alt, string $pie, string $clase = ''): void {
    $ruta = asset('/img/guia/' . $archivo); ?>
  <figure class="guia-figura <?= e($clase) ?>">
    <a href="<?= e($ruta) ?>" target="_blank" rel="noopener" title="Abrir la imagen en otra pestaña"><img src="<?= e($ruta) ?>" alt="<?= e($alt) ?>"></a>
    <figcaption><?= e($pie) ?></figcaption>
  </figure>
<?php };
$ir = fn(string $ruta, string $texto): string => '<a class="guia-ir" href="' . e($ruta) . '">' . e($texto) . ' →</a>';
$icoAviso = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg>';
$icoInfo = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>';
?>
<link rel="stylesheet" href="<?= e(asset('/css/guia.css')) ?>">

<div class="guia" data-guia data-guia-titulo="<?= e($guiaTitulo) ?> - VIGÍA SPNP">
  <div class="guia-portada">
    <img src="<?= e(asset('/img/logo-vigia-spnp-color.svg')) ?>" alt="" width="110" height="108">
    <div class="guia-portada-marca">VIGÍA SPNP</div>
    <h1><?= e($guiaTitulo) ?></h1>
    <p>Vigilancia epidemiológica · Área de Epidemiología · DIRSAPOL</p>
    <p class="guia-portada-fecha">Actualizada el <?= e($guiaFecha) ?></p>
    <div class="guia-portada-indice">
      <div class="eyebrow">Contenido</div>
      <ol>
        <?php foreach ($indice as $ancla => [$titulo]): ?><li><a href="#<?= e($ancla) ?>"><?= e($titulo) ?></a></li><?php endforeach; ?>
      </ol>
    </div>
  </div>

  <header class="guia-cab">
    <div>
      <div class="page-title"><?= e($guiaTitulo) ?></div>
      <div class="page-desc"><?= e($guiaDescripcion) ?> · actualizada el <?= e($guiaFecha) ?></div>
    </div>
    <div class="guia-acciones">
      <div class="filtro-caja guia-busca">
        <input type="search" data-guia-buscar placeholder="Buscar en la guía, por ejemplo Z21" aria-label="Buscar en la guía">
      </div>
      <button type="button" class="btn btn-primary" data-guia-pdf>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 11l5 5 5-5M4 20h16"/></svg>
        Guardar como PDF
      </button>
    </div>
  </header>
  <?php
  $guiasDisponibles = [
      'ADMIN'       => ['administrador' => ['Administrador', '/ayuda/administrador'], 'registrador' => ['Registrador', '/ayuda'], 'jefe-red' => ['Jefe de red', '/ayuda/jefe-red'], 'iaas' => ['IAAS', '/ayuda/iaas']],
      'REGISTRADOR' => ['registrador' => ['Registrador', '/ayuda'], 'iaas' => ['IAAS', '/ayuda/iaas']],
  ][\App\Core\Auth::usuario()['rol']] ?? [];
  ?>
  <?php if ($guiasDisponibles): ?>
    <nav class="pestanas guia-pestanas" aria-label="Guías">
      <?php foreach ($guiasDisponibles as $clave => [$etiqueta, $ruta]): ?>
        <a class="pestana<?= $guiaActual === $clave ? ' on' : '' ?>" href="<?= e($ruta) ?>"><?= e($etiqueta) ?></a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>
  <p class="guia-ayuda-pdf" data-guia-resultado aria-live="polite" hidden></p>

  <div class="guia-cuerpo">
    <nav class="guia-indice" aria-label="Contenido de la guía" data-guia-indice>
      <div class="eyebrow">Contenido</div>
      <ul>
        <?php foreach ($indice as $ancla => [$titulo, $subs]): ?>
          <li>
            <a href="#<?= e($ancla) ?>" data-seccion="<?= e($ancla) ?>"><?= e($titulo) ?></a>
            <?php if ($subs): ?>
              <ul>
                <?php foreach ($subs as $subAncla => $subTitulo): ?><li><a href="#<?= e($subAncla) ?>" data-seccion="<?= e($subAncla) ?>"><?= e($subTitulo) ?></a></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <article class="guia-contenido">
