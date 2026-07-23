<?php
/**
 * verificar_fichas.php
 *
 * Compara lo que hay en la base de datos (seccion_def / campo_def) contra el
 * manifiesto esperado (manifiesto_fichas.json, construido a partir de
 * DEFINICION_FICHAS.md y DEFINICION_FICHAS_B_C_D.md) y reporta diferencias
 * en ambas direcciones: faltantes (en el manifiesto pero no en la BD) y
 * sobrantes (en la BD pero no en el manifiesto — aquí caen los campos
 * inventados), además de tipos incorrectos.
 *
 * Uso:
 *   php verificar_fichas.php                # imprime el reporte en Markdown
 *   php verificar_fichas.php > REPORTE_VERIFICACION.md
 *   php verificar_fichas.php --json         # imprime el resultado crudo en JSON (para tooling)
 *
 * Desde RECARGA_FICHAS.md Fase 4, además de secciones/campos/tipos también
 * verifica catálogos: todo campo SELECT/MULTISELECT/GRUPO_SI_NO/CRONOLOGIA
 * debe tener catalogo_id, ese catálogo debe tener al menos un catalogo_item,
 * y sus opciones deben coincidir con las del manifiesto (ver INFORME_CARGADOR.md,
 * hallazgo A.2b).
 *
 * No modifica la base de datos ni las definiciones de ficha: solo lee y reporta.
 */

require __DIR__ . '/app/Core/Autoload.php';

use App\Core\Database;

$modoJson = in_array('--json', $argv, true);

$manifiestoPath = __DIR__ . '/manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($manifiestoPath), true);
if ($manifiesto === null) {
    fwrite(STDERR, "No se pudo leer/parsear {$manifiestoPath}: " . json_last_error_msg() . "\n");
    exit(1);
}

$pdo = Database::conexion();

// Mismos tipos que TIPOS_CON_OPCIONES en cargar_fichas.php: todo campo de
// estos tipos se inserta siempre con catalogo_id (nunca NULL).
const TIPOS_CON_CATALOGO = ['SELECT', 'MULTISELECT', 'GRUPO_SI_NO', 'CRONOLOGIA'];

// ----------------------------------------------------------------------
// Normalización para comparar etiquetas/nombres de forma tolerante a
// tildes, mayúsculas, signos de puntuación y espacios extra.
// ----------------------------------------------------------------------
function normalizar(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $mapa = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ];
    $texto = strtr($texto, $mapa);
    $texto = preg_replace('/[¿?¡!.,:;°ºª"\'\/()]/u', ' ', $texto);
    $texto = preg_replace('/\s+/u', ' ', $texto);
    return trim($texto);
}

function distanciaRelativa(string $a, string $b): float
{
    $maxLen = max(mb_strlen($a), mb_strlen($b));
    if ($maxLen === 0) {
        return 0.0;
    }
    return levenshtein($a, $b) / $maxLen;
}

/**
 * Empareja una lista de nombres esperados contra una lista de nombres
 * encontrados (ambas asociativas: índice => nombre normalizado), primero
 * por coincidencia exacta y luego por similitud aproximada.
 * Devuelve [emparejados => [indiceEsperado => indiceEncontrado], faltantes => [indices esperados], sobrantes => [indices encontrados]]
 */
function emparejar(array $esperados, array $encontrados, float $umbral = 0.30): array
{
    $emparejados = [];
    $encontradosLibres = $encontrados;

    // Paso 1: coincidencia exacta
    foreach ($esperados as $ie => $textoE) {
        foreach ($encontradosLibres as $if => $textoF) {
            if ($textoE === $textoF) {
                $emparejados[$ie] = $if;
                unset($encontradosLibres[$if]);
                break;
            }
        }
    }

    // Paso 2: coincidencia aproximada (contención o distancia de Levenshtein relativa)
    foreach ($esperados as $ie => $textoE) {
        if (isset($emparejados[$ie])) {
            continue;
        }
        $mejorIf = null;
        $mejorDist = null;
        foreach ($encontradosLibres as $if => $textoF) {
            if ($textoE === '' || $textoF === '') {
                continue;
            }
            $contenido = (mb_strpos($textoF, $textoE) !== false || mb_strpos($textoE, $textoF) !== false);
            $dist = distanciaRelativa($textoE, $textoF);
            if ($contenido || $dist <= $umbral) {
                $puntaje = $contenido ? 0.0 : $dist;
                if ($mejorDist === null || $puntaje < $mejorDist) {
                    $mejorDist = $puntaje;
                    $mejorIf = $if;
                }
            }
        }
        if ($mejorIf !== null) {
            $emparejados[$ie] = $mejorIf;
            unset($encontradosLibres[$mejorIf]);
        }
    }

    $faltantes = array_values(array_diff(array_keys($esperados), array_keys($emparejados)));
    $sobrantes = array_values(array_keys($encontradosLibres));

    return [
        'emparejados' => $emparejados,
        'faltantes' => $faltantes,
        'sobrantes' => $sobrantes,
    ];
}

