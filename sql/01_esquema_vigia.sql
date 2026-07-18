-- ============================================================================
-- VIGÍA · Sistema de Vigilancia Epidemiológica — DIRSAPOL / Sanidad PNP
-- Esquema de base de datos  ·  MySQL 8 / MariaDB 10.4+
-- Cargar este archivo PRIMERO, luego 02_ubigeo_data.sql (datos INEI).
-- ============================================================================

CREATE DATABASE IF NOT EXISTS vigia
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vigia;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1) GEOGRAFÍA  (estructura INEI 2016; los datos vienen en 02_ubigeo_data.sql)
-- ============================================================================
CREATE TABLE departamento (
  id      CHAR(2)     NOT NULL,
  nombre  VARCHAR(60) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE provincia (
  id              CHAR(4)     NOT NULL,
  nombre          VARCHAR(60) NOT NULL,
  departamento_id CHAR(2)     NOT NULL,
  PRIMARY KEY (id),
  KEY ix_prov_dep (departamento_id),
  CONSTRAINT fk_prov_dep FOREIGN KEY (departamento_id) REFERENCES departamento(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE distrito (
  id              CHAR(6)     NOT NULL,
  nombre          VARCHAR(60) NOT NULL,
  provincia_id    CHAR(4)     NOT NULL,
  departamento_id CHAR(2)     NOT NULL,
  PRIMARY KEY (id),
  KEY ix_dist_prov (provincia_id),
  KEY ix_dist_dep  (departamento_id),
  CONSTRAINT fk_dist_prov FOREIGN KEY (provincia_id)    REFERENCES provincia(id),
  CONSTRAINT fk_dist_dep  FOREIGN KEY (departamento_id) REFERENCES departamento(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2) EXTENSIONES PNP  (grado y unidad/dependencia del efectivo)
-- ============================================================================
CREATE TABLE grado_pnp (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  abreviatura VARCHAR(16) NOT NULL,
  nombre      VARCHAR(60) NOT NULL,
  categoria   ENUM('OFICIAL_GENERAL','OFICIAL_SUPERIOR','OFICIAL_SUBALTERNO',
                   'SUBOFICIAL','EMPLEADO_CIVIL') NOT NULL,
  jerarquia   SMALLINT NOT NULL,          -- 1 = mayor rango
  UNIQUE KEY uq_grado_abrev (abreviatura)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE unidad_pnp (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(160) NOT NULL,      -- comisaría, dirección, escuela, etc.
  tipo        VARCHAR(60)  NULL,          -- Comisaría / Dirección / Unidad / Escuela
  distrito_id CHAR(6)      NULL,
  activo      TINYINT(1)   NOT NULL DEFAULT 1,
  KEY ix_unid_dist (distrito_id),
  CONSTRAINT fk_unid_dist FOREIGN KEY (distrito_id) REFERENCES distrito(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3) ESTABLECIMIENTOS DE SALUD  (RENIPRESS)
-- ============================================================================
CREATE TABLE red_salud (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  diresa VARCHAR(120) NOT NULL DEFAULT 'DIRSAPOL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE establecimiento (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  cod_renipress VARCHAR(8)   NULL,
  nombre        VARCHAR(160) NOT NULL,
  red_id        INT          NULL,
  institucion   ENUM('MINSA','ESSALUD','FFAA_SANIDAD','PRIVADO')
                             NOT NULL DEFAULT 'FFAA_SANIDAD',
  distrito_id   CHAR(6)      NULL,
  activo        TINYINT(1)   NOT NULL DEFAULT 1,
  KEY ix_est_red  (red_id),
  KEY ix_est_dist (distrito_id),
  CONSTRAINT fk_est_red  FOREIGN KEY (red_id)      REFERENCES red_salud(id),
  CONSTRAINT fk_est_dist FOREIGN KEY (distrito_id) REFERENCES distrito(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4) SEGURIDAD
-- ============================================================================
CREATE TABLE usuario (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  nombre             VARCHAR(120) NOT NULL,
  email              VARCHAR(120) NOT NULL,
  password_hash      VARCHAR(255) NOT NULL,
  rol                ENUM('ADMIN','REGISTRADOR')
                                  NOT NULL DEFAULT 'REGISTRADOR',
  establecimiento_id INT          NULL,     -- registrador ligado a su EESS
  activo             TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_email (email),
  KEY ix_user_est (establecimiento_id),
  CONSTRAINT fk_user_est FOREIGN KEY (establecimiento_id) REFERENCES establecimiento(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5) MOTOR DE FICHAS  (metadatos: cada enfermedad define sus secciones/campos)
-- ============================================================================
CREATE TABLE enfermedad (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(120) NOT NULL,
  cie10      VARCHAR(10)  NULL,
  tipo_notif ENUM('INMEDIATA','SEMANAL') NOT NULL DEFAULT 'SEMANAL',
  grupo      VARCHAR(40)  NULL,       -- A: caso estándar, B: binomio, C: evento...
  activo     TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE catalogo (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,        -- 'sexo', 'tipo_muestra', 'resultado_lab'...
  UNIQUE KEY uq_catalogo (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE catalogo_item (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  catalogo_id INT          NOT NULL,
  valor       VARCHAR(60)  NOT NULL,
  etiqueta    VARCHAR(120) NOT NULL,
  orden       SMALLINT     NOT NULL DEFAULT 0,
  KEY ix_item_cat (catalogo_id),
  CONSTRAINT fk_item_cat FOREIGN KEY (catalogo_id) REFERENCES catalogo(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE seccion_def (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  enfermedad_id INT          NOT NULL,
  nombre        VARCHAR(120) NOT NULL,
  orden         SMALLINT     NOT NULL DEFAULT 0,
  KEY ix_sec_enf (enfermedad_id),
  CONSTRAINT fk_sec_enf FOREIGN KEY (enfermedad_id) REFERENCES enfermedad(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE campo_def (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  seccion_id  INT          NOT NULL,
  clave       VARCHAR(60)  NOT NULL,   -- clave máquina (fiebre, dolor_abdominal...)
  etiqueta    VARCHAR(160) NOT NULL,
  tipo        ENUM('TEXTO','NUMERO','FECHA','BOOLEANO','SELECT','MULTISELECT','TEXTAREA')
                           NOT NULL,
  obligatorio TINYINT(1)   NOT NULL DEFAULT 0,
  catalogo_id INT          NULL,       -- para SELECT/MULTISELECT
  orden       SMALLINT     NOT NULL DEFAULT 0,
  KEY ix_campo_sec (seccion_id),
  KEY ix_campo_cat (catalogo_id),
  CONSTRAINT fk_campo_sec FOREIGN KEY (seccion_id)  REFERENCES seccion_def(id) ON DELETE CASCADE,
  CONSTRAINT fk_campo_cat FOREIGN KEY (catalogo_id) REFERENCES catalogo(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6) NÚCLEO  (paciente + caso + valores de campos)
-- ============================================================================
CREATE TABLE paciente (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  tipo_doc          ENUM('DNI','CE','PTP','PAS','OTRO') NOT NULL DEFAULT 'DNI',
  num_doc           VARCHAR(20)  NOT NULL,
  apellidos_nombres VARCHAR(160) NOT NULL,
  sexo              ENUM('M','F') NULL,
  fecha_nac         DATE         NULL,
  distrito_id       CHAR(6)      NULL,          -- domicilio actual
  -- ----- Datos PNP -----
  es_pnp            TINYINT(1)   NOT NULL DEFAULT 0,
  cip               VARCHAR(12)  NULL,          -- Carné de Identidad Personal
  situacion_pnp     ENUM('ACTIVIDAD','RETIRO','DISPONIBILIDAD') NULL,
  grado_id          INT          NULL,
  unidad_id         INT          NULL,          -- dependencia donde presta servicio
  tipo_beneficiario ENUM('TITULAR','DERECHOHABIENTE') NULL,
  creado_en         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_paciente_doc (tipo_doc, num_doc),   -- clave para detectar duplicados
  KEY ix_pac_dist   (distrito_id),
  KEY ix_pac_grado  (grado_id),
  KEY ix_pac_unidad (unidad_id),
  KEY ix_pac_cip    (cip),
  CONSTRAINT fk_pac_dist   FOREIGN KEY (distrito_id) REFERENCES distrito(id),
  CONSTRAINT fk_pac_grado  FOREIGN KEY (grado_id)    REFERENCES grado_pnp(id),
  CONSTRAINT fk_pac_unidad FOREIGN KEY (unidad_id)   REFERENCES unidad_pnp(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE caso (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  codigo                VARCHAR(20) NOT NULL,        -- F-08843
  enfermedad_id         INT NOT NULL,
  paciente_id           INT NOT NULL,
  establecimiento_id    INT NOT NULL,
  usuario_id            INT NOT NULL,                -- quién registró
  fecha_notif           DATE NOT NULL,
  anio_epi              SMALLINT NULL,
  semana_epi            SMALLINT NULL,
  fecha_inicio_sintomas DATE NULL,
  clasificacion         ENUM('SOSPECHOSO','PROBABLE','CONFIRMADO','DESCARTADO')
                                    NOT NULL DEFAULT 'SOSPECHOSO',
  estado                ENUM('ABIERTA','VALIDACION','CERRADA')
                                    NOT NULL DEFAULT 'ABIERTA',
  hospitalizado         TINYINT(1) NOT NULL DEFAULT 0,
  fallecido             TINYINT(1) NOT NULL DEFAULT 0,
  creado_en             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_caso_codigo (codigo),
  KEY ix_caso_enf    (enfermedad_id),
  KEY ix_caso_pac    (paciente_id),
  KEY ix_caso_est    (establecimiento_id),
  KEY ix_caso_user   (usuario_id),
  KEY ix_caso_se     (anio_epi, semana_epi),
  KEY ix_caso_clasif (clasificacion),
  CONSTRAINT fk_caso_enf  FOREIGN KEY (enfermedad_id)      REFERENCES enfermedad(id),
  CONSTRAINT fk_caso_pac  FOREIGN KEY (paciente_id)        REFERENCES paciente(id),
  CONSTRAINT fk_caso_est  FOREIGN KEY (establecimiento_id) REFERENCES establecimiento(id),
  CONSTRAINT fk_caso_user FOREIGN KEY (usuario_id)         REFERENCES usuario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores de los campos propios de cada enfermedad (EAV)
CREATE TABLE caso_valor (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  caso_id      INT  NOT NULL,
  campo_def_id INT  NOT NULL,
  valor        TEXT NULL,
  UNIQUE KEY uq_cv (caso_id, campo_def_id),
  KEY ix_cv_campo (campo_def_id),
  CONSTRAINT fk_cv_caso  FOREIGN KEY (caso_id)      REFERENCES caso(id)      ON DELETE CASCADE,
  CONSTRAINT fk_cv_campo FOREIGN KEY (campo_def_id) REFERENCES campo_def(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7) TABLAS HIJAS REPETITIVAS  (1:N sobre el caso)
-- ============================================================================
CREATE TABLE caso_contacto (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  caso_id     INT NOT NULL,
  nombres     VARCHAR(160) NULL,
  parentesco  VARCHAR(60)  NULL,
  doc         VARCHAR(20)  NULL,
  celular     VARCHAR(20)  NULL,
  KEY ix_cont_caso (caso_id),
  CONSTRAINT fk_cont_caso FOREIGN KEY (caso_id) REFERENCES caso(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE caso_muestra (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  caso_id      INT NOT NULL,
  tipo_muestra VARCHAR(80) NULL,
  tipo_prueba  VARCHAR(80) NULL,
  resultado    VARCHAR(40) NULL,
  fecha_toma   DATE NULL,
  fecha_result DATE NULL,
  KEY ix_mues_caso (caso_id),
  CONSTRAINT fk_mues_caso FOREIGN KEY (caso_id) REFERENCES caso(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE caso_viaje (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  caso_id       INT NOT NULL,
  pais          VARCHAR(60) NULL,        -- para viajes al exterior
  distrito_id   CHAR(6)     NULL,        -- para viajes dentro del país
  fecha_salida  DATE NULL,
  fecha_retorno DATE NULL,
  KEY ix_viaj_caso (caso_id),
  KEY ix_viaj_dist (distrito_id),
  CONSTRAINT fk_viaj_caso FOREIGN KEY (caso_id)     REFERENCES caso(id) ON DELETE CASCADE,
  CONSTRAINT fk_viaj_dist FOREIGN KEY (distrito_id) REFERENCES distrito(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE caso_vacuna (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  caso_id INT NOT NULL,
  vacuna  VARCHAR(80) NULL,
  dosis   VARCHAR(40) NULL,
  fecha   DATE NULL,
  KEY ix_vac_caso (caso_id),
  CONSTRAINT fk_vac_caso FOREIGN KEY (caso_id) REFERENCES caso(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE caso_bitacora (
  id         BIGINT AUTO_INCREMENT PRIMARY KEY,
  caso_id    INT NOT NULL,
  usuario_id INT NULL,
  accion     VARCHAR(60) NOT NULL,      -- CREACION / EDICION / CLASIFICACION / CIERRE
  detalle    VARCHAR(255) NULL,
  fecha      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_bit_caso (caso_id),
  KEY ix_bit_user (usuario_id),
  CONSTRAINT fk_bit_caso FOREIGN KEY (caso_id)    REFERENCES caso(id) ON DELETE CASCADE,
  CONSTRAINT fk_bit_user FOREIGN KEY (usuario_id) REFERENCES usuario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 8) DATOS SEMILLA
-- ============================================================================

-- --- Grados PNP (jerarquía 1 = mayor rango; ajusta a la escala vigente) ---
INSERT INTO grado_pnp (id, abreviatura, nombre, categoria, jerarquia) VALUES
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
INSERT INTO usuario (id, nombre, email, password_hash, rol, establecimiento_id) VALUES
 (1,'Administrador','admin@dirsapol.gob.pe','$2y$10$REEMPLAZAR_ESTE_HASH_BCRYPT','ADMIN',NULL);
