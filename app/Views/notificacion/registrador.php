<?php
use App\Core\Csrf;
use App\Models\NotificacionSemanal;

// Notificación semanal del REGISTRADOR (2026-10-17). Ver NotificacionSemanalController.
$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$textoPlazo = $dias[(int) $plazo->format('w')] . ' ' . $plazo->format('d/m') . ', ' . $plazo->format('H:i');
$inicio = inicioSemanaEpidemiologica($anio, $semana);
$rangoSe = $inicio->format('d/m') . ' al ' . $inicio->modify('+6 days')->format('d/m/Y');
// Las enfermedades con fichas, arriba.
usort($enfermedades, fn($a, $b) => [empty($fichas[$a['id']]), $a['nombre']] <=> [empty($fichas[$b['id']]), $b['nombre']]);
$totalFichas = array_sum($fichas);
$conCasos = count(array_filter($enfermedades, fn($en) => !empty($fichas[$en['id']])));
$sinCasos = count($enfermedades) - $conCasos;
$abierta = in_array($estado, ['P', 'X'], true);
$vencida = $estado === 'X';
$nombreEst = nombreEnOracion($establecimiento['nombre']);
$urlSe = fn(array $s) => '/notificacion-semanal?' . http_build_query(['anio' => $s['anio'], 'semana' => $s['semana']]);
$volver = '?' . http_build_query(['anio' => $anio, 'semana' => $semana]);
$horasRestantes = ($plazo->getTimestamp() - $ahora->getTimestamp()) / 3600;
$faltan = $horasRestantes >= 48 ? 'en ' . (int) floor($horasRestantes / 24) . ' días' : ($horasRestantes >= 1 ? 'en ' . (int) floor($horasRestantes) . ' h' : 'en menos de 1 h');
$anterior = semanaEpidemiologica($inicio->modify('-7 days')->format('Y-m-d'));
$siguiente = semanaEpidemiologica($inicio->modify('+7 days')->format('Y-m-d'));
$hayAnterior = [$anterior['anio'], $anterior['semana']] >= [$primeraSe['anio'], $primeraSe['semana']];
$haySiguiente = [$siguiente['anio'], $siguiente['semana']] <= [$actual['anio'], $actual['semana']];
$urlFichas = fn(?int $enfermedadId = null) => '/casos?' . http_build_query(array_filter(['pestana' => 'TODAS', 'periodo' => 'rango', 'anio' => $anio, 'se_desde' => $semana, 'se_hasta' => $semana, 'enfermedad_id' => $enfermedadId]));
$ico = [
    'reloj'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    'check'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>',
    'alerta' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg>',
    'candado'=> '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>',
    'info'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>',
];
?>
<div class="page-head">
  <div>
    <div class="page-title">Notificación semanal</div>
    <div class="page-desc"><?= e($nombreEst) ?><?= $establecimiento['red_nombre'] ? ' · ' . e($establecimiento['red_nombre']) : '' ?></div>
  </div>
  <div class="spacer"></div>
  <nav class="ns-semana" aria-label="Semana epidemiológica">
    <?php if ($hayAnterior): ?><a class="ns-flecha" href="<?= e($urlSe($anterior)) ?>" aria-label="Semana anterior">‹</a><?php else: ?><span class="ns-flecha off" aria-hidden="true">‹</span><?php endif; ?>
    <div class="ns-semana-lbl"><b class="mono">SE <?= $semana ?> · <?= $anio ?></b><small><?= $rangoSe ?><?= $enCurso ? ' · en curso' : '' ?></small></div>
    <?php if ($haySiguiente): ?><a class="ns-flecha" href="<?= e($urlSe($siguiente)) ?>" aria-label="Semana siguiente">›</a><?php else: ?><span class="ns-flecha off" aria-hidden="true">›</span><?php endif; ?>
  </nav>
</div>

