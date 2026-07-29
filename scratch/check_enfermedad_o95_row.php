<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$enf = $pdo->query("SELECT * FROM enfermedad WHERE cie10 = 'O95'")->fetch(PDO::FETCH_ASSOC);
print_r($enf);
