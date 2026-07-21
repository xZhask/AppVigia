-- 25_lote6_esavi.sql
-- LOTE 6 de DEFINICION_FICHAS_B_C_D.md: ESAVI severo (id=7).
-- El anexo 6.2 (lista de chequeo del vacunatorio) se deja fuera: el propio
-- documento autoriza cargarlo en una sesión aparte ("la ficha ESAVI
-- funciona sin él"). Sin casos creados: DELETE en cascada seguro.

DELETE FROM seccion_def WHERE enfermedad_id = 7;
UPDATE enfermedad SET usa_vacunas = 1 WHERE id = 7;

INSERT INTO catalogo (nombre) VALUES ('Tipo de ESAVI');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SEVERO', 'Severo', 1), (@cat, 'CONGLOMERADO', 'Conglomerado (leve-moderado)', 2);
SET @cat_tipo_esavi = @cat;

INSERT INTO catalogo (nombre) VALUES ('Tipo de localidad');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'URBANO', 'Urbano', 1), (@cat, 'PERIURBANO', 'Periurbano', 2), (@cat, 'RURAL', 'Rural', 3);
SET @cat_tipo_localidad = @cat;

INSERT INTO catalogo (nombre) VALUES ('Aseguramiento');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SI', 'Sí', 1), (@cat, 'NO', 'No', 2), (@cat, 'SIS', 'SIS', 3), (@cat, 'ESSALUD', 'EsSalud', 4), (@cat, 'PRIVADO', 'Privado', 5);
SET @cat_asegurado = @cat;

INSERT INTO catalogo (nombre) VALUES ('Ocupación - ESAVI');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SIN_OCUPACION', 'Sin ocupación', 1), (@cat, 'ESTUDIANTE', 'Estudiante', 2),
  (@cat, 'COMERCIANTE', 'Comerciante', 3), (@cat, 'EMPLEADO', 'Empleado', 4),
  (@cat, 'PERSONAL_SALUD', 'Personal de salud', 5), (@cat, 'OTRO', 'Otro', 6);
SET @cat_ocupacion_esavi = @cat;

INSERT INTO catalogo (nombre) VALUES ('Vía de administración - vacuna');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ORAL', 'Oral', 1), (@cat, 'INTRADERMICA', 'Intradérmica', 2),
  (@cat, 'SUBCUTANEA', 'Subcutánea', 3), (@cat, 'INTRAMUSCULAR', 'Intramuscular', 4);
SET @cat_via_vacuna = @cat;

INSERT INTO catalogo (nombre) VALUES ('Sitio de vacunación');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'HOMBRO_D', 'Hombro derecho', 1), (@cat, 'HOMBRO_I', 'Hombro izquierdo', 2),
  (@cat, 'BRAZO_D', 'Brazo derecho', 3), (@cat, 'BRAZO_I', 'Brazo izquierdo', 4),
  (@cat, 'VASTO_D', 'Vasto externo de muslo derecho', 5), (@cat, 'VASTO_I', 'Vasto externo de muslo izquierdo', 6),
  (@cat, 'ORAL', 'Oral', 7);
SET @cat_sitio_vacuna = @cat;

INSERT INTO catalogo (nombre) VALUES ('ESAVI previo - cuál');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CONVULSION', 'Convulsión', 1), (@cat, 'RUSH', 'Rush', 2),
  (@cat, 'PERDIDA_CONOCIMIENTO', 'Pérdida de conocimiento', 3), (@cat, 'OTRA', 'Otra', 4);
SET @cat_esavi_previo_cual = @cat;

