# VIGÍA · Cambios del manifiesto (v1.0 → v2.0), Fase 1 de RECARGA_FICHAS.md

> Solo se tocó `manifiesto_fichas.json`. No se modificó la base de datos, el
> código ni las definiciones de ficha. `REPORTE_VERIFICACION.md` se
> regeneró contra este manifiesto corregido, únicamente para confirmar que
> el verificador sigue funcionando — no para aplicar nada.

## Qué cambió de v1.0 a v2.0

v1.0 (generada en `AUDITORIA_CARGADOR.md`) se construyó a partir de
`DEFINICION_FICHAS.md`/`DEFINICION_FICHAS_B_C_D.md`. Esta versión se validó
en cambio contra **el PDF de fichas MINSA** que compartiste
(`INFORME PARA APLICATIVO DE EPIDEMIOLOGIA_removed.pdf`), que resultó ser el
compilado real de las 23 fichas (y Dengue, fuera de alcance). Se encontraron
diferencias reales entre el `.md` y el PDF en varias fichas — es decir, el
`.md` mismo tenía errores de transcripción, tal como advertía
`RECARGA_FICHAS.md`.

**Cambios de esquema** (aplican a las 23 fichas):
- Todo campo `SELECT`/`MULTISELECT` trae ahora `"opciones": [...]` con las
  alternativas de respuesta tal como aparecen en el PDF.
- Todo campo `GRUPO_SI_NO` trae `"opciones": [...]` con la lista de
  ítems/preguntas que se responden Sí/No/Ignorado (el patrón de respuesta es
  fijo para el tipo; lo que varía por ficha es la lista de preguntas).
- Todo campo `MATRIZ` trae `"filas": [...]` y `"columnas": [...]`.
- Cada ficha trae un objeto `"tablas_hijas"` con qué tabla hija
  (`caso_contacto`/`caso_muestra`/`caso_viaje`/`caso_vacuna`) usa, y una
  `"_observaciones_tablas_hija"` donde el flag de la BD no calzaba con lo
  que el PDF realmente pide.
- Los campos con una corrección de fondo (no solo agregado de opciones)
  llevan una nota `"_corregido"` explicando qué cambió y por qué.

## Correcciones de contenido encontradas, ficha por ficha

### Tos ferina (`A37.0`) — PDF pág. 1-2
- **Tipo**: "¿Se diagnosticaron otras infecciones por laboratorio?" era
  `BOOLEANO` en v1.0; el PDF lo presenta como Sí(especificar)/No/Desconocido
  → `SELECT`.
- **Sección nueva**: "Antecedentes vacunales (preguntas contextuales)" — 5
  campos que v1.0 no tenía (¿madre vacunada con Tdap en la gestación? +
  fecha; ¿gestante recibió Tdap? + semana + fecha). El listado de dosis
  recibidas (Pentavalente, DPT) sigue yendo a `caso_vacuna`, pero estas 5
  preguntas son de valor único y van en `campo_def`.
- **Matriz corregida**: "Contactos por lugar" — v1.0 no traía filas/columnas;
  se agregaron. La fila "Universidad/Instituto" que trae
  `DEFINICION_FICHAS.md` **no existe en el PDF** para esta ficha (sí existe
  para Varicela y Parotiditis) — se quitó.
- **Observación abierta**: la BD tiene `usa_contactos=1`, pero el PDF no
  trae ningún censo de contactos nombrados para esta ficha (solo el
  conteo agregado por lugar). Queda anotado en `tablas_hijas`, sin decidir
  si el flag debe bajarse a 0.

### Varicela (`B01`) — PDF pág. 3
- **Matriz corregida**: "Contactos por lugar" — v1.0 copió por error las
  columnas de Tos ferina (esquema de vacunación completo/incompleto,
  recibieron vacunación/antibióticos). El PDF de Varicela solo pide
  nombre del lugar, dirección, contactos sanos y contactos enfermos.

### Parotiditis (`B26`) — PDF pág. 4
- **Mismo error de columnas** que Varicela, corregido igual.
- **Tipo**: "Trimestre de gestación (contacto)" era `NUMERO` en v1.0; el
  PDF pide trimestre I/II/III (checkbox), no un número → `SELECT`.
- **Observación abierta**: la BD tiene `usa_muestras=1`, pero el PDF de
  Parotiditis no trae ninguna sección de laboratorio (a diferencia de
  Varicela, que sí la trae).

### Difteria (`A36`) — PDF pág. 13-14
- Sin cambios de contenido: ya estaba reconstruida correctamente en
  `AUDITORIA_FICHA_DIFTERIA.md`. Solo se agregaron las opciones de catálogo.

