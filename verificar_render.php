<?php
/**
 * verificar_render.php
 *
 * Complementa a verificar_fichas.php: ese compara manifiesto↔BD (seccion_def/
 * campo_def), pero nunca mira si un campo_def declarado y cargado realmente
 * aparece en el HTML de "Nueva ficha". Un campo puede estar perfecto en
 * manifiesto y BD y ser inalcanzable en la práctica si un partial compartido
 * lo excluye (por ejemplo, por nombre de sección) sin que nadie lo note --
 * caso real: PENDIENTES.md ítem N (p35_0_n_de_historia_clinica,
 * o95_n_de_historia_clinica).
 *
 * Para cada una de las fichas en la tabla `enfermedad`, renderiza "Nueva
 * ficha" con el controlador real (App\Controllers\CasosController::nuevo(),
 * el mismo código que sirve la página en producción) y confirma que todo
 * campo_def de esa enfermedad tiene su name="campo_<id>" en el HTML
 * resultante (con o sin sufijo [..], según el tipo de campo -- GRUPO_SI_NO,
 * MATRIZ, MULTISELECT, CRONOLOGIA y SI_NO_FECHA usan corchetes; el resto no).
 *
 * No modifica nada: nuevo() es una acción GET de solo lectura, no escribe en
 * la base de datos ni en el manifiesto.
 *
 * Sexto mecanismo (PENDIENTES.md ítem N.2): 4 claves no usan name="campo_<id>"
 * a propósito -- CasosController::validarCamposDinamicos() (líneas ~986-1017)
 * las remapea a mano desde un name= literal ("hora_notificacion",
 * "identificado_por", "o95_tipo_ficha", "b26_lugar_tipo[]"...). Sin esta
 * lista, esta corrida las reportaba como huérfanas aunque funcionan --
 * ruido que en dos semanas nadie mira. Se listan aparte, no como huérfanos.
 *
 * Uso:
 *   php verificar_render.php                # imprime el reporte en Markdown
 *   php verificar_render.php > REPORTE_VERIFICACION_RENDER.md
 *   php verificar_render.php --json         # imprime el resultado crudo en JSON (para tooling)
 */

require __DIR__ . '/app/Core/Autoload.php';
require __DIR__ . '/app/Core/ayudantes.php';

use App\Controllers\CasosController;
use App\Core\Database;
use App\Core\Session;

const CLAVES_MECANISMO_NO_ESTANDAR = [
    'b26_contactos_por_lugar'      => 'lugar-probable-infeccion-b26.php: filas dinámicas con name="b26_lugar_tipo[]" etc., remapeadas en CasosController.php:986.',
    'a37_0_contactos_por_lugar'    => 'secciones-clinicas.php: filas dinámicas con name="a370_lugar_tipo[]" etc. (mismo patrón que b26_contactos_por_lugar), remapeadas en CasosController.php (validarCamposDinamicos).',
    'o95_hora_de_la_notificacion'  => 'notificacion-fechas-o95.php: name="hora_notificacion" literal, remapeado en CasosController.php:1001.',
    'o95_identificado_por'         => 'notificacion-fechas-o95.php: name="identificado_por" literal, remapeado en CasosController.php:1007.',
    'o95_tipo_de_ficha'            => 'notificacion-fechas-o95.php: name="o95_tipo_ficha" literal, remapeado en CasosController.php:1013.',
    'a35_distrito_probable_infeccion' => 'secciones-clinicas.php: selector real Departamento/Provincia/Distrito, name="a35_lugar_infeccion_distrito_id" literal, remapeado en CasosController.php (validarCamposDinamicos).',
];

$modoJson = in_array('--json', $argv, true);

Session::iniciar();
$_SESSION['usuario'] = ['id' => 1, 'nombre' => 'verificar_render', 'rol' => 'ADMIN', 'establecimiento_id' => 1, 'persona_id' => null];

$pdo = Database::conexion();

$enfermedades = $pdo->query('SELECT id, nombre, cie10 FROM enfermedad ORDER BY cie10')->fetchAll();

