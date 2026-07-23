# HALLAZGOS_RECARGA_FICHAS.md

Registro consolidado de hallazgos por fase de `RECARGA_FICHAS.md`,
`CIERRE_RECARGA_Y_FASE5.md`, `PENDIENTES_POST_FASE5.md` y
`ANTES_DE_COMPARAR_FICHAS.md`. Cada fase tiene su propio entregable
detallado (referenciado abajo); este documento es el índice de "qué se
encontró" para no tener que reconstruirlo leyendo cinco archivos
distintos.

---

## ANTES_DE_COMPARAR_FICHAS.md

### Sección 1 — Secciones núcleo: nada que implementar

Verificación completa en `REPORTE_NUCLEO.md`. Los 12 campos núcleo
("Notificación", "Datos del paciente", "Investigador") ya estaban
implementados de punta a punta desde antes de esta sesión (esquema,
formulario de alta y edición, guardado con validación server-side, vista
de solo lectura) — no hizo falta escribir código. Se confirmó además que
`etnia` está correctamente excluida del listado de fichas (la consulta
`Caso::listarPaginado()` ni siquiera la selecciona) y de cualquier export
(no existe ninguno que la incluya).

### Sección 2 — Prueba de humo real en navegador: hecha

**A diferencia de las dos corridas anteriores, esta vez sí hubo
verificación visual real.** Con autorización explícita del usuario se
instaló Playwright + Chromium (en el scratchpad de la sesión, no en el
repo) y se levantó el servidor con `php -S`. Para loguearse sin tocar la
contraseña real del admin sembrado, se creó un usuario ADMIN temporal
(`prueba-humo-temporal@test.local`) — **borrado al terminar**, junto con
un caso de prueba (`F-00009`, creado para probar el flujo real de
guardado) que se registró y se volvió a abrir en edición antes de
borrarlo. La base quedó exactamente como estaba antes de empezar (1 caso
real, 2 usuarios reales, 0 filas en `caso_vacuna`/`caso_contacto`).

**Se revisaron las 6 fichas** (Sarampión, PFA, Muerte materna, ESAVI,
Tétanos, Difteria): 24 secciones numeradas correlativamente sin repetirse
(salvo una coincidencia de título sin relación, ver hallazgo 3 abajo), sin
errores de consola, sin selects vacíos fuera de los esperados
(departamento→provincia→distrito antes de elegir), sin controles
desbordando su tarjeta. Los `MATRIZ` de PFA (fuerza muscular, tono,
reflejos, Glasgow) y los `GRUPO_SI_NO`/"Las cuatro demoras" de Muerte
materna se ven exactamente como matrices de opciones, no como listas
sueltas ni con etiquetas concatenadas.

**Hallazgo real, corregido: `#campoGestante`/`#campoSemanasGestacion`
nunca se ocultaban visualmente.** El JS (`ficha.js`) sí calculaba
correctamente que debían ocultarse cuando sexo ≠ F (`hidden="true"` se
aplicaba al elemento), pero `public/css/theme.css` nunca traducía eso a
`display:none` para `.field` — la regla de autor `.field{display:flex}`
le ganaba siempre a la regla de user-agent para `[hidden]` (mismo
fenómeno que el propio `theme.css` ya documentaba y arreglaba para
`.sel-list`, `.cond-panel` y `.dep-wrap`, solo que no se había aplicado
a `.field`). Se agregó `.field[hidden]{display:none}` — una línea, mismo
patrón ya usado 3 veces en el archivo. Verificado en el navegador:
sexo vacío → oculto; sexo=F → visible; gestante=Sí → "Semanas de
gestación" visible; sexo=M → ambos ocultos de nuevo. Esto **no** afectaba
a los campos condicionales de `campo_def`/`seccion_def` (usan `.dep-wrap`,
que ya tenía la regla correcta) — era específico de los 2 campos núcleo
de gestación.

