-- ============================================================================
-- VIGÍA · Fase 7 — Índices de rendimiento
-- Verificado con EXPLAIN y con 8 000 fichas de prueba (volumen realista):
--   - El listado de fichas sin filtro (vista por defecto de /casos) hacía un
--     full table scan + filesort para ORDER BY fecha_notif DESC, id DESC.
--     Este índice lo resuelve con un backward index scan.
--   - cod_renipress no tenía índice ni restricción de unicidad; con el
--     padrón real cargado (82 establecimientos, Fase 7) conviene garantizar
--     que no se dupliquen códigos RENIPRESS.
-- ============================================================================
USE vigia;

ALTER TABLE caso
  ADD INDEX ix_caso_fecha_notif (fecha_notif, id);

ALTER TABLE establecimiento
  ADD UNIQUE KEY uq_est_cod_renipress (cod_renipress);
