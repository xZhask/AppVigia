<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();
$stmt = $pdo->query("
    SELECT s.id as seccion_id, s.orden, s.nombre as seccion_nombre, c.id as campo_id, c.clave, c.etiqueta
    FROM seccion_def s
    LEFT JOIN campo_def c ON c.seccion_id = s.id
    WHERE s.enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95')
    ORDER BY s.orden, c.orden
");
$rows = $stmt->fetchAll();

$secciones = [];
foreach ($rows as $r) {
    $sId = $r['seccion_id'];
    if (!isset($secciones[$sId])) {
        $secciones[$sId] = [
            'id' => $sId,
            'orden' => $r['orden'],
            'nombre' => $r['seccion_nombre'],
            'campos' => []
        ];
    }
    if ($r['campo_id']) {
        $secciones[$sId]['campos'][] = [
            'id' => $r['campo_id'],
            'clave' => $r['clave'],
            'etiqueta' => $r['etiqueta']
        ];
    }
}

foreach ($secciones as $s) {
    echo "[Sección {$s['orden']} (ID {$s['id']})] {$s['nombre']} (" . count($s['campos']) . " campos)\n";
    foreach ($s['campos'] as $c) {
        echo "   - [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']}\n";
    }
}
