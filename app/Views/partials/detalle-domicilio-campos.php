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
 *   $valoresDetalle             array con claves SIN prefijo: tipo_zona, tipo_asentamiento, nombre_zona, tipo_via, nombre_via, numero, mz_lote
 *   $detalleDomicilioPermitido  array de claves permitidas (TIPO_ZONA, TIPO_ASENTAMIENTO, NOMBRE_ZONA, TIPO_VIA, NOMBRE_VIA, NUMERO, MZ_LOTE)
 *   $enfermedad                 (opcional) para la etiqueta de tipo_zona (nucleo_ajustes.etiquetas)
 *
 * TIPO_ASENTAMIENTO (2026-10-16): Urbanización, AA.HH., etc. -- el "Zona°"
 * de A00 y el "TIPO DE ZONA" de A95/B57 --, otro eje que TIPO_ZONA
 * (Urbano/Periurbano/Rural).
 */
$tieneDetalleDomicilioCampo = fn(string $campo): bool => in_array($campo, $detalleDomicilioPermitido, true);
?>
<div class="fields thirds" style="margin-top:14px" data-detalle-domicilio-bloque="<?= $prefijoNombreCampo === '' ? 'actual' : 'anterior' ?>">
  <div class="field" data-detalle-domicilio-campo="TIPO_ZONA" <?= $tieneDetalleDomicilioCampo('TIPO_ZONA') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl"><?= e(etiquetaNucleo($enfermedad ?? [], 'tipo_zona', 'Tipo de zona')) ?></label>
    <div class="control">
      <select name="<?= e($prefijoNombreCampo) ?>tipo_zona" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <option value="URBANO" <?= seleccionado($valoresDetalle['tipo_zona'] ?? '', 'URBANO') ?>>Urbano</option>
        <option value="PERIURBANO" <?= seleccionado($valoresDetalle['tipo_zona'] ?? '', 'PERIURBANO') ?>>Periurbano</option>
        <option value="RURAL" <?= seleccionado($valoresDetalle['tipo_zona'] ?? '', 'RURAL') ?>>Rural</option>
      </select>
    </div>
  </div>
  <div class="field" data-detalle-domicilio-campo="TIPO_ASENTAMIENTO" <?= $tieneDetalleDomicilioCampo('TIPO_ASENTAMIENTO') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Tipo de zona <span class="hint">(Urbanización, AA.HH., etc.)</span></label>
    <div class="control">
      <select name="<?= e($prefijoNombreCampo) ?>tipo_asentamiento" data-nosearch="true">
        <option value="">Seleccionar…</option>
        <?php foreach (TIPOS_ASENTAMIENTO as $codigoAsentamiento => $etiquetaAsentamiento): ?>
          <option value="<?= e($codigoAsentamiento) ?>" <?= seleccionado($valoresDetalle['tipo_asentamiento'] ?? '', $codigoAsentamiento) ?>><?= e($etiquetaAsentamiento) ?></option>
        <?php endforeach; ?>
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
