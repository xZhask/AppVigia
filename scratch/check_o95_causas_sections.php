<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$secs = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);

foreach ($secs as $s) {
    echo "Seccion ID {$s['id']} (Orden {$s['orden']}): {$s['nombre']}\n";
    $campos = $pdo->query("SELECT id, clave, etiqueta, tipo FROM campo_def WHERE seccion_id = {$s['id']} ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($campos as $c) {
        echo "   - [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']} ({$c['tipo']})\n";
    }
}
