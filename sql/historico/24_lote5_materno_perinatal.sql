-- 24_lote5_materno_perinatal.sql
-- LOTE 5 de DEFINICION_FICHAS_B_C_D.md: Gestante con VIH/niño expuesto,
-- VIH/SIDA, Sífilis materna y congénita, Síndrome de rubéola congénita.
-- Reemplaza los stubs de 2-3 campos por sección (dejados por un intento
-- previo con nombres de columna incorrectos) por la definición completa.
-- Sin casos creados para estas 4 enfermedades: DELETE en cascada seguro.
-- Catálogos 1=sexo(M/F) y 2=si_no(SI/NO/IGN) ya existen y se reutilizan.

DELETE FROM seccion_def WHERE enfermedad_id IN (17, 18, 19, 20);

-- ============================================================================
-- 5.1 Gestante con VIH y niño expuesto (id=18) — multi_sujeto, MADRE/NINO_EXPUESTO
-- Todos los campos sensible=1 (VIH).
-- ============================================================================
UPDATE enfermedad SET usa_muestras = 1 WHERE id = 18;

INSERT INTO catalogo (nombre) VALUES ('Momento de diagnóstico VIH - gestante');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'PREVIO', 'Previo a la gestación actual', 1),
  (@cat, 'DURANTE', 'Durante la actual gestación', 2);
SET @cat_momento_dx = @cat;

INSERT INTO catalogo (nombre) VALUES ('Momento del diagnóstico durante gestación');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'APN', 'Atención prenatal (APN)', 1),
  (@cat, 'PARTO', 'Trabajo de parto', 2),
  (@cat, 'PUERPERIO', 'Puerperio', 3),
  (@cat, 'POST_PUERPERIO', 'Posterior al puerperio', 4),
  (@cat, 'ABORTO', 'Por aborto', 5);
SET @cat_momento_durante = @cat;

INSERT INTO catalogo (nombre) VALUES ('Institución del EE.SS. del parto');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MINSA', 'MINSA', 1),
  (@cat, 'ESSALUD', 'EsSalud', 2),
  (@cat, 'FFAA_FFPP', 'FFAA/FFPP', 3),
  (@cat, 'PRIVADO', 'Privado', 4),
  (@cat, 'OTRO', 'Otro', 5);
SET @cat_institucion_parto = @cat;

INSERT INTO catalogo (nombre) VALUES ('ARV recibido - niño expuesto');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'AZT', 'AZT', 1),
  (@cat, 'AZT_NVP', 'AZT + NVP', 2),
  (@cat, 'OTRO', 'Otro', 3);
SET @cat_arv_nino = @cat;

INSERT INTO catalogo (nombre) VALUES ('Estado serológico final - niño expuesto');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'INFECTADO', 'Infectado por VIH', 1),
  (@cat, 'NO_INFECTADO', 'No infectado por VIH', 2),
  (@cat, 'INDETERMINADO', 'Estado indeterminado', 3);
SET @cat_estado_serologico = @cat;

INSERT INTO catalogo (nombre) VALUES ('Motivo de estado indeterminado');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SEGUIMIENTO', 'Continúa en seguimiento', 1),
  (@cat, 'FALLECIDO', 'Fallecido antes de poder determinar su estado', 2),
  (@cat, 'ABANDONO', 'Abandonó el seguimiento / seguimiento irregular', 3),
  (@cat, 'REFERIDO', 'Referido', 4);