**Hallazgo real, corregido: ESAVI mostraba solo "Dosis" en antecedentes
vacunales.** Al configurar columnas por ficha (`PENDIENTES_POST_FASE5.md`
punto 3) se dejó `caso_vacuna` en el mínimo por defecto para todas las
fichas, incluida ESAVI — la ficha que originalmente motivó ampliar
`caso_vacuna` con fabricante/lote/vía/sitio/adyuvante/fecha de
vencimiento/EE.SS. Se vio en el navegador: el widget solo mostraba
Vacuna/Otro/Fecha/Dosis. Corregido: se agregó `columnas_tablas_hija.caso_vacuna`
para `Y59.0` con las 8 columnas. Confirmado visualmente que ahora
aparecen todas, con sus `<select>` poblados desde los catálogos nuevos
(vacuna: 21 opciones, dosis: 7, vía: 5, sitio: 8, adyuvante: 3 —
incluyendo el placeholder).

**Hallazgo cosmético, no corregido:** en Sarampión (y probablemente otras
fichas con sección propia de "Antecedentes epidemiológicos" en
`campo_def`), el título de esa sección coincide textualmente con el
título fijo de la tarjeta de tablas hija (`<h3>Antecedentes
epidemiológicos</h3>`, hardcodeado en `editar.php`/`nueva/index.php`).
No hay contenido duplicado ni numeración repetida (son las secciones 6 y
10), solo el mismo título aparece dos veces en la misma página. No se
tocó — es un problema de nombres, no de datos, y corregirlo requeriría
decidir un nuevo título para uno de los dos (¿cuál? es una decisión de
producto, no un bug).

**Guardar y reabrir**: probado con una ficha real de Difteria — se
completaron los campos mínimos obligatorios (forzando los campos de
identidad bloqueados por RENIEC, ya que no hay conexión real a RENIEC en
este entorno), se guardó (`F-00009` se creó de verdad en la BD), se
reabrió en `/casos/9/editar` y los valores cargaron desde la BD sin
error. La persona se vinculó por número de documento a un registro ya
existente (comportamiento esperado de deduplicación por documento, no un
defecto). Caso de prueba borrado al terminar.

**Playwright queda instalado** en el scratchpad de esta sesión (no en el
repo del proyecto) por si una sesión futura quiere repetir esta prueba
sin reinstalar.

---

## PENDIENTES_POST_FASE5.md

### Punto 1 — Sensibilidad: Sífilis corregida, SRC evaluado y NO aplicado

**Sífilis materna y congénita (`A50`)**: tenía 0/25 campos `sensible`.
Corregido: 23/25 (todo lo clínico y de clasificación de las secciones II y
III); se dejaron fuera los 2 campos administrativos del "Encabezado"
("Investigación de", "Nivel del establecimiento") por ser clasificación
organizacional, no dato clínico ni identificatorio del paciente. Recargada
y verificada.

**Síndrome de rubéola congénita (`P35.0`) — recomendación, sin aplicar**:
el documento pidió evaluar si el mismo criterio aplica. Revisando
`DEFINICION_FICHAS_B_C_D.md` sección 0.b, la lista de fichas que exige
`sensible=1` es explícita y cerrada: VIH/SIDA, sífilis, violencia familiar y
antecedentes de muerte materna. La rubéola congénita **no está en esa
lista** — es una enfermedad prevenible por vacuna, no de transmisión
sexual, y no comparte el motivo de confidencialidad de las otras cuatro
(estigma social/legal asociado a ITS o violencia). **Recomendación: no
marcar P35.0 como sensible** salvo que haya otra razón institucional no
documentada en el criterio 0.b (ej. protección genérica de datos de
recién nacidos) — de haberla, es una decisión de producto que corresponde
confirmar explícitamente antes de aplicarla, no inferirla de este criterio.

### Punto 2 — Catálogo de `caso_vacuna`; `caso_muestra` revisado (ya estaba bien)

`caso_vacuna.vacuna/dosis/via/sitio` eran texto libre desde la Fase 5. Se
creó un catálogo compartido por columna (`vacuna_minsa` 20 ítems,
`dosis_vacuna` 6, `via_vacuna` 4, `sitio_vacuna` 7, `adyuvante_vacuna` 2 —
`sql/37_catalogos_vacuna_compartidos.sql`) y se agregó una columna
`adyuvante` (el único campo de vacunación de ESAVI que seguía en
`campo_def` por no tener dónde ir — ya no queda ninguno).

