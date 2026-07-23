-- 22_lote4_sarampion.sql
-- LOTE 4 de DEFINICION_FICHAS.md: Sarampión / rubéola / otras febriles eruptivas (B05).
-- Reemplaza los 7 campos placeholder BOOLEANO (sección "Cuadro clínico" original)
-- por la definición completa. Sin fichas creadas aún para esta enfermedad
-- (enfermedad_id=2), por lo que el DELETE en cascada no pierde datos reales.

DELETE FROM seccion_def WHERE enfermedad_id = 2;

UPDATE enfermedad
SET usa_contactos = 1, usa_muestras = 1, usa_viajes = 1, usa_vacunas = 1,
    multi_sujeto = 0, roles_sujeto = 'CASO_INDICE'
WHERE id = 2;

-- Catálogos para los dos GRUPO_SI_NO del cuadro clínico
INSERT INTO catalogo (nombre) VALUES ('Signos - Sarampión/Rubéola');
SET @cat_signos = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_signos, 'TOS', 'Tos', 1),
  (@cat_signos, 'CORIZA', 'Coriza o rinorrea', 2),
  (@cat_signos, 'CONJUNTIVITIS', 'Conjuntivitis', 3),
  (@cat_signos, 'KOPLIK', 'Manchas de Koplik', 4),
  (@cat_signos, 'ADENOPATIA_CERVICAL', 'Adenopatía cervical', 5),
  (@cat_signos, 'ADENOPATIA_RETROAURICULAR', 'Adenopatía retroauricular', 6),
  (@cat_signos, 'ARTRALGIAS', 'Artralgias', 7),
  (@cat_signos, 'OTROS', 'Otros', 8);

INSERT INTO catalogo (nombre) VALUES ('Complicaciones - Sarampión/Rubéola');
SET @cat_complicaciones = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_complicaciones, 'OTITIS_MEDIA', 'Otitis media', 1),
  (@cat_complicaciones, 'CONVULSIONES', 'Convulsiones', 2),
  (@cat_complicaciones, 'NEUMONIA', 'Neumonía', 3),
  (@cat_complicaciones, 'TROMBOCITOPENIA', 'Trombocitopenia', 4),
  (@cat_complicaciones, 'DIARREA', 'Diarrea', 5),
  (@cat_complicaciones, 'ENCEFALITIS', 'Encefalitis', 6),
  (@cat_complicaciones, 'OTRAS', 'Otras', 7);

-- Catálogos para los SELECT / MULTISELECT del resto de la ficha
INSERT INTO catalogo (nombre) VALUES ('Estado general - Sarampión/Rubéola');
SET @cat_estado_general = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_estado_general, 'BUENO', 'Bueno', 1),
  (@cat_estado_general, 'REGULAR', 'Regular', 2),
  (@cat_estado_general, 'MALO', 'Malo', 3);

INSERT INTO catalogo (nombre) VALUES ('Estado vacunal - Sarampión/Rubéola');
SET @cat_estado_vacunal = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_estado_vacunal, 'VACUNADO', 'Vacunado', 1),
  (@cat_estado_vacunal, 'VACUNADO_INCOMPLETO', 'Vacunado incompleto', 2),
  (@cat_estado_vacunal, 'NO_VACUNADO', 'No vacunado', 3),
  (@cat_estado_vacunal, 'IGNORADO', 'Ignorado', 4),
  (@cat_estado_vacunal, 'NO_CORRESPONDE', 'No corresponde', 5),
  (@cat_estado_vacunal, 'SIN_EVIDENCIA', 'Sin evidencia', 6);

INSERT INTO catalogo (nombre) VALUES ('Captación del caso - Sarampión/Rubéola');
SET @cat_captacion = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_captacion, 'CONSULTA', 'Consulta', 1),
  (@cat_captacion, 'LABORATORIO', 'Laboratorio', 2),
  (@cat_captacion, 'BA_INSTITUCIONAL', 'Búsqueda activa institucional', 3),
  (@cat_captacion, 'BA_COMUNITARIA', 'Búsqueda activa comunitaria', 4),
  (@cat_captacion, 'INVESTIGACION_CONTACTOS', 'Investigación de contactos', 5),
  (@cat_captacion, 'CASOS_REPORTADOS_COMUNIDAD', 'Casos reportados en comunidad', 6),
  (@cat_captacion, 'OTROS', 'Otros', 7);

