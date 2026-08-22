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

// Cotejo de Enfermedad de Chagas (B57), sección "IV. Migración" (pág. 40-41
// del PDF): años/meses que reside en el domicilio actual, más -si reside
// menos de 6 meses ahí- el domicilio anterior completo (mismos 6 campos que
// "detalle_domicilio" + distrito, reutilizando selector-ubigeo.php con
// prefijo y el partial detalle-domicilio-campos.php). Opt-in vía
// enfermedad.migracion_reciente (add_migracion_reciente_col.php). Solo se
// guarda distrito_id (como el domicilio actual de persona): departamento y
// provincia se derivan por join vía contextoUbigeo(), igual que
// residencia-madre.php.
addColIfNotExists($pdo, 'persona', 'tiempo_reside_anios', 'SMALLINT UNSIGNED NULL AFTER tiempo_residencia');
addColIfNotExists($pdo, 'persona', 'tiempo_reside_meses', 'TINYINT UNSIGNED NULL AFTER tiempo_reside_anios');
addColIfNotExists($pdo, 'persona', 'anterior_distrito_id', 'CHAR(6) NULL AFTER tiempo_reside_meses');
addColIfNotExists($pdo, 'persona', 'anterior_tipo_zona', "ENUM('URBANO','PERIURBANO','RURAL') NULL AFTER anterior_distrito_id");
addColIfNotExists($pdo, 'persona', 'anterior_nombre_zona', 'VARCHAR(160) NULL AFTER anterior_tipo_zona');
addColIfNotExists($pdo, 'persona', 'anterior_tipo_via', 'VARCHAR(60) NULL AFTER anterior_nombre_zona');
addColIfNotExists($pdo, 'persona', 'anterior_nombre_via', 'VARCHAR(160) NULL AFTER anterior_tipo_via');
addColIfNotExists($pdo, 'persona', 'anterior_numero', 'VARCHAR(20) NULL AFTER anterior_nombre_via');
addColIfNotExists($pdo, 'persona', 'anterior_mz_lote', 'VARCHAR(40) NULL AFTER anterior_numero');

addForeignKeyIfNotExists($pdo, 'persona', 'fk_pac_anterior_dist', 'FOREIGN KEY (anterior_distrito_id) REFERENCES distrito (id)');
