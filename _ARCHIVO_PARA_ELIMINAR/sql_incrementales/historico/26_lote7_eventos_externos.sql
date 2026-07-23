-- 26_lote7_eventos_externos.sql
-- LOTE 7 de DEFINICION_FICHAS_B_C_D.md: Violencia familiar y Lesiones por
-- accidentes de tránsito. Sin casos creados: DELETE en cascada seguro.
-- Violencia familiar: TODOS los campos sensible=1 (máxima confidencialidad).

DELETE FROM seccion_def WHERE enfermedad_id IN (21, 22);

-- ============================================================================
-- 7.1 Violencia familiar (id=21) — multi_sujeto, CASO_INDICE(agredida)/AGRESOR
-- ============================================================================
INSERT INTO catalogo (nombre) VALUES ('Institución que registra - Violencia');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MINSA', 'MINSA', 1), (@cat, 'PNP', 'PNP', 2), (@cat, 'DEMUNAS', 'DEMUNAS', 3),
  (@cat, 'CMM', 'CMM', 4), (@cat, 'OTROS', 'Otros', 5);
SET @cat_institucion_registra = @cat;

INSERT INTO catalogo (nombre) VALUES ('Tipo de institución - Violencia');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'HOSPITAL', 'Hospital', 1), (@cat, 'CENTRO_SALUD', 'Centro de salud', 2);
SET @cat_tipo_institucion_violencia = @cat;

INSERT INTO catalogo (nombre) VALUES ('Estado civil');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SOLTERO', 'Soltero(a)', 1), (@cat, 'CASADO', 'Casado(a)', 2), (@cat, 'CONVIVIENTE', 'Conviviente', 3),
  (@cat, 'SEPARADO', 'Separado(a)', 4), (@cat, 'DIVORCIADO', 'Divorciado(a)', 5), (@cat, 'VIUDO', 'Viudo(a)', 6);
SET @cat_estado_civil = @cat;

INSERT INTO catalogo (nombre) VALUES ('Grado de instrucción (nivel)');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ILETRADA', 'Iletrada', 1), (@cat, 'PRIMARIA', 'Primaria', 2),
  (@cat, 'SECUNDARIA', 'Secundaria', 3), (@cat, 'SUPERIOR', 'Superior', 4);
SET @cat_grado_instruccion_nivel = @cat;

INSERT INTO catalogo (nombre) VALUES ('Completa / Incompleta');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'COMPLETA', 'Completa', 1), (@cat, 'INCOMPLETA', 'Incompleta', 2);
SET @cat_completa_incompleta = @cat;

INSERT INTO catalogo (nombre) VALUES ('Vínculo con la víctima');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ESPOSO', 'Esposo(a)', 1), (@cat, 'CONVIVIENTE', 'Conviviente', 2), (@cat, 'HIJO', 'Hijo(a)', 3),
  (@cat, 'PADRE', 'Padre', 4), (@cat, 'MADRE', 'Madre', 5), (@cat, 'OTRO', 'Otro (especificar)', 6);
SET @cat_vinculo_victima = @cat;

INSERT INTO catalogo (nombre) VALUES ('Estado del agresor');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ECUANIME', 'Ecuánime', 1), (@cat, 'DROGAS', 'Efecto de drogas', 2),
  (@cat, 'ALCOHOL', 'Efecto de alcohol', 3), (@cat, 'AMBAS', 'Ambas', 4);
SET @cat_estado_agresor = @cat;

INSERT INTO catalogo (nombre) VALUES ('Tipo de violencia');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'FISICA', 'Física', 1), (@cat, 'PSICOLOGICA', 'Psicológica', 2),
  (@cat, 'SEXUAL_FORZADA', 'Relaciones sexuales forzadas', 3), (@cat, 'ABANDONO', 'Abandono', 4);
SET @cat_tipo_violencia = @cat;

INSERT INTO catalogo (nombre) VALUES ('Medio utilizado en la agresión');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'PROPIO_CUERPO', 'Propio cuerpo', 1), (@cat, 'ARMA_BLANCA', 'Arma blanca', 2),
  (@cat, 'ARMA_FUEGO', 'Arma de fuego', 3), (@cat, 'OBJETO_CONTUNDENTE', 'Objeto contundente', 4);
SET @cat_medio_utilizado = @cat;

