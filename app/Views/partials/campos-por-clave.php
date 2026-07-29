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
 *     ['id' => int|null, 'name' => string, 'val' => mixed, 'err' => ?string,
 *      'opciones' => array, 'campo' => array|null]
 *     Si la clave no existe hoy en campo_def para esta $enfermedad, 'id' y
 *     'campo' vienen null, 'name'/'val' vienen vacíos, y la clave se registra
 *     en $clavesFaltantesCampos — no falla en silencio.
 *
 *   $clavesFaltantesCampos: string[]
 *     Claves pedidas que no existen. Se llena progresivamente a medida que
 *     la vista llama a $campo(); consultarla solo tiene sentido después de
 *     haber pedido todas las claves del formulario.
 *
 *   $avisoClavesFaltantesCampos(): string
 *     HTML del aviso (vacío si no hay claves faltantes o si app.debug está
 *     apagado). La vista decide dónde hacer echo de esto, si quiere mostrarlo.
 *
 *   $mapaClaveNombreCampos: array<string,string>
 *     [clave => 'campo_<id>'] de todos los campos de la $enfermedad actual.
 *     Fase 4: se emite como JSON para que ficha.js resuelva por clave en vez
 *     de por ID (ver public/js/ficha.js, campoPorClave()).
 */

use App\Models\CampoDef;
use App\Models\CatalogoItem;
use App\Models\SeccionDef;

$campoDefPorClave = [];
foreach (SeccionDef::porEnfermedad((int) $enfermedad['id']) as $__seccionCPC) {
    foreach (CampoDef::porSeccion((int) $__seccionCPC['id']) as $__campoCPC) {
        // En claves duplicadas dentro de la misma ficha (ver MAPA_IDS_CAMPOS.md,
        // sección "Claves duplicadas"), gana la primera aparición (orden de
        // sección, luego orden de campo) y no se sobrescribe: es preferible un
        // resultado determinista y detectable a uno que cambie según qué
        // sección se cargó al final.
        $campoDefPorClave[$__campoCPC['clave']] ??= $__campoCPC;
    }
}

$mapaClaveNombreCampos = [];
foreach ($campoDefPorClave as $__claveMCN => $__filaMCN) {
    $mapaClaveNombreCampos[$__claveMCN] = 'campo_' . (int) $__filaMCN['id'];
}

$clavesFaltantesCampos = [];
$__opcionesPorCatalogoCPC = [];

$campo = function (string $clave) use (
    $campoDefPorClave,
    &$clavesFaltantesCampos,
    &$__opcionesPorCatalogoCPC,
    $valoresCampos,
    $erroresCampos
): array {
    $fila = $campoDefPorClave[$clave] ?? null;
    if (!$fila) {
        if (!in_array($clave, $clavesFaltantesCampos, true)) {
            $clavesFaltantesCampos[] = $clave;
        }
        return ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];
    }

    $id = (int) $fila['id'];
    $opciones = [];
    if (!empty($fila['catalogo_id'])) {
        $catalogoId = (int) $fila['catalogo_id'];
        $__opcionesPorCatalogoCPC[$catalogoId] ??= CatalogoItem::porCatalogo($catalogoId);
        $opciones = $__opcionesPorCatalogoCPC[$catalogoId];
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

$avisoClavesFaltantesCampos = function () use (&$clavesFaltantesCampos): string {
    if (empty($clavesFaltantesCampos)) {
        return '';
    }
    static $config = null;
    $config ??= require __DIR__ . '/../../../config/config.php';
    if (empty($config['app']['debug'])) {
        return '';
    }

    return '<div class="info-callout" style="background:rgba(239,68,68,0.1); border:1px solid var(--danger, #ef4444); '
        . 'border-radius:var(--radius-sm, 8px); padding:10px 14px; margin-bottom:14px; color:var(--danger, #ef4444); '
        . 'font-size:0.85rem;"><strong>Claves sin campo_def (solo visible con app.debug):</strong> '
        . e(implode(', ', $clavesFaltantesCampos)) . '</div>';
};
