-- 35_fase5_ampliar_tablas_hija.sql
-- CIERRE_RECARGA_Y_FASE5.md, Parte 2 (Fase 5 de RECARGA_FICHAS.md) y Parte 1.4
-- (cadena de transmisión de Sarampión, que necesita las columnas nuevas de
-- caso_contacto).
--
-- Todas las columnas son NULL-ables: no rompe filas existentes.

-- caso_vacuna: hoy solo tiene vacuna/dosis/fecha. Le faltan los datos que
-- ESAVI, Tos ferina y Difteria piden sobre la vacuna aplicada.
ALTER TABLE caso_vacuna
  ADD COLUMN fabricante        VARCHAR(120) NULL,
  ADD COLUMN lote              VARCHAR(60)  NULL,
  ADD COLUMN via                VARCHAR(40)  NULL,
  ADD COLUMN sitio             VARCHAR(60)  NULL,
  ADD COLUMN fecha_vencimiento DATE         NULL,
  ADD COLUMN establecimiento   VARCHAR(160) NULL;

-- caso_sujeto: sujetos que no son el caso índice (madre en muerte fetal,
-- gestante/niño en fichas materno-perinatales) necesitan ubicación propia.
ALTER TABLE caso_sujeto
  ADD COLUMN distrito_id CHAR(6)      NULL,
  ADD COLUMN direccion   VARCHAR(200) NULL,
  ADD CONSTRAINT fk_cs_distrito FOREIGN KEY (distrito_id) REFERENCES distrito(id);

-- caso_viaje: cubre "Lugar probable de infección" (difteria, fiebre
-- amarilla, Chagas, Carrión), que hoy solo tiene país/distrito/fechas.
ALTER TABLE caso_viaje
  ADD COLUMN lugar_institucion VARCHAR(200) NULL,
  ADD COLUMN permanencia_dias  SMALLINT     NULL;

-- caso_contacto: cadena de transmisión de sarampión (Parte 1.4) y censo de
-- contactos de tos ferina ("lugar de exposición" -> lugar_contacto).
ALTER TABLE caso_contacto
  ADD COLUMN fecha_contacto        DATE         NULL,
  ADD COLUMN lugar_contacto        VARCHAR(160) NULL,
  ADD COLUMN fecha_inicio_erupcion DATE         NULL,
  ADD COLUMN vacunado_72h          ENUM('SI','NO','DESCONOCIDO') NULL;
