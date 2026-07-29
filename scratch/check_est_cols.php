<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("SELECT es.*, d.nombre as dist_nombre, d.provincia_id, p.nombre as prov_nombre, p.departamento_id, dep.nombre as dep_nombre 
FROM establecimiento es 
LEFT JOIN distrito d ON d.id = es.distrito_id 
LEFT JOIN provincia p ON p.id = d.provincia_id 
LEFT JOIN departamento dep ON dep.id = p.departamento_id 
LIMIT 10");

echo "Establecimientos en DB:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "ID: {$row['id']} | Nombre: {$row['nombre']} | DepID: {$row['departamento_id']} ({$row['dep_nombre']}) | ProvID: {$row['provincia_id']} ({$row['prov_nombre']}) | DistID: {$row['distrito_id']} ({$row['dist_nombre']})\n";
}
