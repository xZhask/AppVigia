<?php
/**
 * A97 sin fiebre amarilla (2026-10-30, decisión del usuario: "si tiene su
 * ficha propia, esa es la que manda"). Fiebre amarilla se notifica solo en
 * su ficha A95; en A97 era una opción más de "Enfermedad / evento a
 * notificar", y un mismo caso podía sumar en dos filas de los reportes.
 *
 * Quita de la base lo mismo que ya quitó el manifiesto:
 *   - la opción FIEBRE_AMARILLA_CIE_10_A95 del catálogo de a97_enfermedad_evento;
 *   - el campo a97_clasificacion_fiebre_amarilla (solo se mostraba con esa
 *     opción) y su catálogo propio;
 *   - sus 4 reglas_campos de clasificación (copia reglas_campos del manifiesto).
 *
 * Es para las bases donde A97 tiene casos y cargar_fichas.php la deja
 * bloqueada. Si algún caso guardó la opción o el campo, no toca nada y los
 * lista: esos casos hay que pasarlos antes a la ficha A95. Idempotente.
 *
 * Uso: php sql/migraciones/set_a97_sin_fiebre_amarilla.php
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();
$manifiesto = json_decode(file_get_contents(__DIR__ . '/../../manifiesto_fichas.json'), true);

$enfermedad = $pdo->query("SELECT id, reglas_campos FROM enfermedad WHERE cie10 = 'A97'")->fetch();
if (!$enfermedad) {
    fwrite(STDERR, "A97 no existe en esta base (correr antes cargar_fichas.php).\n");
    exit(1);
}
$enfermedadId = (int) $enfermedad['id'];

$campo = function (string $clave) use ($pdo, $enfermedadId): ?array {
    $consulta = $pdo->prepare('SELECT cd.* FROM campo_def cd JOIN seccion_def s ON s.id = cd.seccion_id
                                WHERE s.enfermedad_id = ? AND cd.clave = ?');
    $consulta->execute([$enfermedadId, $clave]);

    return $consulta->fetch() ?: null;
};

$evento = $campo('a97_enfermedad_evento');
$clasificacion = $campo('a97_clasificacion_fiebre_amarilla');
if (!$evento) {
    fwrite(STDERR, "A97: no existe a97_enfermedad_evento en esta base (correr antes cargar_fichas.php).\n");
    exit(1);
}

// Casos que usaron la opción o el campo: si hay, no se toca nada.
$consulta = $pdo->prepare("SELECT DISTINCT c.codigo FROM caso_valor cv JOIN caso c ON c.id = cv.caso_id
                            WHERE (cv.campo_def_id = ? AND cv.valor = 'FIEBRE_AMARILLA_CIE_10_A95')
                               OR (cv.campo_def_id = ? AND cv.valor <> '')
                            ORDER BY c.codigo");
$consulta->execute([(int) $evento['id'], (int) ($clasificacion['id'] ?? 0)]);
$casos = $consulta->fetchAll(PDO::FETCH_COLUMN);
if ($casos) {
    fwrite(STDERR, 'A97: ' . count($casos) . ' caso(s) registraron fiebre amarilla en esta ficha: ' . implode(', ', $casos)
        . ".\nNo se cambió nada. Pásalos a la ficha A95 (o anúlalos) y vuelve a correr el script.\n");
    exit(1);
}

$pdo->beginTransaction();

$borrar = $pdo->prepare("DELETE FROM catalogo_item WHERE catalogo_id = ? AND valor = 'FIEBRE_AMARILLA_CIE_10_A95'");
$borrar->execute([(int) $evento['catalogo_id']]);
echo $borrar->rowCount() ? "A97: opción \"Fiebre amarilla\" quitada de Enfermedad / evento a notificar.\n"
    : "A97: la opción \"Fiebre amarilla\" ya no estaba.\n";

if ($clasificacion) {
    $pdo->prepare('DELETE FROM caso_valor WHERE campo_def_id = ?')->execute([(int) $clasificacion['id']]);
    $pdo->prepare('DELETE FROM campo_def WHERE id = ?')->execute([(int) $clasificacion['id']]);
    $catalogoId = (int) $clasificacion['catalogo_id'];
    $usos = $pdo->prepare('SELECT COUNT(*) FROM campo_def WHERE catalogo_id = ?');
    $usos->execute([$catalogoId]);
    if ($catalogoId && !(int) $usos->fetchColumn()) {
        $pdo->prepare('DELETE FROM catalogo_item WHERE catalogo_id = ?')->execute([$catalogoId]);
        $pdo->prepare('DELETE FROM catalogo WHERE id = ?')->execute([$catalogoId]);
    }
    echo "A97: campo \"Clasificación (Fiebre amarilla)\" quitado.\n";
} else {
    echo "A97: el campo \"Clasificación (Fiebre amarilla)\" ya no estaba.\n";
}

$declaradas = $manifiesto['fichas']['A97']['reglas_campos'] ?? null;
if ((json_decode((string) $enfermedad['reglas_campos'], true) ?: null) != ($declaradas ?: null)) {
    $pdo->prepare('UPDATE enfermedad SET reglas_campos = ? WHERE id = ?')
        ->execute([$declaradas ? json_encode($declaradas, JSON_UNESCAPED_UNICODE) : null, $enfermedadId]);
    echo "A97: reglas de clasificación copiadas del manifiesto (sin las de fiebre amarilla).\n";
} else {
    echo "A97: las reglas de clasificación ya coincidían con el manifiesto.\n";
}

$pdo->commit();
