<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Nuevo tipo de campo "SI_NO" (cotejo B55, 2026-08-25): pregunta Sí/No suelta
// sin fecha dependiente ni lista de sub-ítems -- variante de SI_NO_FECHA sin
// el bloque de fecha (campos/si-no.php), pedida por el usuario porque
// BOOLEANO (chip .chip-option) resultaba ambiguo para una pregunta suelta.
$col = $pdo->query("SHOW COLUMNS FROM campo_def LIKE 'tipo'")->fetch();
if ($col && strpos($col['Type'], "'SI_NO'") === false) {
    $pdo->exec("ALTER TABLE campo_def MODIFY tipo ENUM('TEXTO','NUMERO','FECHA','BOOLEANO','SELECT','MULTISELECT','TEXTAREA','GRUPO_SI_NO','SI_NO_FECHA','SI_NO','MATRIZ','CRONOLOGIA') NOT NULL");
    echo "Tipo 'SI_NO' añadido al enum campo_def.tipo.\n";
} else {
    echo "campo_def.tipo ya incluye 'SI_NO'.\n";
}
