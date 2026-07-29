<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$enfs = $pdo->query("SELECT id, cie10, nombre, opciones_clasificacion FROM enfermedad")->fetchAll(PDO::FETCH_ASSOC);
print_r($enfs);
