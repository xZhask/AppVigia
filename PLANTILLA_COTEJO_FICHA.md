# Plantilla de cotejo — ficha `{CIE10}` · {NOMBRE} · págs. {PÁGINAS} del compendio

> Se rellena una por ficha. Los `{...}` se reemplazan antes de enviar. Todo lo demás se manda tal cual.

---

## Instrucciones permanentes (aplican a toda la petición)

1. **Nada de condiciones por ficha en código compartido.** Si un paso te obliga a escribir `if ($cie10 === '...')` o a agregar un CIE-10 a una lista dentro de un partial compartido, **para y explícame** antes de hacerlo. Las variaciones por ficha se declaran como dato en `manifiesto_fichas.json`.
2. **Si un cambio mueve, oculta o altera algo en una ficha ya cotejada, avísame antes de commitear**, aunque el cambio sea correcto. Fichas cotejadas al día de hoy: A36, B26, A80, O95, B05, P35.0.
3. **Un arreglo de partial compartido por sesión.** Nunca dos en el mismo commit.
4. **La fuente de verdad es la página del PDF**, nunca un inventario generado por otra herramienta.
5. **Clave explícita obligatoria** en todo campo que agregues. Nada de claves derivadas de la etiqueta.

---

## Paso 0 — Línea base (antes de tocar nada)

Corre los tres verificadores y guárdame el resultado como línea base:

- `verificar_fichas.php` — manifiesto ↔ BD
- `verificar_claves.php` — código ↔ claves
- `verificar_render.php` — BD ↔ HTML

**Si `{CIE10}` ya aparece con huérfanos, dímelo y para.** Un campo huérfano preexistente significa que hay más de un camino de render involucrado, y hay que entenderlo antes de agregar nada.

Recordatorio de por qué importa: no existe un solo camino de render. Hoy se conocen cuatro (`secciones-clinicas.php` genérico, partials a medida, `nueva/index.php` directo, y `name` literal remapeado a mano en `CasosController`). `verificar_render.php` es lo único que mira la unión de todos.

---

## Paso 1 — Inventario declarativo

Reporta el estado actual de `{CIE10}` en cada mecanismo, y qué debería declarar según el PDF. **Solo reportar en este paso, sin escribir nada.**

| Mecanismo | Qué preguntar contra el PDF |
|---|---|
| `nucleo_omitidos` | ¿Qué campos del núcleo NO pide esta ficha? (gestante, celular, ocupación, nombre/celular del tutor, nacionalidad, etnia, pueblo étnico, dirección, localidad…) |
| `unidades_edad` | ¿Pide edad con unidad? ¿Cuáles exactamente? Ausente = solo años |
| `columnas_sujeto` / `titulo_sujeto` | ¿Hay un sujeto secundario con bloque propio de identidad (madre, contacto, fallecido)? ¿O ese rol es el sujeto principal y se captura por el formulario normal? |
| `tablas_hijas` + `columnas_tablas_hija` | contacto / muestra / viaje / vacuna: ¿cuáles usa y con qué columnas exactas? |
| Cabecera estándar de fechas | ¿La trae? (n.º de ficha, conocimiento local, la cadena EE.SS.→Red→DISA→CDC, investigación) |
| `depende_de` / `valor_activador` | Todo campo que en el papel esté indentado bajo un "Sí" o una casilla |
| Tipos de campo | `MATRIZ` cuando la tabla tiene columnas propias; `GRUPO_SI_NO` solo cuando es Sí/No/Ignorado sin columnas |
| Anexos | ¿Es una ficha con anexos (como O95 o Y59.0)? |
| Orden de secciones | El del PDF manda; agrupar distinto es aceptable, invertir el orden de los ítems no |

---

## Paso 2 — Cotejo campo por campo

Recorre el PDF **por número de ítem**, de principio a fin, y devuélveme esta tabla completa antes de escribir nada:

| Ítem PDF | Etiqueta en el papel | ¿Existe hoy? | Dónde (núcleo / `campo_def` / tabla hija) | Acción |
|---|---|---|---|---|

Acciones posibles: `OK` · `AGREGAR` · `RENOMBRAR` · `QUITAR` (no lo pide el PDF) · `RETIPAR` · `MOVER DE SECCIÓN` · `BLOQUEADO` (con el motivo).

No omitas ítems por parecer obvios. Los que más se han perdido hasta ahora fueron los que estaban dentro de un bloque más grande: tiempo de residencia, referencia para localizar, columnas de resultado (−)/(+), semana de gestación de una tabla.

**Para aquí y espera mi visto bueno antes de escribir código.**

---

## Paso 3 — Implementación

En este orden, con commit por bloque:

1. Declaraciones del manifiesto (`nucleo_omitidos`, `unidades_edad`, `columnas_sujeto`, `columnas_tablas_hija`, orden de secciones).
2. Altas, bajas y renombres de `campo_def`, con clave explícita.
3. `depende_de` / `valor_activador`.
4. Lo que requiera motor, **si es que lo hay** — y solo tras avisarme.

### Condiciones de parada

Para y repórtame, sin degradar la solución, cuando:

- el PDF pida una capacidad que el motor no tiene (fue el caso de las 17 fechas de manifestación de P35.0: convertir a `MATRIZ` habría perdido los radios exclusivos, y era peor que no hacerlo);
- haga falta un dato externo que no está en el sistema (un catálogo, una nomenclatura OMS);
- el arreglo correcto toque un partial compartido por varias fichas;
- un campo del PDF no tenga dónde vivir sin agregar una columna al núcleo (el núcleo **no** es extensible desde el manifiesto: cuesta ~8 archivos a mano).

---

## Paso 4 — Verificación de cierre

No basta con que los verificadores pasen.

1. Los tres verificadores en verde, y `verificar_render.php` **sin huérfanos nuevos** en ninguna de las 24.
2. Vuelta completa con el controlador real en `{CIE10}`: crear → guardar → editar → ver. Confirmar en BD que los valores persistieron. Borrar el caso de prueba y confirmar 0 filas residuales.
3. **Prueba negativa:** intenta forzar por POST algo que `{CIE10}` no declara y confirma que el servidor lo descarta. Que no se pinte no prueba que no se guarde.
4. Una ficha de control ya cotejada, sin cambios: la vuelta completa en A36 (o la que corresponda si el cambio tocó otro mecanismo).

---

## Paso 5 — Cierre

Resúmeme:

- tabla de ítems del PDF con su estado final;
- qué quedó en `PENDIENTES.md` y por qué (bloqueado, decisión mía, o dato externo faltante);
- si `{CIE10}` queda **al 100 %** o no, y si no, exactamente qué falta.

Una ficha se declara cerrada solo cuando todos sus ítems del PDF están en `OK` o en `PENDIENTES.md` con motivo escrito.
