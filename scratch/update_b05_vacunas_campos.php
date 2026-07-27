<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Eliminar de BD los campos estaticos de vacuna de B05 (IDs 16053 a 16058)
$idsAEliminar = [16053, 16054, 16055, 16056, 16057, 16058];
$inClause = implode(',', $idsAEliminar);

$pdo->exec("DELETE FROM campo_def WHERE id IN ($inClause)");
echo "Eliminados campos estáticos de vacuna (IDs $inClause) de BD.\n";

// Actualizar manifiesto_fichas.json
$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($jsonPath), true);

$secciones = &$manifiesto['fichas']['B05']['secciones'];

foreach ($secciones as &$sec) {
    if (stripos($sec['nombre'], 'vacunal') !== false) {
        $nuevosCampos = [];
        foreach ($sec['campos'] as $c) {
            if ($c['etiqueta'] === 'Estado vacunal') {
                $nuevosCampos[] = $c;
            }
        }
        $sec['campos'] = $nuevosCampos;
        echo "Sección '{$sec['nombre']}' en manifiesto actualizada a solo 'Estado vacunal'.\n";
    }
}

file_put_contents($jsonPath, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "manifiesto_fichas.json guardado.\n";
