<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// enfermedad.campos_persona (Z21, pedido del usuario 2026-09-12): claves de
// campo_def que se pintan dentro de la tarjeta de identidad ("Datos de la
// gestante" / "Datos del niño nacido expuesto") en vez de en su propia
// tarjeta. Hermano de campos_notificacion, mismo formato JSON (lista de
// claves); cargar_fichas.php la valida y la persiste. NULL = la ficha no
// declara nada, comportamiento de siempre.
$hasCol = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'campos_persona'")->fetchColumn();
if (!$hasCol) {
    $pdo->query('ALTER TABLE enfermedad ADD COLUMN campos_persona LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(campos_persona)) AFTER campos_notificacion');
    echo "Columna campos_persona agregada a la tabla enfermedad.\n";
} else {
    echo "Columna campos_persona ya existe en enfermedad.\n";
}
