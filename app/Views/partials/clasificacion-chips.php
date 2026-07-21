<?php
/**
 * Selector de clasificación del caso (chips seleccionables), único marcado
 * reutilizado por "Nueva ficha" y "Editar ficha" para que ambos queden
 * siempre idénticos. Variables esperadas: $clasificacionActual, $enfermedad.
 *
 * Cada ficha puede restringir las 4 opciones genéricas a un subconjunto
 * propio (enfermedad.opciones_clasificacion, CSV; NULL = las 4 genéricas) —
 * ej. difteria solo admite Confirmado/Descartado (AUDITORIA_FICHA_DIFTERIA.md).
 */
$opcionesClasificacion = [
    'SOSPECHOSO' => ['dot' => 'dot-sos', 'etiqueta' => 'Sospechoso'],
    'PROBABLE'   => ['dot' => 'dot-pro', 'etiqueta' => 'Probable'],
    'CONFIRMADO' => ['dot' => 'dot-con', 'etiqueta' => 'Confirmado'],
    'DESCARTADO' => ['dot' => 'dot-des', 'etiqueta' => 'Descartado'],
];
$opcionesClasificacion = array_intersect_key($opcionesClasificacion, array_flip(opcionesClasificacionPara($enfermedad)));
?>
<div class="field">
  <label class="fl">Clasificación del caso</label>
  <div class="chip-select" role="radiogroup" aria-label="Clasificación del caso">
    <?php foreach ($opcionesClasificacion as $valor => $op): ?>
      <label class="chip-option">
        <input type="radio" name="clasificacion" value="<?= $valor ?>" <?= marcado($clasificacionActual === $valor) ?>>
        <span class="chip"><span class="dot <?= $op['dot'] ?>"></span> <?= e($op['etiqueta']) ?></span>
      </label>
    <?php endforeach; ?>
  </div>
</div>
