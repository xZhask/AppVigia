-- 23_limpieza_duplicados_lote1_2.sql
-- Limpia secciones/campos placeholder que quedaron duplicados/mezclados con
-- la definición real de Lotes 1-2, probablemente por una re-ejecución
-- parcial de su script de siembra en una sesión anterior.
--
-- Diagnóstico (ver conversación): cada disciplina tenía una sección vieja
-- "Cuadro clínico"/"Signos de alarma" con campos BOOLEANO sueltos (sin
-- prefijo tipo a37_/a95_/b55_) coexistiendo con la sección real, causando
-- choques de `orden` y, en Fiebre amarilla, campos repetidos dentro de la
-- MISMA fila de seccion_def.

-- Tos ferina (enfermedad_id=3): sección vieja id=4 (mezcla interna con
-- orden repetido) + 2 copias exactas de sobra de la sección "Cuadro
-- clínico" correcta (ids 11, 12; se conserva la 10). Sin casos creados.
DELETE FROM seccion_def WHERE id IN (4, 11, 12);

-- Leishmaniasis (enfermedad_id=4): sección vieja id=5 ("Cuadro clínico",
-- 4 campos BOOLEANO sueltos). La sección real "Lesiones cutáneas" (id=34)
-- queda intacta. Sin casos creados.
DELETE FROM seccion_def WHERE id = 5;

-- Difteria (enfermedad_id=5): sección vieja id=6 ("Cuadro clínico", 5
-- campos BOOLEANO sueltos). La sección real "Información epidemiológica"
-- (id=26) queda intacta. Sin casos creados.
DELETE FROM seccion_def WHERE id = 6;

-- Fiebre amarilla (enfermedad_id=6): hay 1 caso real (id=3) con valores
-- guardados (todos en 0/sin marcar) apuntando a los campos viejos; hay que
-- borrar esos caso_valor primero porque fk_cv_campo es RESTRICT, no CASCADE.
-- Sección id=8 es un duplicado completo de la sección real "Cuadro
-- clínico" (id=27, 12 campos a95_ limpios) más 5 campos BOOLEANO viejos
-- sueltos. Sección id=9 ("Signos de alarma") es debris viejo sin
-- equivalente nuevo.
DELETE FROM caso_valor WHERE campo_def_id IN (SELECT id FROM campo_def WHERE seccion_id IN (8, 9));
DELETE FROM seccion_def WHERE id IN (8, 9);
