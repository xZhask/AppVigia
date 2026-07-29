<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$secId = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND (nombre LIKE '%Complicaciones%' OR orden = 6)")->fetchColumn();

$dbCampos = $pdo->query("SELECT id, clave, etiqueta, tipo, depende_de, valor_activador FROM campo_def WHERE seccion_id = $secId ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);

echo "DB Campos:\n";
foreach ($dbCampos as $c) {
    $depEtiqueta = '';
    if ($c['depende_de']) {
        $depEtiqueta = $pdo->query("SELECT etiqueta FROM campo_def WHERE id = {$c['depende_de']}")->fetchColumn();
    }
    echo " - [{$c['id']}] {$c['clave']} | {$c['etiqueta']} | {$c['tipo']} | depende: {$c['depende_de']} ($depEtiqueta) = '{$c['valor_activador']}'\n";
}

$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

echo "\nManifiesto Campos:\n";
foreach ($manifest['fichas']['O95']['secciones'] as $sec) {
    if ($sec['orden'] == 6 || strpos($sec['nombre'], 'Complicaciones') !== false) {
        foreach ($sec['campos'] as $c) {
            $dep = $c['depende_de'] ?? '';
            $act = $c['valor_activador'] ?? '';
            echo " - {$c['clave']} | {$c['etiqueta']} | {$c['tipo']} | depende: $dep = '$act'\n";
        }
    }
}