SET @cat_motivo_indeterminado = @cat;

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (18, 'Sección I — Gestante con VIH', 1);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'codigo_ficha', 'Código', 'TEXTO', 1, 0, 1, 'MADRE', NULL),
  (@sec, 'fecha_reporte_fie', 'Fecha de reporte de la ficha de investigación epidemiológica', 'FECHA', 2, 0, 1, 'MADRE', NULL),
  (@sec, 'momento_diagnostico', 'Momento de diagnóstico de infección por VIH', 'SELECT', 3, 1, 1, 'MADRE', @cat_momento_dx),
  (@sec, 'anio_diagnostico', 'Año de diagnóstico (si es previo)', 'NUMERO', 4, 0, 1, 'MADRE', NULL),
  (@sec, 'momento_diagnostico_durante', 'Si es durante la gestación', 'SELECT', 5, 0, 1, 'MADRE', @cat_momento_durante),
  (@sec, 'prueba_tamizaje_1', 'Prueba de tamizaje N.° 1', 'FECHA', 6, 0, 1, 'MADRE', NULL),
  (@sec, 'prueba_tamizaje_2', 'Prueba de tamizaje N.° 2', 'FECHA', 7, 0, 1, 'MADRE', NULL),
  (@sec, 'prueba_confirmatoria', 'Prueba confirmatoria', 'FECHA', 8, 0, 1, 'MADRE', NULL),
  (@sec, 'fur', 'FUR', 'FECHA', 9, 1, 1, 'MADRE', NULL),
  (@sec, 'recibio_apn', '¿Recibió APN?', 'BOOLEANO', 10, 0, 1, 'MADRE', NULL),
  (@sec, 'embarazo_multiple', '¿Embarazo múltiple?', 'BOOLEANO', 11, 0, 1, 'MADRE', NULL),
  (@sec, 'recibio_arv', '¿Recibió ARV?', 'BOOLEANO', 12, 0, 1, 'MADRE', NULL),
  (@sec, 'fecha_inicio_arv', 'Fecha de inicio de ARV', 'FECHA', 13, 0, 1, 'MADRE', NULL),
  (@sec, 'abandono_arv', '¿Abandonó terapia ARV?', 'BOOLEANO', 14, 0, 1, 'MADRE', NULL),
  (@sec, 'recibe_targa', '¿Recibe terapia triple / TARGA?', 'BOOLEANO', 15, 0, 1, 'MADRE', NULL),
  (@sec, 'nacidos_vivos', 'N.° de nacidos vivos', 'NUMERO', 16, 0, 1, 'MADRE', NULL),
  (@sec, 'obitos_fetales', 'N.° de óbitos fetales', 'NUMERO', 17, 0, 1, 'MADRE', NULL),
  (@sec, 'aborto', 'Aborto', 'BOOLEANO', 18, 0, 1, 'MADRE', NULL),
  (@sec, 'parto_cesarea', '¿Parto por cesárea?', 'BOOLEANO', 19, 0, 1, 'MADRE', NULL),
  (@sec, 'eess_parto_nombre', 'EE.SS. del parto (DIRESA / nombre)', 'TEXTO', 20, 0, 1, 'MADRE', NULL),
  (@sec, 'eess_parto_institucion', 'Institución del EE.SS. del parto', 'SELECT', 21, 0, 1, 'MADRE', @cat_institucion_parto),
  (@sec, 'fecha_parto', 'Fecha del parto', 'FECHA', 22, 0, 1, 'MADRE', NULL),
  (@sec, 'carga_viral_indetectable', '¿Carga viral indetectable?', 'BOOLEANO', 23, 0, 1, 'MADRE', NULL),
  (@sec, 'abandona_seguimiento_madre', '¿Abandona seguimiento?', 'BOOLEANO', 24, 0, 1, 'MADRE', NULL),
  (@sec, 'gestante_fallece', '¿La gestante fallece?', 'BOOLEANO', 25, 0, 1, 'MADRE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (18, 'Sección II — Niño nacido expuesto al VIH', 2);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'codigo_nino', 'Código', 'TEXTO', 1, 1, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'dni_nino', 'DNI', 'TEXTO', 2, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'sexo_nino', 'Sexo', 'SELECT', 3, 1, 1, 'NINO_EXPUESTO', 1),
  (@sec, 'apellidos_nombres_nino', 'Apellidos y nombres', 'TEXTO', 4, 1, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'fecha_nacimiento_nino', 'Fecha de nacimiento', 'FECHA', 5, 1, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'codigo_madre', 'Código de la madre', 'TEXTO', 6, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'dni_madre', 'DNI de la madre', 'TEXTO', 7, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'recibio_arv_nino', '¿Recibió ARV?', 'BOOLEANO', 8, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'fecha_inicio_arv_nino', 'Fecha de inicio de ARV', 'FECHA', 9, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'abandono_arv_nino', '¿Abandonó terapia ARV?', 'BOOLEANO', 10, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'arv_recibido', 'ARV recibido', 'SELECT', 11, 0, 1, 'NINO_EXPUESTO', @cat_arv_nino),
  (@sec, 'dias_tomo_arv', 'N.° de días que tomó ARV', 'NUMERO', 12, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'profilaxis_arv_nt', '¿Profilaxis ARV de acuerdo a NT vigente?', 'BOOLEANO', 13, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'sucedaneos_leche', '¿Sucedáneos de leche materna?', 'BOOLEANO', 14, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'meses_sucedaneos', 'N.° de meses que los recibió', 'NUMERO', 15, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'tomo_leche_materna', '¿Tomó leche materna?', 'BOOLEANO', 16, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'estado_serologico_final', 'Estado serológico final', 'SELECT', 17, 0, 1, 'NINO_EXPUESTO', @cat_estado_serologico),
  (@sec, 'motivo_estado_indeterminado', 'Motivo de estado indeterminado', 'SELECT', 18, 0, 1, 'NINO_EXPUESTO', @cat_motivo_indeterminado),
  (@sec, 'pruebas_diagnosticas_nino', 'Pruebas diagnósticas (1.er PCR, 2.º PCR, ELISA, confirmatoria: fecha y resultado)', 'TEXTAREA', 19, 0, 1, 'NINO_EXPUESTO', NULL),
  (@sec, 'observaciones_nino', 'Observaciones', 'TEXTAREA', 20, 0, 1, 'NINO_EXPUESTO', NULL);

-- ============================================================================
-- 5.2 VIH / SIDA — notificación individual (id=19)
-- Ficha con código de paciente, no nombre completo. Todos los campos sensible=1.
-- Grado de instrucción se reutiliza como catálogo compartido en este lote.
-- ============================================================================
UPDATE enfermedad SET usa_muestras = 1 WHERE id = 19;

INSERT INTO catalogo (nombre) VALUES ('Grado de instrucción');
SET @cat_grado_instruccion = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_grado_instruccion, 'ANALFABETA', 'Analfabeta', 1),
  (@cat_grado_instruccion, 'PRIMARIA', 'Primaria', 2),
  (@cat_grado_instruccion, 'SECUNDARIA', 'Secundaria', 3),
  (@cat_grado_instruccion, 'TECNICA', 'Técnica', 4),
  (@cat_grado_instruccion, 'UNIVERSITARIA', 'Universitaria', 5);

