<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Section ID para 'Clasificación final' en B05 es 2779

// 1. Crear catálogo para 'Criterio para confirmación' si no existe
$stmtFind = $pdo->prepare("SELECT id FROM catalogo WHERE nombre = 'criterio_confirmacion_b05'");
$stmtFind->execute();
$newCidConf = $stmtFind->fetchColumn();

if (!$newCidConf) {
    $insCat = $pdo->prepare("INSERT INTO catalogo (nombre) VALUES ('criterio_confirmacion_b05')");
    $insCat->execute();
    $newCidConf = $pdo->lastInsertId();

    $itemsConf = [
        ['LABORATORIO', 'Laboratorio', 1],
        ['NEXO_EPIDEMIOLOGICO', 'Nexo epidemiológico', 2],
        ['CLINICA', 'Clínica', 3],
    ];

    foreach ($itemsConf as $it) {
        $ins = $pdo->prepare("INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES (:cid, :val, :etiq, :orden)");
        $ins->execute(['cid' => $newCidConf, 'val' => $it[0], 'etiq' => $it[1], 'orden' => $it[2]]);
    }
    echo "Creado Catálogo ID $newCidConf ('criterio_confirmacion_b05').\n";
} else {
    echo "Ya existe Catálogo ID $newCidConf.\n";
}

// 2. Actualizar campo ID 16085 (Criterio para confirmación):
// Usar el catálogo nuevo ($newCidConf), depende_de = 16084 (Clasificación), valor_activador = 'SARAMPION,RUBEOLA'
$pdo->exec("UPDATE campo_def SET catalogo_id = $newCidConf, depende_de = 16084, valor_activador = 'SARAMPION,RUBEOLA', etiqueta = 'Criterio para confirmación' WHERE id = 16085");

// 3. Crear campo 'Si fue confirmación por Laboratorio, indicar resultado' (SELECT, catalogo_id 15, depende_de 16085, valor_activador LABORATORIO)
$stmtExist1 = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2779 AND clave = 'resultado_confirmacion_laboratorio'");
$stmtExist1->execute();
$idResLab = $stmtExist1->fetchColumn();

if (!$idResLab) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, catalogo_id, depende_de, valor_activador, orden) VALUES (2779, 'resultado_confirmacion_laboratorio', 'Si fue confirmación por Laboratorio, indicar resultado', 'SELECT', 0, 15, 16085, 'LABORATORIO', 3)");
    $ins->execute();
    $idResLab = $pdo->lastInsertId();
    echo "Creado campo 'Si fue confirmación por Laboratorio, indicar resultado' (ID $idResLab).\n";
}

// 4. Actualizar ID 16086 (Criterio de descarte): depende_de = 16084, valor_activador = 'DESCARTADO'
$pdo->exec("UPDATE campo_def SET depende_de = 16084, valor_activador = 'DESCARTADO' WHERE id = 16086");

// 5. Crear campo 'Otro criterio de descarte' (TEXTO, depende_de 16086, valor_activador OTROS)
$stmtExist2 = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2779 AND clave = 'otro_criterio_descarte'");
$stmtExist2->execute();
$idOtroDescarte = $stmtExist2->fetchColumn();

if (!$idOtroDescarte) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2779, 'otro_criterio_descarte', 'Otro criterio de descarte', 'TEXTO', 0, 16086, 'OTROS', 5)");
    $ins->execute();
    $idOtroDescarte = $pdo->lastInsertId();
    echo "Creado campo 'Otro criterio de descarte' (ID $idOtroDescarte).\n";
}

// 6. Crear campo 'Si es importado, indicar país de importación' (TEXTO, depende_de 16087, valor_activador IMPORTADO)
$stmtExist3 = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2779 AND clave = 'pais_importacion'");
$stmtExist3->execute();
$idPaisImportacion = $stmtExist3->fetchColumn();

if (!$idPaisImportacion) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2779, 'pais_importacion', 'Si es importado, indicar país de importación', 'TEXTO', 0, 16087, 'IMPORTADO', 7)");
    $ins->execute();
    $idPaisImportacion = $pdo->lastInsertId();
    echo "Creado campo 'Si es importado, indicar país de importación' (ID $idPaisImportacion).\n";
}

// 7. Crear campo 'Observaciones' (TEXTAREA) si no existe en la sección 2779
$stmtExist4 = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2779 AND clave = 'observaciones'");
$stmtExist4->execute();
$idObservaciones = $stmtExist4->fetchColumn();

if (!$idObservaciones) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden) VALUES (2779, 'observaciones', 'Observaciones', 'TEXTAREA', 0, 10)");
    $ins->execute();
    $idObservaciones = $pdo->lastInsertId();
    echo "Creado campo 'Observaciones' (ID $idObservaciones).\n";
}

// 8. Reordenar campos en sección 2779
$ordenDeseado = [
    16084, // Clasificación
    16085, // Criterio para confirmación
    (int) $idResLab, // Si fue confirmación por Laboratorio, indicar resultado
    16086, // Criterio de descarte
    (int) $idOtroDescarte, // Otro criterio de descarte
    16087, // Clasificación según fuente de infección
    (int) $idPaisImportacion, // Si es importado, indicar país de importación
    16088, // Fecha de clasificación final
    16089, // Clasificado por
    (int) $idObservaciones, // Observaciones
];

$i = 1;
foreach ($ordenDeseado as $cId) {
    $pdo->prepare("UPDATE campo_def SET orden = :ord WHERE id = :id")->execute(['ord' => $i, 'id' => $cId]);
    $i++;
}

echo "Reordenados los campos de Clasificación final (1 a " . ($i - 1) . ").\n";
