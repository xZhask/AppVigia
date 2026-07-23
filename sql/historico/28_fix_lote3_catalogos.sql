-- 28_fix_lote3_catalogos.sql
-- Corrige un bug pre-existente de 19_lotes_3_4.sql: sus campos SELECT,
-- MULTISELECT y GRUPO_SI_NO se crearon todos con catalogo_id NULL, por lo
-- que renderizan como listas desplegables/matrices vacías. Afecta a
-- EDA grave / cólera (id=11), Tétanos (id=14), Tétanos neonatal (id=15) y
-- Parálisis flácida aguda (id=16). Sin casos creados para estas 4
-- enfermedades: UPDATE de catalogo_id es seguro.
-- Opciones tomadas de DEFINICION_FICHAS.md (Lote 3), condensando MATRIZ a
-- SELECT donde el proyecto ya venía haciéndolo en lotes anteriores.

-- ============================================================================
-- EDA grave / cólera (id=11)
-- ============================================================================

INSERT INTO catalogo (nombre) VALUES ('Fuente de agua de consumo');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CANO_CASA', 'Caño dentro de su casa', 1),
  (@cat, 'CANO_PUBLICO', 'Caño público', 2),
  (@cat, 'POZO', 'Pozo', 3),
  (@cat, 'RIO', 'Río', 4),
  (@cat, 'PUQUIAL', 'Puquial (manantial)', 5),
  (@cat, 'CAMION_CISTERNA', 'Camión cisterna', 6),
  (@cat, 'EMBOTELLADA', 'Embotellada', 7),
  (@cat, 'OTRO', 'Otro', 8);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 517;

INSERT INTO catalogo (nombre) VALUES ('Tipo de recipiente de almacenamiento de agua');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'TANQUE_ELEVADO', 'Tanque elevado', 1),
  (@cat, 'CILINDRO', 'Cilindro', 2),
  (@cat, 'TANQUE_BAJO', 'Tanque bajo', 3),
  (@cat, 'OTRO', 'Otro', 4);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 519;

INSERT INTO catalogo (nombre) VALUES ('Lugar de consumo de alimentos');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CASA', 'Solo preparados en casa', 1),
  (@cat, 'RESTAURANTE', 'Restaurante', 2),
  (@cat, 'AMBULANTE', 'Ambulante', 3),
  (@cat, 'PENSION', 'Pensión', 4),
  (@cat, 'MERCADO', 'Mercado', 5),
  (@cat, 'OTRO', 'Otro', 6);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 522;

INSERT INTO catalogo (nombre) VALUES ('Alimentación en menores de 2 años');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'BIBERON', 'Ingiere leche en biberón', 1),
  (@cat, 'IGUAL_ADULTOS', 'Consume los mismos alimentos que los adultos', 2),
  (@cat, 'LACTANCIA', 'Recibe lactancia materna', 3);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 523;

INSERT INTO catalogo (nombre) VALUES ('Eliminación de excretas');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'RED_DENTRO', 'Red pública dentro de la vivienda', 1),
  (@cat, 'RED_FUERA', 'Red pública fuera de la vivienda', 2),
  (@cat, 'POZO_LETRINA', 'Pozo negro/ciego/letrina', 3),
  (@cat, 'SIN_SERVICIO', 'Sin servicio', 4),
  (@cat, 'OTRO', 'Otro', 5);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 524;

INSERT INTO catalogo (nombre) VALUES ('Síntomas - EDA grave/cólera');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'DIARREA', 'Diarrea', 1),
  (@cat, 'DOLOR_ABDOMINAL', 'Dolor abdominal', 2),
  (@cat, 'NAUSEAS', 'Náuseas', 3),
  (@cat, 'VOMITOS', 'Vómitos', 4),
  (@cat, 'ARTRALGIAS', 'Artralgias', 5),
  (@cat, 'FIEBRE', 'Fiebre', 6),
  (@cat, 'CEFALEA', 'Cefalea', 7),
  (@cat, 'MALESTAR_GENERAL', 'Malestar general', 8),
  (@cat, 'CALAMBRES', 'Calambres', 9);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 526;

INSERT INTO catalogo (nombre) VALUES ('Consistencia de la deposición');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ACUOSA', 'Acuosa o líquida', 1),
  (@cat, 'GRUMOSA', 'Grumosa', 2),
  (@cat, 'PASTOSA', 'Pastosa', 3);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 528;

INSERT INTO catalogo (nombre) VALUES ('Tipo de diarrea');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ACUOSA', 'EDA acuosa', 1),
  (@cat, 'DISENTERICA', 'EDA disentérica', 2),
  (@cat, 'PERSISTENTE', 'EDA persistente', 3);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 529;

INSERT INTO catalogo (nombre) VALUES ('Presencia de (deposición)');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MOCO', 'Moco', 1),
  (@cat, 'SANGRE', 'Sangre', 2),
  (@cat, 'MOCO_SANGRE', 'Moco y sangre', 3);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 530;

INSERT INTO catalogo (nombre) VALUES ('Clasificación de deshidratación');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SIN', 'Sin deshidratación', 1),
  (@cat, 'LEVE', 'Con deshidratación leve', 2),
  (@cat, 'MODERADA', 'Con deshidratación moderada', 3),
  (@cat, 'GRAVE', 'Con deshidratación grave', 4),
  (@cat, 'SHOCK', 'Shock', 5);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 532;

