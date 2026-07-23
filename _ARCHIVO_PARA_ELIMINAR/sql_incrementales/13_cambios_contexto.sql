-- ============================================================================
-- VIGÍA · Cambios de Contexto y Selector de Enfermedades
-- Agrega familias epidemiológicas y nombres cortos para el selector
-- Limpia la tabla usuario de sufijos anómalos
-- ============================================================================
USE vigia;

-- 1. Modificar tabla enfermedad
ALTER TABLE enfermedad
  ADD COLUMN familia VARCHAR(100) NULL AFTER grupo,
  ADD COLUMN nombre_corto VARCHAR(100) NULL AFTER nombre;

-- 2. Asignar familias y nombres cortos según agrupación epidemiológica

-- Inmunoprevenibles
UPDATE enfermedad SET familia = 'Inmunoprevenibles', nombre_corto = nombre
  WHERE nombre IN ('Tos ferina', 'Varicela con complicaciones', 'Parotiditis con complicaciones', 'Difteria', 'Tétanos', 'Tétanos neonatal', 'Parálisis flácida aguda (PFA)');
UPDATE enfermedad SET familia = 'Inmunoprevenibles', nombre_corto = 'Sarampión / rubéola'
  WHERE nombre = 'Sarampión / rubéola / febriles eruptivas';
UPDATE enfermedad SET familia = 'Inmunoprevenibles', nombre_corto = 'Síndrome de rubéola congénita'
  WHERE nombre = 'Síndrome de rubéola congénita (SRC)';

-- Metaxénicas y zoonóticas
UPDATE enfermedad SET familia = 'Metaxénicas y zoonóticas', nombre_corto = 'Dengue y arbovirosis'
  WHERE nombre = 'Dengue, chikungunya, zika y arbovirosis';
UPDATE enfermedad SET familia = 'Metaxénicas y zoonóticas', nombre_corto = nombre
  WHERE nombre IN ('Fiebre amarilla', 'Leishmaniasis', 'Enfermedad de Chagas', 'Enfermedad de Carrión (bartonelosis)', 'Viruela del mono (Mpox)');

-- Transmisión hídrica y alimentaria
UPDATE enfermedad SET familia = 'Transmisión hídrica y alimentaria', nombre_corto = nombre
  WHERE nombre = 'EDA grave / cólera';

-- Materno-perinatal y transmisión vertical
UPDATE enfermedad SET familia = 'Materno-perinatal y transmisión vertical', nombre_corto = 'Gestante con VIH'
  WHERE nombre = 'Gestante con VIH y niño expuesto';
UPDATE enfermedad SET familia = 'Materno-perinatal y transmisión vertical', nombre_corto = 'VIH / SIDA'
  WHERE nombre = 'VIH / SIDA — notificación individual';
UPDATE enfermedad SET familia = 'Materno-perinatal y transmisión vertical', nombre_corto = nombre
  WHERE nombre IN ('Sífilis materna y congénita', 'Muerte materna', 'Muerte fetal y neonatal');

-- Otros eventos bajo vigilancia
UPDATE enfermedad SET familia = 'Otros eventos bajo vigilancia', nombre_corto = nombre
  WHERE nombre IN ('ESAVI severo', 'Violencia familiar', 'Lesiones por accidentes de tránsito');

-- 3. Limpiar nombres de usuario corruptos o con rol adjunto
-- Corrige "Mario Chávez, Registrador" -> "Mario Chávez" o "Mario Ch|ávez" -> "Mario Chávez"
UPDATE usuario
   SET nombre = TRIM(SUBSTRING_INDEX(nombre, ',', 1))
 WHERE nombre LIKE '%, %';

UPDATE usuario
   SET nombre = REPLACE(nombre, '|', '')
 WHERE nombre LIKE '%|%';
