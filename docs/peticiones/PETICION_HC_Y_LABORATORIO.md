# Petición — N.° de historia clínica al núcleo + diseño del laboratorio (P35.0)

Dos partes. La primera se implementa; la segunda es diseño y termina en parada.

## Instrucciones permanentes

1. Nada de condiciones por ficha en código compartido. Si algo obliga a escribir `if ($cie10 === '...')`, **para y explícame**.
2. Si un cambio mueve, oculta o altera algo en una ficha ya cotejada (A36, B26, A80, O95, B05, P35.0), avísame antes de commitear.
3. Un arreglo de partial compartido por sesión.
4. La fuente de verdad es la página del PDF, no los documentos generados por otra herramienta.
5. Clave explícita obligatoria en todo campo nuevo.

---

## PARTE 1 — N.° de historia clínica al núcleo

### El problema

Hoy el mismo dato se pinta de dos maneras distintas según la ficha:

- **O95:** dentro de la tarjeta "Datos del persona", junto al documento de identidad. Lo emite `nueva/index.php` directamente — el cuarto mecanismo de render, hardcodeado por ficha.
- **P35.0:** como `campo_def` (id 50449), así que el motor lo coloca en su propia tarjeta, separado del resto de los datos del paciente.

Es un campo del núcleo disfrazado de campo de ficha. `f4a0a17` lo hizo alcanzable, pero no lo puso donde corresponde.

Y el pintado directo desde `nueva/index.php` es otra instancia del patrón de condiciones por ficha en código compartido — la misma familia de las entradas A, C, E, I y N.

### Lo que hay que hacer

Promoverlo al núcleo con el mismo patrón que `pueblo_etnico` y que el bloque `detalle_domicilio` de `baec9cf`.

1. **Antes de tocar nada, repórtame** en qué fichas aparece hoy el N.° de historia clínica y por cuál de los cuatro mecanismos de render se pinta cada una. Sé que están al menos B05, A37.0, Y59.0, O95 y P35.0, y que la página 51 de A00 también lo pide (ítem M de `PENDIENTES.md`). Verifica contra las páginas reales del PDF, no contra el documento generado. **Con esa cuenta decidimos opt-in u opt-out; no lo asumas.**

   Mi hipótesis es opt-in, porque parecen ~6 de 24. Si tu conteo dice otra cosa, dímelo y paramos.

2. Columna `persona.n_historia_clinica` (texto). Migración numerada + recongelado de `sql/01_esquema_actual.sql`.

3. Ubicación en el partial: **junto al documento de identidad**, exactamente donde O95 lo muestra hoy. Esa disposición ya está validada visualmente y es la que quiero para todas.

4. Elimina el pintado directo desde `nueva/index.php` para O95. Al terminar, O95 debe verse igual que hoy pero por el mecanismo genérico. Anota en `PENDIENTES.md` que era otra instancia del patrón, con la familia a la que pertenece.

5. Retira los `campo_def` de N.° de historia clínica de las fichas que lo tengan (P35.0 id 50449 y las demás que encuentres). **Antes de retirar cada uno, confirma que no tenga filas en `caso_valor`.** Si alguno tiene datos, para y dime — hay que migrar los valores a la columna nueva, no perderlos.

6. Declara el opt-in en las fichas que lo piden según tu conteo del punto 1.

### Verificación

Los tres verificadores; vuelta completa crear → guardar → editar → ver en P35.0 y en O95; prueba negativa forzando el campo por POST en una ficha que no lo declare; y diff de render de O95 contra el actual.

---

## PARTE 2 — Laboratorio de P35.0 (entrada D): diseño

**El bloqueo anterior era un error mío.** Dije que D dependía del catálogo de genotipos de rubéola de la OMS. El PDF de la página 20 pide `Genotipo:________` — una línea en blanco, no una lista. Va **texto libre**. Igual la titulación (formato `1:8`, `1:32`).

Y el otro bloqueo tampoco existe: la entrada C se cerró el 1 de agosto y `muestras.php` ya no tiene la serología atada a `if ($esB05)`.