INSERT INTO catalogo (nombre) VALUES ('Motivo expresado de la agresión');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'FAMILIARES', 'Familiares', 1), (@cat, 'CELOS', 'Celos', 2), (@cat, 'ECONOMICOS', 'Económicos', 3),
  (@cat, 'LABORALES', 'Laborales', 4), (@cat, 'SIN_MOTIVO', 'Sin motivo', 5), (@cat, 'OTROS', 'Otros', 6);
SET @cat_motivo_agresion = @cat;

INSERT INTO catalogo (nombre) VALUES ('Lugar de la agresión');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CALLE', 'Calle', 1), (@cat, 'CASA', 'Casa', 2), (@cat, 'TRABAJO', 'Centro de trabajo', 3), (@cat, 'OTROS', 'Otros', 4);
SET @cat_lugar_agresion = @cat;

INSERT INTO catalogo (nombre) VALUES ('Medidas tomadas - Violencia');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ATENCION_MEDICA', 'Atención médica', 1), (@cat, 'ATENCION_PSICOLOGICA', 'Atención psicológica', 2),
  (@cat, 'DENUNCIA_JUDICIAL', 'Denuncia judicial', 3), (@cat, 'ASISTENCIA_SOCIAL', 'Asistencia social', 4),
  (@cat, 'DENUNCIA_POLICIAL', 'Denuncia policial', 5), (@cat, 'OTROS', 'Otros (especificar)', 6);
SET @cat_medidas_tomadas = @cat;

INSERT INTO catalogo (nombre) VALUES ('Derivación - Violencia');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MINSA', 'Ministerio de Salud', 1), (@cat, 'POLICIA', 'Policía', 2), (@cat, 'ONG', 'ONG', 3),
  (@cat, 'MINISTERIO_PUBLICO', 'Ministerio Público', 4), (@cat, 'MEDICO_LEGAL', 'Médico legal', 5),
  (@cat, 'DEMUNA', 'DEMUNA', 6), (@cat, 'OTROS', 'Otros', 7);
