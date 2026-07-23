-- ============================================================================
-- VIGÍA · Fase 7 — Simplificación del modelo de roles
-- El sistema solo reconoce dos roles: ADMIN (área central de epidemiología
-- DIRSAPOL, gestiona catálogos/usuarios y decide clasificación/cierre) y
-- REGISTRADOR (personal de cada establecimiento, notifica y da seguimiento
-- a sus propias fichas). Se retiran EPIDEMIOLOGO y LECTOR.
-- ============================================================================
USE vigia;

-- EPIDEMIOLOGO pasa a ADMIN (mismo nivel de responsabilidad central que ya tenía).
UPDATE usuario SET rol = 'ADMIN' WHERE rol = 'EPIDEMIOLOGO';

-- LECTOR no tiene equivalente en el nuevo modelo (no era ni personal de
-- establecimiento ni área central); se retira la cuenta de prueba sembrada
-- en la Fase 2.
DELETE FROM usuario WHERE rol = 'LECTOR';

ALTER TABLE usuario
  MODIFY COLUMN rol ENUM('ADMIN','REGISTRADOR') NOT NULL DEFAULT 'REGISTRADOR';
