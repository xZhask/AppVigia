<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Z21 (gestante con VIH y niño expuesto), pedido del usuario 2026-09-13: el
// "Formulario de registro de casos ... identificados por búsqueda activa
// institucional" (pág. 15 del PDF) clasifica cada caso como Gestante con VIH
// (1), Aborto (2), Mortinato (3) o Niño nacido expuesto al VIH (4). La ficha
// no lo pregunta: CasosController la calcula al guardar con las reglas
// "clasificar" de enfermedad.reglas_campos y la guarda en caso.clasificacion.
//
// 1) caso.clasificacion es un ENUM real (ver ampliar_enum_clasificacion_caso.php):
//    sin los 4 valores nuevos el guardado falla. Ampliar un ENUM no toca
//    ninguna fila existente.
$pdo->exec(
    "ALTER TABLE caso MODIFY clasificacion
     ENUM('SOSPECHOSO','PROBABLE','CONFIRMADO','COMPATIBLE','DESCARTADO',
          'DIRECTA','INDIRECTA','INCIDENTAL','POR_DETERMINAR',
          'GESTANTE_CON_VIH','ABORTO','MORTINATO','NINO_EXPUESTO_VIH')
     COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SOSPECHOSO'"
);
echo "caso.clasificacion ampliado: " . $pdo->query("SHOW COLUMNS FROM caso LIKE 'clasificacion'")->fetch()['Type'] . "\n";

// 2) Las 4 opciones de Z21, mismo mecanismo que set_opciones_clasificacion_a00.php.
$pdo->prepare('UPDATE enfermedad SET opciones_clasificacion = ? WHERE cie10 = ?')
    ->execute(['GESTANTE_CON_VIH,ABORTO,MORTINATO,NINO_EXPUESTO_VIH', 'Z21']);
echo "enfermedad.opciones_clasificacion actualizado para Z21.\n";