INSERT INTO catalogo (nombre) VALUES ('Motivo de notificación - VIH/SIDA');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'INFECCION_VIH', 'Infección por VIH', 1),
  (@cat, 'NINO_EXPUESTO', 'Niño nacido expuesto al VIH', 2),
  (@cat, 'NINO_EXPUESTO_NO_INFECTADO', 'Niño nacido expuesto, no infectado por VIH', 3);
SET @cat_motivo_notif = @cat;

INSERT INTO catalogo (nombre) VALUES ('Subtipo de infección VIH');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ESTADIO_SIDA', 'Estadio SIDA', 1),
  (@cat, 'GESTANTE_VIH', 'Gestante con VIH', 2),
  (@cat, 'NINO_INFECTADO', 'Niño nacido expuesto, infectado por VIH', 3),
  (@cat, 'INICIO_TARGA', 'Inicio de TARGA', 4),
  (@cat, 'FALLECIDO', 'Fallecido con VIH o SIDA', 5);
SET @cat_subtipo_vih = @cat;

INSERT INTO catalogo (nombre) VALUES ('Estadio de infección VIH al diagnóstico');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ESTADIO_1', 'Estadio 1', 1),
  (@cat, 'ESTADIO_2', 'Estadio 2 (Avanzado)', 2),
  (@cat, 'ESTADIO_3', 'Estadio 3 (SIDA)', 3),
  (@cat, 'DESCONOCIDO', 'Desconocido', 4);
SET @cat_estadio_dx = @cat;

INSERT INTO catalogo (nombre) VALUES ('Condición especial - VIH/SIDA');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'TRABAJADOR_SEXUAL', 'Trabajador(a) sexual', 1),
  (@cat, 'PRIVADO_LIBERTAD', 'Privado de libertad', 2),
  (@cat, 'UDI', 'Usuario de drogas inyectables', 3),
  (@cat, 'UDNI', 'Usuario de drogas no inyectables', 4);
SET @cat_condicion_especial = @cat;

INSERT INTO catalogo (nombre) VALUES ('Sexo al nacer');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MUJER', 'Mujer', 1),
  (@cat, 'HOMBRE', 'Hombre', 2);
SET @cat_sexo_nacer = @cat;

INSERT INTO catalogo (nombre) VALUES ('Identidad de género');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'FEMENINO', 'Femenino', 1),
  (@cat, 'MASCULINO', 'Masculino', 2),
  (@cat, 'TRANS_MF', 'Transgénero masculino a femenino', 3),
  (@cat, 'TRANS_FM', 'Transgénero femenino a masculino', 4),
  (@cat, 'OTRO', 'Otro', 5),
  (@cat, 'DESCONOCIDO', 'Desconocido', 6);
SET @cat_identidad_genero = @cat;

INSERT INTO catalogo (nombre) VALUES ('Antecedentes de relaciones sexuales');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'RS_HOMBRES', 'RS con hombres', 1),
  (@cat, 'RS_MUJERES', 'RS con mujeres', 2),
  (@cat, 'RS_AMBOS', 'RS con ambos sexos', 3),
  (@cat, 'DESCONOCIDO', 'Desconocido', 4);
SET @cat_antecedentes_rs = @cat;

