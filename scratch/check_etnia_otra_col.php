<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$c1 = $pdo->query("SHOW COLUMNS FROM caso LIKE 'etnia%'")->fetchAll(PDO::FETCH_ASSOC);
$c2 = $pdo->query("SHOW COLUMNS FROM persona LIKE 'etnia%'")->fetchAll(PDO::FETCH_ASSOC);

echo "CASO etnia cols:\n";
print_r($c1);

echo "PERSONA etnia cols:\n";
print_r($c2);
