<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// 1. Agregar 'DESCONOCIDO' a Catálogo 12 si no existe
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM catalogo_item WHERE catalogo_id = 12 AND valor = 'DESCONOCIDO'");
$stmtCheck->execute();
if ($stmtCheck->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES (12, 'DESCONOCIDO', 'Desconocido', 8)");
    echo "Agregado 'DESCONOCIDO' a Catálogo 12.\n";
}

// 2. Crear catálogo para 'Casos reportados en últimos 30 días en su jurisdicción'
$newCid = null;
$stmtFind = $pdo->prepare("SELECT id FROM catalogo WHERE nombre = 'casos_reportados_30_dias_b05'");
$stmtFind->execute();
$newCid = $stmtFind->fetchColumn();

if (!$newCid) {
    $insCat = $pdo->prepare("INSERT INTO catalogo (nombre) VALUES ('casos_reportados_30_dias_b05')");
    $insCat->execute();
    $newCid = $pdo->lastInsertId();

    $itemsReportados = [
        ['SI_SARAMPION', 'Sí, con sarampión', 1],
        ['SI_RUBEOLA', 'Sí, con rubeola', 2],
        ['SI_AMBOS', 'Sí, con ambos', 3],
        ['NO', 'No', 4],
        ['DESCONOCIDO', 'Desconocido', 5],
    ];

    foreach ($itemsReportados as $it) {
        $ins = $pdo->prepare("INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES (:cid, :val, :etiq, :orden)");
        $ins->execute(['cid' => $newCid, 'val' => $it[0], 'etiq' => $it[1], 'orden' => $it[2]]);
    }
    echo "Creado nuevo Catálogo ID $newCid ('casos_reportados_30_dias_b05') con las 5 opciones.\n";
} else {
    echo "Ya existe Catálogo ID $newCid.\n";
}

// 3. Actualizar campos existentes en BD:
// ID 16060: ¿El caso es contacto de otro caso conocido? -> SELECT, catalogo_id 145
$pdo->exec("UPDATE campo_def SET tipo = 'SELECT', catalogo_id = 145 WHERE id = 16060");

// ID 16061: Código del caso con el que tuvo contacto -> depende_de = 16060, valor_activador = 'SI'
$pdo->exec("UPDATE campo_def SET depende_de = 16060, valor_activador = 'SI' WHERE id = 16061");

// ID 16062: ¿Tuvo contacto con gestante en las primeras 20 semanas? -> SELECT, catalogo_id 145
$pdo->exec("UPDATE campo_def SET tipo = 'SELECT', catalogo_id = 145 WHERE id = 16062");

// ID 16063: Nombre de la gestante -> depende_de = 16062, valor_activador = 'SI'
$pdo->exec("UPDATE campo_def SET depende_de = 16062, valor_activador = 'SI' WHERE id = 16063");

// ID 16064: Fecha del contacto con la gestante -> depende_de = 16062, valor_activador = 'SI'
$pdo->exec("UPDATE campo_def SET depende_de = 16062, valor_activador = 'SI' WHERE id = 16064");

// ID 16083: ¿Hubo casos reportados de sarampión en los últimos 30 días en su jurisdicción? -> Mover a seccion 2776, tipo SELECT, catalogo_id = $newCid
$pdo->exec("UPDATE campo_def SET seccion_id = 2776, tipo = 'SELECT', catalogo_id = $newCid, etiqueta = '¿Hubo casos reportados de sarampión en los últimos 30 días en su jurisdicción?' WHERE id = 16083");

echo "Campos existentes actualizados en BD.\n";

// 4. Crear los campos nuevos si no existen:
// Campo nuevo: 'Especificar otra captación del caso'
$stmtExist1 = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2776 AND etiqueta = 'Especificar otra captación del caso'");
$stmtExist1->execute();
$idEspCapt = $stmtExist1->fetchColumn();

if (!$idEspCapt) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2776, 'especificar_otra_captacion_caso', 'Especificar otra captación del caso', 'TEXTO', 0, 16059, 'OTROS', 2)");
    $ins->execute();
    $idEspCapt = $pdo->lastInsertId();
    echo "Creado campo 'Especificar otra captación del caso' (ID $idEspCapt).\n";
}

// Campo nuevo: '¿Se han reportado otras enfermedades eruptivas febriles en su jurisdicción?'
$stmtExist2 = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2776 AND etiqueta LIKE '%enfermedades eruptivas febriles%'");
$stmtExist2->execute();
$idOtrasFeb = $stmtExist2->fetchColumn();

if (!$idOtrasFeb) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, catalogo_id, orden) VALUES (2776, 'reporto_otras_febriles_eruptivas', '¿Se han reportado otras enfermedades eruptivas febriles en su jurisdicción?', 'SELECT', 0, 145, 9)");
    $ins->execute();
    $idOtrasFeb = $pdo->lastInsertId();
    echo "Creado campo '¿Se han reportado otras enfermedades eruptivas febriles en su jurisdicción?' (ID $idOtrasFeb).\n";
}

// Campo nuevo: 'Especificar otras enfermedades eruptivas febriles reportadas'
$stmtExist3 = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2776 AND etiqueta LIKE '%Especificar otras enfermedades eruptivas%'");
$stmtExist3->execute();
$idEspOtrasFeb = $stmtExist3->fetchColumn();

if (!$idEspOtrasFeb) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2776, 'especificar_otras_febriles_eruptivas', 'Especificar otras enfermedades eruptivas febriles reportadas', 'TEXTO', 0, :dep, 'SI', 10)");
    $ins->execute(['dep' => $idOtrasFeb]);
    $idEspOtrasFeb = $pdo->lastInsertId();
    echo "Creado campo 'Especificar otras enfermedades eruptivas febriles reportadas' (ID $idEspOtrasFeb).\n";
}

// Reordenar campos en sección 2776 para orden limpio 1..N
$ordenDeseado = [
    16059, // Captación del caso
    (int) $idEspCapt, // Especificar otra captación
    16060, // ¿El caso es contacto de otro caso conocido?
    16061, // Código del caso con el que tuvo contacto
    16062, // ¿Tuvo contacto con gestante en las primeras 20 semanas?
    16063, // Nombre de la gestante
    16064, // Fecha del contacto con la gestante
    16083, // ¿Hubo casos reportados de sarampión en los últimos 30 días?
    (int) $idOtrasFeb, // ¿Se han reportado otras enfermedades eruptivas febriles?
    (int) $idEspOtrasFeb, // Especificar otras enfermedades eruptivas febriles
];

$i = 1;
foreach ($ordenDeseado as $cId) {
    $pdo->prepare("UPDATE campo_def SET orden = :ord WHERE id = :id")->execute(['ord' => $i, 'id' => $cId]);
    $i++;
}

echo "Reordenados los campos de Antecedentes epidemiológicos (1 a " . ($i - 1) . ").\n";
