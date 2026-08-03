# Pendientes — Petición 2 (IDs por clave y orden explícito)

Generado 2026-07-29. Hallazgos detectados durante la Petición 2 que quedan
fuera de su alcance (no son parte de "migrar de ID a clave" ni de "orden
explícito"), documentados aquí para no perderlos. Ninguno bloquea el
cierre de la petición.

---

## 1. `ver.php` no lee ninguno de los mecanismos declarativos que el formulario sí usa para decidir qué mostrar

`app/Views/fichas/ver.php` renderiza toda ficha guardada con un único
motor genérico, campo por campo, como texto plano. No conoce ni el anexo
activo, ni los 21 partials a medida, ni las columnas declaradas de tabla
hija que "Nueva ficha"/"Editar" sí consultan para decidir qué pintar. Es
una sola deuda con tres síntomas, no tres bugs sueltos:

- **Anexo de O95 (Anexo 1 / Anexo 2):** al ver un caso O95 notificado
  como Anexo 1, las 8 secciones exclusivas del Anexo 2 (Antecedentes
  patológicos, Atención prenatal, Complicaciones, Hospitalizaciones,
  Parto o aborto, Entorno social y comunitario, Datos comunitarios, Las
  cuatro demoras) igual se muestran, vacías, debajo de las secciones
  reales. Arreglable: ahora que `o95_tipo_de_ficha` persiste (Petición 2,
  Agregado 1), `ver.php` puede leer ese valor igual que
  `secciones-clinicas.php` y ocultar/omitir lo que no corresponda.
- **21 partials a medida ignorados:** el formulario pinta a mano 21
  secciones con lógica propia (dependencias entre campos, tablas hijas,
  widgets como el MATRIZ de B26, formato específico por tipo de dato).
  `ver.php` no incluye ninguno — para las fichas con partial a medida
  (B05, B26, O95, y las que se sumen después) lo que el formulario
  muestra al capturar y lo que `ver.php` muestra al consultar son
  visualmente distintos (ejemplo concreto: el MATRIZ
  `b26_contactos_por_lugar` se ve en `ver.php` como JSON crudo, no como
  tabla).
- **Tabla hija `caso_muestra`, columnas declarativas ignoradas:** la
  sección "Laboratorio" de `ver.php` está hardcodeada a 6 campos fijos
  (tipo_muestra/tipo_prueba/¿antibiótico?/resultado/fecha de
  toma/fecha de resultado) sin leer `columnas_muestra` — la serología de
  B05 (IgM/IgG/genotipo/PCR) se guarda pero nunca se muestra en el
  detalle del caso, y `titulacion` (agregada en
  PETICION_HC_Y_LABORATORIO.md Parte 2, Fase D1) cae en el mismo hueco en
  cuanto alguna ficha la declare.

Investigado durante la Petición 2 al evaluar si excluir secciones de
`ver.php` (ver conversación): no hay separación clara hoy entre "esta
sección/columna tiene rendering dedicado en `ver.php`" y "esto solo se ve
por el motor genérico" — cada ficha con partial a medida, y cada tabla
hija con columnas declarativas, necesitaría su propio tratamiento en
`ver.php`. Material de Petición 3.

## 2. La validación de servidor de `obligatorio` no sabe de anexo (O95)

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

## 3. El cargador salta en silencio las secciones sin campos — ✅ cerrado (solo el reporte)

Cerrado 2026-08-01. `cargar_fichas.php` no se tocó (sigue omitiendo
`seccion_def` para toda sección con `"campos": []`, a propósito — eso
no era el problema). El cambio fue en `verificar_fichas.php`: el mismo
criterio que ya excluía estas secciones de lo esperado
(`empty($s['campos']) && isset($s['_nota'])`) ahora además las junta en
`secciones_informativas_omitidas` por ficha (JSON y Markdown), y el
reporte las lista explícitamente bajo "Secciones informativas, omitidas
a propósito" con su `_nota`, en vez de no mencionarlas. No cambia
`estado` (`OK`/`CON_DIFERENCIAS`) de ninguna ficha ni el código de
salida — es solo visibilidad.

La red de seguridad que pedía este ítem sigue intacta: una sección
nueva con `"campos": []` que a alguien se le olvide y por eso no traiga
`"_nota"` NO cae en esta excepción — sigue evaluándose como esperada y,
al no existir en la BD, se sigue reportando como "sección faltante"
igual que antes.

<details>
<summary>Redacción original del pendiente (contexto histórico)</summary>

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

</details>

## 4. Hueco de contenido en Y59.0 (ESAVI): Anexo 6.2

"Anexo 6.2 — Lista de chequeo del vacunatorio" es un anexo real del
MINSA (contenido disponible: 12 secciones romanas, patrón Sí/No +
consideración + comentario, ver PDF páginas 7-8) sin ningún campo
definido en el manifiesto todavía — se dejó fuera a propósito porque
necesita una sección condicional (activarse solo si la clasificación
final es 2 o 3) que el motor de fichas no soporta hoy. Resolver cuando
se valide la ficha Y59.0.

## 5. `fecha_notif` derivable de la cabecera estándar MINSA — esperar a tener más fichas cotejadas

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
3. **No es la misma clase de problema que el ítem 2 de este documento.**
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

## 6. Normalizar `clave` explícita en los ~870 campos restantes del manifiesto — ✅ cerrado

Cerrado 2026-08-01 en 21 commits (`0c06edc`..`862ceb8`), uno por ficha
(las 21 que faltaban de las 24: O95, B05 y P35.0 ya estaban 100%
explícitas). Cada commit escribió el valor que `claveCampo()` produce
hoy para los campos sin `clave`, verificado antes de escribir contra
`campo_def` real (no solo corriendo el verificador) — cambio cero de
comportamiento confirmado campo por campo, no ficha por ficha. Las
claves ya explícitas (A33/A80/Y07/Z21, desambiguadas a mano el
2026-07-30) quedaron intactas.

`--check-all` sobre el manifiesto completo, antes de tocar nada, no
encontró ninguna clave duplicada nueva más allá de las 4 ya conocidas
y ya resueltas — así que no hubo nada que reportar y frenar. Cierre
verificado con `cargar_fichas.php` (dry-run, 24/24 fichas) y
`verificar_claves.php` (194/194, 0 faltantes) tras el último commit.

<details>
<summary>Redacción original del pendiente (contexto histórico)</summary>

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

</details>

## 7. Ubigeo de O95 (fallecimiento y referencia) sin integridad referencial real — falta un tipo UBIGEO en el motor (hallazgo A.7)

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

## 8. Núcleo no tiene rol y "ocupación" no es un campo núcleo — hallazgo de PETICION_P35_RUBEOLA_CONGENITA.md Fase 1

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

## 9. Estado de PETICION_P35_RUBEOLA_CONGENITA.md al 2026-08-01 — retomar desde acá

Fases 0, 1 y 2 completas (2a/2b/2c/2d, todas commiteadas). Los "8
puntos" (`if (cie10 === 'P96')` hardcodeados) ya se reemplazaron por
el gate declarativo `tieneSujeto(...) && !in_array($rol,
CampoDef::rolesConSeccionPropia($enfermedadId))`, generalizado a
recorrer TODOS los roles de `columnas_sujeto` (no un solo "MADRE" fijo
en código) vía `rolesSujetoDeclarados()`/`rolesSujetoSinAnclaje()`
(`app/Core/ayudantes.php`). `datosResidenciaMadre()` se reemplazó por
`CasosController::datosSujetoDesdePost(string $rol, array $columnas)`
+ `valoresSujetoPorRolDesdePost(array $enfermedad)` (todos los roles a
la vez, con `fechaIsoValida()` para las columnas tipo fecha).

Los dos bloqueos adicionales que se habían encontrado también se
resolvieron:
- `CasoSujeto::guardarSujetos()` ya no tiene el INSERT hardcodeado a 7
  columnas -- ahora recorre `COLUMNAS_DATOS` (11, incluidas las 4
  nuevas).
- `ver.php` (que no usa `secciones-clinicas.php`) se generalizó por
  separado: recorre todos los roles declarados y sus columnas vía
  `metaColumnasSujeto()`, en modo texto. **Decisión tomada** (no
  quedó abierta): se mantuvo la posición fija de siempre (cerca de
  "Lugar probable de infección"), sin replicar el anclaje por rol de
  2c ahí -- es de solo lectura, menos sensible al orden que un
  formulario, y evita tocar el loop compartido por las 24 fichas en
  esa vista. Si en el futuro se quiere que `ver.php` también ancle el
  bloque junto a la sección clínica del rol, es trabajo aparte.

**Verificado de punta a punta con casos de prueba reales** (crear →
editar → ver, vía `CasosController` real, no simulado; casos borrados
al terminar, 0 filas residuales):
- **P96**: dirección/distrito se guardan, prefilan y muestran igual
  que siempre; las 4 columnas nuevas de `caso_sujeto` quedan `NULL`
  (P96 no las declara) -- comportamiento preservado exacto.
- **P35.0**: las 8 columnas de identidad (tipo_doc, doc, apellidos,
  nombres, edad, fecha_nacimiento, nacionalidad, ocupacion) ahora se
  guardan de verdad, se prefilan en `editar()` y se muestran en
  `ver()` bajo el título "Datos de la madre". Antes de esta fase se
  perdían en silencio.

`verificar_claves.php` (194/194) y `verificar_fichas.php` (24/24, sin
avisos) OK. Sin warnings de PHP atribuibles a estos cambios.

**Fase 2 de la petición: cerrada por completo.** Sigue la Fase 3.

### Fases 3 a 6

**A. BUG, prioridad alta — `campos/matriz.php` detecta tipo de celda por
fila, no por columna; Z21 y Y59.0 ya pierden sus columnas de fecha —
✅ cerrado**

Cerrado 2026-08-01. Antes de tocar el partial se enumeraron los 21
campos `MATRIZ` reales de las 24 fichas (11 fichas los usan: A37.0 (2),
B01 (1), B26 (1), B55 (2), A44 (2), B04X (1), A80 (7), Z21 (1), B24
(1), A50 (2), Y59.0 (1) — es toda la superficie real, la lista original
de abajo tenía a "Chagas" y "B05" de más y le faltaba A50). Con eso se
confirmaron los 2 casos ya conocidos y aparecieron **3 bugs más de la
misma familia, no documentados hasta ahora**:
- **A50**, sus 2 campos (`Pruebas no treponémicas`, `Pruebas
  treponémicas`): columna "Fecha" pintándose como texto libre, mismo
  patrón que Z21/Y59.0.
- **A37.0** "Antibióticos recibidos": columna "Fecha de inicio"
  pintándose como texto libre.
- **B24** "Enfermedades indicadoras de SIDA": bug inverso — columnas
  "Descripción"/"Código CIE-10" (ninguna tiene una palabra que dispare
  el modo libre) caían en modo **radio**, forzando a elegir entre
  escribir la descripción O el código, nunca ambos. El más serio de los
  3: no perdía calidad de dato, impedía capturarlo.

**Arreglo:** por columna (no por fila), no por matriz completa:
- Tipo texto/fecha: fecha si la etiqueta de columna O la de fila
  contiene "FECHA" (el "o fila" preserva el único caso real que
  dependía de la fila — A80 "Fechas de seguimiento", filas "Fecha
  programada"/"Fecha que se realizó", columnas "30/60/90/180 días" sin
  la palabra "fecha").
- Radio vs. libre: una columna solo es candidata a radio si además de
  no matchear las palabras de siempre (fecha/dosis/días/n.°/cantidad/mm)
  está escrita TODA en mayúsculas en el manifiesto — así se distinguen
  los códigos cerrados reales (`DIM/AUS/NORM/IGN`, `SI/NO/IGN/
  PROXIMAL/DISTAL`, `AUS/PRES/IGN`, las 5 matrices 100% radio de A80)
  de una etiqueta de columna común (`Total`, `Descripción`, `Cara`) que
  sin ese filtro colaría como opción exclusiva (el bug de B24 de
  arriba, y por qué "detectar por columna" a secas —sin el requisito de
  mayúsculas— habría sido peor que no tocar nada).
- Modo híbrido nuevo: cuando una fila mezcla columnas radio y libres,
  las radio comparten un único grupo bajo la clave reservada `_radio`
  (no colisiona con los índices enteros de columna) y las libres siguen
  en `[fIdx][cIdx]` — probado con un campo sintético (no existe hoy en
  ninguna ficha), mecánica confirmada sin colisión de nombres.

Los 2 modos que ya eran 100% correctos (radio puro: 5 campos de A80;
libre puro: el resto) quedan con el mismo formato de valor de siempre
— sin datos capturados que migrar (`caso_valor` en 0 filas para los 21
campos, verificado antes de tocar nada).

**Verificado con render real, campo por campo y ficha por ficha**
(`scratch/test_render_matriz_item_a.php`, no commiteado —
`/scratch` está en `.gitignore`, mismo patrón que
`test_render_b26.php`): render aislado de los 21 campos (vacío +
prefill fabricado) antes/después, diff línea por línea contra la
corrida sin el fix — coincide exactamente con lo esperado (los 5 bugs
corregidos, cero cambios en los 16 campos restantes). Además, render de
página completa (`nueva/index.php`) de las 11 fichas con sesión ADMIN
real (B24 y Z21 son fichas confidenciales — sin ADMIN,
`secciones-clinicas.php` filtra sus campos `sensible` y esconde el bug
detrás de "Todavía no hay campos clínicos definidos aquí", no relacionado
con este fix); A80 (7 campos `MATRIZ` en una sola página) confirma que
usar closures locales en vez de `function`/`const` de nivel superior no
revienta con "Cannot redeclare" al repetirse el `require`. 0
excepciones, 0 warnings/notices con `E_ALL`.

<details>
<summary>Redacción original del pendiente (contexto histórico)</summary>

`app/Views/partials/campos/matriz.php` decide un solo modo de render
para la matriz COMPLETA (radios exclusivos vs. texto/fecha libre), y
cuando cae al modo libre, decide `type="date"` vs `type="text"` mirando
el nombre de la **fila** (`str_contains($fila, 'FECHA')`), no el de la
columna. Encontrado al investigar la Fase 3 de P35.0, pero **no es un
hueco de P35.0 — son dos fichas ya cargadas y en uso, con pérdida de
calidad de dato real, silenciosa, que nadie había notado**:

- **Z21** ("Pruebas diagnósticas"): columnas `["Fecha de toma de
  muestra", "Resultado"]`, filas `1.er PCR / 2.° PCR / Prueba de ELISA /
  Prueba confirmatoria`. Como una columna dice "Fecha", toda la matriz
  cae a modo texto; como ninguna fila dice "Fecha", la columna "Fecha de
  toma de muestra" se pinta como `<input type="text">` en vez de
  `type="date"`.
- **Y59.0** ("Signos y síntomas", 18 ítems): columnas `["Tiempo...",
  "Fecha de inicio", "Fecha de término"]`. Mismo problema — ambas
  columnas de fecha se pintan como texto libre.

**Arreglo:** detección de tipo por columna (no por fila) + modo híbrido
que permita radios exclusivos en unas columnas y texto/fecha libre en
otras dentro de la misma fila. Es el único partial `MATRIZ` de todo el
sistema — superficie de prueba: toda ficha que lo use (A80, B26, B05,
Chagas, Mpox, A37.0, B01, B55, A44, B04X, A50, B24, además de Z21 y
Y59.0). No intentado en esta sesión — verificar con cada ficha antes de
tocar el partial compartido.

</details>

**B. Cotejo incompleto de P35.0, prioridad media — 17 fechas de
manifestación sin capturar. Ya NO bloqueada por A (A cerrado
2026-08-01) — ✅ cerrado**

Cerrado 2026-08-01. Los 4 campos `GRUPO_SI_NO` de "Cuadro clínico —
manifestaciones" pasaron a `MATRIZ`: `"opciones"` se renombró a
`"filas"` (mismos 17 ítems, sin tocar texto) y se agregó `"columnas":
["SI", "NO", "DESCONOCIDO", "Fecha de manifestación"]` a los 4. Con el
fix de A ya aplicado, esto cae directo en modo **híbrido**: las 3
primeras columnas (mayúsculas, sin palabra libre) son radio exclusivo,
"Fecha de manifestación" (contiene "FECHA", no está en mayúsculas) es
`type="date"` libre — primer consumidor real del modo híbrido que A
dejó preparado pero sin ejemplos en las 24 fichas. `caso_valor` tenía 0
filas para los 4 campos antes de tocar nada (verificado) — no había
respuestas capturadas que migrar ni perder.

No se tocó ningún `.php`: `campos/matriz.php` (fix de A) y
`CasosController.php` (guarda/lee `GRUPO_SI_NO` y `MATRIZ` de forma
idéntica, ambos arrays JSON) ya soportaban esto sin cambios — todo el
cierre es manifiesto. `php cargar_fichas.php --apply --cie10=P35.0`
aplicado; `verificar_fichas.php` (P35.0: 35/35, sin diferencias) y
`verificar_claves.php` (194/194) OK.

**Verificado con render y caso real vía `CasosController`**
(`scratch/test_p350_manifestaciones_matriz_item_b.php`, gitignored;
caso borrado al terminar, 0 filas residuales): render de "Nueva ficha"
confirma las 4 tablas con columnas `SI | NO | DESCONOCIDO | Fecha de
manifestación`, filas correctas (4+1+3+9 = 17) y modo híbrido
(`_radio` presente, 0 columnas en texto libre). Un caso de prueba con
valores en ambos tipos de columna (radio SI/NO/DESCONOCIDO + fecha en
algunas filas, sin fecha en otras) se creó, verificó en BD
(`caso_valor` con el JSON híbrido esperado por campo), se confirmó su
prefill en `editar()` (checked/value coinciden con lo guardado) y se
actualizó cambiando un valor (`actualizar()` lo reflejó correctamente
sin tocar los otros 3 campos). `ver()` no truena (sigue mostrando JSON
crudo para `MATRIZ` — ítem 1 de este documento, preexistente, no es
parte de este cierre). 0 excepciones, 0 warnings/notices con `E_ALL`.

**Cambio de aspecto esperado, no es un bug:** al convertir de
`GRUPO_SI_NO` a `MATRIZ`, se pierde el agrupamiento visual "subgrupo"
que unía "Manifestaciones oftálmicas" + "Manifestación auditiva" +
"Cardiopatía congénita" en un solo bloque con un encabezado compartido
(`secciones-clinicas.php:130`, `$esSubgrupo` — ese mecanismo solo
aplica entre `GRUPO_SI_NO` consecutivos). Cada uno pasa a ser su propia
tabla independiente con su propia etiqueta, igual que cualquier otro
campo `MATRIZ` de las 24 fichas — es la consecuencia normal de cambiar
de tipo de campo, no algo a corregir.

**C. BUG, prioridad alta — `muestras.php` tiene las columnas de
serología hardcodeadas a B05 con `if ($esB05)`, no declarativas —
✅ cerrado**

Cerrado 2026-08-01. Las 7 columnas de serología (`resultado_pcr`,
`fecha_result_pcr`, `genotipo`, `resultado_igm`, `fecha_result_igm`,
`resultado_igg`, `fecha_result_igg`) salieron de dentro del
`if ($esB05)` de `muestras.php` y pasaron al mismo mecanismo
declarativo que ya usan A80 y el resto de las 13 fichas con
`usa_muestras=1`:
- `cargar_fichas.php`: las 7 se agregaron a
  `COLUMNAS_TABLA_HIJA_VALIDAS['caso_muestra']` (antes solo tenía las 9
  genéricas).
- `manifiesto_fichas.json`: B05 declara
  `columnas_tablas_hija.caso_muestra` con exactamente esas 7 — ni una
  más, ni una menos que las que el `if` pintaba antes.
- `muestras.php`: los 7 bloques se movieron tal cual (mismas opciones,
  mismas clases `b05-pcr-group`/`b05-serologia-group` para el toggle
  de `public/js/ficha.js` que no se tocó) a `<?php if
  ($muestra('...')): ?>` fuera del `if/else` de siempre. `tipo_muestra`,
  `fecha_toma`, `fecha_envio_ins` y `fecha_recepcion_ins` **no** se
  tocaron: siguen hardcodeados dentro de `if ($esB05)` con sus
  etiquetas propias (`fecha_recepcion_ins` ni siquiera está en
  `COLUMNAS_TABLA_HIJA_VALIDAS` — eso es otro alcance, no pedido acá).

**Verificado con render real** (`scratch/test_render_muestras_item_c.php`,
gitignored): render aislado de B05 (vacío + una fila SUERO + una fila
HNF_FAR, para ejercitar ambos grupos condicionales) antes/después vía
`git stash` + re-`--apply` de la BD en cada estado — la sección
"Laboratorio" de una página completa (`nueva/index.php`, sesión ADMIN)
queda **byte-idéntica** salvo comentarios y espacios (diff 0 tras
normalizar). Las otras 12 fichas con `usa_muestras=1` (A00, A36, A37.0,
A44, A80, A95, A97, B01, B24, B55, B57, P35.0) confirman
`serologia_presente=no`: ninguna ve aparecer los 7 campos nuevos, cero
regresión. `cargar_fichas.php` (dry-run 24/24), `verificar_fichas.php`
(B05: 85/85, sin diferencias) y `verificar_claves.php` (194/194) OK.
`caso_muestra` tenía 0 filas antes de tocar nada — no había datos que
migrar.

**D. Cotejo incompleto de P35.0, prioridad media — laboratorio (ítems
42-43) sin capturar. Ya NO bloqueada por C (C cerrado 2026-08-01) —
sigue sin implementar, es trabajo aparte (ver notas propias de D:
tipos de muestra, IgM/IgG, Titulación y catálogo de genotipos siguen
sin resolver, ninguno se tocó en el cierre de C)**

El bloque VI del PDF (ítems 42-43) pide, por muestra: tipo de muestra,
fecha de obtención, fecha de resultado, IgM (-/+), IgG (-/+ con
Titulación), y Genotipo para hisopado nasal y faríngeo — más un
segundo bloque (ítem 43, solo casos confirmados de SRC: seguimiento de
excreción viral desde los 3 meses de edad hasta 2 pruebas negativas
con 1 mes de intervalo). **Decisión (2026-08-01): se queda sin
laboratorio, documentado como deuda, hasta que C esté resuelto.**

Lo que hace falta cuando se retome (no es solo "activar" las columnas
de B05 — ninguna sirve tal cual para P35.0):
- Tipos de muestra propios: "1.ª muestra serológica" / "2.ª muestra
  serológica" / "Hisopado nasal y faríngeo" (B05 usa
  SUERO/HNF_FAR/ORINA — mismo concepto, vocabulario distinto).
