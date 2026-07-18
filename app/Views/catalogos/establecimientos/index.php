<?php
$pestanas = [
    ['ruta' => 'catalogos/establecimientos', 'etiqueta' => 'Establecimientos'],
    ['ruta' => 'catalogos/redes',            'etiqueta' => 'Redes de salud'],
];
$etiquetasInstitucion = [
    'MINSA' => 'MINSA', 'ESSALUD' => 'EsSalud', 'FFAA_SANIDAD' => 'Sanidad FFAA', 'PRIVADO' => 'Privado',
];
?>
<div class="page-head">
  <div>
    <div class="page-title">Establecimientos</div>
    <div class="page-desc">Padrón de establecimientos de salud (RENIPRESS)</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-primary" href="/catalogos/establecimientos/nuevo">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    Nuevo establecimiento
  </a>
</div>

<?php require __DIR__ . '/../../partials/tabs-catalogo.php'; ?>

<div class="card table-card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 15 15"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.4" fill="none"/><path d="m10 10 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      <input placeholder="Buscar por nombre, código o red…" data-filtro-tabla="#tabla-establecimientos">
    </div>
  </div>
  <div style="overflow-x:auto">
  <table id="tabla-establecimientos">
    <thead><tr><th>Establecimiento</th><th>Cód. RENIPRESS</th><th>Red</th><th>Institución</th><th>Distrito</th><th>Estado</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($establecimientos)): ?>
        <tr><td colspan="7" style="color:var(--muted);text-align:center;padding:32px 16px">No hay establecimientos registrados todavía.</td></tr>
      <?php endif; ?>
      <?php foreach ($establecimientos as $est): ?>
        <tr>
          <td class="pt-name"><?= e($est['nombre']) ?></td>
          <td class="mono"><?= e($est['cod_renipress'] ?? '—') ?></td>
          <td><?= e($est['red_nombre'] ?? '—') ?></td>
          <td><?= e($etiquetasInstitucion[$est['institucion']] ?? $est['institucion']) ?></td>
          <td><?= e($est['distrito_nombre'] ?? '—') ?></td>
          <td><span class="state"><span class="dot <?= $est['activo'] ? 'st-open' : 'st-closed' ?>"></span> <?= $est['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
          <td>
            <div class="row-actions">
              <a class="ra" title="Editar" href="/catalogos/establecimientos/<?= (int) $est['id'] ?>/editar">
                <svg width="15" height="15" viewBox="0 0 15 15"><path d="M10 2.5 12.5 5 5 12.5 2 13l.5-3L10 2.5Z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round"/></svg>
              </a>
              <form method="post" action="/catalogos/establecimientos/<?= (int) $est['id'] ?>/alternar" onsubmit="return confirm('<?= $est['activo'] ? 'Desactivar' : 'Activar' ?> este establecimiento?')">
                <?= \App\Core\Csrf::campoOculto() ?>
                <button class="ra" type="submit" title="<?= $est['activo'] ? 'Desactivar' : 'Activar' ?>">
                  <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 2v5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><path d="M4.5 4A5 5 0 1 0 10.5 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
