-- 36_seccion_condicional.sql
-- CIERRE_RECARGA_Y_FASE5.md, Parte 1.5.
--
-- Extiende a nivel de seccion_def el mecanismo condicional que ya existía a
-- nivel de campo_def (depende_de/valor_activador): una sección con
-- depende_de no se renderiza mientras el campo disparador (de una sección
-- anterior, dentro de la misma ficha) no tenga el valor_activador.
--
-- Se construye la CAPACIDAD ahora; el contenido del Anexo 6.2 de ESAVI que
-- la motivó se difiere a una sesión aparte (PDF pág. 7-8, ~12 secciones de
-- checklist) -- por eso ninguna ficha usa todavía esta columna.
ALTER TABLE seccion_def
  ADD COLUMN depende_de INT NULL,
  ADD COLUMN valor_activador VARCHAR(60) NULL,
  ADD CONSTRAINT fk_seccion_depende FOREIGN KEY (depende_de) REFERENCES campo_def(id);
