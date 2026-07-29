<?php
/**
 * Plantilla especializada para la Sección 11: Las cuatro demoras (Anexo 2) de Muerte Materna (O95).
 */

$raw1 = $valoresCampos[14380] ?? '';
$raw2 = $valoresCampos[14381] ?? '';
$raw3 = $valoresCampos[14382] ?? '';
$raw4 = $valoresCampos[14383] ?? '';

$val1 = is_array($raw1) ? ($raw1['EN_LA_IDENTIFICACION_DEL_PROBLEMA'] ?? '') : (string)$raw1;
$val2 = is_array($raw2) ? ($raw2['EN_LA_DECISION_DE_BUSCAR_AYUDA'] ?? '') : (string)$raw2;
$val3 = is_array($raw3) ? ($raw3['EN_ACCEDER_A_LOS_SERVICIOS_DE_SALUD'] ?? '') : (string)$raw3;
$val4 = is_array($raw4) ? ($raw4['EN_RECIBIR_TRATAMIENTO_ADECUADO_Y_OPORTUNO'] ?? '') : (string)$raw4;

$valObs = $valoresCampos[16182] ?? '';

$demoras = [
    [
        'num' => '1.ª DEMORA',
        'titulo' => 'En la identificación del problema',
        'sugerencia' => 'Retraso en la identificación del problema: La gestante, la familia o la comunidad no reconocieron los signos de alarma o la gravedad de la situación.',
        'campo' => 'campo_14380[EN_LA_IDENTIFICACION_DEL_PROBLEMA]',
        'val' => (string)$val1,
        'pos' => 'bottom'
    ],
    [
        'num' => '2.ª DEMORA',
        'titulo' => 'En la decisión de buscar ayuda',
        'sugerencia' => 'Retraso en la toma de decisión para buscar atención en un establecimiento de salud.',
        'campo' => 'campo_14381[EN_LA_DECISION_DE_BUSCAR_AYUDA]',
        'val' => (string)$val2,
        'pos' => 'bottom'
    ],
    [
        'num' => '3.ª DEMORA',
        'titulo' => 'En acceder a los servicios de salud',
        'sugerencia' => 'Dificultad para acceder al establecimiento de salud por distancia, transporte, condiciones geográficas u otras barreras.',
        'campo' => 'campo_14382[EN_ACCEDER_A_LOS_SERVICIOS_DE_SALUD]',
        'val' => (string)$val3,
        'pos' => 'bottom'
    ],
    [
        'num' => '4.ª DEMORA',
        'titulo' => 'En recibir tratamiento adecuado y oportuno',
        'sugerencia' => 'Retraso en la atención por falta de capacidad resolutiva, personal, equipamiento, insumos u otros factores del establecimiento.',
        'campo' => 'campo_14383[EN_RECIBIR_TRATAMIENTO_ADECUADO_Y_OPORTUNO]',
        'val' => (string)$val4,
        'pos' => 'top'
    ],
];
?>

