<?php
/**
 * cargar_fichas.php
 *
 * Cargador único e idempotente de definiciones de ficha (seccion_def /
 * campo_def / catalogo / catalogo_item) a partir de manifiesto_fichas.json.
 * Reemplaza a los ~12 SQL sueltos de sql/*lote*.sql (ver INFORME_CARGADOR.md).
 *
 * Requisitos que implementa (RECARGA_FICHAS.md, Fase 2):
 *   1. Idempotente por diseño: por cada enfermedad, dentro de una
 *      transacción, borra sus seccion_def (cascada a campo_def por la FK)
 *      y vuelve a insertar desde el manifiesto. Correrlo dos veces deja el
 *      mismo resultado.
 *   2. Falla dura: si un campo SELECT/MULTISELECT/GRUPO_SI_NO/CRONOLOGIA no
 *      trae "opciones" en el manifiesto, o si un "tipo" no es reconocido,
 *      el script aborta con excepción ANTES de escribir nada (se valida
 *      todo el manifiesto primero). Nunca inserta con catalogo_id NULL.
 *   3. Convención de clave única: "{cie10}_{slug(etiqueta)}", igual para
 *      las 23 fichas (sin sufijos hexadecimales ni por lote).
 *   4. Protege datos capturados: si una enfermedad tiene caso_valor
 *      asociados a sus campo_def actuales, NO se borra — se reporta y hay
 *      que confirmar explícitamente con --confirmar-perdida=<CIE10>.
 *   5. Catálogos: reutiliza un catálogo existente si su lista de opciones
 *      ya existe (por contenido, no por nombre), en vez de duplicarlo por
 *      ficha. Los catálogos genéricos (Sí/No, Sí/No/Ignorado, etc.) se
 *      nombran "Compartido: ..." para que se note que no son de una sola
 *      ficha.
 *
 * MODO DE USO
 * -----------
 *   php cargar_fichas.php                        Dry-run de las 23 fichas:
 *                                                 hace todo el trabajo real
 *                                                 dentro de una transacción
 *                                                 por ficha y la revierte
 *                                                 (ROLLBACK) al final — no
 *                                                 queda nada escrito.
 *   php cargar_fichas.php --apply --confirmo-apply
 *                                                  Aplica de verdad (COMMIT).
 *                                                  --apply solo no alcanza:
 *                                                  hace falta también
 *                                                  --confirmo-apply, a
 *                                                  propósito, para que
 *                                                  aplicar de verdad nunca
 *                                                  sea un accidente de
 *                                                  copiar/pegar o de probar
 *                                                  otra bandera.
 *   php cargar_fichas.php --apply --confirmo-apply --cie10=A36,A37.0
 *                                                  Aplica solo esas fichas.
 *   php cargar_fichas.php --apply --confirmo-apply --confirmar-perdida=A97
 *                                                  Aplica y, además, permite
 *                                                  borrar/recargar una
 *                                                  enfermedad aunque tenga
 *                                                  caso_valor capturados
 *                                                  (los pierde a propósito).
 *   php cargar_fichas.php --json                   Salida en JSON en vez de
 *                                                   texto legible.
 *
 * El dry-run (modo por defecto) es seguro de correr las veces que se quiera:
 * usa la misma lógica que --apply pero nunca hace COMMIT.
 */

require __DIR__ . '/app/Core/Autoload.php';

use App\Core\Database;

// ============================================================================
// CLI
// ============================================================================
$aplicar = in_array('--apply', $argv, true);
if ($aplicar && !in_array('--confirmo-apply', $argv, true)) {
    fwrite(STDERR, "--apply requiere también --confirmo-apply, a propósito: esto borra e inserta de verdad seccion_def/campo_def/catalogo en la base de datos.\nCorré primero sin --apply para ver el plan (dry-run, no escribe nada).\n");
    exit(1);
}
$modoJson = in_array('--json', $argv, true);
$soloEstas = null;
$forzarPerdida = [];
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--cie10=')) {
        $soloEstas = array_map('trim', explode(',', substr($arg, 8)));
    }
    if (str_starts_with($arg, '--confirmar-perdida=')) {
        $forzarPerdida = array_map('trim', explode(',', substr($arg, 20)));
    }
}

$manifiestoPath = __DIR__ . '/manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($manifiestoPath), true);
if ($manifiesto === null) {
    fwrite(STDERR, "No se pudo leer/parsear {$manifiestoPath}: " . json_last_error_msg() . "\n");
    exit(1);
}

