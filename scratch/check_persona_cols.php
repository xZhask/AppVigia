<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("DESCRIBE persona");
echo "Columnas en tabla persona:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}
