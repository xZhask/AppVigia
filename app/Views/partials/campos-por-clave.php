<?php
/**
 * Resolvedor único de campos por `clave`, para dejar de depender del ID
 * autoincremental de `campo_def` en vistas, JS y controladores (ver
 * PETICION_02_v2_IDS_Y_ORDEN.md, Fase 2). `cargar_fichas.php` regenera los
 * IDs en cada recarga; la `clave` es estable por construcción.
 *
 * Incluir una sola vez por formulario, **después** de que `$enfermedad`,
 * `$valoresCampos` y `$erroresCampos` ya estén en el scope de quien incluye
 * (mismo contrato que hoy consumen secciones-clinicas.php y campo-dinamico.php).
 *
 * Expone:
 *   $campo(string $clave): array
 *     ['id' => int, 'name' => string, 'val' => mixed, 'err' => ?string,
 *      'opciones' => array, 'campo' => array]
 *     Si la clave no existe hoy en campo_def para esta $enfermedad, lanza
 *     \RuntimeException (Petición 2, cotejo O95, 2026-07-30) -- antes
 *     devolvía 'id'/'campo' null en silencio y así sobrevivieron semanas
 *     62 claves mal escritas en O95 y 1 en B05. Es un bug de código, no una
 *     entrada inválida: no hay try/catch "elegante" salvo el caso puntual
 *     que necesite tolerar una clave todavía sin resolver (ver
 *     o95_eess_fallecimiento_id en datos-fallecimiento-o95.php). Antes de
 *     lanzar, igual registra la clave en $clavesFaltantesCampos, así que
 *     ese try/catch puntual sigue apareciendo en el aviso.
 *
 *   $clavesFaltantesCampos: string[]
 *     Claves pedidas que no existen. Se llena progresivamente a medida que
 *     la vista llama a $campo(); consultarla solo tiene sentido después de
 *     haber pedido todas las claves del formulario (o dentro de un
 *     try/catch puntual, para la única clave que lo necesita hoy).
 *
 *   $avisoClavesFaltantesCampos(): string
 *     HTML del aviso (vacío si no hay claves faltantes o si app.debug está
 *     apagado), agrupado por ficha -- junta $clavesFaltantesCampos (el
 *     $campo ambiente) con $__clavesFaltantesPorCie10 (lo que no resolvió
 *     ningún $resolvedorPara($cie10)). La vista decide dónde hacer echo,
 *     nueva/index.php y fichas/editar.php ya lo hacen; CasosController
 *     también lo manda en el JSON de seccionesClinicas() (clave
 *     "avisoClavesFaltantes"). Con el throw de más arriba, en la práctica
 *     solo puede mostrar más de una clave junta si todas pasaron por un
 *     try/catch como el de o95_eess_fallecimiento_id -- verificar_claves.php
 *     es la herramienta para ver TODAS las claves faltantes de una vez,
 *     sin tener que ir disparando excepciones una por una.
 *
 *   $mapaClaveNombreCampos: array<string,string>
 *     [clave => 'campo_<id>'] de todos los campos de la $enfermedad actual.
 *     Fase 4: se emite como JSON para que ficha.js resuelva por clave en vez
 *     de por ID (ver public/js/ficha.js, campoPorClave()).
 *
 *   $resolvedorPara(string $cie10): callable(string):array
 *     Para partials que se renderizan UNA SOLA VEZ en el shell de la página
 *     (fuera de #secciones-clinicas, que sí recibe un $campo fresco en cada
 *     fetch de cambio de enfermedad — ver CasosController::seccionesClinicas())
 *     y necesitan resolver claves de una ficha que NO es necesariamente la
 *     $enfermedad activa en ese render inicial — ej. las tarjetas de B26/O95
 *     que quedan ocultas en el DOM hasta que el usuario cambia de enfermedad
 *     en el selector, sin volver a renderizarse (PENDIENTES.md, item 7).
 *     Devuelve un $campo() ligado a $cie10: mismo contrato de 6 campos, y
 *     las claves que no resuelven se registran igual (por ficha, no se
 *     mezclan entre sí ni con $clavesFaltantesCampos — ver
 *     $__clavesFaltantesPorCie10 si hace falta inspeccionarlas). Memoizado
 *     por cie10 dentro del mismo request: llamarlo varias veces con el
 *     mismo $cie10 no recalcula el índice de campo_def.
 */

