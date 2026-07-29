<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$c1 = $pdo->query("SHOW COLUMNS FROM caso LIKE '%tutor%'")->fetchAll(PDO::FETCH_ASSOC);
$c2 = $pdo->query("SHOW COLUMNS FROM persona LIKE '%tutor%'")->fetchAll(PDO::FETCH_ASSOC);

echo "CASO tutor cols:\n";
print_r($c1);

echo "PERSONA tutor cols:\n";
print_r($c2);
