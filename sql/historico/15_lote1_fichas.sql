-- 15_lote1_fichas.sql

-- Habilitar tablas hijas
UPDATE enfermedad SET usa_contactos=1, usa_muestras=1, usa_viajes=1, usa_vacunas=1 WHERE cie10 LIKE 'A37%'; -- Tos ferina
UPDATE enfermedad SET usa_contactos=1, usa_muestras=1, usa_vacunas=1 WHERE cie10 LIKE 'B01%'; -- Varicela
UPDATE enfermedad SET usa_contactos=1, usa_muestras=1, usa_vacunas=1 WHERE cie10 LIKE 'B26%'; -- Parotiditis
UPDATE enfermedad SET usa_contactos=1, usa_muestras=1, usa_viajes=1, usa_vacunas=1 WHERE cie10 LIKE 'A36%'; -- Difteria


-- Tos Ferina
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Cuadro clínico', 1 FROM enfermedad WHERE cie10 LIKE 'A37%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_duraci_n_en_d_as_tos_parox_stica', 'Duración en días: tos paroxística', 'NUMERO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_duraci_n_en_d_as_tos_persistente_en_1_a_o', 'Duración en días: tos persistente en ≥1 año', 'NUMERO', 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_tos_parox_stica_10_golpes_de_tos_', 'Tos paroxística (>10 golpes de tos)', 'SI_NO_FECHA', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_tos_persistente_2_semanas_', 'Tos persistente (>2 semanas)', 'SI_NO_FECHA', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_estridor', 'Estridor', 'SI_NO_FECHA', 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_v_mitos_despu_s_de_la_tos', 'Vómitos después de la tos', 'SI_NO_FECHA', 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_apnea', 'Apnea', 'SI_NO_FECHA', 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_cianosis', 'Cianosis', 'SI_NO_FECHA', 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_otros_s_ntomas', 'Otros síntomas', 'SI_NO_FECHA', 0, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Tratamiento', 2 FROM enfermedad WHERE cie10 LIKE 'A37%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_paciente_recibi_antibi_tico_', '¿Paciente recibió antibiótico?', 'BOOLEANO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Tratamiento';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_antibi_ticos_recibidos', 'Antibióticos recibidos', 'MATRIZ', 0, 2, '{\"columnas\":[\"Dosis\",\"Fecha de inicio\",\"Vía de administración\",\"N.° de días\"],\"filas\":[\"Antibiótico 1\",\"Antibiótico 2\",\"Antibiótico 3\"]}', NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Tratamiento';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Complicaciones y hospitalización', 3 FROM enfermedad WHERE cie10 LIKE 'A37%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_neumon_a', 'Neumonía', 'SI_NO_FECHA', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_convulsiones', 'Convulsiones', 'SI_NO_FECHA', 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_encefalopat_a', 'Encefalopatía', 'SI_NO_FECHA', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_anorexia', 'Anorexia', 'SI_NO_FECHA', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_desnutrici_n', 'Desnutrición', 'SI_NO_FECHA', 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_deshidrataci_n', 'Deshidratación', 'SI_NO_FECHA', 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_otitis_media', 'Otitis media', 'SI_NO_FECHA', 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_otras_complicaciones', 'Otras complicaciones', 'SI_NO_FECHA', 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_hospitalizaci_n', 'Hospitalización', 'BOOLEANO', 0, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_n_historia_cl_nica', 'N.° historia clínica', 'TEXTO', 0, 10, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_establecimiento', 'Establecimiento', 'TEXTO', 0, 11, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_fecha_de_hospitalizaci_n', 'Fecha de hospitalización', 'FECHA', 0, 12, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_n_de_d_as_de_hospitalizaci_n', 'N.° de días de hospitalización', 'NUMERO', 0, 13, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_dx_de_ingreso', 'Dx de ingreso', 'TEXTO', 0, 14, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_fecha_de_alta', 'Fecha de alta', 'FECHA', 0, 15, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_dx_de_egreso', 'Dx de egreso', 'TEXTO', 0, 16, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_presenta_alguna_comorbilidad_', '¿Presenta alguna comorbilidad?', 'TEXTO', 0, 17, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_se_diagnosticaron_otras_infecciones_por_laboratorio_', '¿Se diagnosticaron otras infecciones por laboratorio?', 'BOOLEANO', 0, 18, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_infecciones_diagnosticadas', 'Infecciones diagnosticadas', 'TEXTO', 0, 19, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_defunci_n', 'Defunción', 'BOOLEANO', 0, 20, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_fecha_de_defunci_n', 'Fecha de defunción', 'FECHA', 0, 21, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_causa_b_sica_de_defunci_n', 'Causa básica de defunción', 'TEXTO', 0, 22, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Complicaciones y hospitalización';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Lugar probable de infección', 4 FROM enfermedad WHERE cie10 LIKE 'A37%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_contacto_con_casos_probables_o_confirmados_de_tos_ferina', '¿Contacto con casos probables o confirmados de tos ferina?', 'TEXTO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Lugar probable de infección';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_alg_n_miembro_de_la_familia_o_persona_cercana_ha_tenido_', '¿Algún miembro de la familia o persona cercana ha tenido tos por más de 2 semanas?', 'BOOLEANO', 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Lugar probable de infección';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_contactos_directos_domiciliarios', 'Contactos directos domiciliarios', 'NUMERO', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Lugar probable de infección';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_contactos_directos_extradomiciliarios', 'Contactos directos extradomiciliarios', 'NUMERO', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Lugar probable de infección';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a37_contactos_por_lugar', 'Contactos por lugar', 'MATRIZ', 0, 5, '{\"columnas\":[\"N.° contactos\",\"Con síntomas\",\"Esquema completo\",\"Esquema incompleto\",\"Recibieron vacunación\",\"Recibieron antibióticos\"],\"filas\":[\"Casa\",\"Nido\\/guardería\",\"Colegio\",\"Universidad\\/Instituto\",\"Centro de trabajo\",\"Establecimiento de salud\",\"Otro\"]}', NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A37%' AND sd.nombre = 'Lugar probable de infección';

