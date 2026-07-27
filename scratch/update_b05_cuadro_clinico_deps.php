<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;
use App\Models\CampoDef;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$pdo = Database::conexion();

$enf = Enfermedad::buscarPorCie10('B05');
$secciones = SeccionDef::porEnfermedad((int) $enf['id']);
$secCuadro = null;
foreach ($secciones as $s) {
    if (trim($s['nombre']) === 'Cuadro clínico') {
        $secCuadro = $s;
        break;
    }
}

if (!$secCuadro) {
    echo "No se encontró sección Cuadro clínico para B05\n";
    exit(1);
}

$campos = CampoDef::porSeccion((int) $secCuadro['id']);
$mapaEtiquetas = [];
foreach ($campos as $c) {
    $mapaEtiquetas[trim($c['etiqueta'])] = $c;
}

$idFiebreCuant = $mapaEtiquetas['¿Fiebre cuantificada?']['id'] ?? null;
$idErupcion = $mapaEtiquetas['¿Erupción cutánea?']['id'] ?? null;

echo "Fiebre cuantificada ID: $idFiebreCuant\n";
echo "Erupción cutánea ID: $idErupcion\n";

if ($idFiebreCuant && isset($mapaEtiquetas['Temperatura (°C)'])) {
    $idTemp = $mapaEtiquetas['Temperatura (°C)']['id'];
    $stmt = $pdo->prepare("UPDATE campo_def SET depende_de = ?, valor_activador = '1' WHERE id = ?");
    $stmt->execute([$idFiebreCuant, $idTemp]);
    echo "Actualizado Temperatura (°C) depende de ¿Fiebre cuantificada?\n";
}

if ($idErupcion) {
    if (isset($mapaEtiquetas['Fecha de inicio de erupción'])) {
        $idFechaEru = $mapaEtiquetas['Fecha de inicio de erupción']['id'];
        $stmt = $pdo->prepare("UPDATE campo_def SET depende_de = ?, valor_activador = '1' WHERE id = ?");
        $stmt->execute([$idErupcion, $idFechaEru]);
        echo "Actualizado Fecha de inicio de erupción depende de ¿Erupción cutánea?\n";
    }
    if (isset($mapaEtiquetas['N.° de días de duración de la erupción'])) {
        $idDiasEru = $mapaEtiquetas['N.° de días de duración de la erupción']['id'];
        $stmt = $pdo->prepare("UPDATE campo_def SET depende_de = ?, valor_activador = '1' WHERE id = ?");
        $stmt->execute([$idErupcion, $idDiasEru]);
        echo "Actualizado N.° de días de duración de la erupción depende de ¿Erupción cutánea?\n";
    }
}

// Update manifiesto_fichas.json
$manifiestoPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($manifiestoPath), true);
foreach ($manifiesto['fichas'] as &$f) {
    if (stripos($f['enfermedad'], 'Sarampión') !== false) {
        foreach ($f['secciones'] as &$sec) {
            if (trim($sec['nombre']) === 'Cuadro clínico') {
                foreach ($sec['campos'] as &$c) {
                    if (trim($c['etiqueta']) === 'Temperatura (°C)') {
                        $c['depende_de'] = '¿Fiebre cuantificada?';
                        $c['valor_activador'] = '1';
                    }
                    if (trim($c['etiqueta']) === 'Fecha de inicio de erupción' || trim($c['etiqueta']) === 'N.° de días de duración de la erupción') {
                        $c['depende_de'] = '¿Erupción cutánea?';
                        $c['valor_activador'] = '1';
                    }
                }
            }
        }
    }
}
file_put_contents($manifiestoPath, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto actualizado con éxito.\n";
