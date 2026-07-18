-- ============================================================================
-- VIGÍA · Fase 2 — credenciales de prueba
-- Reemplaza el hash placeholder del admin sembrado en 01_esquema_vigia.sql
-- y agrega un usuario de ejemplo por cada rol para probar el control de acceso.
--
-- Contraseña para TODAS las cuentas de esta semilla: Vigia2026!
-- Generado con password_hash('Vigia2026!', PASSWORD_BCRYPT) — reemplázalo
-- por hashes propios antes de pasar a producción.
-- ============================================================================
USE vigia;

UPDATE usuario
   SET password_hash = '$2y$12$1RbwvqKuMpG1jMjxWqxybuNl40LqcjXwi91k0v3fqPWpiPi3fnu..'
 WHERE id = 1;

INSERT INTO usuario (nombre, email, password_hash, rol, establecimiento_id) VALUES
 ('Elena Ríos, Epidemióloga',  'epidemiologo@dirsapol.gob.pe', '$2y$12$1RbwvqKuMpG1jMjxWqxybuNl40LqcjXwi91k0v3fqPWpiPi3fnu..', 'EPIDEMIOLOGO', NULL),
 ('Mario Chávez, Registrador', 'registrador@dirsapol.gob.pe',  '$2y$12$1RbwvqKuMpG1jMjxWqxybuNl40LqcjXwi91k0v3fqPWpiPi3fnu..', 'REGISTRADOR',  2),
 ('Ana Torres, Lectora',       'lector@dirsapol.gob.pe',       '$2y$12$1RbwvqKuMpG1jMjxWqxybuNl40LqcjXwi91k0v3fqPWpiPi3fnu..', 'LECTOR',       NULL);