INSERT INTO catalogo (nombre) VALUES ('Contacto con - Sarampión/Rubéola');
SET @cat_contacto_lugares = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_contacto_lugares, 'EXTRANJEROS', 'Extranjeros', 1),
  (@cat_contacto_lugares, 'VISITO_EESS', 'Visitó establecimiento de salud', 2),
  (@cat_contacto_lugares, 'RECIBIO_VISITAS', 'Recibió visitas en casa', 3),
  (@cat_contacto_lugares, 'CELEBRACIONES_MASIVAS', 'Asistió a celebraciones masivas', 4),
  (@cat_contacto_lugares, 'OTROS', 'Otros', 5);

INSERT INTO catalogo (nombre) VALUES ('Clasificación final - Sarampión/Rubéola');
SET @cat_clasificacion = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_clasificacion, 'SARAMPION', 'Sarampión', 1),
  (@cat_clasificacion, 'RUBEOLA', 'Rubéola', 2),
  (@cat_clasificacion, 'DESCARTADO', 'Descartado', 3);

INSERT INTO catalogo (nombre) VALUES ('Criterio de confirmación - Sarampión/Rubéola');
SET @cat_criterio_confirmacion = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_criterio_confirmacion, 'IGM_INDIRECTA', 'IgM indirecta (+)', 1),
  (@cat_criterio_confirmacion, 'SEROCONVERSION_IGG', 'Seroconversión de IgG indirecta', 2),
  (@cat_criterio_confirmacion, 'PCR', 'PCR (+)', 3);

INSERT INTO catalogo (nombre) VALUES ('Criterio de descarte - Sarampión/Rubéola');
SET @cat_criterio_descarte = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_criterio_descarte, 'IGM_NEGATIVO', 'Sarampión/rubéola IgM negativo', 1),
  (@cat_criterio_descarte, 'REACCION_VACUNAL', 'Reacción vacunal', 2),
  (@cat_criterio_descarte, 'DENGUE', 'Dengue', 3),
  (@cat_criterio_descarte, 'PARVOVIRUS_B19', 'Parvovirus B19', 4),
  (@cat_criterio_descarte, 'HERPES_6', 'Herpes 6', 5),
  (@cat_criterio_descarte, 'REACCION_ALERGICA', 'Reacción alérgica', 6),
  (@cat_criterio_descarte, 'ZIKA', 'Zika', 7),
  (@cat_criterio_descarte, 'OTROS', 'Otros', 8);

INSERT INTO catalogo (nombre) VALUES ('Clasificación según fuente de infección - Sarampión/Rubéola');
SET @cat_fuente_infeccion = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_fuente_infeccion, 'IMPORTADO', 'Importado', 1),
  (@cat_fuente_infeccion, 'RELACIONADO_IMPORTACION', 'Relacionado a importación', 2),
  (@cat_fuente_infeccion, 'FUENTE_DESCONOCIDA', 'Fuente desconocida', 3),
  (@cat_fuente_infeccion, 'LOCAL_AUTOCTONO', 'Local o autóctono', 4);

