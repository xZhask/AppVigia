<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();
$stmt = $pdo->query("DESCRIBE caso_muestra");
$cols = $stmt->fetchAll();

echo "Columns in caso_muestra:\n";
foreach ($cols as $c) {
    echo "  - {$c['Field']} ({$c['Type']})\n";
}
