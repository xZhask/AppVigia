<?php
/**
 * Plantilla especializada para la Sección 3: Causas de defunción de Muerte Materna (O95).
 * 
 * Estructura Consolidada (Anexo 1 y Anexo 2):
 * - Tabla con Causas (Final, Intermedia, Básica, Asociada) y sus respectivos códigos CIE-10.
 * - Causa genérica (con especificación para 'Otra causa').
 * - Clasificación inicial y Clasificación final de la muerte materna.
 */

$valCausaFinal = $valoresCampos[14311] ?? ($valoresCampos[14374] ?? '');
$valCausaFinalCie = $valoresCampos[16134] ?? '';

$valCausaInter = $valoresCampos[14312] ?? ($valoresCampos[14375] ?? '');
$valCausaInterCie = $valoresCampos[16135] ?? '';

$valCausaBasica = $valoresCampos[14313] ?? ($valoresCampos[14376] ?? '');
$valCausaBasicaCie = $valoresCampos[16136] ?? '';

$valCausaAsociada = $valoresCampos[14377] ?? '';
$valCausaAsociadaCie = $valoresCampos[16181] ?? '';

$valCausaGenerica = $valoresCampos[14314] ?? ($valoresCampos[14378] ?? '');
$valCausaGenericaOtra = $valoresCampos[16137] ?? '';

$valClasifInicial = $valoresCampos[14315] ?? '';
$valClasifFinal   = $valoresCampos[14379] ?? '';
?>