INSERT INTO catalogo (nombre) VALUES ('Vía de transmisión - VIH/SIDA');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SEXUAL_HETERO', 'Sexual: heterosexual', 1),
  (@cat, 'SEXUAL_HOMO', 'Sexual: homosexual', 2),
  (@cat, 'SEXUAL_BI', 'Sexual: bisexual', 3),
  (@cat, 'SEXUAL_ND', 'Sexual: no determinado', 4),
  (@cat, 'PARENTERAL_TRANSFUSION', 'Parenteral: transfusión de sangre y/o derivados', 5),
  (@cat, 'PARENTERAL_UDI', 'Parenteral: compartir agujas / UDI', 6),
  (@cat, 'PARENTERAL_ACCIDENTE', 'Parenteral: accidente con material contaminado', 7),
  (@cat, 'PARENTERAL_TRASPLANTE', 'Parenteral: trasplante de órganos o tejidos', 8),
  (@cat, 'PARENTERAL_ND', 'Parenteral: no determinado', 9),
  (@cat, 'VERTICAL', 'Madre-niño (vertical)', 10),
  (@cat, 'DESCONOCIDA', 'Desconocida', 11);
SET @cat_via_transmision = @cat;

INSERT INTO catalogo (nombre) VALUES ('Criterio diagnóstico de SIDA');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CD4', 'CD4', 1),
  (@cat, 'ENFERMEDAD_INDICADORA', 'Enfermedad indicadora', 2);
SET @cat_criterio_sida = @cat;

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (19, 'Identificación', 1);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'codigo_paciente', 'Código del paciente (iniciales AP, AM, N1, N2 + fecha de nacimiento)', 'TEXTO', 1, 1, 1, 'CASO_INDICE', NULL),
  (@sec, 'doc_identidad_vih', 'DNI / CE / Pasaporte', 'TEXTO', 2, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'motivo_notificacion', 'Motivo de notificación', 'SELECT', 3, 1, 1, 'CASO_INDICE', @cat_motivo_notif),
  (@sec, 'subtipo_infeccion_vih', 'Subtipo (si el motivo es infección por VIH)', 'MULTISELECT', 4, 0, 1, 'CASO_INDICE', @cat_subtipo_vih),
  (@sec, 'estadio_diagnostico', 'Estadio de infección VIH al momento del diagnóstico', 'SELECT', 5, 0, 1, 'CASO_INDICE', @cat_estadio_dx);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (19, 'Datos sociodemográficos', 2);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'grado_instruccion_vih', 'Grado de instrucción', 'SELECT', 1, 0, 1, 'CASO_INDICE', @cat_grado_instruccion),
  (@sec, 'condicion_especial', 'Condición especial', 'MULTISELECT', 2, 0, 1, 'CASO_INDICE', @cat_condicion_especial),
  (@sec, 'sexo_al_nacer', 'Sexo al nacer', 'SELECT', 3, 1, 1, 'CASO_INDICE', @cat_sexo_nacer),
  (@sec, 'identidad_genero', 'Identidad de género', 'SELECT', 4, 0, 1, 'CASO_INDICE', @cat_identidad_genero),
  (@sec, 'antecedentes_rs', 'Antecedentes de relaciones sexuales', 'SELECT', 5, 0, 1, 'CASO_INDICE', @cat_antecedentes_rs);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (19, 'Vía de transmisión', 3);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'via_transmision', 'Vía de transmisión', 'SELECT', 1, 1, 1, 'CASO_INDICE', @cat_via_transmision);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (19, 'TARGA y estadio SIDA', 4);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'fecha_inicio_targa', 'Fecha de inicio de tratamiento', 'FECHA', 1, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'fecha_diagnostico_sida', 'Estadio SIDA: fecha de diagnóstico', 'FECHA', 2, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'criterio_diagnostico_sida', 'Criterio diagnóstico de SIDA', 'SELECT', 3, 0, 1, 'CASO_INDICE', @cat_criterio_sida),
  (@sec, 'enfermedades_indicadoras_sida', 'Enfermedades indicadoras de SIDA (descripción + CIE-10)', 'TEXTAREA', 4, 0, 1, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (19, 'Coinfección', 5);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'coinf_tuberculosis', 'Tuberculosis', 'BOOLEANO', 1, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'fecha_dx_tuberculosis', 'Fecha de diagnóstico de tuberculosis', 'FECHA', 2, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'coinf_hepatitis_b', 'Hepatitis B', 'BOOLEANO', 3, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'fecha_dx_hepatitis_b', 'Fecha de diagnóstico de hepatitis B', 'FECHA', 4, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'coinf_hepatitis_c', 'Hepatitis C', 'BOOLEANO', 5, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'fecha_dx_hepatitis_c', 'Fecha de diagnóstico de hepatitis C', 'FECHA', 6, 0, 1, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (19, 'Defunción', 6);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'fecha_defuncion_vih', 'Fecha de defunción', 'FECHA', 1, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'defuncion_relacionada_sida', '¿Defunción relacionada a SIDA?', 'BOOLEANO', 2, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'causa_muerte_vih', 'Causa de muerte', 'TEXTO', 3, 0, 1, 'CASO_INDICE', NULL);

