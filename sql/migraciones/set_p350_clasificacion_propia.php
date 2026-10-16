<?php
/**
 * P35.0 (2026-10-16): la ficha mostraba dos "Clasificación del caso" (el chip
 * genérico y la suya, ítem 7 del PDF). Como en A37.0, el chip ya no se pinta
 * (nucleo_omitidos) y caso.clasificacion se calcula de la clasificación propia
 * con reglas_campos "clasificar" (Infección congénita queda en Sospechoso,
 * como hacía ficha.js). El bloque "Seguimiento de excreción viral"
 * (bloques_condicionales) pasa a depender de esa clasificación propia.
 *
 * El manifiesto ya lo declara; este script es para las bases donde P35.0
 * tiene casos y cargar_fichas.php la deja bloqueada. Copia del manifiesto
 * nucleo_omitidos, reglas_campos y bloques_condicionales, y recalcula
 * caso.clasificacion de los casos guardados. Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';
require_once __DIR__ . '/../../app/Core/ayudantes.php';

use App\Core\Database;
use App\Models\CampoDef;

$pdo = Database::conexion();

$manifiesto = json_decode(file_get_contents(__DIR__ . '/../../manifiesto_fichas.json'), true);
$ficha = $manifiesto['fichas']['P35.0'] ?? null;
$enfermedad = $pdo->query("SELECT * FROM enfermedad WHERE cie10 = 'P35.0'")->fetch();
if (!$ficha || !$enfermedad) {
    fwrite(STDERR, "No existe P35.0 en el manifiesto o en esta base: correr antes cargar_fichas.php.\n");
    exit(1);
}

$cambios = 0;
foreach (['nucleo_omitidos', 'reglas_campos', 'bloques_condicionales'] as $columna) {
    $declarado = $ficha[$columna] ?? null;
    if ((json_decode((string) $enfermedad[$columna], true) ?: null) == ($declarado ?: null)) {
        continue;
    }
    $pdo->prepare("UPDATE enfermedad SET {$columna} = ? WHERE id = ?")
        ->execute([$declarado ? json_encode($declarado, JSON_UNESCAPED_UNICODE) : null, $enfermedad['id']]);
    $cambios++;
}
echo $cambios === 0
    ? "P35.0 ya calculaba su clasificación desde la propia.\n"
    : "P35.0: {$cambios} ajuste(s) de enfermedad copiados del manifiesto.\n";

$enfermedad = $pdo->query("SELECT * FROM enfermedad WHERE cie10 = 'P35.0'")->fetch();
$idPorClave = array_column(array_map(
    fn($id, $campo) => ['clave' => $campo['clave'], 'id' => $id],
    array_keys($campos = CampoDef::porEnfermedad((int) $enfermedad['id'])),
    $campos
), 'id', 'clave');
$casos = $pdo->prepare('SELECT id, clasificacion FROM caso WHERE enfermedad_id = ?');
$casos->execute([$enfermedad['id']]);
$valoresDe = $pdo->prepare('SELECT campo_def_id, valor FROM caso_valor WHERE caso_id = ?');
$actualizar = $pdo->prepare('UPDATE caso SET clasificacion = ? WHERE id = ?');
$recalculados = 0;
foreach ($casos->fetchAll() as $caso) {
    $valoresDe->execute([$caso['id']]);
    $valores = $valoresDe->fetchAll(PDO::FETCH_KEY_PAIR);
    $nueva = clasificacionDerivada($enfermedad, fn(string $clave) => (string) ($valores[$idPorClave[$clave] ?? 0] ?? ''));
    if ($nueva !== null && $nueva !== $caso['clasificacion']) {
        $actualizar->execute([$nueva, $caso['id']]);
        $recalculados++;
    }
}
echo $recalculados === 0
    ? "Ningún caso de P35.0 cambió de clasificación.\n"
    : "{$recalculados} caso(s) de P35.0 con la clasificación recalculada desde la propia.\n";
