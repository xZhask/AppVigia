-- 32_fix_tosferina_fiebreamarilla.sql
-- Efecto colateral de auditar difteria (AUDITORIA_FICHA_DIFTERIA.md, punto 3:
-- "si asignó TEXTO por defecto en difteria, probablemente lo hizo en otras
-- fichas"): se confirmó el mismo patrón en Tos ferina y Fiebre amarilla al
-- comparar contra DEFINICION_FICHAS.md. Sin casos para tos ferina (verificado)
-- y sin valores guardados para los campos de fiebre amarilla que se tocan
-- (verificado): conversión de tipo segura.

-- ============================================================================
-- Tos ferina (enfermedad_id=3)
-- ============================================================================
INSERT INTO catalogo (nombre) VALUES ('Sí/No/Desconocido - Tos ferina');
SET @cat_snd = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_snd, 'SI', 'Sí', 1), (@cat_snd, 'NO', 'No', 2), (@cat_snd, 'DESCONOCIDO', 'Desconocido', 3);

UPDATE campo_def SET tipo = 'SELECT', catalogo_id = @cat_snd
 WHERE id = 123; -- ¿Presenta alguna comorbilidad?

SELECT seccion_id INTO @sec_comorbilidad FROM campo_def WHERE id = 123;

-- Corre el resto de la sección un puesto para insertar "especificar" justo
-- después del campo 123 (orden 17).
UPDATE campo_def SET orden = orden + 1 WHERE seccion_id = @sec_comorbilidad AND orden >= 18;
INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, catalogo_id, depende_de, valor_activador, orden, obligatorio, sensible, origen)
VALUES (@sec_comorbilidad, 'a37_comorbilidad_especificar', 'Comorbilidad (especificar)', 'TEXTO', NULL, 123, 'SI', 18, 0, 0, 'FICHA_MINSA');

UPDATE campo_def SET tipo = 'SELECT', catalogo_id = @cat_snd
 WHERE id = 129; -- ¿Contacto con casos probables o confirmados de tos ferina?

-- ============================================================================
-- Fiebre amarilla (enfermedad_id=6)
-- ============================================================================
INSERT INTO catalogo (nombre) VALUES ('Condición de egreso - Fiebre amarilla');
SET @cat_egreso = LAST_INSERT_ID();
INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES
  (@cat_egreso, 'ALTA_RECUPERADO', 'Alta / Recuperado', 1), (@cat_egreso, 'FALLECIDO', 'Fallecido', 2);

UPDATE campo_def SET tipo = 'SELECT', catalogo_id = @cat_egreso
 WHERE id = 257; -- Condición de egreso

UPDATE campo_def SET depende_de = 257, valor_activador = 'FALLECIDO'
 WHERE id IN (258, 259, 260, 261); -- Fallecido: necropsia / Dx macroscópico / Dx microscópico / fecha

UPDATE enfermedad SET opciones_clasificacion = 'CONFIRMADO,DESCARTADO' WHERE id = 6;
