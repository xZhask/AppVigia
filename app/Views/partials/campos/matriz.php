<?php
$nombreCampo = 'campo_' . $campo['id'];
$config = json_decode($campo['config'] ?? '{}', true);
$filas = $config['filas'] ?? [];
$columnas = $config['columnas'] ?? [];
$valores = is_array($valor) ? $valor : [];

// Palabras que delatan una columna de entrada libre (fecha, dosis, una
// cantidad...) en vez de un código de opción cerrada. Usadas por columna,
// no por fila entera (PENDIENTES.md, Fases 3-6, ítem A) -- así una sola
// columna de fecha ya no arrastra a modo texto libre a las demás columnas
// de la misma fila (bug real en Z21 "Pruebas diagnósticas" y en Y59.0
// "Signos y síntomas": ambas tienen una columna "Fecha..." junto a otras
// que no lo son).
$palabrasLibres = ['FECHA', 'DOSIS', 'DIAS', 'DÍAS', 'DIA', 'DÍA', 'N.°', 'CANTIDAD', 'MM'];

// Una columna es candidata a radio exclusivo (como DIM/AUS/NORM/IGN o
// SI/NO/IGN/PROXIMAL/DISTAL, las 5 matrices de A80 que sí deben seguir
// siendo 100% radio) solo si, además de no matchear $palabrasLibres y no
// ser larga, está escrita TODA en mayúsculas en el manifiesto. Ese
// requisito extra es lo que evita que una etiqueta de columna común y
// corriente -- "Total", "Descripción", "Código CIE-10", "Cara" -- cuele
// como si fuera una opción exclusiva más: en las 24 fichas actuales, la
// convención de mayúsculas SIEMPRE marca un código cerrado real (nunca al
// revés), así que es una señal fiable, no una coincidencia.
$columnaEsRadio = function (string $col) use ($palabrasLibres): bool {
    $col = trim($col);
    if ($col === '' || mb_strlen($col) > 20 || mb_strtoupper($col) !== $col) {
        return false;
    }
    foreach ($palabrasLibres as $palabra) {
        if (str_contains($col, $palabra)) {
            return false;
        }
    }
    return true;
};
$esFecha = fn (string $texto): bool => str_contains(mb_strtoupper($texto), 'FECHA');

// Elegibilidad de radio por columna (misma para todas las filas, ya que
// depende solo del encabezado de columna).
$columnasRadio = [];
foreach ($columnas as $cIdx => $col) {
    if ($columnaEsRadio((string) $col)) {
        $columnasRadio[$cIdx] = true;
    }
}

// Si una de las columnas exclusivas es "SI" (p.ej. SI/NO/DESCONOCIDO),
// las columnas libres de esa misma fila (p.ej. "Fecha de manifestación")
// solo tienen sentido cuando la fila está marcada como SI -- se
// deshabilitan en las demás filas (PENDIENTES.md, observación P35.0
// "Cuadro clínico — manifestaciones"). $colSiIdx es null cuando el campo
// no tiene una columna "SI" (a37_0/b55/z21/a50/y59_0: sin gate, sin cambio).
$colSiIdx = null;
foreach ($columnasRadio as $cIdx => $_) {
    if (in_array(mb_strtoupper(trim((string) $columnas[$cIdx])), ['SI', 'SÍ'], true)) {
        $colSiIdx = $cIdx;
        break;
    }
}

// Tres modos por CAMPO:
//  - 'radio':   todas las columnas son exclusivas -> idéntico a como
//               funcionaba siempre (A80: Localización de la parálisis,
//               Fuerza/Tono muscular, Reflejos, Signos meníngeos).
//               Formato de valor SIN CAMBIOS: $valores[$fIdx] = "<columna
//               elegida>".
//  - 'libre':   ninguna columna es exclusiva -> idéntico a como
//               funcionaba siempre, salvo que texto-vs-fecha ahora se
//               decide por columna (o por fila, ver $filaEsFecha más
//               abajo), no solo por fila. Formato SIN CAMBIOS:
//               $valores[$fIdx][$cIdx] = "...".
//  - 'hibrido': mezcla real de columnas exclusivas y libres en la misma
//               fila. Las exclusivas comparten un único radio por fila
//               bajo la clave reservada '_radio' (no colisiona con los
//               índices enteros 0..n-1 de columna); las libres siguen
//               usando $valores[$fIdx][$cIdx]. Ningún campo de las 24
//               fichas actuales cae acá (verificado) -- es la capacidad
//               nueva que pide el ítem A, sin datos guardados que migrar.
if (empty($columnas) || empty($columnasRadio)) {
    $modo = 'libre';
} elseif (count($columnasRadio) === count($columnas)) {
    $modo = 'radio';
} else {
    $modo = 'hibrido';
}

