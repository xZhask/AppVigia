-- AJUSTES_VISUALES.md punto 1: corrige datos ya guardados con mojibake.
--
-- Origen del daño: en algún momento se cargaron estas filas con el cliente
-- de MySQL usando una codificación de conexión distinta de utf8mb4 (cp850).
-- Cada carácter acentuado (rango Latin-1, bytes UTF-8 que empiezan en 0xC2 u
-- 0xC3) se leyó como si fueran bytes CP850 sueltos y el resultado se guardó
-- como si fuera texto correcto. No es un problema de visualización: los
-- bytes almacenados en la BD están mal.
--
-- La corrección revierte exactamente esa transformación: se reinterpretan
-- los caracteres actuales como CP850 (recuperando los bytes UTF-8
-- originales) y se decodifican de nuevo como utf8mb4. Verificado fila por
-- fila contra el patrón de corrupción antes de aplicar (script de
-- diagnóstico, no versionado). El WHERE usa UNHEX para no depender de la
-- codificación con la que se ejecute este archivo.

UPDATE establecimiento
SET nombre = CONVERT(CAST(CONVERT(nombre USING cp850) AS BINARY) USING utf8mb4)
WHERE CAST(nombre AS BINARY) LIKE CONCAT('%', UNHEX('E2949C'), '%');

UPDATE usuario
SET nombre = CONVERT(CAST(CONVERT(nombre USING cp850) AS BINARY) USING utf8mb4)
WHERE CAST(nombre AS BINARY) LIKE CONCAT('%', UNHEX('E2949C'), '%');

UPDATE seccion_def
SET nombre = CONVERT(CAST(CONVERT(nombre USING cp850) AS BINARY) USING utf8mb4)
WHERE CAST(nombre AS BINARY) LIKE CONCAT('%', UNHEX('E2949C'), '%');

UPDATE campo_def
SET etiqueta = CONVERT(CAST(CONVERT(etiqueta USING cp850) AS BINARY) USING utf8mb4)
WHERE CAST(etiqueta AS BINARY) LIKE CONCAT('%', UNHEX('E2949C'), '%');

UPDATE red_salud
SET nombre = CONVERT(CAST(CONVERT(nombre USING cp850) AS BINARY) USING utf8mb4)
WHERE CAST(nombre AS BINARY) LIKE CONCAT('%', UNHEX('E294AC'), '%');

-- Errata previa en el padrón de origen (sql/06_fase7_padron_pnp.sql tenía
-- "HUÀNUCO" con tilde grave): no es corrupción de codificación, es un typo
-- de la fila sembrada. Se corrige aparte porque la reversión de arriba es
-- fiel al dato de origen, no puede adivinar la tilde correcta.
UPDATE establecimiento
SET nombre = 'POLICLINICO POLICIAL HUÁNUCO'
WHERE id = 17 AND nombre = 'POLICLINICO POLICIAL HUÀNUCO';
