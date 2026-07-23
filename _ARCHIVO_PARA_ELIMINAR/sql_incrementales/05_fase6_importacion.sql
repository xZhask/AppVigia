-- ============================================================================
-- VIGÍA · Fase 6 — Importación desde Excel
-- Traza cada lote de importación (conteos agregados; el detalle fila a fila
-- solo se muestra en la pantalla de resultados, no se persiste).
-- Cargar después de 01-04.
-- ============================================================================
USE vigia;

CREATE TABLE lote_importacion (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  enfermedad_id      INT NOT NULL,
  establecimiento_id INT NOT NULL,
  usuario_id         INT NOT NULL,
  nombre_archivo     VARCHAR(255) NOT NULL,
  total_filas        INT NOT NULL,
  filas_importadas   INT NOT NULL,
  filas_error        INT NOT NULL,
  creado_en          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_lote_enf (enfermedad_id),
  KEY ix_lote_est (establecimiento_id),
  KEY ix_lote_user (usuario_id),
  CONSTRAINT fk_lote_enf  FOREIGN KEY (enfermedad_id)      REFERENCES enfermedad(id),
  CONSTRAINT fk_lote_est  FOREIGN KEY (establecimiento_id) REFERENCES establecimiento(id),
  CONSTRAINT fk_lote_user FOREIGN KEY (usuario_id)         REFERENCES usuario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
