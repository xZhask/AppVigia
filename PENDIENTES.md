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
fila, no por columna; Z21 y Y59.0 ya pierden sus columnas de fecha**

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

**B. Cotejo incompleto de P35.0, prioridad media — 17 fechas de
manifestación sin capturar. BLOQUEADA POR A, no retomar antes**

El ítem 34 del PDF pide, por cada una de las 17 manifestaciones
clínicas, marcar Sí/No/Desconocido **y** la fecha en que apareció. El
manifiesto de P35.0 tiene esas 17 manifestaciones como 4 campos
`GRUPO_SI_NO` (sin columna de fecha — `GRUPO_SI_NO` no tiene columnas).
Convertirlos a `MATRIZ` con columnas `Sí/No/Desconocido/Fecha de
manifestación` HOY sería estrictamente peor que dejarlos como están: al
tener una columna de fecha, la matriz completa cae a modo texto libre
(bug A) y se pierde también la exclusividad Sí/No/Desconocido que ya
funciona bien en `GRUPO_SI_NO`. **Decisión (2026-08-01): se queda como
`GRUPO_SI_NO`, sin las 17 fechas, hasta que A esté resuelto.** Cuando A
se arregle, B se resuelve solo convirtiendo los 4 campos a `MATRIZ` —
no es trabajo nuevo, es la misma conversión que hoy degradaría.

**C. BUG, prioridad alta — `muestras.php` tiene las columnas de
serología hardcodeadas a B05 con `if ($esB05)`, no declarativas**

`caso_muestra` ya tiene columnas `resultado_igm`, `fecha_result_igm`,
`resultado_igg`, `fecha_result_igg`, `genotipo`, `resultado_pcr`,
`fecha_result_pcr`, pero `app/Views/partials/tablas-hijas/muestras.php`
solo las pinta dentro de una rama `if ($esB05)` — no pasan por
`columnas_tablas_hija.caso_muestra` (el mecanismo que sí usan A80 y el
resto de las 13 fichas con `usa_muestras=1`), ni están en
`COLUMNAS_TABLA_HIJA_VALIDAS` de `cargar_fichas.php`. Es el mismo
antipatrón `if (cie10 === 'X')` en código compartido que la Fase 2 de
esta petición acabó de eliminar para P96 — preexistente, encontrado al
investigar la Fase 5.2, no introducido por esta sesión. No arreglado
(no era parte del alcance pedido).

**D. Cotejo incompleto de P35.0, prioridad media — laboratorio (ítems
42-43) sin capturar. BLOQUEADA POR C, no retomar antes**

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
B05 dentro del partial que comparten las 24 fichas**

Encontrado al reportar la Fase 6 (pregunta 3, ítem 18 del PDF de
P35.0). No es un hueco de contenido de P35.0 — es la **misma familia
de bug que A y C**: `datos-paciente-nucleo.php` (el partial de "Datos
del paciente" que usan las 24 fichas) tiene un bloque
`b05-field-wrap` que solo se muestra cuando `$esB05` es verdadero,
con el campo "Referencia para localizar (a la altura de o cerca de:
Iglesia, fundo, comercio, etc.)" adentro, como `campo_def` propio de
B05 (`b05_referencia_para_localizar_cerca_de_iglesia_fundo_co`) — no
como parte del núcleo declarativo (`nucleo_omitidos`/`data-nucleo-campo`)
que ya usan `celular`/`nacionalidad`/`localidad`/`direccion`/etc. Para
que otra ficha (P35.0 u otra) pudiera pedir este mismo dato, hoy
tendría que declarar su propio `campo_def` con el mismo texto — el
campo de B05 no es reutilizable ni generalizable sin tocar el bloque
`b05-field-wrap`. No arreglado (no era parte del alcance pedido).

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

**G. Pendiente de decisión, prioridad media — "Tiempo de residencia"
(ítem 18 del PDF de P35.0)**

No existe en ningún lado del sistema: ni en el núcleo (`persona`/`caso`)
ni como `campo_def` de ninguna de las 24 fichas. Candidato directo a
agregarse como `campo_def` propio de P35.0 (mismo patrón que "N.° de
historia clínica", resuelto en esta sesión) cuando se decida hacerlo
— no bloqueado por nada, a diferencia de B/D/lo de arriba.

**H. Pendiente de decisión, prioridad media — "Pueblo étnico" (ítem
15 del PDF de P35.0, texto libre)**

Sin equivalente reusable. El núcleo tiene "Etnia/raza" (`persona.etnia`,
ENUM cerrado: Mestizo/Andino/Asiático descendiente/Afrodescendiente/
Indígena amazónico/Otro) — corresponde al ítem 16, no al 15. B05 y O95
tienen algo con nombre parecido ("Pueblo étnico o etnia" / "Etnia /
Pueblo étnico") pero ambos son `SELECT` de catálogo cerrado, que
fusiona pueblo+etnia en un solo campo — no el texto libre separado que
pide el ítem 15 de P35.0. Reusar el campo de B05/O95 tal cual
degradaría el dato (de texto libre a lista cerrada) o exigiría
inventar un catálogo de "pueblos" que hoy no existe. Candidato más
simple: `campo_def` TEXTO propio de P35.0, sin reusar nada de B05/O95.

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
