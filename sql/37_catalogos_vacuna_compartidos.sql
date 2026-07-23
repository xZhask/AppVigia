-- 37_catalogos_vacuna_compartidos.sql
-- PENDIENTES_POST_FASE5.md, punto 2.
--
-- Al mover la vacunación de ESAVI de campo_def a caso_vacuna (Fase 5) se
-- perdió el catálogo cerrado: eran SELECT con códigos MINSA y quedaron como
-- texto libre en caso_vacuna.vacuna/dosis/via/sitio. La solución NO es
-- agregar columnas *_id con FK a catalogo_item (como sugería el documento):
-- caso_muestra ya resuelve este mismo problema desde antes -- sus columnas
-- (tipo_muestra/tipo_prueba/resultado) son varchar, pero el widget
-- (muestras.php) las llena con <select> respaldados por catalogo_item fijo
-- (catalogos 3/4/5, ver CasosController::datosMuestrasCatalogo()), no con
-- texto libre. Se sigue ese mismo patrón ya probado en vez de introducir
-- columnas e índices nuevos.
--
-- Los códigos (etiqueta completa "NN Nombre") y valores (slug en mayúsculas)
-- replican exactamente cómo cargar_fichas.php los codificaba cuando estos
-- eran campo_def SELECT en ESAVI, para que la migración sea transparente
-- para quien ya los conocía así.

INSERT INTO catalogo (nombre) VALUES
 ('vacuna_minsa'), ('via_vacuna'), ('sitio_vacuna'), ('dosis_vacuna'), ('adyuvante_vacuna');

INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden)
SELECT c.id, v.valor, v.etiqueta, v.orden
FROM catalogo c
JOIN (
    SELECT 'vacuna_minsa' cat, '01_BCG' valor, '01 BCG' etiqueta, 1 orden
    UNION ALL SELECT 'vacuna_minsa', '02_DPT', '02 DPT', 2
    UNION ALL SELECT 'vacuna_minsa', '03_APO', '03 APO', 3
    UNION ALL SELECT 'vacuna_minsa', '04_HEPATITIS_B', '04 Hepatitis B', 4
    UNION ALL SELECT 'vacuna_minsa', '05_HIB', '05 Hib', 5
    UNION ALL SELECT 'vacuna_minsa', '06_PENTAVALENTE', '06 Pentavalente', 6
    UNION ALL SELECT 'vacuna_minsa', '07_SPR', '07 SPR', 7
    UNION ALL SELECT 'vacuna_minsa', '08_FIEBRE_AMARILLA', '08 Fiebre amarilla', 8
    UNION ALL SELECT 'vacuna_minsa', '09_SR', '09 SR', 9
    UNION ALL SELECT 'vacuna_minsa', '10_DT', '10 DT', 10
    UNION ALL SELECT 'vacuna_minsa', '11_INFLUENZA_ESTACIONAL', '11 Influenza estacional', 11
    UNION ALL SELECT 'vacuna_minsa', '12_ANTISARAMPION', '12 Antisarampión', 12
    UNION ALL SELECT 'vacuna_minsa', '13_NEUMOCOCO', '13 Neumococo', 13
    UNION ALL SELECT 'vacuna_minsa', '14_ROTAVIRUS', '14 Rotavirus', 14
    UNION ALL SELECT 'vacuna_minsa', '15_VPH', '15 VPH', 15
    UNION ALL SELECT 'vacuna_minsa', '16_IPV', '16 IPV', 16
    UNION ALL SELECT 'vacuna_minsa', '17_VARICELA', '17 Varicela', 17
    UNION ALL SELECT 'vacuna_minsa', '18_DTPA', '18 dTpa', 18
    UNION ALL SELECT 'vacuna_minsa', '19_ANTI_COVID_19', '19 Anti COVID-19', 19
    UNION ALL SELECT 'vacuna_minsa', '20_OTRO', '20 Otro', 20

    UNION ALL SELECT 'via_vacuna', '01_ORAL', '01 Oral', 1
    UNION ALL SELECT 'via_vacuna', '02_INTRADERMICA', '02 Intradérmica', 2
    UNION ALL SELECT 'via_vacuna', '03_SUBCUTANEA', '03 Subcutánea', 3
    UNION ALL SELECT 'via_vacuna', '04_INTRAMUSCULAR', '04 Intramuscular', 4

    UNION ALL SELECT 'sitio_vacuna', '01_HOMBRO_DERECHO', '01 Hombro derecho', 1
    UNION ALL SELECT 'sitio_vacuna', '02_HOMBRO_IZQUIERDO', '02 Hombro izquierdo', 2
    UNION ALL SELECT 'sitio_vacuna', '03_BRAZO_DERECHO', '03 Brazo derecho', 3
    UNION ALL SELECT 'sitio_vacuna', '04_BRAZO_IZQUIERDO', '04 Brazo izquierdo', 4
    UNION ALL SELECT 'sitio_vacuna', '05_VASTO_EXTERNO_MUSLO_DERECHO', '05 Vasto externo de muslo derecho', 5
    UNION ALL SELECT 'sitio_vacuna', '06_VASTO_EXTERNO_MUSLO_IZQUIERDO', '06 Vasto externo de muslo izquierdo', 6
    UNION ALL SELECT 'sitio_vacuna', '09_ORAL', '09 Oral', 7

    UNION ALL SELECT 'dosis_vacuna', '01_PRIMERA', '01 Primera', 1
    UNION ALL SELECT 'dosis_vacuna', '02_SEGUNDA', '02 Segunda', 2
    UNION ALL SELECT 'dosis_vacuna', '03_TERCERA', '03 Tercera', 3
    UNION ALL SELECT 'dosis_vacuna', '04_ADICIONAL', '04 Adicional', 4
    UNION ALL SELECT 'dosis_vacuna', '05_UNICA', '05 Única', 5
    UNION ALL SELECT 'dosis_vacuna', '06_REFUERZO', '06 Refuerzo', 6

    UNION ALL SELECT 'adyuvante_vacuna', '01_SI', '01 Sí', 1
    UNION ALL SELECT 'adyuvante_vacuna', '02_NO', '02 No', 2
) v ON v.cat = c.nombre;

-- caso_vacuna no tenía columna para "adyuvante" (el único campo de ESAVI que
-- se había dejado en campo_def, ver HALLAZGOS_RECARGA_FICHAS.md Parte 2,
-- justo porque no tenía dónde ir). Ahora sí la tiene, en el mismo estilo
-- texto-libre-respaldado-por-catálogo que el resto de estas columnas.
ALTER TABLE caso_vacuna
  ADD COLUMN adyuvante VARCHAR(20) NULL;
