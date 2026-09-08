<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

function addColIfNotExists($pdo, $table, $col, $def) {
    $cols = $pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$col}'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$col} {$def}");
        echo "Anadida columna {$col} a la tabla {$table}.\n";
    } else {
        echo "Columna {$col} ya existe en {$table}.\n";
    }
}

// Cotejo de EDA grave / colera (A00), "V. LABORATORIO" de la pag. 51 del PDF.
// La tabla del papel tiene 6 columnas por muestra: Establecimiento de Salud |
// Muestra | Examen realizado | Resultado | Serogrupo | Serotipo. Las 3
// primeras ya existian en caso_muestra (establecimiento no); serogrupo y
// serotipo vivian como campo_def a nivel de CASO, lo que impedia registrar
// mas de un aislamiento por caso -- el usuario pidio explicitamente que
// "cada muestra sea un registro independiente", asi que bajan a columna de
// la tabla hija, donde ademas el gate "Serogrupo = O1 -> habilitar Serotipo"
// se expresa con el mecanismo declarativo que ya existe
// (columnas_tablas_hija.caso_muestra.depende_de_columna).
//
// Las 3 son opt-in por ficha (COLUMNAS_TABLA_HIJA_VALIDAS en cargar_fichas.php
// + columnas_tablas_hija en el manifiesto): ninguna de las otras 11 fichas con
// usa_muestras=1 las declara, asi que su widget no cambia.
//
// 'establecimiento' es texto libre y no un FK a establecimiento.id: mismo
// criterio y mismo ancho que caso_vacuna.establecimiento (varchar 160), que
// ya resolvio este mismo problema para el widget de vacunas. El EE.SS.
// notificante del caso sigue siendo el del nucleo; esta columna es el
// laboratorio/EE.SS. que tomo o proceso ESA muestra.
addColIfNotExists($pdo, 'caso_muestra', 'establecimiento', 'VARCHAR(160) NULL AFTER contexto');
addColIfNotExists($pdo, 'caso_muestra', 'serogrupo', 'VARCHAR(40) NULL AFTER titulacion');
addColIfNotExists($pdo, 'caso_muestra', 'serotipo', 'VARCHAR(40) NULL AFTER serogrupo');
