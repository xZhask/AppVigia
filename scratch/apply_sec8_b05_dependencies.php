<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// 1. Convertir los 4 activadores principales a SELECT con catalogo 145 (SI, NO, DESCONOCIDO)
$pdo->exec("UPDATE campo_def SET tipo = 'SELECT', catalogo_id = 145 WHERE id IN (16067, 16071, 16076, 16081)");

// 2. Asignar dependencias para Búsqueda activa institucional (ID 16067)
$pdo->exec("UPDATE campo_def SET depende_de = 16067, valor_activador = 'SI' WHERE id IN (16068, 16069, 16070)");

// 3. Crear los 3 campos faltantes de Búsqueda activa comunitaria si no existen
$stmt = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2778 AND clave = 'b05_casos_cumplen_definicion'");
$stmt->execute();
$idCumplenDef = $stmt->fetchColumn();

if (!$idCumplenDef) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2778, 'b05_casos_cumplen_definicion', 'N.º casos que cumplen definición de caso', 'NUMERO', 0, 16071, 'SI', 6)");
    $ins->execute();
    $idCumplenDef = $pdo->lastInsertId();
}

$stmt = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2778 AND clave = 'b05_casos_nuevos_sistema'");
$stmt->execute();
$idNuevosSistema = $stmt->fetchColumn();

if (!$idNuevosSistema) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2778, 'b05_casos_nuevos_sistema', 'N.º casos nuevos que ingresan al sistema', 'NUMERO', 0, 16071, 'SI', 7)");
    $ins->execute();
    $idNuevosSistema = $pdo->lastInsertId();
}

$stmt = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2778 AND clave = 'b05_casos_ya_en_vigilancia'");
$stmt->execute();
$idYaVigilancia = $stmt->fetchColumn();

if (!$idYaVigilancia) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2778, 'b05_casos_ya_en_vigilancia', 'N.º casos que ya se encuentran en sistema de vigilancia', 'NUMERO', 0, 16071, 'SI', 8)");
    $ins->execute();
    $idYaVigilancia = $pdo->lastInsertId();
}

// 4. Asignar dependencias para Búsqueda activa comunitaria (ID 16071)
$pdo->exec("UPDATE campo_def SET depende_de = 16071, valor_activador = 'SI' WHERE id IN (16072, 16073, 16074, 16075)");

// 5. Crear los campos de desglose de edad para Bloqueo Vacunal si no existen
$stmt = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2778 AND clave = 'vacunados_bloqueo_menor_1'");
$stmt->execute();
$idVacMenor1 = $stmt->fetchColumn();

if (!$idVacMenor1) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2778, 'vacunados_bloqueo_menor_1', 'Número de vacunados en bloqueo (< 1 año)', 'NUMERO', 0, 16076, 'SI', 17)");
    $ins->execute();
    $idVacMenor1 = $pdo->lastInsertId();
}

$stmt = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2778 AND clave = 'vacunados_bloqueo_1_4'");
$stmt->execute();
$idVac14 = $stmt->fetchColumn();

if (!$idVac14) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2778, 'vacunados_bloqueo_1_4', 'Número de vacunados en bloqueo (1 - 4 años)', 'NUMERO', 0, 16076, 'SI', 18)");
    $ins->execute();
    $idVac14 = $pdo->lastInsertId();
}

$stmt = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2778 AND clave = 'vacunados_bloqueo_5_14'");
$stmt->execute();
$idVac514 = $stmt->fetchColumn();

if (!$idVac514) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2778, 'vacunados_bloqueo_5_14', 'Número de vacunados en bloqueo (5 - 14 años)', 'NUMERO', 0, 16076, 'SI', 19)");
    $ins->execute();
    $idVac514 = $pdo->lastInsertId();
}

$stmt = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2778 AND clave = 'vacunados_bloqueo_mayor_15'");
$stmt->execute();
$idVacMayor15 = $stmt->fetchColumn();

if (!$idVacMayor15) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2778, 'vacunados_bloqueo_mayor_15', 'Número de vacunados en bloqueo (> 15 años)', 'NUMERO', 0, 16076, 'SI', 20)");
    $ins->execute();
    $idVacMayor15 = $pdo->lastInsertId();
}

// 6. Asignar dependencias para Bloqueo Vacunal (ID 16076)
$pdo->exec("UPDATE campo_def SET depende_de = 16076, valor_activador = 'SI' WHERE id IN (16077, 16078, 16079, 16080)");

// 7. Asignar dependencias para Monitoreo rápido (ID 16081)
$pdo->exec("UPDATE campo_def SET depende_de = 16081, valor_activador = 'SI' WHERE id = 16082");

// 8. Reordenar campos en sección 2778
$ordenCampos = [
    16067, // Búsqueda activa inst
    16068, // Total Dx revisados
    16069, // Casos que ya existian
    16070, // Casos nuevos ingresados
    16071, // Búsqueda activa comunitaria
    (int) $idCumplenDef, // Nº casos que cumplen definicion
    (int) $idNuevosSistema, // Nº casos nuevos que ingresan al sistema
    (int) $idYaVigilancia, // Nº casos que ya se encuentran en vigilancia
    16072, // Casas abiertas
    16073, // Casas cerradas
    16074, // Casas abandonadas
    16075, // Total casas
    16076, // ¿Se realizo bloqueo vacunal?
    16077, // Fecha inicio bloqueo
    16078, // Fecha termino bloqueo
    16079, // Localidades bloqueo
    (int) $idVacMenor1,
    (int) $idVac14,
    (int) $idVac514,
    (int) $idVacMayor15,
    16080, // Total vacunados bloqueo (Total VAC)
    16081, // ¿Se realizo MRC?
    16082, // % vacunados MRC
];

$i = 1;
foreach ($ordenCampos as $cId) {
    $pdo->prepare("UPDATE campo_def SET orden = :ord WHERE id = :id")->execute(['ord' => $i, 'id' => $cId]);
    $i++;
}

echo "Sección 2778 (Investigación epidemiológica) actualizada y reordenada exitosamente con dependencias.\n";