$campos = $pdo->query(
    'SELECT cd.id, cd.clave, cd.etiqueta, cd.tipo, sd.nombre AS seccion_nombre, sd.enfermedad_id
       FROM campo_def cd
       JOIN seccion_def sd ON sd.id = cd.seccion_id
   ORDER BY sd.enfermedad_id, sd.orden, sd.id, cd.orden, cd.id'
)->fetchAll();

$camposPorEnfermedad = [];
foreach ($campos as $campo) {
    $camposPorEnfermedad[(int) $campo['enfermedad_id']][] = $campo;
}

$resultado = [];

foreach ($enfermedades as $enf) {
    $enfermedadId = (int) $enf['id'];

    $_GET = ['enfermedad_id' => (string) $enfermedadId];
    $_SERVER['REQUEST_METHOD'] = 'GET';

    $html = '';
    $errorRender = null;
    try {
        $controlador = new CasosController();
        ob_start();
        $controlador->nuevo();
        $html = ob_get_clean();
    } catch (\Throwable $e) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $errorRender = $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine();
    }

    $camposFicha = $camposPorEnfermedad[$enfermedadId] ?? [];
    $faltantes = [];
    $noEstandar = [];
    $presentes = 0;

    if ($errorRender === null) {
        foreach ($camposFicha as $campo) {
            $aguja = 'name="campo_' . $campo['id'];
            if (str_contains($html, $aguja)) {
                $presentes++;
                continue;
            }
            $datosCampo = [
                'id'             => (int) $campo['id'],
                'clave'          => $campo['clave'],
                'etiqueta'       => $campo['etiqueta'],
                'tipo'           => $campo['tipo'],
                'seccion_nombre' => $campo['seccion_nombre'],
            ];
            if (isset(CLAVES_MECANISMO_NO_ESTANDAR[$campo['clave']])) {
                $datosCampo['mecanismo'] = CLAVES_MECANISMO_NO_ESTANDAR[$campo['clave']];
                $noEstandar[] = $datosCampo;
            } else {
                $faltantes[] = $datosCampo;
            }
        }
    }

    $estado = 'OK';
    if ($errorRender !== null) {
        $estado = 'ERROR_RENDER';
    } elseif (!empty($faltantes)) {
        $estado = 'CAMPOS_HUERFANOS';
    } elseif (!empty($noEstandar)) {
        $estado = 'SOLO_MECANISMO_NO_ESTANDAR';
    }

    $resultado[] = [
        'cie10'         => $enf['cie10'] ?: '—',
        'enfermedad'    => $enf['nombre'],
        'enfermedad_id' => $enfermedadId,
        'error_render'  => $errorRender,
        'declarados'    => count($camposFicha),
        'presentes'     => $presentes,
        'faltantes'     => $faltantes,
        'no_estandar'   => $noEstandar,
        'estado'        => $estado,
    ];
}