const TIPOS_CON_OPCIONES = ['SELECT', 'MULTISELECT', 'GRUPO_SI_NO', 'CRONOLOGIA'];
const TIPOS_VALIDOS = ['TEXTO', 'NUMERO', 'FECHA', 'BOOLEANO', 'SELECT', 'MULTISELECT', 'TEXTAREA', 'GRUPO_SI_NO', 'SI_NO_FECHA', 'MATRIZ', 'CRONOLOGIA'];

// Listas de opciones tan genéricas que se comparten entre fichas en vez de
// crear un catálogo por ficha (se detectan por contenido exacto, no por
// nombre — cualquier campo con exactamente esta lista de opciones cae acá).
const CATALOGOS_COMPARTIDOS = [
    ['Sí', 'No'],
    ['Sí', 'No', 'Ignorado'],
    ['Sí', 'No', 'Desconocido'],
    ['Sí', 'No', 'No recuerda'],
    ['Bueno', 'Regular', 'Malo'],
    ['Completa', 'Incompleta'],
    ['I', 'II', 'III'],
];

// ============================================================================
// Utilidades
// ============================================================================
function slug(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $mapa = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'];
    $texto = strtr($texto, $mapa);
    $texto = preg_replace('/[^a-z0-9]+/', '_', $texto);
    return trim($texto, '_');
}

function claveCampo(string $cie10, string $etiqueta, array &$clavesUsadasEnFicha): string
{
    $prefijo = slug($cie10);
    $base = $prefijo . '_' . slug($etiqueta);
    $base = mb_substr($base, 0, 55); // deja margen para el sufijo de deduplicación
    $clave = $base;
    $n = 2;
    while (isset($clavesUsadasEnFicha[$clave])) {
        $clave = $base . '_' . $n;
        $n++;
    }
    $clavesUsadasEnFicha[$clave] = true;
    return mb_substr($clave, 0, 60);
}

function claveOpciones(array $opciones): string
{
    return implode('§', $opciones);
}

function esCatalogoCompartido(array $opciones): bool
{
    foreach (CATALOGOS_COMPARTIDOS as $generico) {
        if ($opciones === $generico) {
            return true;
        }
    }
    return false;
}

/**
 * Valida el manifiesto ENTERO antes de tocar la base de datos. Aborta con
 * excepción ante el primer campo SELECT/MULTISELECT/GRUPO_SI_NO/CRONOLOGIA
 * sin "opciones", MATRIZ sin "columnas", o "tipo" no reconocido.
 */
function validarManifiesto(array $manifiesto): void
{
    foreach ($manifiesto['fichas'] as $cie10 => $ficha) {
        foreach ($ficha['secciones'] as $seccion) {
            foreach ($seccion['campos'] as $campo) {
                $tipo = $campo['tipo'] ?? null;
                $etiqueta = $campo['etiqueta'] ?? '(sin etiqueta)';
                if (!in_array($tipo, TIPOS_VALIDOS, true)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" tiene tipo desconocido: " . json_encode($tipo));
                }
                if (in_array($tipo, TIPOS_CON_OPCIONES, true) && empty($campo['opciones'])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" es {$tipo} pero no trae \"opciones\". El cargador nunca inserta catalogo_id NULL para estos tipos.");
                }
                if ($tipo === 'MATRIZ' && empty($campo['columnas'])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" es MATRIZ pero no trae \"columnas\".");
                }
            }
        }
    }
}

/**
 * Resuelve (reutilizando si es posible) el catalogo_id para una lista de
 * opciones. $cache está indexada por claveOpciones() -> catalogo_id y se
 * precarga con los catálogos ya existentes en la BD antes de procesar
 * cualquier ficha, así que "reutilizar" incluye tanto catálogos creados en
 * corridas anteriores como los creados más temprano en esta misma corrida.
 */
