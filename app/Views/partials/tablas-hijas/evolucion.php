<?php
/**
 * Fila dinámica de evolución clínica del caso (caso_evolucion), propia de
 * A44 (Enfermedad de Carrión, pág. 43 del PDF: tabla "EVOLUCIÓN" por fecha).
 * Variable esperada: $filasEvolucion (array de filas, ver $filaDefecto).
 * Reemplaza a los campos de un único registro (a44_hemoglobina/
 * a44_hematocrito/a44_transfusiones_u/a44_antibioticos_usados) que no
 * permitían repetir la evaluación en varias fechas -- mismo mecanismo que
 * viajes.php/muestras.php (subrows + <template> + agregar-fila).
 *
 * Hemocultivo (*) trae una nota especial en el PDF: "indicar si se tomó
 * muestra ya que el resultado del cultivo demora hasta 40 días" -- se
 * modela como un disparador Sí/No ("¿Se tomó muestra?") que revela fecha de
 * toma/resultado/fecha de resultado, vía el mecanismo genérico
 * data-depende-columna/data-valores-activadores (evaluarDependenciasMuestra
 * en ficha.js, generalizado con data-prefijo-lista para no chocar con el
 * prefijo "muestra_" de caso_muestra).
 */
$erroresEvolucion = $erroresEvolucion ?? [];

$antibioticos = [
    'penicilina'     => 'Penicilina',
    'cloranfenicol'  => 'Cloranfenicol',
    'rifampicina'    => 'Rifampicina',
    'ciprofloxacina' => 'Ciprofloxacina',
    'eritromicina'   => 'Eritromicina',
    'cotrimoxazol'   => 'Cotrimoxazol',
    'ceftriaxona'    => 'Ceftriaxona',
    'otros'          => 'Otros',
];

$filaDefecto = ['fecha' => '', 'temperatura' => '', 'hemoglobina' => '', 'hematocrito' => '', 'transfusiones' => '', 'frotis' => '', 'hemocultivo_muestra_tomada' => '', 'hemocultivo_fecha_toma' => '', 'hemocultivo_resultado' => '', 'hemocultivo_fecha_resultado' => '', 'atb_otros_especificar' => ''];
foreach (array_keys($antibioticos) as $atb) {
    $filaDefecto["atb_{$atb}_usado"] = '';
    $filaDefecto["atb_{$atb}_dosis"] = '';
}

