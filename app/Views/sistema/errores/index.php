<?php
use App\Core\RegistroErrores;
use App\Models\ErrorRegistro;

// Errores del sistema (2026-10-27): un renglón por error, con sus
// repeticiones agrupadas. Ver ErroresController::index.
$url = fn(array $cambios) => '/sistema/errores?' . http_build_query(array_filter(array_merge(['estado' => $estado, 'origen' => $origen], $cambios)));
$estados = [
    'PENDIENTES' => ['Sin resolver', (int) $resumen['pendientes'], 'c'],
    'RESUELTOS'  => ['Resueltos', (int) $resumen['resueltos'], ''],
    'TODOS'      => ['Todos', (int) $resumen['pendientes'] + (int) $resumen['resueltos'], ''],
];
$origenes = ['' => 'Todos', 'PHP' => 'Servidor', 'JS' => 'Navegador'];
$cuando = function (string $fecha): string {
    $seg = time() - strtotime($fecha);
    return match (true) {
        $seg < 60     => 'hace un momento',
        $seg < 3600   => 'hace ' . intdiv($seg, 60) . ' min',
        $seg < 86400  => 'hace ' . intdiv($seg, 3600) . ' h',
        $seg < 172800 => 'ayer, ' . date('H:i', strtotime($fecha)),
        default       => date('d/m/Y H:i', strtotime($fecha)),
    };
};
?>
<div class="page-head">
  <div>
    <div class="page-title">Errores del sistema</div>
    <div class="page-desc">Lo que falló en el servidor y en el navegador de los usuarios · <?= (int) $resumen['ultimas_24h'] ?> en las últimas 24 horas</div>
  </div>
  <div class="spacer"></div>
  <form class="err-buscar" method="get" action="/sistema/errores" role="search">
    <div class="filtro-caja"><input name="codigo" placeholder="Código, p. ej. E-00042" aria-label="Buscar por código" autocomplete="off"></div>
    <button type="submit" class="btn btn-ghost ns-btn-sm">Abrir</button>
  </form>
</div>

<div class="card cat-panel">
  <div class="cat-alertas">
    <?php foreach ($estados as $clave => [$etiqueta, $n, $tono]): ?>
      <a class="cat-alerta <?= $tono ?><?= $estado === $clave ? ' on' : '' ?>" href="<?= e($url(['estado' => $clave])) ?>"<?= $n === 0 ? ' data-cero' : '' ?>><?= e($etiqueta) ?> <b><?= $n ?></b></a>
    <?php endforeach; ?>
    <div class="err-seg" role="group" aria-label="Origen">
      <?php foreach ($origenes as $clave => $etiqueta): ?>
        <a class="<?= (string) $origen === $clave ? 'on' : '' ?>" href="<?= e($url(['origen' => $clave])) ?>"><?= e($etiqueta) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="cat-desplazable" style="margin-top:14px">
    <table class="cat-tabla err-tabla">
      <thead><tr>
        <th>Error</th>
        <th>Origen</th>
        <th class="n">Veces</th>
        <th class="cat-ocultar-s">Afectó a</th>
        <th>Última vez</th>
        <th>Estado</th>
      </tr></thead>
      <tbody>
        <?php if (!$grupos): ?>
          <tr><td colspan="6" class="cat-vacio"><?= $estado === 'PENDIENTES' ? 'No hay errores sin resolver.' : 'No hay errores con este filtro.' ?></td></tr>
        <?php endif; ?>
        <?php foreach ($grupos as $g): $pendiente = (int) $g['pendientes'] > 0; ?>
          <tr>
            <td>
              <a class="err-msj" href="/sistema/errores/<?= (int) $g['ultimo_id'] ?>" title="<?= e($g['mensaje']) ?>"><?= e($g['mensaje']) ?></a>
              <div class="cell-sub cat-mono"><?= e(RegistroErrores::codigo((int) $g['ultimo_id'])) ?> · <?= e($g['archivo'] ? $g['archivo'] . ($g['linea'] ? ':' . $g['linea'] : '') : 'sin archivo') ?></div>
            </td>
            <td><span class="cat-badge<?= $g['origen'] === 'JS' ? ' w' : '' ?>"><?= $g['origen'] === 'JS' ? 'Navegador' : 'Servidor' ?></span><div class="cell-sub"><?= e(ErrorRegistro::NIVELES[$g['nivel']] ?? $g['nivel']) ?></div></td>
            <td class="n cat-mono"><?= (int) $g['veces'] ?></td>
            <td class="cat-ocultar-s"><?= (int) $g['usuarios'] ?> <?= (int) $g['usuarios'] === 1 ? 'usuario' : 'usuarios' ?><div class="cell-sub"><?= (int) $g['ipress'] ?> IPRESS</div></td>
            <td><?= e($cuando($g['ultimo'])) ?><div class="cell-sub">desde <?= e(date('d/m/Y', strtotime($g['primero']))) ?></div></td>
            <td><?= $pendiente ? '<span class="cat-badge w"><i></i>Sin resolver</span>' : '<span class="cat-badge ok"><i></i>Resuelto</span>' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<p class="err-nota">Un error resuelto que vuelve a ocurrir aparece otra vez como sin resolver. No se guarda lo escrito en los formularios, solo los nombres de los campos.</p>
