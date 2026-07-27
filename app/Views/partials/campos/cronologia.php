<?php
/**
 * Cronología de signos y síntomas.
 * Variables: $campo, $valor (array), $error (?string), $opciones (array de catalogo_item para catalogo 456)
 */
$nombreCampo = 'campo_' . $campo['id'];
$valores = is_array($valor) ? $valor : [];
$isSi = isset($valores['marcado']) && $valores['marcado'] === 'SI';
$isNo = isset($valores['marcado']) && $valores['marcado'] === 'NO';
$isIgn = isset($valores['marcado']) && $valores['marcado'] === 'IGNORADO';
$respondido = $isSi || $isNo || $isIgn;
$idMatriz = 'cronologia_' . $campo['id'];

$fechaCero = $valores['fecha'] ?? '';

// Opciones del catálogo 456
if (empty($opciones) && !empty($campo['catalogo_id'])) {
    $opciones = \App\Models\CatalogoItem::porCatalogo((int) $campo['catalogo_id']);
}

// Calcular rango PHP inicial si hay fecha 0
$strMinInicial = '1900-01-01';
$strMaxInicial = date('Y-m-d');

if (!empty($fechaCero)) {
    $timeF0 = strtotime($fechaCero);
    if ($timeF0 !== false) {
        $strMinInicial = date('Y-m-d', strtotime('-10 days', $timeF0));
        $max10 = date('Y-m-d', strtotime('+10 days', $timeF0));
        $hoy = date('Y-m-d');
        $strMaxInicial = min($max10, $hoy);
    }
}

$cie10Actual = $enfermedad['cie10'] ?? ($GLOBALS['enfermedad']['cie10'] ?? '');
$permitirIgnorado = ($cie10Actual !== 'B05');
$anchoSeg = $permitirIgnorado ? '190px' : '130px';
?>