SET @cat_derivacion = @cat;

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (21, 'Institución que registra', 1);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'institucion_registra', 'Institución', 'SELECT', 1, 1, 1, 'CASO_INDICE', @cat_institucion_registra),
  (@sec, 'otras_instituciones_violencia', '¿Qué otras instituciones? (si aplica)', 'TEXTO', 2, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'tipo_institucion_violencia', 'Tipo', 'SELECT', 3, 0, 1, 'CASO_INDICE', @cat_tipo_institucion_violencia);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (21, 'Datos de la persona agredida', 2);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'doc_identidad_agredida', 'Documento de identidad', 'TEXTO', 1, 1, 1, 'CASO_INDICE', NULL),
  (@sec, 'departamento_residencia_agredida', 'Departamento de residencia en el último año', 'TEXTO', 2, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'edad_agredida', 'Edad', 'NUMERO', 3, 1, 1, 'CASO_INDICE', NULL),
  (@sec, 'sexo_agredida', 'Sexo', 'SELECT', 4, 1, 1, 'CASO_INDICE', 1),
  (@sec, 'gestando_agredida', 'Si es mujer: ¿se encuentra gestando?', 'BOOLEANO', 5, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'estado_civil_agredida', 'Estado civil', 'SELECT', 6, 0, 1, 'CASO_INDICE', @cat_estado_civil),
  (@sec, 'grado_instruccion_agredida', 'Grado de instrucción', 'SELECT', 7, 0, 1, 'CASO_INDICE', @cat_grado_instruccion_nivel),
  (@sec, 'grado_instruccion_completitud_agredida', 'Completa / Incompleta', 'SELECT', 8, 0, 1, 'CASO_INDICE', @cat_completa_incompleta),
  (@sec, 'tiene_empleo_agredida', '¿Tiene empleo remunerado?', 'BOOLEANO', 9, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'ocupacion_agredida', '¿Cuál es su ocupación?', 'TEXTO', 10, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'direccion_agredida', 'Dirección (departamento, provincia, distrito, localidad)', 'TEXTO', 11, 0, 1, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (21, 'Datos de la persona agresora', 3);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'edad_agresor', 'Edad', 'NUMERO', 1, 0, 1, 'AGRESOR', NULL),
  (@sec, 'sexo_agresor', 'Sexo', 'SELECT', 2, 0, 1, 'AGRESOR', 1),
  (@sec, 'vinculo_victima', 'Vínculo con la víctima', 'SELECT', 3, 1, 1, 'AGRESOR', @cat_vinculo_victima),
  (@sec, 'grado_instruccion_agresor', 'Grado de instrucción', 'SELECT', 4, 0, 1, 'AGRESOR', @cat_grado_instruccion_nivel),
  (@sec, 'grado_instruccion_completitud_agresor', 'Completa / Incompleta', 'SELECT', 5, 0, 1, 'AGRESOR', @cat_completa_incompleta),
  (@sec, 'tiene_empleo_agresor', '¿Tiene empleo remunerado?', 'BOOLEANO', 6, 0, 1, 'AGRESOR', NULL),
  (@sec, 'ocupacion_agresor', 'Ocupación', 'TEXTO', 7, 0, 1, 'AGRESOR', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (21, 'Datos sobre la agresión', 4);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'estado_agresor', 'Estado del agresor', 'SELECT', 1, 0, 1, 'CASO_INDICE', @cat_estado_agresor),
  (@sec, 'tipo_violencia', 'Tipo de violencia', 'MULTISELECT', 2, 1, 1, 'CASO_INDICE', @cat_tipo_violencia),
  (@sec, 'medio_utilizado_violencia', 'Medio utilizado', 'MULTISELECT', 3, 0, 1, 'CASO_INDICE', @cat_medio_utilizado),
  (@sec, 'motivo_expresado_violencia', 'Motivo expresado', 'SELECT', 4, 0, 1, 'CASO_INDICE', @cat_motivo_agresion),
  (@sec, 'primera_vez_agredida', '¿Es la primera vez que es agredido(a)?', 'BOOLEANO', 5, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'veces_agredida_semana', 'Durante la semana, ¿cuántas veces fue agredido(a)?', 'NUMERO', 6, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'veces_agredida_mes', 'Durante el último mes, ¿cuántas veces?', 'NUMERO', 7, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'lugar_agresion', 'Lugar de la agresión', 'SELECT', 8, 0, 1, 'CASO_INDICE', @cat_lugar_agresion);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (21, 'Medidas tomadas y seguimiento', 5);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'medidas_tomadas_violencia', 'Medidas tomadas', 'MULTISELECT', 1, 0, 1, 'CASO_INDICE', @cat_medidas_tomadas),
  (@sec, 'fue_derivado_violencia', '¿Fue derivado?', 'BOOLEANO', 2, 0, 1, 'CASO_INDICE', NULL),
  (@sec, 'donde_derivado_violencia', '¿Dónde?', 'MULTISELECT', 3, 0, 1, 'CASO_INDICE', @cat_derivacion);

-- ============================================================================
-- 7.2 Lesiones por accidentes de tránsito (id=22) — multi_sujeto, CASO_INDICE(lesionado)/CONDUCTOR
-- ============================================================================
INSERT INTO catalogo (nombre) VALUES ('Fuente de financiamiento - accidentes');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SOAT', 'SOAT', 1), (@cat, 'MTC', 'MTC', 2), (@cat, 'PARTICULAR', 'Particular', 3);
SET @cat_fuente_financiamiento = @cat;

INSERT INTO catalogo (nombre) VALUES ('Condición de egreso - accidentes');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ALTA', 'Alta', 1), (@cat, 'FALLECIDO', 'Fallecido', 2), (@cat, 'REFERIDO', 'Referido', 3);
SET @cat_condicion_egreso_accidente = @cat;

INSERT INTO catalogo (nombre) VALUES ('Vía donde ocurrió el accidente');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'CALLES_JIRONES', 'Calles/jirones', 1), (@cat, 'AVENIDAS', 'Avenidas', 2),
  (@cat, 'CARRETERAS', 'Carreteras', 3), (@cat, 'AUTOPISTAS', 'Autopistas / vía expresa', 4),
  (@cat, 'FLUVIAL', 'Fluvial', 5), (@cat, 'AEREO', 'Aéreo', 6), (@cat, 'MARITIMO', 'Marítimo', 7);
SET @cat_via_accidente = @cat;

INSERT INTO catalogo (nombre) VALUES ('Tipo de accidente');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'ATROPELLADO', 'Atropellado', 1), (@cat, 'CHOQUE', 'Choque', 2),
  (@cat, 'VOLCADURA', 'Volcadura', 3), (@cat, 'CAIDA_OCUPANTE', 'Caída de ocupante', 4), (@cat, 'OTRO', 'Otro', 5);
