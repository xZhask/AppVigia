<?php
use App\Core\Csrf;
use App\Models\IaasIndicadores;
use App\Models\IaasNorma;

// Indicadores del mes (Anexo 10), revisión de calidad y cierre por sala,
// personal de salud del año (Anexo 9). Cada número lleva a su origen.
$anexo = 'Anexos 9 y 10';
$mesTxt = IaasNorma::nombreMes($mes, $anio);
$claveMes = sprintf('%04d-%02d', $anio, $mes);
$mesAnt = date('Y-m', strtotime($claveMes . '-01 -1 month'));
$mesSig = date('Y-m', strtotime($claveMes . '-01 +1 month'));
$hayMesSig = $mesSig <= date('Y-m');
$url = fn(array $p) => '/iaas/indicadores?' . http_build_query(array_filter($p + ['mes' => $claveMes, 'sala' => $salaId]));
$filas = $resultado['filas'];
$personal = $resultado['personal'];
$sinHosp = $configuracion && $configuracion['sin_hospitalizacion'];
$salasPendientes = array_filter($calidad, fn($c) => !$c['cierre'] || $c['cierre']['reabierto_en'] !== null);
$problemas = 0;
foreach ($calidad as $c) {
    $problemas += ($c['sin_marcar'] ? 1 : 0) + ($c['sin_procedim'] ? 1 : 0) + ($c['por_completar'] ? 1 : 0);
}
$tasa = fn(?float $t) => $t === null ? '—' : number_format($t, 2, ',', ' ');
$urlNum = function (array $f) use ($anio, $mes): string {
    if ($f['tipo_iaas'] === 'EXPO') {
        return '/iaas/exposicion?' . http_build_query(['anio' => $anio, 'mes' => $mes]);
    }
    return '/iaas/casos?' . http_build_query(['anio' => $anio, 'mes' => $mes, 'tipo' => $f['tipo_iaas']]);
};
$salaParaDisp = function (array $f) use ($salas, $salaId): ?int {
    if ($salaId) {
        return $salaId;
    }
    foreach ($salas as $s) {
        if (isset($f['dispositivo']) && in_array($f['dispositivo'], IaasNorma::DISPOSITIVOS_POR_UPSS[$s['upss']], true)) {
            return (int) $s['id'];
        }
        if (isset($f['procedimiento']) && in_array($f['procedimiento'], IaasNorma::PROCEDIMIENTOS_POR_UPSS[$s['upss']] ?? [], true)) {
            return (int) $s['id'];
        }
    }
    return null;
};
$acciones = '<button type="button" class="btn btn-ghost ns-btn-sm" data-imprimir>Imprimir</button>';
require __DIR__ . '/_cabecera.php';
?>
<div class="iaas-dia-barra">
  <nav class="ns-semana iaas-dia-nav" aria-label="Mes">
    <a class="ns-flecha" href="<?= e($url(['mes' => $mesAnt])) ?>" aria-label="Mes anterior">‹</a>
    <div class="ns-semana-lbl"><b><?= e($mesTxt) ?></b><small><?= $mesTerminado ? 'mes terminado' : 'mes en curso' ?></small></div>
    <?php if ($hayMesSig): ?><a class="ns-flecha" href="<?= e($url(['mes' => $mesSig])) ?>" aria-label="Mes siguiente">›</a><?php else: ?><span class="ns-flecha off" aria-hidden="true">›</span><?php endif; ?>
  </nav>
  <?php if (!$sinHosp && count($salas) > 1): ?>
    <form method="get" class="iaas-filtro-sala">
      <input type="hidden" name="mes" value="<?= e($claveMes) ?>">
      <div class="filtro-caja"><select name="sala" data-nosearch="true" aria-label="Sala" onchange="this.form.submit()">
        <option value="">Todo el establecimiento</option>
        <?php foreach ($salas as $s): ?><option value="<?= (int) $s['id'] ?>"<?= $salaId === (int) $s['id'] ? ' selected' : '' ?>><?= e(IaasNorma::nombreSala($s)) ?></option><?php endforeach; ?>
      </select></div>
    </form>
  <?php endif; ?>
</div>

