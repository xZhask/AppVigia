# Petición — Cotejo completo de P35.0 (Síndrome de rubéola congénita)

**Fuente de verdad:** PDF MINSA, ficha de investigación clínico epidemiológica de SRC, código CIE-10 P35.0 (pág. 20 del compendio).

**Alcance:** cerrar la ficha P35.0 completa contra el PDF, no solo la sección de datos del paciente. Incluye núcleo del paciente, identidad de la madre, cuadro clínico, laboratorio y condicionales.

**Regla que gobierna toda la petición:** las variaciones por ficha se declaran como **dato en `manifiesto_fichas.json`**, nunca como condiciones por CIE-10 dentro de código compartido. Si una fase te obliga a escribir un `if ($cie10 === 'P35.0')` en un archivo compartido, **para y explícame por qué antes de hacerlo**.

---

## Fase 0 — Congelar las claves de P35.0 (BLOQUEANTE, va primero)

Hoy P35.0 tiene **7 de 34 campos con `clave` explícita**. Los otros 27 derivan su clave desde la etiqueta en cada recarga. Esta petición renombra etiquetas en varias fases; si se hace sin blindar antes, cada renombre cambia la clave en silencio.

Agravante: los `depende_de` del manifiesto apuntan a la **etiqueta** del campo padre, no a su clave. Un renombre rompe dos cosas a la vez.

1. Lee las claves reales que hoy tiene `campo_def` para P35.0 (las que produjo la última carga).
2. Escríbelas como `clave` explícita en los 27 campos de P35.0 que no la tienen, **sin cambiar ninguna etiqueta todavía**. Las 34 claves quedan idénticas a las actuales.
3. Corre `verificar_claves.php` y `verificar_fichas.php`. Ambos deben pasar antes de continuar.
4. Commit con mensaje propio: `P35.0 fase 0: congelar claves antes del cotejo`.

**No avances a la Fase 1 si `verificar_fichas.php` no da 24/24.**

---

## Fase 1 — `nucleo_omitidos` por rol de sujeto

### El problema

P35.0 es `multi_sujeto: true` con `roles_sujeto: [CASO_INDICE, MADRE]`. Los dos sujetos necesitan cosas opuestas del mismo núcleo:

| Campo del núcleo | Paciente (bebé, menor de 1 año) | Madre |
|---|---|---|
| Ocupación | no la pide | sí, ítem 29 |
| Gestante | no | no |
| Celular propio | no | no |
| Celular del apoderado | sí, ítem 17 | no |

`nucleo_omitidos` es hoy una **lista plana por ficha**. Declarar `ocupacion` ahí la mataría también para la madre.

### Lo que hay que hacer

1. Localiza quién lee `nucleo_omitidos` hoy (el partial `datos-paciente-nucleo.php` y lo que lo alimente).
2. Extiende la lectura para que acepte **dos formas**:
   - Lista plana → aplica a todos los roles. Retrocompatible con A36, A80 y O95, que **no se tocan**.
   - Objeto por rol → `{"CASO_INDICE": [...], "MADRE": [...]}`.
3. Declara en P35.0:

```json
"nucleo_omitidos": {
  "CASO_INDICE": ["gestante", "celular", "ocupacion", "nombre_tutor"],
  "MADRE": ["gestante", "celular", "celular_tutor", "nombre_tutor"]
}
```

Justificación campo por campo contra el PDF:
- `gestante`: la ficha es de menores de 1 año. Hoy el formulario habilita gestante al elegir sexo femenino. El PDF no lo pide para ninguno de los dos sujetos.
- `celular` (del paciente): el PDF pide el ítem 17 **"N.° celular del apoderado"**, no el del paciente.
- `celular_tutor` se **conserva** para el CASO_INDICE (es el ítem 17) y se omite para la madre.
- `nombre_tutor`: el PDF pide el celular del apoderado pero **no su nombre**.
- `ocupacion`: no aparece en el bloque II del PDF (datos del paciente). Sí aparece en el bloque IV (ítem 29, madre).

4. Verifica que A36, A80 y O95 sigan renderizando exactamente igual que antes del cambio.

**Condición de parada:** si `nucleo_omitidos` no se consume donde asumo, o si el partial del núcleo no sabe con qué rol se está pintando, **para y explícame la estructura real antes de improvisar**.

---

## Fase 2 — Identidad de la madre (ítems 23-29 del PDF)

El bloque IV del PDF abre con la identidad de la madre y hoy no se guarda en ningún lado:

