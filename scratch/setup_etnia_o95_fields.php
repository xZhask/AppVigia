<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Obtener id seccion 5 de O95
$stmtS = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND orden = 5");
$seccionId = $stmtS->fetchColumn();

// 1. o95_grupo_etnico (ID 16110)
$stmt1 = $pdo->query("SELECT * FROM campo_def WHERE clave = 'o95_grupo_etnico'");
$campo1 = $stmt1->fetch();

if (!$campo1) {
    $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden) VALUES (?, 'o95_grupo_etnico', 'Grupo étnico', 'SELECT', 0, 10)");
    $stmtIns->execute([$seccionId]);
    $id1 = $pdo->lastInsertId();
} else {
    $id1 = $campo1['id'];
    $pdo->prepare("UPDATE campo_def SET tipo = 'SELECT', etiqueta = 'Grupo étnico' WHERE id = ?")->execute([$id1]);
}

// 2. o95_etnia_pueblo_etnico
$stmt2 = $pdo->query("SELECT * FROM campo_def WHERE clave = 'o95_etnia_pueblo_etnico'");
$campo2 = $stmt2->fetch();

if (!$campo2) {
    $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden) VALUES (?, 'o95_etnia_pueblo_etnico', 'Etnia / Pueblo étnico', 'SELECT', 0, 11)");
    $stmtIns->execute([$seccionId]);
    $id2 = $pdo->lastInsertId();
} else {
    $id2 = $campo2['id'];
    $pdo->prepare("UPDATE campo_def SET tipo = 'SELECT', etiqueta = 'Etnia / Pueblo étnico' WHERE id = ?")->execute([$id2]);
}

// Catalogo Grupo Etnico
$stmtCat1 = $pdo->query("SELECT id FROM catalogo WHERE nombre = 'O95 - Grupo étnico'");
$catId1 = $stmtCat1->fetchColumn();
if (!$catId1) {
    $pdo->query("INSERT INTO catalogo (nombre) VALUES ('O95 - Grupo étnico')");
    $catId1 = $pdo->lastInsertId();
}
$pdo->prepare("UPDATE campo_def SET catalogo_id = ? WHERE id = ?")->execute([$catId1, $id1]);

// Items grupo etnico
$itemsGrupo = ['Mestizo', 'Andino', 'Indígena amazónico', 'Afroperuano', 'Asiático descendiente', 'Otro'];

$pdo->prepare("DELETE FROM catalogo_item WHERE catalogo_id = ?")->execute([$catId1]);
$insItem = $pdo->prepare("INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES (?, ?, ?, ?)");
$o = 1;
foreach ($itemsGrupo as $it) {
    $insItem->execute([$catId1, $it, $it, $o++]);
}

// Catalogo Etnia / Pueblo etnico
$stmtCat2 = $pdo->query("SELECT id FROM catalogo WHERE nombre = 'O95 - Etnia / Pueblo étnico'");
$catId2 = $stmtCat2->fetchColumn();
if (!$catId2) {
    $pdo->query("INSERT INTO catalogo (nombre) VALUES ('O95 - Etnia / Pueblo étnico')");
    $catId2 = $pdo->lastInsertId();
}
$pdo->prepare("UPDATE campo_def SET catalogo_id = ? WHERE id = ?")->execute([$catId2, $id2]);

$itemsPueblo = [
    'Quechua', 'Aymara', 'Jaqaru', 'Uro',
    'Asháninka', 'Awajún', 'Shipibo-Konibo', 'Yánesha', 'Kukama Kukamiria', 'Achuar', 'Bora', 'Matsés', 'Ese Eja', 'Harakbut',
    'Afroperuano',
    'No aplica',
    'Chino-peruano', 'Japonés-peruano',
    'Otro'
];

$pdo->prepare("DELETE FROM catalogo_item WHERE catalogo_id = ?")->execute([$catId2]);
$o = 1;
foreach ($itemsPueblo as $it) {
    $insItem->execute([$catId2, $it, $it, $o++]);
}

// Actualizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        if (($sec['orden'] ?? 0) == 5 || stripos($sec['nombre'] ?? '', 'Datos básicos adicionales') !== false) {
            $nuevosCampos = [];
            foreach ($sec['campos'] as $c) {
                if (($c['clave'] ?? '') !== 'o95_grupo_etnico' && ($c['clave'] ?? '') !== 'o95_etnia_pueblo_etnico') {
                    $nuevosCampos[] = $c;
                }
            }
            $nuevosCampos[] = [
                'clave' => 'o95_grupo_etnico',
                'etiqueta' => 'Grupo étnico',
                'tipo' => 'SELECT',
                'obligatorio' => 0,
                'opciones' => $itemsGrupo
            ];
            $nuevosCampos[] = [
                'clave' => 'o95_etnia_pueblo_etnico',
                'etiqueta' => 'Etnia / Pueblo étnico',
                'tipo' => 'SELECT',
                'obligatorio' => 0,
                'opciones' => $itemsPueblo
            ];
            $sec['campos'] = $nuevosCampos;
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto guardado correctamente.\n";
