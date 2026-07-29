<?php
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::conexion();
$enf = $pdo->query("SELECT id FROM enfermedad WHERE cie10 = 'P35.0'")->fetch();
$enfId = $enf['id'];

$sec = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = $enfId ORDER BY orden")->fetchAll();
echo "SECCIONES PARA P35.0:\n";
print_r($sec);