-- Varicela con complicaciones
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Cuadro clínico', 1 FROM enfermedad WHERE cie10 LIKE 'B01%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_fecha_de_inicio_de_erupci_n_d_rmica', 'Fecha de inicio de erupción dérmica', 'FECHA', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_lesi_n_m_cula', 'Lesión: mácula', 'BOOLEANO', 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_lesi_n_p_pula', 'Lesión: pápula', 'BOOLEANO', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_lesi_n_ves_cula', 'Lesión: vesícula', 'BOOLEANO', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_lesi_n_costra', 'Lesión: costra', 'BOOLEANO', 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_fecha_de_inicio_de_fiebre', 'Fecha de inicio de fiebre', 'FECHA', 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_temperatura_c_', 'Temperatura (°C)', 'NUMERO', 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_n_de_d_as_de_duraci_n_de_fiebre', 'N.° de días de duración de fiebre', 'NUMERO', 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Complicaciones', 2 FROM enfermedad WHERE cie10 LIKE 'B01%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_sobreinfecci_n_de_piel_y_partes_blandas', 'Sobreinfección de piel y partes blandas', 'BOOLEANO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Complicaciones';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_neurol_gicas', 'Neurológicas', 'BOOLEANO', 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Complicaciones';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_respiratorias', 'Respiratorias', 'BOOLEANO', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Complicaciones';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_hemorr_gicas', 'Hemorrágicas', 'BOOLEANO', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Complicaciones';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_otras_especificar_', 'Otras (especificar)', 'TEXTO', 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Complicaciones';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Factores de riesgo', 3 FROM enfermedad WHERE cie10 LIKE 'B01%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_inmunosupresi_n', 'Inmunosupresión', 'BOOLEANO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Factores de riesgo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_asma', 'Asma', 'BOOLEANO', 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Factores de riesgo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_c_ncer', 'Cáncer', 'BOOLEANO', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Factores de riesgo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_malformaci_n_cong_nita', 'Malformación congénita', 'BOOLEANO', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Factores de riesgo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_gestante', 'Gestante', 'BOOLEANO', 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Factores de riesgo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_enfermedad_reumatol_gica', 'Enfermedad reumatológica', 'BOOLEANO', 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Factores de riesgo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_enfermedad_card_aca', 'Enfermedad cardíaca', 'BOOLEANO', 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Factores de riesgo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_des_rdenes_metab_licos', 'Desórdenes metabólicos', 'BOOLEANO', 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Factores de riesgo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_otras_especificar_', 'Otras (especificar)', 'TEXTO', 0, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Factores de riesgo';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Hospitalización y egreso', 4 FROM enfermedad WHERE cie10 LIKE 'B01%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_hospitalizaci_n', 'Hospitalización', 'BOOLEANO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Hospitalización y egreso';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_establecimiento', 'Establecimiento', 'TEXTO', 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Hospitalización y egreso';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_fecha_de_hospitalizaci_n', 'Fecha de hospitalización', 'FECHA', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Hospitalización y egreso';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_n_de_d_as', 'N.° de días', 'NUMERO', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Hospitalización y egreso';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_condici_n_de_egreso', 'Condición de egreso', 'TEXTO', 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Hospitalización y egreso';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_fecha_de_fallecimiento', 'Fecha de fallecimiento', 'FECHA', 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Hospitalización y egreso';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_causa_de_muerte', 'Causa de muerte', 'TEXTO', 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Hospitalización y egreso';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Lugar probable de infección', 5 FROM enfermedad WHERE cie10 LIKE 'B01%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_en_las_ltimas_2_a_3_semanas_estuvo_en_contacto_con_otro_', '¿En las últimas 2 a 3 semanas estuvo en contacto con otro caso de varicela?', 'TEXTO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Lugar probable de infección';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_contactos_por_lugar', 'Contactos por lugar', 'MATRIZ', 0, 2, '{\"columnas\":[\"Nombre del lugar\",\"Dirección\",\"N.° contactos sanos\",\"N.° contactos enfermos\"],\"filas\":[\"Casa\",\"Nido\\/guardería\",\"Colegio\",\"Universidad\\/Instituto\",\"Centro de trabajo\",\"Establecimiento de salud\",\"Otros\"]}', NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Lugar probable de infección';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_tuvo_contacto_con_gestante_', '¿Tuvo contacto con gestante?', 'BOOLEANO', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Lugar probable de infección';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b01_semanas_de_gestaci_n_contacto_', 'Semanas de gestación (contacto)', 'NUMERO', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B01%' AND sd.nombre = 'Lugar probable de infección';

-- Parotiditis con complicaciones
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Cuadro clínico', 1 FROM enfermedad WHERE cie10 LIKE 'B26%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_present_inflamaci_n_de_gl_ndulas_par_tidas_', '¿Presentó inflamación de glándulas parótidas?', 'BOOLEANO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_fecha_de_inicio_de_parotiditis', 'Fecha de inicio de parotiditis', 'FECHA', 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_n_de_d_as_de_duraci_n', 'N.° de días de duración', 'NUMERO', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_localizaci_n', 'Localización', 'TEXTO', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_inflamaci_n_de_gl_ndulas_submandibulares', 'Inflamación de glándulas submandibulares', 'BOOLEANO', 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_inflamaci_n_de_gl_ndulas_sublinguales', 'Inflamación de glándulas sublinguales', 'BOOLEANO', 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Complicaciones', 2 FROM enfermedad WHERE cie10 LIKE 'B26%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_orquitis', 'Orquitis', 'SI_NO_FECHA', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Complicaciones';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_ooforitis', 'Ooforitis', 'SI_NO_FECHA', 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Complicaciones';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_p_rdida_de_audici_n', 'Pérdida de audición', 'SI_NO_FECHA', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Complicaciones';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_encefalitis', 'Encefalitis', 'SI_NO_FECHA', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Complicaciones';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_meningitis', 'Meningitis', 'SI_NO_FECHA', 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Complicaciones';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_otras_complicaciones', 'Otras complicaciones', 'SI_NO_FECHA', 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Complicaciones';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Lugar probable de infección', 3 FROM enfermedad WHERE cie10 LIKE 'B26%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_en_las_ltimas_2_a_4_semanas_estuvo_en_contacto_con_otro_', '¿En las últimas 2 a 4 semanas estuvo en contacto con otro caso de parotiditis?', 'TEXTO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Lugar probable de infección';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_contactos_por_lugar', 'Contactos por lugar', 'MATRIZ', 0, 2, '{\"columnas\":[\"Nombre\",\"Dirección\",\"N.° contactos sanos\",\"N.° contactos enfermos\"],\"filas\":[\"Casa\",\"Nido\\/guardería\",\"Colegio\",\"Escuela militar\\/policial\",\"Universidad\\/Instituto\",\"Centro de trabajo\",\"Establecimiento de salud\",\"Otros\"]}', NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Lugar probable de infección';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_tuvo_contacto_con_gestante_', '¿Tuvo contacto con gestante?', 'BOOLEANO', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Lugar probable de infección';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'b26_trimestre_de_gestaci_n_contacto_', 'Trimestre de gestación (contacto)', 'NUMERO', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B26%' AND sd.nombre = 'Lugar probable de infección';

-- Difteria
INSERT INTO catalogo (nombre) VALUES ('Signos Clínicos - Difteria'), ('Presencia de Placa - Difteria');
SET @cat_signos = (SELECT id FROM catalogo WHERE nombre = 'Signos Clínicos - Difteria');
SET @cat_placa = (SELECT id FROM catalogo WHERE nombre = 'Presencia de Placa - Difteria');
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES 
(@cat_signos, 'FIEBRE', 'Fiebre o sensación de alza térmica', 1),
(@cat_signos, 'DOLOR_GARGANTA', 'Dolor de garganta o al deglutir', 2),
(@cat_signos, 'FARINGITIS', 'Faringitis', 3),
(@cat_signos, 'LARINGITIS', 'Laringitis', 4),
(@cat_signos, 'AMIGDALITIS', 'Amigdalitis', 5),
(@cat_signos, 'AUMENTO_CUELLO', 'Aumento de volumen en cuello', 6),
(@cat_signos, 'TOS', 'Tos', 7),
(@cat_signos, 'SECRECION_NASAL', 'Secreción nasal (mucosa o sanguinolenta)', 8),
(@cat_signos, 'LESION_CUTANEA', 'Lesión cutánea ulcerosa', 9),
(@cat_signos, 'DISNEA', 'Disnea', 10);
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES 
(@cat_placa, 'OROFARINGE', 'Orofaringe', 1),
(@cat_placa, 'NASAL', 'Nasal', 2),
(@cat_placa, 'TRAQUEOBRONQUIAL', 'Traqueobronquial', 3),
(@cat_placa, 'OTROS', 'Otros', 4);
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Signos y síntomas', 1 FROM enfermedad WHERE cie10 LIKE 'A36%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_temperatura_c', 'Temperatura °C', 'NUMERO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Signos y síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_signos_cl_nicos', 'Signos clínicos', 'GRUPO_SI_NO', 0, 2, NULL, @cat_signos
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Signos y síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_presencia_de_placa_seudomembrana_', 'Presencia de placa (seudomembrana)', 'GRUPO_SI_NO', 0, 3, NULL, @cat_placa
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Signos y síntomas';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Evolución', 2 FROM enfermedad WHERE cie10 LIKE 'A36%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_hospitalizado', 'Hospitalizado', 'TEXTO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_antibi_tico_antes_del_ingreso_', '¿Antibiótico antes del ingreso?', 'BOOLEANO', 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_antibi_tico_antes_del_ingreso_especificar_', 'Antibiótico antes del ingreso (especificar)', 'TEXTO', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_hospital', 'Hospital', 'TEXTO', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_fecha_de_hospitalizaci_n', 'Fecha de hospitalización', 'FECHA', 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_tratamiento_antibi_tico', 'Tratamiento: Antibiótico', 'BOOLEANO', 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_tratamiento_antitoxina', 'Tratamiento: Antitoxina', 'BOOLEANO', 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_tratamiento_especificar_', 'Tratamiento (especificar)', 'TEXTO', 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_egreso_del_hospital', 'Egreso del hospital', 'TEXTO', 0, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_fecha_de_alta', 'Fecha de alta', 'FECHA', 0, 10, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_fecha_de_defunci_n', 'Fecha de defunción', 'FECHA', 0, 11, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_complicaciones_card_acas', 'Complicaciones: Cardíacas', 'BOOLEANO', 0, 12, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_complicaciones_neurol_gicas', 'Complicaciones: Neurológicas', 'BOOLEANO', 0, 13, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_complicaciones_otras', 'Complicaciones: Otras', 'TEXTO', 0, 14, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Evolución';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Información epidemiológica', 3 FROM enfermedad WHERE cie10 LIKE 'A36%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_estuvo_en_contacto_con_un_posible_caso_de_difteria_', '¿Estuvo en contacto con un posible caso de difteria?', 'TEXTO', 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Información epidemiológica';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_sabe_si_hay_casos_similares_en_la_zona_', '¿Sabe si hay casos similares en la zona?', 'TEXTO', 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Información epidemiológica';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_aislamiento_domiciliario', 'Aislamiento domiciliario', 'TEXTO', 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Información epidemiológica';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, config, catalogo_id) 
            SELECT sd.id, 'a36_fecha_de_aislamiento', 'Fecha de aislamiento', 'FECHA', 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A36%' AND sd.nombre = 'Información epidemiológica';
