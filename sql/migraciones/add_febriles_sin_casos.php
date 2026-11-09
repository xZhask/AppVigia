<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// febriles_sin_casos (pedido del usuario 2026-11-09): "hoy este
// establecimiento no tuvo febriles", la notificación negativa DIARIA del
// registro de febriles (REG-FEB). La semanal de las demás enfermedades sigue
// siendo el cierre de la SE (notificacion_semanal); esta solo distingue, día
// por día, "no hubo febriles" de "no se registró". Una fila por
// establecimiento + fecha: si se anula y se vuelve a notificar, se reutiliza
// la misma fila (anulada_en vuelve a NULL). No se borra nunca. Registrar un
// febril en esa fecha la anula sola (CasosController).
$existe = $pdo->query("SHOW TABLES LIKE 'febriles_sin_casos'")->fetchColumn();
if (!$existe) {
    $pdo->exec(
        "CREATE TABLE febriles_sin_casos (
            id int NOT NULL AUTO_INCREMENT,
            establecimiento_id int NOT NULL,
            fecha date NOT NULL,
            usuario_id int NOT NULL,
            creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            anulada_por int DEFAULT NULL,
            anulada_en timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_febriles_sin_casos (establecimiento_id, fecha),
            KEY fk_febriles_sin_casos_usuario (usuario_id),
            KEY fk_febriles_sin_casos_anulada_por (anulada_por),
            CONSTRAINT fk_febriles_sin_casos_est FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id),
            CONSTRAINT fk_febriles_sin_casos_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id),
            CONSTRAINT fk_febriles_sin_casos_anulada_por FOREIGN KEY (anulada_por) REFERENCES usuario (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "Tabla febriles_sin_casos creada.\n";
} else {
    echo "Tabla febriles_sin_casos ya existe.\n";
}