**Desviación deliberada del ALTER TABLE que sugería el documento**: no se
agregaron columnas `_id` con FK a `catalogo_item`. En vez de eso se siguió
el patrón que **ya usaba `caso_muestra`** desde antes (columnas `varchar`
llenadas por `<select>` con el `valor` del catálogo, no por texto libre) —
mismo resultado (agrupa bien en reportes, sin "Pentavalente"/"pentavalente"),
menos columnas e índices nuevos. Se agregó `CatalogoItem::porNombreCatalogo()`
porque estos catálogos nuevos no tienen id fijo (a diferencia de
sexo/si_no/resultado_lab/tipo_muestra/tipo_prueba, sembrados con id
explícito en `01_esquema_vigia.sql`).

"Vacuna" es la única columna con opción "Otro": se agregó un campo
`vacuna_otro` de texto libre que, si se completa, reemplaza al código
elegido — es la única opción del PDF que lo pedía.

**`caso_muestra` revisado, no tenía el problema**: sus 3 columnas de
catálogo (`tipo_muestra`, `tipo_prueba`, `resultado`) ya se llenan con
`<select>` respaldados por catálogos fijos (ids 3/4/5,
`CasosController::datosMuestrasCatalogo()`) desde antes de esta sesión —
nunca fueron texto libre. No se tocó nada ahí.

### Punto 3 — Configuración de columnas por ficha (construida desde cero)

Confirmado (ya lo había encontrado en la sesión anterior): no existía
ningún mecanismo de esto. Se construyó:

- **Schema**: `enfermedad.columnas_contacto/muestra/viaje/vacuna` (JSON,
  `sql/38_columnas_tabla_hija_por_ficha.sql`).
- **Manifiesto**: cada ficha puede declarar opcionalmente
  `"columnas_tablas_hija": {"caso_contacto": [...], ...}`.
  `cargar_fichas.php` valida las columnas contra una lista fija de
  columnas configurables por tabla (`COLUMNAS_TABLA_HIJA_VALIDAS`) y
  actualiza `enfermedad.columnas_*` en cada corrida — si una ficha no lo
  declara, lo deja en `NULL` explícito (no conserva lo de una corrida
  anterior; el manifiesto manda).
- **Controlador**: `CasosController::datosColumnasTablaHija()` resuelve el
  JSON guardado o un mínimo por defecto (`COLUMNAS_HIJA_DEFECTO`) — el
  mínimo, no todas, para que una ficha sin configurar no herede de golpe
  columnas de otra.
- **Widgets**: `contactos.php` y `vacunas.php` ahora envuelven cada campo
  "de más" en un `if (in_array(...))`. `viajes.php` y `muestras.php` **no
  se tocaron** — sus columnas no cambiaron en la Fase 5 (viajes tiene las
  mismas 3 de siempre tras corregir el punto de abajo; muestras nunca tuvo
  columnas nuevas), así que no había nada que ocultar todavía.
- **Configurado**: Difteria, Mpox, Sarampión y Tos ferina (contactos) con
  las columnas que de verdad les corresponden. El documento sugería
  columnas que no existen en el esquema (`direccion`, `tipo_exposicion` en
  contactos; la fila de PFA mezclaba columnas de vacuna con contactos, que
  PFA ni siquiera usa) — se dejaron fuera en vez de inventar columnas
  nuevas para calzar con esos nombres. `caso_vacuna` se quedó en el mínimo
  por defecto para todas las fichas: el documento no llegó a especificar
  columnas por ficha para vacuna, queda abierto para una próxima revisión
  del PDF.
- **De paso, corregido**: la nota de Tos ferina decía "no hay campos que
  llenar en caso_contacto para esta ficha" — era el mismo error que ya se
  había corregido en la Fase 1.2 de `CIERRE_RECARGA_Y_FASE5.md` (sí hay una
  tabla nominal, pregunta 61 del PDF) pero `tablas_hijas.caso_contacto`
  había quedado en `false` en el manifiesto pese a que `usa_contactos=1` en
  la BD era correcto. Corregido a `true` para que coincidan.

