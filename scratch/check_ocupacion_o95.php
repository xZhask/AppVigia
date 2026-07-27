<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("SELECT * FROM campo_def WHERE clave LIKE '%ocupac%'");
echo "Campos de ocupacion en DB:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  - ID {$row['id']} | Clave: '{$row['clave']}' | Etiqueta: '{$row['etiqueta']}'\n";
}
