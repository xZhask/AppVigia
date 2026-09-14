<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// caso.fecha_nac_desconocida (A50, sífilis materna y congénita, 2026-09-14):
// casilla "Desconocido" junto a la fecha de nacimiento del núcleo, en las
// fichas que la declaran (nucleo_ajustes.fecha_nac_desconocida o por rama; en
// A50, el ítem 13 del producto: fecha de parto o culminación del embarazo).
// Marcada, la persona queda sin fecha y el caso guarda 1, para distinguir
// "desconocida" de "no llenada". Las fichas que no la declaran no la escriben.
$hasCol = $pdo->query("SHOW COLUMNS FROM caso LIKE 'fecha_nac_desconocida'")->fetchColumn();
if (!$hasCol) {
    $pdo->query('ALTER TABLE caso ADD COLUMN fecha_nac_desconocida TINYINT(1) NOT NULL DEFAULT 0 AFTER edad_unidad');
    echo "Columna fecha_nac_desconocida agregada a la tabla caso.\n";
} else {
    echo "Columna fecha_nac_desconocida ya existe en caso.\n";
}