-- ============================================================================
-- 5.3 Sífilis materna y congénita (id=20) — multi_sujeto, MADRE/RECIEN_NACIDO
-- ============================================================================
INSERT INTO catalogo (nombre) VALUES ('Investigación de - Sífilis');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MATERNA', 'Sífilis materna', 1),
  (@cat, 'CONGENITA', 'Sífilis congénita', 2);
SET @cat_investigacion_sifilis = @cat;

INSERT INTO catalogo (nombre) VALUES ('Nivel del establecimiento');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'I1', 'I-1', 1), (@cat, 'I2', 'I-2', 2), (@cat, 'I3', 'I-3', 3), (@cat, 'I4', 'I-4', 4),
  (@cat, 'II1', 'II-1', 5), (@cat, 'II2', 'II-2', 6), (@cat, 'III1', 'III-1', 7);
SET @cat_nivel_eess = @cat;

INSERT INTO catalogo (nombre) VALUES ('Resultado prueba treponémica/no treponémica');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'REACTIVO', 'Reactivo', 1),
  (@cat, 'NO_REACTIVO', 'No reactivo', 2),
  (@cat, 'DESCONOCIDO', 'Desconocido', 3);
SET @cat_resultado_prueba = @cat;

INSERT INTO catalogo (nombre) VALUES ('Motivo de tratamiento inadecuado - sífilis');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SIN_PENICILINA', 'Tratamiento sin penicilina', 1),
  (@cat, 'ULTIMOS_30_DIAS', 'Tratamiento durante los 30 días previos al parto', 2),
  (@cat, 'NO_INICIO', 'No inició tratamiento durante la gestación', 3),
  (@cat, 'INCOMPLETO', 'Tratamiento incompleto (1 ó 2 dosis)', 4);
SET @cat_motivo_no_tratamiento = @cat;

INSERT INTO catalogo (nombre) VALUES ('Clasificación de caso de sífilis en la gestante');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'PROBABLE', 'Probable', 1),
  (@cat, 'CONFIRMADO', 'Confirmado', 2),
  (@cat, 'DESCARTADO_FALSO_POSITIVO', 'Descartado (falso positivo)', 3),
  (@cat, 'DESCARTADO_MEMORIA', 'Descartado (sífilis memoria)', 4);
SET @cat_clasif_sifilis_gestante = @cat;

INSERT INTO catalogo (nombre) VALUES ('Lugar del parto - sífilis');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'EESS', 'Establecimiento de salud', 1),
  (@cat, 'DOMICILIO', 'Domicilio', 2);
SET @cat_lugar_parto = @cat;

INSERT INTO catalogo (nombre) VALUES ('Estado vital al nacer');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'VIVO', 'Vivo', 1),
  (@cat, 'NACIO_VIVO_FALLECIO', 'Nació vivo, luego falleció', 2),
  (@cat, 'MORTINATO', 'Mortinato', 3),
  (@cat, 'ABORTO', 'Aborto', 4);
SET @cat_estado_vital = @cat;

INSERT INTO catalogo (nombre) VALUES ('Criterios de caso de sífilis congénita');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MADRE_SIN_TRATAMIENTO', 'Madre con sífilis que no recibió tratamiento o fue tratada inadecuadamente', 1),
  (@cat, 'TITULOS_4X', 'Títulos no treponémicos cuatro veces mayores que los de la madre', 2),
  (@cat, 'CLINICA_SUGESTIVA', 'Niño con manifestaciones clínicas sugestivas', 3),
  (@cat, 'TREPONEMA_DEMOSTRADO', 'Demostración de Treponema pallidum en lesiones, placenta, cordón o autopsia', 4),
  (@cat, 'MAYOR_2_ANOS', 'Niño mayor de 2 años con signos de sífilis secundaria, descartado abuso/contacto sexual', 5);
SET @cat_criterios_congenita = @cat;

INSERT INTO catalogo (nombre) VALUES ('Tratamiento del niño - sífilis congénita');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'PENICILINA_10DIAS', 'Sí, con penicilina G sódica o procaínica por ≥10 días', 1),
  (@cat, 'PENICILINA_BENZATINICA', 'Sí, con penicilina benzatínica × 1 dosis', 2),
  (@cat, 'OTRO_TRATAMIENTO', 'Sí, con otro tratamiento', 3),
  (@cat, 'NO_RECIBIO', 'No recibió tratamiento', 4),
  (@cat, 'DESCONOCIDO', 'Desconocido', 5);
SET @cat_tratamiento_nino_sifilis = @cat;

INSERT INTO catalogo (nombre) VALUES ('Clasificación final - sífilis congénita');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CONGENITA', 'Sífilis congénita', 1),
  (@cat, 'EXPUESTO_NO_INFECTADO', 'Niño expuesto a sífilis, no infectado', 2);