-- 1) Cuadro clínico
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (2, 'Cuadro clínico', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id, config) VALUES
  (@sec_id, 'fecha_inicio_fiebre', 'Fecha de inicio de fiebre', 'FECHA', 1, 1, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'fiebre_cuantificada', '¿Fiebre cuantificada?', 'BOOLEANO', 2, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'duracion_fiebre_dias', 'N.° de días de duración de la fiebre', 'NUMERO', 3, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'temperatura_c', 'Temperatura (°C)', 'NUMERO', 4, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'erupcion_cutanea', '¿Erupción cutánea?', 'BOOLEANO', 5, 1, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'fecha_inicio_erupcion', 'Fecha de inicio de erupción', 'FECHA', 6, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'duracion_erupcion_dias', 'N.° de días de duración de la erupción', 'NUMERO', 7, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'estado_general', 'Estado general', 'SELECT', 8, 0, 0, 'CASO_INDICE', @cat_estado_general, NULL),
  (@sec_id, 'signos', 'Signos', 'GRUPO_SI_NO', 9, 0, 0, 'CASO_INDICE', @cat_signos, NULL),
  (@sec_id, 'descripcion_erupcion', 'Descripción de la erupción cutánea', 'TEXTAREA', 10, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'complicaciones', 'Complicaciones', 'GRUPO_SI_NO', 11, 0, 0, 'CASO_INDICE', @cat_complicaciones, NULL),
  (@sec_id, 'hospitalizado', 'Hospitalizado', 'BOOLEANO', 12, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'fecha_hospitalizacion', 'Fecha de hospitalización', 'FECHA', 13, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'establecimiento_hospitalizacion', 'Establecimiento de hospitalización', 'TEXTO', 14, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'num_historia_clinica', 'N.° de historia clínica', 'TEXTO', 15, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'fallecido', 'Fallecido', 'BOOLEANO', 16, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'fecha_defuncion', 'Fecha de defunción', 'FECHA', 17, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'causa_basica_defuncion', 'Causa básica de defunción', 'TEXTO', 18, 0, 0, 'CASO_INDICE', NULL, NULL);

-- 2) Cronología de signos y síntomas (día -10 a +10 respecto a la erupción).
-- El motor implementa CRONOLOGIA como SI_NO_FECHA por síntoma (fallback
-- explícitamente autorizado por DEFINICION_FICHAS.md §1 para no bloquear el
-- lote si la línea de tiempo completa resulta muy costosa).
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (2, 'Cronología de signos y síntomas', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id, config) VALUES
  (@sec_id, 'cron_erupcion', 'Erupción (día 0)', 'CRONOLOGIA', 1, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'cron_fiebre', 'Fiebre', 'CRONOLOGIA', 2, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'cron_tos', 'Tos', 'CRONOLOGIA', 3, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'cron_conjuntivitis', 'Conjuntivitis', 'CRONOLOGIA', 4, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'cron_coriza', 'Coriza o rinorrea', 'CRONOLOGIA', 5, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'cron_manchas_koplik', 'Manchas de Koplik', 'CRONOLOGIA', 6, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'cron_adenopatia_retroauricular', 'Adenopatía retroauricular', 'CRONOLOGIA', 7, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'cron_adenopatia_cervical', 'Adenopatía cervical', 'CRONOLOGIA', 8, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'cron_artralgias', 'Artralgias', 'CRONOLOGIA', 9, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'cron_otros', 'Otros', 'CRONOLOGIA', 10, 0, 0, 'CASO_INDICE', NULL, NULL);

-- 3) Antecedentes vacunales. El detalle por dosis (tipo de vacuna, N.° de
-- dosis, fecha, EE.SS.) se cubre con la tabla hija genérica `caso_vacuna`
-- (usa_vacunas=1); aquí solo el estado vacunal resumen, que es un dato único
-- por ficha y no por dosis.
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (2, 'Antecedentes vacunales', 3);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id, config) VALUES
  (@sec_id, 'estado_vacunal', 'Estado vacunal', 'SELECT', 1, 0, 0, 'CASO_INDICE', @cat_estado_vacunal, NULL);

-- 4) Antecedentes epidemiológicos
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (2, 'Antecedentes epidemiológicos', 4);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id, config) VALUES
  (@sec_id, 'captacion_caso', 'Captación del caso', 'SELECT', 1, 0, 0, 'CASO_INDICE', @cat_captacion, NULL),
  (@sec_id, 'contacto_otro_caso', '¿El caso es contacto de otro caso conocido?', 'BOOLEANO', 2, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'codigo_caso_contacto', 'Código del caso con el que tuvo contacto', 'TEXTO', 3, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'contacto_gestante', '¿Tuvo contacto con gestante en las primeras 20 semanas?', 'BOOLEANO', 4, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'nombre_gestante_contacto', 'Nombre de la gestante', 'TEXTO', 5, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'fecha_contacto_gestante', 'Fecha del contacto con la gestante', 'FECHA', 6, 0, 0, 'CASO_INDICE', NULL, NULL);

