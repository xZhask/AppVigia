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

// Entrada J acotada al bloque de domicilio (PETICION_MAPEO_Y_EDAD.md):
// detalle de dirección dentro del distrito ya resuelto por distrito_id
// (selector-ubigeo.php) -- tipo de zona/vía, nombre de vía, número,
// Mz./Lote y tiempo de residencia. Mismo ancho que 'direccion'/
// 'referencia_localizar', su familia más cercana. tipo_zona es ENUM
// porque el PDF da un código cerrado de 3 valores (1=Urbano;
// 2=Periurbano; 3=Rural) en las dos fichas que lo piden tal cual
// (A37.0, P35.0); tipo_via es texto libre porque el propio PDF lo
// insinúa como lista abierta ("Avenida, Calle, Jirón, etc."). Opt-in vía
// enfermedad.detalle_domicilio (add_detalle_domicilio_col.php), no
// NUCLEO_OMITIBLES: con solo 2/24 fichas confirmadas, opt-out pintaría
// estos campos sin base en el PDF en el resto.
addColIfNotExists($pdo, 'persona', 'tipo_zona', "ENUM('URBANO','PERIURBANO','RURAL') NULL AFTER referencia_localizar");
addColIfNotExists($pdo, 'persona', 'tipo_via', 'VARCHAR(60) NULL AFTER tipo_zona');
addColIfNotExists($pdo, 'persona', 'nombre_via', 'VARCHAR(160) NULL AFTER tipo_via');
addColIfNotExists($pdo, 'persona', 'numero', 'VARCHAR(20) NULL AFTER nombre_via');
addColIfNotExists($pdo, 'persona', 'mz_lote', 'VARCHAR(40) NULL AFTER numero');
addColIfNotExists($pdo, 'persona', 'tiempo_residencia', 'VARCHAR(60) NULL AFTER mz_lote');