function resolverCatalogo(PDO $pdo, array $opciones, string $cie10, string $nombreSugerido, array &$cache, array &$nombresUsados, array &$reporte): int
{
    $clave = claveOpciones($opciones);
    if (isset($cache[$clave])) {
        $reporte['catalogos_reutilizados'][] = ['nombre' => $cache[$clave]['nombre'], 'opciones' => $opciones];
        return $cache[$clave]['id'];
    }

    $compartido = esCatalogoCompartido($opciones);
    $nombreBase = $compartido
        ? 'Compartido: ' . implode('/', $opciones)
        : "{$cie10} - {$nombreSugerido}";
    $nombreBase = mb_substr($nombreBase, 0, 76);
    $nombre = $nombreBase;
    $n = 2;
    while (isset($nombresUsados[$nombre])) {
        $nombre = mb_substr($nombreBase, 0, 74) . " ({$n})";
        $n++;
    }
    $nombresUsados[$nombre] = true;

    $stmt = $pdo->prepare('INSERT INTO catalogo (nombre) VALUES (?)');
    $stmt->execute([$nombre]);
    $catalogoId = (int) $pdo->lastInsertId();

    $stmtItem = $pdo->prepare('INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES (?,?,?,?)');
    $orden = 1;
    foreach ($opciones as $opcion) {
        $valor = mb_strtoupper(slug($opcion));
        $stmtItem->execute([$catalogoId, mb_substr($valor, 0, 60), mb_substr($opcion, 0, 120), $orden]);
        $orden++;
    }

    $cache[$clave] = ['id' => $catalogoId, 'nombre' => $nombre];
    $reporte['catalogos_creados'][] = ['nombre' => $nombre, 'opciones' => $opciones];
    return $catalogoId;
}

function precargarCatalogos(PDO $pdo, array &$nombresUsados): array
{
    $cache = [];
    $catalogos = $pdo->query('SELECT id, nombre FROM catalogo')->fetchAll();
    $items = $pdo->query('SELECT catalogo_id, etiqueta, orden FROM catalogo_item ORDER BY catalogo_id, orden')->fetchAll();
    $itemsPorCatalogo = [];
    foreach ($items as $it) {
        $itemsPorCatalogo[$it['catalogo_id']][] = $it['etiqueta'];
    }
    foreach ($catalogos as $cat) {
        $nombresUsados[$cat['nombre']] = true;
        $opciones = $itemsPorCatalogo[$cat['id']] ?? [];
        if (!$opciones) {
            continue;
        }
        $clave = claveOpciones($opciones);
        // Si dos catálogos existentes tuvieran el mismo contenido, se
        // conserva el primero encontrado (más antiguo = probablemente el
        // "canónico"); no se fusionan acá, solo se elige cuál reutilizar de
        // ahora en adelante.
        if (!isset($cache[$clave])) {
            $cache[$clave] = ['id' => (int) $cat['id'], 'nombre' => $cat['nombre']];
        }
    }
    return $cache;
}

/**
 * Inserta un campo_def (y su catálogo si aplica) dentro de la sección dada.
 */
function insertarCampo(PDO $pdo, int $seccionId, string $cie10, array $campo, int $orden, string $rolSujeto, array &$clavesUsadas, array &$catalogCache, array &$nombresCatalogo, array &$reporte): void
{
    $tipo = $campo['tipo'];
    $etiqueta = $campo['etiqueta'];
    $clave = claveCampo($cie10, $etiqueta, $clavesUsadas);
    $sensible = !empty($campo['sensible']) ? 1 : 0;

    $catalogoId = null;
    $config = null;

    if (in_array($tipo, TIPOS_CON_OPCIONES, true)) {
        $catalogoId = resolverCatalogo($pdo, $campo['opciones'], $cie10, $etiqueta, $catalogCache, $nombresCatalogo, $reporte);
    }

    if ($tipo === 'MATRIZ') {
        $filas = $campo['filas'] ?? null;
        $config = json_encode([
            'columnas' => $campo['columnas'],
            'filas' => is_array($filas) ? $filas : null,
            'filas_nota' => is_string($filas) ? $filas : null,
        ], JSON_UNESCAPED_UNICODE);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, rol_sujeto, sensible, catalogo_id, config, origen, orden)
         VALUES (?,?,?,?,0,?,?,?,?,\'FICHA_MINSA\',?)'
    );
    $stmt->execute([$seccionId, $clave, $etiqueta, $tipo, $rolSujeto, $sensible, $catalogoId, $config, $orden]);

    $reporte['campos_creados'][] = ['clave' => $clave, 'etiqueta' => $etiqueta, 'tipo' => $tipo];
}

/**
 * Procesa una ficha completa: verifica protección de datos, borra sus
 * secciones actuales y vuelve a insertar desde el manifiesto. Asume que el
 * llamador ya abrió la transacción correspondiente (ver main(): en modo
 * --apply es una transacción por ficha; en dry-run es una sola transacción
 * para todo el lote, así los catálogos creados por una ficha siguen
 * visibles para las siguientes dentro del mismo dry-run, igual que
 * quedarían visibles de verdad con --apply).
 */
