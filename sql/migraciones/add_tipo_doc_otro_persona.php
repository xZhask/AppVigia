<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// persona.tipo_doc admite OTRO (2026-10-07): el selector de "Nueva ficha" y la
// importación ofrecían OTRO, pero el ENUM no lo tenía y con STRICT_TRANS_TABLES
// el INSERT de persona fallaba (1265 "Data truncated") en las 24 fichas.
$tipo = $pdo->query("SHOW COLUMNS FROM persona LIKE 'tipo_doc'")->fetch(PDO::FETCH_ASSOC)['Type'] ?? '';
if (strpos($tipo, "'OTRO'") === false) {
    $pdo->query("ALTER TABLE persona MODIFY tipo_doc ENUM('DNI','CE','PTP','PAS','OTRO','SIN_DOCUMENTO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DNI'");
    echo "persona.tipo_doc ahora admite OTRO.\n";
} else {
    echo "persona.tipo_doc ya admite OTRO.\n";
}