SET @cat_tipo_accidente = @cat;

INSERT INTO catalogo (nombre) VALUES ('Tipo de vehículo');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'MOTOCICLETA', 'Motocicleta', 1), (@cat, 'MOTOCAR', 'Motocar', 2), (@cat, 'AUTOMOVIL', 'Automóvil', 3),
  (@cat, 'MICROBUS', 'Microbús', 4), (@cat, 'OMNIBUS', 'Ómnibus', 5), (@cat, 'CAMION_TRAILER', 'Camión/tráiler', 6),
  (@cat, 'TREN', 'Tren', 7), (@cat, 'BICICLETA', 'Bicicleta', 8), (@cat, 'CARRETA', 'Carreta', 9),
  (@cat, 'AVION', 'Avión', 10), (@cat, 'AVIONETA_HELICOPTERO', 'Avioneta/helicóptero', 11),
  (@cat, 'EMBARCACION_CON_MOTOR', 'Embarcación con motor', 12), (@cat, 'EMBARCACION_SIN_MOTOR', 'Embarcación sin motor', 13);
SET @cat_tipo_vehiculo = @cat;

INSERT INTO catalogo (nombre) VALUES ('Ubicación del lesionado');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'PASAJERO', 'Pasajero', 1), (@cat, 'CONDUCTOR', 'Conductor', 2), (@cat, 'PEATON', 'Peatón', 3);
SET @cat_ubicacion_lesionado = @cat;

INSERT INTO catalogo (nombre) VALUES ('Traslado del lesionado por');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'OCASIONANTE', 'Ocasionante', 1), (@cat, 'FAMILIAR', 'Familiar', 2), (@cat, 'PROPIOS_MEDIOS', 'Propios medios', 3),
  (@cat, 'SERENAZGO', 'Serenazgo', 4), (@cat, 'PARTICULAR', 'Persona particular', 5), (@cat, 'POLICIA', 'Policía', 6),
  (@cat, 'BOMBERO', 'Bombero', 7), (@cat, 'AMBULANCIA', 'Ambulancia de servicio de salud', 8);
SET @cat_traslado_lesionado = @cat;

INSERT INTO catalogo (nombre) VALUES ('Condición del vehículo ocasionante');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'PARTICULAR', 'Particular', 1), (@cat, 'PUBLICO', 'Público', 2), (@cat, 'ESTATAL', 'Estatal', 3), (@cat, 'PRIVADO', 'Privado', 4);
SET @cat_condicion_vehiculo = @cat;

INSERT INTO catalogo (nombre) VALUES ('Licencia de conducir');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'SI', 'Sí', 1), (@cat, 'NO', 'No', 2), (@cat, 'NO_SE_SABE', 'No se sabe', 3);
SET @cat_licencia_conducir = @cat;

INSERT INTO catalogo (nombre) VALUES ('Aseguradora - accidentes');
SET @cat = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat, 'RIMAC', 'Rímac', 1), (@cat, 'PACIFICO', 'Pacífico Seguros', 2), (@cat, 'LA_POSITIVA', 'La Positiva', 3),
  (@cat, 'GENERALI', 'Generali Perú', 4), (@cat, 'MAPFRE', 'Mapfre Perú', 5), (@cat, 'LATINO', 'Latino Seguros', 6), (@cat, 'OTRO', 'Otro', 7);
