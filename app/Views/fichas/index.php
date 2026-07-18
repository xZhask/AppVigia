<div class="page-head">
  <div>
    <div class="page-title">Fichas registradas</div>
    <div class="page-desc"><?= $totalFichas === 0 ? 'Todas las redes y establecimientos' : number_format($totalFichas, 0, ',', ' ') . ' ' . ($totalFichas === 1 ? 'ficha' : 'fichas') . ' · todas las redes y establecimientos' ?></div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-primary" href="/casos/nuevo">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    Nueva ficha
  </a>
</div>

<?php if ($totalFichas === 0): ?>
  <?php
  $icono = '<svg width="19" height="19" viewBox="0 0 19 19" fill="none"><rect x="3" y="2.5" width="13" height="14" rx="2" stroke="currentColor" stroke-width="1.4"/><path d="M6.5 7h6M6.5 10h6M6.5 13h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';
  $mensaje = 'Todavía no se ha registrado ninguna ficha. Empieza por notificar el primer caso de una enfermedad bajo vigilancia.';
  $accionTexto = 'Registrar la primera ficha';
  $accionHref = '/casos/nuevo';
  require __DIR__ . '/../partials/estado-vacio.php';
  ?>
<?php else: ?>
  <div class="card">
    <div class="section-body" style="text-align:center;padding:48px 24px">
      <p style="color:var(--muted);font-size:13.5px;max-width:420px;margin:0 auto;line-height:1.55">
        El listado completo con filtros y búsqueda llega en una próxima fase. Por ahora, cada ficha registrada queda guardada correctamente.
      </p>
    </div>
  </div>
<?php endif; ?>
