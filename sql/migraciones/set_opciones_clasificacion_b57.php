<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Cotejo de Enfermedad de Chagas (B57), "VII. Clasificación final" (pág. 41
// del PDF): solo trae Caso confirmado / Caso descartado, sin Sospechoso ni
// Probable -- mismo criterio que Difteria/A35 (ver
// _ARCHIVO_PARA_ELIMINAR/sql_incrementales/historico/32_fix_tosferina_fiebreamarilla.sql,
// precedente histórico del mismo ajuste). Restringe los chips genéricos de
// "Clasificación del caso" (clasificacion-chips.php) para que
// sincronizarClasificacionB57() (ficha.js) nunca tenga que resolver a un
// valor que no exista en el DOM.
$pdo->prepare('UPDATE enfermedad SET opciones_clasificacion = ? WHERE cie10 = ?')
    ->execute(['CONFIRMADO,DESCARTADO', 'B57']);

echo "enfermedad.opciones_clasificacion actualizado para B57.\n";
