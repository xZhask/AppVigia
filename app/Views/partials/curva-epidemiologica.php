<?php
/**
 * Dibuja a mano el SVG de la curva epidemiológica (sin Chart.js ni ninguna
 * librería, tal como pide la Fase 5). Variables esperadas, ya calculadas por
 * el controlador (PanelController::calcularCurva) — este partial solo hace
 * geometría/SVG, no toca la BD ni decide el método de la banda:
 *
 *   $curva['semanas']      [['anio'=>int,'semana'=>int], ...]
 *   $curva['valores']      [int, ...] casos notificados por semana
 *   $curva['bandaBaja']    [float, ...] borde inferior de la banda de referencia
 *   $curva['bandaAlta']    [float, ...] borde superior
 *   $curva['sobreUmbral']  [bool, ...] semana por encima de bandaAlta
 *   $curva['etiquetaBanda'] string  "Corredor endémico" o el respaldo provisional
 *   $curva['notaBanda']    ?string  nota al pie cuando es el respaldo
 */
$semanas = $curva['semanas'];
$valores = $curva['valores'];
$bandaBaja = $curva['bandaBaja'];
$bandaAlta = $curva['bandaAlta'];
$sobreUmbral = $curva['sobreUmbral'];
$n = count($semanas);

$izq = 44;
$der = 740;
$arriba = 24;
$abajo = 206;
$anchoTrazado = $der - $izq;
$altoTrazado = $abajo - $arriba;

$maxValor = max(1, max(array_merge($valores, $bandaAlta)));

$y = fn(float $valor): float => $abajo - ($valor / $maxValor) * $altoTrazado;

$anchoCarril = $n > 0 ? $anchoTrazado / $n : $anchoTrazado;
$anchoBarra = min(30, $anchoCarril * 0.6);
$xCentro = fn(int $i): float => $izq + $anchoCarril * $i + $anchoCarril / 2;

$puntosAlta = [];
$puntosBaja = [];
foreach ($semanas as $i => $semana) {
    $puntosAlta[] = [$xCentro($i), $y($bandaAlta[$i])];
    $puntosBaja[] = [$xCentro($i), $y($bandaBaja[$i])];
}
$puntosBajaInverso = array_reverse($puntosBaja);

$aPuntos = fn(array $puntos): string => implode(' ', array_map(fn($p) => round($p[0], 1) . ',' . round($p[1], 1), $puntos));

$pathBanda = 'M' . $aPuntos($puntosAlta) . ' L' . $aPuntos($puntosBajaInverso) . ' Z';
$pathBordeSuperior = 'M' . $aPuntos($puntosAlta);

$indicesEtiqueta = [];
if ($n > 0) {
    $pasos = min(5, $n);
    for ($k = 0; $k < $pasos; $k++) {
        $indicesEtiqueta[(int) round($k * ($n - 1) / max(1, $pasos - 1))] = true;
    }
}
?>
<div class="chart-wrap">
  <svg class="epi" viewBox="0 0 760 240" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Curva epidemiológica con banda de referencia">
    <g stroke="#EEF2F5" stroke-width="1">
      <?php for ($nivel = 0; $nivel <= 3; $nivel++): $yLinea = $arriba + ($altoTrazado / 3) * $nivel; ?>
        <line x1="<?= $izq ?>" y1="<?= round($yLinea, 1) ?>" x2="<?= $der ?>" y2="<?= round($yLinea, 1) ?>"/>
      <?php endfor; ?>
    </g>
    <g font-family="IBM Plex Mono" font-size="10" fill="#A4B0BA">
      <?php for ($nivel = 0; $nivel <= 3; $nivel++): $yLinea = $arriba + ($altoTrazado / 3) * $nivel; $valorLinea = (int) round($maxValor * (3 - $nivel) / 3); ?>
        <text x="<?= $izq - 8 ?>" y="<?= round($yLinea + 3, 1) ?>" text-anchor="end"><?= $valorLinea ?></text>
      <?php endfor; ?>
    </g>

    <?php if ($n > 1): ?>
      <path d="<?= $pathBanda ?>" fill="#0E7A6E" opacity="0.07"/>
      <path d="<?= $pathBordeSuperior ?>" fill="none" stroke="#0E7A6E" stroke-width="1.2" stroke-dasharray="3 4" opacity="0.55"/>
    <?php endif; ?>

    <g>
      <?php foreach ($semanas as $i => $semana):
        $valor = $valores[$i];
        $alturaBarra = ($valor / $maxValor) * $altoTrazado;
        $xBarra = $xCentro($i) - $anchoBarra / 2;
        $yBarra = $abajo - $alturaBarra;
        $sobre = $sobreUmbral[$i];
        $intensidad = $maxValor > 0 ? 0.32 + 0.4 * ($valor / $maxValor) : 0.32;
        $color = $sobre ? 'var(--s-confirmado)' : '#0E7A6E';
        $opacidad = $sobre ? min(1, 0.72 + 0.28 * ($valor / $maxValor)) : $intensidad;
      ?>
        <rect x="<?= round($xBarra, 1) ?>" y="<?= round($yBarra, 1) ?>" width="<?= round($anchoBarra, 1) ?>" height="<?= round(max(0, $alturaBarra), 1) ?>" rx="3" fill="<?= $color ?>" opacity="<?= round($opacidad, 2) ?>"/>
      <?php endforeach; ?>
    </g>

    <line x1="<?= $izq ?>" y1="<?= $abajo ?>" x2="<?= $der ?>" y2="<?= $abajo ?>" stroke="#D6DEE3" stroke-width="1"/>
    <g font-family="IBM Plex Mono" font-size="10" fill="#8A97A2" text-anchor="middle">
      <?php foreach ($semanas as $i => $semana): if (!isset($indicesEtiqueta[$i])) continue; ?>
        <text x="<?= round($xCentro($i), 1) ?>" y="222">SE <?= $semana['semana'] ?></text>
      <?php endforeach; ?>
    </g>
  </svg>
</div>
<div class="chart-legend">
  <div class="lg"><span class="sw" style="background:#0E7A6E;opacity:.55"></span> Casos notificados</div>
  <div class="lg"><span class="sw" style="background:var(--s-confirmado)"></span> Sobre el umbral esperado</div>
  <div class="lg"><span class="swl" style="background:#0E7A6E;opacity:.6;border-top:1px dashed #0E7A6E"></span> <?= e($curva['etiquetaBanda']) ?></div>
</div>
<?php if (!empty($curva['notaBanda'])): ?>
  <p style="padding:0 18px 16px;margin:0;font-size:11.5px;color:var(--faint)"><?= e($curva['notaBanda']) ?></p>
<?php endif; ?>
