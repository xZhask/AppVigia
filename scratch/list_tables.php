<?php
require_once __DIR__ . '/../app/Core/Database.php';
$pdo = \App\Core\Database::conexion();
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