// ----------------------------------------------------------------------
// Carga del estado actual de la BD: enfermedad + seccion_def + campo_def
// ----------------------------------------------------------------------
$enfermedades = $pdo->query('SELECT id, nombre, cie10 FROM enfermedad')->fetchAll();
$enfermedadPorCie10 = [];
foreach ($enfermedades as $e) {
    if ($e['cie10']) {
        $enfermedadPorCie10[$e['cie10']] = $e;
    }
}

$secciones = $pdo->query('SELECT id, enfermedad_id, nombre, orden FROM seccion_def ORDER BY enfermedad_id, orden, id')->fetchAll();
$seccionesPorEnfermedad = [];
foreach ($secciones as $s) {
    $seccionesPorEnfermedad[$s['enfermedad_id']][] = $s;
}

$campos = $pdo->query('SELECT id, seccion_id, clave, etiqueta, tipo, catalogo_id, orden FROM campo_def ORDER BY seccion_id, orden, id')->fetchAll();
$camposPorSeccion = [];
foreach ($campos as $c) {
    $camposPorSeccion[$c['seccion_id']][] = $c;
}

$catalogoItems = $pdo->query('SELECT catalogo_id, etiqueta, orden FROM catalogo_item ORDER BY catalogo_id, orden')->fetchAll();
$itemsPorCatalogo = [];
foreach ($catalogoItems as $it) {
    $itemsPorCatalogo[$it['catalogo_id']][] = $it['etiqueta'];
}

// ----------------------------------------------------------------------
// Comparación ficha por ficha
// ----------------------------------------------------------------------
$resultado = [];
$cie10Cubiertos = [];

