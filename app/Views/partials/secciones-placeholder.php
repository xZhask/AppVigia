<?php
/**
 * Las tres secciones que el mockup muestra colapsadas (se activan en fases
 * posteriores). Viven dentro del mismo contenedor recargable que las
 * secciones clínicas para que la numeración siga siendo correcta al cambiar
 * de enfermedad. Variable esperada: $numeroSeccion (entero, se incrementa).
 */
$placeholders = [
    ['titulo' => 'Antecedentes epidemiológicos', 'nota' => 'Viajes · contactos · lugar probable'],
    ['titulo' => 'Laboratorio', 'nota' => 'Muestras y resultados'],
    ['titulo' => 'Clasificación del caso', 'nota' => 'Sospechoso · probable · confirmado · descartado'],
];
?>
<?php foreach ($placeholders as $ph): ?>
  <div class="card section">
    <div class="section-head">
      <span class="section-num"><?= $numeroSeccion ?></span>
      <h3><?= e($ph['titulo']) ?></h3>
      <span class="btn-quiet" style="margin-left:auto;font-size:12px;color:var(--faint)"><?= e($ph['nota']) ?></span>
    </div>
  </div>
  <?php $numeroSeccion++; ?>
<?php endforeach; ?>
