# VIGÍA · Recarga completa de fichas desde una fuente única

Objetivo: dejar las **23 fichas** alineadas a una sola fuente de verdad de una
sola vez, de modo que al revisarlas después ficha por ficha, la mayoría ya esté
completa y solo reste comparar contra el PDF. No más SQL suelto por lote.

Base: `INFORME_CARGADOR.md`, `REPORTE_VERIFICACION.md`, `manifiesto_fichas.json`
y `verificar_fichas.php`, ya generados. Siguen vigentes las reglas de diseño de
`PLAN_CLAUDE_CODE.md`.

> Ejecutar por fases y **detenerse a validar entre cada una**. Respaldar la base
> de datos antes de la Fase 3.

---

## Advertencia sobre el alcance de la automatización

El verificador compara la base contra `manifiesto_fichas.json`, y ese manifiesto
se construyó desde `DEFINICION_FICHAS.md` / `DEFINICION_FICHAS_B_C_D.md`, **no
desde el PDF MINSA**. Por lo tanto:

- Se puede automatizar dejar las 23 fichas **idénticas al manifiesto**.
- "Idéntico al manifiesto" **no** garantiza "idéntico a MINSA" si el `.md`
  traía un error de transcripción.

Por eso la Fase 1 valida el manifiesto contra el PDF **una sola vez**. Después de
esa validación, todo lo demás es automático y confiable. La comparación final
contra el papel, ficha por ficha, la seguirá haciendo el usuario — pero sobre
fichas que ya estarán completas y consistentes.

---

## FASE 1 — Validar y corregir el manifiesto contra el PDF

Fuente de verdad: el **PDF de fichas MINSA**, no los `.md`.

1. Para cada una de las 23 fichas, comparar `manifiesto_fichas.json` contra la
   ficha correspondiente del PDF: secciones, campos, tipos y —esto es nuevo—
   las **opciones de cada catálogo** (los valores de cada SELECT / MULTISELECT /
   GRUPO_SI_NO).
2. Corregir en el manifiesto lo que no coincida con el PDF: agregar lo que falte,
   quitar lo que el PDF no pida, ajustar tipos.
3. Ampliar el esquema del manifiesto para que **cada campo de opciones cerradas
   incluya su lista de opciones**. Hoy el manifiesto trae el tipo pero no las
   opciones; sin ellas no se puede cargar el catálogo ni verificarlo.
4. Aplicar la **regla de frontera** (INFORME_CARGADOR.md, Parte C) al manifiesto:
   - Quitar del manifiesto lo que debe vivir en tabla hija, no en `campo_def`
     (ej. la sección "Migración" de Chagas → `caso_viaje`).
   - Marcar qué tablas hijas usa cada ficha (`usa_contactos`, `usa_muestras`,
     `usa_viajes`, `usa_vacunas`) y con qué columnas.

**Entregable:** `manifiesto_fichas.json` corregido y un `CAMBIOS_MANIFIESTO.md`
que liste, por ficha, qué se cambió respecto de la versión anterior y contra qué
página/sección del PDF se validó. **Detenerse aquí** para revisión del usuario
antes de tocar la base de datos.

---

## FASE 2 — Cargador único idempotente

Reemplazar los ~12 SQL sueltos por **un solo cargador** que lee el manifiesto y
genera las definiciones. Es la corrección de raíz que propone el informe.

Requisitos (de INFORME_CARGADOR.md):

1. **Idempotente por diseño.** Por cada enfermedad, dentro de una transacción:
   `DELETE FROM seccion_def WHERE enfermedad_id = ?` (cascada a `campo_def` por
   la FK) y luego insertar desde el manifiesto. Correrlo dos veces deja el mismo
   resultado, nunca duplicados.
2. **Falla dura, nunca degradación silenciosa.** Si un campo es
   SELECT / MULTISELECT / GRUPO_SI_NO y no trae opciones en el manifiesto, el
   cargador **aborta con error** — jamás inserta con `catalogo_id NULL`.
   Si un tipo no es reconocido, aborta; no lo convierte en `TEXTO`.
3. **Convención de claves única** para todas las fichas (elegir una y aplicarla:
   `{cie10}_{slug}` es razonable). Nada de sufijos hexadecimales ni convenciones
   por lote.
4. **Protege los datos capturados.** Antes del `DELETE`, si algún `campo_def` de
   esa enfermedad tiene `caso_valor` asociados, no borrar a ciegas: reportar esos
   casos y pedir confirmación (ver Fase 3 para dengue). En una base sin datos
   reales, esto no dispara nada.
5. Manejo correcto de catálogos: crear/actualizar `catalogo` y `catalogo_item`
   desde las opciones del manifiesto, reutilizando catálogos compartidos
   (sexo, sí/no, resultado_lab) en vez de duplicarlos por ficha.

