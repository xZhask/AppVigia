<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("DESCRIBE caso");
echo "Columnas en tabla caso:\n";
foreach ($stmt->fetchAll() as $row) {
    if (stripos($row['Field'], 'etn') !== false || stripos($row['Field'], 'raza') !== false || stripos($row['Field'], 'pueblo') !== false || stripos($row['Field'], 'grupo') !== false) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
}