-- 5) Lugar probable de infección (7 a 30 días antes de la erupción).
-- El viaje puntual se cubre con `caso_viaje` (usa_viajes=1).
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (2, 'Lugar probable de infección', 5);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id, config) VALUES
  (@sec_id, 'contacto_exposicion_lugares', 'Contacto con', 'MULTISELECT', 1, 0, 0, 'CASO_INDICE', @cat_contacto_lugares, NULL),
  (@sec_id, 'domicilio_lat_long', 'Latitud y longitud del domicilio', 'TEXTO', 2, 0, 0, 'CASO_INDICE', NULL, NULL);

-- 6) Investigación epidemiológica (bloqueo vacunal, MRC, búsqueda activa)
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (2, 'Investigación epidemiológica', 6);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id, config) VALUES
  (@sec_id, 'busqueda_activa_institucional', '¿Búsqueda activa institucional?', 'BOOLEANO', 1, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'ba_institucional_dx_revisados', 'Total de Dx revisados', 'NUMERO', 2, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'ba_institucional_casos_existian', 'Casos que ya existían', 'NUMERO', 3, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'ba_institucional_casos_nuevos', 'Casos nuevos ingresados', 'NUMERO', 4, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'busqueda_activa_comunitaria', '¿Búsqueda activa comunitaria?', 'BOOLEANO', 5, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'ba_comunitaria_casas_abiertas', 'Casas abiertas', 'NUMERO', 6, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'ba_comunitaria_casas_cerradas', 'Casas cerradas', 'NUMERO', 7, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'ba_comunitaria_casas_abandonadas', 'Casas abandonadas', 'NUMERO', 8, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'ba_comunitaria_casas_total', 'Total de casas', 'NUMERO', 9, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'bloqueo_vacunal', '¿Se realizó bloqueo vacunal?', 'BOOLEANO', 10, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'bloqueo_fecha_inicio', 'Fecha de inicio del bloqueo', 'FECHA', 11, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'bloqueo_fecha_termino', 'Fecha de término del bloqueo', 'FECHA', 12, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'bloqueo_localidades', 'Localidades del bloqueo', 'TEXTAREA', 13, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'bloqueo_num_vacunados', 'Número de vacunados en el bloqueo', 'NUMERO', 14, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'mrc_realizado', '¿Se realizó monitoreo rápido de coberturas (MRC)?', 'BOOLEANO', 15, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'mrc_porcentaje_vacunados', 'Porcentaje de vacunados (MRC)', 'NUMERO', 16, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'casos_reportados_30dias', '¿Hubo casos reportados de sarampión en los últimos 30 días en su jurisdicción?', 'BOOLEANO', 17, 0, 0, 'CASO_INDICE', NULL, NULL);

-- 7) Clasificación final
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (2, 'Clasificación final', 7);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id, config) VALUES
  (@sec_id, 'clasificacion_sarampion_rubeola', 'Clasificación', 'SELECT', 1, 1, 0, 'CASO_INDICE', @cat_clasificacion, NULL),
  (@sec_id, 'criterio_confirmacion', 'Criterio de confirmación', 'SELECT', 2, 0, 0, 'CASO_INDICE', @cat_criterio_confirmacion, NULL),
  (@sec_id, 'criterio_descarte', 'Criterio de descarte', 'SELECT', 3, 0, 0, 'CASO_INDICE', @cat_criterio_descarte, NULL),
  (@sec_id, 'clasificacion_fuente_infeccion', 'Clasificación según fuente de infección', 'SELECT', 4, 0, 0, 'CASO_INDICE', @cat_fuente_infeccion, NULL),
  (@sec_id, 'fecha_clasificacion_final', 'Fecha de clasificación final', 'FECHA', 5, 0, 0, 'CASO_INDICE', NULL, NULL),
  (@sec_id, 'clasificado_por', 'Clasificado por', 'TEXTO', 6, 0, 0, 'CASO_INDICE', NULL, NULL);