if ($modoJson) {
    echo json_encode(['fichas' => $resultado], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n";
    exit(0);
}

// ----------------------------------------------------------------------
// Salida en Markdown
// ----------------------------------------------------------------------
$fechaHoy = date('Y-m-d');
echo "# REPORTE_VERIFICACION_RENDER.md\n\n";
echo "Generado por `verificar_render.php` el {$fechaHoy}: renderiza \"Nueva ficha\" con el controlador real para cada enfermedad y confirma que todo `campo_def` declarado aparece en el HTML (`name=\"campo_<id>\"`).\n\n";
echo "No se modificó nada: `nuevo()` es una acción GET de solo lectura.\n\n";
echo "**Qué NO cubre esta corrida:** solo confirma presencia del `name=\"campo_<id>\"` en el HTML de \"Nueva ficha\" (formulario vacío, primera carga). No verifica `editar()`/`ver()`, no verifica que el campo sea visible sin JavaScript adicional (p.ej. un `depende_de` oculto por disparador es 'presente' aunque hoy esté con `hidden`), y no verifica el endpoint AJAX de cambio de ficha (`secciones-clinicas.php` vía fetch). Un campo 'presente' aquí puede seguir teniendo otros bugs; un campo 'huérfano' aquí es inalcanzable sin excepción.\n\n";
echo "**Mecanismo no estándar (ítem N.2):** " . count(CLAVES_MECANISMO_NO_ESTANDAR) . " claves conocidas se capturan por un `name=` literal remapeado a mano en `CasosController::validarCamposDinamicos()`, no por `name=\"campo_<id>\"`. Esta corrida las reconoce y las lista aparte, no como huérfanas -- si el día de mañana esa lista queda desactualizada (se agrega un quinto mecanismo así), sí aparecerán como huérfanas hasta que se las enseñe acá.\n\n";
echo "---\n\n";

echo "## Resumen\n\n";
echo "| Ficha (CIE-10) | Enfermedad | Declarados | Presentes | Huérfanos | No estándar | Estado |\n";
echo "|---|---|---|---|---|---|---|\n";
$totalHuerfanos = 0;
$totalNoEstandar = 0;
$fichasConHuerfanos = 0;
foreach ($resultado as $item) {
    $estadoTexto = match ($item['estado']) {
        'OK' => '✅ OK',
        'CAMPOS_HUERFANOS' => '❌ Huérfanos',
        'SOLO_MECANISMO_NO_ESTANDAR' => '⚙️ No estándar (OK)',
        'ERROR_RENDER' => '💥 Error al renderizar',
        default => $item['estado'],
    };
    $numFaltantes = count($item['faltantes']);
    $numNoEstandar = count($item['no_estandar']);
    if ($numFaltantes > 0) {
        $totalHuerfanos += $numFaltantes;
        $fichasConHuerfanos++;
    }
    $totalNoEstandar += $numNoEstandar;
    printf(
        "| %s | %s | %s | %s | %s | %s | %s |\n",
        $item['cie10'],
        $item['enfermedad'],
        $item['error_render'] !== null ? '—' : $item['declarados'],
        $item['error_render'] !== null ? '—' : $item['presentes'],
        $item['error_render'] !== null ? '—' : $numFaltantes,
        $item['error_render'] !== null ? '—' : $numNoEstandar,
        $estadoTexto
    );
}
echo "\n";
echo "**Total: {$totalHuerfanos} campo(s) huérfano(s) en {$fichasConHuerfanos} de " . count($resultado) . " ficha(s). {$totalNoEstandar} campo(s) más capturados por mecanismo no estándar (no huérfanos).**\n\n";
echo "---\n\n";

echo "## Detalle por ficha\n\n";
foreach ($resultado as $item) {
    if ($item['estado'] === 'OK') {
        continue;
    }
    echo "### {$item['enfermedad']} (`{$item['cie10']}`)\n\n";

    if ($item['error_render'] !== null) {
        echo "> 💥 No se pudo renderizar: {$item['error_render']}\n\n";
        continue;
    }

    if ($item['faltantes']) {
        echo "**Campos huérfanos** (en `campo_def`, no encontrados en el HTML de \"Nueva ficha\"):\n\n";
        foreach ($item['faltantes'] as $f) {
            echo "- `{$f['clave']}` — \"{$f['etiqueta']}\" ({$f['tipo']}, id={$f['id']}) — sección «{$f['seccion_nombre']}»\n";
        }
        echo "\n";
    }

    if ($item['no_estandar']) {
        echo "**Capturados por mecanismo no estándar** (no son huérfanos -- funcionan, pero no vía `name=\"campo_<id>\"`):\n\n";
        foreach ($item['no_estandar'] as $f) {
            echo "- `{$f['clave']}` — \"{$f['etiqueta']}\" ({$f['tipo']}, id={$f['id']}) — sección «{$f['seccion_nombre']}» — {$f['mecanismo']}\n";
        }
        echo "\n";
    }
}

if ($totalHuerfanos === 0) {
    echo "Ninguna ficha tiene campos huérfanos en esta corrida.\n";
}