<style>
.demora-row {
  position: relative;
  z-index: 1;
}
.demora-row:hover,
.demora-row:focus-within,
.demora-row.has-active-tooltip {
  z-index: 100 !important;
}
.demora-help-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  cursor: pointer;
  outline: none;
}
.demora-help-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--accent-soft, #e2f1f0);
  color: var(--accent-deep, #0e7a6e);
  border: 1px solid var(--accent, #14b8a6);
  font-size: 11px;
  font-weight: 700;
  transition: all 0.2s ease;
}
.demora-help-btn:hover .demora-help-icon,
.demora-help-btn:focus .demora-help-icon,
.demora-help-btn.active .demora-help-icon {
  background: var(--accent, #14b8a6);
  color: #fff;
  transform: scale(1.1);
}
.demora-tooltip {
  position: absolute;
  top: 130%;
  left: 50%;
  transform: translateX(-50%);
  width: 280px;
  padding: 10px 14px;
  background: var(--ink-deep, #0f172a);
  color: #ffffff;
  font-size: 0.78rem;
  font-weight: 400;
  line-height: 1.45;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
  z-index: 999;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease;
  pointer-events: none;
  text-align: left;
}
.demora-tooltip::after {
  content: "";
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  border-width: 6px;
  border-style: solid;
  border-color: transparent transparent var(--ink-deep, #0f172a) transparent;
}
.demora-help-btn[data-pos="top"] .demora-tooltip {
  top: auto;
  bottom: 130%;
}
.demora-help-btn[data-pos="top"] .demora-tooltip::after {
  bottom: auto;
  top: 100%;
  border-color: var(--ink-deep, #0f172a) transparent transparent transparent;
}
.demora-help-btn:hover .demora-tooltip,
.demora-help-btn:focus .demora-tooltip,
.demora-help-btn.active .demora-tooltip {
  opacity: 1;
  visibility: visible;
}
.demora-help-btn[data-pos="bottom"]:hover .demora-tooltip,
.demora-help-btn[data-pos="bottom"]:focus .demora-tooltip,
.demora-help-btn[data-pos="bottom"].active .demora-tooltip {
  transform: translateX(-50%) translateY(4px);
}
.demora-help-btn[data-pos="top"]:hover .demora-tooltip,
.demora-help-btn[data-pos="top"]:focus .demora-tooltip,
.demora-help-btn[data-pos="top"].active .demora-tooltip {
  transform: translateX(-50%) translateY(-4px);
}
</style>

<div id="bloqueDemorasO95">
  <!-- Apartado Informativo Superior -->
  <div class="info-callout" style="background:var(--accent-soft); border:1px solid var(--accent); border-radius:var(--radius-sm, 8px); padding:14px 18px; margin-bottom:20px; color:var(--ink); font-size:0.875rem; line-height:1.6;">
    <div style="display:flex; align-items:flex-start; gap:10px;">
      <span style="display:inline-flex; align-items:center; justify-content:center; background:var(--surface); border:1px solid var(--accent); color:var(--accent-deep); width:24px; height:24px; border-radius:50%; font-size:12px; font-weight:700; flex-shrink:0; box-shadow:var(--shadow-soft); margin-top:1px;">ℹ</span>
      <div style="color:var(--ink-2); font-size:0.85rem; line-height:1.5;">
        <strong style="color:var(--accent-deep);">Nota sobre Muerte Materna Institucional:</strong> En caso de una muerte materna institucional procedente o no de una referencia institucional y ocurrida inmediatamente después de ingresar, la primera demora puede corresponder a la identificación del problema por parte del personal de salud.
      </div>
    </div>
  </div>

  <div style="display:flex; flex-direction:column; gap:0; border:1px solid var(--line); border-radius:var(--radius-sm, 8px); background:var(--surface); position:relative;">
    <?php foreach ($demoras as $idx => $d): 
      $isSi = (strtoupper($d['val']) === 'SI');
      $isNo = (strtoupper($d['val']) === 'NO');
    ?>
      <div class="demora-row" style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:<?= $idx < 3 ? '1px solid var(--line-2)' : '0' ?>; gap:16px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:10px; flex:1; min-width:280px;">
          <span style="font-size:0.75rem; font-weight:700; color:var(--accent-deep); letter-spacing:0.5px; text-transform:uppercase; background:var(--accent-soft); padding:3px 8px; border-radius:4px; flex-shrink:0;"><?= e($d['num']) ?></span>
          <span style="font-size:0.875rem; font-weight:600; color:var(--ink);"><?= e($d['titulo']) ?></span>
          
          <!-- Ícono de Ayuda / Tooltip (ⓘ) -->
          <div class="demora-help-btn" data-pos="<?= e($d['pos']) ?>" tabindex="0" role="button" aria-label="Información de <?= e($d['num']) ?>">
            <span class="demora-help-icon">ⓘ</span>
            <div class="demora-tooltip">
              <?= e($d['sugerencia']) ?>
            </div>
          </div>
        </div>

        <div class="seg" style="width:130px; flex-shrink:0;">
          <label class="seg-label <?= $isSi ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Sí">
            <input type="radio" name="<?= e($d['campo']) ?>" value="SI" class="sr-only" <?= $isSi ? 'checked' : '' ?>>
            Sí
          </label>
          <label class="seg-label <?= $isNo ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="No">
            <input type="radio" name="<?= e($d['campo']) ?>" value="NO" class="sr-only" <?= $isNo ? 'checked' : '' ?>>
            No
          </label>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Campo de Observaciones -->
  <div style="margin-top:16px;">
    <label class="fl" style="font-weight:600; color:var(--ink);">OBSERVACIONES: Anote información adicional relevante</label>
    <div class="control">
      <textarea name="campo_16182" rows="3" placeholder="Observaciones o notas adicionales sobre las cuatro demoras…" style="width:100%; font-family:inherit; font-size:0.875rem; padding:10px 12px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line); background:var(--surface); color:var(--ink); resize:vertical;"><?= e($valObs) ?></textarea>
    </div>
  </div>
</div>