<div class="field wide grupo-si-no-field cronologia-field" id="<?= $idMatriz ?>" data-campo-id="<?= $campo['id'] ?>">
  <div class="grupo-si-no-row <?= $isSi ? 'is-si' : '' ?> <?= $respondido ? 'respondido' : 'pendiente' ?>" tabindex="-1" style="display:flex; flex-direction:column; justify-content:center; border-bottom:1px solid var(--line-2); min-height:40px; padding:6px 0; transition: border-left 0.15s; border-left: <?= $isSi ? '3px solid var(--accent)' : '3px solid transparent' ?>;">
    
    <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
      <!-- Etiqueta a la izquierda -->
      <span class="row-label" style="font-size: 13.5px; color: <?= $isSi ? 'var(--ink)' : ($respondido ? 'var(--ink-2)' : 'var(--ink)') ?>; font-weight: <?= $isSi ? '500' : 'normal' ?>; flex:1; padding-left:6px;">
        <?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?>
      </span>
      
      <!-- Control segmentado a la derecha -->
      <div class="seg" style="width: <?= $anchoSeg ?>; flex-shrink:0;">
        <label class="seg-label <?= $isSi ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Sí">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="SI" class="sr-only" <?= $isSi ? 'checked' : '' ?> onchange="var d=this.closest('.grupo-si-no-row').querySelector('.fecha-dep'); if(d) d.style.display = this.checked ? 'block' : 'none';">
          Sí
        </label>
        <label class="seg-label <?= $isNo ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="No">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="NO" class="sr-only" <?= $isNo ? 'checked' : '' ?> onchange="var d=this.closest('.grupo-si-no-row').querySelector('.fecha-dep'); if(d) d.style.display = 'none';">
          No
        </label>
        <?php if ($permitirIgnorado): ?>
        <label class="seg-label <?= $isIgn ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Ignorado">
          <input type="radio" name="<?= e($nombreCampo) ?>[marcado]" value="IGNORADO" class="sr-only" <?= $isIgn ? 'checked' : '' ?> onchange="var d=this.closest('.grupo-si-no-row').querySelector('.fecha-dep'); if(d) d.style.display = 'none';">
          Ign.
        </label>
        <?php endif; ?>
      </div>
    </div>

    <!-- Campo de Fecha Día 0 -->
    <div class="fecha-dep" style="display: <?= $isSi ? 'block' : 'none' ?>; margin-top:10px; padding-left:6px; padding-right:6px; width:100%;">
      <div class="field" style="margin-bottom:0; max-width:260px;">
        <label class="fl" style="font-size:12px; margin-bottom:4px; color:var(--muted)">Fecha de inicio del exantema (Día 0):</label>
        <div class="control mono <?= $error ? 'err' : '' ?>">
          <input type="date" name="<?= e($nombreCampo) ?>[fecha]" class="input-fecha-dia-cero" value="<?= e($fechaCero) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
        </div>
      </div>

      <!-- Subsección Tabla de Cronología (3 columnas: Signo, Inicio, Fin) -->
      <div class="tabla-cronologia-wrap" style="display: <?= ($isSi && !empty($fechaCero)) ? 'block' : 'none' ?>; margin-top: 16px; border-top: 1px solid var(--line-2); padding-top: 16px; width: 100%;">
        <div style="font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 10px;">
          Cronología por signo y síntoma
        </div>
        <div style="overflow-x: auto;">
          <table class="tabla-cronologia" style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
            <thead>
              <tr style="border-bottom: 2px solid var(--line-2); text-align: left; color: var(--muted); font-size: 12px; font-weight: 600;">
                <th style="padding: 8px 12px; width: 40%;">Signo</th>
                <th style="padding: 8px 12px; width: 30%;">Inicio</th>
                <th style="padding: 8px 12px; width: 30%;">Fin</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($opciones as $op): 
                $vVal = $op['valor'];
                $signoData = $valores['signos'][$vVal] ?? [];
                $fInicio = $signoData['inicio'] ?? '';
                $fFin = $signoData['fin'] ?? '';
                $esOtros = (in_array(strtoupper($vVal), ['OTROS', 'OTRAS'], true) || stripos($op['etiqueta'], 'otro') !== false);
              ?>
                <tr data-signo-key="<?= e($vVal) ?>" style="border-bottom: <?= $esOtros ? 'none' : '1px solid var(--line-2)' ?>; min-height: 44px;">
                  <td style="padding: 8px 12px; font-weight: 500; color: var(--ink);">
                    <?= e($op['etiqueta']) ?>
                  </td>
                  <td style="padding: 6px 12px;">
                    <div class="control mono">
                      <input type="date"
                             name="<?= e($nombreCampo) ?>[signos][<?= e($vVal) ?>][inicio]"
                             class="cron-fecha-inicio"
                             value="<?= e($fInicio) ?>"
                             min="<?= e($strMinInicial) ?>"
                             max="<?= e($strMaxInicial) ?>">
                    </div>
                  </td>
                  <td style="padding: 6px 12px;">
                    <div class="control mono">
                      <input type="date"
                             name="<?= e($nombreCampo) ?>[signos][<?= e($vVal) ?>][fin]"
                             class="cron-fecha-fin"
                             value="<?= e($fFin) ?>"
                             min="<?= e($strMinInicial) ?>"
                             max="<?= e($strMaxInicial) ?>">
                    </div>
                  </td>
                </tr>
                <?php if ($esOtros): ?>
                  <tr style="border-bottom: 1px solid var(--line-2);">
                    <td colspan="3" style="padding: 2px 12px 10px 12px;">
                      <div class="control">
                        <input type="text"
                               name="<?= e($nombreCampo) ?>[signos][<?= e($vVal) ?>][especificar]"
                               value="<?= e($valores['signos'][$vVal]['especificar'] ?? '') ?>"
                               placeholder="Especifique otros signos y síntomas…"
                               style="width: 100%;">
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Subsección Gráfico de Cronología -->
      <div class="cronologia-grafico-wrap" style="display: <?= ($isSi && !empty($fechaCero)) ? 'block' : 'none' ?>; margin-top: 24px; border-top: 1px dashed var(--line-2); padding-top: 18px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px;">
          <div style="font-size: 13px; font-weight: 600; color: var(--ink); display:flex; align-items:center; gap:8px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            Línea de Tiempo Epidemiológica (Día -10 a +10)
          </div>
          <span style="font-size: 11.5px; color: var(--muted);">Día 0 = Fecha de exantema</span>
        </div>

        <div style="overflow-x: auto; background: var(--paper); border: 1px solid var(--line-2); border-radius: 8px; padding: 14px 16px;">
          <div style="min-width: 680px;">
            <!-- Escala de Días -->
            <div style="display: flex; align-items: center; border-bottom: 2px solid var(--line-2); padding-bottom: 8px; margin-bottom: 8px; font-size: 11px; font-weight: 600; color: var(--muted); text-align: center;">
              <div style="width: 140px; text-align: left; padding-left: 4px; flex-shrink: 0; color: var(--ink);">Signo / Síntoma</div>
              <div style="flex: 1; display: grid; grid-template-columns: repeat(21, 1fr); gap: 2px;">
                <?php for ($d = -10; $d <= 10; $d++): ?>
                  <div style="padding: 2px 0; border-radius: 4px; <?= $d === 0 ? 'background: var(--accent-subtle, rgba(59, 130, 246, 0.15)); color: var(--accent); font-weight: bold;' : '' ?>">
                    <?= $d > 0 ? '+' . $d : $d ?>
                  </div>
                <?php endfor; ?>
              </div>
            </div>

            <!-- Filas de Signos en el Gráfico -->
            <?php foreach ($opciones as $op): 
              $vVal = $op['valor'];
              $signoData = $valores['signos'][$vVal] ?? [];
              $fInicio = $signoData['inicio'] ?? '';
              $fFin = $signoData['fin'] ?? '';

              $dayIni = null;
              $dayFin = null;
              if (!empty($fechaCero)) {
                  $tCero = strtotime($fechaCero);
                  if (!empty($fInicio) && strtotime($fInicio) !== false) {
                      $dayIni = (int) round((strtotime($fInicio) - $tCero) / 86400);
                  }
                  if (!empty($fFin) && strtotime($fFin) !== false) {
                      $dayFin = (int) round((strtotime($fFin) - $tCero) / 86400);
                  }
              }
              if ($dayIni !== null && $dayFin === null) $dayFin = $dayIni;
              if ($dayFin !== null && $dayIni === null) $dayIni = $dayIni;
            ?>
              <div class="chart-row" data-signo-key="<?= e($vVal) ?>" style="display: flex; align-items: center; min-height: 28px; border-bottom: 1px solid var(--line-2); font-size: 12px;">
                <div style="width: 140px; text-align: left; padding-left: 4px; flex-shrink: 0; font-weight: 500; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= e($op['etiqueta']) ?>">
                  <?= e($op['etiqueta']) ?>
                </div>
                <div style="flex: 1; display: grid; grid-template-columns: repeat(21, 1fr); gap: 2px; align-items: center;">
                  <?php for ($d = -10; $d <= 10; $d++): 
                    $inRange = ($dayIni !== null && $dayFin !== null && $d >= $dayIni && $d <= $dayFin);
                    $isStart = ($inRange && $d === $dayIni);
                    $isEnd = ($inRange && $d === $dayFin);
                  ?>
                    <div class="chart-cell <?= $inRange ? 'in-range' : '' ?> <?= $isStart ? 'range-start' : '' ?> <?= $isEnd ? 'range-end' : '' ?>"
                         data-day="<?= $d ?>"
                         title="Día <?= $d > 0 ? '+' . $d : $d ?>"
                         style="height: 18px; <?= $d === 0 && !$inRange ? 'background: rgba(128, 128, 128, 0.08);' : '' ?>"></div>
                  <?php endfor; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
  <?php if ($error): ?><span class="hint err" style="margin-top:8px; display:block;"><?= e($error) ?></span><?php endif; ?>
</div>

<style>
.cronologia-field .chart-cell {
  transition: background 0.15s ease, border-radius 0.15s ease;
  border-radius: 2px;
}
.cronologia-field .chart-cell.in-range {
  background: var(--accent, #3b82f6);
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.cronologia-field .chart-cell.range-start {
  border-top-left-radius: 6px;
  border-bottom-left-radius: 6px;
}
.cronologia-field .chart-cell.range-end {
  border-top-right-radius: 6px;
  border-bottom-right-radius: 6px;
}
</style>
