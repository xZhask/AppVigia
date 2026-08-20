<?php
/**
 * Fila dinámica de exámenes auxiliares del caso (caso_examen_auxiliar),
 * propia de A44 (Enfermedad de Carrión, pág. 43 del PDF: tabla "EXAMEN
 * AUXILIAR" por fecha). Variable esperada: $filasExamen. Todos los valores
 * son texto libre (no todos los resultados de laboratorio son numéricos
 * puros -- titulaciones, "No reactivo", etc.) -- mismo criterio que
 * agente_aislado/titulacion en muestras.php.
 */
$erroresExamen = $erroresExamen ?? [];

$camposExamen = [
    'grupo_sanguineo'       => 'Grupo sanguíneo',
    'plaquetas'             => 'Plaquetas',
    'hematies'              => 'Hematíes',
    'tgo'                   => 'TGO',
    'tgp'                   => 'TGP',
    'fosfatasa_alcalina'    => 'Fosfatasa alcalina',
    'bilirrubina_directa'   => 'Bilirrubina directa',
    'bilirrubina_indirecta' => 'Bilirrubina indirecta',
    'bilirrubina_total'     => 'Bilirrubina total',
    'urea'                  => 'Urea',
    'glucosa'               => 'Glucosa',
    'creatinina'            => 'Creatinina',
    'leucocitos_totales'    => 'Leucocitos totales',
    'segmentados'           => 'Segmentados',
    'abastonados'           => 'Abastonados',
    'linfocitos'            => 'Linfocitos',
    'monocitos'             => 'Monocitos',
    'eosinofilos'           => 'Eosinófilos',
    'basofilos'             => 'Basófilos',
    'blastos'               => 'Blastos',
    'aglutinacion_tifico_o' => 'Aglutinación: Tífico "O"',
    'aglutinacion_tifico_h' => 'Aglutinación: Tífico "H"',
    'paratifico_a'          => 'Paratífico A',
    'paratifico_b'          => 'Paratífico B',
    'brucellas'             => 'Brucellas',
];

$filaExamen = function (array $fila = [], ?array $error = null) use ($camposExamen): void {
    $errorFecha = $error['fecha'] ?? null;
    ?>
  <div class="subrow" style="border:1px solid var(--line-2); border-radius:10px; padding:14px; margin-bottom:14px; width:100%">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px">
      <div class="fields thirds" style="flex:1">
        <div class="field">
          <label class="fl">Fecha</label>
          <div class="control mono <?= $errorFecha ? 'err' : '' ?>"><input type="date" name="examen_fecha[]" value="<?= e($fila['fecha'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
          <?php if ($errorFecha): ?><span class="hint err"><?= e($errorFecha) ?></span><?php endif; ?>
        </div>
        <?php foreach ($camposExamen as $col => $etiqueta): ?>
        <div class="field">
          <label class="fl"><?= e($etiqueta) ?></label>
          <div class="control"><input type="text" name="examen_<?= $col ?>[]" value="<?= e($fila[$col] ?? '') ?>"></div>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="ra quitar-fila" title="Quitar registro">
        <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      </button>
    </div>
  </div>
<?php };
?>
<div class="subrows" data-lista="examenes">
  <?php foreach ($filasExamen as $i => $fila): $filaExamen($fila, $erroresExamen[$i] ?? null); endforeach; ?>
</div>
<template id="plantilla-examenes"><?php $filaExamen(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-examenes" data-lista="examenes" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar examen
</button>
