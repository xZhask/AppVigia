# HALLAZGOS_RECARGA_FICHAS.md

Registro consolidado de hallazgos por fase de `RECARGA_FICHAS.md`. Cada fase
tiene su propio entregable detallado (referenciado abajo); este documento es
el índice de "qué se encontró" para no tener que reconstruirlo leyendo cinco
archivos distintos.

---

## Fase 1 — Validar el manifiesto contra el PDF MINSA

**Detalle completo:** `CAMBIOS_MANIFIESTO.md`.

- Los `.md` (`DEFINICION_FICHAS.md` / `DEFINICION_FICHAS_B_C_D.md`) tenían sus
  propios errores de transcripción, independientes de los bugs del cargador:
  columnas de matriz mezcladas entre Varicela/Parotiditis/Tos ferina, un campo
  de Fiebre amarilla redundante con el mecanismo núcleo
  (`enfermedad.opciones_clasificacion`), campos faltantes en Leishmaniasis.
- Se aplicó la regla de frontera (`INFORME_CARGADOR.md`, Parte C): Chagas
  tenía una sección "Migración" en `campo_def` que duplicaba `caso_viaje` —
  se quitó del manifiesto.
- Quedaron **6 decisiones de producto abiertas**, no resueltas por no ser
  responsabilidad de esta automatización (listadas en detalle en
  `CAMBIOS_MANIFIESTO.md`): clasificación de PFA (3 vs. 5 valores), flags
  `usa_contactos`/`usa_muestras` de Tos ferina/Parotiditis, sección "Cadena
  de transmisión" de Sarampión, Anexo 6.2 de ESAVI, simplificaciones de
  Chagas/VIH.

## Fase 2 — Cargador único idempotente

**Detalle completo:** docblock de `cargar_fichas.php`.

- El diseño fail-hard (`validarManifiesto()`) sí encontró trabajo real que
  hacer: obligó a completar `opciones`/`columnas` en el manifiesto para todo
  campo cerrado antes de poder correr un solo dry-run.
- **Bug encontrado y corregido durante el desarrollo:** `campo_def.depende_de`
  es una FK autorreferencial sin `ON DELETE CASCADE` — el primer dry-run
  falló al borrar una sección con un campo "especificar" dependiente antes
  que su campo disparador. Se corrigió limpiando `depende_de` antes del
  `DELETE FROM seccion_def`.
- **Bug encontrado y corregido durante el desarrollo:** el segundo dry-run
  falló con violación de FK en `catalogo` porque cada ficha usaba su propia
  transacción (con rollback individual), invalidando la caché de catálogos
  en PHP para la ficha siguiente. Se corrigió usando una sola transacción
  para todo el lote en modo dry-run.
- **Incidente:** un intento de "verificar que el guard existe" ejecutó
  `--apply` de verdad contra la base viva, sin querer, antes de que el guard
  `--confirmo-apply` existiera. Sin pérdida de datos reales (no había
  `caso_valor` en ninguna ficha), pero dejó un catálogo huérfano que se
  identificó y borró. Se agregó el guard `--confirmo-apply` como
  consecuencia directa. Ver memoria `feedback_flags_destructivos`.

## Fase 3 — Recarga real

**Detalle completo:** `sql/33_fase3_limpieza_casos_prueba_dengue.sql`,
`sql/historico/README.md`.

- Dengue (A97) no tenía entrada en el manifiesto — era un stub sembrado a
  mano en `01_esquema_vigia.sql`, origen de "campos inventados" (hallazgo
  A.3 de `INFORME_CARGADOR.md`). Se reconstruyó contra el PDF (pág. 49,
  Anexo N.° 01) y se agregó al manifiesto antes de recargar.
- Sus 3 casos existentes se verificaron como datos de prueba (mismo usuario
  "Administrador", mismo día, nombres explícitos de prueba) antes de
  borrarlos — 0 pérdida de datos reales.
- Backup previo (`backups/`, `mysqldump --column-statistics=0` — sin ese
  flag falla en este MySQL 8.4 por `information_schema.column_statistics`).
- Resultado: BD pasó de 107 secciones/801 campos/146 catálogos/743 ítems a
  142/790/265/1450. Los 15 SQL de lote/parche se archivaron en
  `sql/historico/` sin borrarlos.

## Fase 4 — Ampliar el verificador y correr la verificación final

**Detalle completo:** `REPORTE_VERIFICACION.md` (regenerado 2026-07-23).

Se amplió `verificar_fichas.php` para que, además de secciones/campos/tipos,
verifique catálogos — lo que la Fase 3 dejó como limitación conocida
(`INFORME_CARGADOR.md`, hallazgo A.2b): para todo campo
SELECT/MULTISELECT/GRUPO_SI_NO/CRONOLOGIA (los mismos tipos que
`cargar_fichas.php` exige con catálogo) ahora se comprueba que `catalogo_id`
no sea NULL, que ese catálogo tenga al menos un `catalogo_item`, y que sus
opciones coincidan con las del manifiesto (mismo emparejamiento tolerante a
tildes/mayúsculas que ya se usaba para nombres de sección y etiquetas de
campo).

**Hallazgo:** ninguno. Se corrieron los 250 campos de tipo catálogo (175
SELECT, 53 MULTISELECT, 21 GRUPO_SI_NO, 1 CRONOLOGIA) contra sus 265
catálogos — los 250 tienen `catalogo_id`, ningún catálogo está vacío, y
ninguna lista de opciones difiere del manifiesto. Esto confirma en la
práctica (no solo por diseño) que el fail-hard de la Fase 2 funcionó: no
quedó ningún campo cerrado sin catálogo ni ningún catálogo con contenido
distinto al validado en la Fase 1.

**Resultado:** `REPORTE_VERIFICACION.md` — **24/24 fichas ✅ OK**, sin
faltantes, sin sobrantes, sin tipos incorrectos y sin catálogos incorrectos.
Criterio de éxito de la Fase 4 (RECARGA_FICHAS.md) cumplido en su totalidad.

---

## Pendiente (no iniciado)

- **Fase 5** (`RECARGA_FICHAS.md`): ampliar `caso_vacuna`
  (`fabricante`/`lote`/`via`/`sitio`/`fecha_vencimiento`), `caso_sujeto`
  (`distrito_id`/`direccion`), `caso_viaje`
  (`lugar_institucion`/`permanencia_dias`); ajustar manifiesto y recargar
  fichas afectadas.
- Las 6 decisiones de producto abiertas de la Fase 1 (ver
  `CAMBIOS_MANIFIESTO.md`) siguen sin resolver — no son parte del alcance
  automatizable de `RECARGA_FICHAS.md`.
