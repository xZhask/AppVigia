<?php
require __DIR__ . '/../app/Core/Autoload.php';
require __DIR__ . '/../app/Core/ayudantes.php';

use App\Core\Database;
use App\Models\Enfermedad;

try {
    $pdo = Database::conexion();
    $enfermedad = Enfermedad::buscarPorCie10('O95');
    
    echo "Enfermedad O95 encontrada: ID {$enfermedad['id']}\n";
    
    $valoresCampos = [];
    $erroresCampos = [];
    $seccionDefModel = new \App\Models\SeccionDef();
    $campoDefModel = new \App\Models\CampoDef();
    $numeroSeccionInicial = 3;
    
    ob_start();
    include __DIR__ . '/../app/Views/partials/secciones-clinicas.php';
    $output = ob_get_clean();
    echo "secciones-clinicas.php para O95 renderizado exitosamente, longitud: " . strlen($output) . " bytes\n";
    
} catch (\Throwable $e) {
    echo "EXCEPCION CAPTURADA: " . $e->getMessage() . "\n";
    echo "Fichero: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
