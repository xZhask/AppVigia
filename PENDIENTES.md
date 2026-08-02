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

## 4. El cargador salta en silencio las secciones sin campos — ✅ cerrado (solo el reporte)

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

## 7. Normalizar `clave` explícita en los ~870 campos restantes del manifiesto — ✅ cerrado

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
crudo para `MATRIZ` — ítem 2 de este documento, preexistente, no es
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
