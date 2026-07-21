-- 31_fix_difteria.sql
-- AUDITORIA_FICHA_DIFTERIA.md: reconstruye la ficha de difteria completa.
-- Sin casos creados para difteria (verificado): DELETE en cascada seguro.
--
-- Cambios respecto a lo cargado antes:
--  - Elimina la sección "Signos de alarma" (edema cervical, disnea/estridor,
--    miocarditis): no existe en la ficha MINSA, fue inventada al cargar.
--  - Hospitalizado, Egreso del hospital, ¿Contacto con caso?, ¿Sabe de casos
--    similares?, Aislamiento domiciliario: pasan de TEXTO a SELECT con las
--    opciones cerradas de la ficha.
--  - Tratamiento recibido y Complicaciones: pasan de casillas BOOLEANO sueltas
--    a MULTISELECT propios.
--  - Se agregan las dependencias condicionales (punto 4): fecha de defunción
--    solo si egreso=Falleció, fecha de aislamiento solo si aislamiento=Sí,
--    "especificar" solo si el campo padre lo activa.
--  - Clasificación en la captación, lugar probable de infección e
--    "Investigador" ya no van acá: son núcleo (persona/caso), cubiertos por
--    30_motor_dependencias_y_nucleo.sql.
--  - enfermedad.opciones_clasificacion ya se restringió a
--    CONFIRMADO,DESCARTADO en 30_motor_dependencias_y_nucleo.sql.

DELETE FROM seccion_def WHERE enfermedad_id = 5; -- borra 24, 25, 26 y la 7 (Signos de alarma inventada)

INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES (4, 'MEMBRANA', 'Membrana', 100);

-- ============================================================================
-- Sección 1: Signos y síntomas
-- ============================================================================
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (5, 'Signos y síntomas', 1);
SET @sec_id = LAST_INSERT_ID();

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_fecha_inicio', 'Fecha de inicio', 'FECHA', NULL, 1, 1, 0, 'FICHA_MINSA');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_temperatura_c', 'Temperatura °C', 'NUMERO', NULL, 2, 0, 0, 'FICHA_MINSA');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_signos_clinicos', 'Signos clínicos', 'GRUPO_SI_NO', 6, 3, 0, 0, 'FICHA_MINSA');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_presencia_de_placa', 'Presencia de placa (seudomembrana)', 'GRUPO_SI_NO', 7, 4, 0, 0, 'FICHA_MINSA');

-- ============================================================================
-- Sección 2: Evolución
-- ============================================================================
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (5, 'Evolución', 2);
SET @sec_id = LAST_INSERT_ID();

INSERT INTO catalogo (nombre) VALUES ('Hospitalizado - Difteria');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SI', 'Sí', 1), (@cat, 'NO', 'No', 2), (@cat, 'IGNORADO', 'Ignorado', 3);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_hospitalizado', 'Hospitalizado', 'SELECT', @cat, 1, 1, 0, 'FICHA_MINSA');

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_antibiotico_antes_del_ingreso', '¿Antibiótico antes del ingreso?', 'BOOLEANO', NULL, 2, 0, 0, 'FICHA_MINSA');
SET @campo_antibiotico_previo = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, depende_de, valor_activador, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_antibiotico_antes_especificar', 'Antibiótico antes del ingreso (especificar)', 'TEXTO', NULL, @campo_antibiotico_previo, '1', 3, 0, 0, 'FICHA_MINSA');

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_hospital', 'Hospital', 'TEXTO', NULL, 4, 0, 0, 'FICHA_MINSA');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_fecha_de_hospitalizacion', 'Fecha de hospitalización', 'FECHA', NULL, 5, 0, 0, 'FICHA_MINSA');

INSERT INTO catalogo (nombre) VALUES ('Tratamiento recibido - Difteria');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ANTIBIOTICO', 'Antibiótico', 1), (@cat, 'ANTITOXINA', 'Antitoxina', 2);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_tratamiento_recibido', 'Tratamiento recibido', 'MULTISELECT', @cat, 6, 0, 0, 'FICHA_MINSA');
SET @campo_tratamiento = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, depende_de, valor_activador, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_tratamiento_especificar', 'Tratamiento (especificar)', 'TEXTO', NULL, @campo_tratamiento, 'ANTIBIOTICO', 7, 0, 0, 'FICHA_MINSA');

INSERT INTO catalogo (nombre) VALUES ('Egreso del hospital - Difteria');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'RECUPERADO', 'Recuperado', 1), (@cat, 'REFERIDO', 'Referido', 2),
  (@cat, 'FALLECIO', 'Falleció', 3), (@cat, 'CON_SECUELA', 'Con secuela', 4);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_egreso_del_hospital', 'Egreso del hospital', 'SELECT', @cat, 8, 0, 0, 'FICHA_MINSA');
SET @campo_egreso = LAST_INSERT_ID();

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_fecha_de_alta', 'Fecha de alta', 'FECHA', NULL, 9, 0, 0, 'FICHA_MINSA');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, depende_de, valor_activador, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_fecha_de_defuncion', 'Fecha de defunción', 'FECHA', NULL, @campo_egreso, 'FALLECIO', 10, 0, 0, 'FICHA_MINSA');

INSERT INTO catalogo (nombre) VALUES ('Complicaciones - Difteria');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CARDIACAS', 'Cardíacas', 1), (@cat, 'NEUROLOGICAS', 'Neurológicas', 2), (@cat, 'OTRAS', 'Otras', 3);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_complicaciones', 'Complicaciones', 'MULTISELECT', @cat, 11, 0, 0, 'FICHA_MINSA');

-- ============================================================================
-- Sección 3: Información epidemiológica
-- ============================================================================
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (5, 'Información epidemiológica', 3);
SET @sec_id = LAST_INSERT_ID();

INSERT INTO catalogo (nombre) VALUES ('Sí/No/Ignorado - Difteria');
SET @cat_sni = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_sni, 'SI', 'Sí', 1), (@cat_sni, 'NO', 'No', 2), (@cat_sni, 'IGNORADO', 'Ignorado', 3);

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_estuvo_en_contacto', '¿Estuvo en contacto con un posible caso de difteria?', 'SELECT', @cat_sni, 1, 0, 0, 'FICHA_MINSA');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_sabe_casos_similares', '¿Sabe si hay casos similares en la zona?', 'SELECT', @cat_sni, 2, 0, 0, 'FICHA_MINSA');

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_aislamiento_domiciliario', 'Aislamiento domiciliario', 'SELECT', @cat_sni, 3, 0, 0, 'FICHA_MINSA');
SET @campo_aislamiento = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, depende_de, valor_activador, orden, obligatorio, sensible, origen)
VALUES (@sec_id, 'a36_fecha_de_aislamiento', 'Fecha de aislamiento', 'FECHA', NULL, @campo_aislamiento, 'SI', 4, 0, 0, 'FICHA_MINSA');
