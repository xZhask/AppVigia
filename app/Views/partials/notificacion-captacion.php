<?php
/**
 * Campos núcleo de captación del caso (AUDITORIA_FICHA_DIFTERIA.md, punto 2):
 * se repiten en casi todas las fichas MINSA, por eso van en la sección
 * núcleo "Notificación" y no en la definición de cada ficha. Distinta de
 * `caso.clasificacion` (la final, tras investigación/laboratorio).
 * Variable esperada: $valoresFijos con tipo_captacion, lugar_captacion,
 * clasificacion_captacion.
 */
?>
<div class="fields thirds" style="margin-top:14px">
  <div class="field">
    <label class="fl">Tipo de captación</label>
    <div class="control">
      <select name="tipo_captacion" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="ACTIVA" <?= seleccionado($valoresFijos['tipo_captacion'], 'ACTIVA') ?>>Activa</option>
        <option value="PASIVA" <?= seleccionado($valoresFijos['tipo_captacion'], 'PASIVA') ?>>Pasiva</option>
      </select>
    </div>
  </div>
  <div class="field">
    <label class="fl">Lugar de captación</label>
    <div class="control">
      <select name="lugar_captacion" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="INSTITUCIONAL" <?= seleccionado($valoresFijos['lugar_captacion'], 'INSTITUCIONAL') ?>>Institucional</option>
        <option value="COMUNIDAD" <?= seleccionado($valoresFijos['lugar_captacion'], 'COMUNIDAD') ?>>Comunidad</option>
      </select>
    </div>
  </div>
  <div class="field">
    <label class="fl">Clasificación en la captación</label>
    <div class="control">
      <select name="clasificacion_captacion" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="CONFIRMADO" <?= seleccionado($valoresFijos['clasificacion_captacion'], 'CONFIRMADO') ?>>Confirmado</option>
        <option value="PROBABLE" <?= seleccionado($valoresFijos['clasificacion_captacion'], 'PROBABLE') ?>>Probable</option>
        <option value="SOSPECHOSO" <?= seleccionado($valoresFijos['clasificacion_captacion'], 'SOSPECHOSO') ?>>Sospechoso</option>
      </select>
    </div>
  </div>
</div>