$filaEvolucion = function (array $fila = [], ?array $error = null) use ($antibioticos, $filaDefecto): void {
    $fila = array_merge($filaDefecto, $fila);
    $errorFecha = $error['fecha'] ?? null;
    $errorHcToma = $error['hemocultivo_fecha_toma'] ?? null;
    $errorHcResultado = $error['hemocultivo_fecha_resultado'] ?? null;
    $tomoMuestra = (string) ($fila['hemocultivo_muestra_tomada'] ?? '');
    ?>
  <div class="subrow" style="border:1px solid var(--line-2); border-radius:10px; padding:14px; margin-bottom:14px; width:100%">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px">
      <div class="fields thirds" style="flex:1">
        <div class="field">
          <label class="fl">Fecha</label>
          <div class="control mono <?= $errorFecha ? 'err' : '' ?>"><input type="date" name="evolucion_fecha[]" value="<?= e($fila['fecha'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
          <?php if ($errorFecha): ?><span class="hint err"><?= e($errorFecha) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">Temperatura (°C)</label>
          <div class="control mono"><input type="number" step="0.1" class="permite-decimales" name="evolucion_temperatura[]" value="<?= e($fila['temperatura'] ?? '') ?>"></div>
        </div>
        <div class="field">
          <label class="fl">Hemoglobina</label>
          <div class="control mono"><input type="number" step="0.1" class="permite-decimales" name="evolucion_hemoglobina[]" value="<?= e($fila['hemoglobina'] ?? '') ?>"></div>
        </div>
        <div class="field">
          <label class="fl">Hematocrito</label>
          <div class="control mono"><input type="number" step="0.1" class="permite-decimales" name="evolucion_hematocrito[]" value="<?= e($fila['hematocrito'] ?? '') ?>"></div>
        </div>
        <div class="field">
          <label class="fl">Transfusiones (U)</label>
          <div class="control mono"><input type="number" step="1" min="0" name="evolucion_transfusiones[]" value="<?= e($fila['transfusiones'] ?? '') ?>"></div>
        </div>
        <div class="field">
          <label class="fl">Frotis</label>
          <div class="control"><input type="text" name="evolucion_frotis[]" value="<?= e($fila['frotis'] ?? '') ?>" placeholder="Frotis…"></div>
        </div>
      </div>
      <button type="button" class="ra quitar-fila" title="Quitar registro">
        <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      </button>
    </div>

    <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--line-2)">
      <div class="eyebrow" style="margin-bottom:8px">Hemocultivo (*)</div>
      <div class="fields thirds" style="flex:1">
        <div class="field">
          <label class="fl">¿Se tomó muestra?</label>
          <div class="control">
            <select name="evolucion_hemocultivo_muestra_tomada[]" data-nosearch="true">
              <option value="">Seleccionar…</option>
              <option value="1" <?= seleccionado($tomoMuestra, '1') ?>>Sí</option>
              <option value="0" <?= seleccionado($tomoMuestra, '0') ?>>No</option>
            </select>
          </div>
        </div>
        <div class="field" data-depende-columna="hemocultivo_muestra_tomada" data-valores-activadores="1" data-prefijo-lista="evolucion" style="<?= $tomoMuestra !== '1' ? 'display:none;' : '' ?>">
          <label class="fl">Fecha de toma</label>
          <div class="control mono <?= $errorHcToma ? 'err' : '' ?>"><input type="date" name="evolucion_hemocultivo_fecha_toma[]" value="<?= e($fila['hemocultivo_fecha_toma'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
          <?php if ($errorHcToma): ?><span class="hint err"><?= e($errorHcToma) ?></span><?php endif; ?>
        </div>
        <div class="field" data-depende-columna="hemocultivo_muestra_tomada" data-valores-activadores="1" data-prefijo-lista="evolucion" style="<?= $tomoMuestra !== '1' ? 'display:none;' : '' ?>">
          <label class="fl">Resultado</label>
          <div class="control">
            <select name="evolucion_hemocultivo_resultado[]" data-nosearch="true">
              <option value="">Pendiente</option>
              <option value="POSITIVO" <?= seleccionado($fila['hemocultivo_resultado'] ?? '', 'POSITIVO') ?>>Positivo</option>
              <option value="NEGATIVO" <?= seleccionado($fila['hemocultivo_resultado'] ?? '', 'NEGATIVO') ?>>Negativo</option>
            </select>
          </div>
        </div>
        <div class="field" data-depende-columna="hemocultivo_muestra_tomada" data-valores-activadores="1" data-prefijo-lista="evolucion" style="<?= $tomoMuestra !== '1' ? 'display:none;' : '' ?>">
          <label class="fl">Fecha de resultado</label>
          <div class="control mono <?= $errorHcResultado ? 'err' : '' ?>"><input type="date" name="evolucion_hemocultivo_fecha_resultado[]" value="<?= e($fila['hemocultivo_fecha_resultado'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
          <?php if ($errorHcResultado): ?><span class="hint err"><?= e($errorHcResultado) ?></span><?php endif; ?>
        </div>
      </div>
    </div>

    <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--line-2)">
      <div class="eyebrow" style="margin-bottom:8px">Antibióticos</div>
      <div style="overflow-x:auto; background:var(--surface); border:1px solid var(--line); border-radius:9px; padding:1px;">
        <table style="width:100%; border-collapse:collapse; min-width:420px;">
          <thead>
            <tr>
              <th style="font-size:10.5px; text-transform:uppercase; color:var(--faint); padding:8px 12px; border-bottom:1px solid var(--line); text-align:left;">Antibiótico</th>
              <th style="font-size:10.5px; text-transform:uppercase; color:var(--faint); padding:8px 12px; border-bottom:1px solid var(--line); text-align:center; min-width:90px;">¿Usado?</th>
              <th style="font-size:10.5px; text-transform:uppercase; color:var(--faint); padding:8px 12px; border-bottom:1px solid var(--line); text-align:left; min-width:120px;">Dosis</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($antibioticos as $atbSlug => $atbEtiqueta): ?>
            <tr>
              <td style="font-size:12px; font-weight:500; color:var(--ink); padding:8px 12px; border-bottom:1px solid var(--line-2);">
                <?php if ($atbSlug === 'otros'): ?>
                  <input type="text" name="evolucion_atb_otros_especificar[]" value="<?= e($fila['atb_otros_especificar'] ?? '') ?>" placeholder="Otros (especificar)…" style="width:100%; border:1px solid var(--line); border-radius:6px; padding:5px 8px; font-size:12px; background:var(--paper); color:var(--ink); outline:none;">
                <?php else: ?>
                  <?= e($atbEtiqueta) ?>
                <?php endif; ?>
              </td>
              <td style="padding:4px 8px; border-bottom:1px solid var(--line-2); text-align:center;">
                <select name="evolucion_atb_<?= $atbSlug ?>_usado[]" data-nosearch="true" style="width:100%; border:1px solid var(--line); border-radius:6px; padding:5px 8px; font-size:12px; background:var(--paper); color:var(--ink); outline:none;">
                  <option value="">—</option>
                  <option value="1" <?= seleccionado($fila["atb_{$atbSlug}_usado"] ?? '', '1') ?>>Sí</option>
                  <option value="0" <?= seleccionado($fila["atb_{$atbSlug}_usado"] ?? '', '0') ?>>No</option>
                </select>
              </td>
              <td style="padding:4px 8px; border-bottom:1px solid var(--line-2);">
                <input type="text" name="evolucion_atb_<?= $atbSlug ?>_dosis[]" value="<?= e($fila["atb_{$atbSlug}_dosis"] ?? '') ?>" placeholder="Dosis…" style="width:100%; border:1px solid var(--line); border-radius:6px; padding:5px 8px; font-size:12px; background:var(--paper); color:var(--ink); outline:none;">
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php };
?>
<div class="subrows" data-lista="evolucion">
  <?php foreach ($filasEvolucion as $i => $fila): $filaEvolucion($fila, $erroresEvolucion[$i] ?? null); endforeach; ?>
</div>
<template id="plantilla-evolucion"><?php $filaEvolucion(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-evolucion" data-lista="evolucion" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar evolución
</button>
