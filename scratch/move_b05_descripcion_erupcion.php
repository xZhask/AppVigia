<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$pdo = Database::conexion();

$enf = Enfermedad::buscarPorCie10('B05');
$secciones = SeccionDef::porEnfermedad((int) $enf['id']);

$secCron = null;
foreach ($secciones as $sec) {
    if (stripos($sec['nombre'], 'cronologia') !== false || stripos($sec['nombre'], 'cronología') !== false) {
        $secCron = $sec;
        break;
    }
}

if (!$secCron) {
    die("No se encontró la sección Cronología para B05\n");
}

echo "Sección Cronología ID: {$secCron['id']}\n";

// Mover campo ID 16042 (Descripción de la erupción cutánea) a la sección de Cronología con orden 2
$stmt = $pdo->prepare("UPDATE campo_def SET seccion_id = :sec_id, orden = 2 WHERE id = 16042");
$stmt->execute(['sec_id' => $secCron['id']]);

echo "Campo ID 16042 movido a la sección ID {$secCron['id']} con orden 2 en BD.\n";

// Reordenar campos en la sección Cuadro clínico (ID 2773) para cerrar el hueco de orden 10
$stmtCuadro = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2773 ORDER BY orden ASC");
$stmtCuadro->execute();
$camposCuadro = $stmtCuadro->fetchAll();

$idx = 1;
foreach ($camposCuadro as $c) {
    $u = $pdo->prepare("UPDATE campo_def SET orden = :orden WHERE id = :id");
    $u->execute(['orden' => $idx, 'id' => $c['id']]);
    $idx++;
}
echo "Reordenados los campos de Cuadro clínico (1 a " . ($idx - 1) . ").\n";

// Ahora actualizar manifiesto_fichas.json
$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($jsonPath), true);

$secCuadroJson = &$manifiesto['B05']['secciones'][2]; // Cuadro clínico
$secCronJson = &$manifiesto['B05']['secciones'][3];   // Cronología

$campoDesc = null;
$nuevosCuadro = [];

foreach ($secCuadroJson['campos'] as $c) {
    if ($c['etiqueta'] === 'Descripción de la erupción cutánea') {
        $campoDesc = $c;
    } else {
        $nuevosCuadro[] = $c;
    }
}

if ($campoDesc) {
    $secCuadroJson['campos'] = $nuevosCuadro;
    $secCronJson['campos'][] = $campoDesc;
    file_put_contents($jsonPath, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "manifiesto_fichas.json actualizado exitosamente.\n";
} else {
    echo "Campo no encontrado en manifiesto (ya movido previamente o etiqueta diferente).\n";
}
