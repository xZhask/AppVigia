-- 30_motor_dependencias_y_nucleo.sql
-- AUDITORIA_FICHA_DIFTERIA.md: capacidades de motor y núcleo que no eran
-- específicas de una sola enfermedad, necesarias para reconstruir difteria
-- (y luego cualquier otra ficha) fielmente contra DEFINICION_FICHAS.md.

-- ============================================================================
-- 1) origen del campo: distingue lo que viene del formato MINSA de lo que
--    agregue la institución (para poder auditar qué se inventó).
-- ============================================================================
ALTER TABLE campo_def
  ADD COLUMN origen ENUM('FICHA_MINSA','INTERNO') NOT NULL DEFAULT 'FICHA_MINSA' AFTER config;

-- ============================================================================
-- 2) motor de dependencias condicionales entre campos
-- ============================================================================
ALTER TABLE campo_def
  ADD COLUMN depende_de INT NULL AFTER catalogo_id,
  ADD COLUMN valor_activador VARCHAR(60) NULL AFTER depende_de,
  ADD CONSTRAINT fk_campo_depende FOREIGN KEY (depende_de) REFERENCES campo_def(id);

-- ============================================================================
-- 3) núcleo — notificación y datos del paciente (van en `caso`/`persona`,
--    no en la definición de cada ficha, porque se repiten en casi todas)
-- ============================================================================
ALTER TABLE caso
  ADD COLUMN tipo_captacion ENUM('ACTIVA','PASIVA') NULL AFTER clasificacion,
  ADD COLUMN lugar_captacion ENUM('INSTITUCIONAL','COMUNIDAD') NULL AFTER tipo_captacion,
  ADD COLUMN clasificacion_captacion ENUM('CONFIRMADO','PROBABLE','SOSPECHOSO') NULL AFTER lugar_captacion;

ALTER TABLE persona
  ADD COLUMN celular VARCHAR(20) NULL AFTER distrito_id,
  ADD COLUMN nacionalidad VARCHAR(60) NULL DEFAULT 'Peruana' AFTER celular,
  ADD COLUMN direccion VARCHAR(160) NULL AFTER nacionalidad,
  ADD COLUMN localidad VARCHAR(120) NULL AFTER direccion,
  ADD COLUMN etnia ENUM('MESTIZO','ANDINO','ASIATICO_DESCENDIENTE','AFRODESCENDIENTE','INDIGENA_AMAZONICO','OTRO') NULL AFTER localidad,
  ADD COLUMN gestante TINYINT(1) NULL AFTER etnia,
  ADD COLUMN semanas_gestacion SMALLINT NULL AFTER gestante;

-- ============================================================================
-- 4) núcleo — sección "Investigador" (cierre de casi todas las fichas MINSA)
-- ============================================================================
ALTER TABLE caso
  ADD COLUMN investigador_nombre VARCHAR(160) NULL AFTER fecha_inicio_sintomas,
  ADD COLUMN investigador_cargo VARCHAR(100) NULL AFTER investigador_nombre,
  ADD COLUMN fecha_investigacion DATE NULL AFTER investigador_cargo;

-- ============================================================================
-- 5) clasificación final propia por ficha: cada enfermedad puede restringir
--    las 4 opciones genéricas de `caso.clasificacion` a un subconjunto (ej.
--    difteria: solo Confirmado/Descartado). NULL = usa las 4 genéricas.
-- ============================================================================
ALTER TABLE enfermedad
  ADD COLUMN opciones_clasificacion VARCHAR(80) NULL AFTER roles_sujeto;

UPDATE enfermedad SET opciones_clasificacion = 'CONFIRMADO,DESCARTADO' WHERE nombre = 'Difteria';

-- ============================================================================
-- 6) tablas hijas genéricas: columnas que se repiten entre varias fichas
--    (censo de contactos con datos clínicos, laboratorio con antibiótico)
-- ============================================================================
ALTER TABLE caso_contacto
  ADD COLUMN edad SMALLINT NULL AFTER parentesco,
  ADD COLUMN sexo ENUM('M','F') NULL AFTER edad,
  ADD COLUMN vacunado ENUM('SI','NO','IGNORADO') NULL AFTER sexo,
  ADD COLUMN fecha_vacunacion DATE NULL AFTER vacunado,
  ADD COLUMN profilaxis ENUM('SI','NO') NULL AFTER fecha_vacunacion;

ALTER TABLE caso_muestra
  ADD COLUMN recibio_antibiotico TINYINT(1) NULL AFTER tipo_prueba;

-- ============================================================================
-- 7) lugar probable de infección (reutilizable: difteria, fiebre amarilla,
--    Chagas, Carrión, ...)
-- ============================================================================
CREATE TABLE `caso_lugar_infeccion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caso_id` int(11) NOT NULL,
  `lugar_institucion` varchar(160) DEFAULT NULL,
  `localidad_texto` varchar(160) DEFAULT NULL,
  `distrito_id` char(6) DEFAULT NULL,
  `permanencia_dias` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_lugin_caso` (`caso_id`),
  KEY `ix_lugin_dist` (`distrito_id`),
  CONSTRAINT `fk_lugin_caso` FOREIGN KEY (`caso_id`) REFERENCES `caso` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lugin_dist` FOREIGN KEY (`distrito_id`) REFERENCES `distrito` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE enfermedad
  ADD COLUMN usa_lugar_infeccion TINYINT(1) NOT NULL DEFAULT 0 AFTER usa_vacunas;

UPDATE enfermedad SET usa_lugar_infeccion = 1 WHERE nombre IN ('Difteria', 'Fiebre amarilla');
