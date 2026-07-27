<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();
$stmt = $pdo->query("DESCRIBE campo_def");
echo "Columns in campo_def:\n";
foreach ($stmt->fetchAll() as $c) {
    echo "  - {$c['Field']} ({$c['Type']})\n";
}
