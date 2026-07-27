<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();
$stmt = $pdo->query("DESCRIBE caso_lugar_infeccion");
echo "Columns in caso_lugar_infeccion:\n";
foreach ($stmt->fetchAll() as $c) {
    echo "  - {$c['Field']} ({$c['Type']})\n";
}
