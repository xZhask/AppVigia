-- 38_columnas_tabla_hija_por_ficha.sql
-- PENDIENTES_POST_FASE5.md, punto 3.
--
-- CIERRE_RECARGA_Y_FASE5.md asumió que ya existía una forma de configurar
-- qué columnas de tabla hija ve cada ficha ("usar la configuración por
-- ficha que ya existe para caso_contacto") -- se verificó el código y no
-- existía ninguna (ver HALLAZGOS_RECARGA_FICHAS.md, Parte 2). Al ampliar
-- caso_contacto para sarampión, la consecuencia quedó visible: difteria ve
-- columnas de sarampión que no le corresponden, y viceversa.

ALTER TABLE enfermedad
  ADD COLUMN columnas_contacto JSON NULL,
  ADD COLUMN columnas_muestra  JSON NULL,
  ADD COLUMN columnas_viaje    JSON NULL,
  ADD COLUMN columnas_vacuna   JSON NULL;
