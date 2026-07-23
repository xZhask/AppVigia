-- ============================================================================
-- VIGÍA · Fase 7 — Fichas restantes del Grupo A
-- Define seccion_def / campo_def para las enfermedades del Grupo A que aún
-- no tenían cuadro clínico propio (solo Dengue lo tenía, sembrado en la
-- Fase 1): tos ferina, sarampión/rubéola, leishmaniasis, difteria y fiebre
-- amarilla. Criterios clínicos alineados a las definiciones de caso vigentes
-- del MINSA/OPS para vigilancia epidemiológica.
-- ============================================================================
USE vigia;

-- --- Sarampión / rubéola (enfermedad_id = 2) ---
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES
 (2, 'Cuadro clínico', 3);
SET @sec_sr = LAST_INSERT_ID();

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, catalogo_id, orden) VALUES
 (@sec_sr, 'fiebre',                     'Fiebre',                              'BOOLEANO', 0, NULL, 1),
 (@sec_sr, 'exantema_maculopapular',     'Exantema maculopapular',              'BOOLEANO', 0, NULL, 2),
 (@sec_sr, 'tos',                        'Tos',                                 'BOOLEANO', 0, NULL, 3),
 (@sec_sr, 'coriza',                     'Coriza',                              'BOOLEANO', 0, NULL, 4),
 (@sec_sr, 'conjuntivitis',              'Conjuntivitis',                       'BOOLEANO', 0, NULL, 5),
 (@sec_sr, 'adenopatia_retroauricular',  'Adenopatía retroauricular/cervical',  'BOOLEANO', 0, NULL, 6),
 (@sec_sr, 'manchas_koplik',             'Manchas de Koplik',                   'BOOLEANO', 0, NULL, 7);

-- --- Tos ferina (enfermedad_id = 3) ---
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES
 (3, 'Cuadro clínico', 3);
SET @sec_tf = LAST_INSERT_ID();

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, catalogo_id, orden) VALUES
 (@sec_tf, 'tos_paroxistica',      'Tos paroxística (≥ 14 días)',        'BOOLEANO', 0, NULL, 1),
 (@sec_tf, 'estridor_inspiratorio','Estridor inspiratorio ("whoop")',    'BOOLEANO', 0, NULL, 2),
 (@sec_tf, 'vomito_postusivo',     'Vómito post-tusígeno',               'BOOLEANO', 0, NULL, 3),
 (@sec_tf, 'apnea',                'Apnea',                              'BOOLEANO', 0, NULL, 4),
 (@sec_tf, 'cianosis',             'Cianosis',                           'BOOLEANO', 0, NULL, 5),
 (@sec_tf, 'duracion_tos_dias',    'Duración de la tos (días)',          'NUMERO',   0, NULL, 6);

-- --- Leishmaniasis (enfermedad_id = 4) ---
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES
 (4, 'Cuadro clínico', 3);
SET @sec_le = LAST_INSERT_ID();

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, catalogo_id, orden) VALUES
 (@sec_le, 'ulcera_cutanea_indolora',   'Úlcera cutánea indolora',              'BOOLEANO', 0, NULL, 1),
 (@sec_le, 'lesion_mucosa',             'Lesión en mucosa nasal/oral',          'BOOLEANO', 0, NULL, 2),
 (@sec_le, 'tiempo_evolucion_semanas',  'Tiempo de evolución (semanas)',        'NUMERO',   0, NULL, 3),
 (@sec_le, 'antecedente_zona_endemica', 'Antecedente de exposición en zona endémica', 'BOOLEANO', 0, NULL, 4);

-- --- Difteria (enfermedad_id = 5) ---
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES
 (5, 'Cuadro clínico', 3),
 (5, 'Signos de alarma', 4);
SET @sec_di1 = LAST_INSERT_ID();
SET @sec_di2 = @sec_di1 + 1;

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, catalogo_id, orden) VALUES
 (@sec_di1, 'fiebre',                  'Fiebre',                                  'BOOLEANO', 0, NULL, 1),
 (@sec_di1, 'pseudomembrana_faringea', 'Pseudomembrana faringoamigdalina grisácea adherente', 'BOOLEANO', 0, NULL, 2),
 (@sec_di1, 'disfagia',                'Disfagia',                                'BOOLEANO', 0, NULL, 3),
 (@sec_di1, 'adenopatia_cervical',     'Adenopatía cervical',                     'BOOLEANO', 0, NULL, 4),
 (@sec_di1, 'disfonia',                'Disfonía',                                'BOOLEANO', 0, NULL, 5),
 (@sec_di2, 'edema_cervical',          'Edema cervical ("cuello de toro")',      'BOOLEANO', 0, NULL, 1),
 (@sec_di2, 'dificultad_respiratoria', 'Dificultad respiratoria/estridor',        'BOOLEANO', 0, NULL, 2),
 (@sec_di2, 'miocarditis_sospecha',    'Sospecha de miocarditis',                 'BOOLEANO', 0, NULL, 3);

-- --- Fiebre amarilla (enfermedad_id = 6) ---
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES
 (6, 'Cuadro clínico', 3),
 (6, 'Signos de alarma', 4);
SET @sec_fa1 = LAST_INSERT_ID();
SET @sec_fa2 = @sec_fa1 + 1;

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, catalogo_id, orden) VALUES
 (@sec_fa1, 'fiebre',         'Fiebre',                 'BOOLEANO', 0, NULL, 1),
 (@sec_fa1, 'ictericia',      'Ictericia',              'BOOLEANO', 0, NULL, 2),
 (@sec_fa1, 'dolor_abdominal','Dolor abdominal',        'BOOLEANO', 0, NULL, 3),
 (@sec_fa1, 'vomitos',        'Vómitos',                'BOOLEANO', 0, NULL, 4),
 (@sec_fa1, 'cefalea',        'Cefalea',                'BOOLEANO', 0, NULL, 5),
 (@sec_fa2, 'sangrado_mucosas','Sangrado de mucosas',   'BOOLEANO', 0, NULL, 1),
 (@sec_fa2, 'oliguria',       'Oliguria',                'BOOLEANO', 0, NULL, 2),
 (@sec_fa2, 'signo_faget',    'Signo de Faget (bradicardia relativa a la fiebre)', 'BOOLEANO', 0, NULL, 3);
