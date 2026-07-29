<?php
require_once __DIR__ . '/../app/Core/Autoload.php';
require_once __DIR__ . '/../app/Core/ayudantes.php';

use App\Controllers\CasosController;

try {
    $_GET['enfermedad_id'] = '9'; // B26
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/casos/nuevo/secciones-clinicas?enfermedad_id=9';

    // Mock Auth
    \App\Core\Session::iniciar();
    $_SESSION['usuario'] = [
        'id' => 1,
        'nombre' => 'Test',
        'rol' => 'ADMIN',
        'establecimiento_id' => 1,
        'persona_id' => null
    ];

    echo "Probando seccionesClinicas(9)...\n";
    $controller = new CasosController();

    ob_start();
    $controller->seccionesClinicas();
    $res = ob_get_clean();

    echo "Respuesta de seccionesClinicas:\n" . substr($res, 0, 300) . "...\n";
    echo "Longitud total: " . strlen($res) . " bytes\n";

} catch (\Throwable $e) {
    echo "ERROR CAPTURADO:\n";
    echo $e->getMessage() . "\n";
    echo "Fichero: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
