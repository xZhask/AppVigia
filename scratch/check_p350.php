<?php
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::conexion();
$enf = $pdo->query("SELECT id, cie10, nombre FROM enfermedad WHERE cie10 = 'P35.0' OR nombre LIKE '%rub%cong%' OR nombre LIKE '%SRC%'")->fetchAll();

echo "ENFERMEDADES:\n";
print_r($enf);

if (!empty($enf)) {
    $enfId = $enf[0]['id'];
    $sec = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = $enfId ORDER BY orden")->fetchAll();
    echo "SECCIONES ($enfId):\n";
    print_r($sec);
}
