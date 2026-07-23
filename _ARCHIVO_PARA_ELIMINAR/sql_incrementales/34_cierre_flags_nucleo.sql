-- 34_cierre_flags_nucleo.sql
-- CIERRE_RECARGA_Y_FASE5.md, Parte 1, decisiones 1.1 y 1.3.
--
-- Estas dos columnas viven en `enfermedad`, que cargar_fichas.php nunca
-- toca (solo administra seccion_def/campo_def/catalogo/catalogo_item) --
-- por eso se aplican con SQL directo, igual que se hizo para Difteria y
-- Tos ferina/Fiebre amarilla en 32_fix_tosferina_fiebreamarilla.sql.

-- 1.1 PFA: los 5 valores de clasificación del PDF (Polio salvaje / derivado
-- de la vacuna / asociado a la vacuna / compatible / Descartado) ya viven
-- como campo propio de la ficha ("Clasificación final", SELECT) desde la
-- Fase 1 del manifiesto. Lo que faltaba era restringir el núcleo
-- (caso.clasificacion) al mismo patrón que Difteria/Fiebre amarilla/Tos
-- ferina: los 3 primeros valores del detalle corresponden a CONFIRMADO,
-- "compatible" a PROBABLE, "descartado" a DESCARTADO -- no hay mecanismo de
-- autocompletado núcleo<-detalle en la app (tampoco lo tienen Difteria ni
-- Fiebre amarilla), así que el usuario sigue fijando caso.clasificacion
-- manualmente, pero ahora el selector de chips solo ofrece las 3 opciones
-- coherentes con PFA en vez de las 4 genéricas (ver
-- app/Core/ayudantes.php::opcionesClasificacionPara()).
UPDATE enfermedad SET opciones_clasificacion = 'CONFIRMADO,PROBABLE,DESCARTADO' WHERE cie10 = 'A80';

-- 1.3 Parotiditis: confirmado que el PDF (pág. 4) no trae sección de
-- laboratorio para esta ficha (a diferencia de Varicela, que sí la trae).
-- El manifiesto ya documentaba "caso_muestra": false en tablas_hijas desde
-- la Fase 1 (CAMBIOS_MANIFIESTO.md lo dejó como observación abierta), pero
-- nunca se había aplicado al flag real.
UPDATE enfermedad SET usa_muestras = 0 WHERE cie10 = 'B26';
