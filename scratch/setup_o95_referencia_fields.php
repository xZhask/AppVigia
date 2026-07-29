<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// 1. Obtener id de seccion 2 de O95 (Referencia (Anexo 1))
$stmtS2 = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND orden = 2");
$sec2Id = $stmtS2->fetchColumn();

// 2. Catalogo SI/NO (Cat ID 485)
$stmtCat = $pdo->query("SELECT id FROM catalogo WHERE id = 485 OR nombre LIKE '%SI%NO%'");
$catSiNoId = $stmtCat->fetchColumn();

// Actualizar o95_referida a SELECT / SI_NO
$pdo->prepare("UPDATE campo_def SET tipo = 'SELECT', catalogo_id = ?, orden = 1 WHERE clave = 'o95_referida'")->execute([$catSiNoId]);
$pdo->prepare("UPDATE campo_def SET orden = 2 WHERE clave = 'o95_ee_ss_de_origen_de_la_referencia'")->execute();

// 3. Crear campos de ubigeo para referencia si no existen
$camposUbigeo = [
    ['clave' => 'o95_referencia_dep_id', 'etiqueta' => 'Departamento (Origen de la referencia)', 'tipo' => 'TEXTO', 'orden' => 3],
    ['clave' => 'o95_referencia_prov_id', 'etiqueta' => 'Provincia (Origen de la referencia)', 'tipo' => 'TEXTO', 'orden' => 4],
    ['clave' => 'o95_referencia_dist_id', 'etiqueta' => 'Distrito (Origen de la referencia)', 'tipo' => 'TEXTO', 'orden' => 5]
];

foreach ($camposUbigeo as $cu) {
    $stmtCheck = $pdo->prepare("SELECT id FROM campo_def WHERE clave = ?");
    $stmtCheck->execute([$cu['clave']]);
    $cid = $stmtCheck->fetchColumn();
    if (!$cid) {
        $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden) VALUES (?, ?, ?, ?, 0, ?)");
        $stmtIns->execute([$sec2Id, $cu['clave'], $cu['etiqueta'], $cu['tipo'], $cu['orden']]);
        echo "Creado campo {$cu['clave']} en DB.\n";
    } else {
        $pdo->prepare("UPDATE campo_def SET seccion_id = ?, etiqueta = ?, tipo = ?, orden = ? WHERE id = ?")->execute([$sec2Id, $cu['etiqueta'], $cu['tipo'], $cu['orden'], $cid]);
        echo "Actualizado campo {$cu['clave']} en DB.\n";
    }
}

// 4. Sincronizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        if (($sec['orden'] ?? 0) == 2 || stripos($sec['nombre'] ?? '', 'Referencia') !== false) {
            $sec['campos'] = [
                ['clave' => 'o95_referida', 'etiqueta' => '¿Referida?', 'tipo' => 'SELECT', 'opciones' => ['SI', 'NO']],
                ['clave' => 'o95_ee_ss_de_origen_de_la_referencia', 'etiqueta' => 'EE.SS. de origen de la referencia', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_referencia_dep_id', 'etiqueta' => 'Departamento (Origen de la referencia)', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_referencia_prov_id', 'etiqueta' => 'Provincia (Origen de la referencia)', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_referencia_dist_id', 'etiqueta' => 'Distrito (Origen de la referencia)', 'tipo' => 'TEXTO']
            ];
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto sincronizado para Sección 2 Referencia O95.\n";
