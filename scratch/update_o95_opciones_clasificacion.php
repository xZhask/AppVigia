<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$st = $pdo->prepare("UPDATE enfermedad SET opciones_clasificacion = 'DIRECTA,INDIRECTA,INCIDENTAL,POR_DETERMINAR' WHERE cie10 = 'O95'");
$st->execute();

echo "Actualizado opciones_clasificacion para O95 en la base de datos: " . $st->rowCount() . " filas.\n";