SET @cat_clasif_final_nino = @cat;

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (20, 'Encabezado', 1);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'investigacion_de_sifilis', 'Investigación de', 'MULTISELECT', 1, 1, 0, 'CASO_INDICE', @cat_investigacion_sifilis),
  (@sec, 'nivel_establecimiento_sifilis', 'Nivel del establecimiento', 'SELECT', 2, 0, 0, 'CASO_INDICE', @cat_nivel_eess);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (20, 'Sección II — Sífilis materna', 2);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'edad_madre_sifilis', 'Edad', 'NUMERO', 1, 0, 0, 'MADRE', NULL),
  (@sec, 'fur_sifilis', 'FUR (o desconocido)', 'FECHA', 2, 0, 0, 'MADRE', NULL),
  (@sec, 'recibio_apn_sifilis', '¿Recibió atención prenatal?', 'SELECT', 3, 0, 0, 'MADRE', 2),
  (@sec, 'fecha_primer_control_prenatal', 'Fecha de primer control prenatal (o desconocido)', 'FECHA', 4, 0, 0, 'MADRE', NULL),
  (@sec, 'edad_gestacional_primer_control', 'Edad gestacional en el primer control prenatal (semanas)', 'NUMERO', 5, 0, 0, 'MADRE', NULL),
  (@sec, 'no_trepo_1_fecha', 'Prueba no treponémica (RPR/VDRL) 1.ª: fecha', 'FECHA', 6, 0, 0, 'MADRE', NULL),
  (@sec, 'no_trepo_1_resultado', 'Prueba no treponémica 1.ª: resultado', 'SELECT', 7, 0, 0, 'MADRE', @cat_resultado_prueba),
  (@sec, 'no_trepo_1_titulo', 'Prueba no treponémica 1.ª: título (1:__)', 'TEXTO', 8, 0, 0, 'MADRE', NULL),
  (@sec, 'no_trepo_2_fecha', 'Prueba no treponémica más reciente: fecha', 'FECHA', 9, 0, 0, 'MADRE', NULL),
  (@sec, 'no_trepo_2_resultado', 'Prueba no treponémica más reciente: resultado', 'SELECT', 10, 0, 0, 'MADRE', @cat_resultado_prueba),
  (@sec, 'no_trepo_2_titulo', 'Prueba no treponémica más reciente: título (1:__)', 'TEXTO', 11, 0, 0, 'MADRE', NULL),
  (@sec, 'trepo_1_fecha', 'Prueba treponémica (TPHA/TPPA/FTA/ELISA/Rápida) 1.ª: fecha', 'FECHA', 12, 0, 0, 'MADRE', NULL),
  (@sec, 'trepo_1_resultado', 'Prueba treponémica 1.ª: resultado', 'SELECT', 13, 0, 0, 'MADRE', @cat_resultado_prueba),
  (@sec, 'trepo_2_fecha', 'Prueba treponémica más reciente: fecha', 'FECHA', 14, 0, 0, 'MADRE', NULL),
  (@sec, 'trepo_2_resultado', 'Prueba treponémica más reciente: resultado', 'SELECT', 15, 0, 0, 'MADRE', @cat_resultado_prueba),
  (@sec, 'tratamiento_adecuado_sifilis', '¿Fue la madre adecuadamente tratada durante el embarazo?', 'SELECT', 16, 0, 0, 'MADRE', 2),
  (@sec, 'motivo_no_tratamiento', 'Si no, motivo', 'SELECT', 17, 0, 0, 'MADRE', @cat_motivo_no_tratamiento),
  (@sec, 'contactos_tratados', 'Contacto(s) sexual(es) tratado(s)', 'SELECT', 18, 0, 0, 'MADRE', 2),
  (@sec, 'num_contactos_tratados', 'N.° de contactos tratados', 'NUMERO', 19, 0, 0, 'MADRE', NULL),
  (@sec, 'clasificacion_sifilis_gestante', 'Clasificación de caso de sífilis en la gestante', 'SELECT', 20, 1, 0, 'MADRE', @cat_clasif_sifilis_gestante);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (20, 'Sección III — Sífilis congénita', 3);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'fecha_parto_sifilis', 'Fecha de parto / culminación del embarazo (o desconocido)', 'FECHA', 1, 0, 0, 'RECIEN_NACIDO', NULL),
  (@sec, 'lugar_parto_sifilis', 'Lugar del parto', 'SELECT', 2, 0, 0, 'RECIEN_NACIDO', @cat_lugar_parto),
  (@sec, 'estado_vital_nino', 'Estado vital', 'SELECT', 3, 1, 0, 'RECIEN_NACIDO', @cat_estado_vital),
  (@sec, 'fecha_fallecimiento_nino_sifilis', 'Fecha de fallecimiento (o desconocido)', 'FECHA', 4, 0, 0, 'RECIEN_NACIDO', NULL),
  (@sec, 'peso_nacimiento_sifilis', 'Peso al nacimiento (gramos, o desconocido)', 'NUMERO', 5, 0, 0, 'RECIEN_NACIDO', NULL),
  (@sec, 'edad_gestacional_sifilis', 'Edad gestacional estimada (semanas, o desconocido)', 'NUMERO', 6, 0, 0, 'RECIEN_NACIDO', NULL),
  (@sec, 'criterios_sifilis_congenita', 'Criterios de caso de sífilis congénita', 'MULTISELECT', 7, 0, 0, 'RECIEN_NACIDO', @cat_criterios_congenita),
  (@sec, 'tratamiento_nino_sifilis', 'Tratamiento del niño', 'SELECT', 8, 0, 0, 'RECIEN_NACIDO', @cat_tratamiento_nino_sifilis),
  (@sec, 'clasificacion_final_nino_sifilis', 'Clasificación final del niño, mortinato o aborto', 'SELECT', 9, 1, 0, 'RECIEN_NACIDO', @cat_clasif_final_nino);

