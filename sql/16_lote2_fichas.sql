-- 16_lote2_fichas.sql

-- Habilitar tablas hijas
UPDATE enfermedad SET usa_contactos=0, usa_muestras=1, usa_viajes=1, usa_vacunas=1 WHERE cie10 LIKE 'A95%'; -- Fiebre amarilla
UPDATE enfermedad SET usa_contactos=0, usa_muestras=1, usa_viajes=0, usa_vacunas=0 WHERE cie10 LIKE 'B55%'; -- Leishmaniasis
UPDATE enfermedad SET usa_contactos=0, usa_muestras=1, usa_viajes=1, usa_vacunas=0 WHERE cie10 LIKE 'B57%'; -- Chagas
UPDATE enfermedad SET usa_contactos=0, usa_muestras=1, usa_viajes=1, usa_vacunas=0 WHERE cie10 LIKE 'A44%'; -- Carrión
UPDATE enfermedad SET usa_contactos=1, usa_muestras=0, usa_viajes=1, usa_vacunas=0 WHERE cie10 LIKE 'B04%'; -- Mpox

-- Nueva columna 'sensible'
ALTER TABLE campo_def ADD COLUMN sensible TINYINT(1) NOT NULL DEFAULT 0 AFTER obligatorio;


-- Fiebre amarilla
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Cuadro clínico', 1 FROM enfermedad WHERE cie10 LIKE 'A95%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_fiebre', 'Fiebre', 'SI_NO_FECHA', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_ictericia', 'Ictericia', 'SI_NO_FECHA', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_pulso_lento_en_relaci_n_a_la_fiebre', 'Pulso lento en relación a la fiebre', 'SI_NO_FECHA', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_hemorragia_nasal', 'Hemorragia nasal', 'SI_NO_FECHA', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_melena_hematemesis', 'Melena / hematemesis', 'SI_NO_FECHA', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_petequias', 'Petequias', 'SI_NO_FECHA', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_diarreas', 'Diarreas', 'SI_NO_FECHA', 0, 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_hipertensi_n', 'Hipertensión', 'SI_NO_FECHA', 0, 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_oliguria', 'Oliguria', 'SI_NO_FECHA', 0, 0, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_proteinuria', 'Proteinuria', 'SI_NO_FECHA', 0, 0, 10, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_coluria', 'Coluria', 'SI_NO_FECHA', 0, 0, 11, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_hepatomegalia', 'Hepatomegalia', 'SI_NO_FECHA', 0, 0, 12, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Migración', 2 FROM enfermedad WHERE cie10 LIKE 'A95%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_tiempo_que_reside_en_domicilio_actual_a_os', 'Tiempo que reside en domicilio actual: años', 'NUMERO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Migración';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_tiempo_que_reside_en_domicilio_actual_meses', 'Tiempo que reside en domicilio actual: meses', 'NUMERO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Migración';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_si_reside_menos_de_6_meses_d_nde_viv_a_anteriormente_', 'Si reside menos de 6 meses, ¿dónde vivía anteriormente?', 'TEXTO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Migración';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_localidades_visitadas_en_los_ltimos_10_d_as', 'Localidades visitadas en los últimos 10 días', 'TEXTAREA', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Migración';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Antecedentes epidemiológicos', 3 FROM enfermedad WHERE cie10 LIKE 'A95%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_casos_reportados_ltimos_10_d_as_en_los_lugares_visitados', 'Casos reportados últimos 10 días: En los lugares visitados', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_casos_reportados_ltimos_10_d_as_en_su_comunidad', 'Casos reportados últimos 10 días: En su comunidad', 'BOOLEANO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_casos_reportados_ltimos_10_d_as_en_su_casa', 'Casos reportados últimos 10 días: En su casa', 'BOOLEANO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_casos_reportados_ltimos_10_d_as_epizootias', 'Casos reportados últimos 10 días: Epizootias', 'BOOLEANO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_cu_ntas_personas_viven_en_su_casa', 'Cuántas personas viven en su casa', 'NUMERO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Hospitalización', 4 FROM enfermedad WHERE cie10 LIKE 'A95%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_hospitalizado', 'Hospitalizado', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_fecha', 'Fecha', 'FECHA', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_hospital', 'Hospital', 'TEXTO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_n_h_c_', 'N.° H.C.', 'TEXTO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_tiempo_de_enfermedad_al_momento_de_la_hospitalizaci_n_d_', 'Tiempo de enfermedad al momento de la hospitalización (días)', 'NUMERO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_tiempo_de_traslado_desde_el_domicilio_horas_minutos_', 'Tiempo de traslado desde el domicilio (horas / minutos)', 'TEXTO', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_dx_de_ingreso_1', 'Dx de ingreso 1', 'TEXTO', 0, 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_dx_de_ingreso_2', 'Dx de ingreso 2', 'TEXTO', 0, 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_condici_n_de_egreso', 'Condición de egreso', 'TEXTO', 0, 0, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_fallecido_necropsia', 'Fallecido: necropsia', 'BOOLEANO', 0, 0, 10, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_fallecido_dx_macrosc_pico', 'Fallecido: Dx macroscópico', 'TEXTO', 0, 0, 11, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_fallecido_dx_microsc_pico', 'Fallecido: Dx microscópico', 'TEXTO', 0, 0, 12, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_fallecido_fecha', 'Fallecido: fecha', 'FECHA', 0, 0, 13, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Hospitalización';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Clasificación final', 5 FROM enfermedad WHERE cie10 LIKE 'A95%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_criterio_laboratorio', 'Criterio: Laboratorio', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Clasificación final';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_criterio_anatom_a_patol_gica', 'Criterio: Anatomía patológica', 'BOOLEANO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Clasificación final';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_criterio_cl_nica', 'Criterio: Clínica', 'BOOLEANO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Clasificación final';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a95_dx_de_descarte', 'Dx de descarte', 'TEXTO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A95%' AND sd.nombre = 'Clasificación final';

