<?php
require_once __DIR__ . '/../app/Core/Autoload.php';
require_once __DIR__ . '/../app/Core/ayudantes.php';

use App\Models\Enfermedad;
use App\Models\Establecimiento;
use App\Models\Departamento;
use App\Models\Provincia;
use App\Models\Distrito;

try {
    $pdo = \App\Core\Database::conexion();
    $b26 = $pdo->query("SELECT * FROM enfermedad WHERE cie10 = 'B26'")->fetch();
    echo "B26 ID: {$b26['id']}\n";

    $_GET['enfermedad_id'] = $b26['id'];

    $enfermedades = Enfermedad::activasConDefinicion();
    $departamentos = Departamento::todos();
    $provinciasIniciales = [];
    $distritosIniciales = [];
    $enfermedad = $b26;
    $establecimientoUsuarioNombre = 'EE.SS. Prueba';
    $puedeElegirEstablecimiento = true;
    $establecimientos = [];

    $valoresFijos = [
        'establecimiento_id' => '',
        'fecha_notif'        => date('Y-m-d'),
        'tipo_doc'           => 'DNI',
        'num_doc'            => '',
        'apellido_paterno'   => '',
        'apellido_materno'   => '',
        'nombres'            => '',
        'sexo'               => '',
        'fecha_nac'          => '',
        'celular'            => '',
        'nacionalidad'       => 'Peruana',
        'direccion'          => '',
        'localidad'          => '',
        'etnia'              => '',
        'etnia_otra'         => '',
        'nombre_tutor'       => '',
        'celular_tutor'      => '',
        'gestante'           => '',
        'semanas_gestacion'  => '',
        'condicion'          => 'PARTICULAR',
    ];
    $erroresFijos = [];
    $valoresCampos = [];
    $erroresCampos = [];

    ob_start();
    include __DIR__ . '/../app/Views/nueva/index.php';
    $html = ob_get_clean();
    echo "Render index.php B26 Exitoso! Longitud HTML: " . strlen($html) . " bytes\n";
} catch (\Throwable $e) {
    echo "ERROR CAPTURADO:\n";
    echo $e->getMessage() . "\n";
    echo "Fichero: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