**Bug propio encontrado y corregido de paso**: al investigar esto se
encontró que `sql/35_fase5_ampliar_tablas_hija.sql` (Fase 5 original) había
agregado `lugar_institucion`/`permanencia_dias` a `caso_viaje` copiando el
ALTER TABLE de `CIERRE_RECARGA_Y_FASE5.md` sin revisar antes si esa
necesidad ya tenía dueño. Sí lo tenía: `caso_lugar_infeccion` ya traía esas
mismas columnas (más `localidad_texto`/`distrito_id`) y ya estaba conectada
de punta a punta desde antes (controlador, modelo, vista). Las columnas de
`caso_viaje` nunca se usaron en ningún controlador/vista (verificado con
grep) y la tabla tenía 0 filas — se revirtieron
(`sql/39_fix_caso_viaje_duplicado.sql`), sin pérdida de dato real.

**Hallazgo aparte, no corregido** (fuera del alcance de los 4 puntos):
`caso_lugar_infeccion` tiene su propio flag `enfermedad.usa_lugar_infeccion`,
independiente de `usa_viajes` y no rastreado en absoluto por el manifiesto.
Difteria y Fiebre amarilla lo tienen en 1 (correcto); Chagas y Carrión lo
tienen en 0 pese a que `CIERRE_RECARGA_Y_FASE5.md` las mencionó como fichas
con "Lugar probable de infección". No se tocó — no estaba entre los 4
puntos pedidos y merece su propia revisión contra el PDF antes de decidir.

### Punto 4 — Residencia de la madre (P96): movida a `caso_sujeto`, con UBIGEO

Se retiró "Residencia habitual de la madre" de `campo_def` (donde había
quedado como solución provisional en `CIERRE_RECARGA_Y_FASE5.md`, ver nota
de esa fecha) y se capturó como estaba previsto desde el principio: en
`caso_sujeto` (rol `MADRE`: `distrito_id` + `direccion`), con selector de
UBIGEO en vez de texto libre.

Esto requerió construir la UI que en la sesión anterior se había
confirmado que no existía:
- `CasoSujeto::guardarSujetos()` ahora acepta `distrito_id`/`direccion` por
  rol (antes solo guardaba `persona_id`).
- `selector-ubigeo.php` tenía el `name="distrito_id"` fijo — solo se podía
  usar una vez por página. Se le agregaron `$nombreCampoDistrito` (default
  `'distrito_id'`, no rompe los 2 usos existentes) y `$distritoRequerido`,
  para poder tener un segundo selector (residencia de la madre) sin que
  choque con el del domicilio del paciente.
- Nuevo parcial `residencia-madre.php`, incluido solo cuando
  `enfermedad.cie10 === 'P96'` (no se generalizó a un mecanismo por rol
  para todas las fichas multi-sujeto — es la única que lo necesita hoy).
- `fichas/ver.php` resuelve y muestra el nombre del distrito (no el
  código) para la vista de solo lectura.

Probado de punta a punta (guardar + leer) dentro de una transacción de
prueba con `ROLLBACK`, sin dejar nada escrito en la base real.

---

## CIERRE_RECARGA_Y_FASE5.md — Parte 0: qué se perdió en la recarga

**Prioridad alta, verificación read-only primero.** La Fase 3 de
`RECARGA_FICHAS.md` hizo `DELETE FROM seccion_def` + `INSERT` completo de las
24 fichas; como `cargar_fichas.php` (Fase 2) no sabía cargar todavía
`campo_def.sensible` ni `depende_de`/`valor_activador` (no eran parte del
esquema del manifiesto v2.1), cualquier valor que solo viviera en la BD y no
en el manifiesto se perdió en silencio. Se comparó el backup
`backups/vigia_pre_fase3_20260722_202718.sql` (restaurado en una BD temporal
`vigia_backup_check`, borrada después) contra el estado post-recarga.

**Confirmado sin pérdida:**
- `campo_def.origen`: 100% `FICHA_MINSA` antes y después (nunca hubo
  `INTERNO`).
- `enfermedad.opciones_clasificacion`, `usa_contactos`, `usa_muestras`,
  `usa_viajes`, `usa_vacunas`: idénticos campo a campo entre backup y estado
  actual para las 24 fichas (`cargar_fichas.php` nunca toca la tabla
  `enfermedad`, así que nunca estuvieron en riesgo). Único cambio: Dengue,
  intencional y ya documentado en la Fase 3.
- `campo_def.sensible` en B04X, B24, Y07: el conteo total cambió por
  consolidación legítima de campos (varios BOOLEANO sueltos → un
  MULTISELECT), pero la proporción sensible/total se mantuvo en 100% donde
  ya era 100%, y los campos nuevos de B04X (comorbilidad VIH) se agregaron
  ya marcados `sensible`.