foreach ($manifiesto['fichas'] as $cie10 => $fichaManifiesto) {
    $cie10Cubiertos[] = $cie10;

    $item = [
        'cie10' => $cie10,
        'enfermedad_manifiesto' => $fichaManifiesto['enfermedad'],
        'existe_en_bd' => false,
        'enfermedad_id' => null,
        'secciones_esperadas' => 0,
        'secciones_encontradas' => 0,
        'campos_esperados' => 0,
        'campos_encontrados' => 0,
        'secciones_faltantes' => [],
        'secciones_sobrantes' => [],
        'diferencias_por_seccion' => [],
        'estado' => 'OK',
    ];

    if (!isset($enfermedadPorCie10[$cie10])) {
        $item['estado'] = 'SIN_ENFERMEDAD_EN_BD';
        $resultado[] = $item;
        continue;
    }

    $enf = $enfermedadPorCie10[$cie10];
    $item['existe_en_bd'] = true;
    $item['enfermedad_id'] = (int) $enf['id'];
    $item['enfermedad_bd'] = $enf['nombre'];

    $seccionesBd = $seccionesPorEnfermedad[$enf['id']] ?? [];
    $item['secciones_encontradas'] = count($seccionesBd);

    // Secciones del manifiesto que sí representan contenido esperable en
    // campo_def (se excluyen las que son puramente informativas / viven en
    // tabla hija, marcadas con "campos": [] y una "_nota").
    $seccionesManifiesto = [];
    foreach ($fichaManifiesto['secciones'] as $idx => $s) {
        if (empty($s['campos']) && isset($s['_nota'])) {
            continue; // informativa: no se exige como seccion_def
        }
        $seccionesManifiesto[$idx] = $s;
    }
    $item['secciones_esperadas'] = count($seccionesManifiesto);
    foreach ($seccionesManifiesto as $s) {
        $item['campos_esperados'] += count($s['campos']);
    }
    foreach ($seccionesBd as $sb) {
        $item['campos_encontrados'] += count($camposPorSeccion[$sb['id']] ?? []);
    }

    $nombresEsperados = [];
    foreach ($seccionesManifiesto as $idx => $s) {
        $nombresEsperados[$idx] = normalizar($s['nombre']);
    }
    $nombresEncontrados = [];
    foreach ($seccionesBd as $i => $sb) {
        $nombresEncontrados[$i] = normalizar($sb['nombre']);
    }

    $match = emparejar($nombresEsperados, $nombresEncontrados, 0.30);

    foreach ($match['faltantes'] as $idxEsperado) {
        $item['secciones_faltantes'][] = $seccionesManifiesto[$idxEsperado]['nombre'];
    }
    foreach ($match['sobrantes'] as $idxEncontrado) {
        $item['secciones_sobrantes'][] = [
            'nombre' => $seccionesBd[$idxEncontrado]['nombre'],
            'seccion_id' => (int) $seccionesBd[$idxEncontrado]['id'],
            'num_campos' => count($camposPorSeccion[$seccionesBd[$idxEncontrado]['id']] ?? []),
        ];
    }

    // Para las secciones emparejadas, comparar campo por campo.
    foreach ($match['emparejados'] as $idxEsperado => $idxEncontrado) {
        $seccionManifiesto = $seccionesManifiesto[$idxEsperado];
        $seccionBd = $seccionesBd[$idxEncontrado];
        $camposBd = $camposPorSeccion[$seccionBd['id']] ?? [];

        $etiquetasEsperadas = [];
        foreach ($seccionManifiesto['campos'] as $ic => $c) {
            $etiquetasEsperadas[$ic] = normalizar($c['etiqueta']);
        }
        $etiquetasEncontradas = [];
        foreach ($camposBd as $ic => $c) {
            $etiquetasEncontradas[$ic] = normalizar($c['etiqueta']);
        }

        $matchCampos = emparejar($etiquetasEsperadas, $etiquetasEncontradas, 0.30);

        $diffSeccion = [
            'seccion' => $seccionManifiesto['nombre'],
            'seccion_bd' => $seccionBd['nombre'],
            'campos_faltantes' => [],
            'campos_sobrantes' => [],
            'tipos_incorrectos' => [],
            'catalogos_incorrectos' => [],
        ];

        foreach ($matchCampos['faltantes'] as $ic) {
            $diffSeccion['campos_faltantes'][] = [
                'etiqueta' => $seccionManifiesto['campos'][$ic]['etiqueta'],
                'tipo_esperado' => $seccionManifiesto['campos'][$ic]['tipo'],
            ];
        }
        foreach ($matchCampos['sobrantes'] as $ic) {
            $diffSeccion['campos_sobrantes'][] = [
                'clave' => $camposBd[$ic]['clave'],
                'etiqueta' => $camposBd[$ic]['etiqueta'],
                'tipo' => $camposBd[$ic]['tipo'],
                'campo_id' => (int) $camposBd[$ic]['id'],
            ];
        }
        foreach ($matchCampos['emparejados'] as $ic => $if) {
            $campoManifiesto = $seccionManifiesto['campos'][$ic];
            $campoBd = $camposBd[$if];
            $tipoEsperado = $campoManifiesto['tipo'];
            $tipoEncontrado = $campoBd['tipo'];
            if ($tipoEsperado !== $tipoEncontrado) {
                $diffSeccion['tipos_incorrectos'][] = [
                    'etiqueta' => $campoManifiesto['etiqueta'],
                    'etiqueta_bd' => $campoBd['etiqueta'],
                    'clave_bd' => $campoBd['clave'],
                    'tipo_esperado' => $tipoEsperado,
                    'tipo_encontrado' => $tipoEncontrado,
                    'campo_id' => (int) $campoBd['id'],
                ];
                continue; // tipo ya está mal: no tiene sentido auditar su catálogo
            }

            if (!in_array($tipoEsperado, TIPOS_CON_CATALOGO, true)) {
                continue;
            }

            $catalogoId = $campoBd['catalogo_id'];
            $opcionesEsperadas = $campoManifiesto['opciones'] ?? [];

            if ($catalogoId === null) {
                $diffSeccion['catalogos_incorrectos'][] = [
                    'etiqueta' => $campoManifiesto['etiqueta'],
                    'clave_bd' => $campoBd['clave'],
                    'campo_id' => (int) $campoBd['id'],
                    'problema' => 'SIN_CATALOGO',
                    'detalle' => 'catalogo_id es NULL para un campo de tipo ' . $tipoEsperado . '.',
                ];
                continue;
            }

            $opcionesBd = $itemsPorCatalogo[$catalogoId] ?? [];
            if (!$opcionesBd) {
                $diffSeccion['catalogos_incorrectos'][] = [
                    'etiqueta' => $campoManifiesto['etiqueta'],
                    'clave_bd' => $campoBd['clave'],
                    'campo_id' => (int) $campoBd['id'],
                    'problema' => 'CATALOGO_VACIO',
                    'detalle' => "catalogo_id={$catalogoId} no tiene ningún catalogo_item.",
                ];
                continue;
            }

            $esperadasNorm = [];
            foreach ($opcionesEsperadas as $io => $op) {
                $esperadasNorm[$io] = normalizar($op);
            }
            $encontradasNorm = [];
            foreach ($opcionesBd as $io => $op) {
                $encontradasNorm[$io] = normalizar($op);
            }
            $matchOpciones = emparejar($esperadasNorm, $encontradasNorm, 0.30);

            if ($matchOpciones['faltantes'] || $matchOpciones['sobrantes']) {
                $opcionesFaltantes = array_map(fn($io) => $opcionesEsperadas[$io], $matchOpciones['faltantes']);
                $opcionesSobrantes = array_map(fn($io) => $opcionesBd[$io], $matchOpciones['sobrantes']);
                $diffSeccion['catalogos_incorrectos'][] = [
                    'etiqueta' => $campoManifiesto['etiqueta'],
                    'clave_bd' => $campoBd['clave'],
                    'campo_id' => (int) $campoBd['id'],
                    'catalogo_id' => (int) $catalogoId,
                    'problema' => 'OPCIONES_NO_COINCIDEN',
                    'opciones_faltantes' => $opcionesFaltantes,
                    'opciones_sobrantes' => $opcionesSobrantes,
                ];
            }
        }

        if ($diffSeccion['campos_faltantes'] || $diffSeccion['campos_sobrantes'] || $diffSeccion['tipos_incorrectos'] || $diffSeccion['catalogos_incorrectos']) {
            $item['diferencias_por_seccion'][] = $diffSeccion;
        }
    }

    $tieneDiferencias = $item['secciones_faltantes'] || $item['secciones_sobrantes'] || $item['diferencias_por_seccion'];
    $item['estado'] = $tieneDiferencias ? 'CON_DIFERENCIAS' : 'OK';

    $resultado[] = $item;
}

