<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Cotejo de Fiebre amarilla (A95): enfermedad.usa_lugar_infeccion venía en 1
// (heredado sin cotejar, junto a Difteria la única otra ficha con este flag
// -- probablemente copiado al crear A95). El PDF de A95 no trae una sección
// "Lugar probable de infección" (caso_lugar_infeccion): ese contenido ya
// está cubierto por "III. Migración" (localidades visitadas/casos
// reportados/caso_viaje, ver migracion-a95.php), así que la tarjeta fija
// genérica "Antecedentes epidemiológicos" quedaba mostrando un
// "+ Agregar lugar" duplicado y sin PDF que lo respalde -- el usuario pidió
// quitarla.
$pdo->prepare('UPDATE enfermedad SET usa_lugar_infeccion = 0 WHERE cie10 = ?')
    ->execute(['A95']);

echo "enfermedad.usa_lugar_infeccion desactivado para A95.\n";
