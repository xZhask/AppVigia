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

$idHosp = $mapaEtiquetas['Hospitalizado']['id'] ?? null;
$idFall = $mapaEtiquetas['Fallecido']['id'] ?? null;

echo "Hospitalizado ID: $idHosp\n";
echo "Fallecido ID: $idFall\n";

if ($idHosp) {
    $camposHosp = ['Fecha de hospitalización', 'Establecimiento de hospitalización', 'N.° de historia clínica'];
    foreach ($camposHosp as $et) {
        if (isset($mapaEtiquetas[$et])) {
            $stmt = $pdo->prepare("UPDATE campo_def SET depende_de = ?, valor_activador = '1' WHERE id = ?");
            $stmt->execute([$idHosp, $mapaEtiquetas[$et]['id']]);
            echo "Actualizado $et depende de Hospitalizado ($idHosp)\n";
        }
    }
}

if ($idFall) {
    $camposFall = ['Fecha de defunción', 'Causa básica de defunción'];
    foreach ($camposFall as $et) {
        if (isset($mapaEtiquetas[$et])) {
            $stmt = $pdo->prepare("UPDATE campo_def SET depende_de = ?, valor_activador = '1' WHERE id = ?");
            $stmt->execute([$idFall, $mapaEtiquetas[$et]['id']]);
            echo "Actualizado $et depende de Fallecido ($idFall)\n";
        }
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
                    if (in_array(trim($c['etiqueta']), ['Fecha de hospitalización', 'Establecimiento de hospitalización', 'N.° de historia clínica'], true)) {
                        $c['depende_de'] = 'Hospitalizado';
                        $c['valor_activador'] = '1';
                    }
                    if (in_array(trim($c['etiqueta']), ['Fecha de defunción', 'Causa básica de defunción'], true)) {
                        $c['depende_de'] = 'Fallecido';
                        $c['valor_activador'] = '1';
                    }
                }
            }
        }
    }
}
file_put_contents($manifiestoPath, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto actualizado con éxito.\n";
