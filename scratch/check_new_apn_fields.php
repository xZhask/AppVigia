<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$c16142 = $pdo->query("SELECT * FROM campo_def WHERE id = 16142 OR clave = 'o95_categoria_eess_apn'")->fetch(PDO::FETCH_ASSOC);
$c16143 = $pdo->query("SELECT * FROM campo_def WHERE id = 16143 OR clave = 'o95_responsable_apn_otro'")->fetch(PDO::FETCH_ASSOC);

var_dump($c16142, $c16143);
