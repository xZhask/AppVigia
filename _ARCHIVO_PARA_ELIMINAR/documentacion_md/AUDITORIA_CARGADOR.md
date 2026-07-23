# VIGÍA · Auditoría del cargador de fichas y verificación de integridad

El objetivo de esta tarea **no** es corregir fichas una por una, sino encontrar y
arreglar la causa por la que quedaron mal cargadas, y dejar una herramienta que
detecte el problema de forma automática en adelante.

Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`.

> Trabajo de diagnóstico y verificación. **No modificar todavía** las
> definiciones de las fichas: primero entender qué falló y medir el daño. La
> corrección ficha por ficha se hará después, manualmente.

---

## Contexto

Al comparar la ficha de **difteria** contra el PDF MINSA aparecieron tres tipos
de defecto:

1. **Campos inventados** — la sección "Signos de alarma" (edema cervical,
   dificultad respiratoria, sospecha de miocarditis) no existe en la ficha
   oficial.
2. **Secciones faltantes** — la sección de vacunación de la ficha no se cargó.
3. **Tipos degradados** — campos de opciones cerradas quedaron como `TEXTO`, y
   estructuras tipo `MATRIZ` quedaron aplanadas a `TEXTAREA`.

El patrón sugiere una **falla sistemática del cargador de definiciones**, no
errores aislados. Si ocurrió en difteria, probablemente ocurrió en las otras 14
fichas cargadas (lotes 1 a 4).

---

## Parte A — Auditar el cargador

Localizar el script o proceso que cargó las definiciones (`seccion_def`,
`campo_def`, `catalogo`, `catalogo_item`) a partir de los documentos
`DEFINICION_FICHAS.md` y `DEFINICION_FICHAS_B_C_D.md`.

Revisar y reportar, **sin corregir aún**:

1. **¿Por qué se truncan secciones?** Verificar si hay un límite de longitud, un
   corte por salto de línea, un `break` prematuro, o un parseo que se detiene en
   cierto patrón (encabezados de sección, tablas markdown, etc.).
2. **¿Por qué se degradan los tipos?** Verificar si el cargador asigna `TEXTO`
   por defecto cuando no reconoce un tipo (`GRUPO_SI_NO`, `SI_NO_FECHA`,
   `MATRIZ`, `CRONOLOGIA`), en lugar de fallar y avisar.
3. **¿De dónde salen los campos inventados?** Verificar si el cargador
   "completa" campos por inferencia, o si esos campos vinieron de una versión
   anterior de las definiciones que no se limpió.
4. **¿Es idempotente?** Si se corre dos veces, ¿duplica secciones y campos, o
   actualiza? Esto explica posibles duplicados.

Entregar un informe breve (en un archivo `INFORME_CARGADOR.md`) con la causa de
cada defecto y la corrección propuesta para el cargador. **No aplicar la
corrección todavía** — se revisa primero.

---

## Parte B — Herramienta de verificación por ficha

Construir un script de verificación (`verificar_fichas.php` o equivalente) que
compare lo que hay en la base de datos contra un **manifiesto esperado** por
ficha, y produzca un reporte de diferencias.

### B.1 Manifiesto esperado

Crear un archivo de manifiesto (`manifiesto_fichas.json` o una tabla) con, por
cada enfermedad cargada, el conteo y los nombres esperados de:
- secciones
- campos por sección
- tipo de cada campo

La fuente de verdad son `DEFINICION_FICHAS.md` y `DEFINICION_FICHAS_B_C_D.md`.
El manifiesto se construye una vez y queda como referencia versionada.

### B.2 El verificador reporta, por ficha:

- **Secciones faltantes** — están en el manifiesto pero no en la BD
- **Secciones sobrantes** — están en la BD pero no en el manifiesto
  *(aquí caen los campos inventados)*
- **Campos faltantes** y **campos sobrantes**, por sección
- **Tipos incorrectos** — el campo existe pero con un tipo distinto al esperado
  *(aquí caen los `TEXTO` que debían ser `GRUPO_SI_NO` o `MATRIZ`)*
- **Resumen**: por ficha, cuántas secciones y campos esperados vs. encontrados,
  y un estado (OK / con diferencias)

> **Auditar en las dos direcciones.** No basta con verificar que esté todo lo del
> PDF (faltantes); hay que detectar también lo que el PDF no pide (sobrantes).
> Los campos inventados son más peligrosos que los faltantes: un faltante se nota
> al llenar la ficha, un inventado plausible se llena sin que nadie lo cuestione
> y contamina los datos en silencio.

### B.3 Salida

Un reporte legible (`REPORTE_VERIFICACION.md`) con una tabla por ficha y el
detalle de cada diferencia. Este reporte es lo que se usará después para corregir
ficha por ficha, así que debe ser preciso y accionable: cada línea debe decir qué
sección/campo, qué se esperaba y qué se encontró.

---

## Parte C — Revisión de la frontera tabla hija / `campo_def`

Hay una ambigüedad estructural que probablemente contribuyó al problema: no está
claro qué datos viven en las tablas hijas (`caso_contacto`, `caso_muestra`,
`caso_vacuna`, `caso_viaje`) y cuáles en `campo_def` / `caso_valor`.

**Regla a aplicar y verificar:**
- **Pregunta contextual única → `campo_def`.** Ejemplo: "¿La madre recibió Tdap
  durante la gestación?" es una sola respuesta.
- **Lista repetible de N filas → tabla hija.** Ejemplo: "dosis recibidas, con
  fecha y establecimiento" es una lista.

Reportar (en `INFORME_CARGADOR.md`) los casos donde un mismo dato podría estar
capturándose **por duplicado** — una vez como campo en `campo_def` y otra como
columna de una tabla hija. Ese solapamiento permite registrar el mismo dato dos
veces con valores distintos y hay que eliminarlo. **Reportar, no corregir aún.**

---

## Lo que esta tarea NO debe hacer

- No corregir las definiciones de las fichas todavía.
- No recargar las fichas todavía.
- No inventar campos para "completar" una ficha que se vea incompleta.
- No modificar `theme.css` ni la interfaz.

El resultado esperado son **tres archivos**: `INFORME_CARGADOR.md`,
`manifiesto_fichas.json` y `REPORTE_VERIFICACION.md`, más el script
`verificar_fichas.php`. Con eso, la corrección posterior será rápida y medible.

---

## Verificación de la tarea

- [ ] Está identificada la causa de las secciones truncadas
- [ ] Está identificada la causa de los tipos degradados
- [ ] Está identificado el origen de los campos inventados
- [ ] El verificador detecta faltantes Y sobrantes
- [ ] El verificador detecta tipos incorrectos
- [ ] El reporte lista, por ficha, las diferencias de forma accionable
- [ ] Están reportados los posibles datos duplicados entre tabla hija y campo_def
- [ ] No se modificó ninguna definición de ficha ni la interfaz