// Si TODAS las filas son solo un número (p.ej. A37.0 "Antibióticos
// recibidos": "1"/"2"/"3", filas repetibles sin etiqueta propia -- el PDF
// tampoco trae esa columna, son 3 renglones en blanco), la columna
// "Parámetro / Evaluado" no aporta nada y solo ocupa espacio: se omite
// entera. El resto de matrices (A80, B01/B26 "Contactos por lugar", etc.)
// sí tienen filas descriptivas y conservan la columna sin cambios.
$filasSonSoloIndice = !empty($filas) && !array_filter($filas, fn ($f) => !preg_match('/^\d+$/', trim((string) $f)));
?>
<div class="field wide grupo-si-no-field">
  <label class="fl"><?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?></label>
  <div style="overflow-x: auto; background: var(--surface); border: 1px solid var(--line); border-radius: 9px; padding: 1px; margin-top: 4px;">
    <table style="width: 100%; border-collapse: collapse; min-width: 480px;">
      <thead>
        <tr>
          <?php if (!$filasSonSoloIndice): ?>
          <th style="font-size: 10.5px; text-transform: uppercase; color: var(--faint); padding: 8px 12px; border-bottom: 1px solid var(--line); text-align: left;">Parámetro / Evaluado</th>
          <?php endif; ?>
          <?php foreach ($columnas as $col): ?>
            <th style="font-size: 10.5px; text-transform: uppercase; color: var(--faint); padding: 8px 12px; border-bottom: 1px solid var(--line); text-align: center; min-width: 90px;"><?= e($col) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($filas as $fIdx => $fila):
          $filaEsFecha = $esFecha((string) $fila);
          $valFilaRadio = null;
          if ($modo === 'radio') {
              $valFilaRadio = $valores[$fIdx] ?? ($valores[(string) $fIdx] ?? '');
          } elseif ($modo === 'hibrido') {
              $valFilaRadio = $valores[$fIdx]['_radio'] ?? '';
          }
          $filaEsSi = $colSiIdx !== null && (string) $valFilaRadio === (string) $columnas[$colSiIdx];
        ?>
          <tr class="grupo-si-no-row">
            <?php if (!$filasSonSoloIndice): ?>
            <td style="font-size: 12px; font-weight: 500; color: var(--ink); padding: 8px 12px; border-bottom: 1px solid var(--line-2);"><?= e($fila) ?></td>
            <?php endif; ?>
            <?php foreach ($columnas as $cIdx => $col): ?>
              <td style="padding: 4px 8px; border-bottom: 1px solid var(--line-2); text-align: center;">
                <?php if (isset($columnasRadio[$cIdx])):
                  $isSel = (string) $valFilaRadio === (string) $col || (string) $valFilaRadio === (string) $cIdx;
                  $nombreRadio = $modo === 'radio' ? "{$nombreCampo}[{$fIdx}]" : "{$nombreCampo}[{$fIdx}][_radio]";
                ?>
                  <div class="seg" style="display:inline-flex; width: 100%;">
                    <label class="seg-label <?= $isSel ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer; padding: 4px 8px; border-radius:6px;" title="<?= e($col) ?>">
                      <input type="radio" name="<?= e($nombreRadio) ?>" value="<?= e($col) ?>" class="sr-only" <?= $isSel ? 'checked' : '' ?>>
                      <?= e($col) ?>
                    </label>
                  </div>
                <?php else:
                  $esFechaCelda = $esFecha((string) $col) || $filaEsFecha;
                  $gateSi = $colSiIdx !== null;
                  $deshabilitada = $gateSi && !$filaEsSi;
                ?>
                  <input type="<?= $esFechaCelda ? 'date' : 'text' ?>" name="<?= e($nombreCampo) ?>[<?= $fIdx ?>][<?= $cIdx ?>]" value="<?= e($valores[$fIdx][$cIdx] ?? '') ?>" placeholder="<?= $esFechaCelda ? '' : '—' ?>" <?= $esFechaCelda ? 'max="' . date('Y-m-d') . '"' : '' ?> <?= $gateSi ? 'data-gated-por-si="1"' : '' ?> <?= $deshabilitada ? 'disabled' : '' ?> style="width: 100%; border: 1px solid var(--line); border-radius: 6px; padding: 5px 8px; font-size: 12px; background: var(--paper); color: var(--ink); outline: none;<?= $deshabilitada ? ' opacity:.55; cursor:not-allowed;' : '' ?>">
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
