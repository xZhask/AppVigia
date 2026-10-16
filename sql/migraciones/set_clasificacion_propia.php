<?php
/**
 * Clasificación única por ficha (2026-10-16, decisión 1 de HOJA_DE_RUTA.md,
 * ficha por ficha): el chip genérico "Clasificación del caso" no se pinta
 * (nucleo_omitidos 'clasificacion') y caso.clasificacion se calcula de la
 * clasificación propia de la ficha con reglas_campos "clasificar". Cuando el
 * chip exigía elegir (Confirmado/Descartado), el campo propio pasa a ser
 * obligatorio. Hecho así en A35, A33 y B57 (P35.0 tiene su propio script,
 * set_p350_clasificacion_propia.php).
 *
 * Uso: php sql/migraciones/set_clasificacion_propia.php A35 A33 B57
 *
 * El manifiesto ya lo declara; este script es para las bases donde esas
 * fichas tienen casos y cargar_fichas.php las deja bloqueadas. Por cada
 * ficha copia del manifiesto nucleo_omitidos y reglas_campos, fija la
 * obligatoriedad de sus campo_def como la declara el manifiesto y recalcula
 * caso.clasificacion de los casos guardados. Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';
require_once __DIR__ . '/../../app/Core/ayudantes.php';

use App\Core\Database;
use App\Models\CampoDef;

$fichas = array_slice($argv, 1);
if (!$fichas) {
    fwrite(STDERR, "Uso: php sql/migraciones/set_clasificacion_propia.php A35 A33 B57\n");
    exit(1);
}

$pdo = Database::conexion();
$manifiesto = json_decode(file_get_contents(__DIR__ . '/../../manifiesto_fichas.json'), true);

foreach ($fichas as $cie10) {
    $ficha = $manifiesto['fichas'][$cie10] ?? null;
    $consulta = $pdo->prepare('SELECT * FROM enfermedad WHERE cie10 = ?');
    $consulta->execute([$cie10]);
    $enfermedad = $consulta->fetch();
    if (!$ficha || !$enfermedad) {
        fwrite(STDERR, "{$cie10}: no existe en el manifiesto o en esta base (correr antes cargar_fichas.php).\n");
        exit(1);
    }
    if (!in_array('clasificacion', $ficha['nucleo_omitidos'] ?? [], true)) {
        fwrite(STDERR, "{$cie10}: el manifiesto no omite el chip de clasificación; nada que hacer.\n");
        exit(1);
    }

    $cambios = 0;
    foreach (['nucleo_omitidos', 'reglas_campos'] as $columna) {
        $declarado = $ficha[$columna] ?? null;
        if ((json_decode((string) $enfermedad[$columna], true) ?: null) == ($declarado ?: null)) {
            continue;
        }
        $pdo->prepare("UPDATE enfermedad SET {$columna} = ? WHERE id = ?")
            ->execute([$declarado ? json_encode($declarado, JSON_UNESCAPED_UNICODE) : null, $enfermedad['id']]);
        $cambios++;
    }

    $campos = CampoDef::porEnfermedad((int) $enfermedad['id']);
    $idPorClave = [];
    foreach ($campos as $id => $campo) {
        $idPorClave[$campo['clave']] = (int) $id;
    }
    $fijarObligatorio = $pdo->prepare('UPDATE campo_def SET obligatorio = ? WHERE id = ?');
    foreach ($ficha['secciones'] as $seccion) {
        foreach ($seccion['campos'] as $declarado) {
            $id = $idPorClave[$declarado['clave']] ?? null;
            if ($id === null) {
                fwrite(STDERR, "{$cie10}: no existe {$declarado['clave']} en esta base (correr antes cargar_fichas.php).\n");
                exit(1);
            }
            $obligatorio = !empty($declarado['obligatorio']) ? 1 : 0;
            if ((int) $campos[$id]['obligatorio'] !== $obligatorio) {
                $fijarObligatorio->execute([$obligatorio, $id]);
                $cambios++;
            }
        }
    }
    echo $cambios === 0
        ? "{$cie10}: ya calculaba la clasificación desde la propia.\n"
        : "{$cie10}: {$cambios} ajuste(s) copiados del manifiesto.\n";

    $consulta->execute([$cie10]);
    $enfermedad = $consulta->fetch();
    $casos = $pdo->prepare('SELECT id, clasificacion FROM caso WHERE enfermedad_id = ?');
    $casos->execute([$enfermedad['id']]);
    $valoresDe = $pdo->prepare('SELECT campo_def_id, valor FROM caso_valor WHERE caso_id = ?');
    $actualizar = $pdo->prepare('UPDATE caso SET clasificacion = ? WHERE id = ?');
    $recalculados = 0;
    $sinClasificacion = 0;
    foreach ($casos->fetchAll() as $caso) {
        $valoresDe->execute([$caso['id']]);
        $valores = $valoresDe->fetchAll(PDO::FETCH_KEY_PAIR);
        $nueva = clasificacionDerivada($enfermedad, fn(string $clave) => (string) ($valores[$idPorClave[$clave] ?? 0] ?? ''));
        if ($nueva === null) {
            $sinClasificacion++; // campo propio vacío: queda la guardada; al editar se pedirá
        } elseif ($nueva !== $caso['clasificacion']) {
            $actualizar->execute([$nueva, $caso['id']]);
            $recalculados++;
        }
    }
    echo $recalculados === 0
        ? "{$cie10}: ningún caso cambió de clasificación.\n"
        : "{$cie10}: {$recalculados} caso(s) con la clasificación recalculada desde la propia.\n";
    if ($sinClasificacion > 0) {
        echo "{$cie10}: aviso, {$sinClasificacion} caso(s) sin la clasificación propia; conservan la guardada y al editarlos se pedirá.\n";
    }
}
