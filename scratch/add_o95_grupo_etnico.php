<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Verificar si ya existe o95_grupo_etnico
$stmt = $pdo->query("SELECT * FROM campo_def WHERE clave = 'o95_grupo_etnico'");
$campoExistente = $stmt->fetch();

if (!$campoExistente) {
    // Obtener id de la seccion 5 de O95
    $stmtS = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND orden = 5");
    $seccionId = $stmtS->fetchColumn();

    // Obtener orden maximo
    $stmtMax = $pdo->prepare("SELECT MAX(orden) FROM campo_def WHERE seccion_id = ?");
    $stmtMax->execute([$seccionId]);
    $maxOrden = (int)$stmtMax->fetchColumn() + 1;

    $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden) VALUES (?, 'o95_grupo_etnico', 'Grupo étnico', 'TEXTO', 0, ?)");
    $stmtIns->execute([$seccionId, $maxOrden]);
    $nuevoId = $pdo->lastInsertId();

    echo "Campo 'o95_grupo_etnico' insertado con ID {$nuevoId} en seccion {$seccionId}\n";
} else {
    echo "El campo ya existe con ID {$campoExistente['id']}\n";
    $nuevoId = $campoExistente['id'];
}

// Actualizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        if (($sec['orden'] ?? 0) == 5 || stripos($sec['nombre'] ?? '', 'Datos básicos adicionales') !== false) {
            $existe = false;
            foreach ($sec['campos'] as $c) {
                if (($c['clave'] ?? '') === 'o95_grupo_etnico') {
                    $existe = true;
                    break;
                }
            }
            if (!$existe) {
                $sec['campos'][] = [
                    'clave' => 'o95_grupo_etnico',
                    'etiqueta' => 'Grupo étnico',
                    'tipo' => 'TEXTO',
                    'obligatorio' => 0
                ];
                echo "Agregado o95_grupo_etnico a la seccion 5 en manifiesto_fichas.json\n";
            }
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto actualizado y guardado correctamente.\n";
