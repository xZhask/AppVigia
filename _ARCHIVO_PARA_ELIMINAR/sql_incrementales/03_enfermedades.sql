-- ============================================================================
-- VIGÍA · Cambios post Fase 4 — Catálogo completo de 24 fichas MINSA
-- Corrige nombre/tipo de notificación de las enfermedades ya sembradas en
-- 01_esquema_vigia.sql (conservando sus id, porque seccion_def/campo_def de
-- Fase 1 y Fase 7 ya apuntan a esos id) e inserta las 18 que faltan.
--
-- Importante: aquí SOLO se cargan filas de `enfermedad`. No se generan
-- seccion_def / campo_def para las nuevas: se definirán ficha por ficha, por
-- grupo, en fases posteriores. Las que ya tienen definición (dengue, y desde
-- la Fase 7: sarampión/rubéola, tos ferina, leishmaniasis, difteria, fiebre
-- amarilla) siguen funcionando sin cambios.
-- ============================================================================
USE vigia;

-- --- Corrige las 3 que ya existían con datos distintos al PDF MINSA ---
UPDATE enfermedad SET nombre = 'Dengue, chikungunya, zika y arbovirosis' WHERE id = 1;
UPDATE enfermedad SET nombre = 'Sarampión / rubéola / febriles eruptivas' WHERE id = 2;
UPDATE enfermedad SET tipo_notif = 'INMEDIATA' WHERE id = 3; -- Tos ferina

-- --- Las demás ya sembradas (Leishmaniasis=4, Difteria=5, Fiebre amarilla=6,
--     ESAVI severo=7) ya coinciden con el PDF: no requieren cambios. ---

-- --- Inserta las 17 fichas restantes del PDF MINSA ---
INSERT INTO enfermedad (nombre, nombre_corto, cie10, tipo_notif, grupo, familia) VALUES
 ('Varicela con complicaciones',                  'Varicela con complicaciones',                  'B01',   'INMEDIATA', 'A', 'Inmunoprevenibles'),
 ('Parotiditis con complicaciones',                'Parotiditis con complicaciones',                'B26',   'INMEDIATA', 'A', 'Inmunoprevenibles'),
 ('Viruela del mono (Mpox)',                       'Viruela del mono (Mpox)',                       'B04X',  'INMEDIATA', 'A', 'Metaxénicas y zoonóticas'),
 ('EDA grave / cólera',                            'EDA grave / cólera',                            'A00',   'INMEDIATA', 'A', 'Transmisión hídrica y alimentaria'),
 ('Enfermedad de Chagas',                          'Enfermedad de Chagas',                          'B57',   'SEMANAL',   'A', 'Metaxénicas y zoonóticas'),
 ('Enfermedad de Carrión (bartonelosis)',          'Enfermedad de Carrión (bartonelosis)',          'A44',   'SEMANAL',   'A', 'Metaxénicas y zoonóticas'),
 ('Tétanos',                                       'Tétanos',                                       'A35',   'INMEDIATA', 'A', 'Inmunoprevenibles'),
 ('Tétanos neonatal',                              'Tétanos neonatal',                              'A33',   'INMEDIATA', 'A', 'Inmunoprevenibles'),
 ('Parálisis flácida aguda (PFA)',                 'Parálisis flácida aguda (PFA)',                 'A80',   'INMEDIATA', 'A', 'Inmunoprevenibles'),
 ('Síndrome de rubéola congénita (SRC)',           'Síndrome de rubéola congénita',                 'P35.0', 'INMEDIATA', 'B', 'Inmunoprevenibles'),
 ('Gestante con VIH y niño expuesto',              'Gestante con VIH',                              'Z21',   'SEMANAL',   'B', 'Materno-perinatal y transmisión vertical'),
 ('VIH / SIDA — notificación individual',          'VIH / SIDA',                                    'B24',   'SEMANAL',   'B', 'Materno-perinatal y transmisión vertical'),
 ('Sífilis materna y congénita',                   'Sífilis materna y congénita',                   'A50',   'SEMANAL',   'B', 'Materno-perinatal y transmisión vertical'),
 ('Violencia familiar',                            'Violencia familiar',                            NULL,    'SEMANAL',   'C', 'Otros eventos bajo vigilancia'),
 ('Lesiones por accidentes de tránsito',           'Lesiones por accidentes de tránsito',           NULL,    'SEMANAL',   'C', 'Otros eventos bajo vigilancia'),
 ('Muerte materna',                                'Muerte materna',                                NULL,    'INMEDIATA', 'D', 'Materno-perinatal y transmisión vertical'),
 ('Muerte fetal y neonatal',                       'Muerte fetal y neonatal',                       NULL,    'INMEDIATA', 'D', 'Materno-perinatal y transmisión vertical');