INSERT INTO catalogo (nombre) VALUES ('Comorbilidad personal - ESAVI');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ALERGIA', 'Alergia', 1), (@cat, 'CONVULSION', 'Convulsión', 2), (@cat, 'ASMA', 'Asma', 3),
  (@cat, 'DIABETES', 'Diabetes', 4), (@cat, 'OBESIDAD', 'Obesidad', 5), (@cat, 'HTA', 'HTA', 6),
  (@cat, 'ENF_RENAL', 'Enf. renal', 7), (@cat, 'DANO_HEPATICO', 'Daño hepático', 8), (@cat, 'CANCER', 'Cáncer', 9),
  (@cat, 'ENF_PULMONAR', 'Enf. pulmonar', 10), (@cat, 'ENF_REUMATOLOGICA', 'Enf. reumatológica', 11),
  (@cat, 'ENF_CARDIOVASCULAR', 'Enf. cardiovascular', 12), (@cat, 'ENF_NEUROLOGICA', 'Enf. neurológica o neuromuscular', 13),
  (@cat, 'INMUNODEFICIENCIA', 'Inmunodeficiencia (incluye VIH)', 14), (@cat, 'OTRA', 'Otra', 15);
SET @cat_comorbilidad_personal = @cat;

INSERT INTO catalogo (nombre) VALUES ('Antecedentes familiares - ESAVI');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ALERGIA', 'Alergia', 1), (@cat, 'ASMA', 'Asma', 2), (@cat, 'URTICARIA', 'Urticaria', 3),
  (@cat, 'EPILEPSIA', 'Epilepsia', 4), (@cat, 'DIABETES', 'Diabetes', 5), (@cat, 'OBESIDAD', 'Obesidad', 6),
  (@cat, 'CANCER', 'Cáncer', 7), (@cat, 'CONVULSION_FEBRIL', 'Convulsión febril en la infancia', 8),
  (@cat, 'COVID19', 'COVID-19', 9), (@cat, 'TBC', 'TBC', 10), (@cat, 'HTA', 'HTA', 11),
  (@cat, 'ENF_CARDIOVASCULAR', 'Enf. cardiovascular', 12), (@cat, 'ENF_PULMONAR', 'Enf. pulmonar', 13),
  (@cat, 'ENF_REUMATOLOGICA', 'Enf. reumatológica', 14), (@cat, 'ENF_RENAL', 'Enf. renal', 15),
  (@cat, 'INMUNODEFICIENCIA', 'Inmunodeficiencia (incluye VIH)', 16), (@cat, 'OTRA', 'Otra', 17);
SET @cat_antecedentes_familiares = @cat;

INSERT INTO catalogo (nombre) VALUES ('Enfermedades prevalentes en la región');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'DENGUE', 'Dengue', 1), (@cat, 'MALARIA', 'Malaria', 2), (@cat, 'ZIKA', 'Zika', 3),
  (@cat, 'LEPTOSPIROSIS', 'Leptospirosis', 4), (@cat, 'BARTONELOSIS', 'Bartonelosis', 5),
  (@cat, 'RABIA', 'Rabia', 6), (@cat, 'OTRA', 'Otra', 7);
SET @cat_enf_prevalentes = @cat;

INSERT INTO catalogo (nombre) VALUES ('Eventos ESAVI severos');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ABSCESO', 'Absceso en el sitio de inyección', 1),
  (@cat, 'LINFADENITIS', 'Linfadenitis supurativa', 2),
  (@cat, 'REACCION_LOCAL_SEVERA', 'Reacción local severa', 3),
  (@cat, 'LLANTO_PERSISTENTE', 'Llanto persistente (>3 horas)', 4),
  (@cat, 'CONVULSIONES', 'Convulsiones', 5),
  (@cat, 'SINDROME_HIPOTONICO', 'Síndrome hipotónico-hiporreactivo', 6),
  (@cat, 'REACCION_ALERGICA', 'Reacción alérgica', 7),
  (@cat, 'PURPURA_TROMBOCITOPENICA', 'Púrpura trombocitopénica', 8),
  (@cat, 'SINCOPE', 'Síncope o reacción vasovagal', 9),
  (@cat, 'PFA', 'Parálisis flácida aguda', 10),
  (@cat, 'ENCEFALOPATIAS', 'Encefalopatías', 11),
  (@cat, 'ENCEFALITIS', 'Encefalitis', 12),
  (@cat, 'MENINGITIS', 'Meningitis', 13),
  (@cat, 'OSTEITIS', 'Osteítis / osteomielitis', 14),
  (@cat, 'ARTRALGIA', 'Artralgia', 15),
  (@cat, 'SEPSIS', 'Sepsis', 16),
  (@cat, 'SHOCK_TOXICO', 'Síndrome de shock tóxico', 17),
  (@cat, 'OTROS_EVENTOS', 'Otros eventos severos e inusuales', 18);
