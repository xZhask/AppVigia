-- 27_lote8_muertes.sql
-- LOTE 8 de DEFINICION_FICHAS_B_C_D.md: Muerte materna, Muerte fetal y neonatal.
-- Sin casos creados: DELETE en cascada seguro.
--
-- Muerte materna: Anexo 1 (notificación) y Anexo 2 (investigación) se
-- modelan como secciones sucesivas del MISMO caso (orden 1-5 = Anexo 1,
-- 6+ = Anexo 2), tal como pide el documento. El gate de UI "Anexo 2 se
-- habilita después de registrado el Anexo 1" no se implementa en este
-- lote (requeriría lógica de habilitación condicional en el controlador/JS,
-- fuera del alcance de una definición de campos); queda como pendiente.

DELETE FROM seccion_def WHERE enfermedad_id IN (23, 24);

-- ============================================================================
-- 8.1 Muerte materna (id=23)
-- ============================================================================
INSERT INTO catalogo (nombre) VALUES ('Identificado por - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ACTIVA', 'Vigilancia activa', 1), (@cat, 'PASIVA', 'Vigilancia pasiva', 2);
SET @cat_identificado_por = @cat;

INSERT INTO catalogo (nombre) VALUES ('Institución que notifica - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'IGSS_GOB_REGIONAL', 'IGSS/Gobierno Regional', 1), (@cat, 'ESSALUD', 'EsSalud', 2),
  (@cat, 'SANIDAD_FFAA_PNP', 'Sanidad de FFAA/PNP', 3), (@cat, 'PRIVADO', 'Privado', 4), (@cat, 'OTRA', 'Otra', 5);
SET @cat_institucion_notifica_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Momento del fallecimiento - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'EMBARAZO', 'Embarazo', 1), (@cat, 'PARTO', 'Parto', 2), (@cat, 'PUERPERIO', 'Puerperio', 3), (@cat, 'DESCONOCIDO', 'Desconocido', 4);
SET @cat_momento_fallecimiento_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Lugar del fallecimiento - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'EESS_IGSS', 'EE.SS. IGSS/Gobierno Regional', 1), (@cat, 'EESS_ESSALUD', 'EE.SS. EsSalud', 2),
  (@cat, 'EESS_FFAA_PNP', 'EE.SS. Sanidad FFAA/PNP', 3), (@cat, 'EESS_PRIVADO', 'EE.SS. privado', 4),
  (@cat, 'TRAYECTO', 'Trayecto', 5), (@cat, 'DOMICILIO', 'Domicilio', 6), (@cat, 'OTRO', 'Otro', 7);
SET @cat_lugar_fallecimiento_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Causa genérica de muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'HEMORRAGIA', 'Hemorragia', 1), (@cat, 'HIPERTENSION_GESTACIONAL', 'Hipertensión gestacional', 2),
  (@cat, 'INFECCION_SEPSIS', 'Infección/Sepsis', 3), (@cat, 'OTRA', 'Otra causa', 4);
SET @cat_causa_generica_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Clasificación de causa - muerte materna (inicial)');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'DIRECTA', 'Directa', 1), (@cat, 'INDIRECTA', 'Indirecta', 2), (@cat, 'INCIDENTAL', 'Incidental', 3), (@cat, 'POR_DETERMINAR', 'Por determinar', 4);
SET @cat_clasificacion_inicial_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Clasificación de causa - muerte materna (final)');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'DIRECTA', 'Directa', 1), (@cat, 'INDIRECTA', 'Indirecta', 2), (@cat, 'INCIDENTAL', 'Incidental', 3);
SET @cat_clasificacion_final_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Idioma');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ESPANOL', 'Español', 1), (@cat, 'QUECHUA', 'Quechua', 2), (@cat, 'AYMARA', 'Aymara', 3), (@cat, 'OTRA', 'Otra', 4);
SET @cat_idioma = @cat;

