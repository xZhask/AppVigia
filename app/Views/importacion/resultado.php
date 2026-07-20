<?php
$totalImportadas = count($filasImportadas);
$totalError = count($filasConError);
?>
<div class="page-head">
  <div>
    <div class="page-title">Resultado de la importación</div>
    <div class="page-desc"><?= e($enfermedad['nombre']) ?> · <?= $totalFilas ?> fila<?= $totalFilas === 1 ? '' : 's' ?> procesada<?= $totalFilas === 1 ? '' : 's' ?></div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/casos/importar">Importar otro archivo</a>
  <a class="btn btn-primary" href="/casos">Ver listado de fichas</a>
</div>

<div class="grid metrics" style="margin-bottom:16px">
  <div class="card metric">
    <div class="eyebrow">Importadas</div>
    <div class="metric-val"><?= $totalImportadas ?></div>
    <div class="metric-meta"><span class="state"><span class="dot st-open"></span> fichas registradas</span></div>
  </div>
  <div class="card metric">
    <div class="eyebrow">Con error</div>
    <div class="metric-val"><?= $totalError ?></div>
    <div class="metric-meta"><span class="state"><span class="dot st-val"></span> filas rechazadas</span></div>
  </div>
</div>

<?php if ($totalImportadas > 0): ?>
  <div class="card table-card" style="margin-bottom:16px">
    <div class="card-head"><div><h3>Fichas importadas</h3></div></div>
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>N.° ficha</th><th>persona</th></tr></thead>
        <tbody>
          <?php foreach ($filasImportadas as $fila): ?>
            <tr>
              <td class="mono"><?= e($fila['codigo']) ?></td>
              <td class="pt-name"><?= e($fila['nombre']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php if ($totalError > 0): ?>
  <div class="card table-card">
    <div class="card-head"><div><h3>Filas con error</h3><div class="sub">Corrige estas filas en tu archivo original y vuelve a subirlo</div></div></div>
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th style="width:70px">Fila</th><th>Motivo</th></tr></thead>
        <tbody>
          <?php foreach ($filasConError as $fila): ?>
            <tr>
              <td class="mono"><?= (int) $fila['fila'] ?></td>
              <td>
                <ul style="margin:0;padding-left:18px">
                  <?php foreach ($fila['errores'] as $motivo): ?>
                    <li style="color:var(--s-confirmado)"><?= e($motivo) ?></li>
                  <?php endforeach; ?>
                </ul>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
