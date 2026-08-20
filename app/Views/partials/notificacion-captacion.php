<?php
/**
 * Campos núcleo de captación del caso (AUDITORIA_FICHA_DIFTERIA.md, punto 2):
 * se repiten en casi todas las fichas MINSA, por eso van en la sección
 * núcleo "Notificación" y no en la definición de cada ficha. Distinta de
 * `caso.clasificacion` (la final, tras investigación/laboratorio).
 * Variable esperada: $valoresFijos con tipo_captacion, lugar_captacion,
 * clasificacion_captacion.
 */
// Fichas cuyo PDF no trae "Clasificación en la captación" junto a
// Tipo/Lugar de captación (B26: cotejo 2026-07-30; B01: cotejo 2026-08-08,
// pág. 3 del PDF -- solo trae ítems 5 y 6, Tipo de captación y Lugar; B57:
// cotejo 2026-08-20, pág. 40 -- tampoco trae "Lugar de captación").
$ocultaClasifCaptacion = in_array($enfermedad['cie10'] ?? null, ['B26', 'B01', 'B57'], true);
// B57 (cotejo 2026-08-20, pág. 40 del PDF): "Notificación Regular [ ] /
// Búsqueda Activa [ ]" mapea 1 a 1 con las 2 opciones que ya trae "Tipo de
// captación" (Pasiva=Regular, Activa=Búsqueda Activa) -- se conserva ese
// campo, pero "Lugar de captación" no tiene equivalente en el PDF.
$esB57Captacion = (($enfermedad['cie10'] ?? null) === 'B57');

// b57_codigo (cotejo 2026-08-20, pedido del usuario): "CÓDIGO" del
// encabezado del PDF (pág. 40, junto a las 4 fechas de conocimiento) se
// pinta en el espacio que deja libre "Lugar de captación" -- mismo criterio
// de envolver en función que notificacion-fechas-a97.php, para no pisar la
// variable $campo del llamador (el resolvedor AMBIENTE de campos-por-clave.php).
$campoCodigoB57 = $esB57Captacion ? $resolvedorPara('B57')('b57_codigo') : null;
$pintarCodigoB57 = function () use ($campoCodigoB57): void {
    if (!$campoCodigoB57 || !$campoCodigoB57['id']) {
        return;
    }
    $campo = $campoCodigoB57['campo'];
    $valor = $campoCodigoB57['val'];
    $error = $campoCodigoB57['err'];
    $opciones = $campoCodigoB57['opciones'];
    require __DIR__ . '/campo-dinamico.php';
};
?>
<div class="capt-grid <?= $ocultaClasifCaptacion ? 'fields halves' : 'fields thirds' ?>" style="margin-top:14px">
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
  <?php if ($esB57Captacion): ?>
    <?php $pintarCodigoB57(); ?>
  <?php else: ?>
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
  <?php endif; ?>
  <div class="field clasif-captacion-hide" <?= $ocultaClasifCaptacion ? 'hidden style="display:none;"' : '' ?>>
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