**Pérdida real, confirmada y corregida:**
1. **`campo_def.sensible` en Z21** (Gestante con VIH y niño expuesto): en el
   backup, **45/45 campos (100%)** estaban marcados `sensible=1` — toda la
   ficha trata de estado VIH de una gestante y su hijo. El manifiesto v2.1
   nunca trasladó esa marca: **0/42 campos** sensibles tras la recarga. Es
   el hallazgo más serio de esta verificación: `sensible=0` hace que
   `CasosController::validarCamposDinamicos()` deje de proteger esos campos
   contra sobrescritura por un usuario sin rol ADMIN al guardar (el acceso a
   la ficha en sí seguía protegido por `Caso::esPrivada()`, que es un
   mecanismo distinto — ver memoria `rol_epidemiologo_no_existe`). Corregido:
   se agregó `"sensible": true` a los 42 campos de Z21 en el manifiesto y se
   recargó esa ficha.
2. **`campo_def.depende_de` / `valor_activador`**: el backup tenía
   exactamente **5 pares** campo-dependiente → campo-disparador; la recarga
   los dejó en **0**. Esto no es solo cosmético: `campoVisiblePorDependencia()`
   (`app/Core/ayudantes.php`) lo usa tanto para ocultar el campo en pantalla
   como para no exigirle obligatoriedad al validar, así que perderlo puede
   bloquear el guardado de una ficha con un campo condicional vacío que
   debería estar oculto. Los 5 pares:
   - Tos ferina (`A37.0`): "Comorbilidad (especificar)" depende de
     "¿Presenta alguna comorbilidad?" = SI.
   - Fiebre amarilla (`A95`): "Necropsia", "Dx macroscópico",
     "Dx microscópico" y una cuarta variable —**"Fecha de necropsia", que
     había desaparecido del manifiesto por completo, no solo la
     dependencia**— dependen de "Condición de egreso" = FALLECIDO. Se
     verificó contra el PDF (págs. 26-27) que las 4 son reales: el formulario
     trae un bloque "Evolución" con "Necroscopia SI/NO", "Dx macroscópico",
     "Dx microscópico" y una segunda fecha, distinta de "Fecha de egreso",
     todos condicionados a que el paciente haya fallecido.
   Corregido en tres partes: (a) manifiesto — se agregó `depende_de`/
   `valor_activador` a los campos existentes y se agregó el campo faltante
   de A95; (b) `cargar_fichas.php` — no soportaba estos atributos; se
   agregó una segunda pasada por ficha (tras insertar todos sus campos, ya
   con id) que resuelve `depende_de` por etiqueta y hace
   `UPDATE campo_def SET depende_de=?, valor_activador=?`, con
   `valor_activador` usando el mismo código de catálogo que
   `catalogo_item.valor` (`mb_strtoupper(slug($opcion))`), no la etiqueta
   visible; `validarManifiesto()` ahora exige que todo `depende_de` apunte a
   una etiqueta que exista en la misma ficha; (c) `verificar_fichas.php` —
   ahora compara `sensible` y `depende_de`/`valor_activador` (resuelto a la
   etiqueta del campo disparador) entre BD y manifiesto, independientemente
   de si el tipo del campo coincide.

**Resultado:** tras recargar Z21, A37.0 y A95 con el cargador ya corregido,
`REPORTE_VERIFICACION.md` vuelve a dar **24/24 ✅ OK**, ahora con esos dos
atributos también verificados.

---

## CIERRE_RECARGA_Y_FASE5.md — Parte 1: las 6 decisiones

