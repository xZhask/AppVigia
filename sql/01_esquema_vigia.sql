-- ============================================================================
-- VIGÍA · Sistema de Vigilancia Epidemiológica — DIRSAPOL / Sanidad PNP
-- Esquema de base de datos  ·  MySQL 8 / MariaDB 10.4+
-- Cargar este archivo PRIMERO, luego 02_ubigeo_data.sql (datos INEI).
--
-- Regenerado a partir de la estructura real de la base de datos (mysqldump
-- --no-data), para que una instalación limpia nazca con la misma estructura
-- que ya corre en producción: identidad unificada en `persona`, nombres
-- separados, integración RENIEC, fichas multi-sujeto y motor de fichas
-- extendido. Los archivos sql/02 en adelante quedan como historial de cómo
-- se llegó a esta estructura; no hace falta volver a ejecutarlos sobre una
-- instalación que parte de este archivo.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS vigia
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vigia;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1) GEOGRAFÍA  (estructura INEI 2016; los datos vienen en 02_ubigeo_data.sql)
-- ============================================================================
CREATE TABLE `departamento` (
  `id` char(2) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `provincia` (
  `id` char(4) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `departamento_id` char(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_prov_dep` (`departamento_id`),
  CONSTRAINT `fk_prov_dep` FOREIGN KEY (`departamento_id`) REFERENCES `departamento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `distrito` (
  `id` char(6) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `provincia_id` char(4) NOT NULL,
  `departamento_id` char(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_dist_prov` (`provincia_id`),
  KEY `ix_dist_dep` (`departamento_id`),
  CONSTRAINT `fk_dist_dep` FOREIGN KEY (`departamento_id`) REFERENCES `departamento` (`id`),
  CONSTRAINT `fk_dist_prov` FOREIGN KEY (`provincia_id`) REFERENCES `provincia` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2) EXTENSIONES PNP  (grado y unidad/dependencia del efectivo)
-- ============================================================================
CREATE TABLE `grado_pnp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `abreviatura` varchar(16) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `nivel` enum('OFICIAL_GENERAL','OFICIAL_SUPERIOR','OFICIAL_SUBALTERNO','SUBOFICIAL','CADETE','ALUMNO','EMPLEADO_CIVIL') NOT NULL,
  `jerarquia` smallint(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grado_abrev` (`abreviatura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `unidad_pnp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(160) NOT NULL,
  `tipo` varchar(60) DEFAULT NULL,
  `distrito_id` char(6) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `ix_unid_dist` (`distrito_id`),
  CONSTRAINT `fk_unid_dist` FOREIGN KEY (`distrito_id`) REFERENCES `distrito` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3) ESTABLECIMIENTOS DE SALUD  (RENIPRESS)
-- ============================================================================
CREATE TABLE `red_salud` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `diresa` varchar(120) NOT NULL DEFAULT 'DIRSAPOL',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `establecimiento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cod_renipress` varchar(8) DEFAULT NULL,
  `nombre` varchar(160) NOT NULL,
  `red_id` int(11) DEFAULT NULL,
  `institucion` enum('MINSA','ESSALUD','FFAA_SANIDAD','PRIVADO') NOT NULL DEFAULT 'FFAA_SANIDAD',
  `distrito_id` char(6) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_est_cod_renipress` (`cod_renipress`),
  KEY `ix_est_red` (`red_id`),
  KEY `ix_est_dist` (`distrito_id`),
  CONSTRAINT `fk_est_dist` FOREIGN KEY (`distrito_id`) REFERENCES `distrito` (`id`),
  CONSTRAINT `fk_est_red` FOREIGN KEY (`red_id`) REFERENCES `red_salud` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4) IDENTIDAD  (persona: quién es alguien, independiente de su rol)
-- ============================================================================
CREATE TABLE `persona` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_interno` varchar(20) DEFAULT NULL,
  `tipo_doc` enum('DNI','CE','PTP','PAS','SIN_DOCUMENTO') NOT NULL DEFAULT 'DNI',
  `num_doc` varchar(20) DEFAULT NULL,
  `apellido_paterno` varchar(60) DEFAULT NULL,
  `apellido_materno` varchar(60) DEFAULT NULL,
  `nombres` varchar(80) DEFAULT NULL,
  `sexo` enum('M','F') DEFAULT NULL,
  `fecha_nac` date DEFAULT NULL,
  `distrito_id` char(6) DEFAULT NULL,
  -- ----- Datos PNP -----
  `es_pnp` tinyint(1) NOT NULL DEFAULT 0,
  `cip` varchar(12) DEFAULT NULL,
  `situacion_pnp` enum('ACTIVIDAD','RETIRO','DISPONIBILIDAD') DEFAULT NULL,
  `grado_id` int(11) DEFAULT NULL,
  `categoria_pnp` enum('ARMAS','SERVICIOS','ASIMILADO') DEFAULT NULL,
  `unidad_id` int(11) DEFAULT NULL,
  `tipo_beneficiario` enum('TITULAR','DERECHOHABIENTE') DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_persona_doc` (`tipo_doc`,`num_doc`),
  UNIQUE KEY `uq_persona_codigo` (`codigo_interno`),
  KEY `ix_pac_dist` (`distrito_id`),
  KEY `ix_pac_grado` (`grado_id`),
  KEY `ix_pac_unidad` (`unidad_id`),
  KEY `ix_pac_cip` (`cip`),
  KEY `ix_pac_apellidos` (`apellido_paterno`,`apellido_materno`),
  KEY `ix_pac_categoria` (`categoria_pnp`),
  CONSTRAINT `fk_pac_dist` FOREIGN KEY (`distrito_id`) REFERENCES `distrito` (`id`),
  CONSTRAINT `fk_pac_grado` FOREIGN KEY (`grado_id`) REFERENCES `grado_pnp` (`id`),
  CONSTRAINT `fk_pac_unidad` FOREIGN KEY (`unidad_id`) REFERENCES `unidad_pnp` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5) SEGURIDAD  (usuario: credenciales y rol de acceso de una persona)
-- ============================================================================
CREATE TABLE `usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `persona_id` int(11) DEFAULT NULL,
  `perfil_incompleto` tinyint(1) NOT NULL DEFAULT 0,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('ADMIN','REGISTRADOR') NOT NULL DEFAULT 'REGISTRADOR',
  `establecimiento_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email` (`email`),
  UNIQUE KEY `uq_user_persona` (`persona_id`),
  KEY `ix_user_est` (`establecimiento_id`),
  CONSTRAINT `fk_user_est` FOREIGN KEY (`establecimiento_id`) REFERENCES `establecimiento` (`id`),
  CONSTRAINT `fk_user_persona` FOREIGN KEY (`persona_id`) REFERENCES `persona` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6) ACCESO  (bloqueo temporal por intentos fallidos + restablecer contraseña)
-- ============================================================================
CREATE TABLE `login_intento` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(120) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `exitoso` tinyint(1) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_login_email_fecha` (`email`,`fecha`),
  KEY `ix_login_ip_fecha` (`ip`,`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_token` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `expira_en` timestamp NOT NULL,
  `usado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prt_hash` (`token_hash`),
  KEY `ix_prt_usuario` (`usuario_id`),
  CONSTRAINT `fk_prt_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7) INTEGRACIÓN RENIEC  (trazabilidad de consultas por documento)
-- ============================================================================
CREATE TABLE `reniec_consulta` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `dni` varchar(8) NOT NULL,
  `encontrado` tinyint(1) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_reniec_usuario` (`usuario_id`),
  KEY `ix_reniec_dni` (`dni`),
  CONSTRAINT `fk_reniec_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8) MOTOR DE FICHAS  (metadatos: cada enfermedad define sus secciones/campos)
-- ============================================================================
CREATE TABLE `enfermedad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `nombre_corto` varchar(100) DEFAULT NULL,
  `palabras_clave` varchar(255) DEFAULT NULL,
  `cie10` varchar(10) DEFAULT NULL,
  `tipo_notif` enum('INMEDIATA','SEMANAL') NOT NULL DEFAULT 'SEMANAL',
  `grupo` varchar(40) DEFAULT NULL,
  `familia` varchar(100) DEFAULT NULL,
  `usa_contactos` tinyint(1) NOT NULL DEFAULT 0,
  `usa_muestras` tinyint(1) NOT NULL DEFAULT 0,
  `usa_viajes` tinyint(1) NOT NULL DEFAULT 0,
  `usa_vacunas` tinyint(1) NOT NULL DEFAULT 0,
  `multi_sujeto` tinyint(1) DEFAULT 0,
  `roles_sujeto` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `catalogo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_catalogo` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `catalogo_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `catalogo_id` int(11) NOT NULL,
  `valor` varchar(60) NOT NULL,
  `etiqueta` varchar(120) NOT NULL,
  `orden` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_item_cat` (`catalogo_id`),
  CONSTRAINT `fk_item_cat` FOREIGN KEY (`catalogo_id`) REFERENCES `catalogo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `seccion_def` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enfermedad_id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `orden` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_sec_enf` (`enfermedad_id`),
  CONSTRAINT `fk_sec_enf` FOREIGN KEY (`enfermedad_id`) REFERENCES `enfermedad` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `campo_def` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seccion_id` int(11) NOT NULL,
  `clave` varchar(60) NOT NULL,
  `etiqueta` varchar(160) NOT NULL,
  `tipo` enum('TEXTO','NUMERO','FECHA','BOOLEANO','SELECT','MULTISELECT','TEXTAREA','GRUPO_SI_NO','SI_NO_FECHA','MATRIZ','CRONOLOGIA') NOT NULL,
  `obligatorio` tinyint(1) NOT NULL DEFAULT 0,
  `rol_sujeto` varchar(50) DEFAULT 'CASO_INDICE',
  `sensible` tinyint(1) NOT NULL DEFAULT 0,
  `catalogo_id` int(11) DEFAULT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `orden` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_campo_sec` (`seccion_id`),
  KEY `ix_campo_cat` (`catalogo_id`),
  CONSTRAINT `fk_campo_cat` FOREIGN KEY (`catalogo_id`) REFERENCES `catalogo` (`id`),
  CONSTRAINT `fk_campo_sec` FOREIGN KEY (`seccion_id`) REFERENCES `seccion_def` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9) NÚCLEO  (caso + valores de campos)
-- Una persona *es* paciente cuando tiene un caso asociado: no hay tabla
-- `paciente` separada.
-- ============================================================================
CREATE TABLE `caso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `enfermedad_id` int(11) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_notif` date NOT NULL,
  `anio_epi` smallint(6) DEFAULT NULL,
  `semana_epi` smallint(6) DEFAULT NULL,
  `fecha_inicio_sintomas` date DEFAULT NULL,
  `clasificacion` enum('SOSPECHOSO','PROBABLE','CONFIRMADO','DESCARTADO') NOT NULL DEFAULT 'SOSPECHOSO',
  `estado` enum('ABIERTA','VALIDACION','CERRADA') NOT NULL DEFAULT 'ABIERTA',
  `anulado` tinyint(1) NOT NULL DEFAULT 0,
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `hospitalizado` tinyint(1) NOT NULL DEFAULT 0,
  `fallecido` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_caso_codigo` (`codigo`),
  KEY `ix_caso_enf` (`enfermedad_id`),
  KEY `ix_caso_pac` (`persona_id`),
  KEY `ix_caso_est` (`establecimiento_id`),
  KEY `ix_caso_user` (`usuario_id`),
  KEY `ix_caso_se` (`anio_epi`,`semana_epi`),
  KEY `ix_caso_clasif` (`clasificacion`),
  KEY `ix_caso_anulado` (`anulado`),
  KEY `ix_caso_fecha_notif` (`fecha_notif`,`id`),
  CONSTRAINT `fk_caso_enf` FOREIGN KEY (`enfermedad_id`) REFERENCES `enfermedad` (`id`),
  CONSTRAINT `fk_caso_est` FOREIGN KEY (`establecimiento_id`) REFERENCES `establecimiento` (`id`),
  CONSTRAINT `fk_caso_persona` FOREIGN KEY (`persona_id`) REFERENCES `persona` (`id`),
  CONSTRAINT `fk_caso_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores de los campos propios de cada enfermedad (EAV)
CREATE TABLE `caso_valor` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `caso_id` int(11) NOT NULL,
  `campo_def_id` int(11) NOT NULL,
  `valor` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cv` (`caso_id`,`campo_def_id`),
  KEY `ix_cv_campo` (`campo_def_id`),
  CONSTRAINT `fk_cv_campo` FOREIGN KEY (`campo_def_id`) REFERENCES `campo_def` (`id`),
  CONSTRAINT `fk_cv_caso` FOREIGN KEY (`caso_id`) REFERENCES `caso` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10) SUJETOS DE UNA FICHA  (fichas multi-sujeto: madre, recién nacido, etc.)
-- Cada fila liga un rol (CASO_INDICE, MADRE, ...) a una persona empadronada
-- o a datos desnormalizados cuando el sujeto no está en `persona`.
-- ============================================================================
CREATE TABLE `caso_sujeto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caso_id` int(11) NOT NULL,
  `persona_id` int(11) DEFAULT NULL,
  `rol` enum('CASO_INDICE','MADRE','RECIEN_NACIDO','NINO_EXPUESTO','AGRESOR','CONDUCTOR','OTRO') NOT NULL,
  `apellidos` varchar(120) DEFAULT NULL,
  `nombres` varchar(80) DEFAULT NULL,
  `doc` varchar(20) DEFAULT NULL,
  `sexo` enum('M','F') DEFAULT NULL,
  `edad` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_cs_caso` (`caso_id`),
  KEY `ix_cs_pac` (`persona_id`),
  CONSTRAINT `fk_cs_caso` FOREIGN KEY (`caso_id`) REFERENCES `caso` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cs_persona` FOREIGN KEY (`persona_id`) REFERENCES `persona` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 11) TABLAS HIJAS REPETITIVAS  (1:N sobre el caso)
-- ============================================================================
CREATE TABLE `caso_contacto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caso_id` int(11) NOT NULL,
  `nombres` varchar(160) DEFAULT NULL,
  `parentesco` varchar(60) DEFAULT NULL,
  `doc` varchar(20) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_cont_caso` (`caso_id`),
  CONSTRAINT `fk_cont_caso` FOREIGN KEY (`caso_id`) REFERENCES `caso` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `caso_muestra` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caso_id` int(11) NOT NULL,
  `tipo_muestra` varchar(80) DEFAULT NULL,
  `tipo_prueba` varchar(80) DEFAULT NULL,
  `resultado` varchar(40) DEFAULT NULL,
  `fecha_toma` date DEFAULT NULL,
  `fecha_result` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_mues_caso` (`caso_id`),
  CONSTRAINT `fk_mues_caso` FOREIGN KEY (`caso_id`) REFERENCES `caso` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `caso_viaje` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caso_id` int(11) NOT NULL,
  `pais` varchar(60) DEFAULT NULL,
  `distrito_id` char(6) DEFAULT NULL,
  `fecha_salida` date DEFAULT NULL,
  `fecha_retorno` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_viaj_caso` (`caso_id`),
  KEY `ix_viaj_dist` (`distrito_id`),
  CONSTRAINT `fk_viaj_caso` FOREIGN KEY (`caso_id`) REFERENCES `caso` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_viaj_dist` FOREIGN KEY (`distrito_id`) REFERENCES `distrito` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `caso_vacuna` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caso_id` int(11) NOT NULL,
  `vacuna` varchar(80) DEFAULT NULL,
  `dosis` varchar(40) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_vac_caso` (`caso_id`),
  CONSTRAINT `fk_vac_caso` FOREIGN KEY (`caso_id`) REFERENCES `caso` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `caso_bitacora` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `caso_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(60) NOT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_bit_caso` (`caso_id`),
  KEY `ix_bit_user` (`usuario_id`),
  CONSTRAINT `fk_bit_caso` FOREIGN KEY (`caso_id`) REFERENCES `caso` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bit_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 12) IMPORTACIÓN MASIVA  (registro de cada lote de Excel/CSV importado)
-- ============================================================================
CREATE TABLE `lote_importacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enfermedad_id` int(11) NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `total_filas` int(11) NOT NULL,
  `filas_importadas` int(11) NOT NULL,
  `filas_error` int(11) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_lote_enf` (`enfermedad_id`),
  KEY `ix_lote_est` (`establecimiento_id`),
  KEY `ix_lote_user` (`usuario_id`),
  CONSTRAINT `fk_lote_enf` FOREIGN KEY (`enfermedad_id`) REFERENCES `enfermedad` (`id`),
  CONSTRAINT `fk_lote_est` FOREIGN KEY (`establecimiento_id`) REFERENCES `establecimiento` (`id`),
  CONSTRAINT `fk_lote_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 13) DATOS SEMILLA  (catálogo mínimo de ejemplo; no es un volcado de datos
--     de producción — el padrón completo de establecimientos, enfermedades y
--     grados PNP se administra desde la aplicación)
-- ============================================================================

-- --- Grados PNP (jerarquía 1 = mayor rango; ajusta a la escala vigente) ---
INSERT INTO grado_pnp (id, abreviatura, nombre, nivel, jerarquia) VALUES
 (1,'Gral. Pol.','General de Policía',        'OFICIAL_GENERAL',   1),
 (2,'Tnte. Gral.','Teniente General',         'OFICIAL_GENERAL',   2),
 (3,'Gral.','General',                         'OFICIAL_GENERAL',   3),
 (4,'Crnel.','Coronel',                        'OFICIAL_SUPERIOR',  4),
 (5,'Cmdte.','Comandante',                     'OFICIAL_SUPERIOR',  5),
 (6,'My.','Mayor',                             'OFICIAL_SUPERIOR',  6),
 (7,'Cap.','Capitán',                          'OFICIAL_SUBALTERNO',7),
 (8,'Tnte.','Teniente',                        'OFICIAL_SUBALTERNO',8),
 (9,'Alf.','Alférez',                          'OFICIAL_SUBALTERNO',9),
 (10,'SS','Suboficial Superior',               'SUBOFICIAL',       10),
 (11,'SB','Suboficial Brigadier',              'SUBOFICIAL',       11),
 (12,'SOT1','Suboficial Técnico de Primera',   'SUBOFICIAL',       12),
 (13,'SOT2','Suboficial Técnico de Segunda',   'SUBOFICIAL',       13),
 (14,'SOT3','Suboficial Técnico de Tercera',   'SUBOFICIAL',       14),
 (15,'SO1','Suboficial de Primera',            'SUBOFICIAL',       15),
 (16,'SO2','Suboficial de Segunda',            'SUBOFICIAL',       16),
 (17,'SO3','Suboficial de Tercera',            'SUBOFICIAL',       17),
 (18,'EC','Empleado Civil',                    'EMPLEADO_CIVIL',   18);

-- --- Catálogos reutilizables ---
INSERT INTO catalogo (id, nombre) VALUES
 (1,'sexo'),(2,'si_no'),(3,'resultado_lab'),(4,'tipo_muestra'),(5,'tipo_prueba');

INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
 (1,'M','Masculino',1),(1,'F','Femenino',2),
 (2,'SI','Sí',1),(2,'NO','No',2),(2,'IGN','Ignorado',3),
 (3,'POS','Positivo',1),(3,'NEG','Negativo',2),(3,'IND','Indeterminado',3),
 (4,'HNF','Hisopado nasofaríngeo',1),(4,'SUERO','Suero',2),(4,'LESION','Hisopado de lesión',3),
 (5,'PCR','PCR - RT',1),(5,'AG','Prueba antigénica',2),(5,'ELISA','ELISA',3),(5,'CULT','Cultivo',4);

-- --- Enfermedades bajo vigilancia (muestra) ---
INSERT INTO enfermedad (id, nombre, cie10, tipo_notif, grupo) VALUES
 (1,'Dengue y arbovirosis','A97','INMEDIATA','A'),
 (2,'Sarampión / rubéola','B05','INMEDIATA','A'),
 (3,'Tos ferina','A37.0','SEMANAL','A'),
 (4,'Leishmaniasis','B55','SEMANAL','A'),
 (5,'Difteria','A36','INMEDIATA','A'),
 (6,'Fiebre amarilla','A95','INMEDIATA','A'),
 (7,'ESAVI severo',NULL,'INMEDIATA','C');

-- --- Ejemplo de definición de ficha (motor de formularios) para Dengue ---
INSERT INTO seccion_def (id, enfermedad_id, nombre, orden) VALUES
 (1,1,'Cuadro clínico',3),
 (2,1,'Signos de alarma',4);

INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, catalogo_id, orden) VALUES
 (1,'fiebre','Fiebre','BOOLEANO',0,NULL,1),
 (1,'cefalea','Cefalea','BOOLEANO',0,NULL,2),
 (1,'mialgias','Mialgias','BOOLEANO',0,NULL,3),
 (1,'dolor_retroocular','Dolor retroocular','BOOLEANO',0,NULL,4),
 (1,'rash','Rash / exantema','BOOLEANO',0,NULL,5),
 (2,'dolor_abdominal','Dolor abdominal intenso','BOOLEANO',0,NULL,1),
 (2,'vomitos_persist','Vómitos persistentes','BOOLEANO',0,NULL,2),
 (2,'sangrado_mucosas','Sangrado de mucosas','BOOLEANO',0,NULL,3);

-- --- Redes y establecimientos (muestra) ---
INSERT INTO red_salud (id, nombre, diresa) VALUES
 (1,'Red Lima Centro','DIRSAPOL'),
 (2,'Red Lima Norte','DIRSAPOL'),
 (3,'Red Sur Oriente','DIRSAPOL');

INSERT INTO establecimiento (id, cod_renipress, nombre, red_id, institucion) VALUES
 (1,NULL,'Hospital Nacional PNP Luis N. Sáenz',2,'FFAA_SANIDAD'),
 (2,NULL,'Policlínico PNP VIPOL',1,'FFAA_SANIDAD'),
 (3,NULL,'Sanidad PNP Cusco',3,'FFAA_SANIDAD');

-- --- Usuario administrador inicial (reemplaza el hash antes de usar) ---
-- Sin persona_id: al iniciar sesión por primera vez, perfil_incompleto = 0
-- exime de la pantalla de completado; si prefieres forzarla, crea la fila
-- con perfil_incompleto = 1 y persona_id NULL.
INSERT INTO usuario (id, nombre, email, password_hash, rol, establecimiento_id) VALUES
 (1,'Administrador','admin@dirsapol.gob.pe','$2y$10$REEMPLAZAR_ESTE_HASH_BCRYPT','ADMIN',NULL);