SET @cat_eventos_esavi = @cat;

INSERT INTO catalogo (nombre) VALUES ('Estado de alta - ESAVI');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MEJORADO', 'Mejorado', 1), (@cat, 'SECUELA', 'Secuela', 2), (@cat, 'FALLECIDO', 'Fallecido', 3);
SET @cat_estado_alta_esavi = @cat;

INSERT INTO catalogo (nombre) VALUES ('Seguimiento del paciente - ESAVI');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'NO_UBICABLE', 'Caso no ubicable', 1), (@cat, 'REHABILITACION', 'En rehabilitación', 2),
  (@cat, 'CONTROL_MEDICO', 'Requiere solo control médico', 3), (@cat, 'TX_QUIRURGICO', 'Requiere tratamiento quirúrgico', 4),
  (@cat, 'CONTROL_Y_QUIRURGICO', 'Requiere control médico y tratamiento quirúrgico', 5),
  (@cat, 'RECUPERADO_SIN_SECUELA', 'Recuperado sin secuela', 6), (@cat, 'RECUPERADO_CON_SECUELA', 'Recuperación con secuela', 7),
  (@cat, 'OTRO', 'Otro estudio final', 8);
SET @cat_seguimiento_esavi = @cat;

INSERT INTO catalogo (nombre) VALUES ('Clasificación final - ESAVI');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'RELACIONADA_VACUNA', 'Reacción relacionada a la vacuna', 1),
  (@cat, 'DEFECTO_CALIDAD', 'Reacción relacionada con un defecto en la calidad de la vacuna', 2),
  (@cat, 'ERROR_INMUNIZACION', 'Reacción relacionada con un error en la inmunización', 3),
  (@cat, 'ANSIEDAD_INMUNIZACION', 'Reacción relacionada con la ansiedad por la inmunización', 4),
  (@cat, 'COINCIDENTE', 'Eventos coincidentes', 5),
  (@cat, 'NO_CONCLUYENTE', 'Evento no concluyente', 6);