INSERT INTO catalogo (nombre) VALUES ('Plan de tratamiento - EDA');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'A', 'Plan A', 1),
  (@cat, 'B', 'Plan B', 2),
  (@cat, 'C', 'Plan C', 3);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 533;

INSERT INTO catalogo (nombre) VALUES ('Complicaciones - EDA grave/cólera');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SHOCK_HIPOVOLEMICO', 'Shock hipovolémico', 1),
  (@cat, 'ACIDOSIS', 'Acidosis', 2),
  (@cat, 'INSUFICIENCIA_RENAL', 'Insuficiencia renal', 3),
  (@cat, 'EDEMA_PULMON', 'Edema agudo de pulmón', 4);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 537;

INSERT INTO catalogo (nombre) VALUES ('Transferencia - EDA grave/cólera');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'HOSPITALIZACION', 'Para hospitalización', 1),
  (@cat, 'DIALISIS', 'Para diálisis', 2);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 539;

INSERT INTO catalogo (nombre) VALUES ('Clasificación final - EDA grave/cólera');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SOSPECHOSO', 'Sospechoso', 1),
  (@cat, 'PROBABLE', 'Probable', 2),
  (@cat, 'CONFIRMADO', 'Confirmado', 3),
  (@cat, 'COMPATIBLE', 'Compatible', 4),
  (@cat, 'DESCARTADO', 'Descartado', 5);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 540;

-- ============================================================================
-- Tétanos (id=14) y Tétanos neonatal (id=15) — catálogo de signos compartido
-- ============================================================================

INSERT INTO catalogo (nombre) VALUES ('Herida - Tétanos');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'UNICA', 'Única', 1),
  (@cat, 'MULTIPLE', 'Múltiple', 2);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 542;

INSERT INTO catalogo (nombre) VALUES ('Tipo de herida - Tétanos');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SUPERFICIAL', 'Superficial', 1),
  (@cat, 'PROFUNDA', 'Profunda', 2);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 543;

INSERT INTO catalogo (nombre) VALUES ('Signos y síntomas - Tétanos');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'FIEBRE', 'Fiebre', 1),
  (@cat, 'TRISMUS', 'Trismus (no succiona)', 2),
  (@cat, 'RISA_SARDONICA', 'Risa sardónica', 3),
  (@cat, 'CONVULSIONES', 'Convulsiones (espasmos)', 4),
  (@cat, 'OPISTOTONOS', 'Opistótonos', 5),
  (@cat, 'ONFALITIS', 'Onfalitis', 6),
  (@cat, 'ICTERICIA', 'Ictericia', 7);
SET @cat_signos_tetanos = @cat;
UPDATE campo_def SET catalogo_id = @cat_signos_tetanos WHERE id = 545;
UPDATE campo_def SET catalogo_id = @cat_signos_tetanos WHERE id = 555;

INSERT INTO catalogo (nombre) VALUES ('Atendido por - Tétanos');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MEDICO', 'Médico', 1),
  (@cat, 'ENFERMERA', 'Enfermera', 2),
  (@cat, 'TECNICO_SANITARIO', 'Técnico sanitario', 3),
  (@cat, 'OTRO', 'Otro', 4);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 547;

INSERT INTO catalogo (nombre) VALUES ('Diagnóstico definitivo - Tétanos');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CONFIRMADO', 'Confirmado', 1),
  (@cat, 'DESCARTADO', 'Descartado', 2);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 552;

-- ============================================================================
-- Parálisis flácida aguda - PFA (id=16)
-- ============================================================================

INSERT INTO catalogo (nombre) VALUES ('Localización de la parálisis - PFA');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MSD', 'Miembro superior derecho', 1),
  (@cat, 'MSI', 'Miembro superior izquierdo', 2),
  (@cat, 'MID', 'Miembro inferior derecho', 3),
  (@cat, 'MII', 'Miembro inferior izquierdo', 4),
  (@cat, 'MUSCULOS_CERVICALES', 'Músculos cervicales', 5),
  (@cat, 'MUSCULOS_RESPIRATORIOS', 'Músculos respiratorios', 6),
  (@cat, 'PARES_CRANEALES', 'Pares craneales', 7);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 568;

INSERT INTO catalogo (nombre) VALUES ('Tipo de parálisis - PFA');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'PARESIA', 'Paresia (parálisis parcial)', 1),
  (@cat, 'PARALISIS', 'Parálisis (completa)', 2);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 569;

INSERT INTO catalogo (nombre) VALUES ('Tono muscular - PFA');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'DISMINUIDO', 'Disminuido', 1),
  (@cat, 'AUSENTE', 'Ausente', 2),
  (@cat, 'NORMAL', 'Normal', 3),
  (@cat, 'IGNORADO', 'Ignorado', 4);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 570;

INSERT INTO catalogo (nombre) VALUES ('Clasificación final - PFA');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CONFIRMADO', 'Poliomielitis confirmada', 1),
  (@cat, 'COMPATIBLE', 'Compatible', 2),
  (@cat, 'DESCARTADO', 'Descartado (no polio)', 3);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 574;

INSERT INTO catalogo (nombre) VALUES ('Evaluación a los 60 días - PFA');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CON_SECUELA', 'Con secuela', 1),
  (@cat, 'SIN_SECUELA', 'Sin secuela', 2),
  (@cat, 'FALLECIDO', 'Fallecido', 3),
  (@cat, 'PERDIDO', 'Perdido de seguimiento', 4),
  (@cat, 'NO_EVALUADO', 'No evaluado', 5);
UPDATE campo_def SET catalogo_id = @cat WHERE id = 575;
