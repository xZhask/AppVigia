<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// establecimiento.correo (pedido del usuario 2026-11-09): el correo del área o
// de la IPRESS, que comparten varias personas. Recibe los avisos (recordatorio
// de cerrar la SE) pero no restablece contraseñas: usuario.email sigue siendo
// único y personal, así un buzón compartido no permite tomar la cuenta de otro.
$existe = $pdo->query("SHOW COLUMNS FROM establecimiento LIKE 'correo'")->fetchColumn();
if (!$existe) {
    $pdo->exec("ALTER TABLE establecimiento ADD COLUMN correo varchar(150) DEFAULT NULL AFTER categoria");
    echo "Columna establecimiento.correo agregada.\n";
} else {
    echo "Columna establecimiento.correo ya existe.\n";
}
