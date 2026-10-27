<?php
use App\Models\IaasCaso;
use App\Models\IaasNorma;
use App\Models\Persona;

// Listado de fichas IAAS (Anexo 5), con pestañas por estado como el de Fichas.
$anexo = 'Anexo 5';
$acciones = '<a class="btn btn-primary" href="/iaas/casos/nuevo"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Nueva ficha IAAS</a>';
$url = fn(array $p) => '/iaas/casos?' . http_build_query(array_filter($p + ['pestana' => $pestana] + $filtros, fn($v) => $v !== null && $v !== '' && $v !== 'TODAS'));
$pestanas = ['TODAS' => 'Todas', 'POR_COMPLETAR' => 'Por completar', 'COMPLETO' => 'Completas', 'ANULADAS' => 'Anuladas'];
require __DIR__ . '/../_cabecera.php';
?>
<div class="card table-card">
  <nav class="iaas-pestanas" aria-label="Estado">
    <?php foreach ($pestanas as $clave => $etq): ?>
      <a href="<?= e($url(['pestana' => $clave])) ?>" class="<?= $pestana === $clave ? 'on' : '' ?><?= $clave === 'POR_COMPLETAR' && $conteos[$clave] ? ' pend' : '' ?>"<?= $pestana === $clave ? ' aria-current="page"' : '' ?>><?= $etq ?><span class="mono"><?= $conteos[$clave] ?></span></a>
    <?php endforeach; ?>
  </nav>
  <form method="get" class="toolbar iaas-filtros">
    <?php if ($pestana !== 'TODAS'): ?><input type="hidden" name="pestana" value="<?= e($pestana) ?>"><?php endif; ?>
    <label class="search"><svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.4"/><path d="m10.5 10.5 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      <input name="q" value="<?= e($filtros['q']) ?>" placeholder="Paciente, DNI o historia clínica" aria-label="Buscar"></label>
    <div class="filtro-caja"><select name="tipo" data-nosearch="true" aria-label="Tipo de IAAS" onchange="this.form.submit()">
      <option value="">Todos los tipos</option>
      <?php foreach (['ITS' => 'ITS', 'ITU' => 'ITU', 'NEU' => 'Neumonía', 'ISQ' => 'ISQ', 'END' => 'Endometritis'] as $cod => $et): ?>
        <option value="<?= $cod ?>"<?= $filtros['tipo'] === $cod ? ' selected' : '' ?>><?= $et ?></option>
      <?php endforeach; ?>
    </select></div>
    <div class="filtro-caja"><select name="mes" data-nosearch="true" aria-label="Mes" onchange="this.form.submit()">
      <option value="">Todo el año</option>
      <?php foreach (IaasNorma::MESES as $n => $nombre): ?><option value="<?= $n ?>"<?= $filtros['mes'] === $n ? ' selected' : '' ?>><?= ucfirst($nombre) ?></option><?php endforeach; ?>
    </select></div>
    <div class="filtro-caja"><select name="anio" data-nosearch="true" aria-label="Año" onchange="this.form.submit()">
      <?php for ($a = (int) date('Y'); $a >= 2024; $a--): ?><option value="<?= $a ?>"<?= $filtros['anio'] === $a ? ' selected' : '' ?>><?= $a ?></option><?php endfor; ?>
    </select></div>
  </form>

  <?php if (!$casos): ?>
    <div class="iaas-vacio-tabla">
      <?php if ($pestana === 'POR_COMPLETAR'): ?>No hay fichas por completar en este periodo.
      <?php elseif ($filtros['q'] !== '' || $filtros['tipo'] !== ''): ?>Ninguna ficha coincide con los filtros.
      <?php else: ?>Todavía no hay fichas IAAS en este periodo. Se crean desde la ronda («Inicia IAAS») o con «Nueva ficha IAAS».<?php endif; ?>
    </div>
  <?php else: ?>
    <div class="tabla-desplazable">
      <table class="iaas-tabla-casos">
        <thead><tr><th>Ficha</th><th>Paciente</th><th>IAAS</th><th>Factor de riesgo</th><th>Servicio</th><th>Agente</th><th>Estado</th></tr></thead>
        <tbody>
          <?php foreach ($casos as $c):
            $factor = IaasNorma::FACTOR_POR_TIPO[$c['tipo_iaas']];
            $riesgo = $factor === 'dispositivo' ? ($c['dispositivo_tipo'] ? IaasNorma::DISPOSITIVOS_SIGLA[$c['dispositivo_tipo']] : '—')
                : ($c['procedimiento_tipo'] ? IaasNorma::PROCEDIMIENTO_SINGULAR[$c['procedimiento_tipo']] . ($c['fecha_procedimiento'] ? ' · ' . date('d/m', strtotime($c['fecha_procedimiento'])) : '') : '—');
            $estado = $c['anulado_en'] ? ['sin', 'Anulada'] : ($c['estado'] === 'COMPLETO' ? ['con', 'Completa'] : ['warn', 'Por completar']);
          ?>
            <tr class="iaas-fila-enlace<?= $c['anulado_en'] ? ' anulada' : '' ?>" data-href="/iaas/casos/<?= (int) $c['id'] ?>">
              <td><a class="mono iaas-cod-ficha" href="/iaas/casos/<?= (int) $c['id'] ?>"><?= e(IaasCaso::codigo($c)) ?></a><div class="cell-sub"><?= e(date('d/m/Y', strtotime($c['fecha_iaas']))) ?></div></td>
              <td><div class="pt-name"><?= e(Persona::nombreCompleto($c)) ?></div><div class="pt-doc"><?= e($c['tipo_doc'] === 'SIN_DOCUMENTO' && $c['historia_clinica'] ? 'HC ' . $c['historia_clinica'] : documentoParaMostrar($c['tipo_doc'], $c['num_doc'])) ?></div></td>
              <td><span class="iaas-tipo-chip t-<?= e($c['tipo_iaas']) ?>"><?= e(IaasNorma::TIPOS_IAAS_SIGLA[$c['tipo_iaas']]) ?></span></td>
              <td><?= e($riesgo) ?></td>
              <td><?= e(IaasNorma::nombreSala(['upss' => $c['upss'], 'nombre' => $c['sala_nombre']])) ?></td>
              <td class="mono"><?= e($c['microorganismo'] ?? '—') ?></td>
              <td><span class="ns-chip <?= $estado[0] ?>"><i></i><?= $estado[1] ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<script src="<?= e(asset('/js/iaas.js')) ?>"></script>
