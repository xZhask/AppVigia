<?php
/**
 * verificar_claves.php
 *
 * Audita que cada literal `'clave_...'` pasado a un resolvedor de
 * campos-por-clave.php ($campo(...), o un alias devuelto por
 * $resolvedorPara($cie10)) exista de verdad en campo_def para la
 * enfermedad correspondiente -- usando el mecanismo real (misma consulta
 * que usa la app), no un cruce de strings a mano.
 *
 * Nace de la auditoría del 2026-07-30: 62 claves de O95 y 1 de B05 llevaban
 * semanas devolviendo id => null en silencio porque cargar_fichas.php
 * recalculaba la clave desde la etiqueta en cada recarga (ver PENDIENTES.md,
 * ítem 7) y nada volvía a comparar el código migrado contra la base tras esa
 * recarga. $avisoClavesFaltantesCampos() existía para esto pero nunca se
 * invocaba desde ninguna vista (ver campos-por-clave.php).
 *
 * Cubre dos formas de uso:
 *   1. $resolvedorPara('CIE10') asignado a una variable ($x = $resolvedorPara('O95');)
 *      -- se detecta el binding automáticamente y se auditan todas las
 *      llamadas $x('clave') de ese mismo archivo.
 *   2. $campo('clave') ambiente, en archivos que SOLO se incluyen cuando la
 *      enfermedad activa ya es una específica (los partials a medida de
 *      O95, despachados por nombre de sección desde secciones-clinicas.php)
 *      -- la lista de esos archivos está a propósito en $AMBIENTE_SEGURO
 *      de este script, no inferida por análisis estático del despacho; si
 *      se agrega un partial a medida nuevo, hay que sumarlo acá.
 *   3. campoPorClave('clave') en public/js/*.js -- el JS resuelve contra el
 *      mapa [clave => name] que emite el servidor para la enfermedad activa,
 *      así que una clave obsoleta ahí falla en SILENCIO (devuelve '' y el
 *      querySelector no encuentra nada). La ficha se deduce del prefijo de la
 *      clave, y la deducción se comprueba contra campo_def.
 *
 * Uso:
 *   php verificar_claves.php                # imprime el reporte en Markdown
 *   php verificar_claves.php --json         # resultado crudo en JSON
 *
 * No modifica nada: solo lee vistas del disco y consulta la base.
 */

require __DIR__ . '/app/Core/Autoload.php';

use App\Models\CampoDef;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$modoJson = in_array('--json', $argv, true);

// Archivos que SOLO se incluyen (require) cuando $enfermedad ya es la que
// se indica -- ver secciones-clinicas.php (despacho por nombre de sección)
// y datos-fallecimiento-o95.php (siempre O95, $secciones[0] especial).
// Mantener esta lista es lo único que no se puede inferir por análisis
// estático de este script: la garantía vive en el `if` que hace el
// `require`, en otro archivo.
$AMBIENTE_SEGURO = [
    'app/Views/partials/antecedentes-patologicos-obstetricos-o95.php' => 'O95',
    'app/Views/partials/atencion-prenatal-o95.php' => 'O95',
    'app/Views/partials/causas-defuncion-o95.php' => 'O95',
    'app/Views/partials/complicaciones-o95.php' => 'O95',
    'app/Views/partials/datos-comunitarios-o95.php' => 'O95',
    'app/Views/partials/datos-fallecimiento-o95.php' => 'O95',
    'app/Views/partials/demoras-o95.php' => 'O95',
    'app/Views/partials/entorno-social-o95.php' => 'O95',
    'app/Views/partials/hospitalizaciones-o95.php' => 'O95',
    'app/Views/partials/parto-aborto-o95.php' => 'O95',
    'app/Views/partials/referencia-o95.php' => 'O95',
];

// secciones-clinicas.php es polimórfico ($campo ambiente = la enfermedad
// activa, siempre correcta) salvo estos literales, pinneados a mano a una
// sola ficha por una condición explícita en el propio archivo -- auditar
// solo esos, no todo lo demás que ahí se resuelve por variable de bucle.
$LITERALES_PINEADOS = [
    'app/Views/partials/secciones-clinicas.php' => [
        'B05' => ['b05_fecha_de_ultimo_dia_de_seguimiento_de_contactos'],
    ],
];

