<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$sections = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);

foreach ($sections as $s) {
    echo "Orden {$s['orden']}: [ID {$s['id']}] {$s['nombre']}\n";
}
