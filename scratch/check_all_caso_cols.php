<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("DESCRIBE caso");
echo "Todas las columnas en tabla caso:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}
