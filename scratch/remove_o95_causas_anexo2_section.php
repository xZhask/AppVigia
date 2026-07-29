<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$sec3Id = 2517; // Seccion 3: Causas de defuncion (Anexo 1)
$sec11Id = 2528; // Seccion 11: Causas de defuncion (Anexo 2)

// 1. Mover campos 14377 (causa asociada) y 14379 (clasificacion final) a la Seccion 3 (ID 2517)
$pdo->query("UPDATE campo_def SET seccion_id = $sec3Id, orden = 15 WHERE id IN (14377, 14379)");

// 2. Crear campo 16181 (CIE-10 Causa asociada) en la Seccion 3
$existsCieAsoc = $pdo->query("SELECT id FROM campo_def WHERE id = 16181 OR clave = 'o95_causa_asociada_cie10'")->fetchColumn();
if (!$existsCieAsoc) {
    $pdo->query("INSERT INTO campo_def (id, seccion_id, clave, etiqueta, tipo, catalogo_id, sensible, orden) VALUES (16181, $sec3Id, 'o95_causa_asociada_cie10', 'CIE-10 Causa asociada', 'TEXTO', NULL, 0, 14)");
    echo "Campo 16181 (o95_causa_asociada_cie10) creado en Seccion 3.\n";
}

// 3. Eliminar campos duplicados (14374, 14375, 14376, 14378) de la Seccion 11
$pdo->query("DELETE FROM campo_def WHERE id IN (14374, 14375, 14376, 14378)");

// 4. Eliminar Seccion 11 (ID 2528)
$pdo->query("DELETE FROM seccion_def WHERE id = $sec11Id");
echo "Seccion 2528 (Causas de defuncion Anexo 2) eliminada de la BD.\n";

// 5. Ajustar orden de Seccion 12 (Las cuatro demoras - ID 2529) a orden 11
$pdo->query("UPDATE seccion_def SET orden = 11 WHERE id = 2529");
echo "Orden de Las cuatro demoras ajustado a 11 en BD.\n";

// 6. Reconstruir manifiesto_fichas.json para O95
$enfermedadId = $pdo->query("SELECT id FROM enfermedad WHERE cie10 = 'O95'")->fetchColumn();
$dbSecs = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = $enfermedadId ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);

$manifestSecs = [];
foreach ($dbSecs as $s) {
    $dbCampos = $pdo->query("SELECT id, clave, etiqueta, tipo, catalogo_id, depende_de, valor_activador FROM campo_def WHERE seccion_id = {$s['id']} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $manifestCampos = [];
    foreach ($dbCampos as $c) {
        $item = [
            'clave' => $c['clave'],
            'etiqueta' => $c['etiqueta'],
            'tipo' => $c['tipo'],
            'requerido' => false,
            'sensible' => false,
        ];
        if ($c['catalogo_id']) {
            $opcs = $pdo->query("SELECT etiqueta FROM catalogo_item WHERE catalogo_id = {$c['catalogo_id']} ORDER BY orden")->fetchAll(PDO::FETCH_COLUMN);
            $item['opciones'] = array_values($opcs);
        }
        if ($c['depende_de']) {
            $depEtiqueta = $pdo->query("SELECT etiqueta FROM campo_def WHERE id = {$c['depende_de']}")->fetchColumn();
            $item['depende_de'] = $depEtiqueta;
            $item['valor_activador'] = $c['valor_activador'];
        }
        $manifestCampos[] = $item;
    }
    $manifestSecs[] = [
        'nombre' => $s['nombre'],
        'orden' => (int)$s['orden'],
        'campos' => $manifestCampos,
    ];
}

$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);
$manifest['fichas']['O95']['secciones'] = $manifestSecs;

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto O95 actualizado con 11 secciones consolidadas.\n";
