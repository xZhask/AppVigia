<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Establecimiento;
use App\Models\ResumenCatalogo;

// Catálogo de usuarios (rediseño 2026-10-19). Ver UsuariosController::index.
$pestanas = [
    ['ruta' => 'catalogos/usuarios', 'etiqueta' => 'Usuarios'],
    ['ruta' => 'catalogos/grados',   'etiqueta' => 'Grados PNP'],
];
$roles = [
    'ADMIN'       => ['Administrador', 'Alcance nacional: catálogos, usuarios, reportes y seguimiento de la notificación semanal.'],
    'REGISTRADOR' => ['Registrador/a', 'Registra fichas y cierra la notificación semanal de su establecimiento.'],
];
$miId = (int) Auth::usuario()['id'];
$ico = [
    'usuario'=> '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/></svg>',
    'reloj'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    'alerta' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg>',
    'puntos' => '<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="12" r="1.7" fill="currentColor"/><circle cx="12" cy="12" r="1.7" fill="currentColor"/><circle cx="19" cy="12" r="1.7" fill="currentColor"/></svg>',
    'x'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
];
$plantillas = '';
?>
<div class="page-head">
  <div>
    <div class="page-title">Usuarios</div>
    <div class="page-desc">Cuentas personales con acceso al sistema · rol, establecimiento y actividad</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-primary" href="/catalogos/usuarios/nuevo">
    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    Nuevo usuario
  </a>
</div>

<?php require __DIR__ . '/../../partials/tabs-catalogo.php'; ?>