-- Leishmaniasis
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Antecedente epidemiológico', 1 FROM enfermedad WHERE cie10 LIKE 'B55%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_lugar_de_contagio_localidad', 'Lugar de contagio: localidad', 'TEXTO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Antecedente epidemiológico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_lugar_de_contagio_distrito', 'Lugar de contagio: distrito', 'TEXTO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Antecedente epidemiológico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_lugar_de_contagio_provincia', 'Lugar de contagio: provincia', 'TEXTO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Antecedente epidemiológico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_lugar_de_contagio_departamento', 'Lugar de contagio: departamento', 'TEXTO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Antecedente epidemiológico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_tiempo_de_permanencia_en_el_lugar_de_contagio', 'Tiempo de permanencia en el lugar de contagio', 'TEXTO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Antecedente epidemiológico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_actividad_que_desarroll_durante_el_contagio', 'Actividad que desarrolló durante el contagio', 'TEXTO', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Antecedente epidemiológico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_existen_otras_personas_con_lesiones_similares_en_su_vivi', '¿Existen otras personas con lesiones similares en su vivienda o localidad?', 'BOOLEANO', 0, 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Antecedente epidemiológico';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Datos clínicos', 2 FROM enfermedad WHERE cie10 LIKE 'B55%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_dolor_en_la_lesi_n', 'Síntoma: Dolor en la lesión', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_fiebre', 'Síntoma: Fiebre', 'BOOLEANO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_prurito_local', 'Síntoma: Prurito local', 'BOOLEANO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_tupidez_nasal', 'Síntoma: Tupidez nasal', 'BOOLEANO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_disfon_a_leve', 'Síntoma: Disfonía leve', 'BOOLEANO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_disfon_a_moderada', 'Síntoma: Disfonía moderada', 'BOOLEANO', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_disfon_a_grave', 'Síntoma: Disfonía grave', 'BOOLEANO', 0, 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_dificultad_respiratoria_leve', 'Síntoma: Dificultad respiratoria leve', 'BOOLEANO', 0, 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_dificultad_respiratoria_moderada', 'Síntoma: Dificultad respiratoria moderada', 'BOOLEANO', 0, 0, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_dificultad_respiratoria_severa', 'Síntoma: Dificultad respiratoria severa', 'BOOLEANO', 0, 0, 10, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_tos', 'Síntoma: Tos', 'BOOLEANO', 0, 0, 11, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_s_ntoma_p_rdida_de_peso', 'Síntoma: Pérdida de peso', 'BOOLEANO', 0, 0, 12, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_antecedente_tbc', 'Antecedente: TBC', 'BOOLEANO', 0, 0, 13, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_antecedente_vih', 'Antecedente: VIH', 'BOOLEANO', 0, 0, 14, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_antecedente_chagas', 'Antecedente: Chagas', 'BOOLEANO', 0, 0, 15, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_antecedente_otras', 'Antecedente: Otras', 'BOOLEANO', 0, 0, 16, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_alergia_a_medicinas', 'Alergia a medicinas', 'BOOLEANO', 0, 0, 17, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_alergia_especificar_', 'Alergia (especificar)', 'TEXTO', 0, 0, 18, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_fecha_de_ltima_regla', 'Fecha de última regla', 'FECHA', 0, 0, 19, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_mac_usado', 'MAC usado', 'TEXTO', 0, 0, 20, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_medicinas_usadas_actualmente', 'Medicinas usadas actualmente', 'TEXTO', 0, 0, 21, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Datos clínicos';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Lesiones cutáneas', 3 FROM enfermedad WHERE cie10 LIKE 'B55%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_n_de_lesiones_activas', 'N.° de lesiones activas', 'NUMERO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Lesiones cutáneas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_n_de_cicatrices', 'N.° de cicatrices', 'NUMERO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Lesiones cutáneas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_lesiones', 'Lesiones', 'MATRIZ', 0, 0, 3, '{\"columnas\":[\"Fecha de inicio\",\"Tipo (1\\/2\\/3\\/4)\",\"Localización (1\\/2\\/3\\/4\\/5)\",\"Ganglios (Sí\\/No)\",\"Infección (Sí\\/No)\",\"Diámetros (mm)\",\"Superficie (mm²)\"],\"filas\":[\"Lesión 1\",\"Lesión 2\",\"Lesión 3\",\"Lesión 4\"]}', NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Lesiones cutáneas';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Enfermedad mucosa', 4 FROM enfermedad WHERE cie10 LIKE 'B55%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_enfermedad_mucosa', 'Enfermedad mucosa', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Enfermedad mucosa';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_fecha_de_inicio_de_s_ntomas', 'Fecha de inicio de síntomas', 'FECHA', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Enfermedad mucosa';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_tiempo_a_os_meses_', 'Tiempo (años / meses)', 'TEXTO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Enfermedad mucosa';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_compromiso_de_estructuras', 'Compromiso de estructuras', 'MATRIZ', 0, 0, 4, '{\"columnas\":[\"Eritema\",\"Edema\",\"Infiltración\",\"Úlcera\",\"N.° de lesiones\"],\"filas\":[\"Narinas\",\"1\\/3 anterior\",\"Septo nasal\",\"Cornetes\",\"Labios\",\"Arcada\",\"Paladar\",\"Úvula\",\"Faringe\",\"Epiglotis\",\"Cuerdas vocales\",\"Otros\"]}', NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Enfermedad mucosa';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Signos de leishmaniasis visceral', 5 FROM enfermedad WHERE cie10 LIKE 'B55%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_hepatomegalia', 'Hepatomegalia', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_esplenomegalia', 'Esplenomegalia', 'BOOLEANO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_adenomegalia', 'Adenomegalia', 'BOOLEANO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_anemia', 'Anemia', 'BOOLEANO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_p_rdida_de_peso', 'Pérdida de peso', 'BOOLEANO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_anorexia', 'Anorexia', 'BOOLEANO', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_adenopat_as', 'Adenopatías', 'BOOLEANO', 0, 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_epistaxis', 'Epistaxis', 'BOOLEANO', 0, 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_hemorragia_gingival', 'Hemorragia gingival', 'BOOLEANO', 0, 0, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_debilidad_progresiva', 'Debilidad progresiva', 'BOOLEANO', 0, 0, 10, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_desnutrici_n', 'Desnutrición', 'BOOLEANO', 0, 0, 11, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_edema', 'Edema', 'BOOLEANO', 0, 0, 12, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_alteraciones_de_la_piel', 'Alteraciones de la piel', 'BOOLEANO', 0, 0, 13, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_ascitis', 'Ascitis', 'BOOLEANO', 0, 0, 14, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Signos de leishmaniasis visceral';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Diagnóstico', 6 FROM enfermedad WHERE cie10 LIKE 'B55%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_forma', 'Forma', 'TEXTO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Diagnóstico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_situaci_n', 'Situación', 'TEXTO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Diagnóstico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b55_tratamiento', 'Tratamiento', 'TEXTO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B55%' AND sd.nombre = 'Diagnóstico';

-- Chagas
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Antecedentes epidemiológicos', 1 FROM enfermedad WHERE cie10 LIKE 'B57%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_lugar_probable_de_contagio_dpto_prov_dist_localidad_', 'Lugar probable de contagio (dpto / prov / dist / localidad)', 'TEXTO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_fecha_probable_de_contagio', 'Fecha probable de contagio', 'FECHA', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_tiempo_de_permanencia_d_as_meses_a_os_', 'Tiempo de permanencia (días/meses/años)', 'TEXTO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_existe_chirimacha_o_chinche_en_su_casa_', '¿Existe "chirimacha" o chinche en su casa?', 'TEXTO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_ha_sido_picado_por_una_chirimacha_o_chinche_', '¿Ha sido picado por una "chirimacha" o chinche?', 'TEXTO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_fecha_de_picadura', 'Fecha de picadura', 'FECHA', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_ha_recibido_transfusi_n_sin_control_para_chagas_', '¿Ha recibido transfusión sin control para Chagas?', 'TEXTO', 0, 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_cu_ntas_veces_transfusi_n_', 'Cuántas veces (transfusión)', 'NUMERO', 0, 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_fecha_de_la_ltima_transfusi_n', 'Fecha de la última transfusión', 'FECHA', 0, 0, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_antecedente_de_madre_seropositiva_', '¿Antecedente de madre seropositiva?', 'TEXTO', 0, 0, 10, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_otra_persona_con_cuadro_similar_en_la_casa_o_lugar_de_co', '¿Otra persona con cuadro similar en la casa o lugar de contagio?', 'BOOLEANO', 0, 0, 11, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_posible_forma_de_transmisi_n', 'Posible forma de transmisión', 'TEXTO', 0, 0, 12, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Antecedentes epidemiológicos';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Migración', 2 FROM enfermedad WHERE cie10 LIKE 'B57%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_tiempo_que_reside_en_domicilio_actual', 'Tiempo que reside en domicilio actual', 'TEXTO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Migración';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_d_nde_viv_a_anteriormente', 'Dónde vivía anteriormente', 'TEXTO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Migración';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_localidades_visitadas_ltimos_10_d_as', 'Localidades visitadas últimos 10 días', 'TEXTAREA', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Migración';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Cuadro clínico', 3 FROM enfermedad WHERE cie10 LIKE 'B57%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_condici_n', 'Condición', 'TEXTO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_aguda_fiebre', 'Etapa aguda: Fiebre', 'BOOLEANO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_aguda_miocarditis', 'Etapa aguda: Miocarditis', 'BOOLEANO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_aguda_chagoma_de_inoculaci_n', 'Etapa aguda: Chagoma de inoculación', 'BOOLEANO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_aguda_signo_de_roma_a', 'Etapa aguda: Signo de Romaña', 'BOOLEANO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_aguda_hepatomegalia', 'Etapa aguda: Hepatomegalia', 'BOOLEANO', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_aguda_esplenomegalia', 'Etapa aguda: Esplenomegalia', 'BOOLEANO', 0, 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_aguda_mialgias', 'Etapa aguda: Mialgias', 'BOOLEANO', 0, 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_aguda_meningoencefalitis', 'Etapa aguda: Meningoencefalitis', 'BOOLEANO', 0, 0, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_aguda_malestar_general', 'Etapa aguda: Malestar general', 'BOOLEANO', 0, 0, 10, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_palpitaciones', 'Etapa crónica: Palpitaciones', 'BOOLEANO', 0, 0, 11, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_arritmia', 'Etapa crónica: Arritmia', 'BOOLEANO', 0, 0, 12, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_dolor_precordial', 'Etapa crónica: Dolor precordial', 'BOOLEANO', 0, 0, 13, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_hepatomegalia', 'Etapa crónica: Hepatomegalia', 'BOOLEANO', 0, 0, 14, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_disfagia', 'Etapa crónica: Disfagia', 'BOOLEANO', 0, 0, 15, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_regurgitaci_n', 'Etapa crónica: Regurgitación', 'BOOLEANO', 0, 0, 16, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_taquicardia', 'Etapa crónica: Taquicardia', 'BOOLEANO', 0, 0, 17, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_disnea', 'Etapa crónica: Disnea', 'BOOLEANO', 0, 0, 18, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_edema', 'Etapa crónica: Edema', 'BOOLEANO', 0, 0, 19, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_soplo', 'Etapa crónica: Soplo', 'BOOLEANO', 0, 0, 20, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_tos', 'Etapa crónica: Tos', 'BOOLEANO', 0, 0, 21, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_etapa_cr_nica_odinofagia', 'Etapa crónica: Odinofagia', 'BOOLEANO', 0, 0, 22, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Cuadro clínico';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Clasificación final', 4 FROM enfermedad WHERE cie10 LIKE 'B57%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_forma_y_clasificaci_n', 'Forma y Clasificación', 'TEXTO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Clasificación final';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b57_fecha_de_clasificaci_n', 'Fecha de clasificación', 'FECHA', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B57%' AND sd.nombre = 'Clasificación final';

-- Carrión
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Antecedente epidemiológico', 1 FROM enfermedad WHERE cie10 LIKE 'A44%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_fecha_de_inicio_de_enfermedad', 'Fecha de inicio de enfermedad', 'FECHA', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Antecedente epidemiológico';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_fecha_de_ingreso_al_estudio_o_diagn_stico', 'Fecha de ingreso al estudio o diagnóstico', 'FECHA', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Antecedente epidemiológico';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Síntomas', 2 FROM enfermedad WHERE cie10 LIKE 'A44%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_fiebre', 'Fiebre', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_palidez', 'Palidez', 'BOOLEANO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_cefalea', 'Cefalea', 'BOOLEANO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_malestar_general', 'Malestar general', 'BOOLEANO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_mialgias', 'Mialgias', 'BOOLEANO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_dolor_articular', 'Dolor articular', 'BOOLEANO', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_astenia', 'Astenia', 'BOOLEANO', 0, 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_prurito', 'Prurito', 'BOOLEANO', 0, 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_petequias', 'Petequias', 'BOOLEANO', 0, 0, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_equimosis', 'Equimosis', 'BOOLEANO', 0, 0, 10, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_escalofr_os', 'Escalofríos', 'BOOLEANO', 0, 0, 11, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_mareos', 'Mareos', 'BOOLEANO', 0, 0, 12, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_verrugas', 'Verrugas', 'BOOLEANO', 0, 0, 13, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_lumbalgia', 'Lumbalgia', 'BOOLEANO', 0, 0, 14, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_n_useas', 'Náuseas', 'BOOLEANO', 0, 0, 15, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_v_mitos', 'Vómitos', 'BOOLEANO', 0, 0, 16, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_hiporexia', 'Hiporexia', 'BOOLEANO', 0, 0, 17, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_dolor_abdominal', 'Dolor abdominal', 'BOOLEANO', 0, 0, 18, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_hematoquesia', 'Hematoquesia', 'BOOLEANO', 0, 0, 19, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_melena', 'Melena', 'BOOLEANO', 0, 0, 20, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_diarrea', 'Diarrea', 'BOOLEANO', 0, 0, 21, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_ictericia', 'Ictericia', 'BOOLEANO', 0, 0, 22, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_disuria', 'Disuria', 'BOOLEANO', 0, 0, 23, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_polaquiuria', 'Polaquiuria', 'BOOLEANO', 0, 0, 24, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_coluria', 'Coluria', 'BOOLEANO', 0, 0, 25, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_epigastralgia', 'Epigastralgia', 'BOOLEANO', 0, 0, 26, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_somnolencia', 'Somnolencia', 'BOOLEANO', 0, 0, 27, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_polipnea', 'Polipnea', 'BOOLEANO', 0, 0, 28, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_tos', 'Tos', 'BOOLEANO', 0, 0, 29, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_expectoraci_n', 'Expectoración', 'BOOLEANO', 0, 0, 30, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_dolor_tor_cico', 'Dolor torácico', 'BOOLEANO', 0, 0, 31, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_disnea', 'Disnea', 'BOOLEANO', 0, 0, 32, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_cianosis', 'Cianosis', 'BOOLEANO', 0, 0, 33, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_convulsiones', 'Convulsiones', 'BOOLEANO', 0, 0, 34, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_inyecci_n_conjuntival', 'Inyección conjuntival', 'BOOLEANO', 0, 0, 35, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_epistaxis', 'Epistaxis', 'BOOLEANO', 0, 0, 36, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_congesti_n_far_ngea', 'Congestión faríngea', 'BOOLEANO', 0, 0, 37, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_odinofagia', 'Odinofagia', 'BOOLEANO', 0, 0, 38, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_fotofobia', 'Fotofobia', 'BOOLEANO', 0, 0, 39, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_excitaci_n_psicomotriz', 'Excitación psicomotriz', 'BOOLEANO', 0, 0, 40, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Síntomas';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Funciones vitales', 3 FROM enfermedad WHERE cie10 LIKE 'A44%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_temperatura_c_', 'Temperatura (°C)', 'NUMERO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Funciones vitales';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_presi_n_arterial', 'Presión arterial', 'TEXTO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Funciones vitales';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_frecuencia_respiratoria', 'Frecuencia respiratoria', 'NUMERO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Funciones vitales';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_pulso', 'Pulso', 'NUMERO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Funciones vitales';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_peso_kg_', 'Peso (kg)', 'NUMERO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Funciones vitales';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Signos generales', 4 FROM enfermedad WHERE cie10 LIKE 'A44%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_l_cido', 'Lúcido', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Signos generales';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_orientado_en_tiempo', 'Orientado en tiempo', 'BOOLEANO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Signos generales';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_orientado_en_espacio', 'Orientado en espacio', 'BOOLEANO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Signos generales';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_orientado_en_persona', 'Orientado en persona', 'BOOLEANO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Signos generales';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_estado_general', 'Estado general', 'TEXTO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Signos generales';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_estado_de_nutrici_n', 'Estado de nutrición', 'TEXTO', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Signos generales';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_estado_de_hidrataci_n', 'Estado de hidratación', 'TEXTO', 0, 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Signos generales';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Piel', 5 FROM enfermedad WHERE cie10 LIKE 'A44%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_palidez', 'Palidez', 'TEXTO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Piel';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_petequias', 'Petequias', 'BOOLEANO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Piel';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_petequias_localizaci_n', 'Petequias: localización', 'TEXTO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Piel';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_equimosis', 'Equimosis', 'BOOLEANO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Piel';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_equimosis_localizaci_n', 'Equimosis: localización', 'TEXTO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Piel';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_lesiones_eruptivas', 'Lesiones eruptivas', 'MATRIZ', 0, 0, 6, '{\"columnas\":[\"N.°\",\"Cara\",\"Cuello\",\"Tronco\",\"Ext. superior\",\"Ext. inferior\",\"Sangrante\"],\"filas\":[\"Miliares\",\"Mulares\",\"Nodulares\"]}', NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Piel';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Tejido celular subcutáneo', 6 FROM enfermedad WHERE cie10 LIKE 'A44%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_sin_alteraciones', 'Sin alteraciones', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Tejido celular subcutáneo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_edema_miembros_inferiores', 'Edema: Miembros inferiores', 'BOOLEANO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Tejido celular subcutáneo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_edema_miembros_superiores', 'Edema: Miembros superiores', 'BOOLEANO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Tejido celular subcutáneo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_edema_palpebral', 'Edema: Palpebral', 'BOOLEANO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Tejido celular subcutáneo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_edema_lumbosacro', 'Edema: Lumbosacro', 'BOOLEANO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Tejido celular subcutáneo';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_edema_otro', 'Edema: Otro', 'TEXTO', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Tejido celular subcutáneo';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Ganglios linfáticos', 7 FROM enfermedad WHERE cie10 LIKE 'A44%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_ganglios_linf_ticos', 'Ganglios linfáticos', 'MATRIZ', 0, 0, 1, '{\"columnas\":[\"N.°\",\"Tamaño (mm)\",\"Móviles (Sí\\/No)\",\"Dolorosos (Sí\\/No)\"],\"filas\":[\"Axilares\",\"Inguinales\",\"Cervicales\",\"Epitrocleares\"]}', NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Ganglios linfáticos';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Hospitalización', 8 FROM enfermedad WHERE cie10 LIKE 'A44%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_hospitalizado', 'Hospitalizado', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_fecha', 'Fecha', 'FECHA', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_d_as_de_hospitalizaci_n', 'Días de hospitalización', 'NUMERO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Hospitalización';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'a44_condici_n_de_alta', 'Condición de alta', 'TEXTO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'A44%' AND sd.nombre = 'Hospitalización';

-- Viruela del mono
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Datos del paciente (adicionales)', 1 FROM enfermedad WHERE cie10 LIKE 'B04%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_poblaci_n_espec_fica_hsh', 'Población específica: HSH', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Datos del paciente (adicionales)';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_poblaci_n_espec_fica_mujer_transg_nero', 'Población específica: Mujer transgénero', 'BOOLEANO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Datos del paciente (adicionales)';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_poblaci_n_espec_fica_trabajador_a_sexual', 'Población específica: Trabajador(a) sexual', 'BOOLEANO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Datos del paciente (adicionales)';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_poblaci_n_espec_fica_privado_de_la_libertad', 'Población específica: Privado de la libertad', 'BOOLEANO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Datos del paciente (adicionales)';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_poblaci_n_espec_fica_personal_de_salud', 'Población específica: Personal de salud', 'BOOLEANO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Datos del paciente (adicionales)';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_poblaci_n_espec_fica_otro', 'Población específica: Otro', 'TEXTO', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Datos del paciente (adicionales)';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_orientaci_n_sexual', 'Orientación sexual', 'TEXTO', 0, 1, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Datos del paciente (adicionales)';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Lugar probable de infección y exposición', 2 FROM enfermedad WHERE cie10 LIKE 'B04%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_lugares_a_los_que_asisti_discoteca', 'Lugares a los que asistió: Discoteca', 'BOOLEANO', 0, 0, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_lugares_a_los_que_asisti_sauna', 'Lugares a los que asistió: Sauna', 'BOOLEANO', 0, 0, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_lugares_a_los_que_asisti_bar', 'Lugares a los que asistió: Bar', 'BOOLEANO', 0, 0, 3, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_lugares_a_los_que_asisti_club_sexual', 'Lugares a los que asistió: Club sexual', 'BOOLEANO', 0, 0, 4, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_lugares_a_los_que_asisti_evento_masivo', 'Lugares a los que asistió: Evento masivo', 'BOOLEANO', 0, 0, 5, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_lugares_a_los_que_asisti_fiesta', 'Lugares a los que asistió: Fiesta', 'BOOLEANO', 0, 0, 6, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_lugares_a_los_que_asisti_ee_ss_', 'Lugares a los que asistió: EE.SS.', 'BOOLEANO', 0, 0, 7, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_lugares_a_los_que_asisti_otro', 'Lugares a los que asistió: Otro', 'TEXTO', 0, 0, 8, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_exposici_n_relaciones_sexuales_con_desconocido_a_o_parej', 'Exposición: Relaciones sexuales con desconocido(a) o parejas múltiples', 'BOOLEANO', 0, 1, 9, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_exposici_n_relaciones_con_trabajador_a_sexual', 'Exposición: Relaciones con trabajador(a) sexual', 'BOOLEANO', 0, 1, 10, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_exposici_n_relaciones_con_su_pareja_con_exantema_o_lesio', 'Exposición: Relaciones con su pareja (con exantema o lesiones)', 'BOOLEANO', 0, 1, 11, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_exposici_n_relaciones_con_su_pareja_sin_molestias_cl_nic', 'Exposición: Relaciones con su pareja (sin molestias clínicas)', 'BOOLEANO', 0, 1, 12, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_exposici_n_contacto_con_personas_con_exantemas_o_lesione', 'Exposición: Contacto con personas con exantemas o lesiones en piel', 'BOOLEANO', 0, 1, 13, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_exposici_n_brind_cuidados_de_un_caso_probable_o_confirma', 'Exposición: Brindó cuidados de un caso probable o confirmado en domicilio', 'BOOLEANO', 0, 1, 14, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_exposici_n_manipul_material_contaminado_en_ee_ss_', 'Exposición: Manipuló material contaminado en EE.SS.', 'BOOLEANO', 0, 1, 15, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_exposici_n_se_realiz_procedimiento_m_dico_o_de_laborator', 'Exposición: Se realizó procedimiento médico o de laboratorio', 'BOOLEANO', 0, 1, 16, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_exposici_n_se_realiz_tatuaje_piercing_o_acupuntura', 'Exposición: Se realizó tatuaje, piercing o acupuntura', 'BOOLEANO', 0, 1, 17, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_exposici_n_comparti_jeringas', 'Exposición: Compartió jeringas', 'BOOLEANO', 0, 1, 18, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_exposici_n_con_caso_probable_o_confirmado_', '¿Exposición con caso probable o confirmado?', 'TEXTO', 0, 0, 19, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_contacto_directo_y_frecuente_con_animales_', '¿Contacto directo y frecuente con animales?', 'BOOLEANO', 0, 0, 20, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_animales_especificar_', 'Animales (especificar)', 'TEXTO', 0, 0, 21, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Lugar probable de infección y exposición';
INSERT INTO seccion_def (enfermedad_id, nombre, orden) SELECT id, 'Antecedentes', 3 FROM enfermedad WHERE cie10 LIKE 'B04%';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_estado_inmunol_gico_deprimido', 'Estado inmunológico deprimido', 'TEXTO', 0, 1, 1, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Antecedentes';
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, sensible, orden, config, catalogo_id) 
            SELECT sd.id, 'b04_por_enfermedad_medicaci_n', 'Por enfermedad / medicación', 'TEXTO', 0, 1, 2, NULL, NULL
            FROM seccion_def sd
            JOIN enfermedad e ON sd.enfermedad_id = e.id
            WHERE e.cie10 LIKE 'B04%' AND sd.nombre = 'Antecedentes';
