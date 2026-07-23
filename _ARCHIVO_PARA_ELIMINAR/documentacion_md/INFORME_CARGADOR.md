# VIGÍA · Informe de auditoría del cargador de fichas

> Diagnóstico solamente. No se modificó ninguna definición de ficha ni la
> base de datos. Corrección propuesta al final de cada punto — **no aplicada**.

---

## Parte A — Auditoría del cargador

### 0. Hallazgo previo a las cuatro preguntas: no existe un "cargador"

No hay ningún script (PHP, ETL, parser de Markdown) que lea
`DEFINICION_FICHAS.md` / `DEFINICION_FICHAS_B_C_D.md` y genere `seccion_def` /
`campo_def` — se buscó en `scripts/` (solo contiene `migrar_usuarios.php` y un
backup de BD) y en `app/` (no hay ningún comando de importación de fichas).

Lo que existió en su lugar fueron **12 archivos SQL con `INSERT` escritos a
mano** (`sql/07_fase7_fichas_grupo_a.sql`, `15_lote1_fichas.sql`,
`16_lote2_fichas.sql`, `18_lotes_5.sql`/`18_lotes_5_8.sql`,
`19_lotes_3_4.sql`, `22_lote4_sarampion.sql`, `24…27_lote5…8_*.sql`),
producidos en sesiones distintas, transcribiendo la prosa de los documentos
de definición directamente a sentencias `INSERT`. Esto se confirma con solo
tres observaciones:

- Cada archivo usa una **convención de `clave` distinta**: `a37_...`
  (prefijo CIE-10, Lote 1-2), `slug_con_sufijo_hex_1a9b` (Lote 3-4, con un
  sufijo hexadecimal aleatorio inexistente en los otros lotes) y
  `nombre_corto_mm`/`_esavi`/`_src`/`_ffn` (Lotes 5-8, sin relación con las
  otras dos convenciones).
- Cada archivo usa una **lista de columnas distinta** en su `INSERT INTO
  campo_def (...)` — el Lote 3-4 directamente omite la columna
  `catalogo_id` de esa lista (ver punto A.2).
- No hay ninguna función, clase o `include` común entre los archivos: cada
  uno es autocontenido.

Es decir: la falla no es de un algoritmo que "se equivocó" de forma
sistemática, sino de **transcripción manual repetida sin herramienta ni
verificación**, por lo que cada uno de los defectos de abajo aparece con
severidad distinta según qué tan cuidadosa fue la sesión que escribió ese
lote en particular. La corrida real de `verificar_fichas.php`
(`REPORTE_VERIFICACION.md`) confirma que el patrón se repite en 22 de las 23
fichas evaluadas — difteria (ya reconstruida en `AUDITORIA_FICHA_DIFTERIA.md`)
es la única sin diferencias.

### A.1 — ¿Por qué se truncan/faltan secciones?

No hay límite de longitud, corte por salto de línea ni parseo de tablas
markdown que se detenga: no hay parseo en absoluto. Las secciones y campos
faltantes son **omisiones humanas** al transcribir documentos largos y muy
repetitivos, sin ningún paso que las detectara — este mismo proyecto de
auditoría es la primera vez que se compara sistemáticamente contra la fuente.

Evidencia (más allá de difteria, ya resuelta en `31_fix_difteria.sql`):

- **Sarampión/rubéola (`B05`)** — falta por completo la sección "Cadena de
  transmisión" que `DEFINICION_FICHAS.md:735-738` pide explícitamente
  (nombre, edad, dirección, celular, fecha de contacto, lugar de contacto,
  fecha de inicio de erupción, fecha de vacunación, vacunado dentro de 72 h
  → `caso_contacto`). No hay ningún rastro de esa sección ni en
  `seccion_def` ni en el código de vistas.
- **ESAVI severo (`Y59.0`)** — la sección "Datos de la vacunación" solo
  cargó 7 de los ~10 campos: faltan *Nombre de vacuna*, *Adyuvante* y
  *Dosis* (verificado directamente contra la tabla: `campo_def` para esa
  sección tiene ids 785-791, ninguno de esos tres).
- **Enfermedad de Carrión (`A44`)** — falta la sección completa
  "Laboratorio y evolución" (hemoglobina, hematocrito, transfusiones,
  antibióticos usados).
