-- Fichas Lotes 3 y 4

UPDATE enfermedad SET usa_contactos=0, usa_muestras=1, usa_viajes=0, usa_vacunas=0, multi_sujeto=0, roles_sujeto='CASO_INDICE' WHERE id=11;
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (11, 'Antecedentes epidemiológicos — fuente de infección', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, '_de_d_nde_obtuvo_el_agua__1a9b', '¿De dónde obtuvo el agua?', 'SELECT', 1, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, '_almacena_agua__f0e8', '¿Almacena agua?', 'BOOLEANO', 2, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'tipo_de_recipiente_05f7', 'Tipo de recipiente', 'SELECT', 3, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'nivel_de_cloro_ac66', 'Nivel de cloro', 'TEXTO', 4, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, '_recipientes_tienen_tapa__752b', '¿Recipientes tienen tapa?', 'BOOLEANO', 5, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, '_d_nde_consumi_alimentos__f146', '¿Dónde consumió alimentos?', 'MULTISELECT', 6, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'menores_de_2_a_os_114e', 'Menores de 2 años', 'MULTISELECT', 7, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'eliminaci_n_de_excretas_1cea', 'Eliminación de excretas', 'SELECT', 8, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, '_familiar_con_diarrea__1264', '¿Familiar con diarrea?', 'BOOLEANO', 9, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (11, 'Cuadro clínico', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 's_ntomas_b51d', 'Síntomas', 'MULTISELECT', 1, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'duraci_n_de_diarrea_d_as__8c27', 'Duración de diarrea (días)', 'NUMERO', 2, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'consistencia_e2f3', 'Consistencia', 'SELECT', 3, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'tipo_de_diarrea_9563', 'Tipo de diarrea', 'SELECT', 4, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'presencia_de_c53a', 'Presencia de', 'SELECT', 5, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'n_de_deposiciones_d_a_d0e3', 'N.° de deposiciones/día', 'NUMERO', 6, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'clasificaci_n_de_deshidrataci_n_3d78', 'Clasificación de deshidratación', 'SELECT', 7, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (11, 'Tratamiento y Evolución', 3);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'plan_de_tratamiento_71ed', 'Plan de tratamiento', 'SELECT', 1, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'tratamiento_antibi_tico_4bde', 'Tratamiento antibiótico', 'BOOLEANO', 2, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'alta_ce5b', 'Alta', 'BOOLEANO', 3, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'hospitalizado_1836', 'Hospitalizado', 'BOOLEANO', 4, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'complicaciones_eebd', 'Complicaciones', 'MULTISELECT', 5, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'fallecido_f903', 'Fallecido', 'BOOLEANO', 6, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'transferencia_445c', 'Transferencia', 'MULTISELECT', 7, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (11, 'Clasificación', 4);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'clasificaci_n_final_c266', 'Clasificación final', 'SELECT', 1, 1, 0, 'CASO_INDICE', NULL);
UPDATE enfermedad SET usa_contactos=0, usa_muestras=0, usa_viajes=0, usa_vacunas=1, multi_sujeto=0, roles_sujeto='CASO_INDICE' WHERE id=14;
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (14, 'Cuadro clínico', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'fecha_inicio_lesi_n_41fe', 'Fecha inicio lesión', 'FECHA', 1, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'herida_1fc4', 'Herida', 'SELECT', 2, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'tipo_de_herida_282f', 'Tipo de herida', 'SELECT', 3, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'causa_de_herida_cf87', 'Causa de herida', 'TEXTO', 4, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'signos_y_s_ntomas_ddb4', 'Signos y síntomas', 'GRUPO_SI_NO', 5, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'complicaciones_1a99', 'Complicaciones', 'TEXTAREA', 6, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (14, 'Atención', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'atendido_por_d78d', 'Atendido por', 'SELECT', 1, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'hospitalizado_4571', 'Hospitalizado', 'BOOLEANO', 2, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'condiciones_alta_c52e', 'Condiciones alta', 'TEXTO', 3, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'fallecido_d3d9', 'Fallecido', 'BOOLEANO', 4, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (14, 'Antecedentes y Diagnóstico', 3);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'lugar_de_infecci_n_91b4', 'Lugar de infección', 'TEXTO', 1, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'clasificaci_n_9a34', 'Clasificación', 'SELECT', 2, 1, 0, 'CASO_INDICE', NULL);
UPDATE enfermedad SET usa_contactos=0, usa_muestras=0, usa_viajes=0, usa_vacunas=0, multi_sujeto=1, roles_sujeto='CASO_INDICE,MADRE' WHERE id=15;
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (15, 'Cuadro clínico (Neonato)', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, '_succi_n_normal_2_primeros_d_as__c1bf', '¿Succión normal 2 primeros días?', 'BOOLEANO', 1, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, '_llanto_normal_2_primeros_d_as__b58e', '¿Llanto normal 2 primeros días?', 'BOOLEANO', 2, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'signos_y_s_ntomas_86d8', 'Signos y síntomas', 'GRUPO_SI_NO', 3, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'complicaciones_8ac3', 'Complicaciones', 'TEXTAREA', 4, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'hospitalizado_8381', 'Hospitalizado', 'BOOLEANO', 5, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'fallecido_f903', 'Fallecido', 'BOOLEANO', 6, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (15, 'Datos de la madre', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'apellidos_y_nombres_6b23', 'Apellidos y nombres', 'TEXTO', 1, 1, 0, 'MADRE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'edad_0072', 'Edad', 'NUMERO', 2, 0, 0, 'MADRE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'n_embarazos_26dc', 'N.° embarazos', 'NUMERO', 3, 0, 0, 'MADRE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'n_partos_8060', 'N.° partos', 'NUMERO', 4, 0, 0, 'MADRE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'n_hijos_fallecidos_28_d_as_20aa', 'N.° hijos fallecidos < 28 días', 'NUMERO', 5, 0, 0, 'MADRE', NULL);
UPDATE enfermedad SET usa_contactos=1, usa_muestras=1, usa_viajes=0, usa_vacunas=1, multi_sujeto=0, roles_sujeto='CASO_INDICE' WHERE id=16;
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (16, 'Cuadro clínico', 1);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'fiebre_al_inicio_d127', 'Fiebre al inicio', 'BOOLEANO', 1, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'progresi_n_de_la_par_lisis_d_as__76a5', 'Progresión de la parálisis (días)', 'NUMERO', 2, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'dificultad_respiratoria_36a2', 'Dificultad respiratoria', 'BOOLEANO', 3, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, '_sufri_traumatismo_intramuscular__16d6', '¿Sufrió traumatismo intramuscular?', 'BOOLEANO', 4, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'localizaci_n_de_la_par_lisis_24f8', 'Localización de la parálisis', 'MULTISELECT', 5, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'tipo_de_par_lisis_8d23', 'Tipo de parálisis', 'SELECT', 6, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'tono_muscular_3b74', 'Tono muscular', 'SELECT', 7, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'hospitalizado_edda', 'Hospitalizado', 'BOOLEANO', 8, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'diagn_stico_de_ingreso_6013', 'Diagnóstico de ingreso', 'TEXTO', 9, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'fallecido_5230', 'Fallecido', 'BOOLEANO', 10, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (16, 'Clasificación Final', 2);
SET @sec_id = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'clasificaci_n_7325', 'Clasificación', 'SELECT', 1, 1, 0, 'CASO_INDICE', NULL);
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, config) 
VALUES (@sec_id, 'evaluaci_n_a_los_60_d_as_7382', 'Evaluación a los 60 días', 'SELECT', 2, 1, 0, 'CASO_INDICE', NULL);
