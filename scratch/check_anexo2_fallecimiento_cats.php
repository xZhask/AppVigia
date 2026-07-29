<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Obtener id de seccion 1 de O95 (Datos del fallecimiento (Anexo 1))
$stmtS1 = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND orden = 1");
$sec1Id = $stmtS1->fetchColumn();

// 1. Catalogo Categoria del EE.SS. (Cat ID 493)
$stmtCat493 = $pdo->query("SELECT id FROM catalogo WHERE id = 493 OR nombre LIKE '%Categoria%'");
$catId493 = $stmtCat493->fetchColumn();
if (!$catId493) {
    $pdo->query("INSERT INTO catalogo (id, nombre) VALUES (493, 'O95 - Categoría del EE.SS.')");
    $catId493 = 493;
}
$pdo->prepare("DELETE FROM catalogo_item WHERE catalogo_id = ?")->execute([$catId493]);
$insItem = $pdo->prepare("INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES (?, ?, ?, ?)");
$catsEE = ['I-1', 'I-2', 'I-3', 'I-4', 'II-1', 'II-2', 'II-E', 'III-1', 'III-E', 'III-2', 'Desconocido'];
foreach ($catsEE as $idx => $cVal) {
    $insItem->execute([$catId493, $cVal, $cVal, $idx + 1]);
}

// 2. Catalogo Responsable de la atencion (Cat ID 494)
$stmtCat494 = $pdo->query("SELECT id FROM catalogo WHERE id = 494 OR nombre LIKE '%Responsable%'");
$catId494 = $stmtCat494->fetchColumn();
if (!$catId494) {
    $pdo->query("INSERT INTO catalogo (id, nombre) VALUES (494, 'O95 - Responsable de la atención')");
    $catId494 = 494;
}
$pdo->prepare("DELETE FROM catalogo_item WHERE catalogo_id = ?")->execute([$catId494]);
$respList = ['Médico G-O', 'Médico intensivista', 'Médico residente', 'Médico general', 'Obstetra', 'Enfermera(o)', 'Interno', 'Técnico', 'Partera', 'Familiar', 'Otro', 'Desconocido'];
foreach ($respList as $idx => $rVal) {
    $insItem->execute([$catId494, $rVal, $rVal, $idx + 1]);
}

// 3. Crear o actualizar campos en campo_def Seccion 1
$camposAnexo2 = [
    ['clave' => 'o95_categoria_del_ee_ss', 'etiqueta' => 'Categoría del EE.SS.', 'tipo' => 'SELECT', 'catalogo_id' => $catId493, 'orden' => 27],
    ['clave' => 'o95_fecha_y_hora_de_ingreso_al_ee_ss', 'etiqueta' => 'Fecha y hora de ingreso al EE.SS.', 'tipo' => 'FECHA', 'catalogo_id' => null, 'orden' => 28],
    ['clave' => 'o95_responsable_de_la_atencion', 'etiqueta' => 'Responsable de la atención', 'tipo' => 'SELECT', 'catalogo_id' => $catId494, 'orden' => 29]
];

foreach ($camposAnexo2 as $ca) {
    $stmtC = $pdo->prepare("SELECT id FROM campo_def WHERE clave = ?");
    $stmtC->execute([$ca['clave']]);
    $cid = $stmtC->fetchColumn();
    if (!$cid) {
        $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, obligatorio, orden) VALUES (?, ?, ?, ?, ?, 0, ?)");
        $stmtIns->execute([$sec1Id, $ca['clave'], $ca['etiqueta'], $ca['tipo'], $ca['catalogo_id'], $ca['orden']]);
        echo "Creado campo '{$ca['clave']}' en DB.\n";
    } else {
        $pdo->prepare("UPDATE campo_def SET seccion_id = ?, etiqueta = ?, tipo = ?, catalogo_id = ?, orden = ? WHERE id = ?")->execute([$sec1Id, $ca['etiqueta'], $ca['tipo'], $ca['catalogo_id'], $ca['orden'], $cid]);
        echo "Actualizado campo '{$ca['clave']}' en DB.\n";
    }
}

// 4. Actualizar manifiesto_fichas.json para O95
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        if (($sec['orden'] ?? 0) == 1 || stripos($sec['nombre'] ?? '', 'Datos del fallecimiento') !== false) {
            // Asegurar que contenga los 3 campos de Anexo 2 al final
            $clavesExistentes = array_column($sec['campos'], 'clave');
            if (!in_array('o95_categoria_del_ee_ss', $clavesExistentes)) {
                $sec['campos'][] = ['clave' => 'o95_categoria_del_ee_ss', 'etiqueta' => 'Categoría del EE.SS.', 'tipo' => 'SELECT', 'opciones' => $catsEE];
            }
            if (!in_array('o95_fecha_y_hora_de_ingreso_al_ee_ss', $clavesExistentes)) {
                $sec['campos'][] = ['clave' => 'o95_fecha_y_hora_de_ingreso_al_ee_ss', 'etiqueta' => 'Fecha y hora de ingreso al EE.SS.', 'tipo' => 'FECHA'];
            }
            if (!in_array('o95_responsable_de_la_atencion', $clavesExistentes)) {
                $sec['campos'][] = ['clave' => 'o95_responsable_de_la_atencion', 'etiqueta' => 'Responsable de la atención', 'tipo' => 'SELECT', 'opciones' => $respList];
            }
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto sincronizado para Anexo 2 fallecimiento O95.\n";
