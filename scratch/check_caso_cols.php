<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$cols = $pdo->query("SHOW COLUMNS FROM caso LIKE 'investigador%'")->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