<?php if ($estado === 'P'): ?>
  <div class="ns-estado abierta">
    <div class="ns-ico"><?= $ico['reloj'] ?></div>
    <div><h2>SE <?= $semana ?> <?= $enCurso ? 'en curso' : 'terminada' ?> · falta cerrarla</h2>
      <p>Plazo: <b><?= $textoPlazo ?></b> (<?= $faltan ?>). Al cerrar, las enfermedades sin fichas quedan notificadas como <b>sin casos</b>.</p></div>
    <div class="ns-cuenta"><b class="mono"><?= $totalFichas ?></b><small>ficha<?= $totalFichas === 1 ? '' : 's' ?> esta semana</small></div>
  </div>
<?php elseif ($estado === 'X'): ?>
  <div class="ns-estado vencida">
    <div class="ns-ico"><?= $ico['alerta'] ?></div>
    <div><h2>SE <?= $semana ?> sin cerrar · plazo vencido</h2>
      <p>El plazo venció el <?= $textoPlazo ?>. Para DIRSAPOL tu establecimiento figura como <b>no notificó</b> esa semana. Aún puedes cerrarla indicando el motivo.</p></div>
    <div class="ns-cuenta"><b class="mono"><?= $totalFichas ?></b><small>ficha<?= $totalFichas === 1 ? '' : 's' ?> registrada<?= $totalFichas === 1 ? '' : 's' ?></small></div>
  </div>
<?php else: ?>
  <div class="ns-estado <?= $estado === 'T' ? 'cerrada' : 'abierta' ?>">
    <div class="ns-ico"><?= $estado === 'T' ? $ico['check'] : $ico['reloj'] ?></div>
    <div><h2>SE <?= $semana ?> cerrada <?= $estado === 'T' ? 'a tiempo' : 'fuera de plazo' ?></h2>
      <p>Cerrada el <?= e(date('d/m/Y \a \l\a\s H:i', strtotime($cierre['creado_en']))) ?> por <?= e($cierre['usuario_nombre']) ?>.
        <?= $estado === 'L' && $cierre['motivo_tardio'] ? 'Motivo: «' . e($cierre['motivo_tardio']) . '». ' : '' ?>
        Las fichas de esta semana que registres después del cierre se marcan como notificación tardía.</p></div>
    <div class="ns-cuenta"><b class="mono"><?= $totalFichas ?></b><small>ficha<?= $totalFichas === 1 ? '' : 's' ?> notificada<?= $totalFichas === 1 ? '' : 's' ?></small></div>
  </div>
<?php endif; ?>

