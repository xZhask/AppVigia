# sql/historico/ — SQL reemplazados por el cargador único

Los 15 archivos de este directorio **ya no se ejecutan** contra la base de
datos. Se movieron acá (RECARGA_FICHAS.md, Fase 3) porque su función —cargar
las definiciones de ficha (`seccion_def`/`campo_def`/`catalogo`/`catalogo_item`)
de las 24 enfermedades— la cumple ahora un solo script:
**`cargar_fichas.php`** (raíz del proyecto), que lee de
**`manifiesto_fichas.json`** en vez de tener el contenido de cada ficha
escrito a mano en SQL.

No se borran: son la evidencia de cómo se cargó (mal, en varios casos) el
estado anterior, y el motivo por el que se auditó y reconstruyó todo esto —
ver `INFORME_CARGADOR.md`, `REPORTE_VERIFICACION.md` y `CAMBIOS_MANIFIESTO.md`
en la raíz del proyecto.

## Qué es cada archivo

**Loaders originales** (`INSERT` de `seccion_def`/`campo_def` escritos a
mano, uno por lote, cada uno con su propia convención de `clave` y su
propia plantilla de columnas — la causa raíz documentada en
`INFORME_CARGADOR.md`, sección "Parte A"):

- `07_fase7_fichas_grupo_a.sql` — seed de placeholder de la Fase 7 (anterior
  al sistema de Lotes). Sembró secciones "Cuadro clínico"/"Signos de
  alarma" genéricas para 5 enfermedades, basadas en criterio clínico
  general, no en el PDF MINSA. Es el origen de los "campos inventados" que
  motivó la auditoría (hallazgo A.3).
- `15_lote1_fichas.sql` — Tos ferina, Varicela, Parotiditis, Difteria.
- `16_lote2_fichas.sql` — Fiebre amarilla, Leishmaniasis, Chagas, Carrión,
  Mpox.
- `18_lotes_5.sql` y `18_lotes_5_8.sql` — dos versiones de un intento de
  cargar los Lotes 5-8 (VIH, sífilis, SRC, ESAVI, violencia, accidentes,
  muertes); ambas quedan acá como evidencia, sin que se haya determinado
  cuál llegó a ejecutarse tal cual contra la base en su momento.
- `19_lotes_3_4.sql` — EDA/cólera, Tétanos, Tétanos neonatal, PFA. Es el
  archivo cuyo `INSERT INTO campo_def` omitía la columna `catalogo_id`
  (hallazgo A.2b).
- `22_lote4_sarampion.sql` — Sarampión/rubéola.
- `24_lote5_materno_perinatal.sql`, `25_lote6_esavi.sql`,
  `26_lote7_eventos_externos.sql`, `27_lote8_muertes.sql` — Lotes 5 a 8
  (Grupos B/C/D de `DEFINICION_FICHAS_B_C_D.md`).

**Parches posteriores** (correcciones puntuales sobre lo ya cargado, antes
de que existiera el cargador único):

- `23_limpieza_duplicados_lote1_2.sql` — borró secciones/campos duplicados
  que aparecieron por falta de idempotencia (hallazgo A.4).
- `28_fix_lote3_catalogos.sql` — corrigió el bug de `catalogo_id NULL` de
  `19_lotes_3_4.sql` para 4 enfermedades.
- `31_fix_difteria.sql` — reconstrucción completa de Difteria
  (`AUDITORIA_FICHA_DIFTERIA.md`), la primera ficha corregida y la que
  disparó toda esta auditoría.
- `32_fix_tosferina_fiebreamarilla.sql` — mismo patrón de tipos degradados
  encontrado en Difteria, corregido puntualmente en Tos ferina y Fiebre
  amarilla.

## Qué NO está acá (y por qué)

- `01_esquema_vigia.sql` sigue en `sql/` — es el esquema base (`CREATE
  TABLE`), no un loader de fichas. Se le quitó únicamente el "ejemplo de
  definición de ficha" de Dengue que tenía sembrado a mano (la sección
  "Signos de alarma" inventada — mismo origen que el hallazgo A.3).
- `14_motor_fichas.sql`, `17_estructura_multisujeto.sql`,
  `29_condicion_paciente.sql`, `30_motor_dependencias_y_nucleo.sql` siguen
  en `sql/` — son evolución de **esquema** (`ALTER TABLE`, tipos nuevos del
  ENUM, tablas núcleo), no contenido de ficha; el cargador los necesita para
  poder insertar (p. ej. el ENUM de `campo_def.tipo` con `GRUPO_SI_NO`,
  `MATRIZ`, etc. viene de `14_motor_fichas.sql`).
- `33_fase3_limpieza_casos_prueba_dengue.sql` sigue en `sql/` — no es un
  loader de fichas, es un registro puntual de que se borraron 3 casos de
  prueba de Dengue antes de recargar esa ficha; queda como parte normal del
  historial de migraciones, no como algo reemplazado por el cargador.

## Si hace falta recargar una ficha

No se edita ninguno de estos archivos. Se corrige `manifiesto_fichas.json`
y se corre `php cargar_fichas.php --apply --confirmo-apply --cie10=<CIE10>`
(con `--confirmar-perdida=<CIE10>` si la enfermedad ya tiene `caso_valor`
capturados y se acepta perderlos). Ver el docblock de `cargar_fichas.php`
para el detalle de uso.
