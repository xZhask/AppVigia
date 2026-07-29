<?php
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::conexion();
$secciones = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = 9 ORDER BY orden")->fetchAll();

echo "SECCIONES ORIGINALES:\n";
foreach ($secciones as $s) {
    var_dump($s['nombre']);
}

$enfermedad = ['cie10' => 'B26'];

$filtradas = array_values(array_filter($secciones, fn($s) => !in_array(trim($s['nombre']), [
    'Datos de notificación e investigación del caso',
    'Lugar probable de infección'
], true)));

echo "\nSECCIONES FILTRADAS:\n";
foreach ($filtradas as $s) {
    var_dump($s['nombre']);
}