<div class="card cat-panel" data-catalogo>
  <div class="cat-alertas">
    <button type="button" class="cat-alerta" data-flag="">Todos <b>0</b></button>
    <button type="button" class="cat-alerta c" data-flag="nunca"><?= $ico['usuario'] ?>Nunca ingresaron <b>0</b></button>
    <button type="button" class="cat-alerta w" data-flag="d90"><?= $ico['reloj'] ?>Sin acceso &gt; <?= ResumenCatalogo::DIAS_SIN_ACCESO ?> días <b>0</b></button>
    <button type="button" class="cat-alerta w" data-flag="estinact"><?= $ico['alerta'] ?>En establecimiento inactivo <b>0</b></button>
  </div>
  <div class="cat-filtros">
    <div class="filtro-caja cat-buscar"><input type="search" data-filtro-q placeholder="Nombre, documento o correo" aria-label="Buscar usuario"></div>
    <div class="filtro-caja"><select data-filtro="rol" aria-label="Rol" data-nosearch="true">
      <option value="">Todos los roles</option>
      <?php foreach ($roles as $clave => [$etiqueta]): ?><option value="<?= $clave ?>"><?= e($etiqueta) ?></option><?php endforeach; ?>
    </select></div>
    <div class="filtro-caja"><select data-filtro="red" aria-label="Red" data-nosearch="true">
      <option value="">Todas las redes</option>
      <?php foreach ($redes as $red): ?><option value="<?= (int) $red['id'] ?>"><?= e($red['nombre']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="seg" role="group" aria-label="Estado">
      <button type="button" data-estado-btn="A">Activos · <span>0</span></button>
      <button type="button" data-estado-btn="I">Inactivos · <span>0</span></button>
    </div>
  </div>
  <div class="cat-desplazable">
    <table class="cat-tabla">
      <thead><tr>
        <th data-orden="nombre">Usuario</th>
        <th data-orden="rol">Rol</th>
        <th data-orden="alc">Establecimiento</th>
        <th data-orden="acc" data-num>Último acceso</th>
        <th data-orden="fichas" data-num class="n cat-ocultar-s">Fichas <?= $anio ?></th>
        <th style="width:44px" aria-label="Acciones"></th>
      </tr></thead>
      <tbody>
        <?php foreach ($usuarios as $u):
            $id = (int) $u['id'];
            $activo = (bool) $u['activo'];
            $soyYo = $id === $miId;
            $nombre = nombreEnOracion($u['nombre']);
            $dias = ResumenCatalogo::diasSinAcceso($u['ultimo_acceso']);
            $esReg = $u['rol'] === 'REGISTRADOR';
            $estVisible = $u['establecimiento_id'] ? Establecimiento::nombreVisible(['nombre' => $u['establecimiento_nombre'], 'nombre_corto' => $u['establecimiento_corto']]) : null;
            $n = $fichas[$id] ?? 0;
            $flags = [];
            if ($activo && $dias === null) $flags[] = 'nunca';
            if ($activo && $dias !== null && $dias > ResumenCatalogo::DIAS_SIN_ACCESO) $flags[] = 'd90';
            if ($activo && $u['establecimiento_id'] && !$u['establecimiento_activo']) $flags[] = 'estinact';
            $documento = $u['persona_num_doc'] ? documentoParaMostrar($u['persona_tipo_doc'], $u['persona_num_doc']) : '';
            $ultimoDeSuEst = $esReg && $activo && $u['establecimiento_id'] && ($registradoresPorEst[(int) $u['establecimiento_id']] ?? 0) === 1;
            $accionAlternar = '/catalogos/usuarios/' . $id . '/alternar';
            $botonDesactivar = 'data-abrir-modal="modalDesactivar" data-accion="' . e($accionAlternar) . '" data-texto-nombre="' . e($nombre) . '"'
                . ($ultimoDeSuEst ? ' data-mostrar-aviso="1" data-texto-aviso="' . e($estVisible . ' quedará sin registrador activo.') . '"' : ' data-mostrar-aviso="0"');
            $botonReactivar = 'data-post="' . e($accionAlternar) . '" data-confirmar="' . e('¿Reactivar a ' . $nombre . '?') . '"';
            $botonTransferir = 'data-abrir-modal="modalTransferir" data-accion="/catalogos/usuarios/' . $id . '/transferir" data-texto-nombre="' . e($nombre) . '" data-texto-origen="' . e($estVisible ?? '') . '"'
                . ($ultimoDeSuEst ? ' data-mostrar-aviso="1" data-texto-aviso="' . e($estVisible . ' quedará sin registrador activo.') . '"' : ' data-mostrar-aviso="0"');
            ?>
          <tr data-fila="<?= $id ?>" class="<?= $activo ? '' : 'inactiva' ?>" data-estado="<?= $activo ? 'A' : 'I' ?>" data-flags="<?= implode(' ', $flags) ?>"
              data-buscar="<?= e($u['nombre'] . ' ' . $u['email'] . ' ' . ($u['persona_num_doc'] ?? '') . ' ' . ($u['establecimiento_nombre'] ?? '')) ?>"
              data-f-rol="<?= e($u['rol']) ?>" data-f-red="<?= (int) ($u['red_id'] ?? 0) ?>"
              data-o-nombre="<?= e($u['nombre']) ?>" data-o-rol="<?= e($roles[$u['rol']][0] ?? $u['rol']) ?>" data-o-alc="<?= e($estVisible ?? 'Nacional') ?>"
              data-o-acc="<?= $dias === null ? 99999 : $dias ?>" data-o-fichas="<?= $esReg ? $n : '' ?>">
            <td><div class="pt-name"><?= e($nombre) ?><?= $soyYo ? ' <span class="cell-sub" style="display:inline">(tú)</span>' : '' ?></div><div class="cell-sub cat-mono"><?= e($documento ?: 'Sin documento: entra con su correo') ?></div></td>
            <td><span class="cat-rol <?= e($u['rol']) ?>"><?= e($roles[$u['rol']][0] ?? $u['rol']) ?></span></td>
            <td><?php if ($esReg): ?><?= e($estVisible ?? '—') ?><div class="cell-sub"><?= e($u['red_nombre'] ?? '') ?><?= $u['establecimiento_id'] && !$u['establecimiento_activo'] ? ' · <span class="cat-viejo">establecimiento inactivo</span>' : '' ?></div><?php else: ?>Nacional<div class="cell-sub">Todas las redes</div><?php endif; ?></td>
            <td class="<?= $activo && ($dias === null || $dias > ResumenCatalogo::DIAS_SIN_ACCESO) ? 'cat-viejo' : '' ?>"><?= e(ResumenCatalogo::textoAcceso($u['ultimo_acceso'])) ?></td>
            <td class="n cat-mono cat-ocultar-s"><?= $esReg ? $n : '—' ?></td>
            <td><button type="button" class="cat-acc" data-menu="<?= $id ?>" aria-haspopup="menu" aria-expanded="false" aria-label="Acciones de <?= e($nombre) ?>"><?= $ico['puntos'] ?></button></td>
          </tr>
          <?php ob_start(); ?>
          <template id="menu-<?= $id ?>">
            <button type="button" data-ver-detalle="<?= $id ?>">Ver detalle</button>
            <a href="/catalogos/usuarios/<?= $id ?>/editar">Editar datos, rol o contraseña</a>
            <?php if ($esReg && $activo): ?><button type="button" <?= $botonTransferir ?>>Transferir a otro establecimiento…</button><?php endif; ?>
            <?php if (!$soyYo): ?><hr><?= $activo ? '<button type="button" class="peligro" ' . $botonDesactivar . '>Desactivar…</button>' : '<button type="button" ' . $botonReactivar . '>Reactivar</button>' ?><?php endif; ?>
          </template>
          <template id="det-<?= $id ?>">
            <div class="cat-cajon-cab">
              <div style="min-width:0"><h2><?= e($nombre) ?></h2><p><?= e($documento ?: ($u['email'] ?? '')) ?></p>
                <div class="cat-etiquetas"><span class="cat-rol <?= e($u['rol']) ?>"><?= e($roles[$u['rol']][0] ?? $u['rol']) ?></span><?= $activo ? '<span class="cat-badge ok"><i></i>Activo</span>' : '<span class="cat-badge"><i></i>Inactivo</span>' ?><?= $soyYo ? '<span class="cat-badge">Tu cuenta</span>' : '' ?></div></div>
              <button type="button" class="cat-acc" data-cerrar-cajon aria-label="Cerrar"><?= $ico['x'] ?></button>
            </div>
            <div class="cat-cajon-cuerpo">
              <?php if (!$activo): ?>
                <div class="cat-sec"><div class="ns-aviso" style="margin:0"><?= $ico['alerta'] ?><div><b>Desactivado.</b> <?= e($u['motivo_inactivo'] ?: 'Sin motivo registrado.') ?> No puede ingresar; sus fichas se conservan.</div></div></div>
              <?php endif; ?>
              <div class="cat-sec"><h3>Rol y establecimiento<span class="r"><a href="/catalogos/usuarios/<?= $id ?>/editar">Cambiar</a></span></h3>
                <div class="cat-kv">
                  <span>Rol</span><b><?= e($roles[$u['rol']][0] ?? $u['rol']) ?></b>
                  <span>Puede</span><span><?= e($roles[$u['rol']][1] ?? '') ?></span>
                  <span>Establecimiento</span><span><?= $esReg ? e(($estVisible ?? '—') . ($u['red_nombre'] ? ' · ' . $u['red_nombre'] : '')) : 'Nacional' ?></span>
                </div>
              </div>
              <div class="cat-sec"><h3>Datos de la persona</h3>
                <div class="cat-kv">
                  <span>Documento</span><b class="cat-mono"><?= e($documento ?: '—') ?></b>
                  <span>Grado</span><span><?= e($u['grado_nombre'] ?? '— (civil o sin grado)') ?></span>
                  <?php if (!empty($u['cip'])): ?><span>CIP</span><span class="cat-mono"><?= e($u['cip']) ?></span><?php endif; ?>
                  <span>Correo</span><span><?= e($u['email'] ?: '— (opcional)') ?></span>
                  <span>Último acceso</span><span class="<?= $u['ultimo_acceso'] === null ? 'cat-viejo' : '' ?>"><?= e(ResumenCatalogo::textoAcceso($u['ultimo_acceso'])) ?></span>
                  <span>Cuenta creada</span><span><?= e(date('d/m/Y', strtotime($u['creado_en']))) ?></span>
                </div>
              </div>
              <div class="cat-sec"><h3>Actividad reciente</h3>
                <?php if (empty($actividad[$id])): ?>
                  <p style="margin:0;color:var(--muted)">Sin actividad registrada.</p>
                <?php else: ?>
                  <ul class="cat-lista"><?php foreach ($actividad[$id] as $ev): ?><li><span><?= e($ev['texto']) ?></span><span class="cuando cat-mono"><?= e(date('d/m · H:i', strtotime($ev['fecha']))) ?></span></li><?php endforeach; ?></ul>
                <?php endif; ?>
                <?php if ($esReg): ?><p><b class="cat-mono"><?= $n ?></b> ficha<?= $n === 1 ? '' : 's' ?> registrada<?= $n === 1 ? '' : 's' ?> en <?= $anio ?>.</p><?php endif; ?>
              </div>
            </div>
            <div class="cat-cajon-pie">
              <a class="btn btn-ghost btn-sm" href="/catalogos/usuarios/<?= $id ?>/editar">Editar</a>
              <?php if ($esReg && $activo): ?><button type="button" class="btn btn-ghost btn-sm" <?= $botonTransferir ?>>Transferir…</button><?php endif; ?>
              <span class="spacer"></span>
              <?php if (!$soyYo): ?><?= $activo ? '<button type="button" class="btn btn-ghost btn-sm" style="color:var(--s-confirmado)" ' . $botonDesactivar . '>Desactivar…</button>' : '<button type="button" class="btn btn-primary btn-sm" ' . $botonReactivar . '>Reactivar</button>' ?><?php endif; ?>
            </div>
          </template>
          <?php $plantillas .= ob_get_clean(); ?>
        <?php endforeach; ?>
        <tr data-vacio hidden><td colspan="6" class="cat-vacio"><b>No hay usuarios con estos filtros.</b> <button type="button" class="btn-enlace" data-limpiar>Limpiar filtros</button></td></tr>
      </tbody>
    </table>
  </div>
  <div class="cat-pie"><span data-contador></span><span>· Clic en una fila para ver el detalle</span></div>
</div>
<?= $plantillas ?>

<div class="modal-fondo" id="modalTransferir" hidden>
  <form class="modal-caja" method="post" role="dialog" aria-modal="true" aria-labelledby="modalTransferirTitulo">
    <?= Csrf::campoOculto() ?>
    <div class="modal-cab"><h3 id="modalTransferirTitulo">Transferir a <span data-texto="nombre"></span></h3>
      <p>Para cambios de colocación. Conserva su cuenta e historial; las fichas que ya registró siguen en <span data-texto="origen"></span>.</p></div>
    <div class="ns-pad-modal" data-bloque="aviso" hidden><div class="ns-aviso"><?= $ico['alerta'] ?><div data-texto="aviso"></div></div></div>
    <div class="ns-campo ns-pad-modal cat-campo-sel"><label for="destinoTransferir">Nuevo establecimiento <em>*</em></label>
      <select id="destinoTransferir" name="establecimiento_id" required>
        <option value="">Seleccionar…</option>
        <?php foreach ($establecimientos as $est): ?><option value="<?= (int) $est['id'] ?>"><?= e(Establecimiento::nombreVisible($est)) ?><?= $est['red_nombre'] ? ' · ' . e($est['red_nombre']) : '' ?></option><?php endforeach; ?>
      </select></div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-primary">Transferir</button></div>
  </form>
</div>
<?php
$desactivar = ['titulo' => 'Desactivar a', 'texto' => 'No podrá ingresar al sistema. Sus fichas y su actividad se conservan.', 'placeholder' => 'Ej.: cambio de colocación, pase al retiro, licencia prolongada'];
require __DIR__ . '/../../partials/catalogo-base.php';
