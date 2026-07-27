<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2778 AND clave = 'b05_fecha_ultimo_dia_seguimiento_contactos'");
$stmt->execute();
$idSeg = $stmt->fetchColumn();

if (!$idSeg) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden) VALUES (2778, 'b05_fecha_ultimo_dia_seguimiento_contactos', 'Fecha de último día de seguimiento de contactos', 'FECHA', 0, 13)");
    $ins->execute();
    $idSeg = $pdo->lastInsertId();
    echo "Creado campo 'Fecha de último día de seguimiento de contactos' (ID $idSeg).\n";
} else {
    echo "El campo ya existe con ID $idSeg.\n";
}
