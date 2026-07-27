<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$cols = json_encode(['fecha_contacto', 'lugar_contacto', 'edad', 'direccion', 'celular', 'vacunado_72h', 'fecha_vacunacion', 'fecha_inicio_erupcion']);
$pdo->prepare("UPDATE enfermedad SET columnas_contacto = :cols WHERE cie10 = 'B05'")->execute(['cols' => $cols]);

echo "Actualizadas columnas_contacto para B05 en tabla enfermedad como JSON.\n";
