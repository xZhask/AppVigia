<?php
use App\Core\Auth;

$pestanas = [
    ['ruta' => 'catalogos/usuarios', 'etiqueta' => 'Usuarios'],
    ['ruta' => 'catalogos/grados',   'etiqueta' => 'Grados PNP'],
    ['ruta' => 'catalogos/unidades', 'etiqueta' => 'Unidades PNP'],
];
$etiquetasRol = [
    'ADMIN' => 'Administrador', 'REGISTRADOR' => 'Registrador/a',
];
$miId = Auth::usuario()['id'];
?>
<div class="page-head">
  <div>
    <div class="page-title">Usuarios</div>
    <div class="page-desc">Cuentas con acceso al sistema y su rol</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-primary" href="/catalogos/usuarios/nuevo">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    Nuevo usuario
  </a>
</div>

<?php require __DIR__ . '/../../partials/tabs-catalogo.php'; ?>

<div class="card table-card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 15 15"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.4" fill="none"/><path d="m10 10 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      <input placeholder="Buscar por nombre o correo…" data-filtro-tabla="#tabla-usuarios">
    </div>
  </div>
  <div style="overflow-x:auto">
  <table id="tabla-usuarios">
    <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Establecimiento</th><th>Estado</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($usuarios)): ?>
        <tr><td colspan="6" style="color:var(--muted);text-align:center;padding:32px 16px">No hay usuarios registrados todavía.</td></tr>
      <?php endif; ?>
      <?php foreach ($usuarios as $u): ?>
        <tr>
          <td class="pt-name"><?= e($u['nombre']) ?><?= (int) $u['id'] === $miId ? ' <span class="cell-sub">(tú)</span>' : '' ?></td>
          <td class="mono"><?= e($u['email']) ?></td>
          <td><?= e($etiquetasRol[$u['rol']] ?? $u['rol']) ?></td>
          <td><?= e($u['establecimiento_nombre'] ?? '—') ?></td>
          <td><span class="state"><span class="dot <?= $u['activo'] ? 'st-open' : 'st-closed' ?>"></span> <?= $u['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
          <td>
            <div class="row-actions">
              <a class="ra" title="Editar" href="/catalogos/usuarios/<?= (int) $u['id'] ?>/editar">
                <svg width="15" height="15" viewBox="0 0 15 15"><path d="M10 2.5 12.5 5 5 12.5 2 13l.5-3L10 2.5Z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round"/></svg>
              </a>
              <?php if ((int) $u['id'] !== $miId): ?>
              <form method="post" action="/catalogos/usuarios/<?= (int) $u['id'] ?>/alternar" onsubmit="return confirm('<?= $u['activo'] ? 'Desactivar' : 'Activar' ?> a este usuario?')">
                <?= \App\Core\Csrf::campoOculto() ?>
                <button class="ra" type="submit" title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>">
                  <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 2v5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><path d="M4.5 4A5 5 0 1 0 10.5 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
