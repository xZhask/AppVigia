-- ============================================================================
-- VIGÍA · Fase 4 — Gestión de casos
-- Amplía `caso` con anulación lógica (nunca borrado físico).
-- Cargar después de 01_esquema_vigia.sql, 02_ubigeo_data.sql y 03_seed_fase2.sql.
-- ============================================================================
USE vigia;

ALTER TABLE caso
  ADD COLUMN anulado TINYINT(1) NOT NULL DEFAULT 0 AFTER estado,
  ADD COLUMN motivo_anulacion VARCHAR(255) NULL AFTER anulado,
  ADD KEY ix_caso_anulado (anulado);
