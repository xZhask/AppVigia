CREATE TABLE caso_sujeto (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  caso_id     INT NOT NULL,
  rol         VARCHAR(30) NOT NULL,       -- ej: 'CASO_INDICE', 'MADRE', 'RECIEN_NACIDO'
  persona_id  INT NULL,                   -- Puede apuntar a la base de personas
  es_anonimo  TINYINT(1) NOT NULL DEFAULT 0,
  
  -- Desnormalización leve para sujetos no empadronados o anónimos temporales
  nombre_temporal VARCHAR(100) NULL,
  
  UNIQUE KEY uq_cs_rol (caso_id, rol),
  KEY ix_cs_persona (persona_id),
  CONSTRAINT fk_cs_caso  FOREIGN KEY (caso_id)     REFERENCES caso(id) ON DELETE CASCADE,
  CONSTRAINT fk_cs_persona FOREIGN KEY (persona_id) REFERENCES persona(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE enfermedad 
  ADD COLUMN multi_sujeto TINYINT(1) DEFAULT 0 AFTER usa_vacunas,
  ADD COLUMN roles_sujeto VARCHAR(255) NULL AFTER multi_sujeto;

ALTER TABLE campo_def 
  ADD COLUMN rol_sujeto VARCHAR(50) DEFAULT 'CASO_INDICE' AFTER obligatorio;

-- Migración de los casos existentes: cada caso se convierte en un CASO_INDICE
INSERT INTO caso_sujeto (caso_id, persona_id, rol)
SELECT id, persona_id, 'CASO_INDICE' FROM caso;
