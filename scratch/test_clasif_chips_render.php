<?php
require __DIR__ . '/../app/Core/Autoload.php';
require __DIR__ . '/../app/Core/ayudantes.php';

use App\Core\Database;
use App\Models\Enfermedad;

$pdo = Database::conexion();
$enfermedad = Enfermedad::buscarPorCie10('O95');

echo "Enfermedad O95 cie10: '{$enfermedad['cie10']}'\n";

$clasificacionActual = 'POR_DETERMINAR';
$valoresCampos = [14315 => 'DIRECTA'];

ob_start();
require __DIR__ . '/../app/Views/partials/clasificacion-chips.php';
$html = ob_get_clean();

echo "HTML Rendered:\n" . $html . "\n";
