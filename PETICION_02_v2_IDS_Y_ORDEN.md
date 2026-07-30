# VIGÍA · Petición 2 (v2) — IDs hardcodeados y orden explícito

Objetivo: que ninguna vista, controlador ni archivo JS dependa de un ID
autoincremental de `campo_def`, y que el orden de secciones y campos deje de ser
implícito. Ambas cosas apuntan a lo mismo: **volver a hacer utilizable
`cargar_fichas.php` sin romper las fichas ya validadas.**

Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`.

> **Requisito previo obligatorio:** la Petición 1 debe estar completa. Esta
> petición se apoya en el respaldo verificado de su Fase 1.
> Ejecutar por fases, **validando entre cada una**.

*(v2: se agrega la Fase 6 — orden explícito. Antes iba implícito en el orden del
array del manifiesto.)*

---

## Por qué esto es urgente

`cargar_fichas.php` es, por diseño, destructivo: por cada enfermedad hace
`DELETE FROM seccion_def` (cascada a `campo_def`) y reinserta desde el
manifiesto. **Los IDs se regeneran en cada recarga.**

Hoy, si se corre `php cargar_fichas.php --apply --confirmo-apply` sobre B26 u O95:

- Los `name="campo_16126"` de las vistas apuntarían a campos de otra ficha o a
  nada. Los formularios se dibujarían vacíos o mezclados.
- `CasosController.php` compara `$campoId === 13729` en la **ruta de guardado**.
  No lanzaría error: guardaría mal, en silencio.
- `ficha.js` busca `input[name="campo_13708"]` para decidir qué mostrar. Los
  condicionales dejarían de responder.

Es decir: **el cargador único y las fichas validadas hoy son mutuamente
excluyentes.** No se puede usar el uno sin romper las otras.

### Dónde están los IDs

177 IDs distintos en 18 archivos:

| Archivo | IDs distintos |
|---|---|
| `public/js/ficha.js` | 44 |
| `app/Views/partials/entorno-social-o95.php` | 20 |
| `app/Views/partials/datos-fallecimiento-o95.php` | 20 |
| `app/Views/partials/cuadro-clinico-b26.php` | 20 |
| `app/Views/partials/referencia-o95.php` | 18 |
| `app/Views/partials/antecedentes-patologicos-obstetricos-o95.php` | 13 |
| `app/Views/partials/causas-defuncion-o95.php` | 12 |
| `app/Views/partials/parto-aborto-o95.php` | 11 |
| `app/Views/partials/atencion-prenatal-o95.php` | 10 |
| `app/Views/partials/datos-paciente-nucleo.php` | 9 |
| `app/Views/partials/datos-comunitarios-o95.php` | 9 |
| `app/Views/partials/complicaciones-o95.php` | 7 |
| `app/Views/partials/demoras-o95.php` | 5 |
| `app/Views/partials/hospitalizaciones-o95.php` | 4 |
| `app/Views/partials/lugar-probable-infeccion-b26.php` | 3 |
| `app/Views/partials/secciones-clinicas.php` | 2 |
| `app/Views/nueva/index.php` | 1 |
| `app/Views/fichas/editar.php` | 1 |

Más tres comparaciones en `app/Controllers/CasosController.php`
(líneas 932, 947, 953: `13729`, `14301`, `14302`).

---

## El patrón de destino ya existe en el proyecto

`app/Views/partials/notificacion-fechas-b26.php` y `notificacion-fechas-p350.php`
**ya resuelven los campos por búsqueda, no por ID**: cargan la sección, arman un
mapa y piden cada campo por su nombre. El `name="campo_{id}"` del HTML se
construye en tiempo de ejecución desde la fila leída.

Ese es el patrón correcto. Esta petición lo generaliza y lo hace obligatorio.

**Una corrección respecto a esos dos archivos:** resuelven por `etiqueta`. La
etiqueta es texto de presentación y cambia cuando se corrige una tilde o una
redacción contra el PDF. Usar **`clave`**, que es estable por construcción
(`{cie10}_{slug}`) y ya es única por ficha.

---

## Fase 1 — Construir el mapa ID → clave

Sin modificar ningún archivo todavía.

1. Recolectar los 177 IDs con `grep -rhoE 'campo_[0-9]{4,}' app/Views public/js`
   más los tres de `CasosController.php`.

2. Para cada ID, consultar la base viva:

   ```sql
   SELECT cd.id, cd.clave, cd.etiqueta, cd.tipo, sd.nombre AS seccion, e.cie10
   FROM campo_def cd
   JOIN seccion_def sd ON sd.id = cd.seccion_id
   JOIN enfermedad e ON e.id = sd.enfermedad_id
   WHERE cd.id IN (...);
   ```

3. Generar `MAPA_IDS_CAMPOS.md` con una fila por ID: ID, CIE-10, sección, clave,
   etiqueta, tipo, y los archivos y líneas donde aparece.

4. **Reportar explícitamente los IDs que no existan en la base.** Son referencias
   muertas —campos borrados por algún script de `scratch/`— y hay que decidir una
   por una si se elimina el bloque de la vista o si falta recrear el campo.
   No inventar una clave para ellos.

5. Verificar que no haya claves duplicadas dentro de una misma ficha. Si las hay,
   detenerse: hay que corregir el manifiesto antes de seguir.

**Validación:** `MAPA_IDS_CAMPOS.md` cubre los 180 IDs, sin huecos silenciosos.

---

## Fase 2 — Un resolvedor único, por clave

Crear `app/Views/partials/campos-por-clave.php`, incluido una sola vez por
formulario, que exponga un helper con esta forma:

```php
// $campo('o95_fecha_de_fallecimiento') devuelve:
//   ['id' => int|null, 'name' => 'campo_123', 'val' => mixed,
//    'err' => ?string, 'opciones' => array, 'campo' => array|null]
```

Requisitos:

- Carga **todas** las secciones y campos de `$enfermedad` una sola vez y los
  indexa por `clave`. Nada de una consulta por campo.
- Si la clave no existe, devuelve `['id' => null, 'name' => '', …]` y **registra
  la clave faltante** en un arreglo que la vista pueda volcar en un aviso visible
  cuando `config('app.debug')` esté activo. Un campo que desaparece del manifiesto
  no debe fallar en silencio: hoy ese es exactamente el modo de fallo.
- Reutiliza `$valoresCampos` y `$erroresCampos` tal como ya llegan a las vistas.
- No cambia ninguna firma ni contrato existente.

**Validación:** una vista de prueba que pida tres claves conocidas y una
inexistente devuelve lo esperado en los cuatro casos.

---

## Fase 3 — Migrar las vistas PHP

Un archivo por vez, del más chico al más grande. Orden sugerido:
`nueva/index.php`, `fichas/editar.php`, `secciones-clinicas.php`,
`lugar-probable-infeccion-b26.php`, `hospitalizaciones-o95.php`,
`demoras-o95.php`, y de ahí hacia arriba en la tabla.

Por cada archivo:

1. Reemplazar cada `campo_NNNN` por la resolución por clave, usando
   `MAPA_IDS_CAMPOS.md`:

   ```php
   <!-- antes -->
   <select name="campo_16110" data-nosearch="true">
   <input type="text" name="campo_16113" value="<?= e($valoresCampos[16113] ?? '') ?>">

   <!-- después -->
   <?php $etnia = $campo('o95_grupo_etnico'); $etniaOtro = $campo('o95_otro_grupo_etnico'); ?>
   <select name="<?= $etnia['name'] ?>" data-nosearch="true">
   <input type="text" name="<?= $etniaOtro['name'] ?>" value="<?= e($etniaOtro['val']) ?>">
   ```

2. Eliminar también los `$valoresCampos[16111]` y similares que acceden al
   arreglo por ID literal.

3. **No cambiar el HTML resultante.** El marcado, las clases y el orden visual
   quedan exactamente iguales. Ninguna clase nueva en `theme.css`.

4. Después de cada archivo: abrir la ficha en el navegador, comprobar que se
   dibuja igual, guardar un caso de prueba y verificar en `caso_valor` que los
   valores caen en los `campo_def_id` correctos.

**Validación de la fase:**
`grep -rE 'campo_[0-9]{4,}' app/Views` no devuelve nada.

---

## Fase 4 — Migrar `ficha.js`

Los 44 IDs del JS no se pueden resolver en el cliente. Se resuelven en el
servidor y se exponen como datos.

1. En el formulario, emitir el mapa clave → nombre de campo de la enfermedad
   activa:

   ```php
   <script type="application/json" id="mapaCampos"><?= json_encode($mapaClaveNombre) ?></script>
   ```

2. En `ficha.js`, leerlo una vez al iniciar y reemplazar cada literal:

   ```js
   // antes
   var radHosp = document.querySelector('input[name="campo_13720"]:checked');
   // después
   var radHosp = document.querySelector('input[name="' + campoPorClave('b26_hospitalizado') + '"]:checked');
   ```

3. `campoPorClave()` devuelve cadena vacía si la clave no está; todo consumidor
   debe tolerarlo sin lanzar excepción (hoy varios bloques asumen que el elemento
   existe).

4. El mapa debe **refrescarse** en el endpoint AJAX que recarga las secciones al
   cambiar de enfermedad. Si no, al cambiar de ficha el JS seguiría usando el mapa
   de la anterior. Verificar este caso explícitamente.

**Validación de la fase:**
`grep -rE 'campo_[0-9]{4,}' public/js` no devuelve nada. Probar en navegador el
cambio de enfermedad ida y vuelta: B26 → O95 → B05 → B26.

---

## Fase 5 — `CasosController.php`

Reemplazar las tres comparaciones por ID por comparación sobre `$campo['clave']`.
Los tres son casos especiales que hoy sortean el motor de tipos; **esta fase no
los rediseña, solo deja de identificarlos por ID**:

| ID | Qué hace hoy | Migrar a |
|---|---|---|
| `13729` | Arma una lista estructurada (tipo/nombre/dirección/sanos/enfermos) desde arrays de `$_POST` y la guarda como JSON | comparación por clave, misma lógica |
| `14301` | Concatena `fecha_notif` + `hora_notificacion` en una sola cadena | comparación por clave, misma lógica |
| `14302` | Toma `identificado_por` de un input suelto | comparación por clave, misma lógica |

Revisar que no queden otros accesos por ID en el mismo flujo de guardado.

> Estos tres son síntomas de tipos que faltan en el motor (`FECHA_HORA`,
> `LISTA_ESTRUCTURADA`). Se anotan aquí para la Petición 3; **no se resuelven en
> esta**, porque cambiar el formato de almacenamiento y migrar IDs a la vez haría
> imposible saber cuál de los dos rompió algo.

**Validación:** guardar un caso completo de B26 y uno de O95 y comparar
`caso_valor` contra lo guardado antes de la migración. Deben ser idénticos.

---

## Fase 6 — Orden explícito de secciones y campos

*(Nueva en v2.)*

### El problema

Las fichas MINSA no comparten un orden de secciones. "Lugar probable de
infección" es la sección 3 en unas fichas y la 5 en otras; un mismo campo cae en
secciones distintas según la ficha. El esquema **ya soporta** esto —
`seccion_def.orden` y `campo_def.orden` son por enfermedad, y los modelos ordenan
por ellos:

```php
'SELECT * FROM seccion_def WHERE enfermedad_id = :enf ORDER BY orden, id'
'SELECT * FROM campo_def WHERE seccion_id = :sec ORDER BY orden, id'
```

El problema es de dónde sale ese `orden`. **`cargar_fichas.php` nunca lee la
clave `orden` del manifiesto.** La asigna por posición en el array:

```php
$ordenSeccion = 1;
foreach ($ficha['secciones'] as $seccion) {
    $stmt->execute([$enfermedadId, $seccion['nombre'], $ordenSeccion]);
    $ordenSeccion++;
}
```

Consecuencias:

- Los `"orden": 1..6` que hay hoy en P35.0 y O95 son **decorativos**. El cargador
  los ignora.
- Las otras 22 fichas no los tienen: **130 secciones sin `orden` explícito**.
- Si alguien reordena editando `orden` y recarga, no pasa nada. Vuelve al orden
  del array, en silencio.

Hoy el orden en pantalla es correcto por coincidencia entre dos fuentes que nadie
compara.

### Qué hacer

1. **Que el cargador respete `orden`.** Si una sección trae `orden`, usarlo. Si
   no, caer a la posición en el array. Igual para `campos`.

2. **Regla de todo o nada por ficha.** Si dentro de una misma ficha algunas
   secciones traen `orden` y otras no, **abortar con excepción** —igual que se
   hace hoy con los tipos desconocidos—. Mezclar orden explícito e implícito
   produce colisiones y huecos silenciosos.

3. **Normalizar las 130 secciones sin `orden`.** Escribir el `orden` explícito
   tomándolo de la posición actual en el array, de modo que el resultado sea
   idéntico al de hoy. Hacer lo mismo con los campos dentro de cada sección.
   Este paso **no cambia nada visible**: solo vuelve explícito lo que hoy es
   accidental.

4. **Que el verificador compare el orden.** `verificar_fichas.php` ya lee `orden`
   de la base (`ORDER BY enfermedad_id, orden, id`) pero no lo contrasta contra
   el manifiesto. Agregar esa comparación al reporte: sección por sección y campo
   por campo. Sin esto, una deriva de orden seguiría siendo invisible.

5. **Verificar unicidad.** Dentro de una enfermedad, dos secciones no pueden
   compartir `orden`; dentro de una sección, dos campos tampoco. Reportar
   duplicados como error del manifiesto.

**Validación de la fase:** las 24 fichas tienen `orden` explícito en todas sus
secciones y campos; un dry-run del cargador no reporta ningún cambio de orden
respecto a la base actual; el verificador incluye la comparación de orden y pasa
en las 24.

---

## Fase 7 — La prueba de fuego

El objetivo real de toda la petición.

1. Respaldar la base (Petición 1, Fase 1).
2. Correr `php cargar_fichas.php` **sin `--apply`** y revisar el plan completo.
3. Correr `php cargar_fichas.php --apply --confirmo-apply --cie10=B26`.
4. Abrir la ficha de Parotiditis en el navegador. Debe verse **idéntica** — mismo
   contenido, mismo orden de secciones, mismos condicionales — con todos los IDs
   de `campo_def` ya regenerados.
5. Repetir con `--cie10=O95` y con `--cie10=B05`.
6. Correr `php verificar_fichas.php` y comprobar que las 24 fichas pasan,
   incluida la comparación de orden.

Si las tres fichas sobreviven a una recarga completa, el problema está cerrado y
el cargador vuelve a ser usable. **A partir de ahí, recargar una ficha desde el
manifiesto deja de ser una operación peligrosa** — que es la condición para poder
encarar la Petición 3.

**Si alguna no sobrevive, restaurar el respaldo y reportar qué se rompió.** No
parchear sobre la marcha.

---

## Fuera de alcance

No se reorganiza ningún partial, no se fusiona `nueva/index.php` con
`fichas/editar.php`, no se agregan tipos nuevos al motor, no se toca `theme.css`
ni el contenido de las fichas. Esta petición es una sustitución mecánica de
referencias más la explicitación del orden: **el comportamiento observable no
cambia en nada.**

Queda anotado para la Petición 3 —motor de componentes reutilizables— lo
siguiente, detectado durante este análisis:

- Tipos que faltan: `FECHA_HORA` (hoy dos inputs concatenados a mano, 5
  `type="time"` sueltos en las vistas) y `LISTA_ESTRUCTURADA` (hoy JSON armado
  desde arrays de `$_POST`).
- `exantema-evolucion-body-map.php` es un renderizador real que **no está en el
  despachador** de `campo-dinamico.php`: se invoca desde un `if` en
  `secciones-clinicas.php`. Debería ser un tipo.
- UBIGEO encadenado no es un tipo de campo, por eso en las tablas hijas quedó
  como texto libre (hallazgo A.7 de `HALLAZGOS_DIFTERIA_PFA.md`, aún abierto).
- Variantes de sección por ficha: qué campos entran a un mismo componente según
  la enfermedad.
## Fase 8 — Recongelar el esquema

La Fase 7 regeneró deliberadamente todos los `campo_def.id` y `seccion_def.id`
de B26, O95 y B05. `sql/01_esquema_actual.sql` incluye los `INSERT` de esas
tablas, así que quedó desactualizado respecto a la base.

1. Regenerar `sql/01_esquema_actual.sql` con el mismo procedimiento de la
   Petición 1, Fase 3, y verificarlo igual: base vacía, aplicar, comparar
   conteos de `campo_def`, `seccion_def`, `catalogo` y `catalogo_item`.
2. Confirmar que `grep -rE 'campo_[0-9]{4,}' app/ public/` sigue sin devolver
   nada — si aparece algo, es una referencia que se coló durante la migración.
3. Commit único, con el esquema regenerado y `MAPA_IDS_CAMPOS.md` juntos.

A partir de acá, recargar una ficha desde el manifiesto deja de invalidar el
esquema versionado: los IDs ya no son parte del contrato con las vistas.