### Fiebre amarilla (`A95`) — PDF pág. 26-27
- **Campo retirado del manifiesto**: "Clasificación final" (SELECT
  Confirmado/Descartado). `enfermedad.opciones_clasificacion` ya está fijado
  a `CONFIRMADO,DESCARTADO` para esta ficha (mismo mecanismo que Difteria):
  ese valor vive en `caso.clasificacion` (núcleo), no en `campo_def`. Dejarlo
  en el manifiesto habría sido pedir un campo redundante. Se mantienen
  "Criterio de clasificación" y "Dx de descarte", que sí son propios.

### Leishmaniasis (`B55`) — PDF pág. 45-48
- **Campos agregados**: "Lugar de contagio: localidad/distrito/
  provincia/departamento" (4 campos TEXTO). v1.0 los había excluido
  asumiendo que vivían en una tabla hija, pero esta ficha tiene
  `usa_viajes=0` — no hay `caso_viaje` habilitado, así que si el PDF los
  pide, tienen que estar en `campo_def`.
- **Matriz "Lesiones"**: se documentaron las columnas reales (fecha de
  inicio, tipo, localización, ganglios, infección, diámetros, superficie).
  Nota abierta: es una tabla de filas dinámicas (una por lesión), no de
  filas fijas — encaja mejor como `MATRIZ` de tamaño variable que como
  catálogo cerrado; se deja así por ahora.

### Enfermedad de Chagas (`B57`) — PDF pág. 40-41
- Sin cambios de fondo (ya excluía correctamente la sección "Migración",
  que corresponde a `caso_viaje` — ver `INFORME_CARGADOR.md` C.1). Se
  agregaron las opciones de catálogo y se documentó "Clasificación final"
  como 6 combinaciones (forma × confirmado/descartado), simplificando la
  tabla del PDF (forma × fecha de confirmación/descarte).

### Enfermedad de Carrión (`A44`) — PDF pág. 42-44
- Sin cambios de fondo; ya estaba completo en v1.0. Se agregaron opciones.

### Viruela del mono / Mpox (`B04X`) — PDF pág. 5-6
- Se agregó el ítem "Exantema/lesión" a la lista de signos y síntomas (el
  PDF lo trae como ítem propio, separado de "Otros").

### EDA grave / cólera (`A00`) — PDF pág. 50
- Sin cambios de fondo; ya estaba completo. Se agregaron opciones.

### Tétanos (`A35`) y Tétanos neonatal (`A33`) — PDF pág. 21-24
- Sin cambios de fondo; ya estaban completos. Se agregaron opciones (el
  catálogo compartido de 7 signos/síntomas es igual en ambas fichas).

### Parálisis flácida aguda (`A80`) — PDF pág. 34-36
- **Confirmado el hallazgo ya anotado en v1.0**: el PDF trae 5 valores de
  clasificación final (Polio salvaje / derivado de la vacuna / asociado a
  la vacuna / compatible / descartado); el catálogo actual en BD solo tiene
  3 (confirmado/compatible/descartado). Corregir esto implica decidir si se
  amplía el catálogo o se documenta la reducción a 3 como decisión de
  producto — se deja para revisión del usuario, no se resuelve aquí.

### Sarampión / rubéola (`B05`) — PDF pág. 37-39
- **Tipo**: "N.° de dosis" (antecedentes vacunales) era `NUMERO` en v1.0; el
  PDF codifica esto como opciones (0=dosis cero, 1=primera, 2=segunda,
  88=adicional, 99=desconocido) → `SELECT`.
- Se confirma el hallazgo de `INFORME_CARGADOR.md` A.1: falta la sección
  "Cadena de transmisión" → `caso_contacto`, que el PDF (pág. 39) sí trae.

### Gestante con VIH y niño expuesto (`Z21`) — PDF pág. 16
- **Campo agregado**: "Institución del EE.SS. del parto" (SELECT
  MINSA/EsSalud/FFAA-FFPP/Privado/Otro) — v1.0 solo tenía el nombre del
  EE.SS. como texto libre, sin la institución.

### VIH/SIDA — notificación individual (`B24`) — PDF pág. 17
- Sin cambios de fondo; ya estaba completo. Se agregaron opciones (la vía
  de transmisión se aplanó a una sola lista de 11 valores en vez del
  agrupamiento sexual/parenteral/vertical del PDF, por simplicidad).

### Sífilis materna y congénita (`A50`) — PDF pág. 18-19
- Sin cambios de fondo; ya estaba completo. Se agregaron opciones.

### Síndrome de rubéola congénita (`P35.0`) — PDF pág. 20
- Sin cambios de fondo; ya estaba completo. Se agregaron opciones.

### ESAVI severo (`Y59.0`) — PDF pág. 9-11 (ficha), 12 (Anexo 1), 7-8 (Anexo 6.2)
- Sin cambios de fondo en la ficha principal; se agregaron opciones,
  incluyendo la matriz completa de 17 signos/síntomas.
