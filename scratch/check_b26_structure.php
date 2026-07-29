<?php
require_once __DIR__ . '/../app/Core/Database.php';
$pdo = \App\Core\Database::conexion();
$b26 = $pdo->query("SELECT id, nombre, cie10 FROM enfermedad WHERE cie10 = 'B26'")->fetch();
echo "Enfermedad B26: ID {$b26['id']}\n";
$stmtSec = $pdo->prepare("SELECT * FROM seccion_def WHERE enfermedad_id = ? ORDER BY orden");
$stmtSec->execute([$b26['id']]);
$sec = $stmtSec->fetchAll();
foreach ($sec as $s) {
    echo "\n=== Seccion " . $s['orden'] . ": " . ($s['nombre_seccion'] ?? $s['nombre'] ?? '') . " (ID: " . $s['id'] . ") ===\n";
    $stmtCampos = $pdo->prepare("SELECT * FROM campo_def WHERE seccion_id = ? ORDER BY orden");
    $stmtCampos->execute([$s['id']]);
    $campos = $stmtCampos->fetchAll();
    foreach ($campos as $c) {
        echo "   - [ID: {$c['id']}] {$c['etiqueta']} | Clave: {$c['clave']} | Tipo: {$c['tipo']} | Depende: {$c['depende_de']} ({$c['valor_activador']})\n";
    }
}