INSERT INTO catalogo (nombre) VALUES ('Nivel educativo - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'NINGUNO', 'Ninguno', 1), (@cat, 'PRIMARIA_INCOMPLETA', 'Primaria incompleta', 2), (@cat, 'PRIMARIA_COMPLETA', 'Primaria completa', 3),
  (@cat, 'SECUNDARIA_INCOMPLETA', 'Secundaria incompleta', 4), (@cat, 'SECUNDARIA_COMPLETA', 'Secundaria completa', 5),
  (@cat, 'SUPERIOR_UNIVERSITARIA', 'Superior universitaria', 6), (@cat, 'SUPERIOR_TECNICA', 'Superior técnica', 7), (@cat, 'DESCONOCIDO', 'Desconocido', 8);
SET @cat_nivel_educativo_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Estado civil - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SOLTERA', 'Soltera', 1), (@cat, 'CASADA', 'Casada', 2), (@cat, 'CONVIVIENTE', 'Conviviente', 3),
  (@cat, 'DIVORCIADA', 'Divorciada', 4), (@cat, 'SEPARADA', 'Separada', 5), (@cat, 'VIUDA', 'Viuda', 6), (@cat, 'DESCONOCIDO', 'Desconocido', 7);
SET @cat_estado_civil_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Tipo de seguro - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SIS', 'SIS', 1), (@cat, 'ESSALUD', 'EsSalud', 2), (@cat, 'PRIVADO', 'Privado', 3), (@cat, 'OTROS', 'Otros', 4), (@cat, 'NO_TIENE', 'No tiene seguro', 5);
SET @cat_tipo_seguro_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Antecedentes patológicos - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'NINGUNO', 'Ninguno', 1), (@cat, 'HTA_CRONICA', 'Hipertensión crónica', 2), (@cat, 'DIABETES', 'Diabetes mellitus', 3),
  (@cat, 'CARDIOPATIAS', 'Cardiopatías', 4), (@cat, 'ENF_RENAL', 'Enfermedad renal', 5), (@cat, 'NEOPLASIAS', 'Neoplasias', 6),
  (@cat, 'ENF_HEPATICA', 'Enfermedad hepática', 7), (@cat, 'TUBERCULOSIS', 'Tuberculosis', 8), (@cat, 'ITS_VIH_SIDA', 'ITS/VIH/SIDA', 9),
  (@cat, 'ALCOHOLISMO', 'Alcoholismo', 10), (@cat, 'DROGADICCION', 'Drogadicción', 11), (@cat, 'VIOLENCIA_GENERO', 'Violencia de género', 12),
  (@cat, 'TABAQUISMO', 'Tabaquismo', 13), (@cat, 'DESNUTRICION_CRONICA', 'Desnutrición crónica', 14), (@cat, 'OTRA', 'Otra', 15), (@cat, 'DESCONOCIDO', 'Desconocido', 16);
SET @cat_antecedentes_patologicos_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Método anticonceptivo previo');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'NO_USO', 'No usó', 1), (@cat, 'HORMONAL', 'Hormonal', 2), (@cat, 'DIU', 'DIU', 3), (@cat, 'BARRERA', 'Barrera', 4),
  (@cat, 'QUIRURGICO', 'Quirúrgico', 5), (@cat, 'ABSTINENCIA_PERIODICA', 'Abstinencia periódica', 6), (@cat, 'OTRO', 'Otro', 7), (@cat, 'DESCONOCIDO', 'Desconocido', 8);
SET @cat_metodo_anticonceptivo = @cat;

INSERT INTO catalogo (nombre) VALUES ('Responsable de la atención');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'GINECO_OBSTETRA', 'Méd. gineco-obstetra', 1), (@cat, 'INTENSIVISTA', 'Méd. intensivista', 2), (@cat, 'RESIDENTE', 'Méd. residente', 3),
  (@cat, 'MEDICO_GENERAL', 'Méd. general', 4), (@cat, 'OBSTETRA', 'Obstetra', 5), (@cat, 'ENFERMERA', 'Enfermera(o)', 6),
  (@cat, 'INTERNO', 'Interno', 7), (@cat, 'TECNICO', 'Técnico', 8), (@cat, 'PARTERA', 'Partera', 9), (@cat, 'FAMILIAR', 'Familiar', 10),
  (@cat, 'OTRO', 'Otro', 11), (@cat, 'DESCONOCIDO', 'Desconocido', 12);