- **Actualización relevante**: el Anexo 6.2 (Lista de chequeo del
  vacunatorio), antes bloqueado por falta de contenido fuente, **ya tiene
  su contenido real disponible** en las páginas 7-8 del PDF. Se deja
  igual fuera del manifiesto porque requiere una capacidad de sección
  condicional que el motor no tiene todavía (activarse solo si la
  clasificación final es 2 o 3) — ver memoria `pendiente_anexo_62_esavi`,
  ya actualizada.

### Violencia familiar (`Y07`) — PDF pág. 25
- Sin cambios de fondo; ya estaba completo. Se agregaron opciones.

### Lesiones por accidentes de tránsito (`V99`) — PDF pág. 29
- Sin cambios de fondo; ya estaba completo. Se agregaron opciones.

### Muerte materna (`O95`) — PDF pág. 30-33
- Sin cambios de fondo; ya estaba completo. Se agregaron opciones (ficha
  más grande del manifiesto: ~80 campos entre Anexo 1 y Anexo 2).

### Muerte fetal y neonatal (`P96`) — PDF pág. 28
- Sin cambios de fondo; ya estaba completo. Se agregaron opciones.

---

## Regla de frontera (Parte C) aplicada

Se revisó, ficha por ficha, si algo del manifiesto debía moverse a tabla
hija o viceversa. Resultado:

- **Chagas**: ya estaba correcto (sin sección "Migración" en el
  manifiesto) — es la BD la que tiene el defecto (ver
  `INFORME_CARGADOR.md`, hallazgo C.1), no el manifiesto.
- **Leishmaniasis**: al revés — el manifiesto tenía el defecto (excluía
  "Lugar de contagio" asumiendo tabla hija que no existe para esta ficha,
  `usa_viajes=0`). Corregido, ver arriba.
- **Sarampión**: falta la sección completa "Cadena de transmisión" en la
  BD; el manifiesto la mantiene como nota informativa (no como campo_def)
  porque su contenido es de `caso_contacto`.
- **Fiebre amarilla**: se detectó un caso nuevo de frontera — no tabla
  hija esta vez, sino núcleo (`caso.clasificacion` restringido por
  `enfermedad.opciones_clasificacion`). Un campo de `campo_def` puede ser
  redundante también con el núcleo, no solo con una tabla hija. Corregido.

Ningún otro cruce ficha/tabla-hija mostró indicios de duplicación nueva —
las demás fichas con tablas hija habilitadas (`caso_contacto`,
`caso_muestra`, `caso_viaje`, `caso_vacuna`) ya excluían correctamente ese
contenido del manifiesto desde v1.0.

---

## Lo que quedaba para revisión del usuario antes de la Fase 2

**Las 6 quedaron resueltas en `CIERRE_RECARGA_Y_FASE5.md` (2026-07-23)** —
detalle completo en `HALLAZGOS_RECARGA_FICHAS.md`. Se deja el enunciado
original de cada una, más la resolución:

1. **PFA**: los 5 valores del PDF ya vivían en el campo propio de la ficha
   (v2.0); faltaba restringir el núcleo. Resuelto:
   `enfermedad.opciones_clasificacion = 'CONFIRMADO,PROBABLE,DESCARTADO'`
   (`sql/34_cierre_flags_nucleo.sql`).
2. **Tos ferina**: confirmado contra el PDF (pág. 1-2) que sí trae una
   tabla nominal de contactos familiares (pregunta 61) — `usa_contactos=1`
   es correcto, sin cambios.
3. **Parotiditis**: confirmado que no hay sección de laboratorio (pág. 4).
   `usa_muestras` bajado a 0 (`sql/34_cierre_flags_nucleo.sql`).
4. **Sarampión**: implementada. `caso_contacto` ampliada con
   `fecha_contacto`/`lugar_contacto`/`fecha_inicio_erupcion`/`vacunado_72h`
   (`sql/35_fase5_ampliar_tablas_hija.sql`); widget, controlador y modelo
   actualizados.
5. **ESAVI Anexo 6.2**: se construyó la capacidad de sección condicional
   (`seccion_def.depende_de`/`valor_activador`, `sql/36_seccion_condicional.sql`,
   más soporte en `cargar_fichas.php` y en la vista). El contenido del Anexo
   6.2 en sí sigue diferido a una sesión aparte.
6. **Chagas**: se agregó "Criterio de descarte" como campo propio (siempre
   visible, igual que "Dx de descarte" en Fiebre amarilla). **VIH/SIDA**: se
   revirtió el aplanamiento a "Vía de transmisión" (4 valores) + dos campos
   "Subtipo" encadenados (uno por vía sexual, otro por vía parenteral, cada
   uno con su propio `depende_de`/`valor_activador` sobre la vía).

Con esas seis decisiones, `manifiesto_fichas.json` queda listo para ser la
entrada del cargador único de la Fase 2. **No se avanzó a Fase 2 ni se tocó
la base de datos.**