SET @cat_clasificacion_final_esavi = @cat;

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (7, 'Notificación', 1);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'tipo_esavi', 'Tipo', 'SELECT', 1, 1, 0, 'CASO_INDICE', @cat_tipo_esavi),
  (@sec, 'fecha_identificacion_local', 'Fecha de identificación local del caso (o consulta)', 'FECHA', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_notif_diresa_cdc', 'Fecha de notificación de DIRESA/GERESA/DIRIS a CDC/MINSA', 'FECHA', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_inicio_investigacion_esavi', 'Fecha de inicio de investigación', 'FECHA', 4, 0, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (7, 'Datos del paciente (adicionales)', 2);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'pueblo_etnico', 'Pueblo étnico', 'TEXTO', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'etnia_esavi', 'Etnia', 'TEXTO', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'gestante_esavi', 'Gestante', 'BOOLEANO', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'semanas_gestacion_esavi', 'N.° de semanas de gestación', 'NUMERO', 4, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'tipo_localidad_esavi', 'Tipo de localidad', 'SELECT', 5, 0, 0, 'CASO_INDICE', @cat_tipo_localidad),
  (@sec, 'asegurado_esavi', '¿Está asegurado?', 'SELECT', 6, 0, 0, 'CASO_INDICE', @cat_asegurado),
  (@sec, 'ocupacion_esavi', 'Ocupación', 'SELECT', 7, 0, 0, 'CASO_INDICE', @cat_ocupacion_esavi);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (7, 'Datos de la vacunación', 3);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'fecha_hora_vacunacion', 'Fecha y hora de vacunación', 'FECHA', 1, 1, 0, 'CASO_INDICE', NULL),
  (@sec, 'eess_vacuno', 'EE.SS. que vacunó', 'TEXTO', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'via_vacunacion', 'Vía', 'SELECT', 3, 0, 0, 'CASO_INDICE', @cat_via_vacuna),
  (@sec, 'sitio_vacunacion', 'Sitio', 'SELECT', 4, 0, 0, 'CASO_INDICE', @cat_sitio_vacuna),
  (@sec, 'fabricante_vacuna', 'Fabricante', 'TEXTO', 5, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'lote_vacuna', 'Lote', 'TEXTO', 6, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_expiracion_vacuna', 'Fecha de expiración', 'FECHA', 7, 0, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (7, 'Antecedentes', 4);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'esavi_previo', '¿ESAVI previo?', 'BOOLEANO', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'esavi_previo_cual', 'ESAVI previo: cuál', 'MULTISELECT', 2, 0, 0, 'CASO_INDICE', @cat_esavi_previo_cual),
  (@sec, 'comorbilidad_personal', 'Condiciones de comorbilidad', 'MULTISELECT', 3, 0, 0, 'CASO_INDICE', @cat_comorbilidad_personal),
  (@sec, 'antecedentes_familiares_esavi', 'Antecedentes familiares — cuadros patológicos', 'MULTISELECT', 4, 0, 0, 'CASO_INDICE', @cat_antecedentes_familiares),
  (@sec, 'enfermedades_prevalentes_region', 'Enfermedades prevalentes en la región', 'MULTISELECT', 5, 0, 0, 'CASO_INDICE', @cat_enf_prevalentes);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (7, 'Signos y síntomas', 5);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'eventos_esavi', 'Eventos', 'GRUPO_SI_NO', 1, 0, 0, 'CASO_INDICE', @cat_eventos_esavi);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (7, 'Descripción del cuadro clínico', 6);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'fecha_inicio_cuadro_esavi', 'Fecha de inicio', 'FECHA', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'gravedad_caso_esavi', 'Gravedad del caso', 'TEXTO', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'secuencia_cronologica_esavi', 'Secuencia cronológica de instalación de signos y síntomas', 'TEXTAREA', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'examenes_auxiliares_esavi', 'Exámenes auxiliares', 'TEXTAREA', 4, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'tratamiento_recibido_esavi', 'Tratamiento recibido', 'TEXTAREA', 5, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'evolucion_esavi', 'Evolución', 'TEXTAREA', 6, 0, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (7, 'Hospitalización', 7);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'num_hc_esavi', 'N.° de historia clínica', 'TEXTO', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_ingreso_esavi', 'Fecha de ingreso', 'FECHA', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_alta_esavi', 'Fecha de alta', 'FECHA', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'dx_ingreso_esavi', 'Dx de ingreso', 'TEXTO', 4, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'dx_egreso_esavi', 'Dx de egreso', 'TEXTO', 5, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'estado_alta_esavi', 'Estado de alta', 'SELECT', 6, 0, 0, 'CASO_INDICE', @cat_estado_alta_esavi),
  (@sec, 'transferido_esavi', '¿Transferido?', 'BOOLEANO', 7, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'transferido_a_donde_esavi', '¿A dónde?', 'TEXTO', 8, 0, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (7, 'Seguimiento y clasificación final', 8);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'seguimiento_paciente_esavi', 'Seguimiento del paciente', 'SELECT', 1, 0, 0, 'CASO_INDICE', @cat_seguimiento_esavi),
  (@sec, 'clasificacion_final_esavi', 'Clasificación final', 'SELECT', 2, 1, 0, 'CASO_INDICE', @cat_clasificacion_final_esavi);
