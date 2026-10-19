<?php
use App\Models\Establecimiento;
use App\Models\NotificacionSemanal;
use App\Models\ResumenCatalogo;

// Catálogo de establecimientos (rediseño 2026-10-19). Ver EstablecimientosController::index.
$pestanas = [
    ['ruta' => 'catalogos/establecimientos', 'etiqueta' => 'Establecimientos'],
    ['ruta' => 'catalogos/redes',            'etiqueta' => 'Redes de salud'],
];
$etiquetasInstitucion = ['MINSA' => 'MINSA', 'ESSALUD' => 'EsSalud', 'FFAA_SANIDAD' => 'Sanidad PNP / FFAA', 'PRIVADO' => 'Privado'];
$categorias = array_values(array_unique(array_filter(array_column($establecimientos, 'categoria'))));
sort($categorias);
$primeraSe = $semanas[0];
$ultimaSe = end($semanas);
$hoy = new DateTimeImmutable('today');
$colorPct = fn(?int $p) => $p === null ? 'var(--line)' : ($p >= 80 ? 'var(--accent)' : ($p >= 60 ? '#C2622D' : 'var(--s-confirmado)'));
$ico = [
    'alerta' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg>',
    'reloj'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    'usuario'=> '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/></svg>',
    'puntos' => '<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="12" r="1.7" fill="currentColor"/><circle cx="12" cy="12" r="1.7" fill="currentColor"/><circle cx="19" cy="12" r="1.7" fill="currentColor"/></svg>',
    'x'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
];
$plantillas = '';
?>
<div class="page-head">
  <div>
    <div class="page-title">Establecimientos</div>
    <div class="page-desc">Padrón de IPRESS (RENIPRESS) · registradores y cumplimiento del cierre semanal</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-primary" href="/catalogos/establecimientos/nuevo">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    Nuevo establecimiento
  </a>
</div>

<?php require __DIR__ . '/../../partials/tabs-catalogo.php'; ?>