- IgM e IgG con 2 estados puros `(-)/(+)` (B05 los modela con 4:
  Pendiente/Positivo/Negativo/Indeterminado).
- Columna **Titulación** para IgG: no existe ninguna en `caso_muestra`.
- Catálogo de **genotipos de rubéola**: las 18 opciones que hoy tiene
  `genotipo` (D8, B3, H1...) son nomenclatura OMS **de sarampión** —
  reutilizarlas mostraría genotipos equivocados. Ni siquiera una
  versión acotada (solo P35.0, sin tocar B05) puede reusar ese
  catálogo tal cual.
- El ítem 43 (seguimiento viral) **no** necesita modelado nuevo: es la
  misma tabla repetible de `caso_muestra`, con filas adicionales de
  fecha posterior — no es trabajo extra una vez resuelto lo anterior.

**E. BUG, prioridad alta — "Referencia para localizar" hardcodeada a
B05 dentro del partial que comparten las 24 fichas — ✅ cerrado**

Cerrado 2026-08-01. "Referencia para localizar" dejó de ser un
`campo_def` propio de B05 (`b05_referencia_para_localizar_cerca_de_iglesia_fundo_co`,
resuelto por etiqueta desde `datos-paciente-b05-loader.php`) y pasó a
ser núcleo real, mismo mecanismo que `celular`/`nacionalidad`/
`localidad`/`direccion`:
- **Schema**: `persona.referencia_localizar VARCHAR(160)` nueva
  (`sql/migraciones/add_referencia_localizar_persona.php`, mismo ancho
  que `direccion`; `sql/01_esquema_actual.sql` recongelado).
  `Caso::conDetalle()` la suma a su SELECT explícito.
- **`cargar_fichas.php`**: `'referencia_localizar'` sumado a
  `NUCLEO_OMITIBLES`.
- **`datos-paciente-nucleo.php`**: nuevo campo declarativo
  `data-nucleo-campo="referencia_localizar"` en el primer bloque
  (junto a Domicilio actual); el `b05-field-wrap` de "Referencia +
  Tipo de localidad" se redujo a solo "Tipo de localidad" (B05 sigue
  siendo la única ficha con ese campo — fuera de alcance acá).
- **`manifiesto_fichas.json`**: el `campo_def` de B05 se eliminó; las
  23 fichas restantes declaran `nucleo_omitidos += "referencia_localizar"`
  (4 ya tenían la lista, 19 la declaran por primera vez) para que
  ninguna cambie de aspecto — B05 es la única que no la omite, así que
  sigue siendo la única que la muestra por defecto, igual que antes.
- **`CasosController`**: `crear()`, `editar()` (prefill),
  `actualizar()` y `valoresFijosPorDefecto()` leen/escriben
  `referencia_localizar` igual que `direccion`; `sanearCamposNucleo()`
  la persiste en `persona`.
- **`fichas/ver.php`**: se agregó, condicionada a que tenga valor
  (mismo criterio que "Madre / Tutor") — sin esto, un caso B05 real
  hubiera perdido visibilidad del dato en la vista de solo lectura.

**Hallazgo colateral, no relacionado con este ítem:** verificando el
flujo completo (crear → editar → actualizar → ver) con un caso real se
encontró que `CasosController::actualizar()` — guardar cambios sobre
CUALQUIER ficha ya creada, no solo B05 — estaba **completamente roto**:
usaba `$enfermedad` sin haberla asignado nunca (`opcionesClasificacionPara($enfermedad)`
con `null`, `TypeError` fatal garantizado en cualquier intento de editar
cualquier ficha, para cualquier CIE-10, desde antes de esta sesión —
confirmado contra el primer commit de hoy). Detrás de ese fatal había un
segundo bug enmascarado: `actualizar()` nunca leía
`investigador_profesion_sel`/`_otra` de `$_POST` (sí lo hace `crear()`),
así que ese campo se habría guardado `NULL` en cada edición una vez
arreglado el primero. Ambos corregidos (mismo patrón que `crear()`) en
un commit aparte, con su propia verificación de extremo a extremo — se
reporta acá porque aparecieron mientras se verificaba este ítem, no
porque sean parte de su alcance.

**Otros bloques condicionados por ficha en `datos-paciente-nucleo.php`**
(pedido explícitamente al ejecutar este ítem — ninguno se tocó, quedan
para quien decida si vale la pena):
- **B05, mismo patrón que el bug de arriba, aún sin resolver**: 4
  campos más siguen siendo `campo_def` propios de B05, resueltos por
  ETIQUETA (no por clave) desde `datos-paciente-b05-loader.php`, e
  irreproducibles por otra ficha sin declarar su propio `campo_def` —
  "Tipo de localidad" (se quedó donde estaba, no era parte del pedido),
  "Pueblo étnico o etnia", "Ocupación" (fuera del bloque
  `b05-field-wrap`, con clase `.b05-elem`), "Lugar probable de parto" y
  el grupo "¿Es menor de edad?" + datos del tutor (doc/nombre/teléfono).
  Candidatos directos a la misma normalización que este ítem si otra
  ficha llega a necesitar alguno.
- **O95 (Anexo 2)**: un bloque grande (Grupo étnico, Etnia/Pueblo
  étnico, Idioma, Nivel educativo, Estado civil, Ocupación, Tipo de
  seguro — 9 campos) se renderiza siempre en el DOM para las 24 fichas,
  oculto vía `hidden` salvo cuando la ficha activa es O95 y el anexo es
  el 2. A diferencia de B05, sí usa `$resolvedorPara('O95')` (por
  clave, no por etiqueta) y trae un comentario explícito documentando
  por qué está desatado de `$enfermedad` — no es el mismo "nadie sabía
  que estaba ahí" que B05, pero sigue siendo lógica condicionada por
  ficha en un partial compartido. Nota al margen: su campo "Ocupación"
  (`o95_ocupacion`) queda duplicado en el DOM junto al nuevo "Ocupación"
  de B05 (dos elementos con la misma etiqueta, uno oculto) — inofensivo
  hoy, preexistente, no introducido por este cierre.
- **B26**: `$esB26Gest` decide si la fila "¿Gestante?" muestra "Semanas
  de gestación" (default, `caso.semanas_gestacion`) o "Trimestre de
  gestación" (`caso.trimestre_gestacion`, solo B26). A diferencia de
  los dos de arriba, ambas son columnas núcleo reales (no
  `campo_def` disfrazado) — es una variante de UI legítima entre dos
  datos ya declarativos, no el bug de reusabilidad que pedía este
  ítem, pero igual es lógica condicionada por CIE-10 en el partial
  compartido.
- **`datos-paciente-b05.php`**: archivo aparte, con su propia copia
  casi idéntica de toda esta lógica (incluida su propia versión de
  "Referencia para localizar", ya no tocada). **No lo requiere nada**
  (`grep` sin resultados fuera de sí mismo) — parece un duplicado
  huérfano de antes de que `datos-paciente-nucleo.php` +
  `datos-paciente-b05-loader.php` centralizaran esto. No se tocó
  (fuera de alcance, decisión de limpieza aparte), pero vale borrarlo
  si se confirma que de verdad no lo usa nada.

Verificado con caso real vía `CasosController` (`scratch/test_referencia_localizar_item_e.php`,
gitignored; caso borrado al terminar, 0 filas residuales): crear un
caso B05 con "Referencia para localizar" lo persiste en
`persona.referencia_localizar`; `editar()` lo prefila; `actualizar()`
con un valor nuevo lo actualiza; `ver()` lo muestra. Render de "Nueva
ficha" (GET, sesión ADMIN) para las 24 fichas: 0 excepciones; el campo
aparece `hidden` en las 23 no-B05 y visible solo en B05.
`verificar_fichas.php` (24/24 sin diferencias) y `verificar_claves.php`
(194/194) OK. `caso_valor` tenía 0 filas para la clave eliminada antes
de tocar nada — no había datos que migrar.

**F. Edad en meses/días (ítem 11 del PDF de P35.0) — ✅ cerrado (Fase
F2, `PETICION_MAPEO_Y_EDAD.md` Parte 2)**

Dejó de ser una decisión solo de P35.0: el inventario contra el PDF
real (`pdftotext`, ficha por ficha, no contra
`CAMPOS_FICHAS_EPIDEMIOLOGICAS.md`) confirmó que al menos 9 fichas
piden "Edad" con una unidad distinta de años — A37.0 y A33/A35
(Años/Meses), B01/B26/B04X/A00 (Años/Meses/Días), Y59.0 (las 5:
Años/Meses/Días/Hora/Minutos) y P35.0 (Meses/Días, **sin** años).
Mecanismo del núcleo, no un `campo_def` por ficha repetido 9 veces.

**Diseño aprobado por el usuario** (contrastado contra el inventario
de consumidores de `edadDesdeFecha()`/`persona.fecha_nac`: solo 3
sitios de solo lectura — `fichas/index.php`, `fichas/ver.php` y el
autocompletar de "Nueva ficha", que calcula `edad` pero nadie lo
consume en `ficha.js` — ninguno rompe):
- `caso.edad_valor` (SMALLINT UNSIGNED) + `caso.edad_unidad` (ENUM de
  5 valores) — en **`caso`, no `persona`**: es una foto al momento de
  esta notificación (mismo criterio que `fecha_inicio_sintomas`), no
  un atributo permanente del paciente. `persona` es única por
  documento — guardarlo ahí habría hecho que un segundo caso del
  mismo paciente sobrescribiera la edad del primero, corrompiendo
  fichas ya cerradas (relevante justo para P35.0/A33, que notifican
  menores de 1 año con reingresos posibles en el mismo año).
- `persona.fecha_nac` no cambia de semántica; `edadDesdeFecha()` sigue
  siendo la fuente para los 3 sitios de solo lectura cuando la ficha
  activa no declaró `unidades_edad`.
- `enfermedad.unidades_edad` (JSON, mismo patrón que
  `nucleo_omitidos`/`columnas_sujeto`/`titulo_sujeto`) declarado por
  ficha en el manifiesto — pero **opt-in**, al revés que
  `nucleo_omitidos`: ausente equivale al comportamiento actual (solo
  años, derivado), así que las fichas que no lo necesitan no se
  tocan (no hace falta declarar nada en las otras 23).
- Edad capturada y Fecha de nacimiento son **campos independientes**,
  sin derivación entre ellos: el PDF los pide como dos ítems
  separados (P35.0: ítem 11 y 13). A00 ni siquiera tiene "Fecha de
  nacimiento" en su propia página — derivar la edad desde una fecha
  que esa ficha nunca pidió habría mostrado un dato inventado por la
  app, no por el formulario real.
- `fichas/index.php`/`fichas/ver.php` muestran la edad capturada con
  su unidad cuando la ficha activa declaró `unidades_edad`, la
  derivada de `fecha_nac` cuando no. El autocompletar se deja como
  estaba, sin ampliar (dato muerto confirmado, no vale la pena).

**Declarado en el manifiesto: solo P35.0**, `["MESES","DIAS"]` — ya
cotejada contra su página del PDF en sesiones anteriores. Las otras 8
fichas del inventario (A37.0, B01, B26, B04X, Y59.0, A33, A35, A00)
quedan para una **Fase F3** posterior, una por una, cada una
verificada contra su propia página antes de declararla.

**Verificado con render y caso real vía `CasosController`**
(`scratch/test_edad_unidad_f2.php`, gitignored; casos borrados al
terminar, 1 fila residual preexistente sin cambios): render de "Nueva
ficha" para las 24 fichas confirma que el bloque de Edad/Unidad solo
aparece en P35.0 (23/23 sin bloque + 1/1 con bloque, OK). Un caso
P35.0 se creó con `edad_valor=3`/`edad_unidad=MESES`, se verificó en
`caso` (no en `caso_valor`: no es un campo dinámico), se confirmó su
prefill en `editar()` ("3" + "MESES" seleccionado), se mostró
correctamente en `ver()` ("3 meses") y en `fichas/index.php` ("3m"),
y se actualizó a `10`/`DIAS` vía `actualizar()` — reflejado en los
tres lugares ("10 días" / "10d"). Un caso A36 se creó **enviando**
`edad_valor=99`/`edad_unidad=DIAS` en el POST a propósito: como A36
no declara `unidades_edad`, `sanearCamposNucleo()` los descartó (NULL
en la BD) — confirma que el opt-in no se puede forzar desde el
formulario. `editar()` de A36 no renderiza el input (bloque ausente
del HTML), y `ver()`/`fichas/index.php` siguen mostrando la edad
derivada de `fecha_nac` sin cambio ("25 años" / "25a") — A36 quedó
idéntica. `cargar_fichas.php` (dry-run 24/24, aplicado con
`--cie10=P35.0`), `verificar_fichas.php` (24/24 OK) y
`verificar_claves.php` (194/194) OK.

**Fuera de alcance explícito (decisión del usuario): `ImportacionController.php`.**
No toca edad hoy — solo lee `fecha_nac` crudo del Excel. Agregarle
columnas de `edad_valor`/`edad_unidad` al formato de importación
masiva no era necesario para cerrar F y queda como pendiente menor,
sin fecha ni prioridad asignada.

**G. "Tiempo de residencia" (ítem 18 del PDF de P35.0) — ✅ cerrado**

**H. "Pueblo étnico" (ítem 15 del PDF de P35.0, texto libre) — ✅
cerrado**

Cerrados juntos 2026-08-01 (mismo commit): dos `campo_def` `TEXTO`
propios de P35.0, con clave explícita (`p35_0_tiempo_de_residencia`,
`p35_0_pueblo_etnico`), en una sección nueva **"Datos del paciente
(adicionales)"** — mismo nombre y propósito que ya usa Y59.0 para lo
mismo (datos de paciente que no viven en el núcleo compartido). Se
insertó como sección orden 2, justo después de "Datos de notificación
e investigación del caso" y antes de "Antecedentes del paciente" — es
la posición más cercana posible al núcleo (domicilio, Etnia/raza) que
permite el mecanismo de `campo_def`, ya que ambos temas son del núcleo
compartido, no de ninguna de las 6 secciones propias de P35.0. Las 5
secciones siguientes se renumeraron (+1 cada una) para no chocar
`"orden"`.

