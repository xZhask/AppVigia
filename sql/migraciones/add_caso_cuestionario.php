<?php
/**
 * Cuestionarios asociados a un caso (Y59.0, Anexo 6.2 "Lista de chequeo del
 * vacunatorio", 2026-10-11). No son una ficha de enfermedad: se llenan aparte
 * y se vinculan al caso. Una fila por caso y cuestionario; las respuestas van
 * en `datos` (JSON), validadas contra la definición de
 * config/cuestionarios/<cuestionario>.php.
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS caso_cuestionario (
        id INT NOT NULL AUTO_INCREMENT,
        caso_id INT NOT NULL,
        cuestionario VARCHAR(60) NOT NULL,
        datos LONGTEXT NOT NULL,
        usuario_id INT NULL,
        creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_caso_cuestionario (caso_id, cuestionario),
        CONSTRAINT fk_caso_cuestionario_caso FOREIGN KEY (caso_id) REFERENCES caso (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "Tabla caso_cuestionario lista.\n";
