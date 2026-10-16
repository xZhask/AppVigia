<?php
/**
 * Fecha de inicio de síntomas por ficha (2026-10-16, decisión 2 de
 * HOJA_DE_RUTA.md): las fichas que la piden con otro nombre declaran en
 * nucleo_ajustes.fecha_inicio_sintomas_desde de qué campo (o campos) sale
 * caso.fecha_inicio_sintomas: A35, A37.0, A97, B57, B04X, A00, B01, A95 (la
 * más temprana de sus síntomas), B55 (solo la de la enfermedad mucosa), A80
 * (deficiencia motora), B05 (inicio de la erupción) y A44.
 * P35.0 (congénita) la omite (nucleo_omitidos).
 *
 * Uso: php sql/migraciones/set_fecha_inicio_sintomas_desde.php A35 A37.0 A97 B57 B04X A00 B01 A95 B55 P35.0 A80 B05 A44
 *
 * El manifiesto ya lo declara; este script es para las bases donde esas
 * fichas tienen casos y cargar_fichas.php las deja bloqueadas. Por cada
 * ficha copia del manifiesto nucleo_ajustes y nucleo_omitidos y recalcula
 * caso.fecha_inicio_sintomas de los casos guardados, igual que al editar y
 * guardar (un campo que no aplica al caso no cuenta). Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';
require_once __DIR__ . '/../../app/Core/ayudantes.php';

use App\Core\Database;
use App\Models\CampoDef;

$fichas = array_slice($argv, 1);
if (!$fichas) {
    fwrite(STDERR, "Uso: php sql/migraciones/set_fecha_inicio_sintomas_desde.php A35 A37.0 A97 B57 B04X A00 B01 A95 B55 P35.0 A80 B05 A44\n");
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

    // Igual que cargar_fichas.php: las notas "_..." de nucleo_ajustes no se guardan.
    $declarados = [
        'nucleo_ajustes' => array_filter((array) ($ficha['nucleo_ajustes'] ?? []), fn($ajuste) => !str_starts_with((string) $ajuste, '_'), ARRAY_FILTER_USE_KEY),
        'nucleo_omitidos' => $ficha['nucleo_omitidos'] ?? null,
    ];
    $cambios = 0;
    foreach ($declarados as $columna => $declarado) {
        if ((json_decode((string) $enfermedad[$columna], true) ?: null) == ($declarado ?: null)) {
            continue;
        }
        $pdo->prepare("UPDATE enfermedad SET {$columna} = ? WHERE id = ?")
            ->execute([$declarado ? json_encode($declarado, JSON_UNESCAPED_UNICODE) : null, $enfermedad['id']]);
        $cambios++;
    }
    echo $cambios === 0
        ? "{$cie10}: ya tenía declarada su fecha de inicio de síntomas.\n"
        : "{$cie10}: {$cambios} ajuste(s) copiados del manifiesto.\n";

    $consulta->execute([$cie10]);
    $enfermedad = $consulta->fetch();
    $derivada = clavesFechaInicioSintomas($enfermedad) !== [];
    if (!$derivada && !nucleoOmitido($enfermedad, 'fecha_inicio_sintomas')) {
        echo "{$cie10}: la fecha se sigue pidiendo en el formulario; no se recalcula.\n";
        continue;
    }

    $campos = CampoDef::porEnfermedad((int) $enfermedad['id']);
    $idPorClave = [];
    foreach ($campos as $id => $campo) {
        $idPorClave[$campo['clave']] = (int) $id;
    }
    $casos = $pdo->prepare('SELECT * FROM caso WHERE enfermedad_id = ?');
    $casos->execute([$enfermedad['id']]);
    $valoresDe = $pdo->prepare('SELECT campo_def_id, valor FROM caso_valor WHERE caso_id = ?');
    $actualizar = $pdo->prepare('UPDATE caso SET fecha_inicio_sintomas = ? WHERE id = ?');
    $recalculados = 0;
    foreach ($casos->fetchAll() as $caso) {
        $valoresDe->execute([$caso['id']]);
        $crudos = $valoresDe->fetchAll(PDO::FETCH_KEY_PAIR);
        $valores = [];
        foreach ($crudos as $id => $valor) {
            $decodificado = json_decode((string) $valor, true);
            $valores[(int) $id] = is_array($decodificado) ? $decodificado : (string) $valor;
        }
        foreach (camposOcultosDelCaso($campos, $crudos, jsonDeEnfermedad($enfermedad, 'reglas_campos'), $caso) as $id => $_) {
            $valores[$id] = '';
        }
        // Omitida sin campo propio (P35.0): queda vacía, como al guardar.
        $nueva = $derivada ? fechaInicioSintomasDerivada($enfermedad, fn(string $clave) => $valores[$idPorClave[$clave] ?? 0] ?? '') : null;
        if ($nueva !== $caso['fecha_inicio_sintomas']) {
            $actualizar->execute([$nueva, $caso['id']]);
            $recalculados++;
        }
    }
    echo $recalculados === 0
        ? "{$cie10}: ningún caso cambió de fecha de inicio de síntomas.\n"
        : "{$cie10}: {$recalculados} caso(s) con la fecha de inicio de síntomas recalculada.\n";
}
