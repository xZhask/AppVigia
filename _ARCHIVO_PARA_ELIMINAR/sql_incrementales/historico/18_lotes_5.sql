-- Fichas Lote 5, 6, 7 y 8

INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
         VALUES ('Gestante con VIH y niño expuesto', 'Gestante VIH/Niño', 'vih,gestante,niño expuesto,transmision vertical', 'Z21', 'SEMANAL', 'Otros', 'Materno-perinatal y transmisión vertical', 0, 1, 0, 0, 1, 'MADRE,NINO_EXPUESTO', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Sección I — Gestante con VIH', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Momento de diagnóstico', 'SELECT', 1, 1, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Año de diagnóstico', 'NUMERO', 2, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Prueba de tamizaje N.° 1', 'FECHA', 3, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Prueba de tamizaje N.° 2', 'FECHA', 4, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Prueba confirmatoria', 'FECHA', 5, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'FUR', 'FECHA', 6, 1, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Recibió APN?', 'BOOLEANO', 7, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Embarazo múltiple?', 'BOOLEANO', 8, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Recibió ARV?', 'BOOLEANO', 9, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Fecha de inicio de ARV', 'FECHA', 10, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Abandonó terapia ARV?', 'BOOLEANO', 11, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Recibe terapia triple / TARGA?', 'BOOLEANO', 12, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'N.° de nacidos vivos', 'NUMERO', 13, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'N.° de óbitos fetales', 'NUMERO', 14, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Aborto', 'BOOLEANO', 15, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Parto por cesárea?', 'BOOLEANO', 16, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Fecha del parto', 'FECHA', 17, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Carga viral indetectable?', 'BOOLEANO', 18, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Abandona seguimiento?', 'BOOLEANO', 19, 0, 1, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿La gestante fallece?', 'BOOLEANO', 20, 0, 1, 'MADRE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Sección II — Niño nacido expuesto al VIH', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Código del niño', 'TEXTO', 1, 1, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'DNI del niño', 'TEXTO', 2, 0, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Apellidos y nombres', 'TEXTO', 3, 1, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Sexo', 'SELECT', 4, 1, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Fecha de nacimiento', 'FECHA', 5, 1, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Recibió ARV?', 'BOOLEANO', 6, 0, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Fecha de inicio de ARV', 'FECHA', 7, 0, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Abandonó terapia ARV?', 'BOOLEANO', 8, 0, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'N.° de días que tomó ARV', 'NUMERO', 9, 0, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Profilaxis ARV de acuerdo a NT vigente?', 'BOOLEANO', 10, 0, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Sucedáneos de leche materna?', 'BOOLEANO', 11, 0, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'N.° de meses que los recibió', 'NUMERO', 12, 0, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Tomó leche materna?', 'BOOLEANO', 13, 0, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Estado serológico final', 'SELECT', 14, 0, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Pruebas diagnósticas', 'MATRIZ', 15, 0, 1, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Observaciones', 'TEXTAREA', 16, 0, 1, 'NINO_EXPUESTO');
INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
         VALUES ('VIH / SIDA — notificación individual', 'VIH / SIDA', 'vih,sida,retrovirus', 'B24', 'SEMANAL', 'Otros', 'Materno-perinatal y transmisión vertical', 0, 1, 0, 0, 0, 'CASO_INDICE', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Identificación y Motivo', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Código del paciente', 'TEXTO', 1, 1, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Motivo de notificación', 'SELECT', 2, 1, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Estadio de infección VIH al momento del diagnóstico', 'SELECT', 3, 0, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Grado de instrucción', 'SELECT', 4, 0, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Condición especial', 'MULTISELECT', 5, 0, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Identidad de género', 'SELECT', 6, 0, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Antecedentes de RS', 'SELECT', 7, 0, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Vía de transmisión', 'SELECT', 8, 1, 1, 'CASO_INDICE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'TARGA y estadio SIDA', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Fecha de inicio de tratamiento', 'FECHA', 1, 0, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Criterio diagnóstico de SIDA', 'SELECT', 2, 0, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Enfermedades indicadoras de SIDA', 'MATRIZ', 3, 0, 1, 'CASO_INDICE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Coinfección y Defunción', 3);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Tuberculosis', 'BOOLEANO', 1, 0, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Hepatitis B', 'BOOLEANO', 2, 0, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Hepatitis C', 'BOOLEANO', 3, 0, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Defunción relacionada a SIDA?', 'BOOLEANO', 4, 0, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Causa de muerte', 'TEXTO', 5, 0, 1, 'CASO_INDICE');
INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
         VALUES ('Sífilis materna y congénita', 'Sífilis', 'sifilis,congenita,materna', 'A50', 'SEMANAL', 'Otros', 'Materno-perinatal y transmisión vertical', 0, 1, 0, 0, 1, 'MADRE,RECIEN_NACIDO', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Sífilis materna', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'FUR', 'FECHA', 1, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Recibió atención prenatal?', 'SELECT', 2, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Pruebas no treponémicas', 'MATRIZ', 3, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Pruebas treponémicas', 'MATRIZ', 4, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Adecuadamente tratada durante el embarazo?', 'SELECT', 5, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Contacto(s) sexual(es) tratado(s)', 'SELECT', 6, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Clasificación de caso en gestante', 'SELECT', 7, 0, 0, 'MADRE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Sífilis congénita', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Fecha de parto', 'FECHA', 1, 0, 0, 'RECIEN_NACIDO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Lugar del parto', 'SELECT', 2, 0, 0, 'RECIEN_NACIDO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Estado vital', 'SELECT', 3, 0, 0, 'RECIEN_NACIDO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Peso al nacimiento (gramos)', 'NUMERO', 4, 0, 0, 'RECIEN_NACIDO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Edad gestacional estimada (semanas)', 'NUMERO', 5, 0, 0, 'RECIEN_NACIDO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Criterios de caso', 'MULTISELECT', 6, 0, 0, 'RECIEN_NACIDO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Tratamiento del niño', 'SELECT', 7, 0, 0, 'RECIEN_NACIDO');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Clasificación final del niño', 'SELECT', 8, 0, 0, 'RECIEN_NACIDO');
INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
         VALUES ('Síndrome de rubéola congénita (SRC)', 'Rubéola congénita', 'rubeola,src,congenita', 'P35.0', 'INDIVIDUAL', 'Otros', 'Materno-perinatal y transmisión vertical', 0, 1, 1, 0, 1, 'CASO_INDICE,MADRE', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Antecedentes del paciente', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Nació prematuro?', 'BOOLEANO', 1, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Edad gestacional al parto (semanas)', 'NUMERO', 2, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Peso al nacer (gramos)', 'NUMERO', 3, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'APGAR 1 min', 'NUMERO', 4, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'APGAR 5 min', 'NUMERO', 5, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Cuadro clínico (Manifestaciones)', 'GRUPO_SI_NO', 6, 0, 0, 'CASO_INDICE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Antecedentes de la madre', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Apellidos y nombres', 'TEXTO', 1, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, 'Edad', 'NUMERO', 2, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Vacunada contra la rubéola?', 'SELECT', 3, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Fiebre y exantema durante embarazo?', 'SELECT', 4, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, catalogo_id, nombre, tipo, orden, obligatorio, sensible, rol_sujeto) 
             VALUES (@sec_id, NULL, '¿Se expuso a personas con fiebre y exantema?', 'SELECT', 5, 0, 0, 'MADRE');
