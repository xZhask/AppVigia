<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// P96, muerte fetal y neonatal (cotejo 2026-09-13, pág. 28 del PDF).
//
// 1) Deja de ser multi_sujeto. La madre solo aportaba "Residencia habitual
//    de la madre" (caso_sujeto, rol MADRE), duplicada con la residencia del
//    núcleo del fallecido: la residencia habitual de un feto o de un neonato
//    es la de su madre, así que ahora es UNA sola, la del núcleo, titulada
//    "Residencia habitual de la madre" (enfermedad.nucleo_ajustes). Mismo
//    criterio que set_z21_sujeto_unico.php: multi_sujeto/roles_sujeto no los
//    gestiona cargar_fichas.php. P96 no tenía ningún caso guardado.
$casosP96 = (int) $pdo->query("SELECT COUNT(*) FROM caso c JOIN enfermedad e ON e.id = c.enfermedad_id WHERE e.cie10 = 'P96'")->fetchColumn();
if ($casosP96 > 0) {
    fwrite(STDERR, "P96 ya tiene {$casosP96} caso(s): revisar a mano sus filas de caso_sujeto (rol MADRE) antes de quitar multi_sujeto.\n");
    exit(1);
}
$pdo->prepare("UPDATE enfermedad SET multi_sujeto = 0, roles_sujeto = NULL WHERE cie10 = 'P96'")->execute();
echo "P96: multi_sujeto = 0, roles_sujeto = NULL.\n";

// 2) Clasificación calculada, no preguntada: "Tipo de muerte" (Fetal /
//    Neonatal) la decide con reglas "clasificar" de enfermedad.reglas_campos.
//    caso.clasificacion es un ENUM real: sin los 2 valores nuevos el guardado
//    falla. Ampliar un ENUM no toca ninguna fila existente.
$pdo->exec(
    "ALTER TABLE caso MODIFY clasificacion
     ENUM('SOSPECHOSO','PROBABLE','CONFIRMADO','COMPATIBLE','DESCARTADO',
          'DIRECTA','INDIRECTA','INCIDENTAL','POR_DETERMINAR',
          'GESTANTE_CON_VIH','ABORTO','MORTINATO','NINO_EXPUESTO_VIH',
          'MUERTE_FETAL','MUERTE_NEONATAL')
     COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SOSPECHOSO'"
);
echo "caso.clasificacion ampliado: " . $pdo->query("SHOW COLUMNS FROM caso LIKE 'clasificacion'")->fetch()['Type'] . "\n";

$pdo->prepare('UPDATE enfermedad SET opciones_clasificacion = ? WHERE cie10 = ?')
    ->execute(['MUERTE_FETAL,MUERTE_NEONATAL', 'P96']);
echo "enfermedad.opciones_clasificacion actualizado para P96.\n";
