<?php
/**
 * Notificación mensual (2026-10-16): enfermedad.tipo_notif solo admitía
 * INMEDIATA y SEMANAL, pero la NTS 115-MINSA/DGE (6.1.4) fija periodicidad
 * mensual para VIH/SIDA (B24); la semanal es solo para gestantes con VIH y
 * niños expuestos (Z21). Agrega MENSUAL al ENUM y pasa B24 a mensual (solo si
 * sigue en SEMANAL: no pisa un cambio hecho desde Catálogos > Enfermedades).
 *
 * Uso: php sql/migraciones/add_tipo_notif_mensual.php
 *
 * Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$columna = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'tipo_notif'")->fetch();
if (!str_contains((string) $columna['Type'], "'MENSUAL'")) {
    $pdo->exec("ALTER TABLE enfermedad MODIFY tipo_notif ENUM('INMEDIATA','SEMANAL','MENSUAL') NOT NULL DEFAULT 'SEMANAL'");
    echo "enfermedad.tipo_notif: MENSUAL agregado.\n";
} else {
    echo "enfermedad.tipo_notif: ya admitía MENSUAL.\n";
}

$actualizada = $pdo->prepare("UPDATE enfermedad SET tipo_notif = 'MENSUAL' WHERE cie10 = 'B24' AND tipo_notif = 'SEMANAL'");
$actualizada->execute();
echo $actualizada->rowCount() ? "B24: notificación mensual.\n" : "B24: sin cambios.\n";
