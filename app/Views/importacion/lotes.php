<div class="page-head">
  <div>
    <div class="page-title">Lotes importados</div>
    <div class="page-desc">Historial de cargas masivas desde Excel</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-primary" href="/casos/importar">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    Importar archivo
  </a>
</div>

<?php if (empty($lotes)): ?>
  <?php
  $icono = '<svg width="19" height="19" viewBox="0 0 19 19" fill="none"><path d="M7 9.5v-7M4 5.5l3-3 3 3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  $mensaje = 'Todavía no se ha importado ningún archivo. Cuando importes un lote, aquí verás su historial.';
  $accionTexto = 'Importar archivo';
  $accionHref = '/casos/importar';
  require __DIR__ . '/../partials/estado-vacio.php';
  ?>
<?php else: ?>
  <div class="card table-card">
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>Fecha</th><th>Archivo</th><th>Enfermedad</th><th>Establecimiento</th><th>Usuario</th><th style="text-align:right">Total</th><th style="text-align:right">Importadas</th><th style="text-align:right">Con error</th></tr></thead>
        <tbody>
          <?php foreach ($lotes as $lote): ?>
            <tr>
              <td class="mono"><?= e(date('d/m/Y H:i', strtotime($lote['creado_en']))) ?></td>
              <td><?= e($lote['nombre_archivo']) ?></td>
              <td><?= e($lote['enfermedad_nombre']) ?></td>
              <td><?= e($lote['establecimiento_nombre']) ?></td>
              <td><?= e($lote['usuario_nombre']) ?></td>
              <td class="mono" style="text-align:right"><?= (int) $lote['total_filas'] ?></td>
              <td class="mono" style="text-align:right"><?= (int) $lote['filas_importadas'] ?></td>
              <td class="mono" style="text-align:right"><?= (int) $lote['filas_error'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