- **Tétanos, Tétanos neonatal, PFA** — a cada una le faltan 2-5 secciones
  completas (ver detalle en `REPORTE_VERIFICACION.md`); son justamente las
  tres fichas de `19_lotes_3_4.sql`, el archivo con más señales de prisa
  (ver A.2).

**Corrección propuesta (no aplicada):** dejar de escribir SQL a mano lote por
lote. Cargar siempre desde `manifiesto_fichas.json` (este mismo manifiesto,
ya construido como Parte B) con un único script PHP que itere el JSON y
genere los `INSERT`, para que "falta transcribir un campo" deje de ser
posible sin que además falte en el manifiesto versionado y por tanto sea
visible en un diff de PR.

### A.2 — ¿Por qué se degradan los tipos (a `TEXTO`/`BOOLEANO`)?

No existe ningún `default a TEXTO cuando no reconoce el tipo` en código —
otra vez, no hay código que "reconozca" nada. Se identificaron **dos
mecanismos distintos**, ambos de autoría manual:

**(a) Inconsistencia de transcripción.** Para un campo de opciones cerradas
a veces se construyó su catálogo (`INSERT INTO catalogo` + `catalogo_item` +
`SELECT`/`GRUPO_SI_NO`/`MULTISELECT`) y para el campo vecino, igualmente
cerrado, se usó `TEXTO` o una serie de `BOOLEANO` sueltos — sin ninguna regla
que obligara a lo primero. Ejemplo verificable en el propio
`15_lote1_fichas.sql` (difteria, antes del fix): en la misma sección
"Evolución", `a36_signos_cl_nicos` se cargó como `GRUPO_SI_NO` con catálogo
armado dos líneas antes, pero `a36_hospitalizado`,
`a36_aislamiento_domiciliario` y `a36_estuvo_en_contacto...` se cargaron como
`TEXTO` puro, pese a que la ficha MINSA los presenta con las mismas
casillas cerradas (Sí/No/Ignorado). El mismo patrón, con "opciones
MULTISELECT aplanadas a una lista de columnas BOOLEANO sueltas" (una fila
`campo_def` por cada casilla en vez de un catálogo), se repite de forma
masiva en el Lote 2: Varicela (`b01_lesi_n_m_cula/p_pula/ves_cula/costra`),
Parotiditis, Leishmaniasis (12 síntomas sueltos en vez de un
`MULTISELECT`), Chagas (17 signos "etapa aguda/crónica" sueltos en vez de
`GRUPO_SI_NO`) y Carrión (39 síntomas sueltos). Todo esto está confirmado,
campo por campo, en `REPORTE_VERIFICACION.md`.

**(b) Bug de plantilla de `INSERT`, ya documentado por el propio proyecto.**
`sql/19_lotes_3_4.sql` (EDA/cólera, Tétanos, Tétanos neonatal, PFA) usa una
lista de columnas para `INSERT INTO campo_def` que **omite la columna
`catalogo_id` por completo** (`seccion_id, clave, etiqueta, tipo, orden,
obligatorio, sensible, rol_sujeto, config` — sin `catalogo_id`). El
resultado: cualquier campo `SELECT`/`MULTISELECT`/`GRUPO_SI_NO` de ese
archivo nace con `catalogo_id = NULL`, es decir, con el tipo correcto pero
sin ninguna opción que mostrar (dropdown/checklist vacío). Esto ya fue
detectado y parcheado a medias en `sql/28_fix_lote3_catalogos.sql`, cuyo
propio comentario lo describe: *"bug pre-existente de 19_lotes_3_4.sql: sus
campos SELECT, MULTISELECT y GRUPO_SI_NO se crearon todos con catalogo_id
NULL"*. El fix condensó además varias estructuras `MATRIZ` a `SELECT` "donde
el proyecto ya venía haciéndolo en lotes anteriores" — una tercera
inconsistencia: la misma estructura conceptual (p.ej. una lista de síntomas)
se modela como `MATRIZ` en un lote y como `SELECT`/`GRUPO_SI_NO` en otro,
según el criterio de quien escribió cada archivo.

**Corrección propuesta (no aplicada):**
1. El futuro cargador único (A.1) debe **fallar duro** si un campo del
   manifiesto tiene tipo `SELECT`/`MULTISELECT`/`GRUPO_SI_NO` y no trae una
   lista de opciones — nunca insertar con `catalogo_id NULL` silenciosamente.
