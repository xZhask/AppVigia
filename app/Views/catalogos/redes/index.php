<?php
$pestanas = [
    ['ruta' => 'catalogos/establecimientos', 'etiqueta' => 'Establecimientos'],
    ['ruta' => 'catalogos/redes',            'etiqueta' => 'Redes de salud'],
];
?>
<div class="page-head">
  <div>
    <div class="page-title">Redes de salud</div>
    <div class="page-desc">Redes que agrupan a los establecimientos</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-primary" href="/catalogos/redes/nuevo">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    Nueva red
  </a>
</div>

<?php require __DIR__ . '/../../partials/tabs-catalogo.php'; ?>

<div class="card table-card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 15 15"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.4" fill="none"/><path d="m10 10 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      <input placeholder="Buscar por nombre…" data-filtro-tabla="#tabla-redes">
    </div>
  </div>
  <div style="overflow-x:auto">
  <table id="tabla-redes">
    <thead><tr><th>Nombre</th><th>DIRESA</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($redes)): ?>
        <tr><td colspan="3" style="color:var(--muted);text-align:center;padding:32px 16px">No hay redes registradas todavía.</td></tr>
      <?php endif; ?>
      <?php foreach ($redes as $red): ?>
        <tr>
          <td class="pt-name"><?= e($red['nombre']) ?></td>
          <td><?= e($red['diresa']) ?></td>
          <td>
            <div class="row-actions">
              <a class="ra" title="Editar" href="/catalogos/redes/<?= (int) $red['id'] ?>/editar">
                <svg width="15" height="15" viewBox="0 0 15 15"><path d="M10 2.5 12.5 5 5 12.5 2 13l.5-3L10 2.5Z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round"/></svg>
              </a>
              <form method="post" action="/catalogos/redes/<?= (int) $red['id'] ?>/eliminar" onsubmit="return confirm('¿Eliminar esta red de salud? Esta acción no se puede deshacer.')">
                <?= \App\Core\Csrf::campoOculto() ?>
                <button class="ra" type="submit" title="Eliminar">
                  <svg width="15" height="15" viewBox="0 0 15 15"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linecap="round"/></svg>
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
