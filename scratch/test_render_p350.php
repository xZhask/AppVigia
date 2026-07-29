<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Enfermedad;
use App\Models\Caso;

$enf = Enfermedad::buscarPorCie10('P35.0');
echo "P35.0 ID: " . $enf['id'] . "\n";

$enfermedadesAgrupadas = Enfermedad::todasAgrupadas();
$enfermedad = $enf;
$establecimientos = [];
$puedeElegirEstablecimiento = false;
$valoresFijos = [
    'establecimiento_id' => 1,
    'fecha_notif' => date('Y-m-d'),
    'tipo_captacion' => '',
    'lugar_captacion' => '',
    'clasificacion_captacion' => '',
    'tipo_doc' => 'DNI',
    'num_doc' => '',
    'sexo' => 'M',
    'fecha_nac' => ''
];
$erroresFijos = [];
$valoresCampos = [];
$erroresCampos = [];
$fechaInicioSintomas = '';
$errorFechaInicioSintomas = null;
$semanaEpiPreview = 30;
$anioEpiPreview = 2026;
$modoEdicion = false;
$filasContactos = [];
$filasViajes = [];
$filasVacunas = [];
$filasLugarInfeccion = [];
$muestrasHtml = '';

ob_start();
include __DIR__ . '/../app/Views/nueva/index.php';
$html = ob_get_clean();

echo "Render index.php P35.0 Exitoso! Longitud HTML: " . strlen($html) . " bytes\n";
