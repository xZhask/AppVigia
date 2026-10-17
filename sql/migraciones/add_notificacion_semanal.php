<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// notificacion_semanal (panel, 2026-10-17): notificación negativa semanal del
// establecimiento, "en esta SE no tuvimos casos de ninguna enfermedad bajo
// vigilancia". Con ella el mapa de cumplimiento distingue "notificó sin
// casos" de "no notificó". Es aparte de notificacion_negativa (P96), que es
// por enfermedad. Una fila por establecimiento + año + semana: anularla no la
// borra y volver a notificar reutiliza la fila.
$existe = $pdo->query("SHOW TABLES LIKE 'notificacion_semanal'")->fetchColumn();
if (!$existe) {
    $pdo->exec(
        "CREATE TABLE notificacion_semanal (
            id int NOT NULL AUTO_INCREMENT,
            establecimiento_id int NOT NULL,
            anio_epi smallint NOT NULL,
            semana_epi smallint NOT NULL,
            usuario_id int NOT NULL,
            creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            anulada_por int DEFAULT NULL,
            anulada_en timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_notif_semanal (establecimiento_id, anio_epi, semana_epi),
            KEY ix_notif_semanal_se (anio_epi, semana_epi),
            KEY fk_notif_semanal_usuario (usuario_id),
            KEY fk_notif_semanal_anulada_por (anulada_por),
            CONSTRAINT fk_notif_semanal_est FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id),
            CONSTRAINT fk_notif_semanal_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id),
            CONSTRAINT fk_notif_semanal_anulada_por FOREIGN KEY (anulada_por) REFERENCES usuario (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "Tabla notificacion_semanal creada.\n";
} else {
    echo "Tabla notificacion_semanal ya existe.\n";
}
