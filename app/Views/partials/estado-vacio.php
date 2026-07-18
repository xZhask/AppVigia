<?php
/**
 * Variables esperadas: $icono (SVG inline), $mensaje, $accionTexto, $accionHref
 */
?>
<div class="card">
  <div class="section-body" style="text-align:center;padding:48px 24px">
    <div style="width:44px;height:44px;border-radius:12px;background:var(--accent-soft);color:var(--accent-deep);display:grid;place-items:center;margin:0 auto 16px">
      <?= $icono ?>
    </div>
    <p style="color:var(--muted);font-size:13.5px;max-width:420px;margin:0 auto 18px;line-height:1.55"><?= htmlspecialchars($mensaje) ?></p>
    <?php if (!empty($accionHref)): ?>
      <a class="btn btn-primary" href="<?= htmlspecialchars($accionHref) ?>">
        <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        <?= htmlspecialchars($accionTexto) ?>
      </a>
    <?php endif; ?>
  </div>
</div>
