<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

function addColIfNotExists($pdo, $table, $col, $def) {
    $cols = $pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$col}'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$col} {$def}");
        echo "Añadida columna {$col} a la tabla {$table}.\n";
    } else {
        echo "Columna {$col} ya existe en {$table}.\n";
    }
}

// "Pueblo étnico o etnia" pasa de ser un campo_def propio de B05
// (b05_pueblo_etnico_o_etnia, SELECT de catálogo con cascada JS desde
// #etniaSel) al núcleo declarativo compartido, junto a 'etnia'/'etnia_otra'.
// ENUM con las mismas 19 opciones que ya tenía catalogo_id=537 (las que
// puebla MAPA_GRUPO_ETNICO en ficha.js según la etnia elegida) -- mismo
// patrón que la columna 'etnia' (ENUM + whitelist en sanearCamposNucleo()),
// no VARCHAR libre como 'direccion'/'referencia_localizar'.
addColIfNotExists($pdo, 'persona', 'pueblo_etnico', "ENUM('Quechua','Aymara','Jaqaru','Uro','Asháninka','Awajún','Shipibo-Konibo','Yánesha','Kukama Kukamiria','Achuar','Bora','Matsés','Ese Eja','Harakbut','Afroperuano','No aplica','Chino-peruano','Japonés-peruano','Otro') NULL AFTER etnia_otra");
