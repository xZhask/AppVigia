<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("DESCRIBE caso");
echo "Columnas en tabla caso:\n";
foreach ($stmt->fetchAll() as $c) {
    echo "  - {$c['Field']} ({$c['Type']})\n";
}
