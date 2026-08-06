<?php
/**
 * Selector de clasificación del caso (chips seleccionables), único marcado
 * reutilizado por "Nueva ficha" y "Editar ficha" para que ambos queden
 * siempre idénticos. Variables esperadas: $clasificacionActual, $enfermedad.
 *
 * Las opciones y su etiqueta/color salen de CATALOGO_CLASIFICACION
 * (ayudantes.php); cada ficha filtra/ordena su subconjunto vía
 * enfermedad.opciones_clasificacion (CSV; NULL = las 4 genéricas) —
 * ej. difteria solo admite Confirmado/Descartado (AUDITORIA_FICHA_DIFTERIA.md).
 */
$esO95Chips = (($enfermedad['cie10'] ?? '') === 'O95');
$valoresPermitidosClasif = opcionesClasificacionPara($enfermedad);
$opcionesClasificacion = array_intersect_key(CATALOGO_CLASIFICACION, array_flip($valoresPermitidosClasif));

if ($esO95Chips) {
    // Este partial se incluye sin condición aunque O95 no sea la ficha
    // activa (mismo motivo que notificacion-fechas-b26.php): $campo
    // resuelve siempre contra la O95 real, no contra $enfermedad.
    $campoO95Chips = $resolvedorPara('O95');
    $valCausaClasif = $campoO95Chips('o95_clasificacion_final_de_la_muerte')['val'];
    if ($valCausaClasif === '') {
        $valCausaClasif = $campoO95Chips('o95_clasificacion_inicial')['val'];
    }
    if ($valCausaClasif && in_array($valCausaClasif, $valoresPermitidosClasif, true)) {
        $clasificacionActual = $valCausaClasif;
    } elseif (!in_array($clasificacionActual, $valoresPermitidosClasif, true)) {
        $clasificacionActual = 'POR_DETERMINAR';
    }
}
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
