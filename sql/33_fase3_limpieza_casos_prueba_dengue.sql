-- 33_fase3_limpieza_casos_prueba_dengue.sql
-- RECARGA_FICHAS.md, Fase 3, paso 2.
--
-- Los 3 casos existentes de Dengue (enfermedad_id=1) son datos de PRUEBA
-- generados por el propio equipo durante el desarrollo/pruebas de la Fase 4,
-- no personas reales. Evidencia (verificada antes de borrar):
--   - Los 3 fueron creados por el usuario "Administrador" (admin@dirsapol.gob.pe)
--     el mismo día (2026-07-18), en un lapso de ~1 hora (16:05 a 17:00).
--   - Los nombres de paciente son explícitamente de prueba: "Fase Cuatro Prueba
--     Bug" (caso F-00001), "Badge Test" (caso F-00004); el tercero
--     ("Ana María Quispe Rojas", caso F-00002) es un nombre de relleno creado
--     en la misma sesión de pruebas.
--   - Ninguno tiene caso_contacto, caso_muestra, caso_vacuna, caso_viaje ni
--     caso_lugar_infeccion asociados (0 filas en cada una).
--
-- Se borran para poder recargar la ficha de Dengue (hasta ahora un stub fuera
-- de spec, ver memoria "pendiente-auditoria-fichas") sin arrastrar caso_valor
-- que apunten a campo_def que el cargador va a reemplazar. El DELETE en
-- caso() cascada a caso_valor, caso_sujeto y caso_bitacora (los 3 tienen
-- ON DELETE CASCADE hacia caso.id) — no hace falta borrarlos aparte.

DELETE FROM caso WHERE enfermedad_id = 1 AND id IN (1, 2, 4);

-- El stub previo de Dengue no usaba ninguna tabla hija. La ficha real (PDF
-- pág. 49) sí necesita laboratorio (sección VI), antecedente de viaje/lugar
-- de estadía en las últimas 2 semanas (sección IV) y antecedente de vacuna
-- antiamarílica (preguntas 33-34) — se activan los flags correspondientes.
UPDATE enfermedad SET usa_muestras = 1, usa_viajes = 1, usa_vacunas = 1 WHERE id = 1;