**Entregable:** `cargar_fichas.php` (o comando equivalente) + su documentación de
uso. **No ejecutarlo todavía sobre la base** — mostrar primero, en seco, qué haría
(modo `--dry-run` que imprime el plan sin escribir).

---

## FASE 3 — Recarga de las 23 fichas

1. **Respaldar la base de datos.**
2. **Dengue (A97):** los 3 casos existentes son registros de prueba generados por
   el propio sistema, **no personas reales** — se pueden descartar. Antes de
   recargar dengue: `DELETE` de esos `caso` de prueba y sus `caso_valor`
   asociados, para que el `DELETE` de `campo_def` no choque con la FK. Registrar
   en un comentario que eran datos de prueba.
3. Correr el cargador único sobre las **23 fichas** desde el manifiesto validado.
4. Retirar de circulación los ~12 SQL sueltos y los archivos de parche
   (`23_limpieza…`, `28_fix…`, `31_fix_difteria…`, etc.): moverlos a una carpeta
   `sql/historico/` con un README que explique que quedaron reemplazados por el
   cargador único. No borrarlos — son la evidencia del historial.
5. Actualizar `01_esquema_vigia.sql` si el esquema base incluía secciones
   sembradas a mano (ej. la sección inventada de dengue en la línea ~532): debe
   nacer sin definiciones de ficha, que ahora las pone el cargador.

**Entregable:** base recargada, más un `README` en `sql/historico/`.

---

## FASE 4 — Ampliar el verificador y correr la verificación final

1. Añadir a `verificar_fichas.php` el chequeo que hoy le falta
   (INFORME_CARGADOR.md, A.2b y limitación 2 del reporte):
   - Todo campo SELECT / MULTISELECT / GRUPO_SI_NO debe tener
     `catalogo_id IS NOT NULL`.
   - Ese catálogo debe tener al menos un `catalogo_item`.
   - Las opciones del catálogo deben coincidir con las del manifiesto.
2. Volver a correr la verificación completa.

**Criterio de éxito:** las 23 fichas en estado ✅ OK, sin faltantes, sin
sobrantes, sin tipos incorrectos y sin catálogos vacíos. Dengue incluido y con
entrada en el manifiesto (ya no queda "sin auditar").

**Entregable:** `REPORTE_VERIFICACION.md` regenerado, idealmente con las 23 en OK.
Si alguna no llega a OK, listar por qué — puede ser una diferencia legítima que
requiera criterio humano, no un fallo del cargador.

---

## FASE 5 — Estructuras hija pendientes (del informe, Parte C)

Resolver de una vez las tablas hijas angostas que el informe detectó, para que la
revisión ficha por ficha posterior no tropiece con ellas:

1. **`caso_vacuna`**: agregar `fabricante`, `lote`, `via`, `sitio`,
   `fecha_vencimiento` (todas NULL-ables). Elimina los campos sueltos de
   vacunación en ESAVI, tos ferina y difteria.
2. **`caso_sujeto`**: agregar `distrito_id` y `direccion` para los sujetos que lo
   necesitan (madre en muerte fetal, gestante/niño en las fichas materno-
   perinatales).
3. **`caso_viaje`**: agregar `lugar_institucion` y `permanencia_dias` para cubrir
   "Lugar probable de infección" (difteria, fiebre amarilla, Chagas, Carrión).
4. Ajustar el manifiesto y recargar las fichas afectadas con el cargador único
   (ya es idempotente, así que es seguro).

---

## Lo que esta tarea debe respetar

- No inventar campos para "completar" una ficha. Si el PDF no lo pide, no va.
- No degradar tipos: si algo es MATRIZ o GRUPO_SI_NO en el PDF, se carga así, no
  como TEXTO ni como BOOLEANOs sueltos.
- No tocar `theme.css` ni la interfaz.
- Datos capturados reales (no los hay salvo los de prueba de dengue) nunca se
  borran sin confirmación explícita.

---

## Verificación final

- [ ] El manifiesto fue validado contra el PDF y quedó documentado qué se corrigió
- [ ] El manifiesto incluye las opciones de cada campo de opciones cerradas
- [ ] Existe un único cargador idempotente; los SQL por lote quedaron archivados
- [ ] El cargador falla si un campo cerrado no trae opciones (no inserta NULL)
- [ ] Los 3 casos de prueba de dengue se eliminaron y dengue se recargó
- [ ] El verificador ahora revisa catálogos, no solo tipos
- [ ] Las 23 fichas quedan en OK, o se explica por qué alguna no
- [ ] `caso_vacuna`, `caso_sujeto` y `caso_viaje` ampliadas
- [ ] `theme.css` sin cambios; sin emojis ni librerías externas
