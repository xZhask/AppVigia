-- Fichas Lotes 5, 6, 7 y 8

INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
VALUES ('Gestante con VIH y niño expuesto', 'Gestante VIH/Niño', 'vih,gestante,niño expuesto,transmision vertical', 'Z21', 'SEMANAL', 'Otros', 'Materno-perinatal y transmisión vertical', 0, 0, 0, 0, 1, 'MADRE,NINO_EXPUESTO', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Sección I — Gestante con VIH', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'momento_de_diagn_stico_1802', 'Momento de diagnóstico', 'SELECT', 1, 1, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'a_o_de_diagn_stico_9b6f', 'Año de diagnóstico', 'NUMERO', 2, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'prueba_confirmatoria_4a2f', 'Prueba confirmatoria', 'FECHA', 3, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'fur_d29a', 'FUR', 'FECHA', 4, 1, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, '_recibi_arv__c2f1', '¿Recibió ARV?', 'BOOLEANO', 5, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, '_parto_por_ces_rea__cf60', '¿Parto por cesárea?', 'BOOLEANO', 6, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'fecha_del_parto_f13f', 'Fecha del parto', 'FECHA', 7, 0, 0, 'MADRE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Sección II — Niño nacido expuesto al VIH', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'c_digo_del_ni_o_d81f', 'Código del niño', 'TEXTO', 1, 1, 0, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'apellidos_y_nombres_8187', 'Apellidos y nombres', 'TEXTO', 2, 1, 0, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'sexo_d864', 'Sexo', 'SELECT', 3, 1, 0, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'fecha_de_nacimiento_53a6', 'Fecha de nacimiento', 'FECHA', 4, 1, 0, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, '_tom_leche_materna__4f0b', '¿Tomó leche materna?', 'BOOLEANO', 5, 0, 0, 'NINO_EXPUESTO');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'estado_serol_gico_final_390f', 'Estado serológico final', 'SELECT', 6, 0, 0, 'NINO_EXPUESTO');
INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
VALUES ('VIH / SIDA — notificación individual', 'VIH / SIDA', 'vih,sida,retrovirus', 'B24', 'SEMANAL', 'Otros', 'Materno-perinatal y transmisión vertical', 0, 0, 0, 0, 0, 'CASO_INDICE', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Identificación y Motivo', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'c_digo_del_paciente_1ea5', 'Código del paciente', 'TEXTO', 1, 1, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'motivo_de_notificaci_n_329e', 'Motivo de notificación', 'SELECT', 2, 1, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'estadio_de_infecci_n_vih_a5a1', 'Estadio de infección VIH', 'SELECT', 3, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'condici_n_especial_a717', 'Condición especial', 'MULTISELECT', 4, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'identidad_de_g_nero_631a', 'Identidad de género', 'SELECT', 5, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'v_a_de_transmisi_n_1c0b', 'Vía de transmisión', 'SELECT', 6, 1, 0, 'CASO_INDICE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'TARGA y estadio SIDA', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'fecha_de_inicio_de_tratamiento_9f0a', 'Fecha de inicio de tratamiento', 'FECHA', 1, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'enfermedades_indicadoras_de_sida_8385', 'Enfermedades indicadoras de SIDA', 'MATRIZ', 2, 0, 0, 'CASO_INDICE');
INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
VALUES ('Sífilis materna y congénita', 'Sífilis', 'sifilis,congenita,materna', 'A50', 'SEMANAL', 'Otros', 'Materno-perinatal y transmisión vertical', 0, 0, 0, 0, 1, 'MADRE,RECIEN_NACIDO', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Sífilis materna', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'fur_81c2', 'FUR', 'FECHA', 1, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'pruebas_trepon_micas_0ecd', 'Pruebas treponémicas', 'MATRIZ', 2, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, '_adecuadamente_tratada__b2e1', '¿Adecuadamente tratada?', 'SELECT', 3, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'clasificaci_n_de_caso_en_gestante_afe9', 'Clasificación de caso en gestante', 'SELECT', 4, 0, 0, 'MADRE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Sífilis congénita', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'fecha_de_parto_0a41', 'Fecha de parto', 'FECHA', 1, 0, 0, 'RECIEN_NACIDO');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'peso_al_nacimiento_c071', 'Peso al nacimiento', 'NUMERO', 2, 0, 0, 'RECIEN_NACIDO');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'clasificaci_n_final_del_ni_o_9459', 'Clasificación final del niño', 'SELECT', 3, 0, 0, 'RECIEN_NACIDO');
INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
VALUES ('Síndrome de rubéola congénita (SRC)', 'Rubéola congénita', 'rubeola,src,congenita', 'P35.0', 'SEMANAL', 'Otros', 'Materno-perinatal y transmisión vertical', 0, 0, 0, 0, 1, 'CASO_INDICE,MADRE', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Antecedentes del paciente', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, '_naci_prematuro__022b', '¿Nació prematuro?', 'BOOLEANO', 1, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'peso_al_nacer_da7f', 'Peso al nacer', 'NUMERO', 2, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'cuadro_cl_nico_8eab', 'Cuadro clínico', 'GRUPO_SI_NO', 3, 0, 0, 'CASO_INDICE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Antecedentes de la madre', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'apellidos_y_nombres_6b23', 'Apellidos y nombres', 'TEXTO', 1, 0, 0, 'MADRE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, '_vacunada_contra_rub_ola__c5b3', '¿Vacunada contra rubéola?', 'SELECT', 2, 0, 0, 'MADRE');
INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
VALUES ('ESAVI severo', 'ESAVI', 'esavi,vacuna,severo,reaccion adversa', 'Y59.0', 'INMEDIATA', 'Otros', 'Inmunoprevenibles', 0, 0, 0, 1, 0, 'CASO_INDICE', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Datos del evento', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, '_sucedi_en_establecimiento_de_salud__f5f3', '¿Sucedió en establecimiento de salud?', 'BOOLEANO', 1, 1, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'tipo_de_esavi_severo_d7ef', 'Tipo de ESAVI severo', 'MULTISELECT', 2, 1, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, '_fue_hospitalizado__32b6', '¿Fue hospitalizado?', 'BOOLEANO', 3, 1, 0, 'CASO_INDICE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Lista de chequeo', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, '_se_verific_cadena_de_fr_o__0cfd', '¿Se verificó cadena de frío?', 'BOOLEANO', 1, 0, 0, 'CASO_INDICE');
INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
VALUES ('Violencia familiar', 'Violencia', 'violencia,agresion,familiar,domestica', 'Y07', 'SEMANAL', 'Otros', 'Otros eventos bajo vigilancia', 0, 0, 0, 0, 1, 'CASO_INDICE,AGRESOR', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Datos del episodio', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'fecha_de_ocurrencia_b2c1', 'Fecha de ocurrencia', 'FECHA', 1, 1, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'lugar_de_ocurrencia_2bd1', 'Lugar de ocurrencia', 'SELECT', 2, 1, 1, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'tipo_de_violencia_4d41', 'Tipo de violencia', 'MULTISELECT', 3, 1, 1, 'CASO_INDICE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Datos de la persona agresora', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'edad_2501', 'Edad', 'NUMERO', 1, 0, 1, 'AGRESOR');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'sexo_d6ee', 'Sexo', 'SELECT', 2, 0, 1, 'AGRESOR');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'v_nculo_con_la_v_ctima_42bf', 'Vínculo con la víctima', 'SELECT', 3, 1, 1, 'AGRESOR');
INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
VALUES ('Lesiones por accidentes de tránsito', 'Accidentes Tránsito', 'accidente,transito,lesion,choque', 'V99', 'SEMANAL', 'Otros', 'Otros eventos bajo vigilancia', 0, 0, 0, 0, 1, 'CASO_INDICE,CONDUCTOR', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Datos del evento', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'fecha_y_hora_del_accidente_f68f', 'Fecha y hora del accidente', 'TEXTO', 1, 1, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'tipo_de_accidente_7c2c', 'Tipo de accidente', 'SELECT', 2, 1, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'rol_del_lesionado_7b17', 'Rol del lesionado', 'SELECT', 3, 1, 0, 'CASO_INDICE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Datos del vehículo y conductor', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'tipo_de_veh_culo_c814', 'Tipo de vehículo', 'SELECT', 1, 0, 0, 'CONDUCTOR');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, '_conductor_se_dio_a_la_fuga__c613', '¿Conductor se dio a la fuga?', 'BOOLEANO', 2, 0, 0, 'CONDUCTOR');
INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
VALUES ('Muerte materna (Anexo 1 y 2)', 'Muerte Materna', 'muerte,materna,fallecimiento,gestante', 'O95', 'INMEDIATA', 'Mortalidad', 'Materno-perinatal y transmisión vertical', 0, 0, 0, 0, 0, 'CASO_INDICE', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Anexo 1 — Notificación', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'lugar_de_fallecimiento_025c', 'Lugar de fallecimiento', 'SELECT', 1, 1, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'momento_del_fallecimiento_68cd', 'Momento del fallecimiento', 'SELECT', 2, 1, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'causa_b_sica_probable_81ed', 'Causa básica probable', 'TEXTO', 3, 1, 0, 'CASO_INDICE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Anexo 2 — Investigación clínica', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'n_atenciones_prenatales_d4c9', 'N.° atenciones prenatales', 'NUMERO', 1, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, '_hubo_demora_en_buscar_ayuda__88e4', '¿Hubo demora en buscar ayuda?', 'BOOLEANO', 2, 0, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'causa_final_confirmada_7633', 'Causa final confirmada', 'TEXTO', 3, 0, 0, 'CASO_INDICE');
INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, grupo, familia, usa_contactos, usa_muestras, usa_viajes, usa_vacunas, multi_sujeto, roles_sujeto, activo) 
VALUES ('Muerte fetal y neonatal', 'Muerte Fetal/Neonatal', 'muerte,fetal,neonatal,mortinato', 'P96', 'SEMANAL', 'Mortalidad', 'Materno-perinatal y transmisión vertical', 0, 0, 0, 0, 0, 'CASO_INDICE', 1);
SET @enf_id = LAST_INSERT_ID();
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Datos de la madre', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'apellidos_y_nombres_08a6', 'Apellidos y nombres', 'TEXTO', 1, 1, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'n_de_gestaciones_b619', 'N.° de gestaciones', 'NUMERO', 2, 0, 0, 'CASO_INDICE');
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (@enf_id, 'Datos del fallecido (Feto/Neonato)', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'momento_de_la_muerte_ab2d', 'Momento de la muerte', 'SELECT', 1, 1, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'peso_gramos__341f', 'Peso (gramos)', 'NUMERO', 2, 1, 0, 'CASO_INDICE');
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto) 
VALUES (@sec_id, 'causa_de_defunci_n_seg_n_certificado__5e61', 'Causa de defunción (según certificado)', 'TEXTO', 3, 1, 0, 'CASO_INDICE');
