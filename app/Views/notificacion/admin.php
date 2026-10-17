<?php
use App\Core\Csrf;
use App\Models\NotificacionSemanal;

// Notificación semanal del ADMIN: cumplimiento nacional (2026-10-17). Ver NotificacionSemanalController.
$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$textoPlazo = $dias[(int) $plazo->format('w')] . ' ' . $plazo->format('d/m') . ', ' . $plazo->format('H:i');
$plazoVencido = $ahora > $plazo;
$inicio = inicioSemanaEpidemiologica($anio, $semana);
$rangoSe = $inicio->format('d/m') . ' al ' . $inicio->modify('+6 days')->format('d/m/Y');
$anterior = semanaEpidemiologica($inicio->modify('-7 days')->format('Y-m-d'));
$siguiente = semanaEpidemiologica($inicio->modify('+7 days')->format('Y-m-d'));
$haySiguiente = [$siguiente['anio'], $siguiente['semana']] <= [$actual['anio'], $actual['semana']];
$filtros = ['anio' => $anio, 'semana' => $semana, 'estado' => $filtroEstado, 'red' => $redId ?? '', 'q' => $busqueda];
$url = fn(array $cambios = []) => '/notificacion-semanal?' . http_build_query(array_filter(array_merge($filtros, $cambios), fn($v) => $v !== '' && $v !== null));
$volver = '?' . http_build_query(array_filter($filtros, fn($v) => $v !== '' && $v !== null));
$pendientes = $conteo['P'];
$etiquetaPendientes = $plazoVencido ? 'No notificaron' : 'Pendientes';
$cerradas = $conteo['T'] + $conteo['L'];
$estados = [
    'T' => ['T', 'A tiempo', '<path d="M5 12l5 5L20 7"/>'],
    'L' => ['L', 'Fuera de plazo', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
    'P' => ['P', 'Pendiente', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
    'X' => ['X', 'No notificó', '<path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/>'],
];
$icoEnviar = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>';
$tile = function (string $clave, string $etiqueta, ?string $color, int $valor) use ($filtroEstado, $url, $total): string {
    $on = $filtroEstado === $clave || ($clave === '' && $filtroEstado === '');
    return '<a class="card ns-kpi' . ($on ? ' on' : '') . '" href="' . e($url(['estado' => $clave])) . '"' . ($on ? ' aria-current="true"' : '') . '>'
        . '<div class="eyebrow">' . ($color ? '<i style="background:' . $color . '"></i>' : '') . e($etiqueta) . '</div>'
        . '<div class="ns-kpi-v mono">' . $valor . ($clave !== '' ? '<small> / ' . $total . '</small>' : '') . '</div></a>';
};
?>
<div class="page-head">
  <div>
    <div class="page-title">Notificación semanal</div>
    <div class="page-desc">Cierre semanal de los establecimientos · plazo de la SE <?= $semana ?>: <?= $textoPlazo ?><?= $plazoVencido ? ' (vencido)' : '' ?></div>
  </div>
  <div class="spacer"></div>
  <nav class="ns-semana" aria-label="Semana epidemiológica">
    <a class="ns-flecha" href="<?= e($url(['anio' => $anterior['anio'], 'semana' => $anterior['semana']])) ?>" aria-label="Semana anterior">‹</a>
    <div class="ns-semana-lbl"><b class="mono">SE <?= $semana ?> · <?= $anio ?></b><small><?= $rangoSe ?><?= $enCurso ? ' · en curso' : '' ?></small></div>
    <?php if ($haySiguiente): ?><a class="ns-flecha" href="<?= e($url(['anio' => $siguiente['anio'], 'semana' => $siguiente['semana']])) ?>" aria-label="Semana siguiente">›</a><?php else: ?><span class="ns-flecha off" aria-hidden="true">›</span><?php endif; ?>
  </nav>
</div>

<div class="ns-kpis">
  <?= $tile('', 'Establecimientos', null, $total) ?>
  <?= $tile('T', 'Cerradas a tiempo', 'var(--accent)', $conteo['T']) ?>
  <?= $tile('L', 'Cerradas tarde', '#C2622D', $conteo['L']) ?>
  <?= $tile('P', $etiquetaPendientes, 'var(--s-confirmado)', $pendientes) ?>
</div>

<div class="card ns-tabla-card">
  <div class="tarjeta-cab ns-pad">
    <div><h3>Establecimientos</h3>
      <div class="sub"><?= $total ? round($cerradas / $total * 100) : 0 ?> % ya cerraron la SE <?= $semana ?><?= $solicitudes ? ' · ' . $solicitudes . ' solicitud' . ($solicitudes === 1 ? '' : 'es') . ' de reapertura' : '' ?> · lo que hay que atender, arriba</div></div>
    <div class="spacer"></div>
    <?php if ($pendientes): ?>
      <form method="post" action="/notificacion-semanal/recordar" data-confirmar="¿Enviar un recordatorio por correo a los registradores de los <?= $pendientes ?> establecimientos que no cerraron la SE <?= $semana ?>?">
        <?= Csrf::campoOculto() ?>
        <input type="hidden" name="anio" value="<?= $anio ?>"><input type="hidden" name="semana" value="<?= $semana ?>"><input type="hidden" name="volver" value="<?= e($volver) ?>">
        <button type="submit" class="btn btn-ghost ns-btn-sm"><?= $icoEnviar ?>Recordar a <?= $pendientes ?> <?= $plazoVencido ? 'sin notificar' : 'pendientes' ?></button>
      </form>
    <?php endif; ?>
  </div>
  <?php if ($total): ?>
    <div class="ns-barra" role="img" aria-label="<?= $conteo['T'] ?> a tiempo, <?= $conteo['L'] ?> tarde, <?= $pendientes ?> sin cerrar">
      <?php if ($conteo['T']): ?><span class="T" style="flex:<?= $conteo['T'] ?>"></span><?php endif; ?>
      <?php if ($conteo['L']): ?><span class="L" style="flex:<?= $conteo['L'] ?>"></span><?php endif; ?>
      <?php if ($pendientes): ?><span class="X" style="flex:<?= $pendientes ?>"></span><?php endif; ?>
    </div>
  <?php endif; ?>
  <form class="ns-filtros" method="get" action="/notificacion-semanal">
    <input type="hidden" name="anio" value="<?= $anio ?>"><input type="hidden" name="semana" value="<?= $semana ?>">
    <?php if ($filtroEstado !== ''): ?><input type="hidden" name="estado" value="<?= e($filtroEstado) ?>"><?php endif; ?>
    <div class="filtro-caja ns-buscar"><input type="search" name="q" value="<?= e($busqueda) ?>" placeholder="Buscar establecimiento o red" aria-label="Buscar establecimiento"></div>
    <div class="filtro-caja"><select name="red" aria-label="Red" onchange="this.form.submit()">
      <option value="">Todas las redes</option>
      <?php foreach ($redes as $red): ?><option value="<?= (int) $red['id'] ?>" <?= seleccionado((string) $redId, $red['id']) ?>><?= e($red['nombre']) ?></option><?php endforeach; ?>
    </select></div>
    <span class="spacer"></span>
    <span class="ns-nota"><?= count($filas) ?> de <?= $total ?></span>
  </form>
  <div class="ns-desplazable">
  <table class="ns-est">
    <thead><tr><th>Establecimiento</th><th>Estado SE <?= $semana ?></th><th class="ns-ocultar-s">Cierre</th><th class="n ns-ocultar-s">Fichas</th><th class="ns-ocultar-s">Registradores</th><th class="ns-ocultar-s">SE <?= $previas[0]['semana'] ?>–<?= end($previas)['semana'] ?></th><th></th></tr></thead>
    <tbody>
      <?php if (!$filas): ?>
        <tr><td colspan="7" class="ns-vacio">Sin establecimientos con este filtro.</td></tr>
      <?php endif; ?>
      <?php foreach ($filas as $fila): [$clase, $texto, $icono] = $estados[$fila['estado']]; ?>
        <tr>
          <td><div class="pt-name"><?= e(nombreEnOracion($fila['nombre'])) ?></div><div class="cell-sub"><?= e($fila['red_nombre'] ?? 'Sin red') ?></div></td>
          <td>
            <span class="ns-st <?= $clase ?>"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icono ?></svg><?= $texto ?></span>
            <?php if ($fila['solicitud']): ?><div class="ns-solicitud" title="<?= e($fila['cierre']['motivo_solicitud']) ?>">Pide reabrir: «<?= e(mb_strimwidth($fila['cierre']['motivo_solicitud'], 0, 60, '…')) ?>»</div><?php endif; ?>
            <?php if ($fila['reabierta']): ?><div class="cell-sub">Reabierta el <?= e(date('d/m H:i', strtotime($fila['reabierta']['anulada_en']))) ?></div><?php endif; ?>
          </td>
          <td class="mono ns-ocultar-s ns-cierre-col"><?php if ($fila['cierre']): ?><?= e(date('d/m · H:i', strtotime($fila['cierre']['creado_en']))) ?><div class="cell-sub" title="<?= e($fila['cierre']['motivo_tardio'] ?? '') ?>"><?= e($fila['cierre']['usuario_nombre']) ?></div><?php else: ?>—<?php endif; ?></td>
          <td class="n mono ns-ocultar-s"><?= $fila['fichas'] ?><?php if ($fila['tardias']): ?><div class="ns-tardias" title="Registradas después del cierre"><?= $fila['tardias'] ?> tardía<?= $fila['tardias'] === 1 ? '' : 's' ?></div><?php endif; ?></td>
          <td class="ns-ocultar-s"><?= $fila['registradores'] ? e(implode(', ', array_map('nombreEnOracion', $fila['registradores']))) : '<span class="ns-sin">Sin registrador activo</span>' ?></td>
          <td class="ns-ocultar-s"><div class="ns-spark"><?php foreach ($fila['serie'] as $s): ?><i class="<?= $s['estado'] ?>" title="SE <?= $s['semana'] ?>: <?= e(NotificacionSemanal::ETIQUETAS[$s['estado']]) ?>"></i><?php endforeach; ?></div></td>
          <td class="ns-acciones">
            <?php if (in_array($fila['estado'], ['P', 'X'], true)): ?>
              <form method="post" action="/notificacion-semanal/recordar">
                <?= Csrf::campoOculto() ?>
                <input type="hidden" name="anio" value="<?= $anio ?>"><input type="hidden" name="semana" value="<?= $semana ?>"><input type="hidden" name="volver" value="<?= e($volver) ?>">
                <input type="hidden" name="establecimiento_id" value="<?= (int) $fila['id'] ?>">
                <button type="submit" class="btn btn-ghost ns-btn-sm"<?= $fila['registradores'] ? '' : ' disabled title="Sin registrador activo"' ?>><?= $icoEnviar ?>Recordar</button>
              </form>
            <?php else: ?>
              <button type="button" class="btn btn-ghost ns-btn-sm" data-reabrir="<?= (int) $fila['id'] ?>" data-nombre="<?= e(nombreEnOracion($fila['nombre'])) ?>">Reabrir</button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="modal-fondo" id="modalReabrir" hidden>
  <form class="modal-caja" method="post" action="/notificacion-semanal/reabrir" role="dialog" aria-modal="true" aria-labelledby="modalReabrirTitulo">
    <?= Csrf::campoOculto() ?>
    <input type="hidden" name="anio" value="<?= $anio ?>"><input type="hidden" name="semana" value="<?= $semana ?>"><input type="hidden" name="volver" value="<?= e($volver) ?>">
    <input type="hidden" name="establecimiento_id" value="">
    <div class="modal-cab"><h3 id="modalReabrirTitulo">Reabrir la SE <?= $semana ?></h3>
      <p><span data-nombre-est></span> podrá agregar fichas y volver a cerrarla; si el plazo ya venció, el nuevo cierre será tardío. Queda registrado quién la reabrió y por qué.</p></div>
    <div class="ns-campo ns-pad-modal"><label for="motivoReabrir">Motivo <em>*</em></label>
      <textarea id="motivoReabrir" name="motivo" maxlength="255" required></textarea></div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-primary">Reabrir</button></div>
  </form>
</div>
<script src="<?= e(asset('/js/notificacion-semanal.js')) ?>"></script>
