<?php
require_once __DIR__ . '/../app/Core/Autoload.php';
require_once __DIR__ . '/../app/Core/ayudantes.php';

use App\Models\Enfermedad;
use App\Models\Departamento;
use App\Models\Establecimiento;

$b26 = Enfermedad::buscarPorCie10('B26');

$_GET['enfermedad_id'] = $b26['id'];

$enfermedad = $b26;
$enfermedades = Enfermedad::activasConDefinicion();
$enfermedadesPorGrupo = [];
foreach ($enfermedades as $enf) {
    $fam = $enf['familia'] ?: 'Otros';
    $enfermedadesPorGrupo[$fam][] = $enf;
}

$departamentos = Departamento::todos();
$provinciasIniciales = [];
$distritosIniciales = [];
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

echo "SECCION LUGAR PROBABLE INFECCION ENCONTRADA EN HTML:\n";
if (strpos($html, 'cardLugarProbableInfeccionB26') !== false) {
    echo "SI, cardLugarProbableInfeccionB26 está presente en el HTML.\n";
    // Check if hidden attribute is present for B26
    preg_match('/<div class="card section b26-lugar-infeccion-card"[^>]*>/', $html, $matches);
    echo "Tag HTML de la tarjeta:\n" . ($matches[0] ?? 'No encontrado') . "\n";
} else {
    echo "NO encontrada!\n";
}
