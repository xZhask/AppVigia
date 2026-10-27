<?php
use App\Core\Csrf;
use App\Core\RegistroErrores;
use App\Models\ErrorRegistro;

// Una ocurrencia de un error y las demás del mismo grupo (2026-10-27).
// Ver ErroresController::ver.
$codigo = RegistroErrores::codigo((int) $error['id']);
$esJs = $error['origen'] === 'JS';
$fecha = fn(?string $f) => $f ? date('d/m/Y H:i:s', strtotime($f)) : '—';
$pendientesGrupo = count(array_filter($ocurrencias, fn($o) => $o['resuelto_en'] === null));
?>
<a class="err-volver" href="/sistema/errores">
  <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M8.5 3 4.5 7l4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
  Errores del sistema
</a>
<div class="page-head">
  <div>
    <div class="page-title">Error <span class="mono"><?= e($codigo) ?></span></div>
    <div class="page-desc"><?= $esJs ? 'Navegador' : 'Servidor' ?> · <?= e(ErrorRegistro::NIVELES[$error['nivel']] ?? $error['nivel']) ?> · <?= e($fecha($error['creado_en'])) ?></div>
  </div>
  <div class="spacer"></div>
  <?php if ($pendientesGrupo > 0): ?>
    <form method="post" action="/sistema/errores/<?= (int) $error['id'] ?>/resolver">
      <?= Csrf::campoOculto() ?><input type="hidden" name="volver" value="detalle">
      <button type="submit" class="btn btn-primary">Marcar como resuelto</button>
    </form>
  <?php else: ?>
    <span class="cat-badge ok"><i></i>Resuelto<?= $error['resuelto_en'] ? ' el ' . e(date('d/m/Y', strtotime($error['resuelto_en']))) . ($error['resuelto_por_nombre'] ? ' por ' . e(nombreEnOracion($error['resuelto_por_nombre'])) : '') : '' ?></span>
  <?php endif; ?>
</div>

<div class="err-mensaje mono"><?= e($error['mensaje']) ?></div>

<div class="err-columnas">
  <section class="card err-caja">
    <div class="cat-sec"><h3>Quién y dónde</h3>
      <div class="cat-kv">
        <span>Usuario</span><span><?= $error['usuario_nombre'] ? '<b>' . e(nombreEnOracion($error['usuario_nombre'])) . '</b> · ' . e($error['usuario_email']) : 'Sin sesión' ?></span>
        <span>IPRESS</span><span><?= e($error['establecimiento_nombre'] ?? ($error['usuario_rol'] === 'ADMIN' ? 'Nacional (administrador)' : '—')) ?></span>
        <span>Pantalla</span><span class="cat-mono err-romper"><?= e(trim(($error['metodo'] ?? '') . ' ' . ($error['ruta'] ?? '—'))) ?></span>
        <?php if (!$esJs): ?><span>Campos enviados</span><span class="err-romper"><?= e($error['campos_enviados'] ?? '— (no envió un formulario)') ?></span><?php endif; ?>
        <span>Navegador</span><span class="err-romper"><?= e($error['navegador'] ?? '—') ?></span>
        <span>IP</span><span class="cat-mono"><?= e($error['ip'] ?? '—') ?></span>
      </div>
    </div>
  </section>
  <section class="card err-caja">
    <div class="cat-sec"><h3>En el código</h3>
      <div class="cat-kv">
        <span>Archivo</span><span class="cat-mono err-romper"><?= e($error['archivo'] ? $error['archivo'] . ($error['linea'] ? ':' . $error['linea'] : '') : '— (sin archivo: código ejecutado en la página)') ?></span>
        <span>Veces</span><span><?= $total ?> en total<?= $total > count($ocurrencias) ? ' (se listan las últimas ' . count($ocurrencias) . ')' : '' ?></span>
      </div>
      <?php if ($error['traza']): ?>
        <details class="err-traza"<?= $esJs ? ' open' : '' ?>><summary>Traza</summary><pre><?= e($error['traza']) ?></pre></details>
      <?php endif; ?>
    </div>
  </section>
</div>

<section class="card cat-panel" style="margin-top:16px">
  <div class="tarjeta-cab" style="padding:16px 18px 0"><div><h3 style="margin:0">Ocurrencias del mismo error</h3><div class="sub">La más reciente primero</div></div></div>
  <div class="cat-desplazable" style="margin-top:12px">
    <table class="cat-tabla">
      <thead><tr><th>Código</th><th>Fecha</th><th>Usuario</th><th class="cat-ocultar-s">IPRESS</th><th class="cat-ocultar-s">Pantalla</th><th>Estado</th></tr></thead>
      <tbody>
        <?php foreach ($ocurrencias as $o): $actual = (int) $o['id'] === (int) $error['id']; ?>
          <tr class="<?= $actual ? 'sel' : '' ?>">
            <td class="cat-mono"><?= $actual ? e(RegistroErrores::codigo((int) $o['id'])) : '<a href="/sistema/errores/' . (int) $o['id'] . '">' . e(RegistroErrores::codigo((int) $o['id'])) . '</a>' ?></td>
            <td><?= e($fecha($o['creado_en'])) ?></td>
            <td><?= e($o['usuario_nombre'] ? nombreEnOracion($o['usuario_nombre']) : 'Sin sesión') ?></td>
            <td class="cat-ocultar-s"><?= e($o['establecimiento_nombre'] ?? '—') ?></td>
            <td class="cat-ocultar-s cat-mono err-ruta" title="<?= e($o['ruta'] ?? '') ?>"><?= e($o['ruta'] ?? '—') ?></td>
            <td><?= $o['resuelto_en'] ? '<span class="cat-badge ok"><i></i>Resuelto</span>' : '<span class="cat-badge w"><i></i>Sin resolver</span>' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