SET @cat_responsable_atencion = @cat;

INSERT INTO catalogo (nombre) VALUES ('Trimestre de primera atención prenatal');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'I', 'I trimestre', 1), (@cat, 'II', 'II trimestre', 2), (@cat, 'III', 'III trimestre', 3);
SET @cat_trimestre_apn = @cat;

INSERT INTO catalogo (nombre) VALUES ('Complicaciones del embarazo');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'HEMORRAGIA', 'Hemorragia', 1), (@cat, 'PREECLAMPSIA', 'Preeclampsia/Eclampsia', 2), (@cat, 'HELLP', 'Síndrome de HELLP', 3),
  (@cat, 'DIABETES_GESTACIONAL', 'Diabetes gestacional', 4), (@cat, 'ABORTO', 'Aborto', 5), (@cat, 'DESNUTRICION', 'Desnutrición', 6),
  (@cat, 'RPM', 'RPM más de 12 horas', 7), (@cat, 'ECTOPICO', 'Embarazo ectópico', 8), (@cat, 'ITU', 'Infección del tracto urinario', 9),
  (@cat, 'SEPSIS', 'Sepsis', 10), (@cat, 'OBITO_FETAL', 'Óbito fetal', 11), (@cat, 'ANEMIA', 'Anemia', 12), (@cat, 'OTRO', 'Otro', 13);
SET @cat_complicaciones_embarazo = @cat;

INSERT INTO catalogo (nombre) VALUES ('Complicaciones del parto');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'HEMORRAGIA', 'Hemorragia', 1), (@cat, 'PREECLAMPSIA', 'Preeclampsia/Eclampsia', 2), (@cat, 'HELLP', 'Síndrome de HELLP', 3),
  (@cat, 'TP_PROLONGADO', 'Trabajo de parto prolongado', 4), (@cat, 'PARTO_OBSTRUIDO', 'Parto obstruido', 5),
  (@cat, 'PARTO_DISTOCICO', 'Parto distócico', 6), (@cat, 'TP_PRECIPITADO', 'Trabajo de parto precipitado', 7),
  (@cat, 'ALUMBRAMIENTO_INCOMPLETO', 'Alumbramiento incompleto', 8), (@cat, 'OTRO', 'Otro', 9);
SET @cat_complicaciones_parto = @cat;

INSERT INTO catalogo (nombre) VALUES ('Complicaciones del puerperio');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'HEMORRAGIA', 'Hemorragia', 1), (@cat, 'ATONIA_UTERINA', 'Atonía uterina', 2), (@cat, 'PREECLAMPSIA', 'Preeclampsia/Eclampsia', 3),
  (@cat, 'HELLP', 'Síndrome de HELLP', 4), (@cat, 'SEPSIS', 'Sepsis', 5), (@cat, 'ENDOMETRITIS', 'Endometritis', 6),
  (@cat, 'RETENCION_RESTOS', 'Retención de restos placentarios', 7), (@cat, 'DEPRESION_POSPARTO', 'Depresión posparto', 8), (@cat, 'OTRO', 'Otro', 9);
SET @cat_complicaciones_puerperio = @cat;

INSERT INTO catalogo (nombre) VALUES ('Lugar del parto o aborto - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'DOMICILIO', 'Domicilio', 1), (@cat, 'EESS', 'En EE.SS.', 2), (@cat, 'OTRO', 'Otro', 3), (@cat, 'NO_APLICA', 'No aplica', 4);
SET @cat_lugar_parto_aborto_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Tipo de parto - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'VAGINAL', 'Vaginal', 1), (@cat, 'CESAREA', 'Cesárea', 2), (@cat, 'INSTRUMENTADO', 'Instrumentado', 3),
  (@cat, 'DESCONOCIDO', 'Desconocido', 4), (@cat, 'NO_APLICA', 'No aplica', 5);
SET @cat_tipo_parto_mm = @cat;

