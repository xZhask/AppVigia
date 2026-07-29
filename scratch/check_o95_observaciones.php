<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$c = $pdo->query("SELECT id, seccion_id, clave, etiqueta, tipo FROM campo_def WHERE clave LIKE '%observacion%' AND seccion_id IN (SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95'))")->fetchAll(PDO::FETCH_ASSOC);

print_r($c);
