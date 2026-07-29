<?php
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::conexion();
$b26 = $pdo->query("SELECT * FROM enfermedad WHERE id = 9 OR cie10 = 'B26'")->fetch();
var_dump($b26);
