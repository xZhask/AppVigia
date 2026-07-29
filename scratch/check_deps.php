<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();
$deps = $pdo->query("SELECT * FROM departamento ORDER BY nombre LIMIT 10")->fetchAll();
echo "Departamentos:\n";
foreach ($deps as $d) {
    echo "ID: '{$d['id']}' | Nombre: '{$d['nombre']}'\n";
}
