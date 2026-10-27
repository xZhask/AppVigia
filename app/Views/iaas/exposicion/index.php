<?php
use App\Models\IaasNorma;

// Exposición laboral (Anexo 8): resumen del año y listado.
$anexo = 'Anexo 8';
$acciones = '<a class="btn btn-primary" href="/iaas/exposicion/nueva"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Registrar exposición</a>';
$punzo = count(array_filter($delAnio, fn($r) => $r['factor'] === 'PUNZOCORTANTE'));
$salpi = count($delAnio) - $punzo;
$sinEpp = count(array_filter($delAnio, fn($r) => empty($r['epp'])));
$conProfilaxis = count(array_filter($delAnio, fn($r) => (int) $r['profilaxis'] === 1));
$url = fn(array $p) => '/iaas/exposicion?' . http_build_query(array_filter($p + ['anio' => $anio, 'mes' => $mes, 'ver' => $anuladas ? 'anuladas' : null], fn($v) => $v !== null && $v !== ''));
require __DIR__ . '/../_cabecera.php';
?>
<?php if ($configuracion && $configuracion['sin_hospitalizacion']): ?>
  <div class="iaas-aviso info"><div>Este establecimiento está registrado <b>sin hospitalización</b>: de la vigilancia de IAAS solo le corresponde la exposición laboral del personal. <a href="/iaas/servicios">Cambiar</a></div></div>
<?php endif; ?>

<div class="grid metrics iaas-kpis">
  <div class="card metric"><div class="eyebrow">Exposiciones <?= $anio ?></div><div class="metric-val"><?= count($delAnio) ?></div><div class="metric-meta">en el año</div></div>
  <div class="card metric"><div class="eyebrow">Punzocortantes</div><div class="metric-val"><?= $punzo ?></div><div class="metric-meta">agujas, bisturí y otros</div></div>
  <div class="card metric"><div class="eyebrow">Salpicaduras</div><div class="metric-val"><?= $salpi ?></div><div class="metric-meta">mucosa o piel no intacta</div></div>
  <div class="card metric"><div class="eyebrow">Sin EPP registrado</div><div class="metric-val"><?= $sinEpp ?></div><div class="metric-meta"><?= $conProfilaxis ?> con profilaxis indicada</div></div>
</div>

<div class="card table-card">
  <form method="get" class="toolbar iaas-filtros">
    <div class="filtro-caja"><select name="mes" data-nosearch="true" aria-label="Mes" onchange="this.form.submit()">
      <option value="">Todo el año</option>
      <?php foreach (IaasNorma::MESES as $n => $nombre): ?><option value="<?= $n ?>"<?= $mes === $n ? ' selected' : '' ?>><?= ucfirst($nombre) ?></option><?php endforeach; ?>
    </select></div>
    <div class="filtro-caja"><select name="anio" data-nosearch="true" aria-label="Año" onchange="this.form.submit()">
      <?php for ($a = (int) date('Y'); $a >= 2024; $a--): ?><option value="<?= $a ?>"<?= $anio === $a ? ' selected' : '' ?>><?= $a ?></option><?php endfor; ?>
    </select></div>
    <div class="spacer"></div>
    <a class="btn btn-quiet ns-btn-sm" href="<?= e($url(['ver' => $anuladas ? '' : 'anuladas'])) ?>"><?= $anuladas ? 'Ver vigentes' : 'Ver anuladas' ?></a>
  </form>
  <?php if (!$registros): ?>
    <div class="iaas-vacio-tabla"><?= $anuladas ? 'No hay registros anulados en este periodo.' : 'Sin exposiciones registradas en este periodo.' ?></div>
  <?php else: ?>
    <div class="tabla-desplazable">
      <table class="iaas-tabla-casos">
        <thead><tr><th>N.º</th><th>Fecha</th><th>Personal</th><th>Servicio</th><th>Factor</th><th>EPP</th><th>Profilaxis</th></tr></thead>
        <tbody>
          <?php foreach ($registros as $r): ?>
            <tr class="iaas-fila-enlace<?= $r['anulado_en'] ? ' anulada' : '' ?>" data-href="/iaas/exposicion/<?= (int) $r['id'] ?>">
              <td><a class="mono iaas-cod-ficha" href="/iaas/exposicion/<?= (int) $r['id'] ?>"><?= (int) $r['id'] ?></a></td>
              <td class="mono"><?= e(date('d/m/Y', strtotime($r['fecha']))) ?><?= $r['hora'] ? '<div class="cell-sub">' . e(substr($r['hora'], 0, 5)) . '</div>' : '' ?></td>
              <td><div class="pt-name"><?= e(IaasNorma::PROFESIONES[$r['profesion']]) ?><?= $r['profesion_otra'] ? ' · ' . e($r['profesion_otra']) : '' ?></div>
                <div class="pt-doc"><?= e(IaasNorma::CONDICIONES_PERSONAL[$r['condicion']]) ?><?= $r['edad_anios'] ? ' · ' . (int) $r['edad_anios'] . ' a' : '' ?><?= $r['sexo'] ? ' · ' . e($r['sexo']) : '' ?></div></td>
              <td><?= e(IaasNorma::SERVICIOS_EXPOSICION[$r['servicio']]) ?></td>
              <td><span class="iaas-tipo-chip t-<?= $r['factor'] === 'PUNZOCORTANTE' ? 'ISQ_SUP' : 'ITU' ?>"><?= $r['factor'] === 'PUNZOCORTANTE' ? 'Punzocortante' : 'Salpicadura' ?></span><?= $r['profundidad'] ? '<div class="cell-sub">' . e(IaasNorma::PROFUNDIDADES[$r['profundidad']][0]) . '</div>' : '' ?></td>
              <td><?= $r['epp'] ? e(implode(', ', array_map(fn($x) => IaasNorma::EPP[$x], explode(',', $r['epp'])))) : '<span class="iaas-alerta-txt">Ninguno</span>' ?></td>
              <td><?= $r['profilaxis'] === null ? '—' : ((int) $r['profilaxis'] ? 'Sí' : 'No') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<script src="<?= e(asset('/js/iaas.js')) ?>"></script>
