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
<?php else:
  $m = $metricas;

  if ($m['fichas_se_anterior'] > 0) {
    $pct = round((($m['fichas_se_actual'] - $m['fichas_se_anterior']) / $m['fichas_se_anterior']) * 100);
    $claseFichas = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
    $signoFichas = $pct > 0 ? '▲' : ($pct < 0 ? '▼' : '—');
    $textoFichas = "$signoFichas " . abs($pct) . '%';
  } elseif ($m['fichas_se_actual'] > 0) {
    $claseFichas = 'up';
    $textoFichas = '▲ ' . $m['fichas_se_actual'] . ' nuevas';
  } else {
    $claseFichas = 'flat';
    $textoFichas = '—';
  }

  $diffConfirmados = $m['confirmados_se_actual'] - $m['confirmados_se_anterior'];
  $claseConfirmados = $diffConfirmados > 0 ? 'up' : ($diffConfirmados < 0 ? 'down' : 'flat');
  $signoConfirmados = $diffConfirmados > 0 ? '▲' : ($diffConfirmados < 0 ? '▼' : '—');

  $maxEnfermedad = !empty($distribucionEnfermedad) ? max(array_column($distribucionEnfermedad, 'total')) : 0;
  $maxClasificacion = !empty($distribucionClasificacion) ? max($distribucionClasificacion) : 0;

  $dotsClasificacion = ['SOSPECHOSO' => 'dot-sos', 'PROBABLE' => 'dot-pro', 'CONFIRMADO' => 'dot-con', 'DESCARTADO' => 'dot-des'];
  $etiquetasClasificacion = ['SOSPECHOSO' => 'Sospechoso', 'PROBABLE' => 'Probable', 'CONFIRMADO' => 'Confirmado', 'DESCARTADO' => 'Descartado'];
  $coloresClasificacion = ['SOSPECHOSO' => 'var(--s-sospechoso)', 'PROBABLE' => 'var(--s-probable)', 'CONFIRMADO' => 'var(--s-confirmado)', 'DESCARTADO' => 'var(--s-descartado)'];
  ?>
  <div class="grid metrics" style="margin-bottom:16px">
    <div class="card metric">
      <div class="eyebrow">Fichas · SE <?= $semanaActual['semana'] ?></div>
      <div class="metric-val"><?= number_format($m['fichas_se_actual'], 0, ',', ' ') ?></div>
      <div class="metric-meta"><span class="delta <?= $claseFichas ?>"><?= $textoFichas ?></span> vs. SE anterior</div>
    </div>
    <div class="card metric">
      <div class="eyebrow">En investigación</div>
      <div class="metric-val"><?= number_format($m['abiertas'], 0, ',', ' ') ?></div>
      <div class="metric-meta"><span class="state"><span class="dot st-open"></span> fichas abiertas</span></div>
    </div>
    <div class="card metric">
      <div class="eyebrow">Confirmados · SE <?= $semanaActual['semana'] ?></div>
      <div class="metric-val"><?= number_format($m['confirmados_se_actual'], 0, ',', ' ') ?></div>
      <div class="metric-meta"><span class="delta <?= $claseConfirmados ?>"><?= $signoConfirmados ?> <?= abs($diffConfirmados) ?></span> vs. SE anterior</div>
    </div>
    <div class="card metric">
      <div class="eyebrow">Pend. de validación</div>
      <div class="metric-val"><?= number_format($m['en_validacion'], 0, ',', ' ') ?></div>
      <div class="metric-meta"><span class="delta flat">de <?= $m['en_validacion_establecimientos'] ?> establecimiento<?= $m['en_validacion_establecimientos'] === 1 ? '' : 's' ?></span></div>
    </div>
  </div>

  <div class="grid panel-grid">
    <div class="card">
      <div class="card-head">
        <div><h3>Curva epidemiológica</h3><div class="sub">Todas las enfermedades · casos notificados por semana</div></div>
        <div class="spacer"></div>
        <div class="seg">
          <a href="/?rango=14" class="<?= $rango === '14' ? 'on' : '' ?>" style="text-decoration:none;display:inline-block">14 sem</a>
          <a href="/?rango=anio" class="<?= $rango === 'anio' ? 'on' : '' ?>" style="text-decoration:none;display:inline-block">Año</a>
        </div>
      </div>
      <?php require __DIR__ . '/../partials/curva-epidemiologica.php'; ?>
    </div>

    <div class="grid" style="gap:16px">
      <div class="card">
        <div class="card-head"><div><h3>Por enfermedad</h3><div class="sub">Últimas 4 SE</div></div></div>
        <div class="brk">
          <?php if (empty($distribucionEnfermedad)): ?>
            <p style="color:var(--muted);font-size:13px;margin:6px 18px 16px">Sin casos en las últimas 4 semanas.</p>
          <?php endif; ?>
          <?php foreach ($distribucionEnfermedad as $fila): ?>
            <div class="brk-row">
              <span class="brk-name"><?= e($fila['nombre']) ?></span>
              <span class="bar-track"><span class="bar-fill" style="width:<?= $maxEnfermedad > 0 ? round($fila['total'] / $maxEnfermedad * 100) : 0 ?>%"></span></span>
              <span class="brk-num"><?= (int) $fila['total'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-head"><div><h3>Clasificación de caso</h3></div></div>
        <div class="class-dist">
          <?php foreach ($distribucionClasificacion as $clasificacion => $total): ?>
            <div class="cd-row">
              <span class="chip"><span class="dot <?= $dotsClasificacion[$clasificacion] ?>"></span> <?= $etiquetasClasificacion[$clasificacion] ?></span>
              <span class="cd-track"><span class="cd-fill" style="width:<?= $maxClasificacion > 0 ? round($total / $maxClasificacion * 100) : 0 ?>%;background:<?= $coloresClasificacion[$clasificacion] ?>"></span></span>
              <span class="cd-num"><?= (int) $total ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
