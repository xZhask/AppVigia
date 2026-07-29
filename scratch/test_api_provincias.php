<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Models\Provincia;

$provincias = Provincia::porDepartamento('05'); // Ayacucho
echo json_encode($provincias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