INSERT INTO catalogo (nombre) VALUES ('Persona que identificó signos de peligro / decisión');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ELLA_MISMA', 'Ella misma', 1), (@cat, 'PAREJA', 'Pareja', 2), (@cat, 'FAMILIAR', 'Familiar', 3), (@cat, 'OTRO', 'Otro', 4);
SET @cat_persona_identifico = @cat;

INSERT INTO catalogo (nombre) VALUES ('Dificultad de acceso a servicios de salud');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'INACCESIBILIDAD_GEOGRAFICA', 'Inaccesibilidad geográfica', 1), (@cat, 'DISTANCIA', 'Distancia', 2),
  (@cat, 'TRANSPORTE', 'Transporte', 3), (@cat, 'CREENCIAS', 'Creencias/costumbres', 4), (@cat, 'OTRO', 'Otro', 5);
SET @cat_dificultad_acceso = @cat;

INSERT INTO catalogo (nombre) VALUES ('Dificultad de atención en el EE.SS.');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ECONOMICAS', 'Económicas', 1), (@cat, 'IDIOMA', 'Idioma', 2), (@cat, 'ADMINISTRATIVAS', 'Administrativas/trámites', 3),
  (@cat, 'DEMORA_ATENCION', 'Demora en la atención', 4), (@cat, 'MALA_ATENCION', 'Mala atención', 5), (@cat, 'OTRO', 'Otro', 6);
SET @cat_dificultad_atencion = @cat;

INSERT INTO catalogo (nombre) VALUES ('Persona que brindó la información');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MADRE', 'Madre', 1), (@cat, 'PADRE', 'Padre', 2), (@cat, 'PAREJA', 'Pareja', 3), (@cat, 'FAMILIAR', 'Familiar', 4),
  (@cat, 'PARTERA', 'Partera', 5), (@cat, 'VECINO', 'Vecino', 6), (@cat, 'OTRO', 'Otro', 7);
SET @cat_persona_informacion = @cat;

INSERT INTO catalogo (nombre) VALUES ('Sintomatología comunitaria - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SANGRADO', 'Sangrado', 1), (@cat, 'PERDIDA_LIQUIDO', 'Pérdida de líquido', 2), (@cat, 'DOLOR', 'Dolor', 3),
  (@cat, 'ALZA_TERMICA', 'Sensación de alza térmica', 4), (@cat, 'NAUSEAS_VOMITOS', 'Náuseas y vómitos', 5),
  (@cat, 'CONVULSIONES', 'Convulsiones', 6), (@cat, 'DEBILIDAD', 'Debilidad', 7), (@cat, 'ANSIEDAD', 'Ansiedad', 8),
  (@cat, 'ALTERACION_CONCIENCIA', 'Pérdida o alteración del estado de conciencia', 9), (@cat, 'CEFALEA', 'Cefalea', 10), (@cat, 'OTRO', 'Otro', 11);
SET @cat_sintomatologia_comunitaria = @cat;

INSERT INTO catalogo (nombre) VALUES ('Maniobras usadas durante el parto/placenta');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'NO_SE_USO', 'No se usó', 1), (@cat, 'MANTEO', 'Manteo', 2), (@cat, 'ACOMODO', 'Acomodo', 3), (@cat, 'MASAJES', 'Masajes', 4), (@cat, 'OTRO', 'Otro', 5);
SET @cat_maniobras = @cat;

INSERT INTO catalogo (nombre) VALUES ('Tipo de establecimiento más cercano');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'PUESTO_SALUD', 'Puesto de salud', 1), (@cat, 'CENTRO_SALUD', 'Centro de salud', 2), (@cat, 'HOSPITAL', 'Hospital', 3);
SET @cat_tipo_eess_cercano = @cat;

