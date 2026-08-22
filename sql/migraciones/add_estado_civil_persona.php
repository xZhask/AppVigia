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

// Cotejo de Enfermedad de Chagas (B57), sección "II. Datos del paciente":
// "Estado civil" no tenía equivalente en el núcleo -- lo único parecido era
// o95_estado_civil, un campo_def exclusivo de O95 (Anexo 2) con opciones
// distintas (formas femeninas + Divorciada + Desconocido, ese formulario es
// solo para mujeres). Mismo criterio que la promoción de "ocupacion" a
// núcleo: campo genérico, opt-out vía NUCLEO_OMITIBLES, no reemplaza a
// o95_estado_civil (coexisten, igual que ocupacion/o95_ocupacion).
addColIfNotExists($pdo, 'persona', 'estado_civil', "ENUM('SOLTERO','CASADO','CONVIVIENTE','SEPARADO','VIUDO') NULL AFTER ocupacion");
