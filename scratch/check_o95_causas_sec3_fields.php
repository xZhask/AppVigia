<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$sec3 = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE id = 2521")->fetch(PDO::FETCH_ASSOC);
echo "Seccion 3 ID {$sec3['id']}: {$sec3['nombre']}\n";
$campos3 = $pdo->query("SELECT id, clave, etiqueta, tipo FROM campo_def WHERE seccion_id = 2521 ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
foreach ($campos3 as $c) echo " - [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']} ({$c['tipo']})\n";

$sec11 = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE id = 2528")->fetch(PDO::FETCH_ASSOC);
echo "\nSeccion 11 ID {$sec11['id']}: {$sec11['nombre']}\n";
$campos11 = $pdo->query("SELECT id, clave, etiqueta, tipo FROM campo_def WHERE seccion_id = 2528 ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
foreach ($campos11 as $c) echo " - [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']} ({$c['tipo']})\n";
