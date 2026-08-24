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
// cotejo 2026-08-20, pág. 40 -- tampoco trae "Lugar de captación"; A95:
// cotejo 2026-08-22, pág. 26 -- mismo encabezado que B57, tampoco lo trae).
$cie10Captacion = $enfermedad['cie10'] ?? null;
$ocultaClasifCaptacion = in_array($cie10Captacion, ['B26', 'B01', 'B57', 'A95'], true);
// B57 (cotejo 2026-08-20, pág. 40 del PDF) y A95 (cotejo 2026-08-22, pág. 26
// -- mismo encabezado que B57): "Notificación Regular [ ] / Búsqueda Activa
// [ ]" mapea 1 a 1 con las 2 opciones que ya trae "Tipo de captación"
// (Pasiva=Regular, Activa=Búsqueda Activa) -- se conserva ese campo, pero
// "Lugar de captación" no tiene equivalente en ninguno de los 2 PDF: en su
// lugar pintan "Código" ("COGIGO" del encabezado, junto a las 4 fechas de
// conocimiento).
$FICHAS_CODIGO_EN_CAPTACION = ['B57', 'A95'];
$usaCodigoEnCaptacion = in_array($cie10Captacion, $FICHAS_CODIGO_EN_CAPTACION, true);

// {cie10}_codigo (B57: cotejo 2026-08-20, pedido del usuario; A95: cotejo
// 2026-08-22, mismo criterio): se pinta en el espacio que deja libre "Lugar
// de captación" -- mismo criterio de envolver en función que
// notificacion-fechas-a97.php, para no pisar la variable $campo del
// llamador (el resolvedor AMBIENTE de campos-por-clave.php).
$campoCodigoCaptacion = $usaCodigoEnCaptacion
    ? $resolvedorPara($cie10Captacion)(strtolower($cie10Captacion) . '_codigo')
    : null;
$pintarCodigoCaptacion = function () use ($campoCodigoCaptacion): void {
    if (!$campoCodigoCaptacion || !$campoCodigoCaptacion['id']) {
        return;
    }
    $campo = $campoCodigoCaptacion['campo'];
    $valor = $campoCodigoCaptacion['val'];
    $error = $campoCodigoCaptacion['err'];
    $opciones = $campoCodigoCaptacion['opciones'];
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
  <?php if ($usaCodigoEnCaptacion): ?>
    <?php $pintarCodigoCaptacion(); ?>
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
