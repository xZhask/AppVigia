<?php
/**
 * Edad con unidad en un solo control (número + unidad), en la fila de Sexo y
 * Fecha de nacimiento (2026-11-16, rediseño de "Datos de la persona"). Solo
 * en las fichas que declaran unidades_edad (A00, A35, A37.0, B04X, P35.0,
 * Y59.0). Antes era una fila aparte "Edad | Unidad" en
 * datos-paciente-nucleo.php.
 *
 * Mismo comportamiento híbrido de siempre (decisión del 2026-08-27): con
 * fecha de nacimiento la edad se calcula a la fecha de notificación y los
 * controles quedan deshabilitados (ficha.js, actualizarEdadCalculada(); el
 * servidor recalcula al guardar); sin fecha se escribe a mano. Los id y los
 * name no cambian: los usan ficha.js y CasosController.
 *
 * Espera $unidadesEdadPersona (lista de unidades) y $valoresFijos.
 */
$etiquetasUnidadEdad = ['ANIOS' => 'Años', 'MESES' => 'Meses', 'DIAS' => 'Días', 'HORAS' => 'Horas', 'MINUTOS' => 'Minutos'];
?>
<div class="field" data-edad-unidad-bloque data-unidades-edad="<?= e(json_encode($unidadesEdadPersona)) ?>">
  <label class="fl" for="edadValorInput">Edad</label>
  <div class="edad-combo">
    <div class="control mono"><input type="number" id="edadValorInput" name="edad_valor" min="0" step="1" placeholder="—" aria-label="Edad" value="<?= e($valoresFijos['edad_valor'] ?? '') ?>"></div>
    <div class="control">
      <select id="edadUnidadSelect" name="edad_unidad" data-nosearch="true" aria-label="Unidad de la edad">
        <option value="">Unidad…</option>
        <?php foreach ($unidadesEdadPersona as $unidad): ?>
          <option value="<?= e($unidad) ?>" <?= seleccionado($valoresFijos['edad_unidad'] ?? '', $unidad) ?>><?= e($etiquetasUnidadEdad[$unidad] ?? $unidad) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <span class="hint" id="edadCalculadaHint">Sin fecha de nacimiento, escríbala a mano.</span>
</div>
