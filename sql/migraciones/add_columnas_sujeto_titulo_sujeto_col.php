<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// PETICION_P35_RUBEOLA_CONGENITA.md Fase 2b: persistencia de
// "columnas_sujeto"/"titulo_sujeto" (declarados en el manifiesto en la
// Fase 2a) hacia enfermedad -- mismo patrón exacto que nucleo_omitidos
// (add_nucleo_omitidos_col.php): un JSON por ficha, objeto por rol.
$hasColumnasSujeto = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'columnas_sujeto'")->fetchColumn();
if (!$hasColumnasSujeto) {
    $pdo->query("ALTER TABLE enfermedad ADD COLUMN columnas_sujeto LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(columnas_sujeto)) AFTER nucleo_omitidos");
    echo "Columna columnas_sujeto agregada a la tabla enfermedad.\n";
} else {
    echo "Columna columnas_sujeto ya existe en enfermedad.\n";
}

$hasTituloSujeto = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'titulo_sujeto'")->fetchColumn();
if (!$hasTituloSujeto) {
    $pdo->query("ALTER TABLE enfermedad ADD COLUMN titulo_sujeto LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(titulo_sujeto)) AFTER columnas_sujeto");
    echo "Columna titulo_sujeto agregada a la tabla enfermedad.\n";
} else {
    echo "Columna titulo_sujeto ya existe en enfermedad.\n";
}