<?php if (!$sinHosp && $calidad): ?>
  <?php if (!$mesTerminado): ?>
    <div class="ns-estado abierta iaas-estado-mes"><div class="ns-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div><div><h2><?= e($mesTxt) ?> en curso</h2><p>Las tasas se van armando con la ronda de cada día. El mes se revisa y se cierra desde el día 1 del mes siguiente.</p></div></div>
  <?php elseif (!$salasPendientes): ?>
    <div class="ns-estado cerrada iaas-estado-mes"><div class="ns-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg></div><div><h2>Listo para reportar</h2><p>Todas las salas cerraron <?= e(mb_strtolower($mesTxt)) ?>. Estos son los números para transcribir.</p></div></div>
  <?php else: ?>
    <div class="ns-estado vencida iaas-estado-mes"><div class="ns-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg></div><div><h2><?= count($salasPendientes) ?> sala<?= count($salasPendientes) === 1 ? '' : 's' ?> sin cerrar</h2><p><?= $problemas ? 'Revisa lo pendiente abajo antes de cerrar: ' : '' ?>las tasas no son definitivas hasta que cada sala cierre el mes.</p></div></div>
  <?php endif; ?>
<?php endif; ?>

<section class="card table-card iaas-ind">
  <div class="card-head"><div><h3>Indicadores del mes</h3><div class="sub">Anexo 10 · <?= $salaId ? 'sala elegida' : 'todo el establecimiento' ?> · clic en un número para ver de dónde sale</div></div></div>
  <div class="tabla-desplazable">
    <table class="iaas-tabla-ind">
      <thead><tr><th>N.º</th><th>Evento vigilado</th><th class="n">Numerador</th><th class="n">Denominador</th><th class="n">Tasa</th><th>Cálculo</th></tr></thead>
      <?php foreach (['densidad' => 'Densidad de incidencia · por 1000 días-dispositivo', 'acumulada' => 'Incidencia acumulada · por 100'] as $grupo => $tituloGrupo):
        $delGrupo = array_values(array_filter($filas, fn($f) => $f['grupo'] === $grupo));
        if (!$delGrupo) continue;
      ?>
        <tbody>
          <tr class="iaas-grupo"><th colspan="6"><?= e($tituloGrupo) ?></th></tr>
          <?php foreach ($delGrupo as $f):
            $salaDen = $salaParaDisp($f);
            $urlDen = $f['tipo_iaas'] === 'EXPO' ? null : ($salaDen ? '/iaas/ronda?' . http_build_query(['sala' => $salaDen, 'vista' => 'mes', 'mes' => $claveMes]) : null);
          ?>
            <tr>
              <td class="mono"><?= (int) $f['n'] ?></td>
              <td><div class="pt-name"><?= e($f['evento']) ?></div><div class="cell-sub"><?= e($f['detalle']) ?></div></td>
              <td class="n mono"><a href="<?= e($urlNum($f)) ?>" title="Ver las fichas"><?= (int) $f['num'] ?></a></td>
              <td class="n mono">
                <?php if ($f['tipo_iaas'] === 'EXPO' && !$f['den']): ?><a class="iaas-alerta-txt" href="#personal">Falta el personal</a>
                <?php elseif ($urlDen): ?><a href="<?= e($urlDen) ?>" title="Ver la ronda del mes"><?= (int) $f['den'] ?></a>
                <?php else: ?><?= (int) $f['den'] ?><?php endif; ?>
              </td>
              <td class="n mono iaas-tasa<?= $f['tasa'] === null ? ' nd' : '' ?>"><?= $tasa($f['tasa']) ?></td>
              <td class="cell-sub">N.º de <?= e($f['num_txt']) ?> / N.º de <?= e($f['den_txt']) ?> × <?= $f['factor'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      <?php endforeach; ?>
    </table>
  </div>
  <p class="iaas-nota-tabla">«—» = sin denominador en el mes. Las ISQ y las endometritis se cuentan en el mes del procedimiento; los días con la IAAS en tratamiento (×) no suman al denominador.</p>
</section>

<?php if ($resultado['neonatos']): ?>
<section class="card table-card iaas-ind">
  <div class="card-head"><div><h3>Neonatos por peso al nacer</h3><div class="sub">UCI neonatal y neonatología · tasa por 1000 días-dispositivo (casos / días)</div></div></div>
  <div class="tabla-desplazable">
    <table class="iaas-tabla-ind">
      <thead><tr><th>Evento</th><?php foreach (IaasNorma::CATEGORIAS_PESO as $cat): ?><th class="n"><?= e($cat) ?></th><?php endforeach; ?><th class="n">Sin peso</th></tr></thead>
      <tbody>
        <?php foreach ($resultado['neonatos'] as $fila): ?>
          <tr><td class="pt-name"><?= e($fila['evento']) ?></td>
            <?php foreach ($fila['celdas'] as $cat => $c): ?>
              <td class="n mono<?= $cat === 'SIN' && $c['den'] ? ' iaas-alerta-txt' : '' ?>"><?= $tasa($c['tasa']) ?><div class="cell-sub"><?= $c['num'] ?> / <?= $c['den'] ?></div></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<div class="iaas-ind-cols">
  <?php if (!$sinHosp && $calidad): ?>
  <section class="card tarjeta iaas-calidad">
    <div class="tarjeta-cab"><div><h3>Revisión y cierre por sala</h3><div class="sub">Antes de reportar: lo que falta en cada sala</div></div></div>
    <ul class="iaas-calidad-lista">
      <?php foreach ($calidad as $c):
        $s = $c['sala'];
        $cerrado = $c['cierre'] && $c['cierre']['reabierto_en'] === null;
        $urlMes = '/iaas/ronda?' . http_build_query(['sala' => $s['id'], 'vista' => 'mes', 'mes' => $claveMes]);
        $ok = !$c['sin_marcar'] && !$c['sin_procedim'] && !$c['por_completar'];
      ?>
        <li class="<?= $cerrado ? 'cerrada' : '' ?>">
          <div class="iaas-cal-cab"><b><?= e(IaasNorma::nombreSala($s)) ?></b>
            <?php if ($cerrado): ?><span class="ns-chip con"><i></i>Cerrado</span><?php elseif ($c['cierre']): ?><span class="ns-chip warn"><i></i>Reabierto</span><?php else: ?><span class="ns-chip sin"><i></i>Abierto</span><?php endif; ?>
          </div>
          <ul class="iaas-checks">
            <li class="<?= $c['sin_marcar'] ? 'mal' : 'bien' ?>"><?= $c['sin_marcar'] ? '<a href="' . e($urlMes) . '">' . $c['sin_marcar'] . ' día' . ($c['sin_marcar'] === 1 ? '' : 's') . '-dispositivo sin marcar</a>' : 'Ronda completa' ?></li>
            <?php if (!empty(IaasNorma::PROCEDIMIENTOS_POR_UPSS[$s['upss']])): ?>
              <li class="<?= $c['sin_procedim'] ? 'mal' : 'bien' ?>"><?= $c['sin_procedim'] ? '<a href="' . e($urlMes) . '">' . $c['sin_procedim'] . ' día' . ($c['sin_procedim'] === 1 ? '' : 's') . ' sin registrar ' . ($s['upss'] === 'GINECO_OBSTETRICIA' ? 'partos' : 'cirugías') . '</a>' : ($s['upss'] === 'GINECO_OBSTETRICIA' ? 'Partos' : 'Cirugías') . ' registrados todos los días' ?></li>
            <?php endif; ?>
            <li class="<?= $c['por_completar'] ? 'mal' : 'bien' ?>"><?= $c['por_completar'] ? '<a href="/iaas/casos?' . e(http_build_query(['pestana' => 'POR_COMPLETAR', 'anio' => $anio, 'mes' => $mes])) . '">' . $c['por_completar'] . ' ficha' . ($c['por_completar'] === 1 ? '' : 's') . ' por completar</a>' : 'Fichas completas' ?></li>
          </ul>
          <?php if ($cerrado): ?>
            <p class="cell-sub">Cerrado por <?= e($c['cierre']['usuario_nombre']) ?> el <?= e(date('d/m/Y H:i', strtotime($c['cierre']['creado_en']))) ?>.</p>
            <?php if ($esAdmin): ?><button type="button" class="btn btn-quiet ns-btn-sm" data-modal="modalReabrir" data-sala="<?= (int) $s['id'] ?>" data-nombre="<?= e(IaasNorma::nombreSala($s)) ?>">Reabrir</button><?php endif; ?>
          <?php elseif ($mesTerminado): ?>
            <?php if ($c['cierre'] && $c['cierre']['reabierto_en']): ?><p class="cell-sub">Reabierto por <?= e($c['cierre']['reabierto_nombre']) ?>: «<?= e($c['cierre']['motivo_reapertura']) ?>».</p><?php endif; ?>
            <form method="post" action="/iaas/indicadores/cerrar" data-confirmar="<?= e(($ok ? '' : 'Esta sala tiene pendientes. ') . '¿Cerrar ' . mb_strtolower($mesTxt) . ' para ' . IaasNorma::nombreSala($s) . '? Después ya no se podrá editar su ronda de ese mes.') ?>">
              <?= Csrf::campoOculto() ?><input type="hidden" name="sala_id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="anio" value="<?= $anio ?>"><input type="hidden" name="mes" value="<?= $mes ?>"><input type="hidden" name="filtro_sala" value="<?= (int) $salaId ?>">
              <button type="submit" class="btn <?= $ok ? 'btn-primary' : 'btn-ghost' ?> ns-btn-sm">Cerrar <?= e(mb_strtolower(IaasNorma::MESES[$mes])) ?></button>
            </form>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <section class="card tarjeta iaas-personal" id="personal">
    <div class="tarjeta-cab"><div><h3>Personal de salud <?= $anio ?></h3><div class="sub">Anexo 9 · denominador de la exposición laboral</div></div><div class="spacer"></div>
      <?php if ($personal): ?><span class="ns-chip con"><i></i><?= IaasIndicadores::totalPersonal($personal) ?> en total</span><?php else: ?><span class="ns-chip warn"><i></i>Sin registrar</span><?php endif; ?></div>
    <form method="post" action="/iaas/indicadores/personal">
      <?= Csrf::campoOculto() ?><input type="hidden" name="anio" value="<?= $anio ?>"><input type="hidden" name="volver_mes" value="<?= e($claveMes) ?>">
      <div class="iaas-personal-grid">
        <?php foreach (IaasNorma::CONDICIONES_PERSONAL as $cod => $et): $campo = strtolower($cod); ?>
          <div class="field"><label class="fl" for="per-<?= $campo ?>"><?= e($et) ?></label><div class="control mono"><input type="number" id="per-<?= $campo ?>" name="personal_<?= $campo ?>" min="0" max="20000" value="<?= $personal ? (int) $personal[$campo] : '' ?>" data-personal></div></div>
        <?php endforeach; ?>
      </div>
      <div class="iaas-personal-pie"><span>Total: <b class="mono" data-personal-total><?= $personal ? IaasIndicadores::totalPersonal($personal) : 0 ?></b></span><button type="submit" class="btn btn-ghost ns-btn-sm"><?= $personal ? 'Actualizar' : 'Guardar' ?></button></div>
    </form>
  </section>
</div>

<?php if ($esAdmin): ?>
<div class="modal-fondo" id="modalReabrir" hidden>
  <form class="modal-caja" method="post" action="/iaas/indicadores/reabrir" role="dialog" aria-modal="true" aria-labelledby="modalReabrirTit">
    <?= Csrf::campoOculto() ?><input type="hidden" name="sala_id" data-campo="sala"><input type="hidden" name="anio" value="<?= $anio ?>"><input type="hidden" name="mes" value="<?= $mes ?>"><input type="hidden" name="filtro_sala" value="<?= (int) $salaId ?>">
    <div class="modal-cab"><h3 id="modalReabrirTit">Reabrir <?= e(mb_strtolower($mesTxt)) ?></h3><p data-campo-txt="nombre"></p></div>
    <div class="ns-campo ns-pad-modal"><label for="motivoReabrir">Motivo <em>*</em></label><textarea id="motivoReabrir" name="motivo" maxlength="255" required placeholder="Ej.: faltó registrar un retiro de CVC"></textarea></div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-primary">Reabrir</button></div>
  </form>
</div>
<?php endif; ?>
<script src="<?= e(asset('/js/iaas.js')) ?>"></script>