| Ítem | Campo | Estado en `caso_sujeto` |
|---|---|---|
| 23 | Tipo de documento | falta columna |
| 24 | N.° de documento | existe (`doc`) |
| 25 | Apellidos y nombres | existe (`apellidos`, `nombres`) |
| 26 | Edad (años) | existe (`edad`) |
| 27 | Fecha de nacimiento | falta columna |
| 28 | Nacionalidad | falta columna |
| 29 | Ocupación | falta columna |

Además, `datosResidenciaMadre()` en `CasosController.php` está **hardcodeado a `cie10 === 'P96'`**, así que no corre para P35.0 aunque su manifiesto ya declare el rol `MADRE`. Eso es exactamente el antipatrón que esta petición prohíbe.

1. Migración numerada en `sql/migraciones/` que agregue a `caso_sujeto`: `tipo_doc`, `fecha_nacimiento`, `nacionalidad`, `ocupacion`. Nada de ALTER TABLE fuera de ese directorio.
2. Reemplaza el hardcode de `P96` por una condición **declarativa**: el bloque de sujeto se pinta cuando el manifiesto de la ficha declara ese rol en `roles_sujeto`. Debe seguir funcionando idéntico para P96.
3. Declara en P35.0 qué columnas de `caso_sujeto` usa el rol `MADRE`, siguiendo el mismo patrón de `columnas_tablas_hija`:

```json
"columnas_sujeto": {
  "MADRE": ["tipo_doc", "doc", "apellidos", "nombres", "edad", "fecha_nacimiento", "nacionalidad", "ocupacion", "distrito_id", "direccion"]
}
```

Si ya existe una clave con otro nombre para esto, usa la existente y dímelo; no crees una segunda convención.

4. Verifica que P96 no haya cambiado de comportamiento.

**Condición de parada:** si generalizar `datosResidenciaMadre()` obliga a tocar el flujo de guardado de casos más allá de este bloque, **para y explícame el alcance real antes de seguir**.

---

## Fase 3 — Cuadro clínico: de `GRUPO_SI_NO` a `MATRIZ`

El ítem 34 del PDF es una tabla con estas columnas por cada manifestación:

**Manifestación | Sí | No | Desconocido | Fecha de manifestación**

El manifiesto lo tiene hoy como cuatro campos `GRUPO_SI_NO`, que es un patrón fijo Sí/No/Ignorado **sin columnas**. Resultado: las 17 fechas de manifestación que pide el PDF no se pueden capturar.

Convierte los cuatro grupos en cuatro campos `MATRIZ`, conservando el agrupamiento del PDF y con estas `columnas` en los cuatro: `["Sí", "No", "Desconocido", "Fecha de manifestación"]`.

| Campo | `filas` |
|---|---|
| Manifestaciones oftálmicas | Cataratas · Glaucoma congénito · Retinopatía pigmentaria · Microftalmia |
| Manifestación auditiva | Déficit de la audición |
| Cardiopatía congénita | Estenosis periférica de arteria pulmonar · Persistencia del conducto arterioso · Comunicación interauricular · Otra cardiopatía congénita |
| Otras manifestaciones | Púrpura · Trombocitopenia · Hepatomegalia · Esplenomegalia · Microcefalia · Meningoencefalitis · Enfermedad ósea de radiotransparencia · Retraso en el desarrollo psicomotor · Ictericia (dentro de las 24 h del nacimiento) |

El campo TEXTO "Otra cardiopatía congénita (especificar)" se **conserva** como campo aparte: en el PDF es una fila de la tabla más una línea para escribir cuál.

**Condición de parada — importante:** si el renderizador de `MATRIZ` pinta todas las celdas como texto libre y no sabe pintar una celda de tipo fecha ni un trío de radios Sí/No/Desconocido, **para y repórtamelo**. Prefiero dejar el cuadro clínico como está a degradar 17 fechas a texto libre. Los precedentes con columnas de fecha son Y59.0 y Z21: revisa cómo se resuelven ahí antes de decidir.

---

## Fase 4 — Condicionales (`depende_de`)

P35.0 no tiene ni un solo `depende_de` declarado, y el PDF tiene condicionales evidentes. Decláralos **después de la Fase 0**, para que ningún renombre los rompa:

| Campo dependiente | Depende de | Activador |
|---|---|---|
| Fecha de vacunación contra rubéola | ¿Vacunada contra la rubéola? | Sí |
| Semana de gestación (fiebre/exantema) | ¿Presentó fiebre y exantema maculopapular durante el embarazo? | Sí |
| ¿Fue confirmada por laboratorio la rubéola de la madre? | ídem anterior | Sí |
| ¿Con quién? (exposición) | ¿Durante el embarazo se expuso a persona con fiebre y exantema? | Sí |
| Semana de gestación (exposición) | ídem anterior | Sí |
| EE.SS. de hospitalización | Hospitalización | Sí |
| Fecha de hospitalización | Hospitalización | Sí |
| Dx de ingreso | Hospitalización | Sí |
| Fecha de defunción | Defunción | Sí |
| Causa básica de defunción | Defunción | Sí |

---

## Fase 5 — Tablas hija

### 5.1 `caso_viaje` — falta la semana de gestación

La tabla de viajes del ítem 33 es **de la madre** y trae columnas: N.° · País · Localidad/ciudad · Fecha de salida · Fecha de retorno · **Semana de gestación**.

`caso_viaje` no tiene columna para la semana de gestación. Agrégala con migración numerada en `sql/migraciones/` y declara `columnas_tablas_hija.caso_viaje` en P35.0. **No inventes el campo en `campo_def`** — es dato repetible por viaje, igual que el resto de la tabla.

### 5.2 `caso_muestra` — laboratorio (ítems 42 y 43)

`caso_muestra: true` está declarado pero **sin `columnas_tablas_hija`**. El PDF pide, por muestra: fecha de obtención · fecha de resultado · IgM (−/+) · IgG (−/+) · Titulación · Genotipo. Los tipos de muestra son: 1.ª muestra serológica, 2.ª muestra serológica, hisopado nasal y faríngeo.

El ítem 43 es un **segundo bloque** de muestras (seguimiento de excreción viral, solo en casos confirmados de SRC, después de los tres meses de edad hasta dos pruebas negativas con un mes de intervalo).

**Esta subfase es la más incierta de la petición. Antes de escribir nada:** revisa qué columnas tiene realmente `caso_muestra` hoy y cómo las declaran A80 y las demás fichas que la usan. Luego **para y propóneme** una de estas dos rutas, con su costo:

- **(a)** ampliar `caso_muestra` con las columnas serológicas que faltan, vía migración numerada;
- **(b)** dejar el laboratorio de P35.0 como deuda anotada en `PENDIENTES.md` y cerrar la ficha sin él.

No elijas por tu cuenta.

---

## Fase 6 — Verificaciones que necesito que me reportes (sin cambiar nada)

Revisa y dime el estado de cada una. Si falta algo, lo decido yo:

1. **Edad en meses/días.** El ítem 11 del PDF ofrece únicamente **Meses** y **Días**, no años. ¿Cómo captura la edad el núcleo hoy? Esto no es una omisión sino un cambio de unidad, y `nucleo_omitidos` no puede expresarlo. **No lo implementes**: repórtame qué haría falta.
2. **N.° de Historia Clínica** (ítem 9): ¿existe en el núcleo?
3. **Tiempo de residencia** y **Referencia para localizar** (ítem 18): ¿existen en el bloque de domicilio del núcleo?
4. **Pueblo étnico vs. Etnia/raza.** El PDF pide **los dos**: ítem 15 "Pueblo étnico" (texto libre) e ítem 16 "Etnia / raza" con lista cerrada (Mestizo · Andino · Asiático descendiente · Afro descendiente · Indígena amazónico · Otro). Dime a qué control del núcleo corresponde cada uno. **No elimines ninguno de los dos** — si algo está mal, es el mapeo, no su existencia.
5. **`o95_establecimiento_sanidad_pnp`**: confirma que su comportamiento condicional sobrevivió intacto a las sesiones de migración. Quedó abierto de la sesión anterior.

---

## Divergencias deliberadas (no las "corrijas")

- El PDF mete hospitalización y defunción (ítems 35-41) **dentro** de "V. CUADRO CLÍNICO". El manifiesto las tiene como sección 5 aparte. Se queda así: buscamos que coincidan los **datos** y el **orden**, no el agrupamiento visual de un formulario de papel a dos columnas.
- Los bloques I (establecimiento notificante) y VIII (personal que llena la ficha) se resuelven por núcleo, no por `campo_def`.

---

## Cierre

Al terminar cada fase: `verificar_claves.php` + `verificar_fichas.php` (debe dar 24/24) y commit independiente por fase. No hagas push.

Al final, resúmeme en una tabla: qué fases quedaron cerradas, qué quedó anotado en `PENDIENTES.md` y qué preguntas de la Fase 6 quedaron sin respuesta.