Ninguno reusa B05/O95: sus campos de etnia/pueblo (`b05_pueblo_etnico_o_etnia`,
`o95_etnia_pueblo_etnico`) son `SELECT` de catálogo cerrado que
fusiona pueblo+etnia — reusarlos habría degradado el texto libre que
pide el ítem 15. Al renderizar se confirmó, de hecho, que el bloque
oculto del Anexo 2 de O95 ("Etnia / raza + Pueblo étnico o etnia +
Ocupación", presente en el DOM de las 24 fichas, ya reportado en el
cierre del ítem E) vive literalmente pegado al `<select>` de Etnia/raza
del núcleo — confirma que ese hueco ya estaba ocupado por un mecanismo
de otra ficha, y valida no haberlo reutilizado.

**Verificado con render y caso real vía `CasosController`**
(`scratch/test_p350_pueblo_etnico_tiempo_residencia_item_gh.php`,
gitignored; caso borrado al terminar, 0 filas residuales): render de
"Nueva ficha" confirma el orden real en el HTML (núcleo → encabezado
"Datos del paciente (adicionales)" → Pueblo étnico → Tiempo de
residencia → Antecedentes del paciente) y que ambos son
`<input type="text">` simples. Un caso de prueba se creó con valores en
ambos campos, se verificó en `caso_valor`, se confirmó su prefill en
`editar()` y se actualizó con valores nuevos vía `actualizar()`;
`ver()` no truena. `cargar_fichas.php` (dry-run 24/24),
`verificar_fichas.php` (P35.0: 37/37, sin diferencias; 24/24 general) y
`verificar_claves.php` (194/194) OK. 0 excepciones, 0 warnings/notices
con `E_ALL`.

**Hallazgo colateral, no corregido — fuera de alcance:**
`cargar_fichas.php:474` inserta `campo_def.obligatorio` con el literal
`0` fijo en el SQL, sin leer `$campo['obligatorio']` del manifiesto en
ningún punto del archivo. Confirmado contra la BD: los 936 `campo_def`
existentes tienen `obligatorio = 0`, sin excepción, y el manifiesto no
declara `"obligatorio": true` en ningún campo de las 24 fichas
(0 ocurrencias) — así que hoy no hay ninguna discrepancia observable,
pero si alguien alguna vez declara `"obligatorio": true` esperando que
`validarCamposDinamicos()` la exija, no va a pasar nada: el valor nunca
llega a la base. No se tocó (no es parte de lo pedido en G/H, y
arreglarlo cambiaría comportamiento de las 24 fichas a la vez).

**I. BUG, prioridad alta — "Fecha de inicio de síntomas" es un campo
fijo del núcleo de `caso`, condicionado por ficha en código compartido
— cuarta instancia de la misma familia que A, C y E**

`secciones-clinicas.php:316-326` inyecta un `<input name="fecha_inicio_sintomas"
required>` (columna `caso.fecha_inicio_sintomas`) al principio de la
tarjeta de `$secciones[0]` para toda ficha que no esté en
`!in_array($cie10, ['A80', 'B05', 'O95'])` — el mismo patrón de
`if ($cie10 === ...)` hardcodeado en un partial compartido que ya
aparecía en `campos/matriz.php` (ítem A), `muestras.php` (ítem C) y
`datos-paciente-nucleo.php` (ítem E), salvo que acá la "lista de
excepción" no vive en el manifiesto — vive en un array literal de PHP
que alguien tiene que editar a mano cada vez que se descubre una ficha
más que no lo pide.

Alcance mayor que los tres anteriores: es obligatorio en **21 de las
24 fichas** (todas menos A80/B05/O95, que en vez de mostrarlo lo
derivan solas vía `CasosController::extraerFechaInicioSintomas()`,
tomando el primer campo `FECHA` que la ficha declare). También
participa `ImportacionController.php` (columna fija obligatoria en la
carga masiva) y `fichas/ver.php:126` (se muestra de solo lectura).

**Caso conocido hoy:** el PDF de P35.0 no tiene ningún ítem de "fecha
de inicio de síntomas" global (el ítem 34 lleva fecha *por
manifestación*, ya resuelto en el ítem B) — y aun así el campo se
exige igual, porque P35.0 nunca estuvo en la lista de exclusión.

**Sospecha sin verificar:** A97 declara además su propio `campo_def`
(`a97_fecha_de_inicio_de_sintomas`, tipo FECHA) con etiqueta casi
idéntica al campo fijo del núcleo — podría ser una captura duplicada
del mismo dato. No confirmado contra el PDF de A97; solo se detectó
por coincidencia de nombre al investigar P35.0.

**Efecto colateral del andamiaje, ya observado:** como el campo se
inyecta siempre en `$secciones[0]` en vez de en una sección fija, cambia
de tarjeta según qué sección del manifiesto quede primero. Al agregar
"Datos del paciente (adicionales)" en el cierre de G/H, el campo se
corrió de "Antecedentes del paciente" a la sección nueva — nadie lo
movió a propósito, es consecuencia de cuál sección gana el índice 0.

No implementado ni corregido — solo reportado, a pedido explícito.

**J. Prioridad media — el núcleo no es extensible desde el
manifiesto; "Pueblo étnico" y "Tiempo de residencia" de P35.0 quedan
como `campo_def`, separados de Etnia/raza y del domicilio**

`nucleo_omitidos` (ítem E) es un interruptor de **ocultar** HTML fijo
que ya existe en `datos-paciente-nucleo.php` — no un mecanismo para
**declarar** un campo nuevo del núcleo desde el manifiesto. Agregar un
campo real al núcleo sigue costando ~8 archivos a mano: migración,
`NUCLEO_OMITIBLES`, el HTML del partial, `Caso::conDetalle()`, 5 puntos
de `CasosController.php` (crear/editar/actualizar/valoresFijosPorDefecto/
sanearCamposNucleo) y `ver.php` — el mismo trabajo que la entrada E
hizo para "Referencia para localizar", sin atajo.

Consecuencia inmediata (ítems G/H, cerrados hoy): "Pueblo étnico" y
"Tiempo de residencia" quedaron como `campo_def` propios de P35.0 en
vez de vivir junto a "Etnia / raza" y el bloque de domicilio del
núcleo, como los tiene el PDF. No es un error de esos cierres — es el
límite real del mecanismo hoy. Se resuelve con un motor declarativo de
verdad (Petición 3), no repitiendo el patrón de la entrada E
campo por campo.

**Decisión abierta para cuando se retome:** el bloque de "Etnia / raza"
en `datos-paciente-nucleo.php` (líneas 66-222) está completo detrás de
`if ($puedeVerEtnia)` (`Auth::tieneRol('ADMIN')`), porque la etnia es
dato sensible. Si "Pueblo étnico" se sube ahí tal cual, hereda ese
mismo candado — un REGISTRADOR, que es quien normalmente llena la
ficha, ya no podría escribirlo. Hoy, como `campo_def` de P35.0, sí
puede. Habría que decidir si "Pueblo étnico" merece el mismo nivel de
sensibilidad que "Etnia / raza" o necesita su propio bloque, no
heredar el candado por estar cerca en el layout.

No implementado — solo reportado, a pedido explícito.

**K. Prioridad alta — campos sensibles con gate de rol ADMIN en el
formulario de captura: protege la entrada, no confirmadamente la
salida**

`campo_def.sensible = 1` bloquea la escritura de un campo a cualquiera
que no sea ADMIN: `secciones-clinicas.php:107-108` lo oculta del
formulario, y `CasosController.php` (~línea 1006, gateado por
`$puedeVerSensibles` en las líneas 539/583/957) descarta en el
servidor cualquier valor que llegue por POST para ese campo desde un
rol distinto — preserva el valor existente en vez de sobrescribirlo,
así que ni siquiera un POST armado a mano lo cambia. Hoy son **127
campos** en 5 fichas: A50 (23/25), B04X (11/41), B24 (26/26, la ficha
entera), Y07 (25/25, entera), Z21 (42/42, entera). Si el ADMIN no
registra fichas directamente, esos campos no se llenan nunca — la
preocupación del pedido original.

**La exclusión de exportaciones no es la protección que parece.**
`ReportesController::exportarExcel()` es el único export del sistema y
solo saca conteos agregados (Sospechoso/Probable/Confirmado/Descartado
por agrupación) — no exporta ningún campo individual, sensible o no.
El aviso "Dato sensible: no aparece en exportaciones" que trae "Etnia /
raza" (`datos-paciente-nucleo.php:213`) es cierto, pero trivialmente:
ningún campo aparece ahí, así que no es una exclusión deliberada de
este dato en particular.

**La salida real, `ver.php`, no filtra por `sensible` en absoluto —
verificado con un caso real.** Se creó un caso A50 (no es "privada",
ver abajo) con "Lugar del parto" (`sensible = 1`) como ADMIN, y se
renderizó `ver()` con sesión REGISTRADOR: la etiqueta y el valor
resuelto ("Establecimiento de salud") aparecen completos. `ver.php` no
importa `Auth` para nada relacionado a `sensible` — cualquier usuario
autenticado que pueda ver el caso ve el 100% de sus campos, incluidos
los 34 de A50/B04X que el formulario de captura le ocultó. Mismo hueco
para los 9 campos del Anexo 2 de O95 (Grupo étnico, Idioma, Ocupación,
etc.): no están marcados `sensible` (confirmado, los 9 en `0`) — los
oculta al capturar un gate aparte (`$puedeVerEtnia`, ver abajo), pero
`ver.php` tampoco sabe de ese gate.