// Enfermedades en la BD que no tienen entrada en el manifiesto (p.ej. Dengue,
// stub fuera de spec conocido) — se listan aparte, sin comparación de detalle.
$sinManifiesto = [];
foreach ($enfermedades as $e) {
    if (!$e['cie10'] || !in_array($e['cie10'], $cie10Cubiertos, true)) {
        $sinManifiesto[] = $e;
    }
}

if ($modoJson) {
    echo json_encode([
        'fichas' => $resultado,
        'enfermedades_sin_manifiesto' => $sinManifiesto,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n";
    exit(0);
}

// ----------------------------------------------------------------------
// Salida en Markdown
// ----------------------------------------------------------------------
$fechaHoy = date('Y-m-d');
echo "# REPORTE_VERIFICACION.md\n\n";
echo "Generado por `verificar_fichas.php` el {$fechaHoy} comparando la base de datos contra `manifiesto_fichas.json`.\n\n";
echo "No se modificó ninguna definición de ficha ni la base de datos: esta es una corrida de solo lectura.\n\n";
echo "**Metodología.** Las secciones y campos se emparejan por nombre/etiqueta normalizada (sin tildes, mayúsculas ni signos de puntuación), primero por coincidencia exacta y luego por similitud aproximada (contención o distancia de Levenshtein relativa ≤ 0.30). Para los campos SELECT/MULTISELECT/GRUPO_SI_NO/CRONOLOGIA (los mismos tipos que `cargar_fichas.php` exige con catálogo), además se verifica: que `catalogo_id` no sea NULL, que ese catálogo tenga al menos un `catalogo_item`, y que sus opciones (emparejadas con la misma normalización) coincidan con las del manifiesto — desde RECARGA_FICHAS.md Fase 4 (antes era una limitación conocida, ver INFORME_CARGADOR.md hallazgo A.2b). Limitación que sigue vigente: si una ficha consolida en la BD varias secciones del manifiesto en una sola (o al revés), puede reportarse una 'sección faltante' que en realidad solo cambió de nombre/agrupación — revisar el detalle antes de asumir contenido perdido.\n\n";
echo "---\n\n";

echo "## Resumen\n\n";
echo "| Ficha (CIE-10) | Enfermedad | Secciones esp. / enc. | Campos esp. / enc. | Estado |\n";
echo "|---|---|---|---|---|\n";
foreach ($resultado as $item) {
    $estadoTexto = match ($item['estado']) {
        'OK' => '✅ OK',
        'CON_DIFERENCIAS' => '⚠️ Con diferencias',
        'SIN_ENFERMEDAD_EN_BD' => '❌ No existe en BD',
        default => $item['estado'],
    };
    $secc = $item['existe_en_bd'] ? "{$item['secciones_esperadas']} / {$item['secciones_encontradas']}" : '—';
    $camp = $item['existe_en_bd'] ? "{$item['campos_esperados']} / {$item['campos_encontrados']}" : '—';
    printf(
        "| %s | %s | %s | %s | %s |\n",
        $item['cie10'],
        $item['enfermedad_manifiesto'],
        $secc,
        $camp,
        $estadoTexto
    );
}
echo "\n";

if ($sinManifiesto) {
    echo "**Enfermedades en la BD sin entrada en el manifiesto** (no auditadas por esta corrida):\n\n";
    foreach ($sinManifiesto as $e) {
        echo "- `{$e['cie10']}` — {$e['nombre']} (id={$e['id']})\n";
    }
    echo "\n";
}

echo "---\n\n";
echo "## Detalle por ficha\n\n";

foreach ($resultado as $item) {
    echo "### {$item['enfermedad_manifiesto']} (`{$item['cie10']}`)\n\n";

    if (!$item['existe_en_bd']) {
        echo "> ❌ No existe ninguna enfermedad con este CIE-10 en la base de datos.\n\n";
        continue;
    }

    if ($item['estado'] === 'OK') {
        echo "✅ Sin diferencias detectadas (secciones y campos coinciden con el manifiesto).\n\n";
        continue;
    }

    if ($item['secciones_faltantes']) {
        echo "**Secciones faltantes** (están en el manifiesto pero no en la BD):\n\n";
        foreach ($item['secciones_faltantes'] as $nombre) {
            echo "- {$nombre}\n";
        }
        echo "\n";
    }

    if ($item['secciones_sobrantes']) {
        echo "**Secciones sobrantes** (están en la BD pero no en el manifiesto — revisar si son campos inventados):\n\n";
        foreach ($item['secciones_sobrantes'] as $s) {
            echo "- `{$s['nombre']}` (seccion_def.id={$s['seccion_id']}, {$s['num_campos']} campo(s))\n";
        }
        echo "\n";
    }

    foreach ($item['diferencias_por_seccion'] as $diff) {
        echo "**Sección: {$diff['seccion']}**";
        if (normalizar($diff['seccion']) !== normalizar($diff['seccion_bd'])) {
            echo " _(en BD: \"{$diff['seccion_bd']}\")_";
        }
        echo "\n\n";

        if ($diff['campos_faltantes']) {
            echo "- Campos faltantes:\n";
            foreach ($diff['campos_faltantes'] as $c) {
                echo "  - «{$c['etiqueta']}» — se esperaba tipo `{$c['tipo_esperado']}`, no está en la BD\n";
            }
        }
        if ($diff['campos_sobrantes']) {
            echo "- Campos sobrantes (posible campo inventado):\n";
            foreach ($diff['campos_sobrantes'] as $c) {
                echo "  - «{$c['etiqueta']}» (clave `{$c['clave']}`, campo_def.id={$c['campo_id']}) — tipo `{$c['tipo']}`, no está en el manifiesto\n";
            }
        }
        if ($diff['tipos_incorrectos']) {
            echo "- Tipos incorrectos:\n";
            foreach ($diff['tipos_incorrectos'] as $c) {
                echo "  - «{$c['etiqueta']}» (campo_def.id={$c['campo_id']}, clave `{$c['clave_bd']}`) — se esperaba `{$c['tipo_esperado']}`, se encontró `{$c['tipo_encontrado']}`\n";
            }
        }
        if ($diff['catalogos_incorrectos']) {
            echo "- Catálogos incorrectos:\n";
            foreach ($diff['catalogos_incorrectos'] as $c) {
                if ($c['problema'] === 'SIN_CATALOGO') {
                    echo "  - «{$c['etiqueta']}» (campo_def.id={$c['campo_id']}, clave `{$c['clave_bd']}`) — {$c['detalle']}\n";
                } elseif ($c['problema'] === 'CATALOGO_VACIO') {
                    echo "  - «{$c['etiqueta']}» (campo_def.id={$c['campo_id']}, clave `{$c['clave_bd']}`) — {$c['detalle']}\n";
                } else {
                    echo "  - «{$c['etiqueta']}» (campo_def.id={$c['campo_id']}, clave `{$c['clave_bd']}`, catalogo_id={$c['catalogo_id']}):\n";
                    if ($c['opciones_faltantes']) {
                        echo "    - Opciones faltantes (en el manifiesto, no en el catálogo): " . implode(', ', $c['opciones_faltantes']) . "\n";
                    }
                    if ($c['opciones_sobrantes']) {
                        echo "    - Opciones sobrantes (en el catálogo, no en el manifiesto): " . implode(', ', $c['opciones_sobrantes']) . "\n";
                    }
                }
            }
        }
        echo "\n";
    }
}
