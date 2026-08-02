<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Simétrico de nucleo_omitidos, con la polaridad invertida: lista de
// campos del núcleo que están OCULTOS por defecto y una ficha declara para
// MOSTRAR (opt-in), en vez de mostrados por defecto y declarados para
// ocultar (opt-out). Mismo mecanismo JSON declarativo, misma tabla
// (enfermedad), para no repetir una columna dedicada por cada campo núcleo
// opt-in nuevo que aparezca -- "n_historia_clinica" es el primero.
// Ver PETICION_HC_Y_LABORATORIO.md, Parte 1.
$hasCol = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'nucleo_incluidos'")->fetchColumn();
if (!$hasCol) {
    $pdo->query("ALTER TABLE enfermedad ADD COLUMN nucleo_incluidos LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(nucleo_incluidos)) AFTER nucleo_omitidos");
    echo "Columna nucleo_incluidos agregada a la tabla enfermedad.\n";
} else {
    echo "Columna nucleo_incluidos ya existe en enfermedad.\n";
}