INSERT INTO catalogo (nombre) VALUES ('Las cuatro demoras - muerte materna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'IDENTIFICACION_PROBLEMA', 'En la identificación del problema', 1),
  (@cat, 'DECISION_BUSCAR_AYUDA', 'En la decisión de buscar ayuda', 2),
  (@cat, 'ACCEDER_SERVICIOS', 'En acceder a los servicios de salud', 3),
  (@cat, 'RECIBIR_TRATAMIENTO', 'En recibir tratamiento adecuado y oportuno', 4);
SET @cat_cuatro_demoras = @cat;

-- ---------------------------------------------------------------------------
-- Anexo 1 — Notificación inmediata
-- ---------------------------------------------------------------------------
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (23, 'Anexo 1 — Notificación inmediata', 1);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'fecha_hora_notificacion_mm', 'Fecha y hora de notificación', 'FECHA', 1, 1, 0, 'CASO_INDICE', NULL),
  (@sec, 'identificado_por_mm', 'Identificado por', 'SELECT', 2, 0, 0, 'CASO_INDICE', @cat_identificado_por),
  (@sec, 'institucion_notifica_mm', 'Institución que notifica', 'SELECT', 3, 0, 0, 'CASO_INDICE', @cat_institucion_notifica_mm),
  (@sec, 'num_hc_fallecida', 'N.° de HC', 'TEXTO', 4, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'domicilio_fallecida', 'Domicilio (departamento, provincia, distrito)', 'TEXTO', 5, 0, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (23, 'Datos del fallecimiento', 2);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'momento_fallecimiento_mm', 'Momento', 'SELECT', 1, 1, 0, 'CASO_INDICE', @cat_momento_fallecimiento_mm),
  (@sec, 'edad_gestacional_fallecimiento', 'Edad gestacional al momento del fallecimiento (semanas, o desconocido)', 'NUMERO', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_hora_fallecimiento_mm', 'Fecha y hora de fallecimiento', 'FECHA', 3, 1, 0, 'CASO_INDICE', NULL),
  (@sec, 'lugar_fallecimiento_mm', 'Lugar del fallecimiento', 'SELECT', 4, 0, 0, 'CASO_INDICE', @cat_lugar_fallecimiento_mm),
  (@sec, 'nombre_eess_lugar_fallecimiento', 'Nombre del EE.SS. o lugar (si Otro)', 'TEXTO', 5, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'permanencia_eess_dias', 'Permanencia (estadía) en el EE.SS.: días', 'NUMERO', 6, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'permanencia_eess_horas', 'Permanencia en el EE.SS.: horas', 'NUMERO', 7, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'ubicacion_fallecimiento_mm', 'Departamento, provincia y distrito del fallecimiento', 'TEXTO', 8, 0, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (23, 'Referencia y causas de defunción (Anexo 1)', 3);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'referida_mm_anexo1', '¿Referida?', 'BOOLEANO', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'eess_origen_referencia_mm', 'EE.SS. de origen (departamento, provincia, distrito)', 'TEXTO', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'causas_probables_mm', 'Causa final, intermedia y básica probable (cada una con su código CIE-10)', 'TEXTAREA', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'causa_generica_inicial_mm', 'Causa genérica', 'SELECT', 4, 0, 0, 'CASO_INDICE', @cat_causa_generica_mm),
  (@sec, 'clasificacion_inicial_mm', 'Clasificación inicial', 'SELECT', 5, 0, 0, 'CASO_INDICE', @cat_clasificacion_inicial_mm);

-- ---------------------------------------------------------------------------
-- Anexo 2 — Investigación epidemiológica
-- ---------------------------------------------------------------------------
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (23, 'Anexo 2 — Datos básicos y antecedentes', 4);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'grupo_etnico_mm', 'Grupo étnico', 'TEXTO', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'etnia_mm', 'Etnia', 'TEXTO', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'idioma_mm', 'Idioma', 'SELECT', 3, 0, 0, 'CASO_INDICE', @cat_idioma),
  (@sec, 'nivel_educativo_mm', 'Nivel educativo', 'SELECT', 4, 0, 0, 'CASO_INDICE', @cat_nivel_educativo_mm),
  (@sec, 'estado_civil_mm', 'Estado civil', 'SELECT', 5, 0, 0, 'CASO_INDICE', @cat_estado_civil_mm),
  (@sec, 'ocupacion_mm', 'Ocupación', 'TEXTO', 6, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'tipo_seguro_mm', 'Tipo de seguro', 'SELECT', 7, 0, 0, 'CASO_INDICE', @cat_tipo_seguro_mm),
  (@sec, 'antecedentes_patologicos_mm', 'Antecedentes patológicos', 'MULTISELECT', 8, 0, 1, 'CASO_INDICE', @cat_antecedentes_patologicos_mm);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (23, 'Antecedentes gineco-obstétricos y atención prenatal', 5);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'num_gestaciones_previas', 'N.° de gestaciones previas', 'NUMERO', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'num_partos_mm', 'N.° de partos', 'NUMERO', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'num_cesareas_mm', 'N.° de cesáreas', 'NUMERO', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'num_abortos_mm', 'N.° de abortos', 'NUMERO', 4, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'num_nacidos_vivos_mm', 'N.° de nacidos vivos', 'NUMERO', 5, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'num_nacidos_muertos_mm', 'N.° de nacidos muertos', 'NUMERO', 6, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'num_hijos_viven_mm', 'N.° de hijos que viven', 'NUMERO', 7, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'periodo_intergenesico_mm', 'Período intergenésico (años/meses)', 'TEXTO', 8, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'metodo_anticonceptivo_previo', 'Uso de método anticonceptivo previo', 'SELECT', 9, 0, 0, 'CASO_INDICE', @cat_metodo_anticonceptivo),
  (@sec, 'recibio_apn_mm', '¿Recibió APN?', 'BOOLEANO', 10, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'primera_atencion_trimestre_mm', 'Primera atención (trimestre)', 'SELECT', 11, 0, 0, 'CASO_INDICE', @cat_trimestre_apn),
  (@sec, 'numero_apn_mm', 'Número de APN', 'NUMERO', 12, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'eess_mayor_atenciones_mm', 'EE.SS. con mayor cantidad de atenciones (+ categoría)', 'TEXTO', 13, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'visitas_domiciliarias_mm', '¿Se realizaron visitas domiciliarias? (+ número)', 'TEXTO', 14, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'plan_parto_completo_mm', '¿Se realizó plan de parto completo?', 'BOOLEANO', 15, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'responsable_apn_mm', 'Responsable de la APN', 'SELECT', 16, 0, 0, 'CASO_INDICE', @cat_responsable_atencion);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (23, 'Complicaciones por etapa', 6);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'complicaciones_embarazo_mm', 'Complicaciones — Embarazo', 'MULTISELECT', 1, 0, 0, 'CASO_INDICE', @cat_complicaciones_embarazo),
  (@sec, 'complicaciones_parto_mm', 'Complicaciones — Parto', 'MULTISELECT', 2, 0, 0, 'CASO_INDICE', @cat_complicaciones_parto),
  (@sec, 'complicaciones_puerperio_mm', 'Complicaciones — Puerperio', 'MULTISELECT', 3, 0, 0, 'CASO_INDICE', @cat_complicaciones_puerperio);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (23, 'Referencia, hospitalizaciones y parto o aborto', 7);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'referida_mm_anexo2', '¿Referida? (+ N.° de referencias, EE.SS. de origen)', 'TEXTO', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fechas_ingreso_egreso_origen', 'Fechas y horas de ingreso y egreso del EE.SS. de origen', 'TEXTAREA', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'tiempo_demora_destino', 'Tiempo de demora en llegar al EE.SS. de destino (días/horas)', 'TEXTO', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'destino_mm', 'Institución y EE.SS. de destino (+ fecha y hora de ingreso)', 'TEXTO', 4, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'hospitalizaciones_gestacion_mm', '¿Hospitalizaciones en la gestación/puerperio? (+ cuántas)', 'TEXTO', 5, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'requirio_transfusion_mm', '¿Requirió transfusión de sangre?', 'BOOLEANO', 6, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'requirio_expansores_mm', '¿Expansores plasmáticos?', 'BOOLEANO', 7, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_parto_aborto_mm', 'Fecha del parto o aborto', 'FECHA', 8, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'lugar_parto_aborto_mm', 'Lugar', 'SELECT', 9, 0, 0, 'CASO_INDICE', @cat_lugar_parto_aborto_mm),
  (@sec, 'tipo_parto_mm', 'Tipo de parto', 'SELECT', 10, 0, 0, 'CASO_INDICE', @cat_tipo_parto_mm),
  (@sec, 'responsable_parto_aborto_mm', 'Responsable de la atención del parto o aborto', 'SELECT', 11, 0, 0, 'CASO_INDICE', @cat_responsable_atencion),
  (@sec, 'necropsia_mm', '¿Necropsia? (+ diagnóstico / causa CIE-10)', 'TEXTO', 12, 0, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (23, 'Entorno social y datos comunitarios', 8);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'identificaron_signos_peligro', '¿Identificaron signos de peligro?', 'BOOLEANO', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'quien_identifico_peligro', 'Persona que los identificó', 'SELECT', 2, 0, 0, 'CASO_INDICE', @cat_persona_identifico),
  (@sec, 'buscaron_ayuda_mm', '¿Buscaron ayuda?', 'BOOLEANO', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'quien_decidio_ayuda', 'Quién tomó la decisión', 'SELECT', 4, 0, 0, 'CASO_INDICE', @cat_persona_identifico),
  (@sec, 'tiempo_buscar_ayuda_mm', 'Tiempo que demoró en buscar ayuda desde el inicio de sus molestias', 'TEXTO', 5, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'dificultad_acceso_servicios', '¿Hubo dificultad con el acceso a servicios de salud?', 'BOOLEANO', 6, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'especificar_dificultad_acceso', 'Especificar', 'MULTISELECT', 7, 0, 0, 'CASO_INDICE', @cat_dificultad_acceso),
  (@sec, 'tiempo_hasta_eess_mm', 'Tiempo desde el inicio de las molestias hasta llegar al EE.SS.', 'TEXTO', 8, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'dificultad_atencion_eess', '¿Tuvo dificultades para ser atendida en el EE.SS.?', 'BOOLEANO', 9, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'especificar_dificultad_atencion', 'Especificar', 'MULTISELECT', 10, 0, 0, 'CASO_INDICE', @cat_dificultad_atencion),
  (@sec, 'tiempo_atencion_eess_mm', 'Tiempo desde que llegó al EE.SS. hasta que fue atendida', 'TEXTO', 11, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'persona_informacion_mm', 'Persona que brindó la información y su relación con la fallecida', 'SELECT', 12, 0, 0, 'CASO_INDICE', @cat_persona_informacion),
  (@sec, 'sintomatologia_comunitaria_mm', 'Sintomatología o molestias (solo muerte extrainstitucional)', 'MULTISELECT', 13, 0, 0, 'CASO_INDICE', @cat_sintomatologia_comunitaria),
  (@sec, 'maniobras_parto_mm', 'Maniobras usadas durante el parto', 'MULTISELECT', 14, 0, 0, 'CASO_INDICE', @cat_maniobras),
  (@sec, 'maniobras_placenta_mm', 'Maniobras usadas para retirar la placenta', 'MULTISELECT', 15, 0, 0, 'CASO_INDICE', @cat_maniobras),
  (@sec, 'tiempo_domicilio_eess_mm', 'Tiempo estimado del domicilio al EE.SS. más cercano', 'TEXTO', 16, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'tipo_eess_cercano_mm', 'Tipo de establecimiento más cercano', 'SELECT', 17, 0, 0, 'CASO_INDICE', @cat_tipo_eess_cercano);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (23, 'Causas de defunción (CPMMyP) y las cuatro demoras', 9);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'causas_confirmadas_mm', 'Causa final, intermedia, básica y asociada (+ CIE-10)', 'TEXTAREA', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'causa_generica_final_mm', 'Causa genérica', 'SELECT', 2, 0, 0, 'CASO_INDICE', @cat_causa_generica_mm),
  (@sec, 'clasificacion_final_mm', 'Clasificación final', 'SELECT', 3, 1, 0, 'CASO_INDICE', @cat_clasificacion_final_mm),
  (@sec, 'cuatro_demoras_mm', 'Las cuatro demoras', 'GRUPO_SI_NO', 4, 0, 0, 'CASO_INDICE', @cat_cuatro_demoras);