2. Agregar una regla de auditoría (ya cubierta por `verificar_fichas.php`)
   que además revise, para todo campo con esos tres tipos, que
   `catalogo_id IS NOT NULL` — hoy el verificador compara tipo pero no esta
   integridad; se puede añadir como chequeo adicional sin tocar el
   manifiesto.

### A.3 — ¿De dónde salen los campos inventados?

Origen confirmado, con nombre de archivo y línea: **`sql/07_fase7_fichas_grupo_a.sql`**,
un seed anterior al sistema de "Lotes" (ejecutado en la "Fase 7", antes de
que existieran `DEFINICION_FICHAS.md` y `DEFINICION_FICHAS_B_C_D.md`). Ese
archivo sembró una sección genérica **"Cuadro clínico" / "Signos de
alarma"** para 5 enfermedades — Sarampión (id 2), Tos ferina (id 3),
Leishmaniasis (id 4), **Difteria (id 5)** y Fiebre amarilla (id 6) — con
campos redactados a partir de criterios clínicos generales de vigilancia
epidemiológica (útiles y no descabellados médicamente, pero **no
transcritos del PDF MINSA**), como *placeholder* para poder probar el motor
de fichas antes de tener las definiciones reales. El propio comentario del
archivo lo dice: *"Define seccion_def/campo_def para las enfermedades del
Grupo A que aún no tenían cuadro clínico propio"*.

El problema no fue sembrar ese placeholder — fue que, cuando llegaron los
Lotes 1 y 2 con las secciones reales (`15_lote1_fichas.sql`,
`16_lote2_fichas.sql`), **ningún archivo borró el seed de Fase 7 antes de
insertar lo nuevo**. No existe un solo `DELETE FROM seccion_def WHERE
enfermedad_id = ...` antes de los `INSERT` de esos lotes. El resultado: las
5 fichas quedaron con la sección inventada conviviendo con la real. Para
difteria, esto es exactamente "Signos de alarma" (edema cervical, disnea,
sospecha de miocarditis) que motivó este proyecto de auditoría —
`AUDITORIA_FICHA_DIFTERIA.md` ya lo identificó y `31_fix_difteria.sql` lo
eliminó (`DELETE FROM seccion_def WHERE enfermedad_id = 5`, comentario:
*"no existe en la ficha MINSA, fue inventada al cargar"*).

**Lo que queda sin auditar de este mismo origen:**
- **Dengue/chikungunya/zika (`A97`, id 1)** también tiene una sección
  "Signos de alarma" sembrada en `01_esquema_vigia.sql:532`, de la misma
  época — y dengue es justamente la ficha marcada como *stub fuera de spec*
  en la auditoría anterior (ver memoria `pendiente-auditoria-fichas`). No se
  tocó aquí porque dengue ya tiene 3 casos reales y su reconstrucción
  necesita plan de migración de datos, tal como se dejó anotado.
- Sarampión (id 2), Tos ferina (id 3), Leishmaniasis (id 4) y Fiebre
  amarilla (id 6) **sí tuvieron su seed de Fase 7 limpiado**, pero por un
  archivo posterior de limpieza (`23_limpieza_duplicados_lote1_2.sql`), no
  porque el lote que insertó lo real se hiciera cargo — es decir, el defecto
  se coló primero y se corrigió después como parche aparte, en vez de
  evitarse en origen.

**Corrección propuesta (no aplicada):** el futuro cargador único (A.1) debe
insertar las definiciones de una enfermedad dentro de una transacción que
primero haga `DELETE FROM seccion_def WHERE enfermedad_id = ?` (cascada a
`campo_def` por la FK `ON DELETE CASCADE`) y luego inserte desde el
manifiesto — así "reemplazar" una ficha nunca puede dejar restos de una
versión anterior, sea un seed viejo o un lote reejecutado.

### A.4 — ¿Es idempotente?

No. Cada archivo es `INSERT` puro: no hay `ON DUPLICATE KEY UPDATE`, no hay
`INSERT IGNORE`, no hay `DELETE` previo condicionado a que ya exista algo.
Correrlo dos veces duplicaría secciones y campos completos.