<div class="ns-cols">
  <div class="card ns-tabla-card">
    <div class="tarjeta-cab ns-pad">
      <div><h3>Resumen por enfermedad</h3><div class="sub">Todas las enfermedades bajo vigilancia · las que no tienen fichas se notifican sin casos</div></div>
      <div class="spacer"></div>
      <label class="ns-toggle"><input type="checkbox" id="soloConCasos">Solo con casos</label>
    </div>
    <table class="ns-enf">
      <thead><tr><th>Enfermedad</th><th class="n ns-col-fichas">Fichas</th><th>Notificación</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($enfermedades as $enfermedad): $n = $fichas[$enfermedad['id']] ?? 0; ?>
          <tr class="<?= $n ? '' : 'cero' ?>">
            <td><span class="ns-enf-nom"><?= e($enfermedad['nombre_corto'] ?? $enfermedad['nombre']) ?></span><span class="ns-cie mono"><?= e($enfermedad['cie10']) ?></span><?php if ($enfermedad['tipo_notif'] === 'INMEDIATA'): ?><span class="ns-inm">Inmediata</span><?php endif; ?></td>
            <td class="n mono ns-col-fichas"><?= $n ?></td>
            <td><?php if ($n): ?><span class="ns-chip con"><i></i><?= $n ?> ficha<?= $n === 1 ? '' : 's' ?></span><?php else: ?><span class="ns-chip sin"><i></i><?= $abierta ? 'Se notificará sin casos' : 'Notificada sin casos' ?></span><?php endif; ?></td>
            <td class="ver"><?php if ($n): ?><a href="<?= e($urlFichas((int) $enfermedad['id'])) ?>">Ver</a><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if ($abierta): ?>
      <div class="ns-pie">
        <span>¿Falta algún caso?</span>
        <a class="btn btn-ghost ns-btn-sm" href="/casos/nuevo">+ Registrar ficha</a>
        <?php if (!$enCurso): ?><span class="ns-nota">Usa una fecha de notificación de la SE <?= $semana ?> (<?= $rangoSe ?>).</span><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="ns-lado">
    <?php if ($abierta): ?>
      <form class="card tarjeta ns-cierre" method="post" action="/notificacion-semanal/cerrar" id="formCierre"
            data-fichas="<?= $totalFichas ?>" data-sin-casos="<?= $sinCasos ?>" data-semana="<?= $semana ?>" data-tardio="<?= $vencida ? '1' : '' ?>">
        <?= Csrf::campoOculto() ?>
        <input type="hidden" name="anio" value="<?= $anio ?>"><input type="hidden" name="semana" value="<?= $semana ?>">
        <input type="hidden" name="volver" value="<?= e($volver) ?>">
        <h3><?= $vencida ? 'Cerrar semana fuera de plazo' : 'Cerrar la semana' ?></h3>
        <p>Revisa el resumen. Después de cerrar, las fichas nuevas de esta semana contarán como tardías.</p>
        <ul class="ns-suma">
          <li><span>Enfermedades con casos</span><b class="mono"><?= $conCasos ?></b></li>
          <li><span>Fichas registradas</span><b class="mono"><?= $totalFichas ?></b></li>
          <li><span>Se notifican sin casos</span><b class="mono"><?= $sinCasos ?></b></li>
        </ul>
        <?php if ($enCurso): ?>
          <div class="ns-aviso"><?= $ico['info'] ?><div>La SE <?= $semana ?> todavía no termina (cierra el sábado). Si la cierras ahora, lo que registres hasta el sábado contará como tardío.</div></div>
        <?php endif; ?>
        <?php if ($vencida): ?>
          <div class="ns-campo"><label for="motivoTardio">Motivo del cierre tardío <em>*</em></label>
            <textarea id="motivoTardio" name="motivo_tardio" maxlength="255" required placeholder="Ej.: falta de conectividad del 05/10 al 08/10"></textarea></div>
        <?php endif; ?>
        <label class="ns-confirma"><input type="checkbox" name="confirmo" value="1" id="confirmoCierre" required>
          <span>Confirmo que <b>no hay más casos</b> por notificar en la SE <?= $semana ?> en <?= e($nombreEst) ?>.</span></label>
        <button type="submit" class="btn btn-primary ns-cerrar" id="btnCerrar" disabled><?= $ico['candado'] ?>Cerrar SE <?= $semana ?></button>
      </form>
    <?php else: ?>
      <div class="card tarjeta ns-cierre">
        <h3>Detalle del cierre</h3>
        <div class="ns-meta">
          <div><span>Semana</span><b>SE <?= $semana ?> · <?= $anio ?></b></div>
          <div><span>Periodo</span><b><?= $rangoSe ?></b></div>
          <div><span>Cerrada por</span><b><?= e($cierre['usuario_nombre']) ?></b></div>
          <div><span>Estado</span><b><?= $estado === 'T' ? 'A tiempo' : 'Fuera de plazo' ?></b></div>
          <div><span>Fichas tardías</span><b class="mono"><?= $tardias ?></b></div>
        </div>
        <?php if ($cierre['reapertura_solicitada_en']): ?>
          <div class="ns-aviso"><?= $ico['info'] ?><div>Reapertura solicitada el <?= e(date('d/m/Y H:i', strtotime($cierre['reapertura_solicitada_en']))) ?>: «<?= e($cierre['motivo_solicitud']) ?>». Espera a que un administrador la reabra.</div></div>
        <?php else: ?>
          <button type="button" class="btn btn-ghost ns-btn-sm" data-abrir-modal="modalSolicitud">Solicitar reapertura</button>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($cierre && $cierre['anulada_en'] !== null): ?>
      <div class="ns-aviso"><?= $ico['info'] ?><div>Semana reabierta por <?= e($cierre['anulada_por_nombre']) ?> el <?= e(date('d/m/Y H:i', strtotime($cierre['anulada_en']))) ?><?= $cierre['motivo_reapertura'] ? ': «' . e($cierre['motivo_reapertura']) . '»' : '' ?>. Vuelve a cerrarla cuando termines.</div></div>
    <?php endif; ?>
    <div class="ns-info"><?= $ico['info'] ?><div>Cerrar la semana es obligatorio aunque no tengas casos. Así DIRSAPOL distingue <b>«sin casos»</b> de <b>«no reportó»</b>.</div></div>
  </div>
