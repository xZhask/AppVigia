<?php
$pestanas = [
    ['ruta' => 'catalogos/usuarios', 'etiqueta' => 'Usuarios'],
    ['ruta' => 'catalogos/grados',   'etiqueta' => 'Grados PNP'],
    ['ruta' => 'catalogos/unidades', 'etiqueta' => 'Unidades PNP'],
];
$etiquetasCategoria = [
    'OFICIAL_GENERAL' => 'Oficial general', 'OFICIAL_SUPERIOR' => 'Oficial superior',
    'OFICIAL_SUBALTERNO' => 'Oficial subalterno', 'SUBOFICIAL' => 'Suboficial', 'EMPLEADO_CIVIL' => 'Empleado civil',
];
?>
<div class="page-head">
  <div>
    <div class="page-title">Grados PNP</div>
    <div class="page-desc">Escala jerárquica usada en fichas de personal policial</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-primary" href="/catalogos/grados/nuevo">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    Nuevo grado
  </a>
</div>

<?php require __DIR__ . '/../../partials/tabs-catalogo.php'; ?>

<div class="card table-card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 15 15"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.4" fill="none"/><path d="m10 10 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      <input placeholder="Buscar por nombre o abreviatura…" data-filtro-tabla="#tabla-grados">
    </div>
  </div>
  <div style="overflow-x:auto">
  <table id="tabla-grados">
    <thead><tr><th>Jerarquía</th><th>Abreviatura</th><th>Nombre</th><th>Categoría</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($grados)): ?>
        <tr><td colspan="5" style="color:var(--muted);text-align:center;padding:32px 16px">No hay grados registrados todavía.</td></tr>
      <?php endif; ?>
      <?php foreach ($grados as $grado): ?>
        <tr>
          <td class="mono"><?= (int) $grado['jerarquia'] ?></td>
          <td class="mono"><?= e($grado['abreviatura']) ?></td>
          <td class="pt-name"><?= e($grado['nombre']) ?></td>
          <td><?= e($etiquetasCategoria[$grado['categoria']] ?? $grado['categoria']) ?></td>
          <td>
            <div class="row-actions">
              <a class="ra" title="Editar" href="/catalogos/grados/<?= (int) $grado['id'] ?>/editar">
                <svg width="15" height="15" viewBox="0 0 15 15"><path d="M10 2.5 12.5 5 5 12.5 2 13l.5-3L10 2.5Z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round"/></svg>
              </a>
              <form method="post" action="/catalogos/grados/<?= (int) $grado['id'] ?>/eliminar" onsubmit="return confirm('¿Eliminar este grado? Esta acción no se puede deshacer.')">
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
