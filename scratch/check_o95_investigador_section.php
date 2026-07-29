<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$secs = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);

foreach ($secs as $s) {
    echo "Seccion ID {$s['id']} (Orden {$s['orden']}): {$s['nombre']}\n";
}

$c = $pdo->query("SELECT id, seccion_id, clave, etiqueta, tipo FROM campo_def WHERE clave LIKE '%investiga%' OR clave LIKE '%notifica%' OR etiqueta LIKE '%investig%' OR etiqueta LIKE '%profesion%'")->fetchAll(PDO::FETCH_ASSOC);

echo "\nCampos encontrados relacionados:\n";
foreach ($c as $cam) {
    echo " - [ID {$cam['id']}] (Sec {$cam['seccion_id']}) {$cam['clave']} => {$cam['etiqueta']}\n";
}
