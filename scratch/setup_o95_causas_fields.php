<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// 1. Obtener id de seccion 3 de O95 (Causas de defunción (Anexo 1))
$stmtS3 = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND orden = 3");
$sec3Id = $stmtS3->fetchColumn();

// 2. Crear nuevos campos para CIE-10 y Especificar Otra causa generica
$nuevosCampos = [
    ['clave' => 'o95_causa_final_cie10', 'etiqueta' => 'CIE-10 Causa final', 'tipo' => 'TEXTO', 'orden' => 2],
    ['clave' => 'o95_causa_intermedia_cie10', 'etiqueta' => 'CIE-10 Causa intermedia', 'tipo' => 'TEXTO', 'orden' => 4],
    ['clave' => 'o95_causa_basica_cie10', 'etiqueta' => 'CIE-10 Causa básica', 'tipo' => 'TEXTO', 'orden' => 6],
    ['clave' => 'o95_causa_generica_otra', 'etiqueta' => 'Especificar otra causa genérica', 'tipo' => 'TEXTO', 'orden' => 8]
];

foreach ($nuevosCampos as $nc) {
    $stmtCheck = $pdo->prepare("SELECT id FROM campo_def WHERE clave = ?");
    $stmtCheck->execute([$nc['clave']]);
    $cid = $stmtCheck->fetchColumn();
    if (!$cid) {
        $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden) VALUES (?, ?, ?, ?, 0, ?)");
        $stmtIns->execute([$sec3Id, $nc['clave'], $nc['etiqueta'], $nc['tipo'], $nc['orden']]);
        echo "Creado campo {$nc['clave']} en DB.\n";
    } else {
        $pdo->prepare("UPDATE campo_def SET seccion_id = ?, etiqueta = ?, tipo = ?, orden = ? WHERE id = ?")->execute([$sec3Id, $nc['etiqueta'], $nc['tipo'], $nc['orden'], $cid]);
        echo "Actualizado campo {$nc['clave']} en DB.\n";
    }
}

// Actualizar orden de los campos existentes
$pdo->prepare("UPDATE campo_def SET orden = 1 WHERE clave = 'o95_causa_final_probable'")->execute();
$pdo->prepare("UPDATE campo_def SET orden = 3 WHERE clave = 'o95_causa_intermedia_probable'")->execute();
$pdo->prepare("UPDATE campo_def SET orden = 5 WHERE clave = 'o95_causa_basica_probable'")->execute();
$pdo->prepare("UPDATE campo_def SET orden = 7 WHERE clave = 'o95_causa_generica'")->execute();
$pdo->prepare("UPDATE campo_def SET orden = 9 WHERE clave = 'o95_clasificacion_inicial'")->execute();

// 3. Sincronizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        if (($sec['orden'] ?? 0) == 3 || stripos($sec['nombre'] ?? '', 'Causas de defunción (Anexo 1)') !== false) {
            $sec['campos'] = [
                ['clave' => 'o95_causa_final_probable', 'etiqueta' => 'Causa final probable', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_causa_final_cie10', 'etiqueta' => 'CIE-10 Causa final', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_causa_intermedia_probable', 'etiqueta' => 'Causa intermedia probable', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_causa_intermedia_cie10', 'etiqueta' => 'CIE-10 Causa intermedia', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_causa_basica_probable', 'etiqueta' => 'Causa básica probable', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_causa_basica_cie10', 'etiqueta' => 'CIE-10 Causa básica', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_causa_generica', 'etiqueta' => 'Causa genérica', 'tipo' => 'SELECT', 'opciones' => ['Hemorragia', 'Hipertensión gestacional', 'Infección/Sepsis', 'Otra causa']],
                ['clave' => 'o95_causa_generica_otra', 'etiqueta' => 'Especificar otra causa genérica', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_clasificacion_inicial', 'etiqueta' => 'Clasificación inicial', 'tipo' => 'SELECT', 'opciones' => ['Directa', 'Indirecta', 'Incidental', 'Por determinar']]
            ];
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto sincronizado para Sección 3 Causas de defunción O95.\n";