use App\Models\CampoDef;
use App\Models\CatalogoItem;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$__indicePorClaveCPC = function (int $enfermedadId): array {
    $indice = [];
    foreach (SeccionDef::porEnfermedad($enfermedadId) as $__seccionCPC) {
        foreach (CampoDef::porSeccion((int) $__seccionCPC['id']) as $__campoCPC) {
            // En claves duplicadas dentro de la misma ficha (ver MAPA_IDS_CAMPOS.md,
            // sección "Claves duplicadas"), gana la primera aparición (orden de
            // sección, luego orden de campo) y no se sobrescribe: es preferible un
            // resultado determinista y detectable a uno que cambie según qué
            // sección se cargó al final.
            $indice[$__campoCPC['clave']] ??= $__campoCPC;
        }
    }
    return $indice;
};

// Parámetro por referencia (no valor de retorno por referencia: un array
// [$fn, &$lista] devuelto y desestructurado con [$a, $b] = ... NO conserva
// la referencia en PHP, se probó aparte) — así la lista de claves faltantes
// que ve el llamador es la misma que muta el closure interno.
//
// $contextoCPC (Petición 2, cotejo O95, 2026-07-30): una clave que no
// existe en campo_def es un bug de código -- el string es un literal que
// nunca vino de un usuario, no hay "entrada inválida" que tolerar. Antes
// devolvía un array vacío en silencio: así sobrevivieron semanas 62 claves
// mal escritas en O95 y 1 en B05 sin que nada las mostrara ($campo(...)
// nunca es null, así que la cadena de fallback de cada vista seguía
// funcionando visualmente con el campo vacío). Ahora lanza, siempre,
// independiente de app.debug -- no hay "modo silencioso" legítimo para
// este caso. Si un caller necesita tolerar una clave que todavía no se
// resolvió (ver o95_eess_fallecimiento_id en datos-fallecimiento-o95.php,
// pendiente de cotejo contra el PDF), es responsabilidad del caller
// envolver esa llamada puntual en try/catch, no de este resolvedor.
$__crearResolvedorCPC = function (array $indicePorClave, array &$clavesFaltantes, string $contextoCPC = '') use ($valoresCampos, $erroresCampos): callable {
    $opcionesPorCatalogo = [];
    return function (string $clave) use ($indicePorClave, &$clavesFaltantes, &$opcionesPorCatalogo, $valoresCampos, $erroresCampos, $contextoCPC): array {
        $fila = $indicePorClave[$clave] ?? null;
        if (!$fila) {
            if (!in_array($clave, $clavesFaltantes, true)) {
                $clavesFaltantes[] = $clave;
            }
            throw new \RuntimeException(
                "campos-por-clave.php: la clave '{$clave}' no existe en campo_def"
                . ($contextoCPC !== '' ? " para {$contextoCPC}" : '')
                . '. Si el campo existe con otro nombre, corregir el string en el código '
                . '(ver verificar_claves.php); si no existe, falta declararlo en el manifiesto.'
            );
        }

        $id = (int) $fila['id'];
        $opciones = [];
        if (!empty($fila['catalogo_id'])) {
            $catalogoId = (int) $fila['catalogo_id'];
            $opcionesPorCatalogo[$catalogoId] ??= CatalogoItem::porCatalogo($catalogoId);
            $opciones = $opcionesPorCatalogo[$catalogoId];
        }

        return [
            'id'       => $id,
            'name'     => 'campo_' . $id,
            'val'      => $valoresCampos[$id] ?? ($fila['tipo'] === 'MULTISELECT' ? [] : ''),
            'err'      => $erroresCampos[$id] ?? null,
            'opciones' => $opciones,
            'campo'    => $fila,
        ];
    };
};

