<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->prepare("SELECT id, clave, etiqueta FROM campo_def WHERE seccion_id = 2778 AND (clave LIKE '%seguimiento%' OR etiqueta LIKE '%seguimiento%')");
$stmt->execute();
$found = $stmt->fetchAll();

echo "Campos de seguimiento en seccion 2778:\n";
var_dump($found);