-- ============================================================================
-- 8.2 Muerte fetal y neonatal (id=24)
-- Un caso por fallecimiento (no una hoja con varias filas). Incluye rol
-- MADRE para la residencia habitual: se corrige multi_sujeto (el stub
-- anterior lo había dejado en 0).
-- ============================================================================
UPDATE enfermedad SET multi_sujeto = 1, roles_sujeto = 'CASO_INDICE,MADRE' WHERE id = 24;

INSERT INTO catalogo (nombre) VALUES ('Tipo de muerte - fetal/neonatal');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'FETAL', 'Fetal', 1), (@cat, 'NEONATAL', 'Neonatal', 2);
SET @cat_tipo_muerte_ffn = @cat;

INSERT INTO catalogo (nombre) VALUES ('Lugar del parto - fetal/neonatal');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'PI', 'PI - Parto institucional', 1), (@cat, 'PD', 'PD - Parto domiciliario', 2);
SET @cat_lugar_parto_ffn = @cat;

INSERT INTO catalogo (nombre) VALUES ('Momento de ocurrencia del fallecimiento - fetal/neonatal');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ANTEPARTO', 'Anteparto', 1), (@cat, 'INTRAPARTO', 'Intraparto', 2), (@cat, 'POSTPARTO', 'Post-parto', 3);
SET @cat_momento_ocurrencia_ffn = @cat;