// Forma 3 del encabezado: claves usadas desde JavaScript vía campoPorClave().
// El servidor emite <script id="mapaCampos"> con [clave => name] de la
// enfermedad ACTIVA (campos-por-clave.php), así que una clave obsoleta acá no
// da error: campoPorClave() devuelve '' y el querySelector('[name=""]') que
// sigue devuelve null, con lo que el bloque entero queda en silencio -- el
// mismo modo de falla que motivó este script para las vistas, pero en el otro
// lado del alambre (ver bug de la tabla de viajes de B05, 2026-08-03).
//
// La ficha de cada clave se deduce del prefijo (cie10 en minúsculas y con
// '.' -> '_', p. ej. P35.0 -> p35_0_), que es la convención que sigue
// cargar_fichas.php; el script COMPRUEBA la deducción contra campo_def, así
// que no es una suposición ciega. Una clave que no case con ningún prefijo se
// reporta como faltante (cie10 '?') en vez de saltarse en silencio: si es una
// excepción legítima, hay que declararla acá con su ficha, igual que
// $AMBIENTE_SEGURO más arriba.
$CLAVES_JS_SIN_PREFIJO = [
    // 'clave_sin_prefijo_de_ficha' => 'CIE10',
];

$archivosJs = glob(__DIR__ . '/public/js/*.js');

// Prefijo -> cie10, de más largo a más corto para que gane el más específico
// (p. ej. 'a37_0_' antes que un hipotético 'a37_').
$prefijosFicha = [];
foreach (Enfermedad::activas() as $enf) {
    $prefijo = strtolower(str_replace('.', '_', (string) $enf['cie10'])) . '_';
    $prefijosFicha[$prefijo] = (string) $enf['cie10'];
}
uksort($prefijosFicha, fn($a, $b) => strlen($b) <=> strlen($a));

$archivosVistas = array_merge(
    glob(__DIR__ . '/app/Views/partials/*.php'),
    [__DIR__ . '/app/Views/nueva/index.php', __DIR__ . '/app/Views/fichas/editar.php']
);

$indiceCache = [];
function indicePorClave(string $cie10, array &$cache): ?array
{
    if (array_key_exists($cie10, $cache)) {
        return $cache[$cie10];
    }
    $enf = Enfermedad::buscarPorCie10($cie10);
    if (!$enf) {
        $cache[$cie10] = null;
        return null;
    }
    $indice = [];
    foreach (SeccionDef::porEnfermedad((int) $enf['id']) as $seccion) {
        foreach (CampoDef::porSeccion((int) $seccion['id']) as $c) {
            $indice[$c['clave']] ??= $c;
        }
    }
    $cache[$cie10] = $indice;
    return $indice;
}

$resultados = []; // ['archivo' => ['cie10' => .., 'clave' => .., 'ok' => bool]]

