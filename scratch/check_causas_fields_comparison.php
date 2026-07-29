<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

echo "--- SECCION 3 (ID 2517): Causas de defuncion (Anexo 1) ---\n";
$campos3 = $pdo->query("SELECT id, clave, etiqueta, tipo FROM campo_def WHERE seccion_id = 2517 ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
foreach ($campos3 as $c) echo " - [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']} ({$c['tipo']})\n";

echo "\n--- SECCION 11 (ID 2528): Causas de defuncion (Anexo 2) ---\n";
$campos11 = $pdo->query("SELECT id, clave, etiqueta, tipo FROM campo_def WHERE seccion_id = 2528 ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
foreach ($campos11 as $c) echo " - [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']} ({$c['tipo']})\n";