INSERT INTO catalogo (nombre) VALUES ('Lugar de la muerte - fetal/neonatal');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ES', 'ES - Establecimiento de salud', 1), (@cat, 'CC', 'CC - Comunidad', 2);
SET @cat_lugar_muerte_ffn = @cat;

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (24, 'Datos del fallecido', 1);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'apellidos_nombres_fallecido_ffn', 'Apellidos y nombres', 'TEXTO', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'sexo_fallecido_ffn', 'Sexo', 'SELECT', 2, 1, 0, 'CASO_INDICE', 1),
  (@sec, 'edad_gestacional_ffn', 'Edad gestacional (semanas)', 'NUMERO', 3, 1, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_nacimiento_ffn', 'Fecha de nacimiento', 'FECHA', 4, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'hora_nacimiento_ffn', 'Hora de nacimiento', 'TEXTO', 5, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_muerte_ffn', 'Fecha de muerte', 'FECHA', 6, 1, 0, 'CASO_INDICE', NULL),
  (@sec, 'hora_muerte_ffn', 'Hora de muerte', 'TEXTO', 7, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'peso_nacer_ffn', 'Peso al nacer (gramos)', 'NUMERO', 8, 1, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (24, 'Tipo de muerte, causa y circunstancias', 2);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'tipo_muerte_ffn', 'Tipo de muerte', 'SELECT', 1, 1, 0, 'CASO_INDICE', @cat_tipo_muerte_ffn),
  (@sec, 'causa_basica_muerte_ffn', 'Causa básica de muerte', 'TEXTO', 2, 1, 0, 'CASO_INDICE', NULL),
  (@sec, 'dx_cie10_ffn', 'Diagnóstico CIE-10', 'TEXTO', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'dias_estancia_hospitalaria_ffn', 'N.° de días de estancia hospitalaria (solo muerte neonatal)', 'NUMERO', 4, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'lugar_parto_ffn', 'Lugar del parto', 'SELECT', 5, 0, 0, 'CASO_INDICE', @cat_lugar_parto_ffn),
  (@sec, 'momento_ocurrencia_ffn', 'Momento de ocurrencia del fallecimiento', 'SELECT', 6, 0, 0, 'CASO_INDICE', @cat_momento_ocurrencia_ffn),
  (@sec, 'lugar_muerte_ffn', 'Lugar de la muerte', 'SELECT', 7, 0, 0, 'CASO_INDICE', @cat_lugar_muerte_ffn);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (24, 'Residencia habitual de la madre', 3);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'departamento_madre_ffn', 'Departamento', 'TEXTO', 1, 0, 0, 'MADRE', NULL),
  (@sec, 'provincia_madre_ffn', 'Provincia', 'TEXTO', 2, 0, 0, 'MADRE', NULL),
  (@sec, 'distrito_madre_ffn', 'Distrito', 'TEXTO', 3, 0, 0, 'MADRE', NULL);