**Criterio para esta parte: no se sacrifica UX ni elegancia por lo barato.** Si hace falta capacidad de motor, se construye.

### Lo que pide el PDF

**Ítem 42 — muestras iniciales.** Tres tipos posibles, no siempre los tres:

| Tipo de muestra | Campos |
|---|---|
| 1.ª muestra serológica | fecha de obtención, fecha de resultado, IgM (−/+), IgG (−/+), titulación |
| 2.ª muestra serológica | igual |
| Hisopado nasal y faríngeo | fecha de obtención, fecha de resultado, resultado (−/+), genotipo |

**Ítem 43 — solo en casos confirmados de SRC.** Seguimiento de la excreción viral después de los tres meses de edad, hasta dos pruebas negativas con un mes de intervalo. Dos hisopados con fecha de obtención, fecha de resultado y resultado.

### Fase D1 — Diseño. Reporta y para

**Bloque declarativo** (probablemente barato — confírmalo):

1. Opciones de `tipo_muestra` por ficha. P35.0 necesita 1.ª serológica / 2.ª serológica / hisopado nasal y faríngeo, distintas de las de B05 (SUERO / HNF_FAR / ORINA). ¿La entrada C hizo declarables las **opciones**, o solo qué columnas aparecen?
2. `resultado_igm` / `resultado_igg`: ¿son ENUM de 4 (Pendiente/Positivo/Negativo/Indeterminado)? El PDF de P35.0 pide solo (−)/(+). ¿Se pueden restringir las opciones por ficha sin columna nueva?
3. Columna `titulacion` en `caso_muestra`: ¿migración simple, o algo lo complica?
4. `genotipo`: hoy es un select de 18 genotipos de sarampión. ¿Se puede declarar por ficha como texto libre, o el tipo de control está fijo en `muestras.php`?

**Capacidades nuevas** (diseña las dos):

5. **Condicionalidad dentro de una fila de tabla hija.** IgM / IgG / Titulación visibles cuando `tipo_muestra` es serología; Resultado / Genotipo cuando es hisopado. Quiero el diseño declarativo, en la misma gramática que `depende_de` / `valor_activador`, aplicado a columnas en vez de a campos.

6. **Tabla hija condicionada al valor de un `campo_def`.** El bloque del ítem 43 visible solo cuando *Clasificación del caso* = Confirmado.

Para las dos, dime explícitamente:

- **a qué otras fichas les serviría.** Creo que la 5 le sirve a cualquier ficha con tabla de muestras y que la 6 es pariente de la lógica de anexos que Y59.0 y O95 necesitan (entradas 1, 3 y 5 de `PENDIENTES.md`). Confírmalo o corrígeme.
- si el diseño se puede acotar a **"condicionalidad por el valor de UNA columna"** en lugar de un motor de reglas general. Prefiero lo acotado y elegante antes que lo general a medio construir.
- dónde te obligaría a escribir condiciones por ficha en código compartido. Ahí paras.
- **estimación en sesiones, no en archivos.** Quiero saber si esto es una tarde o son dos días.

**No implementes la Parte 2.** Reporta y espera mi visto bueno.

---

## Nota sobre el commit único de `baec9cf`

Estuvo bien resuelto: si (a)-(d) eran un mecanismo entrelazado en el mismo bloque del manifiesto, partirlo a mano habría añadido riesgo sin beneficio. El criterio de "un commit por bloque" existe para poder aislar una regresión, y ahí no había dos cosas que aislar. Avisar antes de desviarte fue lo correcto.

Una observación de tu propia verificación, para tenerla presente y no para arreglarla ahora: los 6 campos nuevos **existen en el DOM de A36 aunque ocultos**, para que el fetch de cambio de ficha los encuentre. La prueba negativa por POST cubre el riesgo real, así que está bien — pero deja anotado que el opt-in es un filtro de visualización, no una ausencia real, para que nadie lo confunda más adelante.
