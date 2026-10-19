<?php
/**
 * Piezas comunes de los catálogos rediseñados (2026-10-19): panel lateral,
 * menú de acciones, formulario POST compartido (reactivar) y la ventana
 * "Desactivar con motivo". Ver public/js/catalogo.js.
 *
 * Variables: $desactivar = ['titulo' => ..., 'texto' => ..., 'placeholder' => ...] (opcional).
 */
use App\Core\Csrf;
?>
<div class="cat-cortina" id="catCortina" hidden></div>
<aside class="cat-cajon" id="catCajon" aria-label="Detalle" role="dialog"></aside>
<div class="cat-menu" id="catMenu" role="menu" hidden></div>
<form method="post" id="catFormPost" hidden><?= Csrf::campoOculto() ?></form>

<?php if (!empty($desactivar)): ?>
<div class="modal-fondo" id="modalDesactivar" hidden>
  <form class="modal-caja" method="post" role="dialog" aria-modal="true" aria-labelledby="modalDesactivarTitulo">
    <?= Csrf::campoOculto() ?>
    <div class="modal-cab"><h3 id="modalDesactivarTitulo"><?= e($desactivar['titulo']) ?> <span data-texto="nombre"></span></h3>
      <p><?= e($desactivar['texto']) ?></p></div>
    <div class="ns-pad-modal" data-bloque="aviso" hidden><div class="cat-aviso-c" style="margin-bottom:12px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg><div data-texto="aviso"></div></div></div>
    <div class="ns-campo ns-pad-modal"><label for="motivoDesactivar">Motivo <em>*</em></label>
      <textarea id="motivoDesactivar" name="motivo" maxlength="255" required placeholder="<?= e($desactivar['placeholder'] ?? '') ?>"></textarea></div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-peligro" data-requiere disabled>Desactivar</button></div>
  </form>
</div>
<?php endif; ?>
<script src="<?= e(asset('/js/catalogo.js')) ?>"></script>
