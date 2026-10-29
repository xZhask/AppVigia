<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Cuidado del plan de RENIEC (2026-10-29): el plan de apiperu.dev se agotó
// (100/100) porque el token estaba en config.php, versionado en un
// repositorio público. reniec_consulta.resultado distingue qué respondió
// el proveedor, para que PersonaService no repita un DNI que RENIEC no
// tenía y pause a todos tras un rechazo por cuota o token
// (ReniecConsulta::motivoParaNoConsultar). "encontrado" se mantiene.
//
// Las filas anteriores con encontrado = 0 quedan como FALLA, no como
// NO_ENCONTRADO: entonces no se distinguía una cuota agotada o un timeout
// de un DNI inexistente, y recordarlas como no encontradas bloquearía DNI
// válidos 30 días.
// Se puede correr más de una vez.
$existe = $pdo->query("SHOW COLUMNS FROM reniec_consulta LIKE 'resultado'")->fetchColumn();
if ($existe) {
    echo "reniec_consulta.resultado ya existe.\n";
    return;
}

$pdo->exec("ALTER TABLE reniec_consulta
    ADD COLUMN resultado enum('ENCONTRADO','NO_ENCONTRADO','RECHAZADA','FALLA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FALLA' AFTER encontrado,
    ADD KEY ix_reniec_resultado (resultado, fecha)");
$filas = $pdo->exec("UPDATE reniec_consulta SET resultado = 'ENCONTRADO' WHERE encontrado = 1");
echo "reniec_consulta.resultado agregada ($filas consulta(s) con resultado ENCONTRADO; el resto, FALLA).\n";
