# VIGÍA · Bugs sistémicos + comparación difteria y PFA

Resultado de la comparación ficha por ficha contra el PDF MINSA (difteria y PFA).
La Parte A son defectos que afectan a **las 24 fichas** y deben corregirse
primero: explican buena parte de lo anotado en las partes B y C, y evitan que
los mismos hallazgos se repitan en cada ficha que se compare después.

Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`.

> Ejecutar la Parte A completa y **volver a revisar difteria y PFA** antes de
> tocar las partes B y C. Varios hallazgos van a desaparecer solos.

---

# PARTE A — Defectos sistémicos (todas las fichas)

## A.1 Los campos condicionales no se ocultan en pantalla

**Síntoma:** los campos con `depende_de` se ven y se editan desde que carga el
formulario, sin importar el valor del campo disparador. Confirmado en difteria
(hospitalización, fecha de aislamiento, dosis de vacunación) y en PFA
(hospitalizado, fallecido).

Es el mismo problema que se corrigió para "Gestante" con `.field[hidden]`, pero
los campos de `depende_de` siguen sin ocultarse. Revisar si usan otro mecanismo,
otra clase contenedora o si el JS no se ejecuta para ellos.

**Debe cumplirse:**
- El campo dependiente **no se renderiza** mientras el disparador no tenga el
  valor activador (ocultar, no deshabilitar).
- Al ocultarse, su valor se limpia antes de guardar.
- La obligatoriedad solo aplica si el campo está visible.
- Funciona en cascada (un campo condicional puede tener hijos).
- Verificar también las **secciones** condicionales (`seccion_def.depende_de`):
  hoy la única es el Anexo 6.2 de ESAVI, que está vacío y por eso no puede
  revelar el defecto. Probar con una sección temporal de dos campos.

Al terminar, verificar en navegador en al menos difteria, PFA y ESAVI.

## A.2 Las matrices se renderizan como campos de texto

**Síntoma:** en PFA, "Fuerza muscular", "Tono muscular", "Reflejos" y "Signos de
irritación meníngea" aparecen como inputs de texto libre. Deberían ser celdas
con opciones cerradas.

- Fuerza / tono / reflejos → DIM · AUS · NORM · IGN
- Signos meníngeos → AUS · PRES · IGN

Cada celda es una selección entre las columnas de la matriz, no texto. Aplicar
el mismo patrón visual del control segmentado que ya se usa en `GRUPO_SI_NO`.

Afecta también a **Carrión** (ganglios linfáticos, lesiones eruptivas) y
**leishmaniasis** (compromiso de estructuras). Revisar todas las fichas con
`MATRIZ`.

## A.3 La configuración de tablas hijas se filtra entre fichas

**Síntoma:** en PFA aparece la columna "¿Recibió antibiótico?" (propia de
difteria) y el catálogo de tipo de muestra ofrece "Hisopado nasofaríngeo" en vez
de Heces 1 / Heces 2. Lo mismo con "Lugar probable de infección", que muestra la
estructura de difteria.

**Causa:** la configuración por ficha (`enfermedad.columnas_*`) solo se aplicó a
4 fichas; las otras 20 caen a un valor por defecto que arrastra las columnas que
se fueron agregando a las tablas compartidas.

**Corregir en dos niveles:**

1. **Columnas** — configurar `columnas_contacto`, `columnas_muestra`,
   `columnas_viaje` y `columnas_vacuna` para **las 24 fichas**, no solo cuatro.
   Si una ficha no usa una tabla hija, su flag `usa_*` debe estar en 0 y el
   widget no debe aparecer.
2. **Opciones de catálogo** — las listas de las tablas hijas también deben ser
   por ficha. Hoy son globales. Ejemplos:
   - Difteria · tipo de muestra: Hisopado · Membrana
   - PFA · tipo de muestra: Heces 1 · Heces 2
   - Tos ferina · tipo de muestra: Hisopado nasofaríngeo · Aspirado nasofaríngeo

   Agregar la configuración de opciones por ficha, en el manifiesto, y
   verificarla como el resto.

**Además:** revisar por qué el widget no se actualiza al cambiar de enfermedad
en el formulario (posible caché o estado que no se recarga).

## A.4 La regla de frontera se aplicó de más

La regla vigente dice "lista repetible → tabla hija". Está incompleta. La
correcta es:

> **Lista repetible de N filas → tabla hija.**
> **Conjunto fijo de preguntas, aunque sean varias → `campo_def` o `MATRIZ`.**

Casos concretos detectados:

| Ficha | Qué se hizo | Qué pide el PDF | Corrección |
|---|---|---|---|
| Difteria · vacunación | `caso_vacuna` (lista libre) | 51. Vacunación contra difteria Sí/No → 52. N.° de dosis (1°/2°/3°) · 53. Refuerzos (1°/2°) · 54. Fecha de última dosis | `usa_vacunas = 0`; campos en `campo_def` con condicional sobre el 51 |
| PFA · vacunación | `caso_vacuna` | 7. Vacunado Sí/No/Ign → N.° de dosis · Verificada con carné · Fecha de última dosis · Establecimiento · Dirección · Ciudad | `usa_vacunas = 0`; campos en `campo_def` con condicional |
| Tos ferina · vacunación | `caso_vacuna` | 51. Dosis recibidas: Pentavalente 1°/2°/3°, DPT 1er/2do refuerzo · 52. Fecha última dosis · 53. EE.SS. | Verificar contra el PDF; probablemente también conjunto fijo |
| Difteria · laboratorio | `caso_muestra` (lista libre) | Una muestra (Hisopado/Membrana) + fecha de toma; Cultivo Sí/No y PCR Sí/No, cada uno con fecha de resultado; Recibió antibiótico | Revisar si conviene `campo_def` en vez de lista repetible |
| PFA · laboratorio | `caso_muestra` (lista libre) | Exactamente dos: Heces 1 y Heces 2, con fecha de obtención · fecha de envío al INS · fecha de resultado · agente aislado · observaciones | Conjunto fijo de 2 filas → `MATRIZ` o dos bloques de campos |
| Difteria · viajes | `usa_viajes = 1` | El PDF no pide viajes; pide "46. Lugar probable de infección (10 días previos, incluye viajes)" | `usa_viajes = 0`; usar solo lugar probable de infección |

**Revisar esta frontera en las 24 fichas**, no solo en estas. `caso_vacuna` con
su forma actual (fabricante, lote, vía, sitio) probablemente solo aplica a ESAVI,
que sí tiene una matriz de hasta 4 vacunas.

## A.5 Campos núcleo faltantes: cadena de notificación y código de registro

La verificación de núcleo revisó tipo de captación, lugar de captación y
clasificación en la captación, pero **no la cadena de fechas de notificación**,
que aparece en casi todas las fichas MINSA.

Agregar a la sección "Notificación":

| Campo | Tipo |
|---|---|
| Código / N.° de registro de la ficha | TEXTO (o autogenerado) |
| Fecha de conocimiento local del caso | FECHA |
| Fecha de notificación EE.SS. a Red/Microred | FECHA |
| Fecha de notificación Red/Microred a DISA/DIRESA | FECHA |
| Fecha de notificación de DISA/DIRESA a CDC/DGE | FECHA |
| Fecha de investigación (visita domiciliaria) | FECHA |

Los nombres exactos varían por ficha; usar estos como núcleo y permitir que cada
ficha muestre solo las que le corresponden.

## A.6 "Fecha de inicio de síntomas" duplicada

Aparece dos veces en difteria y en PFA: una en el núcleo
(`caso.fecha_inicio_sintomas`) y otra como campo de la ficha.

Dejar **solo la del núcleo** y retirar la duplicada del manifiesto en todas las
fichas donde ocurra. En PFA verificar el matiz: el PDF distingue "fecha de inicio
de síntomas generales (pródromos)" de "fecha de la deficiencia motora" — son dos
fechas distintas y ambas deben existir, pero ninguna duplica a la otra.

## A.7 Selectores de UBIGEO en las tablas hijas

En "Lugar probable de infección" (difteria) los campos de departamento,
provincia y distrito deben ser los mismos selectores encadenados desde la base
que usa la sección de datos del paciente, no campos libres.

Aplicar a todas las tablas hijas con ubicación: `caso_lugar_infeccion`,
`caso_viaje`, `caso_contacto` y `caso_sujeto`.

## A.8 Verificar "Gestante" en difteria

**[VERIFICADO — OK]** Cubierto y validado correctamente. No requiere cambios en la condición de gestante.

---

# PARTE B — Difteria (hallazgos propios de la ficha)

Además de lo que se resuelve con la Parte A:

1. **Condicionalidad de hospitalización.** Fuera del bloque condicional quedan
   solo: *37. Hospitalizado (Sí/No/Ignorado)* y *38. Antibiótico antes del
   ingreso (Sí/No)* con su "especificar". Todo lo demás —hospital, fecha de
   hospitalización, tratamiento recibido, egreso, fecha de alta, fecha de
   defunción, complicaciones— depende de que 37 sea "Sí".
2. **"Especificar antibiótico"** (en tratamiento recibido) solo visible si se
   marca la opción "Antibiótico".
3. **Fecha de aislamiento** solo visible si "Aislamiento domiciliario" es Sí.
4. **Vacunación (51-54)** como conjunto condicional, según A.4.
5. **Censo de contactos:** la fecha de vacunación de cada contacto solo se
   habilita si "Vacunado" es Sí.
6. **Laboratorio** — estructura real del PDF:
   - 57. Tipo de muestra: Hisopado · Membrana
   - 56. Fecha de toma de muestra (al seleccionar muestra)
   - 58. Cultivo Sí/No · 59. PCR Sí/No → 60. Fecha de resultado
   - 61. Recibió antibiótico Sí/No
   - 62. Clasificación final: Confirmado · Descartado

---

# PARTE C — PFA (hallazgos propios de la ficha)

1. **Sección 1 · Registro:** falta N.° de orden nacional, y departamento /
   provincia / distrito / localidad del caso.
2. **Sección 2 · Datos personales:** faltan Padre y Madre (tutores), y la
   distinción entre residencia permanente y residencia provisional.
3. **Sección 3 · Conocimiento del caso:** Notificación · Búsqueda activa.
   Verificar si el campo núcleo "tipo de captación" ya lo cubre; si sí, no
   duplicar.
4. **Primer notificante** (subsección de la 3): nombre, institución /
   establecimiento, dirección, teléfono.
5. **Sección 4 · Localización de la parálisis** — falta. Matriz con columnas
   SI · NO · IGN · Prox. · Dist. y filas MSI · MSD · MII · MID, más las
   subsecciones de signos y síntomas y cara (lado afectado).
6. **Semana epidemiológica:** la calcula el sistema; quitar el input.
7. **Secciones 5 y 6 · Hospitalización y fallecido:** condicionales (Parte A.1).
   Además hoy quedan al final bajo un encabezado "Signos y síntomas" que no
   corresponde — ubicarlas en su sección propia.
8. **Sección 7 · Antecedentes de vacuna antipolio:** ver A.4.
9. **Sección 8 · Laboratorio:** Heces 1 y Heces 2 (ver A.4). Fecha de envío al
   INS, fecha de resultado y agente aislado dependen de que haya fecha de
   obtención.
10. **Sección 9 · Fuente probable de infección** — tres preguntas, dos de ellas
    condicionales:
    - (a) ¿Viajes en los 30 días antes del inicio de la deficiencia motora?
      Sí/No → si Sí: "A dónde"
    - (b) ¿Visitas recibidas en los 30 días antes? Sí/No → si Sí: "De dónde"
    - (c) ¿Existen otros casos semejantes en el área? Sí/No/No sabe *(ya existe)*
11. **Sección 10 · Cadena de transmisión** — falta por completo. Es
    `caso_contacto`, una fila por persona: nombre · edad · N.° de dosis
    recibidas · fecha de última dosis · fecha de colecta de heces · fecha de
    envío · fecha de resultado · resultado del aislamiento.
    Incluir el texto instructivo de la ficha (contactos 45 días antes y 45 días
    después del inicio de la parálisis; seguimiento de asintomáticos hasta 60
    días).
12. **Sección 11 · Acciones de control** — falta:
    - Bloqueo: localidad(es) · fecha de inicio
    - Búsqueda activa: N.° de casos hallados · ingresan al sistema · se descartan
    - Cobertura de vacunación por edad (<1 año · 1-4 · 5-14 · >15) con total
      calculado
    - Censo de casas (abiertas · cerradas · abandonadas) con total calculado
13. **Sección 12 · Seguimiento de secuelas** — falta. Matriz longitudinal a 30,
    60, 90 y 180 días, con fecha programada y fecha realizada, más evaluación de
    trofismo (fuerza, tono, atrofia, sensibilidad por segmento) y de reflejos
    (extremidades, Babinski, músculos respiratorios) y comentarios evolutivos.
14. **Secciones 15-17 · Clasificación final:**
    - 15. Los 5 valores oficiales (polio salvaje · derivado de la vacuna ·
      asociado a la vacuna · compatible · descartado) + "especificar" +
      fecha de clasificación
    - 16. Criterios: laboratorio · defunción · con parálisis residual · sin
      parálisis residual · descartado
    - 17. **Condicional a que 15 sea "Descartado":** SGB · neuritis traumática ·
      mielitis transversa · tumor · desconocido · otro (especificar)
15. **Sección 19 · Observaciones generales** — falta, antes de los datos del
    investigador.

---

## Orden sugerido

1. Parte A completa
2. Volver a revisar difteria y PFA en navegador — varios hallazgos de B y C se
   resuelven solos
3. Lo que quede de B y C
4. Regenerar `REPORTE_VERIFICACION.md`

## Verificación

- [ ] Los campos condicionales se ocultan y muestran correctamente, en cascada
- [ ] Las secciones condicionales funcionan (probadas con contenido real)
- [ ] Las matrices se renderizan con opciones, no con inputs de texto
- [ ] Las 24 fichas tienen su configuración de columnas y de opciones de catálogo
- [ ] Ninguna ficha muestra columnas ni opciones de otra
- [ ] El widget se actualiza al cambiar de enfermedad
- [ ] La frontera tabla hija / `campo_def` revisada en las 24 fichas
- [ ] Cadena de notificación y código de registro en el núcleo
- [ ] Sin fechas de inicio de síntomas duplicadas
- [ ] UBIGEO encadenado en todas las tablas hijas con ubicación
- [ ] `theme.css` sin cambios más allá de correcciones puntuales anunciadas
- [ ] Sin emojis ni librerías externas
