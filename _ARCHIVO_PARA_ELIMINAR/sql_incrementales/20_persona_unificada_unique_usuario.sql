-- ============================================================================
-- VIGÍA · Cierre de CAMBIOS_PERSONA_UNIFICADA.md
-- "Un usuario por persona": la relación usuario.persona_id ya se validaba en
-- la capa de aplicación (Usuario::existePersona), pero no estaba forzada en
-- la base de datos. Se reemplaza el índice simple por uno único; MySQL/
-- MariaDB permiten múltiples NULL en una UNIQUE KEY, así que los usuarios con
-- perfil_incompleto = 1 y persona_id aún sin enlazar no se ven afectados.
-- ============================================================================
USE vigia;

ALTER TABLE usuario
  DROP INDEX ix_user_persona,
  ADD UNIQUE KEY uq_user_persona (persona_id);
