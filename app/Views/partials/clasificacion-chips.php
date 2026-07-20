<?php
/**
 * Selector de clasificación del caso (chips seleccionables), único marcado
 * reutilizado por "Nueva ficha" y "Editar ficha" para que ambos queden
 * siempre idénticos. Variable esperada: $clasificacionActual.
 */
$opcionesClasificacion = [
    'SOSPECHOSO' => ['dot' => 'dot-sos', 'etiqueta' => 'Sospechoso'],
    'PROBABLE'   => ['dot' => 'dot-pro', 'etiqueta' => 'Probable'],
    'CONFIRMADO' => ['dot' => 'dot-con', 'etiqueta' => 'Confirmado'],
    'DESCARTADO' => ['dot' => 'dot-des', 'etiqueta' => 'Descartado'],
];
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
