<?php
/**
 * Bloque reusable de "detalle de domicilio" (tipo/nombre de zona, tipo/
 * nombre de vía, número, Mz./Lote) -- mismos 6 sub-campos para el domicilio
 * actual del núcleo (datos-paciente-nucleo.php, bloque "1b") y para
 * "Domicilio anterior" (Migración, cotejo B57), parametrizado por prefijo de
 * nombre de campo para no colisionar cuando ambos aparecen en el mismo
 * <form>. El permiso (qué sub-campos se muestran) se comparte entre los dos
 * bloques: mismo criterio que enfermedad.detalle_domicilio.
 *
 * Variables esperadas:
 *   $prefijoNombreCampo         string, '' (domicilio actual) o 'anterior_' (domicilio anterior)
 *   $valoresDetalle             array con claves SIN prefijo: tipo_zona, nombre_zona, tipo_via, nombre_via, numero, mz_lote
 *   $detalleDomicilioPermitido  array de claves permitidas (TIPO_ZONA, NOMBRE_ZONA, TIPO_VIA, NOMBRE_VIA, NUMERO, MZ_LOTE)
 */
$tieneDetalleDomicilioCampo = fn(string $campo): bool => in_array($campo, $detalleDomicilioPermitido, true);
?>
<div class="fields thirds" style="margin-top:14px" data-detalle-domicilio-bloque="<?= $prefijoNombreCampo === '' ? 'actual' : 'anterior' ?>">
  <div class="field" data-detalle-domicilio-campo="TIPO_ZONA" <?= $tieneDetalleDomicilioCampo('TIPO_ZONA') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Tipo de zona</label>
    <div class="control">
      <select name="<?= e($prefijoNombreCampo) ?>tipo_zona" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="URBANO" <?= seleccionado($valoresDetalle['tipo_zona'] ?? '', 'URBANO') ?>>Urbano</option>
        <option value="PERIURBANO" <?= seleccionado($valoresDetalle['tipo_zona'] ?? '', 'PERIURBANO') ?>>Periurbano</option>
        <option value="RURAL" <?= seleccionado($valoresDetalle['tipo_zona'] ?? '', 'RURAL') ?>>Rural</option>
      </select>
    </div>
  </div>
  <div class="field" data-detalle-domicilio-campo="NOMBRE_ZONA" <?= $tieneDetalleDomicilioCampo('NOMBRE_ZONA') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Nombre de zona</label>
    <div class="control"><input type="text" name="<?= e($prefijoNombreCampo) ?>nombre_zona" value="<?= e($valoresDetalle['nombre_zona'] ?? '') ?>"></div>
  </div>
  <div class="field" data-detalle-domicilio-campo="TIPO_VIA" <?= $tieneDetalleDomicilioCampo('TIPO_VIA') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Tipo de vía <span class="hint">(Avenida, Calle, Jirón, etc.)</span></label>
    <div class="control"><input type="text" name="<?= e($prefijoNombreCampo) ?>tipo_via" value="<?= e($valoresDetalle['tipo_via'] ?? '') ?>"></div>
  </div>
  <div class="field" data-detalle-domicilio-campo="NOMBRE_VIA" <?= $tieneDetalleDomicilioCampo('NOMBRE_VIA') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Nombre de vía</label>
    <div class="control"><input type="text" name="<?= e($prefijoNombreCampo) ?>nombre_via" value="<?= e($valoresDetalle['nombre_via'] ?? '') ?>"></div>
  </div>
  <div class="field" data-detalle-domicilio-campo="NUMERO" <?= $tieneDetalleDomicilioCampo('NUMERO') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Nro.</label>
    <div class="control"><input type="text" name="<?= e($prefijoNombreCampo) ?>numero" value="<?= e($valoresDetalle['numero'] ?? '') ?>"></div>
  </div>
  <div class="field" data-detalle-domicilio-campo="MZ_LOTE" <?= $tieneDetalleDomicilioCampo('MZ_LOTE') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Mz./Lote</label>
    <div class="control"><input type="text" name="<?= e($prefijoNombreCampo) ?>mz_lote" value="<?= e($valoresDetalle['mz_lote'] ?? '') ?>"></div>
  </div>
</div>