-- ============================================================================
-- 5.4 Síndrome de rubéola congénita — SRC (id=17) — multi_sujeto, CASO_INDICE(niño)/MADRE
-- Catálogo 2 (si_no: SI/NO/IGN) y 3 (resultado_lab: POS/NEG/IND) reutilizados.
-- ============================================================================
UPDATE enfermedad SET usa_viajes = 1, usa_muestras = 1 WHERE id = 17;

INSERT INTO catalogo (nombre) VALUES ('Fiebre y exantema durante el embarazo');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SI', 'Sí', 1), (@cat, 'NO', 'No', 2), (@cat, 'NO_RECUERDA', 'No recuerda', 3);
SET @cat_fiebre_exantema = @cat;

INSERT INTO catalogo (nombre) VALUES ('Manifestaciones clínicas - SRC');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CATARATAS', 'Cataratas', 1),
  (@cat, 'GLAUCOMA_CONGENITO', 'Glaucoma congénito', 2),
  (@cat, 'RETINOPATIA_PIGMENTARIA', 'Retinopatía pigmentaria', 3),
  (@cat, 'MICROFTALMIA', 'Microftalmia', 4),
  (@cat, 'DEFICIT_AUDICION', 'Déficit de la audición', 5),
  (@cat, 'ESTENOSIS_PULMONAR', 'Estenosis periférica de arteria pulmonar', 6),
  (@cat, 'PERSISTENCIA_CONDUCTO', 'Persistencia del conducto arterioso', 7),
  (@cat, 'COMUNICACION_INTERAURICULAR', 'Comunicación interauricular', 8),
  (@cat, 'OTRA_CARDIOPATIA', 'Otra cardiopatía congénita', 9),
  (@cat, 'PURPURA', 'Púrpura', 10),
  (@cat, 'TROMBOCITOPENIA', 'Trombocitopenia', 11),
  (@cat, 'HEPATOMEGALIA', 'Hepatomegalia', 12),
  (@cat, 'ESPLENOMEGALIA', 'Esplenomegalia', 13),
  (@cat, 'MICROCEFALIA', 'Microcefalia', 14),
  (@cat, 'MENINGOENCEFALITIS', 'Meningoencefalitis', 15),
  (@cat, 'ENF_OSEA_RADIOTRANSPARENCIA', 'Enfermedad ósea de radiotransparencia', 16),
  (@cat, 'RETRASO_PSICOMOTOR', 'Retraso en el desarrollo psicomotor', 17),
  (@cat, 'ICTERICIA_24H', 'Ictericia (dentro de las 24 h del nacimiento)', 18);
SET @cat_manifestaciones_src = @cat;

INSERT INTO catalogo (nombre) VALUES ('Clasificación del caso - SRC');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SOSPECHOSO', 'Sospechoso', 1),
  (@cat, 'CONFIRMADO', 'Confirmado', 2),
  (@cat, 'DESCARTADO', 'Descartado', 3),
  (@cat, 'INFECCION_CONGENITA', 'Infección congénita por el virus de la rubéola', 4);
