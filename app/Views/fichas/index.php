<?php
$clasificaciones = [
    'SOSPECHOSO' => ['dot' => 'dot-sos', 'etiqueta' => 'Sospechoso'],
    'PROBABLE'   => ['dot' => 'dot-pro', 'etiqueta' => 'Probable'],
    'CONFIRMADO' => ['dot' => 'dot-con', 'etiqueta' => 'Confirmado'],
    'DESCARTADO' => ['dot' => 'dot-des', 'etiqueta' => 'Descartado'],
];
$estados = [
    'ABIERTA'    => ['dot' => 'st-open',   'etiqueta' => 'Abierta'],
    'VALIDACION' => ['dot' => 'st-val',    'etiqueta' => 'Validación'],
    'CERRADA'    => ['dot' => 'st-closed', 'etiqueta' => 'Cerrada'],
];
?>
<div class="page-head">
  <div>
    <div class="page-title">Fichas registradas</div>
    <div class="page-desc"><?= $total === 0 ? 'Todas las redes y establecimientos' : number_format($total, 0, ',', ' ') . ' ' . ($total === 1 ? 'ficha' : 'fichas') . ' · todas las redes y establecimientos' ?></div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/casos/importar">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 9.5v-7M4 5.5l3-3 3 3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Importar Excel
  </a>
  <a class="btn btn-primary" href="/casos/nuevo">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    Nueva ficha
  </a>
</div>

<?php if ($total === 0 && empty(array_filter($filtros))): ?>
  <?php
  $icono = '<svg width="19" height="19" viewBox="0 0 19 19" fill="none"><rect x="3" y="2.5" width="13" height="14" rx="2" stroke="currentColor" stroke-width="1.4"/><path d="M6.5 7h6M6.5 10h6M6.5 13h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';
  $mensaje = 'Todavía no se ha registrado ninguna ficha. Empieza por notificar el primer caso de una enfermedad bajo vigilancia.';
  $accionTexto = 'Registrar la primera ficha';
  $accionHref = '/casos/nuevo';
  require __DIR__ . '/../partials/estado-vacio.php';
  ?>
