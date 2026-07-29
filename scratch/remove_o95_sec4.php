<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// 1. Obtener ID de la enfermedad O95
$stmtE = $pdo->query("SELECT id FROM enfermedad WHERE cie10 = 'O95'");
$enfId = $stmtE->fetchColumn();

// 2. Obtener id de Seccion 1 (Datos del fallecimiento) y Seccion 4 (Datos básicos adicionales)
$stmtS1 = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = $enfId AND orden = 1");
$sec1Id = $stmtS1->fetchColumn();

$stmtS4 = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = $enfId AND nombre LIKE '%Datos básicos adicionales%'");
$sec4Id = $stmtS4->fetchColumn();

if ($sec4Id) {
    // Mover todos los campos de Seccion 4 a Seccion 1
    $pdo->prepare("UPDATE campo_def SET seccion_id = ? WHERE seccion_id = ?")->execute([$sec1Id, $sec4Id]);
    echo "Campos movidos de Seccion 4 (ID $sec4Id) a Seccion 1 (ID $sec1Id).\n";

    // Eliminar Seccion 4
    $pdo->exec("DELETE FROM seccion_def WHERE id = $sec4Id");
    echo "Sección 4 eliminada de seccion_def.\n";
}

// 3. Reordenar las 13 secciones restantes de O95 en seccion_def
$stmtSecs = $pdo->query("SELECT id, nombre FROM seccion_def WHERE enfermedad_id = $enfId ORDER BY orden, id");
$secs = $stmtSecs->fetchAll();

$updSec = $pdo->prepare("UPDATE seccion_def SET orden = ? WHERE id = ?");
foreach ($secs as $idx => $s) {
    $nuevoOrden = $idx + 1;
    $updSec->execute([$nuevoOrden, $s['id']]);
    echo "Sección ID {$s['id']} '{$s['nombre']}' -> orden $nuevoOrden\n";
}

// 4. Actualizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    $o95Secs = &$manifest['fichas']['O95']['secciones'];
    
    $sec4Campos = [];
    $nuevasSecs = [];

    foreach ($o95Secs as $s) {
        if (stripos($s['nombre'] ?? '', 'Datos básicos adicionales') !== false) {
            $sec4Campos = $s['campos'] ?? [];
        } else {
            $nuevasSecs[] = $s;
        }
    }

    // Agregar campos extra a la primera seccion del manifiesto
    if (!empty($sec4Campos) && !empty($nuevasSecs)) {
        $existentesClaves = array_column($nuevasSecs[0]['campos'], 'clave');
        foreach ($sec4Campos as $c) {
            if (!in_array($c['clave'], $existentesClaves)) {
                $nuevasSecs[0]['campos'][] = $c;
            }
        }
    }

    // Reordenar secuencialmente 1..N
    foreach ($nuevasSecs as $idx => &$ns) {
        $ns['orden'] = $idx + 1;
    }

    $manifest['fichas']['O95']['secciones'] = $nuevasSecs;
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Manifiesto de O95 actualizado sin la sección redundante.\n";
}
