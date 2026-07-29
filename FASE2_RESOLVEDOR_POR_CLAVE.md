# Petición 2 · Fase 2 — Resolvedor único por clave

Generado 2026-07-29. Documenta qué se construyó, por qué, y cómo se
verificó, para que la Fase 3 (migración de las 18 vistas) pueda apoyarse
en esto sin releer el código.

---

## Qué se creó

**`app/Views/partials/campos-por-clave.php`** — se incluye una sola vez por
formulario (después de que `$enfermedad`, `$valoresCampos`, `$erroresCampos`
ya estén en scope) y expone:

- `$campo(string $clave): array` — devuelve
  `['id' => int|null, 'name' => string, 'val' => mixed, 'err' => ?string,
  'opciones' => array, 'campo' => array|null]`.
- `$clavesFaltantesCampos` — array de claves pedidas que no existen hoy en
  `campo_def` para la `$enfermedad` actual. Se llena progresivamente según
  se llama a `$campo()`.
- `$avisoClavesFaltantesCampos(): string` — HTML del aviso de debug (string
  vacío si no hay claves faltantes o si `config/config.php` tiene
  `app.debug` apagado). La vista decide dónde hacer `echo` de esto.

**`scratch/test_campos_por_clave.php`** — script de validación (no
versionado, como todo `scratch/` desde la Petición 1 Fase 4). Bootstrapea
la app igual que `public/index.php` (`Autoload.php` + `ayudantes.php`) y
llama a `$campo()` contra datos reales de la base viva.

---

## Decisiones de diseño y por qué

1. **Carga todo de una vez, no por campo.** `SeccionDef::porEnfermedad()` +
   `CampoDef::porSeccion()` por cada sección, indexado por `clave` en un
   solo array armado al incluir el partial — cumple el requisito de "nada
   de una consulta por campo" sin cambiar los modelos existentes.

2. **Claves duplicadas dentro de la misma ficha: gana la primera
   aparición, no error.** `MAPA_IDS_CAMPOS.md` ya documentó que A33, A80,
   Y07 y Z21 repiten la misma clave en dos secciones paralelas (víctima/
   agresor, gestante/niño). El resolvedor usa `??=` al indexar, así que la
   sección que aparece primero (por `orden`) gana de forma determinista.
   **No es una solución** — sigue habiendo ambigüedad real si alguna vista
   de esas 4 fichas llega a migrarse con este mecanismo sin antes prefijar
   las claves — pero es preferible a un error duro en Fase 2, que no toca
   esas fichas. Verificado con Z21 (caso 6 del test): `z21_recibio_arv`
   resuelve a la fila de la sección "Gestante", que es la que aparece
   primero.

3. **`opciones` solo se calcula si `catalogo_id` no es null**, cacheado por
   `catalogo_id` dentro de la misma resolución de formulario (mismo patrón
   que `$opcionesPorCatalogo` en `secciones-clinicas.php`) — dos claves con
   el mismo catálogo no repiten la consulta.

4. **El aviso de debug es responsabilidad de la vista, no de este
   partial.** `campos-por-clave.php` no decide dónde mostrarse: expone
   `$avisoClavesFaltantesCampos()` y cada vista migrada en Fase 3 decide si
   hace `echo` de eso y dónde. No se tocó `shell.php` — hubiera sido un
   cambio más amplio que "crear un archivo nuevo", que es lo que pedía esta
   fase.

5. **`config/config.php` se lee directamente** (mismo patrón que
   `app/Core/Database.php`), no se inventó una función global `config()`
   nueva — no estaba en el alcance de esta fase y el proyecto no la tiene.

---

## Cobertura de la validación (25 verificaciones, 0 fallos)

| # | Qué prueba | Ficha usada |
|---|---|---|
| 1 | Clave conocida, con valor, sin error (incluye que `campo` trae la fila real) | B26 |
| 2 | Clave conocida, con valor **y con error** (pasa `$erroresCampos` tal cual) | B26 |
| 3 | Clave conocida sin valor capturado (`val` cae a `''`) + campo con catálogo real (`opciones` no vacío) | B26 |
| 3b | Dos claves con catálogos distintos no comparten `opciones` | B26 |
| 4 | Clave inexistente: los 6 campos del contrato vienen como especifica la Fase 2, se registra en `$clavesFaltantesCampos`, y pedirla dos veces no duplica la entrada | — |
| 5 | `MULTISELECT` sin valor capturado cae a `[]`, no a `''`; el resolvedor funciona igual para una segunda `$enfermedad` en el mismo proceso | O95 |
| 6 | Clave duplicada dentro de una misma ficha resuelve a la primera sección, sin error | Z21 |
| — | Aviso de debug vacío cuando `app.debug=false` (valor real de `config/config.php` en este entorno) | — |

**Hueco de cobertura, dejado explícito:** la rama `app.debug=true` de
`$avisoClavesFaltantesCampos()` (que arma el HTML con la clave faltante) se
verificó **por lectura de código**, no por ejecución — requeriría cambiar
`config/config.php` a `debug: true`, aunque sea temporalmente, y no pareció
razonable tocar la configuración real del entorno solo para esta prueba. Es
una rama de 3 líneas (`if (empty(...debug...)) return ''`), bajo riesgo,
pero queda anotado en vez de darlo por probado.

---

## Qué necesita la Fase 3 de esto

- Incluir `campos-por-clave.php` una vez por vista/formulario, después de
  que `$enfermedad` esté resuelto.
- Para las 162 filas `ok` de `MAPA_IDS_CAMPOS.md`: sustitución mecánica
  directa, `campo_NNNN` → `$campo('clave_real')['name']`.
- Para las 10 `divergente` (todas en `datos-fallecimiento-o95.php` +
  `ficha.js`): usar la "Clave correcta" de la tabla, no la que corresponde
  al ID que hoy aparece en el código.
- Para las 4 `muerto_con_sucesor`: igual, usar la clave del sucesor
  (16128/16129/16130 y 16116 para 14306).
- Para `13729`/`14301`/`14302` y los dos agregados de la sesión anterior
  (`o95_tipo_ficha`, N.° H.C. de O95): estos no se resuelven con
  sustitución mecánica — son los casos especiales que la Fase 5 maneja por
  `clave` en `CasosController.php`, y necesitan que el campo exista primero
  en el manifiesto (para `o95_tipo_ficha` y N.° H.C.) antes de que
  `$campo()` deje de reportarlos como faltantes. Hasta que la Fase 7
  recargue O95, `$campo('o95_tipo_ficha')` devolverá `id => null`
  correctamente — es el comportamiento diseñado, no un bug.
- `secciones-clinicas.php` (despacho por `orden`) no se resuelve con este
  partial — es un cambio aparte (despacho por nombre de sección), ya
  decidido en la sesión anterior, que va en el mismo commit que la
  corrección de `datos-fallecimiento-o95.php`.
