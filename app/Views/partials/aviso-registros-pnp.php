<?php
// Aviso de "Enfermedad: todas" (2026-11-24): cuántas atenciones de los
// registros PNP (otras enfermedades, febriles) quedan fuera de las cifras y
// un enlace por registro que las muestra. Espera $registrosPnp: lista de
// ['nombre', 'n', 'href'] (Caso::registrosPnpExcluidos más el enlace).
if (empty($registrosPnp)) {
    return;
}
$totalRegistrosPnp = array_sum(array_column($registrosPnp, 'n'));
?>
<div class="aviso-registros" role="note">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
  <div>
    <b>No incluye <?= number_format($totalRegistrosPnp, 0, ',', ' ') ?> atenci<?= $totalRegistrosPnp === 1 ? 'ón' : 'ones' ?> de registros PNP</b>, que no son casos de una enfermedad con ficha:
    <?php foreach (array_values($registrosPnp) as $i => $registro): ?><?= $i > 0 ? ' · ' : '' ?><a href="<?= e($registro['href']) ?>"><?= e($registro['nombre']) ?> (<?= number_format($registro['n'], 0, ',', ' ') ?>)</a><?php endforeach; ?>
  </div>
</div>
