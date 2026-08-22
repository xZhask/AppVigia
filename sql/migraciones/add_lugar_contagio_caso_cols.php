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

function addForeignKeyIfNotExists($pdo, $table, $constraint, $definition) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?"
    );
    $stmt->execute([$table, $constraint]);
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} {$definition}");
        echo "Añadida restricción {$constraint} a la tabla {$table}.\n";
    } else {
        echo "Restricción {$constraint} ya existe en {$table}.\n";
    }
}

// Cotejo de Enfermedad de Chagas (B57), sección "V. Antecedentes
// epidemiológicos" (pág. 40 del PDF): "Lugar probable de contagio"
// (Departamento/Provincia/Distrito/Localidad), un único bloque por caso (no
// una lista) -- va en `caso`, no en `persona` (es un dato del caso, no del
// paciente). Mismo criterio de "solo se guarda distrito_id" que el domicilio
// de persona/domicilio anterior: departamento y provincia se derivan por
// join vía contextoUbigeo(). Se pinta a medida en secciones-clinicas.php,
// justo antes de "Fecha probable de contagio" (campo_def real).
addColIfNotExists($pdo, 'caso', 'lugar_contagio_distrito_id', 'CHAR(6) NULL AFTER fecha_inicio_sintomas');
addColIfNotExists($pdo, 'caso', 'lugar_contagio_localidad', 'VARCHAR(160) NULL AFTER lugar_contagio_distrito_id');

addForeignKeyIfNotExists($pdo, 'caso', 'fk_caso_lugar_contagio_dist', 'FOREIGN KEY (lugar_contagio_distrito_id) REFERENCES distrito (id)');
