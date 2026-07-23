-- 39_fix_caso_viaje_duplicado.sql
-- Corrige un error propio de sql/35_fase5_ampliar_tablas_hija.sql.
--
-- Esa migración agregó `lugar_institucion`/`permanencia_dias` a caso_viaje
-- siguiendo al pie de la letra el ALTER TABLE sugerido en
-- CIERRE_RECARGA_Y_FASE5.md ("caso_viaje: cubre 'Lugar probable de
-- infección'"), sin revisar antes si esa necesidad ya tenía dueño. Sí lo
-- tenía: `caso_lugar_infeccion` (01_esquema_vigia.sql) ya trae
-- `lugar_institucion`, `localidad_texto`, `distrito_id` y
-- `permanencia_dias`, y ya está conectada de punta a punta
-- (CasosController::filasLugarInfeccion(), CasoLugarInfeccion::reemplazarTodos(),
-- sección "Lugar probable de infección" en fichas/ver.php).
--
-- Las columnas agregadas en caso_viaje nunca se usaron en ningún
-- controlador/vista (se verificó con grep antes de tocar nada) y la tabla
-- tiene 0 filas, así que no hay pérdida de dato real al quitarlas.
ALTER TABLE caso_viaje
  DROP COLUMN lugar_institucion,
  DROP COLUMN permanencia_dias;