SET @cat_clasificacion_src = @cat;

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (17, 'Antecedentes del paciente', 1);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'nacio_prematuro', '¿Nació prematuro?', 'BOOLEANO', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'edad_gestacional_parto_src', 'Edad gestacional al parto (semanas)', 'NUMERO', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'peso_al_nacer_src', 'Peso al nacer (gramos)', 'NUMERO', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'apgar_1_src', 'APGAR 1\'', 'NUMERO', 4, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'apgar_5_src', 'APGAR 5\'', 'NUMERO', 5, 0, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (17, 'Antecedentes de la madre', 2);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'doc_madre_src', 'Tipo y N.° de documento', 'TEXTO', 1, 0, 0, 'MADRE', NULL),
  (@sec, 'apellidos_nombres_madre_src', 'Apellidos y nombres', 'TEXTO', 2, 1, 0, 'MADRE', NULL),
  (@sec, 'edad_madre_src', 'Edad', 'NUMERO', 3, 0, 0, 'MADRE', NULL),
  (@sec, 'fecha_nacimiento_madre_src', 'Fecha de nacimiento', 'FECHA', 4, 0, 0, 'MADRE', NULL),
  (@sec, 'nacionalidad_madre_src', 'Nacionalidad', 'TEXTO', 5, 0, 0, 'MADRE', NULL),
  (@sec, 'ocupacion_madre_src', 'Ocupación', 'TEXTO', 6, 0, 0, 'MADRE', NULL),
  (@sec, 'vacunada_rubeola', '¿Vacunada contra la rubéola?', 'SELECT', 7, 0, 0, 'MADRE', 2),
  (@sec, 'fecha_vacuna_rubeola', 'Fecha de vacunación', 'FECHA', 8, 0, 0, 'MADRE', NULL),
  (@sec, 'fiebre_exantema_embarazo', '¿Presentó fiebre y exantema maculopapular durante el embarazo?', 'SELECT', 9, 0, 0, 'MADRE', @cat_fiebre_exantema),
  (@sec, 'semana_gestacion_fiebre_exantema', 'Semana de gestación (fiebre/exantema)', 'NUMERO', 10, 0, 0, 'MADRE', NULL),
  (@sec, 'confirmada_lab_madre', '¿Fue confirmada por laboratorio la rubéola de la madre?', 'BOOLEANO', 11, 0, 0, 'MADRE', NULL),
  (@sec, 'expuesta_persona_fiebre_exantema', '¿Se expuso a persona con fiebre y exantema maculopapular?', 'SELECT', 12, 0, 0, 'MADRE', 2),
  (@sec, 'con_quien_expuesta', '¿Con quién?', 'TEXTO', 13, 0, 0, 'MADRE', NULL),
  (@sec, 'semana_gestacion_exposicion', 'Semana de gestación (exposición)', 'NUMERO', 14, 0, 0, 'MADRE', NULL),
  (@sec, 'viajo_fuera_pais_src', '¿Durante el embarazo viajó fuera del país?', 'BOOLEANO', 15, 0, 0, 'MADRE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (17, 'Cuadro clínico', 3);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'manifestaciones_src', 'Manifestaciones', 'GRUPO_SI_NO', 1, 0, 0, 'CASO_INDICE', @cat_manifestaciones_src);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (17, 'Hospitalización y defunción', 4);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'hospitalizado_src', 'Hospitalización', 'BOOLEANO', 1, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'eess_hospitalizacion_src', 'EE.SS.', 'TEXTO', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_hospitalizacion_src', 'Fecha de hospitalización', 'FECHA', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'dx_ingreso_src', 'Dx de ingreso', 'TEXTO', 4, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'defuncion_src', 'Defunción', 'BOOLEANO', 5, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_defuncion_src', 'Fecha de defunción', 'FECHA', 6, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'causa_defuncion_src', 'Causa básica de defunción', 'TEXTO', 7, 0, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (17, 'Laboratorio', 5);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'muestra1_igm_src', '1.ª muestra serológica: IgM', 'SELECT', 1, 0, 0, 'CASO_INDICE', 3),
  (@sec, 'muestra1_igg_src', '1.ª muestra serológica: IgG', 'SELECT', 2, 0, 0, 'CASO_INDICE', 3),
  (@sec, 'muestra1_titulacion_src', '1.ª muestra serológica: titulación', 'TEXTO', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'muestra2_igm_src', '2.ª muestra serológica: IgM', 'SELECT', 4, 0, 0, 'CASO_INDICE', 3),
  (@sec, 'muestra2_igg_src', '2.ª muestra serológica: IgG', 'SELECT', 5, 0, 0, 'CASO_INDICE', 3),
  (@sec, 'muestra2_titulacion_src', '2.ª muestra serológica: titulación', 'TEXTO', 6, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'hisopado_resultado_src', 'Hisopado nasal y faríngeo: resultado', 'SELECT', 7, 0, 0, 'CASO_INDICE', 3),
  (@sec, 'hisopado_genotipo_src', 'Hisopado nasal y faríngeo: genotipo', 'TEXTO', 8, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'seguimiento_excrecion_viral', 'Seguimiento de excreción viral (solo confirmados: 2 hisopados adicionales con 1 mes de intervalo)', 'TEXTAREA', 9, 0, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (17, 'Clasificación del caso', 6);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'clasificacion_src', 'Clasificación del caso', 'SELECT', 1, 1, 0, 'CASO_INDICE', @cat_clasificacion_src);
