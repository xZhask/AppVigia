<div class="page-head">
  <div>
    <div class="page-title">Enfermedades</div>
    <div class="page-desc">Enfermedades y eventos bajo vigilancia · fichas modelo MINSA</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-primary" href="/catalogos/enfermedades/nuevo">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    Nueva enfermedad
  </a>
</div>

<div class="card table-card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 15 15"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.4" fill="none"/><path d="m10 10 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      <input placeholder="Buscar por nombre o CIE-10…" data-filtro-tabla="#tabla-enfermedades">
    </div>
  </div>
  <div style="overflow-x:auto">
  <table id="tabla-enfermedades">
    <thead><tr>
      <th>Nombre</th><th>CIE-10</th><th>Notificación</th><th>Grupo</th><th>Estado</th><th></th>
    </tr></thead>
    <tbody>
      <?php if (empty($enfermedades)): ?>
        <tr><td colspan="6" style="color:var(--muted);text-align:center;padding:32px 16px">No hay enfermedades registradas todavía.</td></tr>
      <?php endif; ?>
      <?php foreach ($enfermedades as $enf): ?>
        <tr>
          <td class="pt-name"><?= e($enf['nombre']) ?></td>
          <td class="mono"><?= e($enf['cie10'] ?? '—') ?></td>
          <td><?= e($enf['tipo_notif'] === 'INMEDIATA' ? 'Inmediata' : 'Semanal') ?></td>
          <td><?= e($enf['grupo'] ?? '—') ?></td>
          <td><span class="state"><span class="dot <?= $enf['activo'] ? 'st-open' : 'st-closed' ?>"></span> <?= $enf['activo'] ? 'Activa' : 'Inactiva' ?></span></td>
          <td>
            <div class="row-actions">
              <a class="ra" title="Editar" href="/catalogos/enfermedades/<?= (int) $enf['id'] ?>/editar">
                <svg width="15" height="15" viewBox="0 0 15 15"><path d="M10 2.5 12.5 5 5 12.5 2 13l.5-3L10 2.5Z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round"/></svg>
              </a>
              <form method="post" action="/catalogos/enfermedades/<?= (int) $enf['id'] ?>/alternar" onsubmit="return confirm('<?= $enf['activo'] ? 'Desactivar' : 'Activar' ?> esta enfermedad?')">
                <?= \App\Core\Csrf::campoOculto() ?>
                <button class="ra" type="submit" title="<?= $enf['activo'] ? 'Desactivar' : 'Activar' ?>">
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
