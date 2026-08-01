# Pendientes — Petición 2 (IDs por clave y orden explícito)

Generado 2026-07-29. Hallazgos detectados durante la Petición 2 que quedan
fuera de su alcance (no son parte de "migrar de ID a clave" ni de "orden
explícito"), documentados aquí para no perderlos. Ninguno bloquea el
cierre de la petición.

---

## 1. `ver.php` no tiene lógica de anexo (Anexo 1 / Anexo 2) para O95

`app/Views/fichas/ver.php` renderiza todas las secciones de una ficha
guardada por igual, sin distinguir si una muerte materna se notificó como
Anexo 1 o Anexo 2. Resultado: al ver un caso O95 notificado como Anexo 1,
las 8 secciones exclusivas del Anexo 2 (Antecedentes patológicos,
Atención prenatal, Complicaciones, Hospitalizaciones, Parto o aborto,
Entorno social y comunitario, Datos comunitarios, Las cuatro demoras)
igual se muestran, vacías, debajo de las secciones reales.

Ahora que `o95_tipo_de_ficha` persiste (Petición 2, Agregado 1), esto es
arreglable: `ver.php` puede leer ese valor igual que
`secciones-clinicas.php` y ocultar/omitir las secciones que no
correspondan al anexo notificado.

## 2. `ver.php` no usa ninguno de los 21 partials a medida

El formulario de "Nueva ficha" / "Editar" pinta a mano 21 secciones con
lógica propia (dependencias entre campos, tablas hijas, widgets como el
MATRIZ de B26, formato específico por tipo de dato). `ver.php` no incluye
ninguno de esos partials: renderiza *todas* las secciones con el motor
genérico (campo por campo, como texto plano), incluidas las de las 5
fichas que sí tienen partials a medida (B05, B26, O95, y las que se sumen
después).

Consecuencia observable: para esas 5 fichas, lo que el formulario muestra
al capturar y lo que `ver.php` muestra al consultar son visualmente
distintos (un ejemplo concreto: el MATRIZ `b26_contactos_por_lugar` se ve
en `ver.php` como JSON crudo, no como tabla). No es una regresión de esta
sesión — es preexistente y aplica a cualquier ficha con partial a medida.

Investigado durante la Petición 2 al evaluar si excluir secciones de
`ver.php` (ver conversación): no hay separación clara hoy entre "esta
sección tiene rendering dedicado en `ver.php`" y "esta sección solo se ve
por el motor genérico" — cada ficha con partials a medida necesitaría su
propio tratamiento en `ver.php`. Material de Petición 3.

## 3. La validación de servidor de `obligatorio` no sabe de anexo (O95)

`validarCamposDinamicos()` en `CasosController.php` evalúa
`$campo['obligatorio']` campo por campo, sin conocer si ese campo
pertenece a una sección visible solo en Anexo 2. Hoy no hay conflicto
porque las 8 secciones exclusivas de Anexo 2 tienen 0 campos marcados
`obligatorio: true` en el manifiesto (verificado).

Es frágil: si en el futuro alguien marca un campo de esas 8 secciones
como obligatorio (para reforzar la captura del Anexo 2), guardar una
ficha en Anexo 1 se vuelve imposible — el servidor exige un campo que el
formulario nunca mostró, y el usuario ve un error sobre un campo que no
puede ver ni llenar.

**Regla provisional mientras no se resuelve:** los campos gateados por
anexo (hoy, las 8 secciones de `$O95_SECCIONES_SOLO_ANEXO_2` en
`secciones-clinicas.php`) no llevan `obligatorio: true` en el manifiesto.
Si se necesita hacer alguno obligatorio, la validación de servidor
primero tiene que aprender a excluir campos de secciones no visibles
para el anexo actual.

## 4. El cargador salta en silencio las secciones sin campos

`cargar_fichas.php:476-478` omite crear `seccion_def` para cualquier
sección del manifiesto con `"campos": []` ("sección informativa: no
genera seccion_def"). `verificar_fichas.php:206-207` usa el mismo
criterio (`empty($s['campos']) && isset($s['_nota'])`) para excluir esas
secciones de lo que exige encontrar en la base — así que el verificador
tampoco las reporta como faltantes.

Hoy es benigno: es la **única** sección así en las 24 fichas del
manifiesto ("Anexo 6.2 — Lista de chequeo del vacunatorio", Y59.0,
`orden: 9`, verificado contra el manifiesto completo), y está en la
última posición de su ficha, así que no abre un hueco en medio del
orden.

Pero es un mecanismo de descarte silencioso del mismo tipo que la
Petición 2 eliminó en todo lo demás: si alguien agrega una sección al
manifiesto y olvida definirle campos, desaparece de la base sin aviso y
`verificar_fichas.php` sigue diciendo "✅ Sin diferencias". Sugerencia:
que el verificador liste estas secciones como "informativas, omitidas a
propósito" en su reporte, en vez de tratarlas como si no existieran en
el manifiesto.

## 5. Hueco de contenido en Y59.0 (ESAVI): Anexo 6.2

"Anexo 6.2 — Lista de chequeo del vacunatorio" es un anexo real del
MINSA (contenido disponible: 12 secciones romanas, patrón Sí/No +
consideración + comentario, ver PDF páginas 7-8) sin ningún campo
definido en el manifiesto todavía — se dejó fuera a propósito porque
necesita una sección condicional (activarse solo si la clasificación
final es 2 o 3) que el motor de fichas no soporta hoy. Resolver cuando
se valide la ficha Y59.0.

## 6. `fecha_notif` derivable de la cabecera estándar MINSA — esperar a tener más fichas cotejadas

`fecha_notif` (columna NOT NULL de `caso`, de la que derivan
`semana_epi`/`anio_epi`, el badge de SE, la curva epidemiológica, los
reportes por semana y la detección de duplicados) se pide hoy como
input aparte, en vez de derivarse de la cabecera estándar MINSA (Nro.
de ficha, fecha de conocimiento local, fecha de investigación, fecha
de notificación EE.SS.→Red/Microred→DISA→DGE) cuando esa ficha la
tiene como `campo_def`.

Se evaluó un conteo contra el manifiesto (2026-07-30) para decidir si
vale un mecanismo declarativo tipo `"fecha_notif_desde": "<clave>"`
por ficha. Tres correcciones a ese análisis, para no repetirlas:

1. **El denominador correcto es el PDF, no el manifiesto.** El conteo
   inicial dio 2/24 (B26, P35.0) porque son las únicas dos fichas
   cotejadas contra el PDF hasta ahora — la cabecera se agregó *porque*
   se cotejaron, no es representativa de cuántas la tienen en la ficha
   real. Conteo contra el PDF: ~11/24. Las 9 restantes van
   apareciendo conforme avanza el cotejo ficha por ficha.
2. **El mecanismo no necesita que las 6 fechas coincidan.**
   `fecha_notif_desde` es un puntero a UN campo por ficha, no un mapeo
   de los seis. Cada ficha nombra cuál de sus propias fechas es la
   canónica — B05 apuntaría a "fecha de identificación local", Z21 a
   "fecha de reporte" — así que da igual que a B05 le falte la cadena
   de escalamiento completa o que Z21 tenga una sola fecha suelta: el
   mecanismo no exige que las 6 existan, solo que exista LA que se
   declare como canónica.
3. **No es la misma clase de problema que el ítem 3 de este documento.**
   Ahí el campo obligatorio está oculto por el anexo activo (el
   servidor exige algo que el formulario nunca mostró). Acá el campo
   origen de `fecha_notif_desde` está siempre visible en el
   formulario: declararlo `obligatorio: true` en el manifiesto alcanza
   para garantizar que no llegue vacío a un NOT NULL. No hace falta
   validación cruzada.

**Decisión:** no construir el mecanismo todavía — pero por costo, no
por los motivos de arriba. El retrofit es barato (una línea nueva en
el manifiesto por ficha, sin migración de datos: `fecha_notif` es
columna de `caso`, no de `caso_valor`), así que esperar no cuesta
nada y permite diseñarlo viendo 5-6 casos reales en vez de 2.

**Regla mientras se espera:** seguir agregando la cabecera como
campos `campo_def` normales al cotejar cada ficha contra el PDF,
dejando `fecha_notif` como input visible y redundante con ella. Cuando
5 o 6 fichas tengan la cabecera (o su fecha canónica equivalente),
construir `fecha_notif_desde` y hacer el retrofit de todas juntas.

## 7. Normalizar `clave` explícita en los ~870 campos restantes del manifiesto

`cargar_fichas.php` ahora trata `"clave"` del manifiesto como
autoritativa cuando está presente (antes era decorativa: `claveCampo()`
la recalculaba siempre desde la etiqueta, ignorando lo que dijera el
JSON — así se desincronizaron las 62 claves de O95 y las 22 de B05 que
se corrigieron el 2026-07-30). Ese cambio solo se aprovechó para O95
(62 campos) y B05 (22 campos) hasta ahora; el resto del manifiesto
(~870 campos de las otras 22 fichas) sigue derivando la clave de la
etiqueta en cada recarga, sin que `"clave"` la fije.

**Trabajo pendiente:** mismo patrón que la normalización de `"orden"`
en la Fase 6 — escribir en `"clave"` el valor que `claveCampo()`
produce hoy para cada uno de esos ~870 campos, cambio cero de
comportamiento (queda igual a lo que ya hay en `campo_def`), pero deja
de ser derivable-y-por lo tanto fragile ante una futura reescritura de
etiqueta durante el cotejo contra el PDF.

**Por qué no se hizo junto con O95/B05:** un diff de 935 campos tapaba
las decisiones puntuales de esa ronda (las 62 correcciones de O95, la
excepción de `eess_fallecimiento_id`, los 6 campos de ubigeo). Hacerlo
aparte, cuando no compite con otra revisión, deja ver mejor el
resultado.

**Beneficio agregado, no solo cosmético:** con las 935 claves
explícitas, la validación de unicidad que `validarManifiesto()` ya
aplica a las claves explícitas (agregada 2026-07-30) pasa a cubrir
*todo* el manifiesto en vez de solo lo declarado a mano. Las claves
duplicadas de A80 (`a80_realizado_por`), A33, Y07 y Z21 que venían
pendientes desde hace semanas ya se desambiguaron a mano el
2026-07-30 (una clave nueva por cada segunda fila, sin tocar
etiquetas); si algo similar vuelve a aparecer en las ~870 restantes,
esta normalización lo va a mostrar solo, sin tener que buscarlo caso
por caso.

## 8. Ubigeo de O95 (fallecimiento y referencia) sin integridad referencial real — falta un tipo UBIGEO en el motor (hallazgo A.7)

Al corregir las 6 claves de ubigeo de O95 (`o95_departamento_fallecimiento`
/ `_provincia_fallecimiento` / `_distrito_fallecimiento` y sus 3
equivalentes de "Origen de la referencia"), se mantuvo el selector
encadenado departamento → provincia → distrito (`selector-ubigeo.php`,
mismo componente que usa el núcleo del paciente) en vez de volver esos
campos a texto libre. Funciona porque `distrito.id`/`provincia.id`/
`departamento.id` YA son el código INEI (`char(6)`/`char(4)`/`char(2)`,
no un autoincremental) — el `<select>` manda ese código como valor, y el
campo de `campo_def` (tipo `TEXTO`) lo guarda tal cual, sin necesidad de
cambiar de tipo.

**La pérdida real:** a diferencia del ubigeo del núcleo del paciente
(`caso.distrito_id`, columna con FK de verdad a `distrito.id`), estos 6
campos viven en `caso_valor` como cualquier otro campo dinámico — nada en
el esquema impide que se guarde un código que no exista en `distrito`.
La integridad depende enteramente de que el único punto de entrada sea
el `<select>` de `selector-ubigeo.php`; un futuro import, una API, o un
`UPDATE` a mano no tienen ninguna barrera.

**Causa de fondo (hallazgo A.7):** el motor de fichas (`campo_def.tipo`)
no tiene un tipo `UBIGEO` — solo `TEXTO`, `NUMERO`, `FECHA`, `SELECT`,
`MULTISELECT`, `BOOLEANO`, `GRUPO_SI_NO`, `CRONOLOGIA`, `MATRIZ`. Un tipo
`UBIGEO` de verdad permitiría declarar la FK a `distrito` a nivel de
`campo_def` (o al menos validarla en el servidor al guardar), en vez de
confiar en que el único camino de escritura sea un `<select>` que nunca
cambió. Mientras no exista, cualquier otra ficha que necesite un ubigeo
fuera del núcleo del paciente (van a aparecer más — P96 ya usa un
patrón similar para "Residencia de la madre") va a repetir esta misma
pérdida.

**No resuelto ahora:** crear el tipo `UBIGEO` es un cambio de motor
(schema de `campo_def`, `cargar_fichas.php`, `campo-dinamico.php`,
`verificar_fichas.php`), no algo que quepa en una corrección de claves.

## 9. Núcleo no tiene rol y "ocupación" no es un campo núcleo — hallazgo de PETICION_P35_RUBEOLA_CONGENITA.md Fase 1

Al cotejar P35.0 contra el PDF (bloque IV, ítem 29 "Ocupación" de la
madre) se evaluó si `nucleo_omitidos` podía generalizarse a
`{"CASO_INDICE": [...], "MADRE": [...]}` para que cada rol de un
`multi_sujeto` omita distinto del núcleo compartido. Se encontraron dos
problemas de fondo, no solo de alcance:

1. **`datos-paciente-nucleo.php` se incluye una sola vez por página**
   (`fichas/editar.php:147`, `nueva/index.php:191`), sin ningún
   parámetro de rol, y sus `<input>` tienen `name` fijo (`celular`,
   `nacionalidad`, `direccion`, etc.) que mapea 1:1 a columnas de
   `persona`/`caso` del paciente. No existe hoy ningún segundo render de
   este partial para MADRE ni ningún otro rol — construirlo es UI nueva
   (parametrizar nombres para evitar colisión con los del paciente,
   decidir a qué tabla guarda cada campo), no una lectura distinta de un
   dato que ya existe.
2. **"Ocupación" no es un campo núcleo.** No está en `NUCLEO_OMITIBLES`
   (`cargar_fichas.php:125` — celular, nacionalidad, localidad,
   direccion, etnia, nombre_tutor, celular_tutor, gestante: 8 campos,
   sin ocupación), no tiene `data-nucleo-campo` en el partial, y
   `persona` no tiene columna `ocupacion`. Las únicas "ocupación" que
   existen en el código son campos `campo_def` ad-hoc de B05 y O95
   (Anexo 2) — cada ficha la modela por su cuenta, no hay un campo
   compartido que omitir.

**Decisión del usuario (2026-08-01):** no construir el render del
núcleo por rol todavía — sin un segundo render para MADRE, la
generalización de `nucleo_omitidos` a objeto-por-rol no tiene
consumidor. `nucleo_omitidos` de P35.0 quedó como lista plana,
aplicada solo al paciente (`CASO_INDICE`): `["gestante", "celular",
"nombre_tutor"]`. La identidad de la madre (incluyendo ocupación, ítem
29) se resuelve en la Fase 2 de la petición vía columnas propias de
`caso_sujeto`, no reutilizando el núcleo.

**Pendiente real:** si una futura ficha necesita mostrar/ocultar campos
del núcleo de forma distinta por rol de sujeto, hace falta construir
antes el segundo render (partial parametrizado por rol + prefijo de
nombre) — recién ahí generalizar `nucleo_omitidos` a objeto-por-rol
tiene sentido.

## 10. Estado de PETICION_P35_RUBEOLA_CONGENITA.md al 2026-08-01 — retomar desde acá

Fases 0, 1 y 2a/2b/2c cerradas y commiteadas. Fase 2d **no empezó**:
ninguno de los "8 puntos" (los `if (cie10 === 'P96')` hardcodeados)
se tocó todavía. Detalle exacto de qué hace cada uno hoy, para no
tener que redescubrirlo:

### Lo que 2c sí cableó (y lo que NO)

2c construyó el mecanismo de render declarativo (`tieneSujeto()`,
`columnasSujeto()`, `tituloSujeto()`, `metaColumnasSujeto()` en
`app/Core/ayudantes.php`; `CampoDef::rolesConSeccionPropia()`; el
partial genérico `tablas-hijas/residencia-madre.php`; el anclaje
dentro de `secciones-clinicas.php` vía `$mostrarSeparadorSujeto()`) y
lo verificó de punta a punta **para P96**, porque el guardado/lectura
de P96 nunca cambiaron — siguen usando el mismo `datosResidenciaMadre()`
y los mismos 8 hardcodes de siempre. Por eso "P96 pasa crear/editar/ver"
no significa que 2d esté hecho: significa que 2c no rompió lo que ya
funcionaba.

**Para P35.0, el formulario ya muestra los 8 campos de identidad**
(se verificó render con datos reales vía el controlador), pero:
- Si se llena y se guarda hoy, esos 8 valores **se pierden en silencio**:
  `CasosController::crear()`/`actualizar()` solo llaman a
  `datosResidenciaMadre()` (que solo lee `madre_distrito_id`/
  `madre_direccion`) y solo cuando `cie10 === 'P96'` — para P35.0 ese
  `if` nunca entra, así que `$sujetos['MADRE']` nunca se arma.
- No hay error visible: el formulario deja guardar igual (los 8 campos
  no son `obligatorio`), y el usuario no se entera de que no se guardó
  nada de la madre.

### Los 8 puntos, tal cual están hoy (ninguno tocado)

Gate a reemplazar en los 6 primeros:
`tieneSujeto($enfermedad['columnas_sujeto'] ?? null, $rol) &&
!in_array($rol, CampoDef::rolesConSeccionPropia($enfermedadId))`
(el `!in_array(...)` es necesario porque si el rol SÍ tiene sección
propia, el bloque ya se pinta solo, anclado, desde `secciones-clinicas.php`
— repetirlo acá sería doble render).

1. `app/Views/nueva/index.php:223` — `$mostrarResidenciaMadre`, gate del
   formulario "Nueva ficha".
2. `app/Views/fichas/editar.php:177` — mismo gate, edición.
3. `app/Views/fichas/ver.php:203` — gate de la vista de solo lectura.
   **Usa `$caso`, no `$enfermedad`** — ya expone `enfermedad_columnas_sujeto`/
   `enfermedad_titulo_sujeto` desde 2b (`Caso::conDetalle()`), así que el
   dato está, falta consumirlo.
4. `CasosController.php:276` (`nuevo()` GET, repoblar `sujetoMadre` tras
   error de validación).
5. `CasosController.php:336` (`crear()` POST, arma `$sujetos['MADRE']`
   antes de `CasoSujeto::guardarSujetos()`).
6. `CasosController.php:415` (`seccionesClinicas()` AJAX, booleano
   `tieneAntecedentesEpi` para la respuesta JSON).
7. `CasosController.php:781` (`actualizar()` GET, repoblar tras error).
8. `CasosController.php:837` (`actualizar()` POST, arma `$sujetos['MADRE']`).

`datosResidenciaMadre()` (privado en `CasosController.php`) hay que
generalizarlo a algo como `datosSujetoDesdePost(string $rol, array
$columnas): array` que lea `{rol_minuscula}_{columna}` por cada columna
declarada, con el caso especial ya identificado en la charla de diseño:
`fecha_nacimiento` necesita `fechaIsoValida()`, no `trim() ?: null`
plano (mismo criterio que ya usa `fecha_nac` del paciente).

### Dos bloqueos adicionales, encontrados recién, que no son de los "8 puntos"

**A. `CasoSujeto::guardarSujetos()` tiene el INSERT hardcodeado a las
7 columnas viejas** (`apellidos, nombres, doc, sexo, edad, distrito_id,
direccion` — `app/Models/CasoSujeto.php:22-24`). No incluye `tipo_doc`,
`fecha_nacimiento`, `nacionalidad`, `ocupacion`. Aunque se resuelvan
los 8 puntos, si no se amplía este INSERT los 4 campos nuevos se siguen
perdiendo en silencio (probado: un `caso_sujeto` de prueba con esas
columnas quedó en `NULL` porque ni siquiera se intentó insertarlas).
Hay que generalizar el INSERT para que recorra las columnas presentes
en `$datos` en vez de una lista fija, o al menos sumar las 4 nuevas.

**B. `fichas/ver.php` no usa `secciones-clinicas.php`** — tiene su
propio `foreach ($secciones as $seccion)` plano (línea 117), sin el
mecanismo de "Sujeto: X" ni el anclaje que se construyó en 2c. El punto
3 de arriba resuelve el *gate* (mostrar/ocultar), pero no la
*posición*: si no se hace nada más, el bloque de identidad de P35.0 en
`ver.php` va a seguir apareciendo tarde (cerca de "Lugar probable de
infección", como hoy hace el de P96), no pegado a la sección clínica
"Antecedentes de la madre" como sí queda en `editar.php`/`nueva/index.php`
desde 2c. Es una inconsistencia entre la vista de edición y la de solo
lectura. **No decidido:** replicar el anclaje por rol dentro del loop
de `ver.php`, o aceptar la posición fija ahí (es de solo lectura, menos
sensible al orden que un formulario) — decidir antes de tocar el punto 3.

Además, `ver.php` hoy solo imprime `direccion`/`distrito_nombre`
(`sujetoMadreConDistrito()`); para P35.0 hay que generalizarlo a las
demás columnas con la misma tabla `metaColumnasSujeto()`, en modo
texto (sin `<input>`), no repetir el mapeo a mano.

### Fases 3 a 6 — no empezadas

- **Fase 3** (cuadro clínico `GRUPO_SI_NO` → `MATRIZ`, 4 campos con
  columnas `Sí/No/Desconocido/Fecha de manifestación`): sin
  investigar todavía si el renderizador de `MATRIZ` sabe pintar una
  celda de tipo fecha o un trío de radios — es la condición de parada
  explícita de la petición para esta fase. Revisar cómo lo resuelven
  Y59.0 y Z21 (mencionados como precedente) antes de decidir si se
  puede hacer o si el cuadro clínico se queda como está.
- **Fase 4** (10 `depende_de` nuevos): no empezada. Depende de que la
  Fase 0 ya congeló las claves (hecho), así que no hay riesgo de que un
  renombre las rompa — se puede hacer en cualquier momento.
- **Fase 5.1** (`caso_viaje` + columna "semana de gestación", migración
  numerada + `columnas_tablas_hija`): no empezada.
- **Fase 5.2** (`caso_muestra`, laboratorio P35.0 ítems 42-43): no
  empezada. La petición pide explícitamente parar y proponer opción
  (a) ampliar `caso_muestra` vs (b) dejarlo como deuda en
  `PENDIENTES.md` — no elegir por cuenta propia. Falta incluso el
  paso previo: revisar qué columnas tiene `caso_muestra` hoy y cómo
  las usan A80 y las demás fichas con `usa_muestras=1`.
- **Fase 6** (verificaciones a reportar, sin implementar nada): no
  revisada. Pendientes de responder: edad en meses/días del núcleo
  (vs. años), N.° de Historia Clínica, Tiempo de residencia y
  Referencia para localizar en el bloque de domicilio, Pueblo étnico
  vs. Etnia/raza, y el estado de `o95_establecimiento_sanidad_pnp`.
