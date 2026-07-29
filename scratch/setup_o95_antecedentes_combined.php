<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// 1. Obtener ID de la enfermedad O95
$stmtE = $pdo->query("SELECT id FROM enfermedad WHERE cie10 = 'O95'");
$enfId = $stmtE->fetchColumn();

// 2. Obtener IDs de secciones actuales
$stmtS = $pdo->prepare("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = ? ORDER BY orden");
$stmtS->execute([$enfId]);
$secciones = $stmtS->fetchAll(PDO::FETCH_ASSOC);

$secPatId = null;
$secGinecId = null;
foreach ($secciones as $s) {
    if (stripos($s['nombre'], 'Antecedentes patológicos') !== false) {
        $secPatId = $s['id'];
    }
    if (stripos($s['nombre'], 'Antecedentes gineco') !== false) {
        $secGinecId = $s['id'];
    }
}

echo "Sec Patologicos ID: $secPatId, Sec Gineco ID: $secGinecId\n";

if ($secPatId && $secGinecId) {
    // Mover campos de Gineco-obstetricos a la seccion de Patologicos
    $pdo->prepare("UPDATE campo_def SET seccion_id = ? WHERE seccion_id = ?")->execute([$secPatId, $secGinecId]);
    // Eliminar la seccion Gineco-obstetricos duplicada
    $pdo->prepare("DELETE FROM seccion_def WHERE id = ?")->execute([$secGinecId]);
    echo "Campos movidos y seccion $secGinecId eliminada.\n";
}

// Renombrar seccion consolidada
$pdo->prepare("UPDATE seccion_def SET nombre = 'Antecedentes patológicos y obstétricos' WHERE id = ?")->execute([$secPatId]);

// 3. Crear nuevos campos para OTRA en antecedentes, periodo intergenesico anios/meses, y otro metodo anticonceptivo
$nuevosCampos = [
    ['clave' => 'o95_antecedentes_patologicos_otra', 'etiqueta' => 'Especificar otro antecedente patológico', 'tipo' => 'TEXTO', 'orden' => 2],
    ['clave' => 'o95_periodo_intergenesico_anios', 'etiqueta' => 'Período intergenésico (Años)', 'tipo' => 'NUMERO', 'orden' => 11],
    ['clave' => 'o95_periodo_intergenesico_meses', 'etiqueta' => 'Período intergenésico (Meses)', 'tipo' => 'NUMERO', 'orden' => 12],
    ['clave' => 'o95_metodo_anticonceptivo_otro', 'etiqueta' => 'Especificar otro método anticonceptivo', 'tipo' => 'TEXTO', 'orden' => 14]
];

foreach ($nuevosCampos as $nc) {
    $stmtCheck = $pdo->prepare("SELECT id FROM campo_def WHERE clave = ?");
    $stmtCheck->execute([$nc['clave']]);
    $cid = $stmtCheck->fetchColumn();
    if (!$cid) {
        $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden) VALUES (?, ?, ?, ?, 0, ?)");
        $stmtIns->execute([$secPatId, $nc['clave'], $nc['etiqueta'], $nc['tipo'], $nc['orden']]);
        echo "Creado campo {$nc['clave']} en DB.\n";
    } else {
        $pdo->prepare("UPDATE campo_def SET seccion_id = ?, etiqueta = ?, tipo = ?, orden = ? WHERE id = ?")->execute([$secPatId, $nc['etiqueta'], $nc['tipo'], $nc['orden'], $cid]);
        echo "Actualizado campo {$nc['clave']} en DB.\n";
    }
}

// 4. Reordenar las secciones para que 'Antecedentes patológicos y obstétricos' sea orden 2 (antes de Referencia)
// Mapeo deseado de secciones:
// 1. Datos del fallecimiento (Anexo 1) -> orden 1
// 2. Antecedentes patológicos y obstétricos -> orden 2
// 3. Referencia (Anexo 1) -> orden 3
// 4. Causas de defunción (Anexo 1) -> orden 4
// 5. Atención prenatal -> orden 5
// 6. Complicaciones -> orden 6
// 7. Referencia y hospitalizaciones -> orden 7
// 8. Parto o aborto -> orden 8
// 9. Entorno social y comunitario -> orden 9
// 10. Datos comunitarios -> orden 10
// 11. Causas de defunción (Anexo 2) -> orden 11
// 12. Las cuatro demoras -> orden 12

$secActuales = $pdo->query("SELECT id, nombre FROM seccion_def WHERE enfermedad_id = $enfId")->fetchAll(PDO::FETCH_ASSOC);

foreach ($secActuales as $sa) {
    $n = trim($sa['nombre']);
    $nOrden = 12;
    if (stripos($n, 'Datos del fallecimiento') !== false) $nOrden = 1;
    elseif (stripos($n, 'Antecedentes patológicos') !== false) $nOrden = 2;
    elseif (stripos($n, 'Referencia (Anexo 1)') !== false) $nOrden = 3;
    elseif (stripos($n, 'Causas de defunción (Anexo 1)') !== false) $nOrden = 4;
    elseif (stripos($n, 'Atención prenatal') !== false) $nOrden = 5;
    elseif (stripos($n, 'Complicaciones') !== false) $nOrden = 6;
    elseif (stripos($n, 'Referencia y hospitalizaciones') !== false) $nOrden = 7;
    elseif (stripos($n, 'Parto o aborto') !== false) $nOrden = 8;
    elseif (stripos($n, 'Entorno social') !== false) $nOrden = 9;
    elseif (stripos($n, 'Datos comunitarios') !== false) $nOrden = 10;
    elseif (stripos($n, 'Causas de defunción (Anexo 2)') !== false) $nOrden = 11;
    elseif (stripos($n, 'cuatro demoras') !== false) $nOrden = 12;

    $pdo->prepare("UPDATE seccion_def SET orden = ? WHERE id = ?")->execute([$nOrden, $sa['id']]);
    echo "Seccion ID {$sa['id']} ($n) -> orden $nOrden\n";
}

// 5. Reconstruir manifiesto_fichas.json para O95
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    $seccionesDB = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = $enfId ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
    $nuevasSeccionesManifest = [];
    foreach ($seccionesDB as $sDB) {
        $camposDB = $pdo->query("SELECT id, clave, etiqueta, tipo, catalogo_id FROM campo_def WHERE seccion_id = {$sDB['id']} ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
        $camposArr = [];
        foreach ($camposDB as $cDB) {
            $cArr = [
                'clave' => $cDB['clave'],
                'etiqueta' => $cDB['etiqueta'],
                'tipo' => $cDB['tipo']
            ];
            if ($cDB['catalogo_id']) {
                $opts = $pdo->query("SELECT etiqueta FROM catalogo_item WHERE catalogo_id = {$cDB['catalogo_id']} ORDER BY orden")->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($opts)) {
                    $cArr['opciones'] = array_values($opts);
                }
            }
            $camposArr[] = $cArr;
        }
        $nuevasSeccionesManifest[] = [
            'nombre' => $sDB['nombre'],
            'orden' => (int)$sDB['orden'],
            'campos' => $camposArr
        ];
    }
    $manifest['fichas']['O95']['secciones'] = $nuevasSeccionesManifest;
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto sincronizado con 12 secciones para O95.\n";
