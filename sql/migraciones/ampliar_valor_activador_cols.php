<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// A97 (dengue/chikungunya/zika/arbovirosis, pág. 49 del PDF) necesita un
// "Enfermedad / evento a notificar" que gatea el bloque de clasificación
// correspondiente vía valor_activador con varios slugs separados por coma
// (5 enfermedades comparten el mismo bloque Probable/Confirmado/Descartado:
// "DENGUE_SIN_SIGNOS_DE_ALARMA,DENGUE_CON_SIGNOS_DE_ALARMA,DENGUE_GRAVE,
// CHIKUNGUNYA,CHIKUNGUNYA_GRAVE", 100 caracteres) -- el varchar(60) original
// de campo_def.valor_activador/seccion_def.valor_activador (suficiente para
// activadores de 1-2 valores, el único caso hasta ahora) truncaba y
// cargar_fichas.php fallaba con "Data too long". Se amplía a 191, margen
// cómodo para futuras combinaciones similares sin volver a tocar el esquema.
foreach (['campo_def', 'seccion_def'] as $tabla) {
    $col = $pdo->query("SHOW COLUMNS FROM {$tabla} LIKE 'valor_activador'")->fetch();
    if ($col && stripos($col['Type'], 'varchar(191)') === false) {
        $pdo->exec("ALTER TABLE {$tabla} MODIFY COLUMN valor_activador VARCHAR(191) DEFAULT NULL");
        echo "{$tabla}.valor_activador ampliada a varchar(191).\n";
    } else {
        echo "{$tabla}.valor_activador ya es varchar(191).\n";
    }
}