Esto no es una hipótesis: **ya ocurrió**. `sql/23_limpieza_duplicados_lote1_2.sql`
existe específicamente para borrar secciones duplicadas exactas que aparecieron
en producción — su propio comentario dice: *"Limpia secciones/campos
placeholder que quedaron duplicados/mezclados con la definición real de
Lotes 1-2, probablemente por una re-ejecución parcial de su script de
siembra en una sesión anterior"*. Ese archivo tuvo que borrar, entre otras
cosas, **dos copias exactas de sobra** de la sección "Cuadro clínico" de tos
ferina (`seccion_def.id` 11 y 12, conservando la 10) y un caso real de Fiebre
amarilla (id 3) que ya tenía `caso_valor` guardados apuntando a los campos
viejos duplicados — tuvo que borrarse el valor antes que el campo por la FK
`RESTRICT`.

**Corrección propuesta (no aplicada):** el cargador único (A.1) debe ser
idempotente por diseño: `DELETE` + `INSERT` dentro de una transacción por
enfermedad (ver A.3), nunca `INSERT` a secas. Esto también hace que
recargar una ficha corregida sea una operación segura de repetir, en vez de
un riesgo cada vez que se toca.

---

## Parte C — Frontera tabla hija / `campo_def`

Regla del proyecto (ya correcta como principio): pregunta contextual única
→ `campo_def`; lista repetible de N filas → tabla hija. La auditoría
encontró que el fallo real no es que se haya aplicado mal esa regla en
general, sino dos variantes más específicas:

### C.1 — Duplicación confirmada: Chagas y `caso_viaje`

**Enfermedad de Chagas (`B57`, id 12)** tiene `usa_viajes = 1` (la tabla
hija `caso_viaje` está habilitada para esta ficha) **y además** una sección
`campo_def` llamada "Migración" (`seccion_def.id = 39`, 3 campos: "Lugar
probable de contagio (dpto/prov/dist/localidad)", tiempo de permanencia,
listado de localidades visitadas). El propio `DEFINICION_FICHAS.md:347-349`
dice explícitamente **"Migración → `caso_viaje`"** — es decir, ese dato ya
tiene su lugar en la tabla hija y no debería tener, además, tres campos
sueltos que permiten registrar la misma información (dónde estuvo, cuánto
tiempo) por un camino paralelo. Con ambos caminos abiertos, nada impide que
un registrador llene la tabla hija y otro llene los campos sueltos con
datos distintos para el mismo caso.

**Corrección propuesta (no aplicada):** eliminar la sección "Migración" de
`campo_def` para Chagas y mover ese contenido a `caso_viaje` únicamente
(mismo patrón que ya usan Difteria y Fiebre amarilla, que si declaran
`usa_viajes=1` sin una sección `campo_def` paralela).

### C.2 — Caso legítimo pero revelador: ESAVI y `caso_vacuna`

**ESAVI severo (`Y59.0`)** tiene una sección `campo_def` completa "Datos de
la vacunación" (fabricante, lote, vía, sitio, fecha de expiración) **a
propósito** — el propio `DEFINICION_FICHAS_B_C_D.md:329` lo anota como
*"complementa `caso_vacuna`"*, porque esa tabla hija solo tiene tres
columnas (`vacuna`, `dosis`, `fecha`) y no alcanza para lo que ESAVI necesita
registrar. Esto no es un error de carga: es una decisión de diseño explícita
y documentada. Pero expone un problema estructural real: `caso_vacuna` es
demasiado angosta para cualquier ficha que pida ese nivel de detalle, y cada
ficha que lo necesite puede terminar reinventando su propio set de campos
sueltos en vez de que la tabla hija se amplíe una sola vez. Tos ferina y
Difteria, por ejemplo, también preguntan "vía de administración" en sus
antecedentes vacunales (`DEFINICION_FICHAS.md:104-109, 227-229`) y hoy no
tienen dónde guardarlo salvo, otra vez, campos sueltos.

**Corrección propuesta (no aplicada):** ampliar `caso_vacuna` con las
columnas que ya se repiten entre fichas (`fabricante`, `lote`, `via`,
`sitio`, `fecha_vencimiento`, todas `NULL`-ables) en vez de dejar que cada
ficha nueva las modele por separado en `campo_def`. Es el mismo patrón que
ya se aplicó bien para `caso_contacto` en `30_motor_dependencias_y_nucleo.sql`
(se le agregaron `edad`, `sexo`, `vacunado`, `fecha_vacunacion`,
`profilaxis` cuando varias fichas lo pidieron a la vez).

