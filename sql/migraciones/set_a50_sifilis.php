<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// A50, sífilis materna y congénita (cotejo 2026-09-14, págs. 18-19 del PDF:
// Anexo 4 de la Directiva Sanitaria N.° 062-MINSA/DGE-V.01, R.M. 127-2015/MINSA).
//
// 1) Deja de ser multi_sujeto. Hasta acá UN caso guardaba a la madre (MADRE) y
//    al recién nacido (RECIEN_NACIDO) juntos; ahora, igual que Z21
//    (set_z21_sujeto_unico.php), cada uno es su propio caso: la ficha de la
//    madre (sífilis materna, O98.1) y una por cada producto de la gestación
//    (sífilis congénita, A50), enlazada a la de su madre por
//    caso.caso_vinculado_id. multi_sujeto/roles_sujeto no los gestiona
//    cargar_fichas.php. Con casos guardados habría que repartir sus filas de
//    caso_sujeto a mano, así que se detiene.
$casosA50 = (int) $pdo->query("SELECT COUNT(*) FROM caso c JOIN enfermedad e ON e.id = c.enfermedad_id WHERE e.cie10 = 'A50'")->fetchColumn();
if ($casosA50 > 0) {
    fwrite(STDERR, "A50 ya tiene {$casosA50} caso(s): revisar a mano sus filas de caso_sujeto (roles MADRE y RECIEN_NACIDO) antes de quitar multi_sujeto.\n");
    exit(1);
}
$pdo->prepare("UPDATE enfermedad SET multi_sujeto = 0, roles_sujeto = NULL WHERE cie10 = 'A50'")->execute();
echo "A50: multi_sujeto = 0, roles_sujeto = NULL.\n";

// 2) Clasificación calculada con los valores que ya usa el sistema (decisión
//    del usuario): Probable / Confirmado / Descartado, como el tipo de
//    diagnóstico C/P/D del Anexo 3. Sale del ítem 12 (madre) o del 21
//    (producto) con reglas "clasificar"; el detalle (falso positivo, sífilis
//    memoria, niño expuesto no infectado) queda en su propio campo. Son
//    valores que el ENUM de caso.clasificacion ya tiene.
$pdo->prepare('UPDATE enfermedad SET opciones_clasificacion = ? WHERE cie10 = ?')
    ->execute(['PROBABLE,CONFIRMADO,DESCARTADO', 'A50']);
echo "enfermedad.opciones_clasificacion de A50: PROBABLE,CONFIRMADO,DESCARTADO.\n";