</div>

<div class="card tarjeta ns-historial-card">
  <div class="tarjeta-cab"><div><h3>Tus últimas <?= count($historial) ?> semanas</h3><div class="sub">Clic en una semana para ver su detalle o cerrarla</div></div></div>
  <div class="ns-historial">
    <?php foreach ($historial as $s): $sel = $s['anio'] === $anio && $s['semana'] === $semana; ?>
      <a class="ns-hw <?= $s['estado'] ?><?= $sel ? ' sel' : '' ?>" href="<?= e($urlSe($s)) ?>" title="SE <?= $s['semana'] ?> · <?= $s['anio'] ?>: <?= e(NotificacionSemanal::ETIQUETAS[$s['estado']]) ?>"<?= $sel ? ' aria-current="true"' : '' ?>>
        <b class="mono"><?= $s['semana'] ?></b><i></i><small><?= ['T' => 'A tiempo', 'L' => 'Tardía', 'X' => 'Sin cerrar', 'P' => 'Pendiente'][$s['estado']] ?></small>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="leyenda">
    <span><i class="celda T"></i>Cerrada a tiempo</span>
    <span><i class="celda L"></i>Cerrada fuera de plazo</span>
    <span><i class="celda X"></i>Sin cerrar (no notificó)</span>
    <span><i class="celda P"></i>Pendiente (plazo vigente)</span>
  </div>
</div>

<?php if ($abierta): ?>
<div class="modal-fondo" id="modalCierre" hidden>
  <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="modalCierreTitulo">
    <div class="modal-cab"><h3 id="modalCierreTitulo">¿Cerrar la SE <?= $semana ?>?</h3>
      <p>Se notificarán <b><?= $totalFichas ?> ficha<?= $totalFichas === 1 ? '' : 's' ?></b> y <b><?= $sinCasos ?> enfermedad<?= $sinCasos === 1 ? '' : 'es' ?> sin casos</b> de <?= e($nombreEst) ?>.<?= $vencida ? ' El cierre quedará registrado como tardío.' : '' ?> Queda registrado quién cerró y cuándo.</p></div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="button" class="btn btn-primary" id="confirmarCierre"><?= $ico['candado'] ?>Cerrar semana</button></div>
  </div>
</div>
<?php elseif (!$cierre['reapertura_solicitada_en']): ?>
<div class="modal-fondo" id="modalSolicitud" hidden>
  <form class="modal-caja" method="post" action="/notificacion-semanal/solicitar-reapertura" role="dialog" aria-modal="true" aria-labelledby="modalSolicitudTitulo">
    <?= Csrf::campoOculto() ?>
    <input type="hidden" name="anio" value="<?= $anio ?>"><input type="hidden" name="semana" value="<?= $semana ?>">
    <input type="hidden" name="volver" value="<?= e($volver) ?>">
    <div class="modal-cab"><h3 id="modalSolicitudTitulo">Solicitar reapertura</h3>
      <p>Un administrador verá la solicitud. Mientras tanto, la SE <?= $semana ?> sigue cerrada.</p></div>
    <div class="ns-campo ns-pad-modal"><label for="motivoSolicitud">Motivo <em>*</em></label>
      <textarea id="motivoSolicitud" name="motivo" maxlength="255" required placeholder="Ej.: se omitió un caso de tos ferina"></textarea></div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-primary">Enviar solicitud</button></div>
  </form>
</div>
<?php endif; ?>
<script src="<?= e(asset('/js/notificacion-semanal.js')) ?>"></script>
