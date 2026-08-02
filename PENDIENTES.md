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

**F. Pendiente de decisión, prioridad media — Edad en meses/días
(ítem 11 del PDF de P35.0)**

El núcleo solo calcula edad en **años**: `edadDesdeFecha()`
(`app/Core/ayudantes.php:371`) hace `$nacimiento->diff(new
DateTime())->y` sobre `persona.fecha_nac` — el `->y` descarta meses y
días. Ninguna de las 24 fichas captura edad en una unidad más fina,
ni siquiera A33 (Tétanos neonatal) o A80 (PFA), que también notifican
pacientes muy jóvenes. Cambiarlo afectaría a las 24 fichas, no solo a
P35.0 (para quien es más relevante: un paciente de 3 meses se ve hoy
como "0 años"). **Decisión del usuario, no implementar** hasta que se
decida el alcance real (¿cambio de unidad solo para P35.0 vía un
campo `campo_def` propio, o cambio de unidad del núcleo para las 24?).

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

- **Fase 4**: ✅ cerrada (commit `a3c35db`). Los 10 pares
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