$campoDefPorClave = $__indicePorClaveCPC((int) $enfermedad['id']);

$mapaClaveNombreCampos = [];
foreach ($campoDefPorClave as $__claveMCN => $__filaMCN) {
    $mapaClaveNombreCampos[$__claveMCN] = 'campo_' . (int) $__filaMCN['id'];
}

$clavesFaltantesCampos = [];
$campo = $__crearResolvedorCPC($campoDefPorClave, $clavesFaltantesCampos, 'la ficha activa (' . ($enfermedad['cie10'] ?? '?') . ')');

$__resolvedoresPorCie10Cache = [];
$__clavesFaltantesPorCie10 = [];
$resolvedorPara = function (string $cie10) use (&$__resolvedoresPorCie10Cache, &$__clavesFaltantesPorCie10, $__indicePorClaveCPC, $__crearResolvedorCPC): callable {
    if (isset($__resolvedoresPorCie10Cache[$cie10])) {
        return $__resolvedoresPorCie10Cache[$cie10];
    }

    $enfermedadDe = Enfermedad::buscarPorCie10($cie10);
    $indice = $enfermedadDe ? $__indicePorClaveCPC((int) $enfermedadDe['id']) : [];

    $__clavesFaltantesPorCie10[$cie10] = [];
    $resolvedor = $__crearResolvedorCPC($indice, $__clavesFaltantesPorCie10[$cie10], $cie10);
    $__resolvedoresPorCie10Cache[$cie10] = $resolvedor;
    return $resolvedor;
};

// Auditoría de claves del 2026-07-30: esta función existía desde la Fase 2
// pero nunca se invocaba desde ninguna vista (ver FASE2_RESOLVEDOR_POR_CLAVE.md,
// hueco de cobertura anotado ahí mismo) -- 62 claves de O95 y 1 de B05
// llevaron semanas devolviendo id => null sin que nada lo mostrara. Además
// solo miraba $clavesFaltantesCampos (el $campo ambiente); las claves que
// no resuelven vía $resolvedorPara($cie10) quedaban en
// $__clavesFaltantesPorCie10, un array aparte que esta función tampoco
// leía -- un segundo punto ciego, este introducido junto con
// $resolvedorPara. Ahora junta los dos.
$avisoClavesFaltantesCampos = function () use (&$clavesFaltantesCampos, &$__clavesFaltantesPorCie10, $enfermedad): string {
    $porFicha = $clavesFaltantesCampos ? [$enfermedad['cie10'] ?? '?' => $clavesFaltantesCampos] : [];
    foreach ($__clavesFaltantesPorCie10 as $cie10 => $claves) {
        if ($claves) {
            $porFicha[$cie10] = $claves;
        }
    }
    if (empty($porFicha)) {
        return '';
    }
    static $config = null;
    $config ??= require __DIR__ . '/../../../config/config.php';
    if (empty($config['app']['debug'])) {
        return '';
    }

    $lineas = [];
    foreach ($porFicha as $cie10 => $claves) {
        $lineas[] = e($cie10) . ': ' . e(implode(', ', $claves));
    }

    return '<div class="info-callout" style="background:rgba(239,68,68,0.1); border:1px solid var(--danger, #ef4444); '
        . 'border-radius:var(--radius-sm, 8px); padding:10px 14px; margin-bottom:14px; color:var(--danger, #ef4444); '
        . 'font-size:0.85rem;"><strong>Claves sin campo_def (solo visible con app.debug):</strong><br>'
        . implode('<br>', $lineas) . '</div>';
};