function procesarFicha(PDO $pdo, string $cie10, array $fichaManifiesto, int $enfermedadId, array &$catalogCache, array &$nombresCatalogo, bool $forzarProtegida): array
{
    $reporte = [
        'cie10' => $cie10,
        'enfermedad' => $fichaManifiesto['enfermedad'],
        'enfermedad_id' => $enfermedadId,
        'bloqueada' => false,
        'motivo_bloqueo' => null,
        'secciones_borradas' => 0,
        'campos_borrados' => 0,
        'secciones_creadas' => [],
        'campos_creados' => [],
        'catalogos_creados' => [],
        'catalogos_reutilizados' => [],
    ];

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM caso_valor cv
         JOIN campo_def cd ON cv.campo_def_id = cd.id
         JOIN seccion_def sd ON cd.seccion_id = sd.id
         WHERE sd.enfermedad_id = ?'
    );
    $stmt->execute([$enfermedadId]);
    $numValores = (int) $stmt->fetchColumn();

    if ($numValores > 0 && !$forzarProtegida) {
        $reporte['bloqueada'] = true;
        $reporte['motivo_bloqueo'] = "Hay {$numValores} caso_valor capturado(s) apuntando a campo_def de esta enfermedad. No se borra sin --confirmar-perdida={$cie10}.";
        return $reporte;
    }

    $stmt = $pdo->prepare('SELECT id FROM seccion_def WHERE enfermedad_id = ?');
    $stmt->execute([$enfermedadId]);
    $seccionesViejas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $reporte['secciones_borradas'] = count($seccionesViejas);

    if ($seccionesViejas) {
        $in = implode(',', array_fill(0, count($seccionesViejas), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM campo_def WHERE seccion_id IN ({$in})");
        $stmt->execute($seccionesViejas);
        $reporte['campos_borrados'] = (int) $stmt->fetchColumn();

        // campo_def.depende_de es una FK autorreferencial sin ON DELETE
        // CASCADE: si un campo con dependientes (p.ej. "especificar") se
        // borra antes que ellos, el DELETE en cascada de seccion_def choca
        // contra esa FK. Se rompen esas referencias primero.
        $pdo->prepare("UPDATE campo_def SET depende_de = NULL WHERE seccion_id IN ({$in})")->execute($seccionesViejas);
    }

    $pdo->prepare('DELETE FROM seccion_def WHERE enfermedad_id = ?')->execute([$enfermedadId]);

    $ordenSeccion = 1;
    foreach ($fichaManifiesto['secciones'] as $seccion) {
        if (empty($seccion['campos'])) {
            continue; // seccion informativa (contenido vive en tabla hija o queda pendiente): no genera seccion_def
        }
        $stmt = $pdo->prepare('INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (?,?,?)');
        $stmt->execute([$enfermedadId, $seccion['nombre'], $ordenSeccion]);
        $seccionId = (int) $pdo->lastInsertId();
        $reporte['secciones_creadas'][] = $seccion['nombre'];

        $rolSujeto = $seccion['rol_sujeto'] ?? 'CASO_INDICE';
        $clavesUsadas = [];
        $ordenCampo = 1;
        foreach ($seccion['campos'] as $campo) {
            insertarCampo($pdo, $seccionId, $cie10, $campo, $ordenCampo, $rolSujeto, $clavesUsadas, $catalogCache, $nombresCatalogo, $reporte);
            $ordenCampo++;
        }
        $ordenSeccion++;
    }

    return $reporte;
}

// ============================================================================
// Main
// ============================================================================

// 1) Validar TODO el manifiesto antes de tocar la BD (falla dura, requisito 2).
validarManifiesto($manifiesto);

$pdo = Database::conexion();

$enfermedades = $pdo->query('SELECT id, cie10, nombre FROM enfermedad')->fetchAll();
$enfermedadPorCie10 = [];
foreach ($enfermedades as $e) {
    if ($e['cie10']) {
        $enfermedadPorCie10[$e['cie10']] = $e;
    }
}

$nombresCatalogo = [];
$catalogCache = precargarCatalogos($pdo, $nombresCatalogo);

$reportes = [];
$sinEnfermedad = [];

if ($aplicar) {
    // Una transacción POR FICHA: si una falla, no arrastra a las demás y
    // lo ya aplicado antes se mantiene (requisito 1 de RECARGA_FICHAS.md).
    foreach ($manifiesto['fichas'] as $cie10 => $fichaManifiesto) {
        if ($soloEstas !== null && !in_array($cie10, $soloEstas, true)) {
            continue;
        }
        if (!isset($enfermedadPorCie10[$cie10])) {
            $sinEnfermedad[] = $cie10;
            continue;
        }
        $forzar = in_array($cie10, $forzarPerdida, true);
        $enfermedadId = (int) $enfermedadPorCie10[$cie10]['id'];

        $pdo->beginTransaction();
        try {
            $reporte = procesarFicha($pdo, $cie10, $fichaManifiesto, $enfermedadId, $catalogCache, $nombresCatalogo, $forzar);
            if ($reporte['bloqueada']) {
                $pdo->rollBack();
            } else {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $reportes[] = $reporte;
    }
} else {
    // Dry-run: UNA sola transacción para todo el lote, revertida al final.
    // Así los catálogos que "crearía" una ficha siguen visibles para las
    // siguientes dentro de la misma corrida (igual que pasaría de verdad
    // con --apply, donde cada commit persiste antes de procesar la
    // siguiente ficha) — y nada queda escrito al terminar.
    $pdo->beginTransaction();
    try {
        foreach ($manifiesto['fichas'] as $cie10 => $fichaManifiesto) {
            if ($soloEstas !== null && !in_array($cie10, $soloEstas, true)) {
                continue;
            }
            if (!isset($enfermedadPorCie10[$cie10])) {
                $sinEnfermedad[] = $cie10;
                continue;
            }
            $forzar = in_array($cie10, $forzarPerdida, true);
            $reportes[] = procesarFicha(
                $pdo,
                $cie10,
                $fichaManifiesto,
                (int) $enfermedadPorCie10[$cie10]['id'],
                $catalogCache,
                $nombresCatalogo,
                $forzar
            );
        }
    } finally {
        $pdo->rollBack();
    }
}

// ============================================================================
// Salida
// ============================================================================
if ($modoJson) {
    echo json_encode([
        'modo' => $aplicar ? 'APLICADO' : 'DRY_RUN',
        'fichas' => $reportes,
        'sin_enfermedad_en_bd' => $sinEnfermedad,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n";
    exit(0);
}

$modoTexto = $aplicar ? 'APLICADO (se hizo COMMIT de cada ficha no bloqueada)' : 'DRY-RUN (cada ficha se procesó dentro de una transacción y se hizo ROLLBACK — nada quedó escrito)';
echo "# cargar_fichas.php — {$modoTexto}\n\n";

$bloqueadas = array_filter($reportes, fn($r) => $r['bloqueada']);
$procesadas = array_filter($reportes, fn($r) => !$r['bloqueada']);

printf("Fichas procesadas: %d · bloqueadas por datos capturados: %d · sin enfermedad en BD: %d\n\n", count($procesadas), count($bloqueadas), count($sinEnfermedad));

if ($sinEnfermedad) {
    echo "## Sin enfermedad en la BD (no se tocaron)\n";
    foreach ($sinEnfermedad as $c) {
        echo "- {$c}\n";
    }
    echo "\n";
}

if ($bloqueadas) {
    echo "## Bloqueadas por datos capturados (no se tocaron)\n\n";
    foreach ($bloqueadas as $r) {
        echo "- **{$r['enfermedad']}** (`{$r['cie10']}`): {$r['motivo_bloqueo']}\n";
    }
    echo "\n";
}

echo "## Plan por ficha\n\n";
foreach ($procesadas as $r) {
    $catNuevos = count($r['catalogos_creados']);
    $catReusados = count($r['catalogos_reutilizados']);
    printf(
        "### %s (`%s`)\n- Secciones: borra %d, crea %d\n- Campos: borra %d, crea %d\n- Catálogos: crea %d, reutiliza %d\n\n",
        $r['enfermedad'],
        $r['cie10'],
        $r['secciones_borradas'],
        count($r['secciones_creadas']),
        $r['campos_borrados'],
        count($r['campos_creados']),
        $catNuevos,
        $catReusados
    );
}

if (!$aplicar) {
    echo "---\n\nEsto fue un dry-run: no se escribió nada en la base. Para aplicar de verdad:\n";
    echo "  php cargar_fichas.php --apply\n";
    echo "o, para una sola ficha:\n";
    echo "  php cargar_fichas.php --apply --cie10=A36\n";
}