$raizNormalizada = str_replace('\\', '/', __DIR__);
foreach ($archivosVistas as $ruta) {
    $relativo = str_replace('\\', '/', $ruta);
    $relativo = ltrim(str_replace($raizNormalizada, '', $relativo), '/');
    $codigo = file_get_contents($ruta);

    // 1) Bindings $var = $resolvedorPara('CIE10');
    $bindings = [];
    if (preg_match_all('/\$(\w+)\s*=\s*\$resolvedorPara\(\s*\'([^\']+)\'\s*\)/', $codigo, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $bindings[$match[1]] = $match[2];
        }
    }
    foreach ($bindings as $var => $cie10) {
        if (preg_match_all('/\$' . preg_quote($var, '/') . '\(\s*\'([a-z0-9_.]+)\'\s*\)/', $codigo, $m2)) {
            foreach (array_unique($m2[1]) as $clave) {
                $resultados[] = ['archivo' => $relativo, 'cie10' => $cie10, 'clave' => $clave, 'origen' => 'resolvedorPara'];
            }
        }
    }

    // 1b) Uso directo sin variable intermedia: $resolvedorPara('CIE10')('clave')
    if (preg_match_all('/\$resolvedorPara\(\s*\'([^\']+)\'\s*\)\(\s*\'([a-z0-9_.]+)\'\s*\)/', $codigo, $m1b, PREG_SET_ORDER)) {
        foreach ($m1b as $match) {
            $resultados[] = ['archivo' => $relativo, 'cie10' => $match[1], 'clave' => $match[2], 'origen' => 'resolvedorPara-directo'];
        }
    }

    // 2) Ambiente seguro (lista explícita de arriba)
    if (isset($AMBIENTE_SEGURO[$relativo])) {
        $cie10 = $AMBIENTE_SEGURO[$relativo];
        if (preg_match_all('/\$campo\(\s*\'([a-z0-9_.]+)\'\s*\)/', $codigo, $m3)) {
            foreach (array_unique($m3[1]) as $clave) {
                $resultados[] = ['archivo' => $relativo, 'cie10' => $cie10, 'clave' => $clave, 'origen' => 'ambiente'];
            }
        }
    }

    // 3) Literales pineados a mano dentro de un archivo polimórfico
    if (isset($LITERALES_PINEADOS[$relativo])) {
        foreach ($LITERALES_PINEADOS[$relativo] as $cie10 => $claves) {
            foreach ($claves as $clave) {
                $resultados[] = ['archivo' => $relativo, 'cie10' => $cie10, 'clave' => $clave, 'origen' => 'pineado'];
            }
        }
    }
}

// 4) campoPorClave('clave') en public/js/*.js (ver $prefijosFicha arriba).
foreach ($archivosJs as $ruta) {
    $relativo = str_replace('\\', '/', $ruta);
    $relativo = ltrim(str_replace($raizNormalizada, '', $relativo), '/');
    $codigo = file_get_contents($ruta);

    if (!preg_match_all('/campoPorClave\(\s*\'([a-z0-9_.]+)\'\s*\)/', $codigo, $mJs)) {
        continue;
    }
    foreach (array_unique($mJs[1]) as $clave) {
        $cie10 = $CLAVES_JS_SIN_PREFIJO[$clave] ?? null;
        if ($cie10 === null) {
            foreach ($prefijosFicha as $prefijo => $cie) {
                if (str_starts_with($clave, $prefijo)) {
                    $cie10 = $cie;
                    break;
                }
            }
        }
        $resultados[] = [
            'archivo' => $relativo,
            'cie10'   => $cie10 ?? '?',
            'clave'   => $clave,
            'origen'  => 'js-campoPorClave',
        ];
    }
}

$faltantes = [];
$totalRevisadas = 0;
foreach ($resultados as $r) {
    $totalRevisadas++;
    $indice = indicePorClave($r['cie10'], $indiceCache);
    if ($indice === null || !isset($indice[$r['clave']])) {
        $faltantes[] = $r;
    }
}

if ($modoJson) {
    echo json_encode(['total_revisadas' => $totalRevisadas, 'faltantes' => $faltantes], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
    exit($faltantes ? 1 : 0);
}

echo "# REPORTE_VERIFICACION_CLAVES.md\n\n";
echo "Generado por `verificar_claves.php` el " . date('Y-m-d') . ".\n\n";
echo "Audita cada literal de clave pasado a \$campo()/\$resolvedorPara()(...) en las\n";
echo "vistas, y cada campoPorClave('...') en public/js/*.js, contra `campo_def`\n";
echo "real, usando el mismo mecanismo de resolución que usa la aplicación.\n";
echo "No modifica nada.\n\n";
echo "Claves revisadas: **{$totalRevisadas}**. Faltantes: **" . count($faltantes) . "**.\n\n";

if (!$faltantes) {
    echo "✅ Ninguna clave faltante.\n";
    exit(0);
}

echo "| Archivo | Ficha | Clave | Origen |\n|---|---|---|---|\n";
foreach ($faltantes as $f) {
    echo "| `{$f['archivo']}` | {$f['cie10']} | `{$f['clave']}` | {$f['origen']} |\n";
}
exit(1);
