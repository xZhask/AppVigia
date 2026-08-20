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

// Grupos de columnas radio (2026-08-19, A44 "Lesiones eruptivas: Sangrante"
// / "Ganglios linfáticos: Móviles y Dolorosos" -- pedido del usuario):
// "grupos_columnas": {"Sangrante": ["SI","NO"]} en el config del campo_def
// fusiona 2+ columnas radio-elegibles en UNA sola columna visual con un
// único .seg (radios pegados, mismo look que cualquier otro Sí/No de la
// ficha) en vez de una columna de tabla por cada opción. Cada grupo
// nombrado guarda su propio valor bajo una subclave propia
// ($valores[$fIdx]['_radio_<slug>']) -- así 2+ preguntas Sí/No
// INDEPENDIENTES pueden convivir en la misma fila (Móviles Y Dolorosos a la
// vez), a diferencia del `_radio` compartido de modo 'hibrido' de más abajo
// (pensado para "elegir una entre N", no para "N preguntas independientes").
// La búsqueda por etiqueta consume el primer índice radio-elegible aún no
// asignado -- soporta etiquetas repetidas ("SI"/"NO" en 2 grupos distintos)
// sin ambigüedad, siempre que los grupos se declaren en el mismo orden
// izquierda-a-derecha en que aparecen sus columnas en el manifiesto.
// Columnas radio NO declaradas en ningún grupo siguen compartiendo el
// `_radio` legado de siempre -- las 6 matrices con radio que ya existían no
// declaran "grupos_columnas", así que cada una queda con 1 solo miembro por
// columna y sin "clave_valor" propia: mismo HTML y mismo formato de datos
// que antes, cero cambio de comportamiento. Ver memoria
// matriz_no_soporta_booleanos_independientes_por_fila.
$slugGrupo = function (string $texto): string {
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $mapa = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'];
    $texto = strtr($texto, $mapa);
    $texto = preg_replace('/[^a-z0-9]+/', '_', $texto);
    return trim($texto, '_');
};
$gruposColumnas = $config['grupos_columnas'] ?? [];
$grupoDeColumna = [];
$indicesGrupoYaAsignados = [];
foreach ($gruposColumnas as $nombreGrupo => $miembros) {
    foreach ((array) $miembros as $miembro) {
        foreach ($columnas as $cIdx => $col) {
            if ((string) $col === (string) $miembro && !isset($indicesGrupoYaAsignados[$cIdx]) && isset($columnasRadio[$cIdx])) {
                $grupoDeColumna[$cIdx] = $nombreGrupo;
                $indicesGrupoYaAsignados[$cIdx] = true;
                break;
            }
        }
    }
}
$columnasVisuales = [];
$gruposYaAgregados = [];
foreach ($columnas as $cIdx => $col) {
    if (!isset($columnasRadio[$cIdx])) {
        $columnasVisuales[] = ['nombre' => $col, 'libre' => true, 'idx' => $cIdx];
        continue;
    }
    $grupo = $grupoDeColumna[$cIdx] ?? null;
    if ($grupo !== null) {
        if (isset($gruposYaAgregados[$grupo])) {
            continue;
        }
        $gruposYaAgregados[$grupo] = true;
        $columnasVisuales[] = [
            'nombre' => $grupo,
            'libre' => false,
            'miembros' => array_keys($grupoDeColumna, $grupo, true),
            'clave_valor' => '_radio_' . $slugGrupo($grupo),
        ];
    } else {
        $columnasVisuales[] = ['nombre' => $col, 'libre' => false, 'miembros' => [$cIdx]];
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

// Mismo concepto que $colSiIdx pero al revés: en vez de UNA columna que
// habilita (SI) y todas las demás deshabilitan, esta es la ÚNICA columna
// que deshabilita y todas las demás habilitan. Para radios que no son
// binarios SI/NO sino un resultado con un estado neutro explícito (A97
// "Pruebas de laboratorio", 2026-08-14: No realizado/Positivo/Negativo --
// ni Positivo ni Negativo se llaman "SI", pero ambos deben habilitar
// "Fecha de resultado"; solo "No realizado" la deshabilita). Solo se busca
// si no hay columna "SI" (ambos gates son mutuamente excluyentes por
// campo).
$colNegativoIdx = null;
if ($colSiIdx === null) {
    foreach ($columnasRadio as $cIdx => $_) {
        if (in_array(mb_strtoupper(trim((string) $columnas[$cIdx])), ['NO REALIZADO', 'NO_REALIZADO'], true)) {
            $colNegativoIdx = $cIdx;
            break;
        }
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
          <th style="font-size: 10.5px; text-transform: uppercase; color: var(--faint); padding: 8px 12px; border-bottom: 1px solid var(--line); text-align: left;">Parámetro</th>
          <?php endif; ?>
          <?php foreach ($columnasVisuales as $cv): ?>
            <th style="font-size: 10.5px; text-transform: uppercase; color: var(--faint); padding: 8px 12px; border-bottom: 1px solid var(--line); text-align: center; min-width: 90px;"><?= e($cv['nombre']) ?></th>
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
          // Si el campo declara una columna "negativa" explícita (No
          // realizado) y esta fila todavía no tiene valor guardado, esa es
          // su selección por defecto (2026-08-14, pedido del usuario): una
          // fila sin marcar ya no se confunde con "no se llenó todavía" --
          // si el usuario no la toca, se guarda explícitamente como "no
          // realizada" al enviar el formulario.
          if ($valFilaRadio === '' && $colNegativoIdx !== null) {
              $valFilaRadio = (string) $columnas[$colNegativoIdx];
          }
          $filaEsSi = $colSiIdx !== null && (string) $valFilaRadio === (string) $columnas[$colSiIdx];
          $filaEsNegativo = $colNegativoIdx !== null && (string) $valFilaRadio === (string) $columnas[$colNegativoIdx];
        ?>
          <tr class="grupo-si-no-row">
            <?php if (!$filasSonSoloIndice): ?>
            <td style="font-size: 12px; font-weight: 500; color: var(--ink); padding: 8px 12px; border-bottom: 1px solid var(--line-2);"><?= e($fila) ?></td>
            <?php endif; ?>
            <?php foreach ($columnasVisuales as $cv): ?>
              <td style="padding: 4px 8px; border-bottom: 1px solid var(--line-2); text-align: center;">
                <?php if (!$cv['libre']):
                  $claveValorGrupo = $cv['clave_valor'] ?? null;
                  $valCeldaRadio = $claveValorGrupo !== null ? ($valores[$fIdx][$claveValorGrupo] ?? '') : $valFilaRadio;
                ?>
                  <div class="seg" style="display:inline-flex; width: 100%;">
                    <?php foreach ($cv['miembros'] as $cIdx):
                      $col = $columnas[$cIdx];
                      $isSel = (string) $valCeldaRadio === (string) $col || (string) $valCeldaRadio === (string) $cIdx;
                      $nombreRadio = $claveValorGrupo !== null
                          ? "{$nombreCampo}[{$fIdx}][{$claveValorGrupo}]"
                          : ($modo === 'radio' ? "{$nombreCampo}[{$fIdx}]" : "{$nombreCampo}[{$fIdx}][_radio]");
                    ?>
                      <label class="seg-label <?= $isSel ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer; padding: 4px 8px; border-radius:6px;" title="<?= e($col) ?>">
                        <input type="radio" name="<?= e($nombreRadio) ?>" value="<?= e($col) ?>" class="sr-only" <?= $isSel ? 'checked' : '' ?>>
                        <?= e($col) ?>
                      </label>
                    <?php endforeach; ?>
                  </div>
                <?php else:
                  $cIdx = $cv['idx'];
                  $col = $cv['nombre'];
                  $esFechaCelda = $esFecha((string) $col) || $filaEsFecha;
                  // "sin_gate_libres" (2026-08-19, A44 "Lesiones eruptivas"):
                  // opt-in para cuando la columna SI/No-realizado es una
                  // pregunta independiente (ej. "Sangrante") que no debe
                  // condicionar las columnas libres de la misma fila (ej.
                  // conteos por localización) -- a diferencia de P35.0/A97
                  // donde SÍ hay una relación real que amerita el gate. Ver
                  // memoria matriz_no_soporta_booleanos_independientes_por_fila.
                  $sinGateLibres = !empty($config['sin_gate_libres']);
                  $gateSi = $colSiIdx !== null && !$sinGateLibres;
                  $gateNegativo = $colNegativoIdx !== null && !$sinGateLibres;
                  // A97 "Pruebas de laboratorio" (2026-08-14): matrices con
                  // radio no-binario (No realizado/Positivo/Negativo) gatean
                  // la(s) columna(s) libre(s) -- "Fecha de resultado" --
                  // habilitadas con CUALQUIER opción salvo la "negativa"
                  // explícita (colNegativoIdx) o sin marcar. Fallback final
                  // (columnasRadio sin "SI" ni "No realizado"): cualquier
                  // selección habilita, igual que antes. Auditado contra los
                  // 6 campos MATRIZ con columnas radio que ya existían (A80
                  // x5: 100% radio, sin columnas libres, no corre; P35.0 x4:
                  // siempre tiene columna "SI", sigue ese camino sin cambio)
                  // -- cero impacto en las 23 fichas restantes.
                  $gateCualquierRadio = !$gateSi && !$gateNegativo && !empty($columnasRadio) && !$sinGateLibres;
                  $deshabilitada = $gateSi
                      ? !$filaEsSi
                      : ($gateNegativo
                          ? ($filaEsNegativo || (string) $valFilaRadio === '')
                          : ($gateCualquierRadio && (string) $valFilaRadio === ''));
                  $tieneGate = $gateSi || $gateNegativo || $gateCualquierRadio;
                ?>
                  <input type="<?= $esFechaCelda ? 'date' : 'text' ?>" name="<?= e($nombreCampo) ?>[<?= $fIdx ?>][<?= $cIdx ?>]" value="<?= e($valores[$fIdx][$cIdx] ?? '') ?>" placeholder="<?= $esFechaCelda ? '' : '—' ?>" <?= $esFechaCelda ? 'max="' . date('Y-m-d') . '"' : '' ?> <?= $tieneGate ? 'data-gated-por-si="1"' : '' ?> <?= $deshabilitada ? 'disabled' : '' ?> style="width: 100%; border: 1px solid var(--line); border-radius: 6px; padding: 5px 8px; font-size: 12px; background: var(--paper); color: var(--ink); outline: none;<?= $deshabilitada ? ' opacity:.55; cursor:not-allowed;' : '' ?>">
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
