<div class="page-head">
  <div>
    <div class="page-title">Panel de vigilancia</div>
    <div class="page-desc">Consolidado de notificaciones de las IPRESS PNP</div>
  </div>
</div>

<?php if ($totalFichas === 0): ?>
  <?php
  $icono = '<svg width="19" height="19" viewBox="0 0 19 19" fill="none"><path d="M2 9.5 5 9.5 7 5 9.5 13 12 3 14 8.5 15.5 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  $mensaje = 'Aún no hay fichas registradas. En cuanto se notifique el primer caso, aquí verás la curva epidemiológica y los indicadores del área.';
  $accionTexto = 'Registrar ficha';
  $accionHref = '/casos/nuevo';
  require __DIR__ . '/../partials/estado-vacio.php';
  ?>
<?php else: ?>
  <div class="card">
    <div class="section-body" style="text-align:center;padding:48px 24px">
      <div class="metric-val" style="margin-bottom:8px"><?= number_format($totalFichas, 0, ',', ' ') ?></div>
      <p style="color:var(--muted);font-size:13.5px;max-width:420px;margin:0 auto 18px;line-height:1.55">
        fichas registradas hasta ahora. La curva epidemiológica y los indicadores del panel llegan en una próxima fase.
      </p>
      <a class="btn btn-primary" href="/casos/nuevo">
        <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Registrar ficha
      </a>
    </div>
  </div>
<?php endif; ?>
