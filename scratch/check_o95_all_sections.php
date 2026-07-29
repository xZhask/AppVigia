<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$manifest = json_decode(file_get_contents(__DIR__ . '/../manifiesto_fichas.json'), true);

echo "=== Secciones en manifiesto_fichas.json para O95 ===\n";
if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as $sec) {
        echo "Orden {$sec['orden']}: {$sec['nombre']}\n";
        foreach ($sec['campos'] as $c) {
            echo "  - Clave: " . ($c['clave'] ?? 'S/C') . " | Etiqueta: {$c['etiqueta']}\n";
        }
    }
}

echo "\n=== Secciones en DB para O95 ===\n";
$stmt = $pdo->query("SELECT * FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') ORDER BY orden");
foreach ($stmt->fetchAll() as $sec) {
    echo "ID {$sec['id']} | Orden {$sec['orden']}: {$sec['nombre']}\n";
    $stmtC = $pdo->prepare("SELECT * FROM campo_def WHERE seccion_id = ? ORDER BY orden");
    $stmtC->execute([$sec['id']]);
    foreach ($stmtC->fetchAll() as $c) {
        echo "  - ID {$c['id']} | Clave: {$c['clave']} | Etiqueta: {$c['etiqueta']}\n";
    }
}
