-- ============================================================================
-- VIGÍA · Cambios post Fase 4 — Trazabilidad de consultas a RENIEC
-- Registra cada consulta externa (quién, cuándo, con qué DNI, si hubo
-- resultado) para trazabilidad institucional. La integración en sí
-- (URL/token) vive en config.php y se resuelve en App\Core\ReniecService.
-- ============================================================================
USE vigia;

CREATE TABLE reniec_consulta (
  id         BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT         NOT NULL,
  dni        VARCHAR(8)  NOT NULL,
  encontrado TINYINT(1)  NOT NULL,
  fecha      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_reniec_usuario (usuario_id),
  KEY ix_reniec_dni (dni),
  CONSTRAINT fk_reniec_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
