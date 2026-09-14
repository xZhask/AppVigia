<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// notificacion_negativa (P96, pedido del usuario 2026-09-13): "esta semana
// epidemiológica este establecimiento no tuvo casos que notificar" de una
// ficha que se reporta como listado semanal (Anexo 1 de la R.M.
// 279-2009/MINSA, muerte fetal y neonatal). Una fila por enfermedad +
// establecimiento + año + semana: si se anula y se vuelve a notificar, se
// reutiliza la misma fila (anulada_en vuelve a NULL). No se borra nunca, así
// queda quién la notificó y quién la dejó sin efecto.
$existe = $pdo->query("SHOW TABLES LIKE 'notificacion_negativa'")->fetchColumn();
if (!$existe) {
    $pdo->exec(
        "CREATE TABLE notificacion_negativa (
            id int NOT NULL AUTO_INCREMENT,
            enfermedad_id int NOT NULL,
            establecimiento_id int NOT NULL,
            anio_epi smallint NOT NULL,
            semana_epi smallint NOT NULL,
            usuario_id int NOT NULL,
            creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            anulada_por int DEFAULT NULL,
            anulada_en timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_notif_negativa (enfermedad_id, establecimiento_id, anio_epi, semana_epi),
            KEY ix_notif_negativa_est (establecimiento_id),
            KEY fk_notif_negativa_usuario (usuario_id),
            KEY fk_notif_negativa_anulada_por (anulada_por),
            CONSTRAINT fk_notif_negativa_enf FOREIGN KEY (enfermedad_id) REFERENCES enfermedad (id),
            CONSTRAINT fk_notif_negativa_est FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id),
            CONSTRAINT fk_notif_negativa_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id),
            CONSTRAINT fk_notif_negativa_anulada_por FOREIGN KEY (anulada_por) REFERENCES usuario (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "Tabla notificacion_negativa creada.\n";
} else {
    echo "Tabla notificacion_negativa ya existe.\n";
}
