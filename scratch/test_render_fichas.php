<?php
require __DIR__ . '/../app/Core/Autoload.php';
require __DIR__ . '/../app/Core/ayudantes.php';

use App\Core\Database;
use App\Models\Enfermedad;
use App\Models\Caso;

try {
    $pdo = Database::conexion();
    $enfermedad = Enfermedad::buscarPorCie10('B05');
    
    echo "Enfermedad B05 encontrada: ID {$enfermedad['id']}\n";
    
    // Simular render de la vista nueva o editar
    $valoresCampos = [];
    $erroresCampos = [];
    $seccionDefModel = new \App\Models\SeccionDef();
    $campoDefModel = new \App\Models\CampoDef();
    
    ob_start();
    include __DIR__ . '/../app/Views/partials/secciones-clinicas.php';
    $output = ob_get_clean();
    echo "secciones-clinicas.php renderizado exitosamente, longitud: " . strlen($output) . " bytes\n";
    
} catch (\Throwable $e) {
    echo "EXCEPCION CAPTURADA: " . $e->getMessage() . "\n";
    echo "Fichero: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
