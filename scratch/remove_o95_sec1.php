<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Obtener id de enfermedad O95
$stmtEnf = $pdo->query("SELECT id FROM enfermedad WHERE cie10 = 'O95'");
$enfId = $stmtEnf->fetchColumn();

// Buscar seccion 1 de O95 (Anexo 1 — Notificación inmediata)
$stmtSec1 = $pdo->prepare("SELECT id FROM seccion_def WHERE enfermedad_id = ? AND (orden = 1 OR nombre LIKE '%Notificación inmediata%')");
$stmtSec1->execute([$enfId]);
$sec1Id = $stmtSec1->fetchColumn();

if ($sec1Id) {
    echo "Eliminando campos y seccion ID {$sec1Id} de O95 en DB...\n";
    $pdo->prepare("DELETE FROM campo_def WHERE seccion_id = ?")->execute([$sec1Id]);
    $pdo->prepare("DELETE FROM seccion_def WHERE id = ?")->execute([$sec1Id]);
}

// Reordenar secciones restantes de O95 en DB
$stmtRest = $pdo->prepare("SELECT id FROM seccion_def WHERE enfermedad_id = ? ORDER BY orden, id");
$stmtRest->execute([$enfId]);
$secciones = $stmtRest->fetchAll(PDO::FETCH_COLUMN);

$u = $pdo->prepare("UPDATE seccion_def SET orden = ? WHERE id = ?");
$i = 1;
foreach ($secciones as $sid) {
    $u->execute([$i++, $sid]);
}

echo "Secciones en DB de O95 reordenadas (Total: " . count($secciones) . ").\n";

// Actualizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    $nuevasSecciones = [];
    $ordenNum = 1;
    foreach ($manifest['fichas']['O95']['secciones'] as $sec) {
        $nombreLower = strtolower($sec['nombre'] ?? '');
        if (stripos($nombreLower, 'notificación inmediata') !== false || stripos($nombreLower, 'notificacion inmediata') !== false) {
            echo "Omitiendo seccion '{$sec['nombre']}' en manifiesto.\n";
            continue;
        }
        $sec['orden'] = $ordenNum++;
        $nuevasSecciones[] = $sec;
    }
    $manifest['fichas']['O95']['secciones'] = $nuevasSecciones;
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto actualizado para O95 (Seccion Notificacion Inmediata removida).\n";
