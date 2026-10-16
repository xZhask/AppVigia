<?php
/**
 * O95 (2026-10-15): los datos de "Datos del fallecimiento" y de "Referencia"
 * que el formulario solo muestra según el lugar del fallecimiento, el tipo de
 * EE.SS., ¿Referida? o la etapa ya no se guardan cuando no corresponden. Antes
 * los bloques se ocultaban solo en el navegador y lo escrito se guardaba
 * igual (p. ej. el ubigeo con lugar = Domicilio):
 *   - depende_de en 26 campos (tipo y nombre del EE.SS., IPRESS PNP,
 *     permanencia, ubigeo, "especificar lugar", EE.SS. de origen y su ubigeo,
 *     y el detalle de la referencia).
 *   - reglas_campos "mostrar" para lo que además es de Anexo 2 (categoría y
 *     fecha y hora de ingreso al EE.SS., detalle de la referencia).
 *   - "Fecha y hora de ingreso al EE.SS." con "con_hora": se guarda
 *     AAAA-MM-DDTHH:MM (antes el servidor rechazaba lo que mandaba el
 *     datetime-local y la ficha no se podía registrar).
 *
 * El manifiesto ya lo declara; este script es para las bases donde O95 tiene
 * casos y cargar_fichas.php la deja bloqueada. Toma las declaraciones del
 * manifiesto (no las repite), actualiza los campo_def existentes (no los
 * recrea) y borra en los casos guardados los valores de esos campos que hoy
 * descartaría el servidor al editar y guardar. Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';
require_once __DIR__ . '/../../app/Core/ayudantes.php';

use App\Core\Database;
use App\Models\CampoDef;

$pdo = Database::conexion();

$manifiesto = json_decode(file_get_contents(__DIR__ . '/../../manifiesto_fichas.json'), true);
$ficha = $manifiesto['fichas']['O95'] ?? null;
if (!$ficha) {
    fwrite(STDERR, "El manifiesto no trae O95.\n");
    exit(1);
}

$enfermedad = $pdo->query("SELECT id FROM enfermedad WHERE cie10 = 'O95'")->fetch();
if (!$enfermedad) {
    fwrite(STDERR, "No existe O95 en esta base: correr antes cargar_fichas.php.\n");
    exit(1);
}
$campos = CampoDef::porEnfermedad((int) $enfermedad['id']);
$idPorClave = [];
foreach ($campos as $id => $campo) {
    $idPorClave[$campo['clave']] = (int) $id;
}

$seccionesTocadas = ['Datos del fallecimiento (Anexo 1)', 'Referencia (Anexo 1)'];
$clavesTocadas = [];
$actualizar = $pdo->prepare('UPDATE campo_def SET depende_de = ?, valor_activador = ?, config = ? WHERE id = ?');
$cambiados = 0;
foreach ($ficha['secciones'] as $seccion) {
    if (!in_array($seccion['nombre'], $seccionesTocadas, true)) {
        continue;
    }
    foreach ($seccion['campos'] as $declarado) {
        $clave = $declarado['clave'];
        $id = $idPorClave[$clave] ?? null;
        if ($id === null) {
            fwrite(STDERR, "No existe {$clave} en esta base: correr antes cargar_fichas.php.\n");
            exit(1);
        }
        $actual = $campos[$id];
        $config = json_decode((string) $actual['config'], true) ?: [];
        if (!empty($declarado['con_hora'])) {
            $config['con_hora'] = true;
        }
        $configJson = $config ? json_encode($config, JSON_UNESCAPED_UNICODE) : null;
        // depende_de del manifiesto: clave o etiqueta del padre en la ficha.
        $padreId = null;
        if (!empty($declarado['depende_de'])) {
            $padreId = $idPorClave[$declarado['depende_de']] ?? null;
            if ($padreId === null) {
                foreach ($campos as $idOtro => $otro) {
                    if ($otro['etiqueta'] === $declarado['depende_de']) {
                        $padreId = (int) $idOtro;
                    }
                }
            }
            if ($padreId === null) {
                fwrite(STDERR, "{$clave}: no se encuentra el padre \"{$declarado['depende_de']}\".\n");
                exit(1);
            }
            $clavesTocadas[] = $clave;
        }
        $activador = $declarado['valor_activador'] ?? null;
        if ((int) $actual['depende_de'] === (int) $padreId && $actual['valor_activador'] === $activador
            && (json_decode((string) $actual['config'], true) ?: []) == $config) {
            continue;
        }
        $actualizar->execute([$padreId, $activador, $configJson, $id]);
        $cambiados++;
    }
}

$reglas = $ficha['reglas_campos'] ?? [];
foreach ($reglas as $regla) {
    foreach ($regla['mostrar'] ?? [] as $clave) {
        $clavesTocadas[] = $clave;
    }
}
$reglasJson = $reglas ? json_encode($reglas, JSON_UNESCAPED_UNICODE) : null;
$reglasActuales = $pdo->query("SELECT reglas_campos FROM enfermedad WHERE id = {$enfermedad['id']}")->fetchColumn();
if ((json_decode((string) $reglasActuales, true) ?: null) != ($reglas ?: null)) {
    $pdo->prepare('UPDATE enfermedad SET reglas_campos = ? WHERE id = ?')->execute([$reglasJson, $enfermedad['id']]);
    $cambiados++;
}

echo $cambiados === 0
    ? "O95 ya tenía las condiciones de lugar y referencia.\n"
    : "{$cambiados} declaración(es) de O95 actualizadas (campos y reglas_campos).\n";

// Valores guardados: lo mismo que decide validarCamposDinamicos() con
// campoVisiblePorDependencia() y la regla "mostrar" de aplicarReglasCampos(),
// hasta que nada cambie (un padre vaciado puede ocultar a sus hijos). Solo se
// borran los campos que este script condiciona.
$campos = CampoDef::porEnfermedad((int) $enfermedad['id']);
$clavesTocadas = array_unique($clavesTocadas);
$valoresPorCaso = [];
$filas = $pdo->prepare(
    'SELECT cv.caso_id, cv.campo_def_id, cv.valor FROM caso_valor cv JOIN caso c ON c.id = cv.caso_id WHERE c.enfermedad_id = ?'
);
$filas->execute([$enfermedad['id']]);
foreach ($filas->fetchAll() as $fila) {
    $decodificado = json_decode((string) $fila['valor'], true);
    $valoresPorCaso[(int) $fila['caso_id']][(int) $fila['campo_def_id']] = is_array($decodificado) ? $decodificado : (string) $fila['valor'];
}

$borrar = $pdo->prepare('DELETE FROM caso_valor WHERE caso_id = ? AND campo_def_id = ?');
$borrados = 0;
foreach ($valoresPorCaso as $casoId => $valores) {
    do {
        $huboCambio = false;
        $valorPorClave = fn (string $clave) => $valores[$idPorClave[$clave] ?? 0] ?? '';
        foreach ($clavesTocadas as $clave) {
            $id = $idPorClave[$clave];
            if (in_array($valores[$id] ?? '', ['', []], true)) {
                continue;
            }
            $visible = campoVisiblePorDependencia($campos[$id], $valores);
            foreach ($reglas as $regla) {
                if (in_array($clave, $regla['mostrar'] ?? [], true) && !condicionReglaCampos($regla['si'], $valorPorClave)) {
                    $visible = false;
                }
            }
            if (!$visible) {
                $borrar->execute([$casoId, $id]);
                $valores[$id] = '';
                $borrados++;
                $huboCambio = true;
            }
        }
    } while ($huboCambio);
}

echo $borrados === 0
    ? "Ningún caso de O95 tenía guardados datos de lugar o referencia que no correspondían.\n"
    : "{$borrados} valor(es) de O95 borrados: eran de un lugar, tipo de EE.SS., referencia o etapa que no corresponde al caso.\n";