SET @cat_aseguradora = @cat;

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (22, 'Datos del lesionado', 1);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'fuente_financiamiento_accidente', 'Fuente de financiamiento', 'SELECT', 1, 0, 0, 'CASO_INDICE', @cat_fuente_financiamiento),
  (@sec, 'num_hc_emergencia', 'N.° de HC de emergencia', 'TEXTO', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'num_hc_hospitalizacion_accidente', 'N.° de HC de hospitalización', 'TEXTO', 3, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'referido_eess_accidente', '¿Referido de un EE.SS.?', 'BOOLEANO', 4, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'nombre_eess_referido', 'Nombre del EE.SS.', 'TEXTO', 5, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'direccion_lesionado', 'Dirección (jr/av/calle/localidad, distrito, provincia, departamento)', 'TEXTO', 6, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_hora_ingreso_lesionado', 'Fecha y hora de ingreso al establecimiento', 'FECHA', 7, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'diagnosticos_lesionado', 'Diagnóstico médico (Dx 1 / Dx 2 / Dx 3 + código CIE-10 de cada uno)', 'TEXTAREA', 8, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'fecha_egreso_lesionado', 'Fecha de egreso del establecimiento', 'FECHA', 9, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'condicion_egreso_lesionado', 'Condición de egreso', 'SELECT', 10, 0, 0, 'CASO_INDICE', @cat_condicion_egreso_accidente),
  (@sec, 'referido_a_donde_lesionado', 'Referido a dónde', 'TEXTO', 11, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'requiere_rehabilitacion', '¿Requiere rehabilitación?', 'BOOLEANO', 12, 0, 0, 'CASO_INDICE', NULL);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (22, 'Datos del accidente', 2);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'fecha_hora_accidente', 'Fecha y hora del accidente', 'FECHA', 1, 1, 0, 'CASO_INDICE', NULL),
  (@sec, 'lugar_accidente', 'Lugar (jr/av/calle/localidad, departamento, provincia, distrito)', 'TEXTO', 2, 0, 0, 'CASO_INDICE', NULL),
  (@sec, 'via_accidente', 'Vía donde ocurrió', 'SELECT', 3, 0, 0, 'CASO_INDICE', @cat_via_accidente),
  (@sec, 'tipo_accidente', 'Tipo de accidente', 'SELECT', 4, 1, 0, 'CASO_INDICE', @cat_tipo_accidente);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (22, 'Referente al lesionado y al ocasionante', 3);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'vehiculo_lesionado', 'El lesionado se encontraba en', 'SELECT', 1, 0, 0, 'CASO_INDICE', @cat_tipo_vehiculo),
  (@sec, 'ubicacion_lesionado', 'Ubicación del lesionado', 'SELECT', 2, 0, 0, 'CASO_INDICE', @cat_ubicacion_lesionado),
  (@sec, 'traslado_lesionado_por', 'Traslado del lesionado por', 'SELECT', 3, 0, 0, 'CASO_INDICE', @cat_traslado_lesionado),
  (@sec, 'tipo_vehiculo_ocasionante', 'Tipo de vehículo del ocasionante', 'SELECT', 4, 0, 0, 'CASO_INDICE', @cat_tipo_vehiculo),
  (@sec, 'condicion_vehiculo_ocasionante', 'Condición del vehículo ocasionante', 'SELECT', 5, 0, 0, 'CASO_INDICE', @cat_condicion_vehiculo);

INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (22, 'Datos del conductor y del vehículo', 4);
SET @sec = LAST_INSERT_ID();
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, orden, obligatorio, sensible, rol_sujeto, catalogo_id) VALUES
  (@sec, 'apellidos_nombres_conductor', 'Apellidos y nombres del conductor', 'TEXTO', 1, 0, 0, 'CONDUCTOR', NULL),
  (@sec, 'edad_conductor', 'Edad', 'NUMERO', 2, 0, 0, 'CONDUCTOR', NULL),
  (@sec, 'sexo_conductor', 'Sexo', 'SELECT', 3, 0, 0, 'CONDUCTOR', 1),
  (@sec, 'licencia_conducir', 'N.° de licencia de conducir', 'SELECT', 4, 0, 0, 'CONDUCTOR', @cat_licencia_conducir),
  (@sec, 'num_licencia_conducir', 'N.° de licencia', 'TEXTO', 5, 0, 0, 'CONDUCTOR', NULL),
  (@sec, 'comisaria_denuncia', 'Comisaría donde se registra la denuncia (departamento, provincia, distrito)', 'TEXTO', 6, 0, 0, 'CONDUCTOR', NULL),
  (@sec, 'num_poliza_soat', 'N.° de póliza SOAT', 'TEXTO', 7, 0, 0, 'CONDUCTOR', NULL),
  (@sec, 'num_placa_vehiculo', 'N.° de placa del vehículo', 'TEXTO', 8, 0, 0, 'CONDUCTOR', NULL),
  (@sec, 'nombre_dueno_poliza', 'Nombre del dueño de la póliza SOAT', 'TEXTO', 9, 0, 0, 'CONDUCTOR', NULL),
  (@sec, 'aseguradora_accidente', 'Aseguradora', 'SELECT', 10, 0, 0, 'CONDUCTOR', @cat_aseguradora);
