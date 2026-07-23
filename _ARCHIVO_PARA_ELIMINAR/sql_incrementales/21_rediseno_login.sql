-- ============================================================================
-- VIGÍA · REDISENO_LOGIN.md — Requisitos de seguridad del acceso
-- Registro de intentos de inicio de sesión (bloqueo temporal + trazabilidad)
-- y tokens de restablecimiento de contraseña de un solo uso.
-- ============================================================================
USE vigia;

CREATE TABLE login_intento (
  id       BIGINT AUTO_INCREMENT PRIMARY KEY,
  email    VARCHAR(120) NOT NULL,
  ip       VARCHAR(45)  NOT NULL,   -- IPv4 o IPv6
  exitoso  TINYINT(1)   NOT NULL,
  fecha    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_login_email_fecha (email, fecha),
  KEY ix_login_ip_fecha    (ip, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_token (
  id         BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT         NOT NULL,
  token_hash CHAR(64)    NOT NULL,  -- sha256 del token; el token en claro solo viaja por el enlace del correo
  creado_en  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expira_en  TIMESTAMP   NOT NULL,
  usado_en   TIMESTAMP   NULL,
  UNIQUE KEY uq_prt_hash (token_hash),
  KEY ix_prt_usuario (usuario_id),
  CONSTRAINT fk_prt_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