**Existe una cuarta capa, independiente de `sensible`, que si
funciona para B24/Z21/Y07:** `Caso::esPrivada()` (`CIE10_PRIVADOS =
['B24', 'Z21']` + coincidencia por nombre "Violencia" para Y07) hace
que un REGISTRADOR solo pueda ver/editar **el caso completo** —no
campo por campo— si él mismo lo registró; cualquier otro REGISTRADOR
recibe 403 (`puedeVerCaso()`, `CasosController.php:1983-1995`,
confirmado con el mismo caso de prueba). Esto sí protege esas 3 fichas
de la fuga de `ver.php` frente a un REGISTRADOR ajeno — pero no protege
A50 ni B04X, que no están en `CIE10_PRIVADOS` a pesar de tener 34
campos `sensible` entre las dos. Y el propio mecanismo está duplicado a
mano: `Caso::listarPaginado()` (línea 92) repite la misma lista de
CIE-10 y el mismo `LIKE "%Violencia%"` como literal SQL aparte, con
un comentario que ya lo advierte ("Debe coincidir con el literal SQL
usado en listarPaginado()") — dos lugares que hay que sincronizar a
mano si se agrega una ficha privada más.

**Desde cuándo:** el gate en sí (columna `sensible`, `$puedeVerSensibles`,
`$puedeVerEtnia`, `CIE10_PRIVADOS`/`esPrivada()`) existe desde
~2026-07-20 (commits `da67f18`/`357c188`), antes de que existiera el
manifiesto unificado — no es un patrón que haya crecido suelto en
muchas sesiones, está concentrado en el arranque del proyecto. B24,
Z21, Y07 y B04X ya traían sus campos sensibles marcados desde entonces
(con una pérdida y restauración de Z21 durante la recarga del
2026-07-23, ver `hallazgo_perdida_silenciosa_recarga.md`); A50 se
sumó el 2026-07-23 como decisión de producto nueva y documentada
(commit `982e666`, `PENDIENTES_POST_FASE5.md`) — la única incorporación
posterior al arranque.

**Decisión de protección de datos pendiente de DIRSAPOL.** No
implementado — solo reportado, a pedido explícito.

**L. Discrepancia del mapeo — página 15 del PDF ("Registro de Búsqueda
Activa de Gestantes VIH") no tiene ficha propia en el manifiesto**

Verificado contra el PDF (`pdftotext`, ya que el renderizado de
páginas no está disponible en este entorno): la página 15 es un
formulario titulado **"FORMULARIO DE REGISTRO DE CASOS DE GESTANTES
CON VIH Y NIÑOS NACIDOS EXPUESTOS AL VIH IDENTIFICADOS POR BÚSQUEDA
ACTIVA INSTITUCIONAL"**, versión 2015.02.25 (misma versión que la
ficha de difteria de las páginas 13-14). Por su forma es una
**planilla de línea a nivel de establecimiento**, no una ficha de caso
individual:
- Cabecera institucional (DISA/DIRESA/GERESA, Red, Microrred, EESS,
  Provincia/Departamento/Distrito, Institución MINSA/EsSalud/FFAA-FFPP/
  Privado/Otro).
- Resumen de totales por servicio de captación (Consultorio externo /
  Emergencia / Hospitalización) y por estado de notificación
  (Notificados / No notificados), con un rango de fechas "Desde—Hasta".
- Una matriz de registro repetible: N.°, Código del paciente, N.° de
  Historia Clínica, Edad + tipo de edad (días/meses/años), Sexo,
  Servicio, Clasificación de caso (Gestante con VIH / Aborto /
  Mortinato / Niño nacido expuesto), Fecha de defunción, Notificado
  (Sí/No), Observaciones.

Está posicionada **inmediatamente antes** de la ficha de Z21 (que
empieza en la página 16 con "SECCIÓN I: GESTANTE CON VIH") — mismo
tema (gestante con VIH / niño expuesto), pero el texto extraído no
trae ninguna etiqueta explícita de "Anexo" que la vincule formalmente
a Z21; es un documento con su propio título, no subordinado
visualmente al de la página 16.

**No implementado ni agregado al manifiesto — a la espera de que el
usuario decida** si el compendio la trae como anexo de Z21 o como
documento suelto fuera de alcance.

**M. Discrepancia del mapeo — A00 (EDA grave / cólera) es una ficha
real de 2 páginas (50-51), el manifiesto solo registra la 50**

Verificado contra el PDF: `manifiesto_fichas.json` declara
`"pdf_paginas": 50` para A00, pero la ficha completa ("FICHA CLÍNICO -
EPIDEMIOLÓGICA", CIE 10: A00-A09) ocupa la página 50 **y** la 51 — no
es un error de conteo, la página 51 trae contenido real que hoy no
está en el manifiesto:

- **N.° de Historia Clínica** (aparece al inicio de "Características
  de la diarrea", página 51) — sin equivalente en el manifiesto.
- **V. LABORATORIO** (página 51, sección completa): Fecha de toma de
  muestra, Fecha de envío al laboratorio, Fecha de recepción en
  laboratorio, y una tabla Establecimiento de Salud / Muestra
  (Heces/Suero/Vómitos) / Examen realizado (Cultivo/Otro) / Resultado
  (Positivo/Negativo), además de "El caso de cólera fue confirmado por
  laboratorio" / "Nexo epidemiológico de un caso confirmado". El
  manifiesto de A00 (sección "Laboratorio") solo tiene **Serogrupo**,
  **Serotipo** y **Otro microorganismo aislado** — los 3 campos que en
  el PDF son el *detalle* del resultado, sin los campos que dan el
  contexto de la muestra (fechas, tipo de muestra, examen, resultado
  positivo/negativo).
- **VI. CLASIFICACIÓN**: el manifiesto ya tiene "Clasificación final"
  con las 5 opciones correctas del PDF (Sospechoso/Probable/
  Confirmado/Compatible/Descartado) — eso SÍ está completo — pero le
  falta la **Fecha** de esa clasificación y el texto libre "[Anotar la
  causa]" para el caso descartado.
- **VII. OBSERVACIONES**: sin campo equivalente en el manifiesto.

**Dato relevante para cuando se decida qué hacer:** A00 ya tiene
`usa_muestras: true` (`tablas_hijas.caso_muestra`) pero **no declara
`columnas_tablas_hija.caso_muestra`** — hoy usa lo que sea que
`muestras.php` pinte por defecto sin configurar. Las fechas y el tipo
de muestra/examen/resultado de la página 51 encajan conceptualmente en
ese mecanismo (`caso_muestra` ya tiene columnas `fecha_toma`,
`fecha_envio_ins`, `tipo_muestra`, `tipo_prueba`, `resultado` en
`COLUMNAS_TABLA_HIJA_VALIDAS`), mientras que Serogrupo/Serotipo/Otro
microorganismo quedaron como `campo_def` sueltos — decisión de diseño
abierta (todo a `caso_muestra` vs. mantener el split actual), no
resuelta acá.

**N. BUG, prioridad alta — ✅ cerrado — un `campo_def` declarado,
cargado y verificado puede ser inalcanzable por un partial compartido
que lo excluye por nombre de sección entera — quinta instancia de la
familia A, C, E e I, la más grave**

Las cuatro anteriores condicionan **qué se pinta dentro de un bloque
que sí se muestra**. Esta volvía **inalcanzable un campo que existe,
está en el manifiesto, está en `campo_def` y que las dos herramientas
de verificación previas del proyecto (`verificar_fichas.php`,
`verificar_claves.php`) reportaban como correcto** — porque ninguna de
las dos renderiza la página, solo comparan manifiesto↔BD.

**Mecanismo:** B05, B26, O95 y P35.0 tienen una sección compartida de
"notificación" (campo_def, generada por el cargador) que se
**duplica** a mano en un partial a medida (`notificacion-fechas-b05.php`,
`-b26.php`, `-o95.php`, `-p350.php`) para darle un layout propio dentro
de la tarjeta "1. Notificación". Para no pintar esos mismos campos dos
veces, `secciones-clinicas.php` excluía la sección **entera** por
nombre (`array_filter` contra `$s['nombre']`) antes de que el render
genérico la viera. El partial a medida resuelve una lista fija de
claves escritas a mano; si alguien agrega un `campo_def` nuevo a esa
misma sección del manifiesto más tarde, caía dentro de la exclusión
mecánica pero **fuera** de la lista a mano del partial — no se pintaba
en ningún lado, sin error, sin aviso.

**Confirmado con una sola instancia real, no dos** — corrección a un
diagnóstico propio equivocado de la misma sesión: se dijo que
`o95_n_de_historia_clinica` tenía el mismo bug que
`p35_0_n_de_historia_clinica`, por inferencia de leer un partial, sin
verificarlo contra el render real. Al implementar el arreglo se
encontró que ese campo de O95 **sí se pintaba**, por un cuarto
mecanismo no visto antes (`nueva/index.php:127-133`, en el shell,
junto al N.° de documento — `name="campo_<id>"` estándar, solo que en
un archivo inesperado). El único caso real era P35.0
(`p35_0_n_de_historia_clinica`, campo_def id 50449, agregado en
`ff892e0`, orden 8 de 8 en su sección — `notificacion-fechas-p350.php`
solo resolvía los 7 originales).

**Arreglo implementado (Ruta 2, commit `f4a0a17`):** exclusión por
CLAVE en vez de por sección entera. Una sección de este grupo solo
desaparece si TODOS sus campos están cubiertos por su partial a medida
(lista explícita en `secciones-clinicas.php`, incluye tanto los campos
con `name="campo_<id>"` estándar como los del sexto mecanismo — ver
N.2); si queda uno sin cubrir, la sección sobrevive con su propia
tarjeta y render genérico. Verificado: P35.0 gana "N.° de historia
clínica" como campo nuevo genuinamente capturable; B05/B26/A36/A80/O95
quedan con el HTML renderizado **byte-idéntico** al de antes (diff
completo, no solo conteo de campos) salvo el token CSRF, que es
aleatorio en cada carga. `verificar_render.php`: 13 → 12 huérfanos
(baja exactamente 1, el de P35.0).

**B05 y B26 usan el mismo mecanismo pero no tenían huérfanos** — se
verificó campo por campo: los 6+7 campos de las dos secciones
excluidas de B05 están cubiertos (el segundo bloque, "Datos de
filiación y tutor", lo resuelve `datos-paciente-b05-loader.php` por
etiqueta, no por clave) y los 7 campos de "Datos de notificación e
investigación del caso" de B26 coinciden 1 a 1 con
`notificacion-fechas-b26.php`. B26 **sí** estaba en la lista de
exclusión de `secciones-clinicas.php` (junto con otras 4 secciones que
tienen partials a medida propios) — no es cierto que se comportara
distinto de P35.0 por estar fuera de esa lista; los 2 "huérfanos" que
`verificar_render.php` reportaba para B26 no eran instancias de este
bug (ver N.2 y N.3 abajo). B05/B26 quedan autoprotegidas: cualquier
campo nuevo que se agregue a una de sus secciones cubiertas sin
actualizar el partial a medida ya no desaparecerá.

**N.2. No es un bug — sexto mecanismo de captura: `name=` literal
remapeado a mano, ajeno a `name="campo_<id>"`**

4 claves no se capturan por el mecanismo estándar y por eso
`verificar_render.php` las reportaba como huérfanas antes de
enseñarle este mecanismo (commit `c5a09b8`): el partial a medida las
pinta con un `name=` literal (`hora_notificacion`, `identificado_por`,
`o95_tipo_ficha`, `b26_lugar_tipo[]`...) y
`CasosController::validarCamposDinamicos()` (líneas ~986-1017) las
remapea a mano por clave, no por `campo_<id>`:
- `o95_hora_de_la_notificacion`, `o95_identificado_por`,
  `o95_tipo_de_ficha` — `notificacion-fechas-o95.php`.
- `b26_contactos_por_lugar` — `lugar-probable-infeccion-b26.php`
  (filas dinámicas "+ Agregar lugar", el mismo tipo de UI que
  necesitaría el ítem P de abajo).

Las 4 funcionan correctamente hoy. `verificar_render.php` ahora las
lista aparte ("capturados por mecanismo no estándar"), no como
huérfanas. Sin acción pendiente — documentado para que la próxima vez
que alguien lea un reporte de huérfanos no las confunda con el bug de
la entrada N.

**N.3. No es un bug de render — campos huérfanos del manifiesto por
diseño superado (basura, no código roto)**

Campos que nunca fueron alcanzables **por diseño**, no por accidente:
reemplazados por otros campos antes de que nadie los borrara.

- **✅ Cerrado (commit `3e64390`):** 5 `campo_def` TEXTO de O95
  (`o95_periodo_intergenesico` y 4 `o95_tiempo_...`) tenían un par de
  reemplazo NUMERO (`_anios`/`_meses` o `_horas`/`_minutos`) que sí se
  renderiza. Confirmado 0 filas en `caso_valor` para los 5 (y 0 casos
  reales de O95 en este entorno) antes de retirarlos del manifiesto —
  limpieza directa, sin dato que migrar. O95: 147 → 142 campos.
- **Abierto, reportado, no implementado:**
  `b26_fecha_de_inicio_de_sintomas` (campo_def id 47738, sección
  "Cuadro clínico") — duplicado conceptual de `caso.fecha_inicio_sintomas`
  (el campo fijo del núcleo, ítem I). El propio código ya lo documenta
  (`cuadro-clinico-b26.php`, comentario junto a `$valFechaInicioSintomas`):
  "el campo_def existe pero ningún input postea a él, nunca fue
  alcanzable". 0 filas en `caso_valor`. Mismo tipo de decisión que los
  5 de O95 (borrar vs. conectar), no incluida en esta sesión porque no
  fue parte de lo pedido — queda para cuando se decida.

**O. BUG, prioridad media — la sección "Cadena de transmisión" nunca
llama al render genérico de `campo_def`, para ninguna ficha que la
use**

Mecanismo distinto al de la entrada N (esa era exclusión por
`array_filter` **antes** del bucle de secciones; esta es un
despacho **dentro** del bucle que nunca delega a `$renderizarCampos`):
`secciones-clinicas.php`, rama `elseif (trim($seccion['nombre']) ===
'Cadena de transmisión')`, solo pinta un texto instructivo fijo y la
tabla hija `caso_contacto` (`tablas-hijas/contactos.php`) — nunca
llama a `$renderizarCampos((int) $seccion['id'])`. Cualquier
`campo_def` real que viva bajo ese nombre de sección es inalcanzable,
para cualquier ficha que lo use.

**Confirmado con A80 (PFA, ficha ya revisada):**
`a80_notas_de_investigacion_de_la_cadena_de_transmision` (TEXTAREA, id
48079) es el único campo de esa sección en A80 — huérfano, detectado
por `verificar_render.php`. No verificado si otra ficha además de A80
usa una sección con este nombre exacto.

No implementado — solo reportado (alcance de diagnóstico, no de
arreglo, en esta sesión).

**P. BUG, prioridad media — `matriz.php` no soporta filas dinámicas
aunque `cargar_fichas.php` ya las anticipa**

Cuando el manifiesto declara `"filas"` como una nota de texto en vez
de un array (p.ej. `"dinámico (una fila por lesión reportada)"`),
`cargar_fichas.php` lo detecta y lo guarda aparte
(`config.filas_nota`, con `config.filas = null`) — no es un bug del
cargador, ya está pensado para esto. Pero `matriz.php:4` solo lee
`$config['filas'] ?? []`, nunca `filas_nota`: con `filas = null` el
`foreach` sobre las filas itera cero veces y la tabla se pinta sin
ninguna fila — cero `<input>`, campo permanentemente vacío, sin forma
de cargar datos aunque el usuario quiera.

**Confirmado con B55 (Leishmaniasis):** `b55_lesiones` (MATRIZ, id
47838, sección "Lesiones cutáneas") es el único caso en las 24 fichas
(verificado contra las 24: es el único `campo_def` MATRIZ con
`filas_nota` no vacío). Necesitaría el mismo tipo de UI dinámica
"+ Agregar fila" que ya tiene `b26_contactos_por_lugar` (ítem N.2) —
no existe hoy un mecanismo MATRIZ genérico para filas repetibles, cada
caso que lo necesitó se resolvió a mano con `name=` literal.

No implementado — solo reportado (alcance de diagnóstico, no de
arreglo, en esta sesión).

**No implementado — a la espera de que el usuario decida el alcance.** Los 10 pares
  `depende_de`/`valor_activador` declarados y verificados con render
  real (oculto sin disparador, visible con el disparador forzado).
- **Fase 5.1**: ✅ cerrada (commit `607882b`). `caso_viaje.semana_gestacion`
  agregada; `viajes.php` generalizado para leer `columnas_tablas_hija`
  (antes no lo hacía, igual que el hallazgo de `columnas_sujeto` en la
  Fase 2) sin afectar a las otras 7 fichas que usan viajes.
- **Fase 5.2**: cerrada como decisión (sin código) — ver C/D arriba.
- **Fase 6**: ✅ reportada, sin implementar (commit `ff892e0` cerró
  aparte el único ítem que sí se implementó: N.° de historia clínica,
  campo 35 de P35.0, copiando el patrón de B05/Y59.0). El resto queda
  en E/F/G/H arriba.

**Q. Entrada J acotada al bloque de domicilio — ✅ cerrado (P35.0)**

Cerrado 2026-08-02. `persona.tipo_zona/tipo_via/nombre_via/numero/
mz_lote/tiempo_residencia` (6 columnas) + `enfermedad.detalle_domicilio`
(opt-in por ficha, mismo mecanismo declarativo que `unidades_edad`:
JSON array de códigos, ausente = comportamiento actual). P35.0 es la
única ficha que lo declara por ahora: `["TIPO_ZONA","TIPO_VIA",
"NOMBRE_VIA","NUMERO","MZ_LOTE","TIEMPO_RESIDENCIA"]`.
`p35_0_tiempo_de_residencia` (campo_def, 0 filas en `caso_valor`) se
retiró del manifiesto — la sección "Datos del paciente (adicionales)"
que solo lo contenía se eliminó entera y las siguientes se renumeraron.

**Corrección previa a esta entrada:** `referencia_localizar` estaba
incorrectamente en `nucleo_omitidos` de P35.0 (colada en la pasada en
bloque de la entrada E, cuando P35.0 aún no estaba cotejada) — su PDF
(pág. 20, ítem 18) sí la pide, texto idéntico al de A37.0. Se sacó de
la lista. Auditoría del mismo error en las otras 5 fichas ya cotejadas,
contra su página real (sin corregir nada, porque no hizo falta):
A36 (pág. 13-14), B26 (pág. 4) y A80 (pág. 34-36) no tienen "Referencia
para localizar" en su PDF — la omiten correctamente. O95 (pág. 30-33)
tiene varias menciones de "REFERENCIA", pero son de referencia
institucional entre EE.SS. (traslado de la paciente), no de ubicación
del domicilio — también la omite correctamente, coincidencia de
palabra, no de concepto. B05 (pág. 37-39) sí la pide y no la omite —
correcto desde que se creó. **Ninguna de las 5 tenía el error de P35.0.**

**Por qué opt-in, no opt-out (asimetría deliberada con `referencia_localizar`):**
contra la página real de las 24 fichas, el bloque completo (Tipo de
zona + Tipo de vía + Nombre de vía + Nro. + Mz./Lote + Tiempo de
residencia, en ese orden y con ese texto) solo aparece **idéntico** en
A37.0 y P35.0. Otras 5 fichas tienen una variante parcial que no
calza sin decisión de mapeo propia — A95 y B57 (formato antiguo:
"NOMBRE DE ZONA"/"INT/DEP/LOTE", tiempo de residencia como años+meses
separado bajo "MIGRACIÓN", un segundo bloque completo para "dónde
vivía antes"), A00 (checkboxes `Zona:[ ]`/`Vía:[ ]`, un "Urbana/Rural"
de 2 valores en otra sección, no 3), O95 (su propio "N°/Interior/
Manzana/Lote" + "Jr./Calle/Avenida/Comité/Sector", sin zona ni tiempo
de residencia) y B04X ("Tiempo de residencia" pero solo para
extranjeros, sin zona/vía/nro). Las 17 restantes no tienen nada de
esto. Con 2/24 confirmadas, opt-out habría pintado los 6 campos sin
base en el PDF en hasta 22 fichas — mismo antipatrón que la entrada N,
aplicado al núcleo en vez de a un partial. `referencia_localizar` sigue
opt-out porque su frecuencia real sí lo justifica: aparece en A37.0,
P35.0, B05, B04X, Mpox (B04X, mismo caso), Tétanos neonatal (A33,
"DOMICILIO CON REFERENCIAS") y Chagas (B57) — la mayoría de las fichas
revisadas la piden, la minoría la omite. Dos campos del mismo bloque
del PDF con dos polaridades opuestas es intencional, no una
inconsistencia a "corregir" después.

`tipo_zona` es ENUM (`URBANO`/`PERIURBANO`/`RURAL`): las 2 fichas que
coinciden exacto dan un código cerrado de 3 valores
(`1=Urbano;2=Periurbano;3=Rural`). El "Urbana/Rural" de A00 es un campo
distinto (2 valores, en "Zona de residencia", otra sección del PDF) —
no es evidencia para forzar ese mapeo. `tipo_via`/`nombre_via`/`numero`/
`mz_lote`/`tiempo_residencia` son texto libre: el PDF mismo insinúa
`tipo_via` como lista abierta ("Avenida, Calle, Jirón, etc.").

A95, B57, A00, O95 y B04X quedan **fuera de este alcance** — cada una
decide su propio mapeo (si acaso) cuando le toque su cotejo, no
reutilizan `detalle_domicilio` tal cual.

**Verificado end-to-end** (`scratch/test_detalle_domicilio_entrada_j.php`,
gitignored; casos borrados al terminar, 0 filas residuales): render de
las 24 confirma los 6 campos presentes en el DOM (para que el fetch de
cambio de ficha pueda mostrarlos sin recargar) pero visibles solo en
P35.0. Caso real crear→editar→actualizar→ver en P35.0: los 6 valores
se guardan, prefilan (`select` de `tipo_zona` incluido) y se muestran
correctamente, y el valor cambia limpio al actualizar. Prueba negativa
en A36 (que no declara `detalle_domicilio`): forzar los 6 campos por
POST resulta en los 6 en `NULL` — `sanearCamposNucleo()` los descarta.
Diff de bytes completo del render de A36 antes/después: única
diferencia son los 6 `<div>` nuevos, los 6 con `hidden style="display:
none;"` — cero cambio visible. Los tres verificadores en verde
(`verificar_fichas.php` 24/24, `verificar_claves.php` 194/194,
`verificar_render.php` sin huérfanos nuevos: P35.0 en `OK`, mismos 3
huérfanos/4 no-estándar preexistentes de los ítems O/N.2/N.3/P, sin
relación con este cambio).

**R. PETICION_HC_Y_LABORATORIO.md, Parte 1 — "N.° de historia clínica" al
núcleo — ✅ cerrado (P35.0, O95, A44)**

Cerrado 2026-08-02. `persona.n_historia_clinica` + `enfermedad.nucleo_incluidos`
(nuevo mecanismo, simétrico de `nucleo_omitidos` con la polaridad
invertida: lista de campos del núcleo OCULTOS por defecto que una ficha
declara para mostrar, en vez de mostrados por defecto y declarados para
ocultar — pensado para reutilizarse en cualquier futuro campo núcleo
opt-in, no solo este). Vive junto al documento de identidad en
`nueva/index.php`/`fichas/editar.php` (no en `datos-paciente-nucleo.php`):
es la ubicación exacta que ya tenía validada el hardcodeo de O95 que
reemplaza.

**El conteo real no dio ~6/24 como se sospechaba — dio 8/24, y no es un
solo concepto.** Verificado contra la página real de las 24: P35.0
(ítem 9), O95 (Anexo 1 y 2) y A44 (encabezado, "N.° HIST.CLÍNICA")
lo piden en el bloque de **identidad**, junto al documento — estas 3 son
las que se promovieron. A37.0 ("H.Cl", ítem 39), B05 ("N.° H.C",
Complicaciones), Y59.0 (Sección VIII Hospitalización), A80 (ítem 5,
"Servicio/N.° Historia clínica/N.° Cama/Ciudad" — **hallazgo nuevo, ficha
ya cotejada, nunca capturado**) y A00 (encabezado de "Características de
la diarrea", cerca de "Evolución del paciente: Hospitalizado" — ya
apuntado por el ítem M) lo piden dentro de su bloque de
**hospitalización** — pregunta distinta del PDF con la misma etiqueta.
**Decisión del usuario:** quedan fuera de este alcance a propósito, sin
fusionar con el campo núcleo de identidad. A37.0/B05/Y59.0 conservan su
`campo_def` propio sin tocar; A80/A00 quedan sin capturar, pendientes de
cuando se coteje cada una.

**Hallazgo adicional durante la implementación:** la petición solo
mencionaba `nueva/index.php:127` como el hardcodeo a eliminar, pero
`fichas/editar.php:90` tenía el mismo hardcodeo idéntico (mismo
`$resolvedorPara('O95')('o95_n_de_historia_clinica')`), sin mencionar —
si solo se corregía `nueva/index.php`, `editar()` habría quedado
inconsistente (mostrando el campo solo para O95, con el mecanismo viejo,
mientras `nuevo()` ya usaba el declarativo). Se corrigieron los dos.

**Nota sobre "un arreglo de partial compartido por sesión":** además de
`nueva/index.php`/`editar.php`, se tocó `secciones-clinicas.php` — pero
solo para borrar una entrada ya muerta de `$CLAVES_CUBIERTAS_POR_PARTIAL_A_MEDIDA['O95']`
(`o95_n_de_historia_clinica`, cuyo `campo_def` este mismo cambio retira
del manifiesto). No es una segunda corrección de lógica compartida, es
limpieza de una referencia que el propio cambio de arriba volvió inerte.
Se anota igual, por transparencia.

Verificado con render de las 24 (visible solo en A44/O95/P35.0, presente
mas oculto en las 21 restantes para que el fetch de cambio de ficha
funcione), caso real crear→editar→actualizar→ver en P35.0, y prueba
negativa en A36 (POST forzado descartado por `sanearCamposNucleo()`).
Diff de bytes completo del render de O95 (antes con el `campo_def` viejo,
después con el mecanismo nuevo): la única diferencia funcional es
`name="campo_<id>"` → `name="n_historia_clinica"` y la clase
`o95-elem` → `data-nucleo-incluido="n_historia_clinica"` — misma
posición, mismo rótulo, mismo placeholder, cero cambio visual. Los tres
verificadores en verde, mismos 3 huérfanos/4 no-estándar preexistentes.

O95 **no se pudo verificar de punta a punta vía `crear()` real** —
bloqueado por el ítem S de abajo, un bug preexistente no relacionado.
Se verificó insertando el caso de prueba directamente (`Persona::crear()`
+ `Caso::crearConCodigo()`) y probando `editar()`/`actualizar()`/`ver()`
reales sobre ese caso, que sí pasan por el código de este cambio.

**S. BUG CRÍTICO, no relacionado — la creación de casos O95 está rota por
un ENUM incompatible entre `caso.clasificacion` y las opciones propias
de O95**

Encontrado 2026-08-02 verificando el ítem R de arriba, sin relación con
"N.° de historia clínica". `opcionesClasificacionPara()`
(`app/Core/ayudantes.php:153-157`) devuelve para O95
`['DIRECTA','INDIRECTA','INCIDENTAL','POR_DETERMINAR']` — y
`clasificacion-chips.php` efectivamente pinta esos 4 valores como el
`value=` real de los chips que ve y envía el usuario. Pero
`caso.clasificacion` es `ENUM('SOSPECHOSO','PROBABLE','CONFIRMADO','DESCARTADO')`
— ninguno de los 4 valores de O95 es miembro de ese ENUM.

**Consecuencia: guardar un caso O95 con cualquier clasificación
seleccionada en la UI real falla siempre**, con
`SQLSTATE[01000]: Warning: 1265 Data truncated for column 'clasificacion'`
capturado por el `catch (Throwable $e)` genérico de `crear()`
(línea ~361), que lo convierte en un flash inofensivo ("No se pudo
registrar la ficha por un error interno. Intenta nuevamente.") sin
pista de la causa real. `actualizar()` tiene el mismo `in_array()` contra
`opcionesClasificacionPara()` (línea ~809), así que un intento de cambiar
la clasificación de un O95 ya creado (si alguno existiera) fallaría
igual. Confirmado forzando `clasificacion=DIRECTA` por POST directo al
controlador: mismo error, reproducible al 100%.

**Introducido en `3b98182` (29 jul, "Parotiditis 100%")**, cuando se le
dieron a O95 sus 4 valores propios de clasificación sin ampliar (ni
separar) la columna que los guarda. 0 casos O95 existen hoy en esta base
(no hay pérdida de datos), pero cualquier intento real de registrar una
muerte materna en producción está bloqueado desde esa fecha.

No implementado — es una decisión de diseño, no un cambio mecánico: ¿se
amplía `caso.clasificacion` a un ENUM superconjunto (mismo patrón que
`caso.edad_unidad`), o `O95` necesita su propia columna de clasificación
separada (mismo criterio que llevó a poner `edad_valor`/`edad_unidad` en
`caso` y no en `persona`)? Prioridad alta — bloquea el uso real de una
ficha que se dio por cotejada al 100%.

**T. PETICION_HC_Y_LABORATORIO.md, Parte 2, Fase D1 "bloque declarativo"
(puntos 1-4) — ✅ cerrado**

`columnas_tablas_hija.<tabla>` acepta ahora, además de la lista plana de
siempre, una forma objeto `{"columnas", "opciones", "texto_libre"}` —
"opciones"/"texto_libre" implementados solo para `caso_muestra` (ver
`cargar_fichas.php::validarManifiesto()`, que rechaza declararlos en
cualquier otra tabla, y rechaza que una misma columna esté en ambos a la
vez). La forma plana sigue aceptándose tal cual — ninguna ficha que ya
declaraba una lista necesitó tocar ese formato para seguir funcionando.

**1. Murió la const `OPCIONES_MUESTRA_POR_ENFERMEDAD`** (PHP,
`CasosController.php`). Su contenido — opciones de tipo_muestra/tipo_prueba
por ficha — se migró a `columnas_tablas_hija.caso_muestra.opciones` en el
manifiesto (`scratch/migrar_opciones_muestra_manifiesto.php`, gitignored).
13 fichas usan `tablas_hijas.caso_muestra` hoy; 12 tenían entrada en la
const (todas salvo B24, que seguía sin filtro y se deja intacta — sigue
mostrando el catálogo completo de tipo_muestra/tipo_prueba, cero cambio).
De esas 12:
- **P35.0 corregido** contra la página 20 del PDF, por instrucción
  explícita del usuario: `tipo_muestra` pasó de
  `["HNF_FAR","SEROLOGIA","SUERO","ORINA"]` (mal, nunca cotejado) a
  `["SEROLOGIA","HNF_FAR"]` ("1.ª/2.ª muestra serológica" son la misma
  opción de catálogo repetida en dos filas, no dos valores distintos —
  confirmado contra `PETICION_P35_RUBEOLA_CONGENITA.md` 5.2). `tipo_prueba`
  se preservó tal cual (`PCR`/`ELISA`) — no fue señalado como incorrecto y
  P35.0 no declara "columnas" propias todavía (Fase D3, sigue pendiente:
  su Laboratorio real necesita IgM/IgG/genotipo/titulación, no
  tipo_prueba/resultado genéricos).
- **B04X**: no usa `caso_muestra` en absoluto (no está entre las 13) — su
  entrada en la const ya era código muerto sin ningún efecto. Se eliminó
  sin migrar nada.
- **B05 y A80**: se preservaron completos (tipo_muestra Y tipo_prueba,
  incluida la mitad que hoy no se pinta — tipo_prueba en A80, ambas en
  B05, cuyo `<select>` de tipo_muestra sigue hardcodeado en
  `muestras.php` dentro de `if ($esB05)`) porque
  `CasosController::filasMuestras()` usa
  `$validosTipoMuestra`/`$validosTipoPrueba` para validar el POST
  **independientemente de qué se haya pintado** — soltarlas habría
  aflojado esa validación (más valores aceptados por POST forjado que los
  que el formulario real ofrece). Convertidas de lista plana a objeto,
  con "columnas" preservando exactamente lo que ya tenían.

**2 y 4. `resultado_igm`/`resultado_igg`/`resultado_pcr` (restricción de
opciones) y `genotipo` (texto libre u opciones restringidas)**: mismo
mecanismo "opciones"/"texto_libre" de arriba, ya implementado en
`muestras.php` (closures `$opcionesSeroPara()`/`$genotipoLibre`). Ninguna
ficha lo declara todavía — queda listo para cuando se declare por ficha
(Fase D3), sin efecto visual hoy (confirmado con diff de bytes, ver
abajo).

**3. Columna `titulacion` en `caso_muestra`**: migración simple
(`sql/migraciones/add_titulacion_caso_muestra.php`, `varchar(60)` libre —
el PDF pide un valor de dilución tipo "1:80", no una lista cerrada),
agregada a `COLUMNAS_TABLA_HIJA_VALIDAS`, al render de `muestras.php`
(mismo bloque `b05-serologia-group` que IgM/IgG), al POST de
`filasMuestras()` y al INSERT de `CasoMuestra::reemplazarTodos()`.
Verificado el round-trip a nivel de modelo (guardar y releer "1:80"
dentro de una transacción con rollback). Tampoco la declara ninguna ficha
todavía.

**Hallazgo colateral, no corregido:** `ver.php` no muestra la serología de
B05 (IgM/IgG/genotipo/PCR) ni mostrará `titulacion` en cuanto alguna
ficha la declare — mismo hueco que las secciones de anexo y los 21
partials a medida, agrupado en el ítem 1 de este documento (no es un
cuarto síntoma suelto, es el mismo patrón).

**Verificación de cierre:** los tres verificadores en verde (mismos 3
huérfanos preexistentes de siempre, ninguno relacionado a `caso_muestra`).
Render real de las 13 fichas con `usa_muestras` comparando el `<select>`
de tipo_muestra/tipo_prueba contra los valores esperados (11 con opciones
migradas, A80.tipo_prueba confirmado ausente del render, B24 confirmado
sin filtrar) — `scratch/test_opciones_muestra_bloque_declarativo.php`,
gitignored. Prueba negativa: A36 no deja colarse `SEROLOGIA` (válido para
P35.0/A95, no para A36) aunque ya no pase por la const vieja. Diff de
bytes completo del render de "Nueva ficha" para A97 (clave nueva
`columnas_tablas_hija` creada de cero) y B05 (lista→objeto, el caso más
delicado): sin diferencias funcionales — solo token CSRF (uno por
request), IDs de `campo_def` que cambiaron por recargar la misma ficha
dos veces seguidas con `cargar_fichas.php --apply` (churn de
autoincremento, no del contenido), y la indentación del bloque de
genotipo (mismo HTML, mismo orden de 18 opciones).

**Pendiente, explícitamente NO implementado en este commit** (ajustes 4 y
5 de la aprobación del usuario, "capacidad 5 más la migración de B05" y
"capacidad 6", cada una con su propio commit y diff de bytes antes de
seguir): B05 sigue con `$esSuero`/`$esPcrGen` y las clases `.b05-*` en
`ficha.js` — el mecanismo `depende_de_columna` todavía no existe, así que
todavía hay dos formas de resolver lo mismo (B05 hardcodeado, P35.0/otras
futuras declarativas). Tampoco existe `bloques_condicionales` (capacidad
6) ni la columna `contexto` de `caso_muestra`. `numero_muestra` quedó
resuelto por separado — ver ítem U.

**U. `numero_muestra` revivido — ordinal de sueros pareados para P35.0 —
✅ cerrado (rediseñado tras prueba en navegador)**

P35.0 pide "1.ª muestra serológica" / "2.ª muestra serológica" /
"Hisopado nasal y faríngeo" (página 20). `tipo_muestra: [SEROLOGIA,
HNF_FAR]` (2 valores) deja la distinción 1.ª/2.ª sin ningún soporte salvo
el orden de las filas — frágil para sueros pareados, donde el usuario
señaló que la comparación entre ambos sostiene el diagnóstico.

**Primer diseño (revertido):** `numero_muestra` (columna preexistente
desde `708821f`, nunca conectada) como `<select>` manual "1.ª"/"2.ª" por
fila. Funcionaba, pero al probar B05 en el navegador el usuario señaló
que un selector manual es redundante — el propio orden de captura ya
determina cuál es la primera y cuál la segunda muestra del mismo tipo —
y que el papel (imagen adjunta a la conversación) no pide *elegir* un
número: el "1era muestra"/"2da muestra" son encabezados fijos de columna,
no una pregunta.

**Diseño final:** `numero_muestra` se calcula automáticamente en
`CasosController::filasMuestras()`, contando cuántas filas anteriores (en
el mismo POST, después de saltar las vacías) comparten el mismo
`tipo_muestra` — la primera fila de un tipo es la 1, la siguiente del
mismo tipo la 2. Sin `<select>` ni `<input>` visible: se retiró de
`COLUMNAS_TABLA_HIJA_VALIDAS['caso_muestra']` (ya no es una columna
"declarable para pintar", es un valor siempre calculado) y de
`columnas_tablas_hija.caso_muestra.columnas` de P35.0. Efecto colateral
positivo: como ya no se lee de `$_POST`, el hallazgo de la vuelta
anterior (columnas de serología sin gate de presencia en
`filasMuestras()`) deja de aplicarle a `numero_muestra` — no hay nada que
forzar por POST porque el campo no acepta entrada del usuario. **El mismo
hueco sigue abierto para `resultado_igm`/`resultado_igg`/`resultado_pcr`/
`genotipo`/`titulacion`**, sin cambios respecto a la vuelta anterior — no
corregido, buen candidato para cuando se declare el laboratorio real de
P35.0 (Fase D3) o junto con una futura capacidad 6.

**Verificación de cierre:** los tres verificadores en verde. Confirmado
que `muestra_numero_muestra` ya no aparece en ningún HTML (ni el
`<select>` ni el `name` del POST). Prueba directa de `filasMuestras()`
con 3 filas (SEROLOGIA, HNF_FAR, SEROLOGIA) → `numero_muestra` = 1, 1, 2
respectivamente. Caso real `crear()` con las mismas 3 filas → guardado
correcto en BD → limpieza.

**V. Capacidad 5 — condicionalidad por columna dentro de una fila de tabla
hija (`depende_de_columna`) + migración de B05 — ✅ cerrado**

B05 mostraba/ocultaba PCR·Genotipo vs IgM·IgG según Tipo de muestra con
`$esSuero`/`$esPcrGen` hardcodeados en `muestras.php` y las clases
`.b05-pcr-group`/`.b05-serologia-group` atadas a `.b05-select-tipo-muestra`
en `ficha.js` — el mecanismo nacía con un solo usuario y, si P35.0 hubiera
necesitado lo mismo, habría sido una segunda forma de resolver el mismo
problema (justo el patrón que esta sesión viene desarmando).

**Diseño:** `columnas_tablas_hija.caso_muestra.depende_de_columna`, mismo
idioma que `depende_de`/`valor_activador` de `campo_def` pero resuelto
DENTRO de una fila de tabla hija en vez de contra todo el documento — un
disparador (`columna`), un conjunto de valores (`valores_activadores`),
por columna dependiente. Validado en `cargar_fichas.php` (columna
dependiente y disparadora deben ser columnas reales de la tabla, no puede
depender de sí misma, solo implementado para `caso_muestra`). Render:
`muestras.php` computa `$visiblePorDependencia($col)` por fila (ausente =
siempre visible, mismo comportamiento que las 12 fichas que no declaran
nada) y emite `data-depende-columna`/`data-valores-activadores`. Vivo:
`ficha.js`'s `evaluarDependenciasMuestra()` reemplaza a
`actualizarCamposMuestraB05()` — genérico, lee esos data-attrs y el
`<select>` disparador de la misma fila por `name`, sin ninguna mención a
B05 ni a ninguna otra ficha. B05 migrado: sus 7 columnas de serología
declaran `depende_de_columna` (2 reglas: PCR/Genotipo con
`tipo_muestra=[HNF_FAR,ORINA]`, IgM/IgG con `tipo_muestra=[SUERO]`) — se
retiraron `.b05-select-tipo-muestra`/`.b05-pcr-group`/`.b05-serologia-group`
del HTML y la función vieja de `ficha.js`. El branch `if ($esB05)` de los
4 campos base (Tipo de muestra hardcodeado, fechas) **no se tocó**, según
lo acordado.

**Verificación de cierre:** los tres verificadores en verde (mismos 3
huérfanos preexistentes). Diff de bytes de `editar()` sobre un caso B05
real con 2 filas (SUERO con IgM/IgG llenos, HNF_FAR con PCR/Genotipo
llenos) comparando código viejo vs nuevo sobre el mismo caso: única
diferencia funcional las clases `.b05-*` reemplazadas por
`data-depende-columna`/`data-valores-activadores` — el atributo
`style="display:none;"`/`style=""` es idéntico campo por campo entre
ambas versiones (más token CSRF y hora de notificación O95, ruido ya
conocido). `node --check` sin errores de sintaxis en `ficha.js`. **No
verificado en navegador real** (sin herramienta de automatización de
navegador disponible en este entorno) — la verificación cubre que el
HTML inicial se computa igual y que el JS es sintácticamente válido y
espeja un patrón ya probado (`evaluarDependencias()`), pero el toggle en
vivo al cambiar el `<select>` no se probó clic a clic.

**Pendiente, explícitamente NO implementado en este commit:** capacidad
6 (`bloques_condicionales`, para el ítem 43 de P35.0 — seguimiento viral
post-confirmación) ni la declaración de `resultado_igm`/`resultado_igg`/
`genotipo`/`titulacion` para P35.0 (Fase D3 del laboratorio). El hallazgo
del ítem U (columnas de serología sin gate de presencia en
`filasMuestras()`, salvo `numero_muestra`) tampoco se tocó — seguía
siendo buen candidato para resolver junto con esta migración, pero no se
incluyó para no mezclar "migrar visibilidad" con "endurecer validación"
en el mismo commit.

**Adenda (verificación en navegador real, tras el aviso "No verificado en
navegador real" de arriba) — bug real encontrado y corregido:** el
usuario probó B05 en el navegador y reportó que TODOS los campos (PCR,
Genotipo, IgM, IgG) aparecían desplegados desde la carga, sin importar
el tipo de muestra. Una primera ronda de investigación (dump del
`<template>` renderizado por `nuevo()` vía CLI, inspección de BD)
verificó el HTML byte a byte correcto y no encontró la causa — se
propuso caché de navegador como hipótesis, con `asset()` (cache-busting
de `<script src>`) como corrección preventiva (commit `5216a10`). Esa
hipótesis era incorrecta: con Playwright (Chromium real, instalado en
este entorno para la verificación) confirmando primero que
`GET /casos/nuevo?enfermedad_id=<B05>` con recarga completa SÍ renderiza
oculto correctamente, la reproducción real solo apareció simulando el
flujo exacto del usuario: cargar "Nueva ficha" con la enfermedad por
defecto y CAMBIAR el selector a Sarampión/rubéola sin recargar la
página. Eso dispara el fetch a
`CasosController::seccionesClinicas()` (`/casos/nuevo/secciones-clinicas`),
que reemplaza el HTML de la sección clínica — incluyendo
`tablas-hijas/muestras.php` — vía AJAX.

**Causa raíz:** `seccionesClinicas()` (línea ~405) extraía a mano solo 3
de las 6 claves que devuelve `datosMuestrasCatalogo()`
(`opcionesTipoMuestra`/`opcionesTipoPrueba`/`opcionesResultado`),
dejando sin asignar `opcionesMuestraExtra`, `textoLibreMuestra` y
`dependeDeColumnaMuestra` — que dentro de `muestras.php` caen a su
default `?? []`. Con `$dependeDeColumnaMuestra = []`,
`$attrsDependencia()` no emite ningún `data-depende-columna` y
`$visiblePorDependencia()` devuelve `true` siempre: los 7 campos de
serología se renderizan visibles y sin atributo, así que ni el HTML
inicial ni `ficha.js` (que depende de ese atributo para reaccionar a
cambios) pueden ocultarlos — coincide exactamente con lo reportado.
Los otros dos flujos completos (`nuevo()`/`crear()`/`editar()`/
`actualizar()`) no tienen este bug porque pasan
`datosMuestrasCatalogo()` completo vía `array_merge()` +
`View::render()`'s `extract()`; solo `seccionesClinicas()` cherry-picking
manual a variables locales para un `require` (no `extract()`) se quedó
corto cuando `datosMuestrasCatalogo()` creció de 3 a 6 claves en la
Fase D1.

**Corregido:** las 3 líneas que faltaban agregadas en
`seccionesClinicas()`. Reproducido el bug con Playwright contra
`git stash` (antes del fix): confirmado `computedDisplay: "flex"` y
`hasDependeAttr: false` en los 7 campos tras el flujo real (cambiar
enfermedad → agregar muestra). Con el fix aplicado, mismo script:
`computedDisplay: "none"`, `hasDependeAttr: true` en los 7 — igual al
flujo de recarga completa. La corrección preventiva de cache-busting
(`asset()`) se mantiene: es una mejora real aunque no haya sido la causa
de este bug.

**W. Laboratorio real de P35.0 (PETICION_P35_RUBEOLA_CONGENITA.md Fase
5.2, ítem 42) — ✅ cerrado**

`caso_muestra: true` estaba declarado para P35.0 sin
`columnas_tablas_hija` propio, así que caía a las columnas por defecto
(`tipo_muestra`/`tipo_prueba`/`resultado`/`fecha_toma`/`fecha_result`) —
ninguna de las cuatro últimas corresponde a lo que pide el PDF (pág. 20):
IgM/IgG/Titulación para la muestra serológica, Resultado/Genotipo para
el hisopado nasal y faríngeo.

**Ruta elegida (de las dos que pedía el punto 5.2 de la petición): (a)**
— las columnas ya existían (mismas que usa B05 desde la migración de
capacidad 5), así que no hizo falta ninguna migración nueva, solo
declarar `columnas_tablas_hija.caso_muestra` para P35.0: `tipo_muestra`,
`fecha_toma`, `resultado_igm`/`fecha_result_igm`,
`resultado_igg`/`fecha_result_igg`, `titulacion`, `resultado_pcr`/
`fecha_result_pcr`, `genotipo` — se retiran `tipo_prueba` y `resultado`
(genéricos, no están en el PDF de P35.0). `depende_de_columna`: IgM/IgG/
Titulación visibles solo si `tipo_muestra=SEROLOGIA`; PCR/Genotipo solo
si `tipo_muestra=HNF_FAR` — mismo mecanismo que B05 (capacidad 5), sin
tocar código. `genotipo` declarado `texto_libre` (catálogo de sarampión
no aplica a rubéola).

**Dos preguntas abiertas, resueltas por decisión explícita del
usuario** ("aplica tus recomendaciones en las 3 interrogantes"):
1. **"Fecha de resultado"** — el PDF dibuja una sola casilla por fila;
   se mantienen las fechas separadas por marcador
   (`fecha_result_igm`/`fecha_result_igg`/`fecha_result_pcr`, heredadas
   de B05) en vez de forzar una `fecha_result` genérica compartida —
   nunca pierde precisión frente al papel, solo la supera.
2. **Vocabulario de Resultado IgM/IgG/PCR** — se mantienen las 4
   opciones (Pendiente/Positivo/Negativo/Indeterminado) en vez de
   restringir a las 2 que dibuja el PDF (−/+); el `<select>` ya empieza
   vacío = "no seleccionado", así que "Pendiente" no fuerza nada e
   "Indeterminado" es un resultado real que el papel no contempla.
3. **Etiqueta "Fecha de obtención"** — hallazgo aparte: la etiqueta de
   `fecha_toma` tenía un caso especial hardcodeado solo para A80
   (`$esPfa`, "Fecha de obtención" en vez de "Fecha de toma"). Se
   generalizó a un `$esFechaObtencion = in_array($cie10Actual, ['A80',
   'P35.0'], true)` separado — "Fecha resultado Fiocruz" (la otra mitad
   de `$esPfa`) sigue siendo específico de A80, el PDF de P35.0 no
   menciona Fiocruz, así que no se tocó esa segunda etiqueta.

**Verificación de cierre:** los tres verificadores en verde. Dump del
`<template>` de P35.0 vía CLI: las 8 columnas de laboratorio con
`data-depende-columna`/`style="display:none;"` correctos, `tipo_muestra`
limitado a SEROLOGIA/HNF_FAR, `genotipo` renderizado como `<input>`
(no `<select>`), `tipo_prueba`/`resultado` genéricos ausentes, etiqueta
"Fecha de obtención" confirmada. Playwright contra el navegador real
(mismo caso B05 de arriba): fila vacía → las 8 columnas ocultas;
`tipo_muestra=SEROLOGIA` → IgM/IgG/Titulación visibles, PCR/Genotipo
ocultos; `tipo_muestra=HNF_FAR` → al revés — el toggle en vivo funciona
igual que B05, cero errores de consola. Caso real `crear()` con una fila
SEROLOGIA (IgM/IgG/Titulación llenos) y una HNF_FAR (PCR/Genotipo
llenos) → guardado correcto en BD (`numero_muestra=1` en ambas, cada
columna en su fila, `null` en la fila que no aplica) → `editar()`
reabre ambas filas con la visibilidad correcta por fila → limpieza.
Negativo: A36 (no declara estas columnas) sigue sin mostrar ningún
`name="muestra_resultado_igm/igg/pcr/genotipo/titulacion"` en su HTML.

**No corregido, ya documentado como deuda preexistente (ítem U):** el
gap de que `filasMuestras()` no bloquea la PRESENCIA de estas columnas
para una ficha que no las declara (solo su validez de valor) — con
P35.0 declarándolas ahora, hay dos fichas expuestas a este gap en vez
de una, pero es el mismo hallazgo ya anotado, no uno nuevo introducido
por este commit.

**Pendiente:** capacidad 6 (`bloques_condicionales`, ítem 43 — el
segundo bloque de muestras de seguimiento viral, solo en casos
confirmados de SRC) sigue sin implementarse — siguiente paso acordado
con el usuario.

**X. "Antecedentes del paciente" (P35.0, en revisión activa, aún no
cerrada) — motor genérico pisaba el manifiesto — ✅ cerrado**

El usuario, cotejando P35.0 contra el PDF, encontró 3 síntomas de la
misma causa: el motor genérico de `secciones-clinicas.php` (compartido
por las 24 fichas) tiene reglas hardcodeadas que no contemplaban a
P35.0: (1) inyecta "Fecha de inicio de síntomas" obligatoria al inicio
de la primera sección clínica de toda ficha salvo una lista de
exclusión (`A80`/`B05`/`O95`) — P35.0 no estaba, la heredaba sin que
exista en el PDF de SRC; (2) agrupa todo campo `BOOLEANO` suelto (que no
sea disparador de otro campo) bajo un encabezado fijo "Signos y
síntomas" al final de la sección — pensado para checklists de síntomas,
pero P35.0 no tiene ninguno: sus booleanos (`p35_0_nacio_prematuro` en
"Antecedentes del paciente", 2 más en "Antecedentes de la madre", 2 más
en "Hospitalización y defunción") son hechos puntuales que quedaban
desterrados fuera de su `orden` real del manifiesto.

**Corregido, acotado por `cie10 === 'P35.0'` en ambos archivos:**
- `secciones-clinicas.php`: agregado a la lista de exclusión de "Fecha
  de inicio de síntomas" (mismo patrón que A80/B05/O95); los `BOOLEANO`
  de P35.0 ya no se separan a "Signos y síntomas", quedan inline en su
  `orden`.
- `CasosController::crear()`/`actualizar()`: la validación de servidor
  (que hacía obligatoria la fecha vía un mecanismo de respaldo -- "toma
  la primera fecha de cualquier campo_def que tenga valor" -- si el
  formulario no la mostraba) ahora se salta entera para P35.0:
  `caso.fecha_inicio_sintomas` queda `NULL` (columna ya nullable), sin
  inventar un valor de respaldo ni bloquear el guardado.

**Verificación:** los tres verificadores en verde. Byte-diff de A80,
B05, O95, A36 y B26 (`render_ficha_cli.php`, HEAD vs. working tree):
0 líneas de diferencia en las 5 -- cero efecto colateral. Caso real
P35.0 `crear()` con `nacio_prematuro=1` y sin fecha de inicio de
síntomas: guardado sin error, `fecha_inicio_sintomas` NULL en BD,
`nacio_prematuro` guardado correctamente en `caso_valor` → limpieza.

**Y. Capacidad 6 — bloques condicionales de tabla hija
(`bloques_condicionales`) + ítem 43 de P35.0 (seguimiento de excreción
viral) — ✅ cerrado**

Diseño reportado y aprobado antes de implementar (Fase D1 de
PETICION_HC_Y_LABORATORIO.md pedía "reporta y espera mi visto bueno"
para esta capacidad). Investigar el motor existente cambió el diseño:
`secciones-clinicas.php` ya tenía un mecanismo de "sección condicional"
(`.dep-wrap`/`data-depende-de`/`data-valor-activador`, CIERRE_RECARGA_Y_FASE5.md
Parte 1.5) reutilizado tal cual para secciones y, ahora, para bloques
de tabla hija -- el único límite real era que `campoVisiblePorDependencia()`
solo resolvía disparadores `campo_def` (`CampoDef::buscar($id)`), no el
núcleo (`clasificacion`). El lado JS (`leerValorCampoPorNombre()`) ya
era genérico y no necesitó ningún cambio.

**Diseño:** `bloques_condicionales` (lista, a nivel de ficha, sibling
de `columnas_tablas_hija`): cada bloque declara `tabla` (hoy solo
`caso_muestra`), `contexto` (string, distinto de `"inicial"`, distingue
sus filas de las del bloque base en la MISMA tabla), `titulo`,
`columnas` (subconjunto de `COLUMNAS_TABLA_HIJA_VALIDAS[tabla]`),
`depende_de` (hoy solo el literal `"clasificacion"` -- el núcleo, no un
`campo_def` con id numérico: no se construyó un motor de reglas
general, solo este único caso de uso) y `valores_activadores`. Columna
nueva `caso_muestra.contexto` (migración) separa las filas de cada
bloque dentro de la misma tabla sin mezclar su `numero_muestra`.

**Piezas nuevas:**
- `cargar_fichas.php`: valida `bloques_condicionales` (forma, columnas
  válidas, `depende_de === "clasificacion"`), persiste en
  `enfermedad.bloques_condicionales` (columna JSON nueva, mismo patrón
  que `columnas_muestra`).
- `CasosController::resolverBloquesCondicionales()` +
  `datosColumnasTablaHija()['bloquesCondicionalesMuestra']`, enhebrado
  en los 4 flujos completos (`nuevo`/`crear`/`editar`/`actualizar`) vía
  `array_merge` + `extract()` de `View::render()` -- y en
  `seccionesClinicas()` extrayendo la clave explícitamente (con la
  lección del hallazgo de B05 fresca: cherry-picking a mano es donde se
  cuelan estos bugs).
- `filasMuestras()`: además del bloque inicial, parsea POST por cada
  bloque declarado (`muestra_<contexto>_<columna>[]`), etiqueta cada
  fila con su `contexto` y las combina en el mismo array que
  `CasoMuestra::reemplazarTodos()` guarda de un solo golpe (una tabla,
  un reemplazo). `separarFilasMuestrasPorContexto()` las vuelve a
  separar para re-renderizar (`crear()`/`actualizar()` en error,
  `editar()` desde `CasoMuestra::porCaso()`).
- `app/Views/partials/tablas-hijas/muestras-condicional.php` (nuevo):
  widget genérico de filas dinámicas para un bloque -- mismo patrón de
  `<template>`/`data-lista` que `muestras.php`, columnas limitadas a
  las 3 que declara el ítem 43 (fecha de obtención, fecha de resultado,
  resultado). Envuelto en `.dep-wrap.bloque-condicional-muestra` con
  `data-depende-de="clasificacion"`.
- `nueva/index.php`/`fichas/editar.php`: recorren
  `$bloquesCondicionalesMuestra` justo después de "Laboratorio",
  numerando cada tarjeta igual que las demás secciones (estructural,
  no por visibilidad en vivo).
- `ficha.js`: al cambiar de enfermedad por AJAX, quita las tarjetas
  `.bloque-condicional-muestra` previas e inserta las nuevas como
  siblings planos de `labCard` (no un wrapper) -- necesario para que
  `renumerarSeccionesSiguientes()` (ya genérico) las cuente igual que
  cualquier otra `.section`. `evaluarDependencias()` no necesitó
  ningún cambio: ya recorre `.dep-wrap[data-depende-de]` en todo el
  documento.

**Verificación:** los tres verificadores en verde. Byte-diff de A80,
B05, O95, A36 y B26: 0 líneas de diferencia (dos rondas -- la primera
encontró un comentario HTML que sí imprimía cuando el bloque no
aplicaba, corregido a comentario PHP dentro del mismo bloque
`<?php ?>`). Validación negativa de `cargar_fichas.php` confirmada
(`depende_de` inválido rechazado con el mensaje esperado). Playwright
en navegador real: bloque oculto con clasificación por defecto
(SOSPECHOSO), visible al elegir CONFIRMADO, oculto de nuevo al volver a
SOSPECHOSO, "Agregar muestra" funcional, cero errores de consola. Caso
real P35.0 `crear()` con fila inicial (SEROLOGIA) + 2 filas de
seguimiento: guardado correcto con `contexto` separado en BD
(`NULL`/`"seguimiento"`), `editar()` reabre ambos bloques con sus
propias filas sin mezclarlos → limpieza. `seccionesClinicas()`
verificado para P35.0 (`htmlBloquesMuestra` con 1 bloque) y A80/B05
(0 bloques, sin excepción).

**Pendiente:** ítem 43 es el último bloque explícito del laboratorio de
P35.0 -- con esto y la Fase D3 (ítem W), el laboratorio de P35.0 queda
completo. Quedan las preguntas de la Fase 6 de
PETICION_P35_RUBEOLA_CONGENITA.md (edad en meses/días, N.° de historia
clínica, tiempo de residencia, pueblo étnico vs. etnia/raza,
`o95_establecimiento_sanidad_pnp`) sin revisar en esta sesión.

**Z. CSS del control "pill" (Sí/No/Ign./Desc.) dependía en silencio de
qué otros campos hubiera en la misma página — ✅ cerrado**

El usuario, mientras esperaba la investigación de la Fase 6, mandó
capturas de P35.0 mostrando radios nativos sin estilo (sin el fondo
gris redondeado tipo interruptor que sí se ve en B05/A36/A80). Causa
raíz: el CSS de `.seg`/`.seg-label`/`.sr-only` (el que oculta el
`<input type=radio>` real y pinta la píldora) nunca vivió en una hoja
de estilos global -- estaba embebido en un `<style>` dentro de
`grupo-si-no.php` y otra copia casi idéntica dentro de
`si-no-fecha.php`. Cualquier otro partial que reusa esas mismas clases
(`select.php` en su rama de 3 opciones Sí/No/Desc., `matriz.php` en modo
radio, `cronologia.php`, y el partial a medida `demoras-o95.php`)
dependía de que, en algún otro lugar de esa misma página, se dibujara
al menos un campo `GRUPO_SI_NO` o `SI_NO_FECHA` que sí trajera el
`<style>` consigo -- si no, el radio nativo quedaba visible sin pintar.

P35.0 no tiene ningún campo `GRUPO_SI_NO`/`SI_NO_FECHA` (solo `SELECT` y
`MATRIZ`), así que el CSS nunca llegaba a su página. **No es exclusivo
de P35.0:** se verificaron las 5 fichas "ya revisadas" y **O95 tiene el
mismo bug** en "Las cuatro demoras" (`demoras-o95.php`), por la misma
razón -- confirmado con el usuario antes de tocar nada, ya que O95 está
en la lista de fichas ya revisadas.

**Corregido:** nuevo `public/css/campos-dinamicos.css` (cargado siempre
desde `app/Views/layouts/shell.php`, junto a `theme.css`/`dark.css`) con
las reglas antes embebidas, ahora sin exigir la clase ancestro
`.grupo-si-no-field`/`.si-no-fecha-field` para las reglas base
(`.sr-only`, `.seg-label`, `.seg-label.on`, variante oscura) -- así
aplican también a `matriz.php`/`select.php`/`demoras-o95.php` sin
importar qué otro campo haya en la página. Las reglas de layout
responsive (`@media max-width:639px`) se dejaron igual de acotadas que
antes (no se les cambió el alcance). Se quitaron los dos `<style>`
embebidos de `grupo-si-no.php`/`si-no-fecha.php` (duplicados, ya
redundantes).

**Verificación:** los tres verificadores en verde. Byte-diff de A80,
B05, O95, A36, B26 y P35.0 (`render_ficha_cli.php`): la única diferencia
es la línea `<link>` nueva en `<head>` y la desaparición de los dos
`<style>` embebidos donde aplicaban -- cero cambios de contenido/campos.
Playwright contra el navegador real: P35.0 (60 usos de `.seg-label`) y
"Las cuatro demoras" de O95 (8 usos) ahora resuelven `border-radius:6px`
y ocultan el radio nativo (antes: sin la regla, radio visible); captura
de pantalla de ambas confirma el estilo píldora correcto; B05 (72 usos,
ya funcionaba) sigue igual, sin regresión; cero errores de consola en
los tres casos.

**Z.2. Tabla de viajes de la madre (ítem 33, P35.0) + B05 tenía la suya
completamente rota — ✅ cerrado**

El usuario pidió que, al elegir "Sí" en "¿Durante el embarazo viajó
fuera del país?" (P35.0), aparezca un formulario de viaje con las
columnas del PDF: País, Localidad/ciudad, Fecha de salida, Fecha de
retorno, Semana de gestación. Antes de construirlo se investigó el
mecanismo existente de `caso_viaje`/`viajes.php` (8 fichas lo usan), lo
que destapó un hallazgo más serio, no relacionado al pedido:

**B05 no mostraba su tabla de viajes en absoluto, ni oculta ni vacía.**
`secciones-clinicas.php` (bloque especial que dibuja la tabla dentro de
"Lugar probable de infección" cuando el booleano vale Sí) comparaba
contra la clave literal `'paciente_viajo_7_30_dias'`, que ya no existe
-- la real es `b05_paciente_viajo_entre_los_7_a_30_dias_antes_del_inic`
(prefijo `b05_` de "clave ahora autoritativa" o de la recarga de
fichas). Como la condición nunca era verdadera, el `require` de
`viajes.php` nunca corría: no era un problema de visibilidad, la tabla
simplemente no existía en el HTML. El mismo patrón se repetía en
`public/js/ficha.js` (`actualizarBloqueViajesB05()`, vía
`campoPorClave('paciente_viajo_7_30_dias')` sobre `mapaCampos`) --
doblemente roto, en PHP y en JS. Confirmado con el usuario antes de
tocar nada (B05 es una ficha ya revisada) que se arreglara primero esto
y se usara ya funcionando como base para P35.0.

**Corregido:**
- `secciones-clinicas.php`: clave corregida a la real; se agregó un
  bloque análogo para P35.0 (`p35_0_durante_el_embarazo_viajo_fuera_del_pais`,
  BOOLEANO valor `'1'`/`'0'`, no catálogo SI/NO/DESCONOCIDO como B05).
- `ficha.js`: misma clave corregida en `actualizarBloqueViajesB05()`
  (dos ocurrencias); nueva `actualizarBloqueViajesP350()` en espejo,
  con el mismo comportamiento (auto-agrega una fila vacía la primera vez
  que se elige "Sí", coherente con lo que pidió el usuario: "debería
  aparecer un formulario para registrar").
- **Segundo hallazgo en cascada:** al corregir la clave de B05, el
  `require` de `viajes.php` sí empezó a ejecutarse dentro de
  `$renderizarCampos` (closure de `secciones-clinicas.php`) -- y
  `$filasViajes` no estaba en su `use(...)`, nunca se había ejercitado
  porque la clave rota lo hacía inalcanzable. Corregido agregando
  `$filasViajes`/`$erroresViajes`/`$columnasViaje` al `use(...)` de la
  closure.
- **Tercer hallazgo:** el endpoint AJAX `seccionesClinicas()` (mismo
  patrón que [[seccionesclinicas_extrae_claves_a_mano]]) no definía esas
  3 variables antes de su propio `require` de `secciones-clinicas.php`
  -- hoy no fallaba porque nunca llegaba a usarlas (clave rota), pero al
  corregir la clave habría roto el JSON de CUALQUIER ficha al cambiar de
  enfermedad por el combo (warning de "Undefined variable" filtrándose
  al body de la respuesta). Corregido: se adelantó la resolución de
  `datosColumnasTablaHija()` (ya se llamaba más abajo para muestras, se
  reutiliza el mismo resultado) antes del primer `require`.
- **Columna nueva `caso_viaje.localidad`** (migración
  `add_localidad_caso_viaje.php`): el PDF de P35.0 pide País y
  Localidad/ciudad como columnas separadas; antes se colapsaban en un
  solo campo de texto libre ("Lugar visitado (país o ciudad)"). Gateada
  por `columnas_tablas_hija.caso_viaje` igual que `semana_gestacion`
  (Fase 5.1) -- las otras 7 fichas que usan viajes no la declaran, sin
  cambio para ellas. `viajes.php`/`filasViajes()`/`CasoViaje::reemplazarTodos()`
  actualizados; `cargar_fichas.php` y el manifiesto de P35.0 actualizados
  y recargados (`--apply --cie10=P35.0`).
- P35.0 se excluyó de la tarjeta genérica "Antecedentes epidemiológicos"
  (`nueva/index.php`/`fichas/editar.php`, variable `$isP350`, mismo
  trato que `$isB05`) -- ya no muestra la tabla de viajes siempre
  visible ahí; solo aparece condicionada al booleano, junto a la
  pregunta, como pide el PDF.

**Adenda (mismo día, feedback del usuario tras ver la primera captura):**
"tipo de transporte no es requerido según la ficha" -- se acotaron
`transporte_ida`/`transporte_retorno` en `viajes.php` con el mismo
mecanismo `$mostrarColViaje()`, pero en sentido opt-OUT (al revés de
`localidad`/`semana_gestacion`, que son opt-in): se agregaron a
`COLUMNAS_TABLA_HIJA_VALIDAS['caso_viaje']` (cargar_fichas.php) y a
`CasosController::COLUMNAS_HIJA_DEFECTO['viaje']` (antes
`['pais','fecha_salida','fecha_retorno']`, ahora incluye ambos
transportes) -- así las 7 fichas que NO declaran
`columnas_tablas_hija.caso_viaje` (no tocadas hoy) siguen viéndolas por
el valor por defecto, mientras que P35.0 (que sí declara su propia
lista explícita, sin incluirlas) las excluye automáticamente, sin
necesidad de recargar el manifiesto de nuevo. Verificado: las 6 fichas
restantes que usan viajes (A97, A37.0, A95, B57, A44, B04X) y B05 siguen
mostrando ambos selects de transporte; P35.0 ya no muestra ninguno.
Byte-diff de A36/A80/O95/B26: 0 líneas; B05: solo diferencias de
indentación en comentarios HTML (cosmético, invisible en el navegador).
Round-trip real: B05 sigue guardando transporte_ida/retorno; P35.0 los
guarda `NULL` sin error.

**Verificación:** los tres verificadores en verde. Byte-diff de A80,
O95, A36 y B26 (`render_ficha_cli.php`, normalizando IDs de `campo_def`
que se recorren al recargar el manifiesto, timestamp de notificación y
versión de caché de `ficha.js`): 0 líneas de diferencia real en las 4.
B05: única diferencia es la tabla de viajes apareciendo (antes ausente
del todo). El endpoint AJAX `seccionesClinicas()` probado para las 6
fichas relevantes: JSON válido, sin warnings filtrados. Playwright en
navegador real: toggle de B05 (catálogo SI/NO/DESCONOCIDO) y de P35.0
(booleano 1/0) -- ambos ocultos por defecto, visibles con fila
auto-agregada al elegir "Sí"/"1", ocultos de nuevo al revertir; cero
errores de consola. Caso real P35.0 `crear()`→`verificar-bd`→`editar()`
con un viaje (país, localidad, fechas, semana de gestación): guardado y
recargado correctamente, con "Localidad/ciudad" mostrando su propio
valor separado de "País" → limpieza. Caso real B05 con un viaje
(transporte ida/retorno, sin localidad): guardado correcto, sin la
columna que B05 no declara.

**Z.3. Campos NUMERO se veían como texto libre (sin spinner) en las 24
fichas — ✅ cerrado**

El usuario notó que los campos numéricos de "Antecedentes del paciente"
(P35.0: APGAR, peso al nacer, edad gestacional) se veían como texto
libre, a diferencia de "Edad" (que sí tiene spinner). Investigado:
existen dos widgets de "campo numérico" nunca unificados -- "Edad" es
un campo del núcleo (`datos-paciente-nucleo.php`, `type="number"` desde
siempre) y los NUMERO de cada ficha son `campo_def` renderizados por
`campos/numero.php`, que usaba `type="text" inputmode="decimal"` sin
ninguna restricción de teclado, desde el primer commit del proyecto --
no es un bug de P35.0 ni algo introducido en esta sesión, afecta a las
125 campos NUMERO de 21 fichas por igual.

`ficha.js` ya tenía (líneas ~2500-2523) un bloqueo genérico de teclas
`e/E/./,/+/-` para inputs `type="number"`/`.solo-enteros`, con una clase
`.permite-decimales` para exceptuar los que sí admiten decimales -- ya
usado a mano en varios campos de O95 (`.solo-enteros`), pero
`.permite-decimales` **nunca se había aplicado a ningún campo del
proyecto** hasta ahora.

**Investigación previa a implementar** (los 125 campos NUMERO de las 24
fichas, clasificados por nombre/etiqueta): 108 probablemente enteros
(conteos "N.°"/"Número"/"Cuántos", días de duración, pares años+meses
u horas+minutos -- estos últimos con precedente idéntico ya
hardcodeado en O95), 7-8 probablemente decimales (Temperatura °C ×5,
Peso en kg, Hemoglobina, Hematocrito, Porcentaje de vacunados MRC), 10
dudosos resueltos por convención (edad gestacional/semana de gestación
→ entero, sin campo "días" compañero; frecuencia respiratoria/pulso →
entero, conteos clínicos). Reportado al usuario con el detalle completo
antes de tocar nada; confirmó el enfoque sin pedir ajustes.

**Mecanismo:** se reutiliza `campo_def.config` (columna JSON que hoy
solo usa MATRIZ, sin migración de esquema) -- `"decimales": true` en el
manifiesto es el opt-in explícito para los ~9 campos que lo necesitan;
por defecto (sin declarar nada) es entero. `cargar_fichas.php` valida
que `"decimales"` solo aparezca en campos `tipo: NUMERO` y sea
booleano. `campos/numero.php` decodifica `config` y renderiza
`type="number" step="1" inputmode="numeric" pattern="[0-9]*"
class="solo-enteros"` (entero, default) o `type="number" step="any"
inputmode="decimal" class="permite-decimales"` (decimal, opt-in).

**Hallazgo en cascada al probar de verdad en navegador:** el mecanismo
`.permite-decimales` de `ficha.js`, al nunca haberse ejercitado antes,
tenía un vacío real -- se saltaba el bloqueo de teclas ENTERO, sin
reemplazarlo por nada: un campo con esa clase dejaba pasar la "e" de
notación científica sin filtrar (confirmado escribiendo
"38e5+.-,2" en Temperatura → quedaba "38e52"; el navegador nativo ya
rechaza "+"/","/"-" mal puestos por su cuenta, pero no la notación
científica). Corregido: para campos `.permite-decimales` ahora se
bloquea específicamente `e`/`E` (dejando pasar el punto decimal), en
vez de no bloquear nada.

**Verificación:** los tres verificadores en verde (huérfanos: los 3 ya
conocidos y preexistentes -- A80, B26, B55 -- sin nuevos). Byte-diff de
A80/O95/B26 (`render_ficha_cli.php`, IDs de `campo_def` normalizados
por el recorrido de `cargar_fichas.php`): 0 líneas reales de diferencia
(O95 no usa `numero.php` para sus NUMERO -- ya los captura con
partials a medida propios, con `.solo-enteros` hardcodeado desde antes;
cero efecto). A36 (temperatura, decimal): único cambio real es
`type="text"` → `type="number" ... class="permite-decimales"`.
Playwright en navegador real: campo entero de A80 con teclas
`e1.2,3+4-5` escritas a mano → queda "12345" (todo lo prohibido
bloqueado); Temperatura de A36 con "38.5" → queda "38.5" (decimal
correcto); con "12e" → queda "12" ("e" bloqueada); cero errores de
consola en ambos casos.

**Z.4. "Fecha de manifestación" (matrices SI/NO/DESCONOCIDO de P35.0)
aceptaba fecha futura y quedaba habilitada aunque la fila fuera NO o
DESCONOCIDO — ✅ cerrado**

El usuario observó en "Cuadro clínico — manifestaciones" (Manifestaciones
oftálmicas, Manifestación auditiva, Cardiopatía congénita, Otras
manifestaciones) que la celda "Fecha de manifestación" de cada fila (i)
admitía fechas posteriores a hoy y (ii) se podía llenar aunque la fila
estuviera marcada NO o DESCONOCIDO, sin ninguna relación con el radio
SI/NO/DESCONOCIDO de esa misma fila.

Estos 4 campos son `tipo: MATRIZ` renderizados por `campos/matriz.php`,
en modo `'hibrido'` (3 columnas exclusivas SI/NO/DESCONOCIDO + 1 columna
libre "Fecha de manifestación") — el único modo `'hibrido'` real de las
24 fichas hoy. El partial nunca gateaba las columnas libres a la
columna "SI" de la misma fila (no existía el concepto), y ninguna celda
de fecha de `matriz.php` tenía `max` (a diferencia de todo el resto de
inputs `type="date"` de la app, que sí tienen `max="<?= date('Y-m-d')
?>"` desde siempre).

**Mecanismo (genérico en `matriz.php`, no hardcodeado a P35.0):** si el
campo tiene modo `'hibrido'` y una de sus columnas exclusivas es
literalmente "SI"/"SÍ" (`$colSiIdx`), las columnas libres de la misma
fila llevan `data-gated-por-si="1"` y se renderizan `disabled` salvo que
esa fila ya esté en SI (`$filaEsSi`). Todas las celdas `type="date"` de
`matriz.php` (gateadas o no) llevan además `max="<?= date('Y-m-d') ?>"`.
En `ficha.js`, `actualizarEstadoFila()` (la misma función que ya
maneja `.fecha-dep`/`.otros-especificar-dep` de `grupo-si-no.php`) ahora
también habilita/deshabilita y limpia cualquier `[data-gated-por-si]`
de la fila al cambiar el radio — sin nuevo listener, reutilizando el
`change` ya cableado sobre `.grupo-si-no-field` (matriz.php comparte esa
clase de wrapper).

**Alcance real verificado:** de los 24 fichas, solo P35.0 tiene columnas
MATRIZ exclusivas en mayúsculas + una columna "SI" real (`a37_0`, `b55`,
`z21`, `a50`, `y59_0` tienen columnas "Fecha..." pero en modo `'libre'`,
sin ningún radio que gatear — cero cambio de comportamiento ahí). El
`max` de fecha sí es universal al partial: además de P35.0, toca A80
("Fechas de seguimiento 30/60/90/180 días", filas "Fecha
programada"/"Fecha que se realizó", modo `'libre'`) — consultado al
usuario antes de commitear por ser ficha ya revisada; confirmó aplicar
el mismo criterio que el resto de la app (ninguna otra fecha del sistema
admite futuro). Sin cambio en A36/B26/O95/B05 (ninguna de sus matrices
tiene columna "Fecha" en ningún formato).

**Verificación:** los tres verificadores en verde (mismos 3 huérfanos
preexistentes A80/B26/B55, sin nuevos; 0 claves faltantes). Byte-diff
`render_ficha_cli.php` de A36/B26/O95/B05: 0 líneas de diferencia. A80:
único cambio real son los `max="2026-08-03"` en las celdas de
"Fechas de seguimiento" (confirmado con el usuario). Playwright en
navegador real sobre P35.0 ("Cataratas"): sin radio marcado → fecha
deshabilitada; NO → deshabilitada; SI → habilitada, acepta
`max="2026-08-03"`; DESCONOCIDO → deshabilitada y limpiada a "". A80
("Fecha programada"): las 4 celdas quedan con `max="2026-08-03"` pero
siguen habilitadas (sin columna SI que gatear). Cero errores de consola
en ambos casos.

**Z.5. "Antecedentes epidemiológicos" (Contactos/Lugar) seguía visible
en P35.0 al cambiar de ficha SIN recargar — ✅ cerrado**

El usuario reportó, con captura, que la sección 8 "Antecedentes
epidemiológicos" (Contactos + Lugar probable de infección) aparecía en
P35.0 cuando el PDF no la pide (P35.0 solo necesita "Viajes", ya
cubierto en "Antecedentes de la madre"). Al cotejar, la carga completa
de P35.0 (`/casos/nuevo?enfermedad_id=17` de una) ya la ocultaba bien
-- el bug real aparecía solo al **cambiar el desplegable de enfermedad
sin recargar la página** (el flujo real de "Nueva ficha": se entra con
una enfermedad por defecto y se elige la correcta del desplegable).

Causa: `CasosController::seccionesClinicas()` (endpoint AJAX que arma
esa sección al cambiar de ficha) calculaba `$tieneAntecedentesEpi` con
`!$isPfa && !$isB05` nada más -- nunca se le sumó `!$isB26`/`!$isP350`
cuando esa exclusión se agregó en `nueva/index.php`/`fichas/editar.php`
(ítem Z.2). Mismo antipatrón ya documentado en memoria
(`seccionesclinicas_extrae_claves_a_mano`): una condición compartida
que vive duplicada en dos sitios y se desincroniza en silencio.

Mismo bug técnico afectaba a B26 (ficha ya revisada) pero ahí quedaba
oculto "por casualidad": `actualizarVacunacionB26()` le pone
`style.display='none'` inline al entrar a B26, y ese inline sobrevive
aunque el `hidden` que se recalcula después esté mal -- P35.0 no tiene
ese seguro accidental, por eso ahí sí se veía. Confirmado con el
usuario antes de commitear (afecta a B26) y corregido: se agregó
`!$isB26 && !$isP350` a la fórmula de `$tieneAntecedentesEpi`, igual
que en los otros dos archivos.

**Verificación:** Playwright en navegador real, cambiando el
desplegable sin recargar: A36→P35.0 y A36→B26 dejan la tarjeta
`hidden` de verdad (antes: `hidden` quedaba `null`/visible para
P35.0; para B26 el valor de fondo también estaba mal, solo tapado por
el inline style). A36→A80 y A36→B05 sin cambios (ya estaban
excluidos ahí). Tres verificadores en verde.

**Z.6. Cambiar de ficha por AJAX parcheaba el DOM caso por caso — se
reemplazó por una recarga real de página**

Investigando Z.5, el usuario preguntó por qué el cambio de ficha no
hace "una especie de reset completo, como una carga inicial" -- había
notado que valores de una ficha (p.ej. "Gestante: Sí") podían quedar
pegados visualmente al cambiar a otra que no aplica. La causa de fondo
es estructural: `selectorEnfermedad.addEventListener('change', ...)`
en `ficha.js` (~220 líneas) pedía por `fetch()` el HTML de "Cuadro
clínico" y then parchaba a mano, campo por campo, wrap por wrap,
docenas de casos especiales por ficha (b05-elem/o95-elem/b26-hide,
nucleo_omitidos/incluidos, unidades_edad, detalle_domicilio,
notificacion-fechas-*, antecedentes epidemiológicos, laboratorio...) --
la misma raíz de los dos bugs de Z.5: cada caso especial nuevo exige
tocar el render inicial del servidor Y esta lista a mano, y es fácil
que uno se desactualice.

Se verificó que el selector de enfermedad es el **primer campo** de
"Nueva ficha" (antes de Documento/Datos del paciente) y que "Editar
ficha" ni siquiera permite cambiar de enfermedad (ahí es texto plano) --
así que no hay nada más que perder al descartar el estado del
formulario en un cambio de enfermedad. Confirmado con el usuario:
`selectorEnfermedad.addEventListener('change', ...)` ahora hace
`window.location.href = '/casos/nuevo?enfermedad_id=' + id` (recarga
completa) en vez de parchar el DOM. Reusa el único render que siempre
está correcto -- el del servidor -- y elimina de raíz toda la clase de
bugs "quedó pegado un valor/sección de la ficha anterior", no solo los
dos que ya se habían encontrado.

**Efecto colateral aceptado:** si el usuario ya escribió algo en
Documento/Datos del paciente antes de cambiar de enfermedad, se pierde
con la recarga -- inevitable en la práctica dado el orden del
formulario, y el usuario lo aceptó explícitamente.

**Verificación:** Playwright con navegación real (`waitForNavigation`):
A36→P35.0 cambia la URL a `?enfermedad_id=17`, tag CIE-10 se actualiza,
tarjeta de antecedentes queda `hidden`. P35.0→A36 (caso inverso, el que
antes quedaba con la tarjeta visible pero VACÍA): ahora "Agregar
contacto"/"Agregar lugar" aparecen con contenido real, igual que una
carga directa de A36. Cero errores de consola en ambos sentidos. Tres
verificadores en verde (mismos 3 huérfanos preexistentes).

**Limpieza (mismo día, a pedido del usuario):** `CasosController::seccionesClinicas()`
(156 líneas) y la ruta `/casos/nuevo/secciones-clinicas` -- confirmado
sin llamadores tras Z.6 -- se eliminaron por completo. `php -l` en
ambos archivos, `curl` a la ruta confirma que ya no resuelve al
controlador, tres verificadores en verde.

**Z.7. Ítem 43 (seguimiento de excreción viral, solo casos confirmados
de SRC) "no aparecía en el esquema" — ✅ cerrado, el mecanismo ya
existía**

El usuario cotejó el ítem 43 del PDF (tabla "Seguimiento de excreción
viral", 2 filas de "Hisopado nasal y faríngeo", visible SOLO en casos
confirmados de SRC) contra el formulario y no lo encontró en ningún
lado, esperando verlo debajo de "Laboratorio", condicionado a
"Clasificación del caso = Confirmado" (sección 7 del PDF).

Investigando, el mecanismo YA estaba completamente implementado desde
antes de esta sesión -- capacidad 6 (`bloques_condicionales`,
`app/Views/partials/tablas-hijas/muestras-condicional.php`, backend en
`CasosController::filasMuestras()`/`separarFilasMuestrasPorContexto()`) --
con el bloque de P35.0 ya declarado en el manifiesto (`contexto:
"seguimiento"`, `depende_de: "clasificacion"`, `valores_activadores:
["CONFIRMADO"]`) y cargado en la base de datos. Confirmado con
Playwright que el bloque SÍ aparece si se marca "Confirmado" en los
chips genéricos de abajo del formulario.

**La causa real:** P35.0 tiene DOS campos de clasificación
independientes que se llaman igual ("Clasificación del caso"): (1) el
`campo_def` propio de la ficha (sección 7, opciones Sospechoso/
Confirmado/Descartado/Infección congénita por el virus de la rubéola,
el que pide el PDF) y (2) el "clasificacion" genérico compartido por
todas las fichas (chips al final del formulario, usado en
paneles/reportes/filtros de lista, y del que depende el bloque del
ítem 43). Marcar "Confirmado" en (1) no tocaba (2), así que el bloque
seguía oculto. Mismo problema de fondo que A80 con su propio
"Clasificación final" -- A80 ya lo resuelve con
`sincronizarDescartadoPfa()` (JS que sincroniza su campo propio hacia
el genérico); no existía el equivalente para P35.0.

**Fix (confirmado con el usuario, mismo patrón que A80):**
`sincronizarClasificacionP350()` en `ficha.js` -- al cambiar el campo
propio de P35.0, sincroniza el genérico: Sospechoso/Confirmado/
Descartado se copian tal cual; "Infección congénita" y vacío caen a
"Sospechoso" (decisión explícita del usuario: el PDF dice "casos
CONFIRMADOS de SRC", "Infección congénita" es una categoría distinta y
NO debe activar el seguimiento -- y además evita que el genérico quede
pegado en "Confirmado" si el usuario pasa por Confirmado y luego
cambia de opinión). Se dejaron AMBOS campos visibles e independientes
(igual que A80): no es una limpieza de UI, solo la sincronización que
faltaba.

**Verificación:** Playwright en navegador real -- elegir "Confirmado"
en el campo propio de P35.0 revela el bloque y marca el chip genérico
correspondiente; "Infección congénita" lo mantiene oculto; volver a
"Sospechoso" también. Ronda completa crear()→verificar-bd→editar():
2 filas de seguimiento (`contexto=seguimiento`) guardadas y
recargadas correctamente, con el bloque visible (sin `hidden`) en
`editar()`. Sin regresión en el sync propio de A80. Tres verificadores
en verde, byte-diff limpio en A36/B26/A80/O95/B05.

**Z.7.1. El bloque revelado no tenía "diseño de sección" — ✅ cerrado**

El usuario confirmó con captura que el bloque SÍ aparece, pero sin el
aspecto de tarjeta de las demás secciones (sin fondo blanco, sin
borde, sin sombra -- título y botón "flotando" sueltos contra el fondo
de la página). Causa: `muestras-condicional.php` es el ÚNICO lugar del
código que combina las clases `card section` (la tarjeta visual) con
`dep-wrap` (el motor de dependencias condicionales) en un mismo
elemento -- en todo el resto de la app, `dep-wrap` envuelve un campo o
grupo suelto DENTRO de un `.fields`, nunca una tarjeta entera. La regla
`.dep-wrap{display:contents}` (necesaria para que el wrap no rompa el
layout de ese caso normal) le quita también la caja propia al `.card`
cuando se combinan, dejando solo el contenido interno fluyendo sin
fondo/borde/sombra.

**Fix:** una regla CSS más específica en `theme.css`,
`.dep-wrap.card.section{display:block}`, restaura la caja SOLO para
esta combinación exacta (confirmado por grep: es la única en las 24
fichas). El `hidden` que ya usa `evaluarDependencias()` para
mostrar/ocultar sigue funcionando igual (no depende de `display:contents`
vs `display:block`, solo del atributo `[hidden]`).

**Verificación:** Playwright -- tras marcar "Confirmado", el bloque
mide `display:block`, fondo blanco, `box-shadow` y borde iguales a los
de "Laboratorio"/"Investigador" (capturado en pantalla). Tres
verificadores en verde; sin otro `.dep-wrap.card.section` en las 24
fichas, cero riesgo de efecto colateral en otra parte de la app.

**Z.7.2. Faltaba "Tipo de muestra" en el bloque de seguimiento — ✅ cerrado**

El usuario notó, ya con el bloque visible y con el diseño correcto,
que solo mostraba Fecha de obtención/Fecha de resultado/Resultado --
sin "Tipo de muestra", pese a que el PDF marca ambas filas del ítem 43
como "Hisopado nasal y faríngeo". Causa: el manifiesto de P35.0
declaraba `bloques_condicionales[0].columnas` como
`["fecha_toma","fecha_result","resultado"]`, sin `"tipo_muestra"` --
`muestras-condicional.php` nunca pintaba ese campo porque no estaba en
la lista, y el partial tampoco sabía renderizarlo (a diferencia de
`muestras.php`, el widget del ítem 42, que sí lo tiene desde antes).

**Decisión (confirmada con el usuario):** en vez de una etiqueta fija
de solo lectura (más fiel al PDF pero exige código nuevo), se reusa el
mismo `<select>` que ya usa el ítem 42 para esta ficha (opciones
SEROLOGIA/HNF_FAR de `datosMuestrasCatalogo()`), preseleccionado en
"Hisopado nasal y faríngeo" pero editable -- cero código de opción
nueva, más consistente con el resto del formulario.

**Cambios:** `"tipo_muestra"` agregado a `bloques_condicionales[0].columnas`
en el manifiesto (ya era una columna válida de `caso_muestra` en
`cargar_fichas.php`, sin tocar la constante). `muestras-condicional.php`
gana el mismo bloque `<select>` que `muestras.php` (reusa
`$opcionesTipoMuestra`, ya disponible en el scope de quien lo incluye
-- `datosMuestrasCatalogo()` se mezcla en la vista igual que para el
bloque principal), y el valor por defecto de una fila nueva pasa a
`'tipo_muestra' => 'HNF_FAR'` en vez de vacío. `cargar_fichas.php --apply --cie10=P35.0`.

**Verificación:** Playwright -- al agregar una fila nueva, "Tipo de
muestra" queda preseleccionado en "Hisopado nasal y faríngeo"
(capturado en pantalla, layout igual al resto del formulario). Ronda
crear()→editar(): 2 filas guardadas con `tipo_muestra='HNF_FAR'` y
recargadas con el `<select>` correcto marcado en las 3 filas (2 datos +
1 plantilla vacía). Tres verificadores en verde, byte-diff limpio en
A36/B26/A80/O95/B05 (el cambio queda acotado a la columna nueva del
bloque de P35.0).

**Z.7.3. Regresión real del fix Z.7.1: el bloque quedó SIEMPRE visible,
ignorando el atributo `hidden` — ✅ cerrado**

El usuario reportó (con captura, "Clasificación del caso" en
"Seleccionar…") que "Seguimiento de excreción viral" aparecía desde la
carga de la ficha, dejando agregar muestras sin haber clasificado el
caso -- exactamente lo que el ítem 43 debía impedir. No era una
decisión de UX pendiente: era una regresión real introducida por el fix
de Z.7.1. `.dep-wrap.card.section{display:block}` (3 clases,
especificidad (0,3,0)) le ganaba a `.dep-wrap[hidden]{display:none}`
(1 clase + 1 atributo, especificidad (0,2,0)) -- el bloque quedaba
`display:block` SIEMPRE, sin importar el atributo `hidden` que
`evaluarDependencias()` sí seguía poniendo/quitando correctamente. El
atributo estaba bien, la regla CSS lo ignoraba.

**Fix:** agregada `.dep-wrap.card.section[hidden]{display:none}` (4
"clases" de especificidad), gana sobre la regla de Z.7.1 cuando el
atributo está presente. `Laboratorio` (sección 9, ítem 42 del PDF) NO
tiene el calificador "SOLO EN CASOS CONFIRMADOS" que sí tiene el ítem
43 -- se deja sin gate a propósito, es correcto que se pueda llenar
antes de clasificar (la muestra suele ser insumo para clasificar, no al
revés).

**Verificación:** Playwright, ronda completa -- carga fresca sin tocar
nada: `hidden` presente, no visible. Tras elegir "Confirmado": `hidden`
ausente, visible, con el mismo look de tarjeta de Z.7.1 intacto (fondo
blanco, sombra, borde). Tras volver a "Sospechoso": `hidden` presente
de nuevo, oculto. Tres verificadores en verde.

**Z.8. Orden de secciones: "Clasificación del caso" pasa a ir después
de "Laboratorio" — ✅ cerrado**

El usuario pidió el orden clínico lógico: muestra → resultado →
clasificación → seguimiento si corresponde -- "Clasificación del caso"
(campo propio de P35.0, sección con `orden: 6` en el manifiesto, la
última del bloque clínico) quedaba ANTES de "Laboratorio" porque las
secciones del manifiesto siempre renderizan dentro de
`#secciones-clinicas` (via `secciones-clinicas.php`), y "Laboratorio"/
"Seguimiento de excreción viral" son tarjetas de posición fija que
`nueva/index.php`/`fichas/editar.php` imprimen DESPUÉS de que ese div
se cierra -- ningún `orden` del manifiesto puede empujar una sección
más allá de esa frontera estructural.

**Mecanismo:** se excluye `p35_0_clasificacion_del_caso` (y su sección,
"Clasificación del caso") del loop genérico de `secciones-clinicas.php`
para P35.0 -- mismo mecanismo ya existente de
`$CLAVES_CUBIERTAS_POR_PARTIAL_A_MEDIDA`/`$SECCIONES_CON_PARTIAL_A_MEDIDA`
que usan B05/B26/O95 para sus partials a medida, no algo nuevo. Un
partial nuevo, `clasificacion-caso-p350.php`, resuelve el campo por
clave (`$campo()`, `campos-por-clave.php`) y reusa el despachador
genérico (`campo-dinamico.php` → `campos/select.php`) para pintarlo
exactamente igual que si siguiera en el loop -- envuelto en una función
para no pisar la variable `$campo` (el resolvedor) del llamador con la
fila de `campo_def`, mismo motivo por el que `$campoFechaUltSeg` se
resuelve ANTES de entrar a la closure `$renderizarCampos` un poco más
arriba en el mismo archivo. Se llama desde `nueva/index.php` y
`fichas/editar.php`, justo después de "Laboratorio" y antes del loop de
`bloques_condicionales` (`$isP350`), incrementando `$numeroSeccion`
adentro del partial para no repetirlo en los dos llamadores.

**Detalle de implementación:** el `require` condicional se puso DENTRO
del bloque `<?php ... ?>` de los bloques condicionales (no como una
etiqueta PHP aparte) para no sumar una línea de espacio en blanco al
HTML de las otras 23 fichas -- se detectó justo así, con un byte-diff
que mostraba +6 bytes idénticos en las 5 fichas ya revisadas por una
línea con solo espacios.

**Verificación:** orden final confirmado por Playwright (`h3` en
orden): "...Laboratorio → Clasificación del caso → Seguimiento de
excreción viral (ítem 43...) → Investigador → Clasificación del caso"
(la última es la genérica compartida de siempre, sin relación). Ronda
crear()→editar(): el valor guardado en el campo propio se recarga
seleccionado correctamente en su nueva posición; el gate del ítem 43
(Z.7/Z.7.3) sigue funcionando igual desde la nueva ubicación. Tres
verificadores en verde (P35.0 sigue 34/34 sin huérfanos), byte-diff
limpio en A36/B26/A80/O95/B05.

## 10. Cotejo de Tétanos (A35) — en curso, retomar desde acá

Arranca el cotejo de A35 contra el PDF (págs. 23-24 del compendio).
Primer tramo pedido explícitamente por el usuario: "fechas y campos de
notificación y número de caso" — la cabecera de la ficha, antes de
tocar "I. DATOS DEL PACIENTE" (núcleo, ya cubierto genéricamente) ni
"II. FUENTE DE NOTIFICACION" (tipo/institución informante/fuente,
concepto propio de A35 que NO es el genérico tipo/lugar de captación —
queda pendiente para un tramo posterior).

**A35.1. Nueva sección "Datos de notificación e investigación del
caso" — ✅ cerrado**

El PDF de A35 tiene un formato de cabecera distinto al resto: un
recuadro "FECHA .........." / "CASO Nº........." junto al título
(no una fila en tabla), y una tabla de 4 columnas -- "Fecha de
conocimiento local", "Fecha de Investigación (visita domiciliaria)",
"Fecha notificación EE SS a Red/Microrred", "Fecha notificación
Red/Microrred a DISA" -- sin "código de registro" en la tabla (a
diferencia de B26) ni escalamiento hasta CDC (a diferencia de B26/
P35.0, que sí llegan a "Dirección de Salud a CDC"). Transcripción
literal del PDF, no se copió la redacción de otra ficha: "EE SS" sin
puntos, "Microrred" con doble r, "DISA" sin expandir, sin el
sufijo "del caso" en "Fecha de conocimiento local" (B26 sí lo tiene).

"FECHA .........." se interpreta como el mismo campo genérico
`fecha_notif` que ya pide la tarjeta "1. Notificación" para las 24
fichas (mismo criterio usado para las demás cabeceras a medida) — no
se declaró un campo nuevo para eso. "CASO Nº........." sí es nuevo
(`a35_caso_n`, TEXTO): mismo rol que "código de registro" en
B05/B26/P35.0, aunque el PDF de A35 lo llama distinto y lo ubica en
otro lugar de la página.

**Mecanismo:** nueva sección `orden: 1` en el manifiesto ("Datos de
notificación e investigación del caso", 5 campos: `a35_caso_n` +
4 `FECHA`), secciones existentes de A35 renumeradas (+1). Partial
nuevo `notificacion-fechas-a35.php`, calcado del patrón ya usado por
B05/B26/O95/P35.0/PFA: se incluye sin condición en la tarjeta "1.
Notificación" de `nueva/index.php`/`fichas/editar.php` (oculto si A35
no es la ficha activa, para sobrevivir al cambio de enfermedad sin
recargar), y se excluye del loop genérico de `secciones-clinicas.php`
vía `$CLAVES_CUBIERTAS_POR_PARTIAL_A_MEDIDA`/
`$SECCIONES_CON_PARTIAL_A_MEDIDA` (mismo mecanismo que ya usan
B05/B26/O95/P35.0, no algo nuevo).

**Fuera de alcance a propósito, no se tocó:** el bloque genérico
`notificacionCaptacionWrap` (tipo/lugar/clasificación en la captación)
sigue mostrándose para A35 aunque esos conceptos no existen en su PDF
real -- ahí vive "II. FUENTE DE NOTIFICACION" (tipo de notificación,
institución informante, fuente, trabajador que diagnostica,
establecimiento que notifica), un concepto distinto que corresponde a
un tramo posterior del cotejo, no a "fechas y número de caso".

**Nota para el ítem 5 de este documento:** con A35 son 6 fichas con
cabecera de notificación propia (A80/PFA con columnas fijas de `caso`;
B05, B26, O95, P35.0, A35 con `campo_def`) — cruza el umbral de "5 o 6
fichas" que el ítem 5 puso como condición para diseñar
`fecha_notif_desde` y hacer el retrofit de todas juntas. No se
construyó acá (fuera del alcance pedido hoy), queda anotado para
cuando se retome ese ítem.

**Verificación:** `cargar_fichas.php --apply --cie10=A35` sin bloqueo
por datos capturados. Tres verificadores en verde (A35 25/25 campos,
0 huérfanos; mismos 3 huérfanos preexistentes de siempre en A80/B26/
B55; 0 claves faltantes de 197). Byte-diff limpio en A36/B26/A80/O95/
B05 -- la única diferencia es la aparición del nuevo bloque
`notificacionFechasA35Wrap` oculto en el DOM de las 5, mismo patrón
exacto que ya usan los bloques hermanos de B05/B26/O95/P35.0/PFA (se
confirmó contra el antes/después vía `git stash`, no es una regresión
de espacios en blanco como Z.8). Playwright: orden de secciones
correcto, bloque visible con las 5 etiquetas esperadas, sin errores de
consola. Ronda crear()→BD→editar(): los 5 valores se guardan en
`caso_valor` con las claves correctas y reaparecen tal cual en el HTML
de editar(); caso de prueba y persona asociada eliminados después.