<?php else: ?>
  <div class="card table-card">
    <form method="get" action="/casos" class="toolbar">
      <div class="search">
        <svg width="15" height="15" viewBox="0 0 15 15"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.4" fill="none"/><path d="m10 10 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
        <input type="text" name="q" value="<?= e($filtros['q'] ?? '') ?>" placeholder="Buscar por paciente, documento o N.° de ficha…">
      </div>
      <div class="filter">
        <b>Enfermedad</b>
        <select name="enfermedad_id" onchange="this.form.submit()">
          <option value="">Todas</option>
          <?php foreach ($enfermedades as $enf): ?>
            <option value="<?= (int) $enf['id'] ?>" <?= seleccionado($filtros['enfermedad_id'] ?? '', $enf['id']) ?>><?= e($enf['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter">
        <b>Clasificación</b>
        <select name="clasificacion" onchange="this.form.submit()">
          <option value="">Todas</option>
          <?php foreach ($clasificaciones as $valor => $c): ?>
            <option value="<?= $valor ?>" <?= seleccionado($filtros['clasificacion'] ?? '', $valor) ?>><?= $c['etiqueta'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter">
        <b>Estado</b>
        <select name="estado" onchange="this.form.submit()">
          <option value="">Todos</option>
          <?php foreach ($estados as $valor => $es): ?>
            <option value="<?= $valor ?>" <?= seleccionado($filtros['estado'] ?? '', $valor) ?>><?= $es['etiqueta'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter">
        <b>Desde</b>
        <input type="text" name="desde" value="<?= e($filtros['desde'] ?? '') ?>" placeholder="dd/mm/aaaa" style="width:92px;border:0;background:none;font:inherit;font-size:12.5px;color:var(--ink-2)">
      </div>
      <div class="filter">
        <b>Hasta</b>
        <input type="text" name="hasta" value="<?= e($filtros['hasta'] ?? '') ?>" placeholder="dd/mm/aaaa" style="width:92px;border:0;background:none;font:inherit;font-size:12.5px;color:var(--ink-2)">
      </div>
      <button type="submit" class="btn btn-ghost">Filtrar</button>
      <?php if (!empty(array_filter($filtros))): ?>
        <a class="btn btn-ghost" href="/casos">Limpiar</a>
      <?php endif; ?>
    </form>
    <div style="overflow-x:auto">
    <table>
      <thead><tr>
        <th>N.° ficha</th><th>Paciente</th><th>Enfermedad</th><th>Establecimiento</th><th>SE</th><th>Notificación</th><th>Clasificación</th><th>Estado</th><th></th>
      </tr></thead>
      <tbody>
        <?php if (empty($fichas)): ?>
          <tr><td colspan="9" style="color:var(--muted);text-align:center;padding:32px 16px">No se encontraron fichas con estos filtros.</td></tr>
        <?php endif; ?>
        <?php foreach ($fichas as $ficha):
          $edad = edadDesdeFecha($ficha['fecha_nac']);
          $c = $clasificaciones[$ficha['clasificacion']];
          $es = $estados[$ficha['estado']];
        ?>
          <tr<?= $ficha['anulado'] ? ' style="opacity:.55"' : '' ?>>
            <td class="mono"><?= e($ficha['codigo']) ?></td>
            <td>
              <div class="pt-name"><?= e($ficha['apellidos_nombres']) ?></div>
              <div class="pt-doc"><?= e($ficha['tipo_doc']) ?> <?= e(enmascararDocumento($ficha['num_doc'])) ?> · <?= e($ficha['sexo'] ?? '—') ?><?= $edad !== null ? ' · ' . $edad . 'a' : '' ?></div>
            </td>
            <td><?= e($ficha['enfermedad_nombre']) ?><div class="cell-sub"><?= e($ficha['cie10'] ?? '—') ?></div></td>
            <td><?= e($ficha['establecimiento_nombre']) ?><div class="cell-sub"><?= e($ficha['red_nombre'] ?? '—') ?></div></td>
            <td class="mono"><?= (int) $ficha['semana_epi'] ?></td>
            <td class="mono"><?= e(fechaIsoADmy($ficha['fecha_notif'])) ?></td>
            <td><span class="chip"><span class="dot <?= $c['dot'] ?>"></span> <?= $c['etiqueta'] ?></span></td>
            <td>
              <?php if ($ficha['anulado']): ?>
                <span class="state"><span class="dot st-closed"></span> Anulada</span>
              <?php else: ?>
                <span class="state"><span class="dot <?= $es['dot'] ?>"></span> <?= $es['etiqueta'] ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="row-actions">
                <a class="ra" title="Ver" href="/casos/<?= (int) $ficha['id'] ?>">
                  <svg width="15" height="15" viewBox="0 0 15 15"><path d="M1.5 7.5S3.5 3 7.5 3s6 4.5 6 4.5-2 4.5-6 4.5-6-4.5-6-4.5Z" stroke="currentColor" stroke-width="1.2" fill="none"/><circle cx="7.5" cy="7.5" r="1.8" stroke="currentColor" stroke-width="1.2" fill="none"/></svg>
                </a>
                <a class="ra" title="Editar" href="/casos/<?= (int) $ficha['id'] ?>/editar">
                  <svg width="15" height="15" viewBox="0 0 15 15"><path d="M10 2.5 12.5 5 5 12.5 2 13l.5-3L10 2.5Z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round"/></svg>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <div class="table-foot">
      <span>Mostrando <?= $total === 0 ? 0 : (($pagina - 1) * 20 + 1) ?>–<?= min($pagina * 20, $total) ?> de <?= number_format($total, 0, ',', ' ') ?> fichas</span>
      <?php if ($totalPaginas > 1): ?>
        <div class="pager">
          <a class="pg" href="/casos?<?= queryConPagina($filtros, ['page' => max(1, $pagina - 1)]) ?>">‹</a>
          <?php for ($p = max(1, $pagina - 2); $p <= min($totalPaginas, $pagina + 2); $p++): ?>
            <a class="pg <?= $p === $pagina ? 'on' : '' ?>" href="/casos?<?= queryConPagina($filtros, ['page' => $p]) ?>"><?= $p ?></a>
          <?php endfor; ?>
          <a class="pg" href="/casos?<?= queryConPagina($filtros, ['page' => min($totalPaginas, $pagina + 1)]) ?>">›</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
