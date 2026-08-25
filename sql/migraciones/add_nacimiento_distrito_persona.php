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

// Cotejo de Leishmaniasis (B55), sección "I. DATOS GENERALES" (pág. 45 del
// PDF): "Lugar de Nacimiento: (Distrito, Provincia, Departamento)" -- campo
// núcleo que no existía en ningún lado del esquema (ni en B55 ni, hasta
// donde se revisó, en ninguna otra ficha). Mismo patrón que
// persona.anterior_distrito_id (add_migracion_reciente_persona_cols.php):
// un solo distrito_id, Provincia/Departamento se derivan por join vía
// contextoUbigeo() -- no se guardan aparte. Opt-in vía enfermedad.nucleo_incluidos
// (cargar_fichas.php: NUCLEO_INCLUIBLES), arrancando solo con B55.
addColIfNotExists($pdo, 'persona', 'nacimiento_distrito_id', 'CHAR(6) NULL AFTER fecha_nac');
addForeignKeyIfNotExists($pdo, 'persona', 'fk_pac_nacimiento_dist', 'FOREIGN KEY (nacimiento_distrito_id) REFERENCES distrito (id)');
