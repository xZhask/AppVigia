<?php
/**
 * Rol de jefe de red (2026-11-04): ve las cifras y el cumplimiento de las IPRESS
 * de su red, sin abrir fichas. usuario.red_id guarda esa red. Idempotente.
 *
 * Uso: php sql/migraciones/add_rol_jefe_red.php
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$rol = $pdo->query("SHOW COLUMNS FROM usuario LIKE 'rol'")->fetch();
if (!str_contains($rol['Type'], 'JEFE_RED')) {
    $pdo->exec("ALTER TABLE usuario MODIFY rol enum('ADMIN','REGISTRADOR','JEFE_RED')
                COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'REGISTRADOR'");
    echo "usuario.rol: agregado JEFE_RED.\n";
} else {
    echo "usuario.rol: ya tiene JEFE_RED.\n";
}

if (!$pdo->query("SHOW COLUMNS FROM usuario LIKE 'red_id'")->fetch()) {
    $pdo->exec("ALTER TABLE usuario
                  ADD COLUMN red_id int DEFAULT NULL AFTER establecimiento_id,
                  ADD KEY ix_user_red (red_id),
                  ADD CONSTRAINT fk_user_red FOREIGN KEY (red_id) REFERENCES red_salud (id)");
    echo "usuario.red_id: agregada.\n";
} else {
    echo "usuario.red_id: ya existe.\n";
}
