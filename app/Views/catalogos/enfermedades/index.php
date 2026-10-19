<?php
// Catálogo de enfermedades (rediseño 2026-10-19). Ver EnfermedadesController::index.
// La estructura de cada ficha la define manifiesto_fichas.json (cargar_fichas.php);
// aquí solo se ajustan nombre, CIE-10, tipo de notificación y grupo, y se activa o desactiva.
$familias = array_values(array_unique(array_filter(array_column($enfermedades, 'familia'))));
sort($familias);
$descNotif = ['INMEDIATA' => 'Inmediata · dentro de las 24 horas', 'SEMANAL' => 'Semanal · en el cierre de la SE', 'MENSUAL' => 'Mensual'];
$aniosPrevios = range($actual['anio'] - 3, $actual['anio'] - 1);
$primeraSe = $semanas[0];
$ultimaSe = end($semanas);
$ico = [
    'db'     => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/></svg>',
    'ficha'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg>',
    'candado'=> '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>',
    'alerta' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg>',
    'puntos' => '<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="12" r="1.7" fill="currentColor"/><circle cx="12" cy="12" r="1.7" fill="currentColor"/><circle cx="19" cy="12" r="1.7" fill="currentColor"/></svg>',
    'x'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
];
$plantillas = '';
?>
<div class="page-head">
  <div>
    <div class="page-title">Enfermedades</div>
    <div class="page-desc">Enfermedades y eventos bajo vigilancia · fichas modelo MINSA</div>
  </div>
</div>