### C.3 — Mismo problema, otro lado: `caso_sujeto` sin dirección

**Muerte fetal y neonatal (`P96`)** tiene una sección `campo_def` "Residencia
habitual de la madre" (departamento/provincia/distrito) para el sujeto de
rol `MADRE`. `caso_sujeto` (la tabla que modela sujetos que no son el
paciente índice) tiene columnas para nombre/documento/sexo/edad pero
**ninguna de dirección o ubigeo** — por eso esos 3 campos no pudieron ir a
la tabla hija ni por error: no hay dónde ponerlos ahí todavía. Igual que en
C.2, esto no es un campo mal puesto sino una tabla hija incompleta para lo
que las fichas multi-sujeto (Lotes 5-8) realmente necesitan.

**Corrección propuesta (no aplicada):** decidir, junto con quien revise las
fichas multi-sujeto, si `caso_sujeto` debe ganar columnas de ubicación
(`distrito_id`, `direccion`) para los sujetos que sí las necesitan (madre,
recién nacido), en vez de resolverlo ficha por ficha en `campo_def`.

### C.4 — Sección que debería vivir en tabla hija y hoy no vive en ningún lado

**Sarampión/rubéola (`B05`)**, además de tener `usa_contactos = 1`, le falta
por completo la sección "Cadena de transmisión" (ver A.1) que
`DEFINICION_FICHAS.md` pide modelar en `caso_contacto`. Esto no es una
duplicación sino el caso inverso a C.1: la tabla hija está habilitada
(`usa_contactos=1`) pero no hay evidencia de que la ficha realmente ofrezca
capturar esos contactos — vale la pena que quien retome esta enfermedad
revise `app/Views/partials/*.php` para confirmar si el widget de
`caso_contacto` se muestra igual para todas las fichas con `usa_contactos=1`
o si depende de configuración adicional que a Sarampión le falte.

---

## Resumen ejecutivo

| # | Defecto | Causa raíz | Alcance confirmado (ver REPORTE_VERIFICACION.md) |
|---|---|---|---|
| A.1 | Secciones/campos faltantes | Omisión de transcripción manual, sin verificación | Sarampión, ESAVI, Carrión, Tétanos, Tétanos neonatal, PFA, y más |
| A.2(a) | MULTISELECT/GRUPO_SI_NO aplanado a BOOLEANOs sueltos | Inconsistencia de transcripción | Varicela, Parotiditis, Leishmaniasis, Chagas, Carrión |
| A.2(b) | `catalogo_id NULL` en campos SELECT/MULTISELECT/GRUPO_SI_NO | Bug de plantilla de INSERT en `19_lotes_3_4.sql` | EDA/cólera, Tétanos, Tétanos neonatal, PFA (parcialmente corregido en `28_fix_lote3_catalogos.sql`) |
| A.3 | Campos/secciones inventados | Seed de placeholder de Fase 7 (`07_fase7_fichas_grupo_a.sql`) nunca borrado al cargar lo real | Dengue (sin auditar), Difteria (ya corregido) |
| A.4 | No idempotente | `INSERT` puro sin `DELETE`/`UPSERT` previo | Ya causó duplicados reales en Tos ferina y Fiebre amarilla (`23_limpieza_duplicados_lote1_2.sql`) |
| C.1 | Dato duplicable en 2 caminos | Sección `campo_def` paralela a una tabla hija ya habilitada | Chagas / `caso_viaje` |
| C.2 | Tabla hija angosta | `caso_vacuna` solo tiene 3 columnas | ESAVI (resuelto ad-hoc), Tos ferina/Difteria (pendiente) |
| C.3 | Tabla hija angosta | `caso_sujeto` sin columnas de dirección | Muerte fetal y neonatal |
| C.4 | Sección faltante en tabla hija habilitada | Mismo mecanismo que A.1 | Sarampión / `caso_contacto` |

Ninguna corrección de esta lista se aplicó. El detalle campo por campo,
ficha por ficha, está en `REPORTE_VERIFICACION.md`, generado por
`verificar_fichas.php` contra `manifiesto_fichas.json`.
