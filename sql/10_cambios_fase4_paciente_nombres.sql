-- ============================================================================
-- VIGÍA · Cambios post Fase 4 — Separar apellidos y nombres del paciente
-- `paciente.apellidos_nombres` (una sola cadena) pasa a tres columnas:
-- apellido_paterno, apellido_materno, nombres.
--
-- El dato existente usa el formato "Apellido Paterno Apellido Materno,
-- Nombres" (la coma separa apellidos de nombres; es el formato que ya
-- muestra toda la aplicación, p. ej. "Colán Peña, Maryuri Astrid"). El
-- respaldo automático se apoya en esa coma en vez de partir solo por el
-- primer espacio, para no perder el segundo apellido ni los nombres.
-- Aun así, revisar manualmente los casos raros (sin coma, un solo apellido,
-- nombres compuestos, etc.) después de correr esta migración.
-- ============================================================================
USE vigia;

ALTER TABLE paciente
  ADD COLUMN apellido_paterno VARCHAR(60) NULL AFTER num_doc,
  ADD COLUMN apellido_materno VARCHAR(60) NULL AFTER apellido_paterno,
  ADD COLUMN nombres          VARCHAR(80) NULL AFTER apellido_materno;

-- Tramo antes de la coma → primer apellido; tramo después de la coma → nombres.
-- Si no hubiera coma (dato mal cargado), SUBSTRING_INDEX(...,',',-1) devuelve
-- la cadena completa: queda en `nombres` sin perderse, para corregir a mano.
UPDATE paciente
   SET nombres = TRIM(SUBSTRING_INDEX(apellidos_nombres, ',', -1)),
       apellido_paterno = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(apellidos_nombres, ',', 1), ' ', 1))
 WHERE apellido_paterno IS NULL AND apellidos_nombres IS NOT NULL AND apellidos_nombres <> '';

-- Lo que quede del tramo antes de la coma, quitando el primer apellido ya
-- extraído, es el apellido materno (si el paciente tiene uno solo, queda NULL).
UPDATE paciente
   SET apellido_materno = NULLIF(TRIM(
         SUBSTRING(SUBSTRING_INDEX(apellidos_nombres, ',', 1), LENGTH(apellido_paterno) + 1)
       ), '')
 WHERE apellidos_nombres IS NOT NULL AND apellidos_nombres <> '';

ALTER TABLE paciente
  DROP COLUMN apellidos_nombres;

CREATE INDEX ix_pac_apellidos ON paciente (apellido_paterno, apellido_materno);