<div id="bloqueCausasDefuncionO95">
  <!-- Grilla de Causas de Defunción y Códigos CIE-10 -->
  <div class="table-wrap" style="margin-bottom:20px; overflow-x:auto;">
    <table class="data-table" style="width:100%; border-collapse:collapse;">
      <thead>
        <tr>
          <th style="width:25%; text-align:left;">Tipo de causa</th>
          <th style="width:22%; text-align:left;">Código CIE-10</th>
          <th style="width:53%; text-align:left;">Descripción de la causa</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong style="color:var(--ink-1); font-weight:600;">Causa final probable</strong></td>
          <td>
            <div class="control mono">
              <input type="text" name="campo_16134" value="<?= e($valCausaFinalCie) ?>" placeholder="Ej: O72.1" style="text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">
            </div>
          </td>
          <td>
            <div class="control">
              <input type="text" name="campo_14311" value="<?= e($valCausaFinal) ?>" placeholder="Descripción de la causa final…">
            </div>
          </td>
        </tr>
        <tr>
          <td><strong style="color:var(--ink-1); font-weight:600;">Causa intermedia probable</strong></td>
          <td>
            <div class="control mono">
              <input type="text" name="campo_16135" value="<?= e($valCausaInterCie) ?>" placeholder="Ej: O85" style="text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">
            </div>
          </td>
          <td>
            <div class="control">
              <input type="text" name="campo_14312" value="<?= e($valCausaInter) ?>" placeholder="Descripción de la causa intermedia…">
            </div>
          </td>
        </tr>
        <tr>
          <td><strong style="color:var(--ink-1); font-weight:600;">Causa básica probable</strong></td>
          <td>
            <div class="control mono">
              <input type="text" name="campo_16136" value="<?= e($valCausaBasicaCie) ?>" placeholder="Ej: O95" style="text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">
            </div>
          </td>
          <td>
            <div class="control">
              <input type="text" name="campo_14313" value="<?= e($valCausaBasica) ?>" placeholder="Descripción de la causa básica…">
            </div>
          </td>
        </tr>
        <tr>
          <td><strong style="color:var(--ink-1); font-weight:600;">Causa asociada</strong></td>
          <td>
            <div class="control mono">
              <input type="text" name="campo_16181" value="<?= e($valCausaAsociadaCie) ?>" placeholder="Ej: O99" style="text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">
            </div>
          </td>
          <td>
            <div class="control">
              <input type="text" name="campo_14377" value="<?= e($valCausaAsociada) ?>" placeholder="Descripción de la causa asociada…">
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Apartado Informativo de Definiciones de Causas -->
  <div class="info-callout" style="background:var(--accent-soft); border:1px solid var(--accent); border-radius:var(--radius-sm, 8px); padding:14px 18px; margin-bottom:20px; color:var(--ink); font-size:0.875rem; line-height:1.6;">
    <div style="margin-bottom:8px;">
      <span style="display:inline-flex; align-items:center; gap:6px; background:var(--surface); border:1px solid var(--accent); color:var(--accent-deep); font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px; text-transform:uppercase; letter-spacing:0.5px; box-shadow:var(--shadow-soft);">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="16" x2="12" y2="12"></line>
          <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        Definiciones de las Causas de Defunción
      </span>
    </div>
    <div style="display:flex; flex-direction:column; gap:6px; font-size:0.85rem; color:var(--ink-2);">
      <div><strong style="color:var(--accent-deep);">CAUSA FINAL PROBABLE:</strong> Responsable directa de la muerte y la que justifica el desenlace fatal.</div>
      <div><strong style="color:var(--accent-deep);">CAUSA INTERMEDIA PROBABLE:</strong> La complicación principal que lleva a la causa final de la muerte.</div>
      <div><strong style="color:var(--accent-deep);">CAUSA BÁSICA PROBABLE:</strong> La enfermedad o afección que dio inicio a la cadena de eventos mórbidos que llevó a la muerte o las circunstancias del accidente o del episodio de violencia que produjeron una lesión fatal.</div>
    </div>
  </div>

  <!-- Causa genérica, Clasificación inicial y Clasificación final -->
  <div class="fields pairs" style="margin-bottom:16px;">
    <div class="field">
      <label class="fl">Causa genérica</label>
      <div class="control">
        <select id="o95CausaGenericaSel" name="campo_14314" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <option value="HEMORRAGIA" <?= seleccionado($valCausaGenerica, 'HEMORRAGIA') ?>>Hemorragia</option>
          <option value="HIPERTENSION_GESTACIONAL" <?= seleccionado($valCausaGenerica, 'HIPERTENSION_GESTACIONAL') ?>>Hipertensión gestacional</option>
          <option value="INFECCION_SEPSIS" <?= seleccionado($valCausaGenerica, 'INFECCION_SEPSIS') ?>>Infección/Sepsis</option>
          <option value="OTRA" <?= seleccionado($valCausaGenerica, 'OTRA') ?>>Otra causa</option>
        </select>
      </div>
    </div>

    <!-- Especificar otra causa genérica (condicional) -->
    <?php $esOtraCausaGen = (strtoupper(trim((string)$valCausaGenerica)) === 'OTRA'); ?>
    <div class="field" id="bloqueCausaGenericaOtraO95" <?= !$esOtraCausaGen ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl">Especificar otra causa genérica</label>
      <div class="control">
        <input type="text" name="campo_16137" value="<?= e($valCausaGenericaOtra) ?>" placeholder="Especificar otra causa…">
      </div>
    </div>
  </div>

  <div class="fields pairs">
    <div class="field">
      <label class="fl">Clasificación inicial de la muerte materna</label>
      <div class="control">
        <select name="campo_14315" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <option value="DIRECTA" <?= seleccionado($valClasifInicial, 'DIRECTA') ?>>Directa</option>
          <option value="INDIRECTA" <?= seleccionado($valClasifInicial, 'INDIRECTA') ?>>Indirecta</option>
          <option value="INCIDENTAL" <?= seleccionado($valClasifInicial, 'INCIDENTAL') ?>>Incidental</option>
          <option value="POR_DETERMINAR" <?= seleccionado($valClasifInicial, 'POR_DETERMINAR') ?>>Por determinar</option>
        </select>
      </div>
    </div>

    <div class="field">
      <label class="fl">Clasificación final de la muerte materna</label>
      <div class="control">
        <select name="campo_14379" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <option value="DIRECTA" <?= seleccionado($valClasifFinal, 'DIRECTA') ?>>Directa</option>
          <option value="INDIRECTA" <?= seleccionado($valClasifFinal, 'INDIRECTA') ?>>Indirecta</option>
          <option value="INCIDENTAL" <?= seleccionado($valClasifFinal, 'INCIDENTAL') ?>>Incidental</option>
          <option value="POR_DETERMINAR" <?= seleccionado($valClasifFinal, 'POR_DETERMINAR') ?>>Por determinar</option>
        </select>
      </div>
    </div>
  </div>
</div>
