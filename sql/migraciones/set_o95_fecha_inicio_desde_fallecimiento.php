<?php
/**
 * O95 (2026-10-15): la ficha no pinta la fecha de inicio de síntomas genérica
 * pero el servidor la exigía y la sacaba del primer FECHA lleno del POST; sin
 * ninguno, la ficha no se registraba y el aviso pedía un campo que no está en
 * pantalla. Ahora nucleo_ajustes.fecha_inicio_sintomas_desde declara que
 * caso.fecha_inicio_sintomas sale de la fecha de fallecimiento, que pasa a
 * ser obligatoria (ya tenía el asterisco en pantalla).
 *
 * El manifiesto ya lo declara; este script es para las bases donde O95 tiene
 * casos y cargar_fichas.php la deja bloqueada. Fija el ajuste y la
 * obligatoriedad, y en los casos guardados pone caso.fecha_inicio_sintomas =
 * fecha de fallecimiento cuando esa fecha está grabada (lo mismo que haría el
 * servidor al editar y guardar). Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$enfermedad = $pdo->query("SELECT id, nucleo_ajustes FROM enfermedad WHERE cie10 = 'O95'")->fetch();
$campo = $pdo->query(
    "SELECT cd.id, cd.obligatorio FROM campo_def cd
       JOIN seccion_def sd ON sd.id = cd.seccion_id
       JOIN enfermedad e ON e.id = sd.enfermedad_id
      WHERE e.cie10 = 'O95' AND cd.clave = 'o95_fecha_de_fallecimiento'"
)->fetch();
if (!$enfermedad || !$campo) {
    fwrite(STDERR, "No existe O95 o su fecha de fallecimiento en esta base: correr antes cargar_fichas.php.\n");
    exit(1);
}

$cambios = 0;
$ajustes = json_decode((string) $enfermedad['nucleo_ajustes'], true) ?: [];
if (($ajustes['fecha_inicio_sintomas_desde'] ?? null) !== 'o95_fecha_de_fallecimiento') {
    $ajustes['fecha_inicio_sintomas_desde'] = 'o95_fecha_de_fallecimiento';
    $pdo->prepare('UPDATE enfermedad SET nucleo_ajustes = ? WHERE id = ?')
        ->execute([json_encode($ajustes, JSON_UNESCAPED_UNICODE), $enfermedad['id']]);
    $cambios++;
}
if ((int) $campo['obligatorio'] !== 1) {
    $pdo->prepare('UPDATE campo_def SET obligatorio = 1 WHERE id = ?')->execute([$campo['id']]);
    $cambios++;
}
echo $cambios === 0
    ? "O95 ya tomaba la fecha de inicio de síntomas de la fecha de fallecimiento.\n"
    : "O95: fecha de inicio de síntomas desde la fecha de fallecimiento, que ahora es obligatoria.\n";

$actualizados = $pdo->prepare(
    "UPDATE caso c
       JOIN caso_valor cv ON cv.caso_id = c.id AND cv.campo_def_id = ?
        SET c.fecha_inicio_sintomas = cv.valor
      WHERE c.enfermedad_id = ?
        AND cv.valor REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
        AND NOT (c.fecha_inicio_sintomas <=> cv.valor)"
);
$actualizados->execute([$campo['id'], $enfermedad['id']]);
echo $actualizados->rowCount() === 0
    ? "Ningún caso de O95 tenía otra fecha de inicio de síntomas.\n"
    : $actualizados->rowCount() . " caso(s) de O95 con la fecha de inicio de síntomas tomada de la fecha de fallecimiento.\n";

$sinFecha = $pdo->prepare(
    "SELECT COUNT(*) FROM caso c
      WHERE c.enfermedad_id = ?
        AND NOT EXISTS (SELECT 1 FROM caso_valor cv WHERE cv.caso_id = c.id AND cv.campo_def_id = ? AND cv.valor <> '')"
);
$sinFecha->execute([$enfermedad['id'], $campo['id']]);
if ((int) $sinFecha->fetchColumn() > 0) {
    echo "Aviso: hay caso(s) de O95 sin fecha de fallecimiento; al editarlos se pedirá.\n";
}
