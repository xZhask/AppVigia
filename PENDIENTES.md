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
