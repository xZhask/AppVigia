<?php
require __DIR__ . '/../../app/Core/Autoload.php';
require __DIR__ . '/../../app/Core/ayudantes.php';

use App\Core\Database;

date_default_timezone_set('America/Lima');
$pdo = Database::conexion();

// notificacion_semanal pasa a ser el CIERRE semanal (2026-10-17): cada
// establecimiento cierra cada SE, tenga casos o no, antes del plazo
// (config notificacion_semanal). Antes solo registraba "semana sin casos".
//   - tardia / motivo_tardio: cierre después del plazo y su motivo.
//   - anulada_por / anulada_en (ya existían) = reapertura por el ADMIN, con
//     motivo_reapertura.
//   - reapertura_solicitada_*: el registrador pide reabrir una semana cerrada.
// Las filas existentes (negativas) quedan como cierres; su "tardia" se
// calcula contra el plazo con la fecha en que se registraron.
$columnas = array_column($pdo->query('SHOW COLUMNS FROM notificacion_semanal')->fetchAll(), 'Field');
$nuevas = [
    'tardia'                     => "ADD COLUMN tardia tinyint(1) NOT NULL DEFAULT '0' AFTER creado_en",
    'motivo_tardio'              => 'ADD COLUMN motivo_tardio varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER tardia',
    'motivo_reapertura'          => 'ADD COLUMN motivo_reapertura varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER anulada_en',
    'reapertura_solicitada_por'  => 'ADD COLUMN reapertura_solicitada_por int DEFAULT NULL AFTER motivo_reapertura',
    'reapertura_solicitada_en'   => 'ADD COLUMN reapertura_solicitada_en timestamp NULL DEFAULT NULL AFTER reapertura_solicitada_por',
    'motivo_solicitud'           => 'ADD COLUMN motivo_solicitud varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER reapertura_solicitada_en',
];
$agregar = array_diff_key($nuevas, array_flip($columnas));
if ($agregar) {
    $pdo->exec('ALTER TABLE notificacion_semanal ' . implode(', ', $agregar));
    echo 'Columnas agregadas: ' . implode(', ', array_keys($agregar)) . ".\n";
} else {
    echo "Columnas ya existen.\n";
}

$fk = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacion_semanal'
                      AND CONSTRAINT_NAME = 'fk_notif_semanal_solicitada_por'")->fetchColumn();
if (!$fk) {
    $pdo->exec('ALTER TABLE notificacion_semanal ADD CONSTRAINT fk_notif_semanal_solicitada_por
                FOREIGN KEY (reapertura_solicitada_por) REFERENCES usuario (id)');
    echo "FK de solicitud de reapertura creada.\n";
}

if (isset($agregar['tardia'])) {
    $actualizar = $pdo->prepare('UPDATE notificacion_semanal SET tardia = :t WHERE id = :id');
    $n = 0;
    foreach ($pdo->query('SELECT id, anio_epi, semana_epi, creado_en FROM notificacion_semanal')->fetchAll() as $fila) {
        $tardia = new DateTimeImmutable($fila['creado_en']) > \App\Models\NotificacionSemanal::plazo((int) $fila['anio_epi'], (int) $fila['semana_epi']);
        $actualizar->execute(['t' => $tardia ? 1 : 0, 'id' => $fila['id']]);
        $n += $tardia ? 1 : 0;
    }
    echo "Cierres existentes marcados como tardíos: $n.\n";
}
