<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// enfermedad.reglas_campos (Z21, pedido del usuario 2026-09-13, "Culminación
// del embarazo"): reglas entre campos que depende_de no puede expresar --
// condiciones sobre varios campos o numéricas ("alguno mayor que 0"), valores
// que se fijan según otro campo (aborto -> 0 nacidos vivos) y topes de suma
// (embarazo único -> nacidos vivos + óbitos fetales <= 1). Lista JSON;
// cargar_fichas.php la valida y la persiste. NULL = la ficha no declara nada.
$hasCol = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'reglas_campos'")->fetchColumn();
if (!$hasCol) {
    $pdo->query('ALTER TABLE enfermedad ADD COLUMN reglas_campos LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(reglas_campos)) AFTER nucleo_condicional');
    echo "Columna reglas_campos agregada a la tabla enfermedad.\n";
} else {
    echo "Columna reglas_campos ya existe en enfermedad.\n";
}
