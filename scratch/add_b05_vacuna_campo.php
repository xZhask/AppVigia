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

$seccionId = (int) $secCuadro['id'];

// Check if field already exists in Cuadro clínico
$stmt = $pdo->prepare("SELECT * FROM campo_def WHERE seccion_id = ? AND etiqueta LIKE '%vacuna%'");
$stmt->execute([$seccionId]);
$existente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existente) {
    echo "El campo ya existe con ID {$existente['id']}\n";
    $campoId = $existente['id'];
} else {
    // Get max orden
    $stmtMax = $pdo->prepare("SELECT MAX(orden) FROM campo_def WHERE seccion_id = ?");
    $stmtMax->execute([$seccionId]);
    $maxOrden = (int) $stmtMax->fetchColumn();

    $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, obligatorio, orden) VALUES (?, 'antecedente_vacunacion_sarampion', ?, 'SELECT', 145, 0, ?)");
    $stmtIns->execute([$seccionId, 'Antecedente de vacunación contra sarampión', $maxOrden + 1]);
    $campoId = $pdo->lastInsertId();
    echo "Insertado nuevo campo 'Antecedente de vacunación contra sarampión' con ID $campoId\n";
}

// Update manifiesto_fichas.json
$manifiestoPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($manifiestoPath), true);

foreach ($manifiesto['fichas'] as &$f) {
    if (stripos($f['enfermedad'], 'Sarampión') !== false) {
        foreach ($f['secciones'] as &$sec) {
            if (trim($sec['nombre']) === 'Cuadro clínico') {
                $yaExisteEnManifiesto = false;
                foreach ($sec['campos'] as $c) {
                    if (stripos($c['etiqueta'], 'vacuna') !== false) {
                        $yaExisteEnManifiesto = true;
                        break;
                    }
                }
                if (!$yaExisteEnManifiesto) {
                    $sec['campos'][] = [
                        'etiqueta' => 'Antecedente de vacunación contra sarampión',
                        'tipo' => 'SELECT',
                        'catalogo_id' => 145,
                        'obligatorio' => 0
                    ];
                    echo "Agregado campo a manifiesto en Cuadro clínico.\n";
                }
            }
        }
    }
}
file_put_contents($manifiestoPath, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto actualizado con éxito.\n";