<div class="card cat-panel" data-catalogo>
  <div class="cat-alertas">
    <button type="button" class="cat-alerta" data-flag="">Todos <b>0</b></button>
    <button type="button" class="cat-alerta c" data-flag="sinreg"><?= $ico['alerta'] ?>Sin registrador <b>0</b></button>
    <button type="button" class="cat-alerta w" data-flag="baja"><?= $ico['reloj'] ?>Cumplimiento &lt; 60 % <b>0</b></button>
    <button type="button" class="cat-alerta w" data-flag="nunca"><?= $ico['usuario'] ?>Registrador que nunca ingresó <b>0</b></button>
  </div>
  <div class="cat-filtros">
    <div class="filtro-caja cat-buscar"><input type="search" data-filtro-q placeholder="Nombre, código RENIPRESS o ubicación" aria-label="Buscar establecimiento"></div>
    <div class="filtro-caja"><select data-filtro="red" aria-label="Red" data-nosearch="true">
      <option value="">Todas las redes</option>
      <?php foreach ($redes as $red): ?><option value="<?= (int) $red['id'] ?>"><?= e($red['nombre']) ?></option><?php endforeach; ?>
      <option value="sin">Sin red</option>
    </select></div>
    <div class="filtro-caja"><select data-filtro="cat" aria-label="Categoría" data-nosearch="true">
      <option value="">Todas las categorías</option>
      <?php foreach ($categorias as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?> · <?= e(tipoEstablecimientoPorCategoria($c) ?? '') ?></option><?php endforeach; ?>
      <option value="sin">Sin categoría</option>
    </select></div>
    <div class="seg" role="group" aria-label="Estado">
      <button type="button" data-estado-btn="A">Activos · <span>0</span></button>
      <button type="button" data-estado-btn="I">Inactivos · <span>0</span></button>
    </div>
  </div>
  <div class="cat-desplazable">
    <table class="cat-tabla">
      <thead><tr>
        <th data-orden="nombre">Establecimiento</th>
        <th data-orden="red">Red</th>
        <th data-orden="ubic" class="cat-ocultar-s">Ubicación</th>
        <th data-orden="cat" class="cat-ocultar-m">Cat.</th>
        <th data-orden="reg" data-num>Registradores</th>
        <th data-orden="pct" data-num class="cat-ocultar-s" title="Semanas cerradas (a tiempo o tarde) de las últimas <?= count($semanas) ?>, sin contar las de plazo vigente">Cumplimiento <?= count($semanas) ?> SE</th>
        <th data-orden="ult" class="cat-ocultar-m">Última notif.</th>
        <th style="width:44px" aria-label="Acciones"></th>
      </tr></thead>
      <tbody>
        <?php foreach ($establecimientos as $est):
            $id = (int) $est['id'];
            $activo = (bool) $est['activo'];
            $todos = $usuarios[$id] ?? [];
            $regs = array_values(array_filter($todos, fn($u) => $u['rol'] === 'REGISTRADOR' && $u['activo']));
            $nunca = count(array_filter($regs, fn($u) => $u['ultimo_acceso'] === null));
            $c = $cumplimiento[$id];
            $pct = $c['porcentaje'];
            $ultima = $ultimaNotif[$id] ?? null;
            $diasUltima = $ultima ? (int) $hoy->diff(new DateTimeImmutable($ultima))->days : null;
            $nombre = Establecimiento::nombreVisible($est);
            $ubicacion = $est['distrito_nombre'] ? nombreEnOracion($est['distrito_nombre']) : '';
            $region = $est['departamento_nombre'] ? nombreEnOracion($est['departamento_nombre']) : '';
            $flags = [];
            if ($activo && !$regs) $flags[] = 'sinreg';
            if ($activo && $pct !== null && $pct < 60) $flags[] = 'baja';
            if ($activo && $nunca) $flags[] = 'nunca';
            $buscar = implode(' ', [$est['nombre'], $est['nombre_corto'], $est['cod_renipress'], $est['red_nombre'], $est['distrito_nombre'], $est['provincia_nombre'], $est['departamento_nombre']]);
            $urlFichas = '/casos?' . http_build_query(['pestana' => 'TODAS', 'periodo' => 'todo', 'establecimiento_id' => $id]);
            $urlSemanal = '/notificacion-semanal?' . http_build_query(['q' => $est['nombre']]);
            $urlAsignar = '/catalogos/usuarios/nuevo?establecimiento_id=' . $id;
            $accionAlternar = '/catalogos/establecimientos/' . $id . '/alternar';
            $botonDesactivar = 'data-abrir-modal="modalDesactivar" data-accion="' . e($accionAlternar) . '" data-texto-nombre="' . e($nombre) . '"'
                . ($regs ? ' data-mostrar-aviso="1" data-texto-aviso="' . e('Tiene ' . count($regs) . ' registrador' . (count($regs) === 1 ? '' : 'es') . ' activo' . (count($regs) === 1 ? '' : 's') . '. Conviene transferirlo' . (count($regs) === 1 ? '' : 's') . ' a otro establecimiento desde Usuarios.') . '"' : ' data-mostrar-aviso="0"');
            $botonReactivar = 'data-post="' . e($accionAlternar) . '" data-confirmar="' . e('¿Reactivar ' . $nombre . '?') . '"';
            ?>
          <tr data-fila="<?= $id ?>" class="<?= $activo ? '' : 'inactiva' ?>" data-estado="<?= $activo ? 'A' : 'I' ?>" data-flags="<?= implode(' ', $flags) ?>"
              data-buscar="<?= e($buscar) ?>" data-f-red="<?= $est['red_id'] ? (int) $est['red_id'] : 'sin' ?>" data-f-cat="<?= e($est['categoria'] ?: 'sin') ?>"
              data-o-nombre="<?= e($nombre) ?>" data-o-red="<?= e($est['red_nombre'] ?? '') ?>" data-o-ubic="<?= e($region . ' ' . $ubicacion) ?>" data-o-cat="<?= e($est['categoria'] ?? '') ?>"
              data-o-reg="<?= count($regs) ?>" data-o-pct="<?= $pct ?? '' ?>" data-o-ult="<?= e($ultima ?? '') ?>">
            <td><div class="pt-name" title="<?= e($est['nombre']) ?>"><?= e($nombre) ?></div><div class="cell-sub cat-mono"><?= e($est['cod_renipress'] ?: 'Sin código RENIPRESS') ?></div></td>
            <td><?= $est['red_nombre'] ? e($est['red_nombre']) : '<span class="cat-viejo">Sin red</span>' ?></td>
            <td class="cat-ocultar-s"><?= e($ubicacion ?: '—') ?><?php if ($region): ?><div class="cell-sub"><?= e($region) ?></div><?php endif; ?></td>
            <td class="cat-ocultar-m"><?= $est['categoria'] ? '<span class="cat-cat">' . e($est['categoria']) . '</span>' : '<span class="cell-sub">—</span>' ?></td>
            <td><?php if (!$activo): ?><span class="cat-badge"><i></i>Inactivo</span>
              <?php elseif (!$regs): ?><span class="cat-sinreg"><?= $ico['alerta'] ?>Sin registrador</span>
              <?php else: ?><?= e(nombreEnOracion($regs[0]['nombre'])) ?><?= count($regs) > 1 ? ' <span class="cell-sub" style="display:inline">+' . (count($regs) - 1) . '</span>' : '' ?><?php if ($nunca): ?><div class="cell-sub cat-viejo"><?= $nunca === 1 && count($regs) === 1 ? 'Nunca ingresó' : $nunca . ' nunca ingresó' ?></div><?php endif; ?><?php endif; ?></td>
            <td class="cat-ocultar-s"><?php if ($activo): ?><div class="cat-medidor"><div class="m"><span style="width:<?= $pct ?? 0 ?>%;background:<?= $colorPct($pct) ?>"></span></div><b><?= $pct === null ? '—' : $pct . ' %' ?></b></div><?php else: ?>—<?php endif; ?></td>
            <td class="cat-ocultar-m<?= $diasUltima !== null && $diasUltima > 60 ? ' cat-viejo' : '' ?>"><?= $ultima ? e(fechaIsoADmy($ultima)) : '<span class="cell-sub">Nunca</span>' ?></td>
            <td><button type="button" class="cat-acc" data-menu="<?= $id ?>" aria-haspopup="menu" aria-expanded="false" aria-label="Acciones de <?= e($nombre) ?>"><?= $ico['puntos'] ?></button></td>
          </tr>
          <?php ob_start(); ?>
          <template id="menu-<?= $id ?>">
            <button type="button" data-ver-detalle="<?= $id ?>">Ver detalle</button>
            <a href="/catalogos/establecimientos/<?= $id ?>/editar">Editar datos</a>
            <?php if ($activo): ?><a href="<?= e($urlAsignar) ?>">Asignar registrador</a><?php endif; ?>
            <a href="<?= e($urlFichas) ?>">Ver sus fichas</a>
            <?php if ($activo): ?><a href="<?= e($urlSemanal) ?>">Ver en Notificación semanal</a><?php endif; ?>
            <hr>
            <?php if ($activo): ?><button type="button" class="peligro" <?= $botonDesactivar ?>>Desactivar…</button><?php else: ?><button type="button" <?= $botonReactivar ?>>Reactivar</button><?php endif; ?>
          </template>
          <template id="det-<?= $id ?>">
            <div class="cat-cajon-cab">
              <div style="min-width:0"><h2><?= e($nombre) ?></h2><p><?= e($est['nombre']) ?></p>
                <div class="cat-etiquetas">
                  <?php if ($est['categoria']): ?><span class="cat-badge"><?= e($est['categoria']) ?> · <?= e(tipoEstablecimientoPorCategoria($est['categoria']) ?? '') ?></span><?php endif; ?>
                  <span class="cat-badge"><?= e($est['red_nombre'] ?? 'Sin red') ?></span>
                  <?= $activo ? '<span class="cat-badge ok"><i></i>Activo</span>' : '<span class="cat-badge"><i></i>Inactivo</span>' ?>
                </div></div>
              <button type="button" class="cat-acc" data-cerrar-cajon aria-label="Cerrar"><?= $ico['x'] ?></button>
            </div>
            <div class="cat-cajon-cuerpo">
              <?php if (!$activo): ?>
                <div class="cat-sec"><div class="ns-aviso" style="margin:0"><?= $ico['alerta'] ?><div><b>Desactivado.</b> <?= e($est['motivo_inactivo'] ?: 'Sin motivo registrado.') ?> Ya no cuenta en la notificación semanal ni en el cumplimiento; sus fichas se conservan.</div></div></div>
              <?php endif; ?>
              <div class="cat-sec"><h3>Registradores<?php if ($activo): ?><span class="r"><a href="<?= e($urlAsignar) ?>">+ Asignar registrador</a></span><?php endif; ?></h3>
                <?php if (!$todos): ?>
                  <div class="cat-aviso-c"><?= $ico['alerta'] ?><div><b>Sin registrador.</b> Nadie puede registrar fichas ni cerrar la notificación semanal de este establecimiento.</div></div>
                <?php else: ?>
                  <ul class="cat-lista">
                    <?php foreach ($todos as $u): ?>
                      <li><span class="cat-av"><?= e(iniciales($u['nombre'])) ?></span>
                        <div><?= e(nombreEnOracion($u['nombre'])) ?><small><?= e($u['email']) ?> · <?= $u['rol'] === 'ADMIN' ? 'Administrador' : 'Registrador/a' ?><?= $u['activo'] ? '' : ' · inactivo' ?></small></div>
                        <span class="cuando<?= $u['ultimo_acceso'] === null ? ' cat-viejo' : '' ?>"><?= e(ResumenCatalogo::textoAcceso($u['ultimo_acceso'])) ?></span></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
              <?php if ($activo): ?>
                <div class="cat-sec"><h3>Cierre semanal · SE <?= $primeraSe['semana'] ?>–<?= $ultimaSe['semana'] ?><span class="r cat-mono" style="font-weight:600;color:<?= $colorPct($pct) ?>"><?= $pct === null ? '—' : $pct . ' %' ?></span></h3>
                  <div class="cat-tira"><?php foreach ($c['serie'] as $s): ?><div title="SE <?= $s['semana'] ?>: <?= e(NotificacionSemanal::ETIQUETAS[$s['estado']]) ?>"><i class="<?= $s['estado'] ?>"></i><?= $s['semana'] ?></div><?php endforeach; ?></div>
                  <div class="leyenda"><span><i class="celda T"></i>A tiempo</span><span><i class="celda L"></i>Tarde</span><span><i class="celda X"></i>No notificó</span><span><i class="celda P"></i>Plazo vigente</span></div>
                  <p>Última ficha notificada: <b><?= $ultima ? e(fechaIsoADmy($ultima)) : 'ninguna' ?></b>. <a href="<?= e($urlSemanal) ?>">Ver en Notificación semanal</a></p>
                </div>
              <?php endif; ?>
              <div class="cat-sec"><h3>Datos del establecimiento<span class="r"><a href="/catalogos/establecimientos/<?= $id ?>/editar">Editar</a></span></h3>
                <div class="cat-kv">
                  <span>Código RENIPRESS</span><b class="cat-mono"><?= e($est['cod_renipress'] ?: '—') ?></b>
                  <span>Nombre en el padrón</span><span><?= e($est['nombre']) ?></span>
                  <span>Nombre corto</span><span><?= e($est['nombre_corto'] ?: '— (se usa el del padrón)') ?></span>
                  <span>Institución</span><span><?= e($etiquetasInstitucion[$est['institucion']] ?? $est['institucion']) ?></span>
                  <span>Categoría</span><span><?= $est['categoria'] ? e($est['categoria'] . ' · ' . (tipoEstablecimientoPorCategoria($est['categoria']) ?? '')) : 'Sin categoría' ?></span>
                  <span>Red de salud</span><span><?= e($est['red_nombre'] ?? 'Sin red') ?></span>
                  <span>Ubicación</span><span><?= $est['distrito_nombre'] ? e(nombreEnOracion($est['distrito_nombre']) . ', ' . nombreEnOracion($est['provincia_nombre']) . ', ' . nombreEnOracion($est['departamento_nombre'])) : '—' ?></span>
                </div>
              </div>
            </div>
            <div class="cat-cajon-pie">
              <a class="btn btn-ghost btn-sm" href="<?= e($urlFichas) ?>">Ver sus fichas</a>
              <span class="spacer"></span>
              <?php if ($activo): ?><button type="button" class="btn btn-ghost btn-sm" style="color:var(--s-confirmado)" <?= $botonDesactivar ?>>Desactivar…</button><?php else: ?><button type="button" class="btn btn-primary btn-sm" <?= $botonReactivar ?>>Reactivar</button><?php endif; ?>
            </div>
          </template>
          <?php $plantillas .= ob_get_clean(); ?>
        <?php endforeach; ?>
        <tr data-vacio hidden><td colspan="8" class="cat-vacio"><b>No hay establecimientos con estos filtros.</b> <button type="button" class="btn-enlace" data-limpiar>Limpiar filtros</button></td></tr>
      </tbody>
    </table>
  </div>
  <div class="cat-pie"><span data-contador></span><span>· Clic en una fila para ver el detalle</span></div>
</div>
<?= $plantillas ?>
<?php
$desactivar = ['titulo' => 'Desactivar', 'texto' => 'Dejará de contar en la notificación semanal y en el cumplimiento. Sus fichas se conservan.', 'placeholder' => 'Ej.: cierre temporal, fusión con otro establecimiento'];
require __DIR__ . '/../../partials/catalogo-base.php';