<div class="card cat-panel" data-catalogo>
  <div class="cat-alertas">
    <button type="button" class="cat-alerta" data-flag="">Todas <b>0</b></button>
    <button type="button" class="cat-alerta" data-flag="sinhist"><?= $ico['db'] ?>Sin corredor endémico <b>0</b></button>
    <button type="button" class="cat-alerta c" data-flag="sinficha"><?= $ico['ficha'] ?>Sin ficha definida <b>0</b></button>
  </div>
  <div class="cat-filtros">
    <div class="filtro-caja cat-buscar"><input type="search" data-filtro-q placeholder="Nombre o CIE-10" aria-label="Buscar enfermedad"></div>
    <div class="filtro-caja"><select data-filtro="notif" aria-label="Notificación" data-nosearch="true">
      <option value="">Notificación: todas</option>
      <?php foreach (['INMEDIATA', 'SEMANAL', 'MENSUAL'] as $t): ?><option value="<?= $t ?>"><?= e(etiquetaTipoNotif($t)) ?></option><?php endforeach; ?>
    </select></div>
    <div class="filtro-caja"><select data-filtro="familia" aria-label="Familia" data-nosearch="true">
      <option value="">Todas las familias</option>
      <?php foreach ($familias as $i => $f): ?><option value="f<?= $i ?>"><?= e($f) ?></option><?php endforeach; ?>
    </select></div>
    <div class="seg" role="group" aria-label="Estado">
      <button type="button" data-estado-btn="A">Activas · <span>0</span></button>
      <button type="button" data-estado-btn="I">Inactivas · <span>0</span></button>
    </div>
  </div>
  <div class="cat-desplazable">
    <table class="cat-tabla">
      <thead><tr>
        <th data-orden="nombre">Enfermedad</th>
        <th data-orden="cie">CIE-10</th>
        <th data-orden="notif">Notificación</th>
        <th data-orden="familia" class="cat-ocultar-m">Familia</th>
        <th data-orden="hist" data-num class="cat-ocultar-s">Corredor endémico</th>
        <th data-orden="anio" data-num class="n">Casos <?= $actual['anio'] ?></th>
        <th data-orden="se" data-num class="n cat-ocultar-s">SE <?= $actual['semana'] ?></th>
        <th style="width:44px" aria-label="Acciones"></th>
      </tr></thead>
      <tbody>
        <?php foreach ($enfermedades as $enf):
            $id = (int) $enf['id'];
            $activa = (bool) $enf['activo'];
            $c = $casos[$id] ?? ['anio' => 0, 'se' => 0, 'por_semana' => [], 'anios_historia' => []];
            $hist = count($c['anios_historia']);
            $secs = $secciones[$id] ?? [];
            $campos = array_sum(array_column($secs, 'campos'));
            $nombre = $enf['nombre_corto'] ?: $enf['nombre'];
            $familiaIdx = array_search($enf['familia'], $familias, true);
            $flags = [];
            if ($activa && $hist < $minimoAnios) $flags[] = 'sinhist';
            if ($activa && !$secs) $flags[] = 'sinficha';
            $urlFichas = '/casos?' . http_build_query(['pestana' => 'TODAS', 'periodo' => 'todo', 'enfermedad_id' => $id]);
            $barras = array_map(fn($s) => $c['por_semana'][$s['anio'] . '-' . $s['semana']] ?? 0, $semanas);
            $maximo = max(1, ...$barras);
            $accionAlternar = '/catalogos/enfermedades/' . $id . '/alternar';
            $confirmarAlternar = $activa
                ? '¿Desactivar ' . $nombre . '? Dejará de aparecer en Nueva ficha y en el cierre semanal. Sus fichas se conservan.' . ($enf['tipo_notif'] === 'INMEDIATA' ? "\n\nEs de notificación inmediata: confirma con Epidemiología que ya no se vigila por este sistema." : '')
                : '¿Reactivar ' . $nombre . '?';
            ?>
          <tr data-fila="<?= $id ?>" class="<?= $activa ? '' : 'inactiva' ?>" data-estado="<?= $activa ? 'A' : 'I' ?>" data-flags="<?= implode(' ', $flags) ?>"
              data-buscar="<?= e($enf['nombre'] . ' ' . $enf['nombre_corto'] . ' ' . $enf['cie10'] . ' ' . $enf['palabras_clave']) ?>"
              data-f-notif="<?= e($enf['tipo_notif']) ?>" data-f-familia="<?= $familiaIdx === false ? '' : 'f' . $familiaIdx ?>"
              data-o-nombre="<?= e($nombre) ?>" data-o-cie="<?= e($enf['cie10'] ?? '') ?>" data-o-notif="<?= e($enf['tipo_notif']) ?>" data-o-familia="<?= e($enf['familia'] ?? '') ?>"
              data-o-hist="<?= $hist ?>" data-o-anio="<?= $c['anio'] ?>" data-o-se="<?= $c['se'] ?>">
            <td><div class="pt-name" title="<?= e($enf['nombre']) ?>"><?= e($nombre) ?></div><?php if ($enf['nombre_corto'] && $enf['nombre_corto'] !== $enf['nombre']): ?><div class="cell-sub"><?= e($enf['nombre']) ?></div><?php endif; ?></td>
            <td class="cat-mono"><?= e($enf['cie10'] ?? '—') ?></td>
            <td><span class="cat-notif <?= e($enf['tipo_notif']) ?>"><i></i><?= e(etiquetaTipoNotif($enf['tipo_notif'])) ?></span></td>
            <td class="cat-ocultar-m"><?= e($enf['familia'] ?? '—') ?></td>
            <td class="cat-ocultar-s"><?= $hist >= $minimoAnios ? $hist . ' años' : '<span class="cell-sub" style="display:inline">Sin historia' . ($hist ? ' (' . $hist . ' año)' : '') . '</span>' ?></td>
            <td class="n cat-mono" style="font-weight:600<?= $c['anio'] ? '' : ';color:var(--muted);font-weight:400' ?>"><?= $c['anio'] ?></td>
            <td class="n cat-mono cat-ocultar-s" style="<?= $c['se'] ? 'font-weight:600' : 'color:var(--muted)' ?>"><?= $c['se'] ?></td>
            <td><button type="button" class="cat-acc" data-menu="<?= $id ?>" aria-haspopup="menu" aria-expanded="false" aria-label="Acciones de <?= e($nombre) ?>"><?= $ico['puntos'] ?></button></td>
          </tr>
          <?php ob_start(); ?>
          <template id="menu-<?= $id ?>">
            <button type="button" data-ver-detalle="<?= $id ?>">Ver detalle</button>
            <a href="/catalogos/enfermedades/<?= $id ?>/editar">Editar nombre, CIE-10 o notificación</a>
            <?php if ($activa && $secs): ?><a href="/casos/nuevo?enfermedad_id=<?= $id ?>">Abrir el formulario</a><?php endif; ?>
            <a href="<?= e($urlFichas) ?>">Ver sus fichas</a>
            <hr>
            <button type="button" class="<?= $activa ? 'peligro' : '' ?>" data-post="<?= e($accionAlternar) ?>" data-confirmar="<?= e($confirmarAlternar) ?>"><?= $activa ? 'Desactivar…' : 'Reactivar' ?></button>
          </template>
          <template id="det-<?= $id ?>">
            <div class="cat-cajon-cab">
              <div style="min-width:0"><h2><?= e($enf['nombre']) ?></h2><p class="cat-mono">CIE-10 · <?= e($enf['cie10'] ?? '—') ?></p>
                <div class="cat-etiquetas"><span class="cat-notif <?= e($enf['tipo_notif']) ?>"><i></i><?= e(etiquetaTipoNotif($enf['tipo_notif'])) ?></span><?php if ($enf['familia']): ?><span class="cat-badge"><?= e($enf['familia']) ?></span><?php endif; ?><?= $activa ? '' : '<span class="cat-badge"><i></i>Inactiva</span>' ?></div></div>
              <button type="button" class="cat-acc" data-cerrar-cajon aria-label="Cerrar"><?= $ico['x'] ?></button>
            </div>
            <div class="cat-cajon-cuerpo">
              <?php if (!$activa): ?>
                <div class="cat-sec"><div class="ns-aviso" style="margin:0"><?= $ico['alerta'] ?><div><b>Desactivada.</b> No aparece en Nueva ficha ni en el cierre semanal; sus fichas se conservan.</div></div></div>
              <?php endif; ?>
              <div class="cat-sec"><h3>Notificación<span class="r"><a href="/catalogos/enfermedades/<?= $id ?>/editar">Editar</a></span></h3>
                <div class="cat-kv">
                  <span>Tipo</span><b><?= e($descNotif[$enf['tipo_notif']] ?? $enf['tipo_notif']) ?></b>
                  <span>Familia</span><span><?= e($enf['familia'] ?? '—') ?></span>
                  <span>Grupo</span><span><?= e($enf['grupo'] ?? '—') ?></span>
                </div>
              </div>
              <div class="cat-sec"><h3>Corredor endémico</h3>
                <div class="cat-anios"><?php foreach ($aniosPrevios as $a): ?><span class="cat-anio <?= in_array($a, $c['anios_historia'], true) ? 'si' : 'no' ?>"><?= $a ?></span><?php endforeach; ?></div>
                <p><?= $hist >= $minimoAnios
                    ? 'Con ' . $hist . ' años de historia el Panel muestra el corredor endémico (éxito, seguridad, alarma y epidemia).'
                    : 'Hacen falta al menos ' . $minimoAnios . ' años con casos registrados. Mientras tanto, el Panel muestra la curva sin corredor.' ?></p>
              </div>
              <div class="cat-sec"><h3>Casos · SE <?= $primeraSe['semana'] ?>–<?= $ultimaSe['semana'] ?><span class="r"><a href="<?= e($urlFichas) ?>"><?= $c['anio'] ?> en <?= $actual['anio'] ?> · ver fichas</a></span></h3>
                <div class="cat-barras"><?php foreach ($barras as $i => $v): ?><i class="<?= $v ? '' : 'z' ?>" style="height:<?= $v ? max(10, round($v / $maximo * 100)) : 5 ?>%" title="SE <?= $semanas[$i]['semana'] ?>: <?= $v ?>"></i><?php endforeach; ?></div>
              </div>
              <div class="cat-sec"><h3>Ficha del formulario<span class="candado"><?= $ico['candado'] ?> la define el manifiesto</span><span class="r cat-mono"><?= count($secs) ?> secciones · <?= $campos ?> campos</span></h3>
                <?php if (!$secs): ?>
                  <div class="cat-aviso-c"><?= $ico['alerta'] ?><div><b>Sin ficha definida.</b> No tiene secciones en el manifiesto: el formulario quedaría vacío.</div></div>
                <?php else: ?>
                  <ol class="cat-secciones"><?php foreach ($secs as $s): ?><li><?= e($s['nombre']) ?><small><?= $s['campos'] ?></small></li><?php endforeach; ?></ol>
                  <p style="color:var(--muted)">Los cambios en la estructura se hacen en manifiesto_fichas.json, no desde esta pantalla.</p>
                <?php endif; ?>
              </div>
            </div>
            <div class="cat-cajon-pie">
              <?php if ($activa && $secs): ?><a class="btn btn-ghost btn-sm" href="/casos/nuevo?enfermedad_id=<?= $id ?>">Abrir el formulario</a><?php endif; ?>
              <a class="btn btn-ghost btn-sm" href="<?= e($urlFichas) ?>">Ver sus fichas</a>
              <span class="spacer"></span>
              <button type="button" class="btn <?= $activa ? 'btn-ghost' : 'btn-primary' ?> btn-sm"<?= $activa ? ' style="color:var(--s-confirmado)"' : '' ?> data-post="<?= e($accionAlternar) ?>" data-confirmar="<?= e($confirmarAlternar) ?>"><?= $activa ? 'Desactivar…' : 'Reactivar' ?></button>
            </div>
          </template>
          <?php $plantillas .= ob_get_clean(); ?>
        <?php endforeach; ?>
        <tr data-vacio hidden><td colspan="8" class="cat-vacio"><b>No hay enfermedades con estos filtros.</b> <button type="button" class="btn-enlace" data-limpiar>Limpiar filtros</button></td></tr>
      </tbody>
    </table>
  </div>
  <div class="cat-pie"><span data-contador></span><span>· Clic en una fila para ver el detalle</span></div>
</div>
<?= $plantillas ?>
<?php require __DIR__ . '/../../partials/catalogo-base.php';
