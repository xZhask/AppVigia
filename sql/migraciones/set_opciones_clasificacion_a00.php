<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Cotejo de EDA grave / colera (A00), "VI. CLASIFICACION" (pag. 51 del PDF):
// Sospechoso / Probable / Confirmado / Compatible / Caso descartado. Las 4
// genericas por defecto no traen "Compatible", asi que hay que declarar el
// CSV completo -- mismo mecanismo que set_opciones_clasificacion_b57.php.
// Requiere ampliar_enum_clasificacion_caso.php ANTES (COMPATIBLE tiene que
// existir en el ENUM de caso.clasificacion o el guardado falla).
$pdo->prepare('UPDATE enfermedad SET opciones_clasificacion = ? WHERE cie10 = ?')
    ->execute(['SOSPECHOSO,PROBABLE,CONFIRMADO,COMPATIBLE,DESCARTADO', 'A00']);

echo "enfermedad.opciones_clasificacion actualizado para A00.\n";