Detalle completo por decisión en `CAMBIOS_MANIFIESTO.md` ("Lo que quedaba
para revisión del usuario"). Resumen de lo que cambió en la BD/manifiesto:

1. **PFA (`A80`)**: los 5 valores de clasificación ya vivían en el campo
   propio de la ficha desde la Fase 1 — no hacía falta tocar el manifiesto.
   Lo que faltaba era restringir el núcleo:
   `enfermedad.opciones_clasificacion` pasó de `NULL` (las 4 genéricas) a
   `CONFIRMADO,PROBABLE,DESCARTADO`, igual patrón que Difteria/Fiebre
   amarilla/Tos ferina. No existe (ni en Difteria ni acá) un mecanismo de
   autocompletado núcleo↔detalle: el usuario sigue fijando ambos por
   separado.
2. **Tos ferina**: verificado contra el PDF (pág. 1-2, extraído con
   `pdftotext`) que sí trae una tabla nominal (pregunta 61: N.° / Apellidos
   y Nombres / Parentesco / Celular / Doc. identidad / Lugar de exposición)
   — `usa_contactos=1` es correcto tal cual estaba. Sin cambios.
3. **Parotiditis (`B26`)**: `usa_muestras` bajado de 1 a 0. El manifiesto ya
   tenía `tablas_hijas.caso_muestra=false` documentado desde la Fase 1, pero
   nunca se había aplicado al flag real de `enfermedad` (que
   `cargar_fichas.php` no toca).
4. **Sarampión (`B05`)**: cadena de transmisión implementada vía
   `caso_contacto` (no `campo_def`) — ver Parte 2 para las columnas nuevas.
   Se confirmó además que el widget de contactos si se muestra en pantalla
   para esta ficha: está condicionado a `enfermedad.usa_contactos` (`app/Views/fichas/editar.php`,
   `app/Views/nueva/index.php`), que ya era 1 antes de este cambio — la duda
   dejada en `INFORME_CARGADOR.md` C.4 queda resuelta.
5. **ESAVI Anexo 6.2**: se construyó la capacidad (schema + cargador +
   vista), el contenido sigue diferido. Ver el bloque dedicado más abajo.
6. **Chagas / VIH-SIDA**: contenido agregado al manifiesto, ver el bloque de
   Parte 2 (retiro de campos ad-hoc) más abajo para el detalle de VIH.

### Capacidad de sección condicional (Parte 1.5)

Se replicó a nivel de `seccion_def` el mismo mecanismo que ya existía a
nivel de `campo_def`:

- **Schema** (`sql/36_seccion_condicional.sql`): `seccion_def.depende_de`
  (FK a `campo_def.id`) + `valor_activador`. Mismo cuidado que la FK
  original de `campo_def.depende_de`: antes del `DELETE FROM seccion_def`,
  `cargar_fichas.php` ahora también limpia
  `seccion_def.depende_de` de la enfermedad, o el borrado en cascada choca
  contra la FK (el mismo bug de la Fase 2, ya conocido, evitado esta vez de
  entrada).
- **Cargador**: `procesarFicha()` resuelve `depende_de` de una sección con
  el mismo mapa etiqueta→id que ya arma para campos (una sección depende de
  un *campo*, no de otra sección). `validarManifiesto()` exige
  `valor_activador` y que la etiqueta referenciada exista en la ficha.
- **Vista** (`app/Views/partials/secciones-clinicas.php`): la tarjeta
  `.card.section` completa se envuelve con las mismas clases/atributos
  `dep-wrap`/`data-depende-de`/`data-valor-activador` que ya usa
  `public/js/ficha.js` para campos — el JS es genérico (opera sobre
  cualquier `.dep-wrap[data-depende-de]`), así que no hizo falta tocarlo.
- **Validación de servidor** (`CasosController::validarCamposDinamicos()`):
  ahora también verifica si la *sección* del campo está oculta
  (`CampoDef::porEnfermedad()` trae `seccion_depende_de`/
  `seccion_valor_activador` vía join), no solo si el campo mismo lo está.
- **Sin uso todavía**: `seccion_def.depende_de` está en 0 filas — ninguna
  ficha cargada usa esta capacidad porque el contenido del Anexo 6.2 de
  ESAVI (su único consumidor previsto) sigue diferido. La vista de solo
  lectura (`app/Views/fichas/ver.php`) tampoco oculta secciones
  condicionales, a propósito: ya no ocultaba campos condicionales
  individuales tampoco, así que no se introdujo esa asimetría.

---

## CIERRE_RECARGA_Y_FASE5.md — Parte 2: tablas hija ampliadas y campos ad-hoc retirados

**Schema** (`sql/35_fase5_ampliar_tablas_hija.sql`): `caso_vacuna` +6
columnas (`fabricante`, `lote`, `via`, `sitio`, `fecha_vencimiento`,
`establecimiento`); `caso_sujeto` +2 (`distrito_id`, `direccion`);
`caso_viaje` +2 (`lugar_institucion`, `permanencia_dias`); `caso_contacto`
+4 (`fecha_contacto`, `lugar_contacto`, `fecha_inicio_erupcion`,
`vacunado_72h`, para Sarampión — Parte 1.4).

**Campos ad-hoc retirados de `campo_def`:**
- **ESAVI (`Y59.0`)**, sección "Datos de la vacunación": 9 de 10 campos
  retirados (nombre de vacuna, dosis, vía, sitio, fecha, EE.SS. que vacunó,
  fabricante, lote, fecha de expiración) — ahora se capturan en
  `caso_vacuna`. Se conserva solo "Adyuvante", que no tiene columna
  equivalente en `caso_vacuna`. **Contrapartida real, no oculta**: en el PDF
  estos eran `SELECT` de catálogo cerrado con códigos institucionales (ej.
  "06 Pentavalente"); `caso_vacuna` es una tabla compartida por las 24
  fichas con columnas de texto libre, así que se pierde la validación de
  catálogo al mover el contenido. Se acepta porque el objetivo explícito de
  esta parte es dejar de duplicar la vacunación en `campo_def`, no
  preservar el catálogo cerrado — pero es una regresión real de calidad de
  dato, no cosmética.
- **Tos ferina y Difteria**: el documento de cierre también las nombraba,
  pero se revisaron y **ya estaban correctas desde la Fase 1** — Tos ferina
  ya excluía el listado de dosis (Pentavalente/DPT) de `campo_def` con una
  nota explícita; Difteria no tiene ninguna sección de vacunación en
  `campo_def`. No se tocó nada ahí.
- **Muerte fetal y neonatal (`P96`)**, "Residencia habitual de la madre":
  el documento de cierre decía que esto ya vivía como campo ad-hoc y debía
  moverse a `caso_sujeto.direccion`. Al revisar, **el campo no existía en
  ninguna parte** — ni en el manifiesto ni en la BD (`CAMBIOS_MANIFIESTO.md`
  lo había dado por "sin cambios de fondo, ya estaba completo", lo cual no
  era correcto: se verificó contra el PDF, pág. 28, y la columna
  "RESIDENCIA HABITUAL DE LA MADRE" sí existe en la tabla). Se agregó como
  campo nuevo. **Desviación deliberada del documento de cierre**: en vez de
  `caso_sujeto`, se modeló como `campo_def` con `rol_sujeto: "MADRE"` (el
  mismo patrón que ya usa Z21 para MADRE/NIÑO_EXPUESTO) — `caso_sujeto` en
  el código actual (`CasosController::guardarSujetos`) solo guarda un
  renglón de identidad del sujeto principal y no tiene ningún formulario de
  captura por rol; usar `campo_def.rol_sujeto` es el mecanismo que
  realmente funciona hoy. Las columnas `distrito_id`/`direccion` de
  `caso_sujeto` quedan creadas (por si una futura sesión construye esa UI)
  pero sin ningún dato pasando por ellas todavía.
- **VIH/SIDA (`B24`)**: revertido el aplanamiento de "Vía de transmisión"
  (11 valores → 4: Sexual/Parenteral/Madre-niño/Desconocida) + 2 campos
  "Subtipo" nuevos, cada uno `depende_de` "Vía de transmisión" con su propio
  `valor_activador` (`SEXUAL`/`PARENTERAL`). El documento de cierre pedía un
  solo campo "Subtipo" encadenado; se modeló como dos porque
  `depende_de`/`valor_activador` solo admite un único valor activador por
  campo — funcionalmente equivalente (se muestra el subtipo correcto según
  la vía elegida) pero expresable con el mecanismo que ya existe, sin
  ampliarlo.
- **Chagas (`B57`)**: se agregó "Criterio de descarte" (texto libre, sin
  `depende_de` — mismo criterio que "Dx de descarte" en Fiebre amarilla y
  "Criterio de clasificación" en PFA/Fiebre amarilla, ninguna de las cuales
  usa dependencia condicional para su campo de detalle).

**Resultado:** dry-run limpio en las 24 fichas, aplicado de verdad,
`REPORTE_VERIFICACION.md` vuelve a dar **24/24 ✅ OK**.

**Lo que sigue sin construirse** (más allá de "Datos de la madre" resuelto
arriba con el atajo de `rol_sujeto`): un mecanismo real de "qué columnas
muestra cada ficha" para los widgets de tabla hija. El documento de cierre
asumía que ya existía uno para `caso_contacto` ("usar la configuración por
ficha que ya existe"); se verificó el código
(`app/Views/partials/tablas-hijas/contactos.php`) y **no existe ninguno** —
el propio comentario del archivo decía "se muestran siempre... por
simplicidad". Se siguió ese mismo criterio ya establecido para las columnas
nuevas de `vacunas.php`/`contactos.php` (mostrarlas siempre) en vez de
construir un motor de configuración por ficha desde cero, que es una
funcionalidad separada y considerablemente más grande que ampliar 4 tablas.

---

## CIERRE_RECARGA_Y_FASE5.md — Parte 3: prueba de humo

**Limitación honesta primero:** no hay navegador real disponible en este
entorno de ejecución (no hay Playwright/Puppeteer ni herramienta de
captura de pantalla), y no se conoce la contraseña real del usuario
ADMIN sembrado (su hash en `01_esquema_vigia.sql` es un placeholder,
`$2y$10$REEMPLAZAR_ESTE_HASH_BCRYPT`) para poder loguearse por HTTP sin
tocar credenciales reales. Por lo tanto **no se hizo QA visual** ni se
probó el flujo completo de login → guardar → reabrir a través del
navegador. Lo que sigue es una prueba de humo a nivel de plantilla PHP:
confirma ausencia de errores fatales/avisos y que la lógica condicional
responde al estado, pero no defectos visuales (desbordes de CSS,
solapamiento, comportamiento del JS en el navegador real).

**Qué se probó:**
1. Se renderizó `secciones-clinicas.php` (la plantilla única que dibuja
   secciones/campos dinámicos, la misma para "Nueva ficha" y "Editar
   ficha") directamente con datos reales de la BD, para las 5 fichas de
   referencia del documento (Difteria, PFA, Sarampión, Muerte materna,
   Mpox) más las dos fichas con dependencias condicionales nuevas o
   restauradas (VIH/SIDA, Fiebre amarilla) — una vez con
   `$valoresCampos` vacío (todo campo/sección condicional debe quedar
   oculto) y otra vez forzando el valor disparador (debe mostrarse). Las
   7 renderizaron sin excepciones ni avisos de PHP (`error_reporting(E_ALL)`).
   El tamaño en bytes cambió entre las corridas "vacío"/"activado" para
   A95 (58766 → 58750) y B24, confirmando que el atributo `hidden` de
   `.dep-wrap` sí responde al valor del campo disparador — no es una
   ruta muerta.
2. Se renderizaron los 3 partials de tabla hija (`contactos.php`,
   `vacunas.php`, `viajes.php`) con listas vacías: sin errores.
3. Se probó el flujo completo de guardado/lectura de `CasoVacuna::reemplazarTodos()`
   y `CasoContacto::reemplazarTodos()` con las columnas nuevas, dentro de
   una transacción creada solo para la prueba y con `ROLLBACK` al final
   (se verificó después que `caso`/`caso_vacuna`/`caso_contacto` quedaron
   exactamente como antes: 1/0/0 filas) — confirma que el SQL de las
   columnas nuevas es válido de punta a punta, no solo que la vista
   compila.

**Qué NO se probó** (requiere navegador real y sesión de ADMIN, pendiente
para el usuario o una sesión con esas herramientas disponibles):
- Que los desplegables/controles se vean bien (sin desbordes, sin
  etiquetas concatenadas — ya pasó antes con `GRUPO_SI_NO`, según el
  propio `CIERRE_RECARGA_Y_FASE5.md`).
- Que el JS de `ficha.js` oculte/muestre en vivo la sección condicional
  nueva al cambiar el campo disparador en pantalla (la lógica de
  `evaluarDependencias()` es genérica sobre `.dep-wrap[data-depende-de]`
  y ya se usaba para campos, pero no se interactuó con un navegador para
  confirmarlo con una sección completa).
- Guardar y reabrir una ficha real de punta a punta por la UI.

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
