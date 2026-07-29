# Mapa ID → clave — Petición 2, Fase 1 (auditoría extendida)

Generado 2026-07-29. Cubre los 180 usos de `campo_NNNN` en `app/Views`,
`public/js/ficha.js` y las 3 comparaciones por ID de `CasosController.php`
(líneas 932, 947, 953).

**Método**, ampliado respecto al pedido original de la Fase 1 a instancias
del usuario, tras encontrar el primer caso de un ID que existe en la base
pero apunta a un campo distinto del que usa el código: no bastaba con
verificar que el ID *existe* en `campo_def`, había que verificar que
apunta al campo *correcto*. Para cada uno de los 180 usos se comparó:

- **PHP**: la etiqueta visible en el HTML (`<label>`, `placeholder`, texto
  de opción) contra `campo_def.etiqueta` / `campo_def.clave` de esa ID.
- **JS** (`ficha.js`, sin etiqueta visible): el nombre de la función y la
  lógica que envuelve cada referencia (p. ej. `calcularFechaHoraIngresoO95`
  → debe leer fecha y hora de fallecimiento) contra la clave real.

Antes de auditar código se verificó si ya había datos capturados con esta
ID que pudieran estar corruptos:

```sql
SELECT e.cie10, COUNT(*) AS valores
FROM caso_valor cv
JOIN campo_def cd ON cd.id = cv.campo_def_id
JOIN seccion_def sd ON sd.id = cd.seccion_id
JOIN enfermedad e ON e.id = sd.enfermedad_id
GROUP BY e.cie10;
```

Resultado: **0 filas**, para cualquier enfermedad — `caso_valor` está
completamente vacío en toda la base (no solo para O95). El único `caso`
real es de A95 (Fiebre amarilla), sin valores capturados. **No hay datos
ya corrompidos por los hallazgos de este documento.**

---

## Resumen

| Categoría | Cantidad | Significa |
|---|---|---|
| `ok` | 162 | La ID usada en el código apunta hoy al campo correcto. Sustitución mecánica segura. |
| `divergente` | 10 | La ID **existe** en la base pero apunta a un campo **distinto** del que el código usa (etiqueta o lógica no coinciden). Requiere corrección, no solo sustitución. |
| `muerto_con_sucesor` | 4 | La ID no existe, pero se identificó con certeza el campo que la reemplazó. |
| `muerto_sin_sucesor` | 2 | La ID no existe y **no hay ninguna clave equivalente en ningún lado** de la base. Código inalcanzable. |
| `muerto_no_universal` | 1 | La ID no existe; el campo que representa solo está definido para 8 de las 24 fichas, cada una con su propia clave — no hay una única clave "correcta" que sustituya la ID. |
| `muerto_inerte` | 1 | La ID no existe, pero la referencia es una condición redundante (`OR`) que nunca cambia el resultado; sin efecto observable. |

**162 de 180 (90%)** están listas para sustitución mecánica directa
(ID → su propia clave). Las 18 restantes necesitan una decisión puntual;
están detalladas abajo y marcadas en la tabla completa.

---

## Hallazgo mayor: `datos-fallecimiento-o95.php` + su espejo en `ficha.js`

**14 de las 18 divergencias/muertes con sucesor caen en una sola sección**:
"Datos del fallecimiento (Anexo 1)" de O95, y su lógica correspondiente en
`ficha.js` (funciones `actualizarDatosFallecimientoO95`,
`calcularFechaHoraIngresoO95`, `sincronizarIpessPnpUbigeoO95`).

Ejemplo concreto de lo que pasa hoy: el campo etiquetado "Hora de
fallecimiento" (`<input type="time" name="campo_16116">`) usa la ID
16116, que en la base de hoy es en realidad `o95_fecha_de_fallecimiento`
(tipo `FECHA`). La hora nunca se guarda donde dice guardarse — y si
llegara a guardarse, se guardaría en la fila que en realidad es "Fecha de
fallecimiento" de otro registro.

**El desfase no es uniforme** (se verificó explícitamente, como pediste,
para no asumir un solo error de generación):

- **10 casos con desfase exactamente `-1`**: la ID usada corresponde al
  campo que ocupa la posición inmediatamente anterior en el orden real
  (hora, tipo de EE.SS., días/horas/minutos de permanencia, especificar
  lugar, departamento/provincia/distrito del ubigeo de fallecimiento).
- **1 caso con desfase `-2`** ("Nombre del establecimiento"): no es parte
  de la cadena de -1, es un salto propio.
- **4 casos sin sucesor adyacente** (`muerto_con_sucesor`): las IDs viejas
  14306, 14321, 14322, 14323 no están "cerca" numéricamente de sus
  reemplazos (16116, 16128, 16129, 16130) — son de una generación anterior
  de `campo_def`, reemplazadas por completo cuando se amplió la sección,
  no corridas de a una.

Conclusión operativa (igual a la que ya intuías): **no es un solo error
de generación corregible con un `-1` mecánico en todo el archivo.** Cada
campo de esta sección tiene que revisarse individualmente contra el PDF
MINSA al migrarlo en la Fase 3. `ficha.js` y este `.php` deben corregirse
**juntos, en el mismo cambio**: el JS lee del DOM los mismos `name=` que
hoy pinta el PHP (aunque esos `name=` no correspondan a ningún
`campo_def.id` real), así que el cálculo automático de "fecha/hora de
ingreso" sigue funcionando visualmente en el navegador ahora mismo — el
problema es exclusivamente que nada de esta sección se está guardando en
`caso_valor` con el campo correcto.

---

## Los otros 4 casos fuera de esa sección

### `CasosController.php:947` y `:953` — 14301 y 14302, código inalcanzable

```php
if ($campoId === 14301 && !empty($_POST['hora_notificacion'])) { ... }
if ($campoId === 14302 && !empty($_POST['identificado_por'])) { ... }
```

`$campos` (línea 925) sale de `CampoDef::porEnfermedad()`, indexado
estrictamente por `campo_def.id` real. Ninguna fila de `campo_def`, en
ninguna de las 24 fichas, tiene clave `hora_notificacion` ni
`identificado_por` — se buscó explícitamente y no existe. Estas dos
condiciones **nunca se cumplen, para ninguna ficha, hoy**. No es que la ID
esté desactualizada: es que el campo destino fue eliminado del esquema
sin dejar sucesor. `app/Views/partials/notificacion-fechas-o95.php`
sigue renderizando los inputs (`name="hora_notificacion"`,
`name="identificado_por"`, con nombres literales, no `campo_NNNN`) — el
usuario los llena, pero el valor nunca llega a `caso_valor`. No hay una
clave a la que migrar esto por sustitución simple; hace falta decidir si
se recrea el campo en el manifiesto/esquema o si se retira el código.

### `fichas/editar.php:91` y `nueva/index.php:128` — 16109, "N.° H.C."

No es una ID desactualizada de una sola ficha: es un campo que **solo
existe en 8 de las 24 fichas**, cada una con su propia clave distinta
(`b05_n_de_historia_clinica`, `a37_0_n_historia_clinica`, `a95_n_h_c`,
`y59_0_n_de_historia_clinica`, `b57_n_h_c`, `a35_n_h_c`, `a33_n_h_c`,
`a80_n_h_c`). **B26 y O95 — las dos fichas de la prueba de fuego de la
Fase 7 — no tienen este campo en absoluto.** El resolvedor por clave de
la Fase 2 no puede resolver esto con una sola clave hardcodeada como los
demás campos: necesita, como mínimo, tolerar que la clave no exista para
la mayoría de fichas (ya previsto en el diseño del resolvedor) y alguien
tiene que decidir si el input debe dejar de mostrarse cuando la ficha no
tiene el campo, en vez de mostrarse siempre y no guardar nunca.

### `ficha.js:97` — 15672, inerte

```js
if (cieText.indexOf('A80') === -1 && !document.querySelector('[name="campo_15672"]')) return;
```

El `querySelector` nunca encuentra nada (15672 no existe en ningún
`name=` del HTML). La condición real ya la resuelve
`cieText.indexOf('A80')`; este `OR` es inofensivo pero muerto. Se puede
retirar sin cambiar el comportamiento.

### `ficha.js:2658` y `:2817` — 14300, ID de otra ficha

```js
var selLugarDef = document.getElementById('o95LugarFallecimientoSel') || document.querySelector('[name="campo_14300"]');
```

14300 sí existe en la base — pero es `v99_aseguradora` ("Aseguradora"),
de **V99 (accidentes de tránsito)**, no de O95. Es un *fallback* de un
*fallback* (`getElementById` primero); en la práctica, para un caso real
de O95, nunca hay un `caso_valor` con `campo_def_id=14300` (esa fila
pertenece a la ficha V99), así que este fallback siempre lee vacío. Bajo
riesgo práctico, pero es una referencia cruzada entre fichas que no
debería existir — se marca como `ok`-por-ID en la tabla porque 14300 en
sí es válido, pero el *uso* que se le da en O95 es incorrecto y conviene
limpiarlo en la Fase 3/4 en vez de migrarlo tal cual.

---

## Claves duplicadas dentro de una misma ficha (punto 5 de la Fase 1 original)

La petición original pide detenerse si aparecen. Aparecen, pero **en
ninguna de las tres fichas de la prueba de fuego** (B26, O95, B05) —
B26/O95/B05 están limpias. Afecta a otras 4 fichas, siempre con el mismo
patrón: la misma clave repetida en dos secciones paralelas de la misma
ficha (víctima/agresor, madre/niño, etc.):

| Ficha | Clave duplicada | Sección A | Sección B |
|---|---|---|---|
| A33 | `a33_atendido_por` | Cuadro clínico (id 13972) | Atención del parto (id 13989) |
| A80 | `a80_realizado_por` | Examen físico (id 15696) | Electromiografía (id 15750) |
| Y07 | `y07_completa_incompleta`, `y07_grado_de_instruccion`, `y07_ocupacion`, `y07_tiene_empleo_remunerado` | Datos de la persona agredida | Datos de la persona agresora |
| Z21 | `z21_abandono_terapia_arv`, `z21_fecha_de_inicio_de_arv`, `z21_recibio_arv` | Sección I — Gestante con VIH | Sección II — Niño nacido expuesto al VIH |

No bloquea esta petición (su alcance es B26/O95/B05), pero sí es un
problema latente para el resolvedor por clave de la Fase 2: si alguna vez
se migra una vista de A33, A80, Y07 o Z21, `$campo('y07_ocupacion')` sería
ambiguo — dos filas de `campo_def` calzan. El manifiesto necesita un
prefijo de sección o un sufijo por rol (`_agredida`/`_agresora`,
`_gestante`/`_nino`) antes de que esas 4 fichas puedan migrarse con el
mismo mecanismo. Queda anotado, no corregido aquí.

---

## Tabla completa (180 filas)

`Desfase` = `ID usada − ID correcta`. `Divergencia` vacío = sin problema,
listo para sustitución mecánica por clave.

| ID | CIE-10 | Sección | Etiqueta / uso | Clave según la ID usada | Clave correcta | ID correcto | Desfase | Divergencia | Archivos:líneas |
|---|---|---|---|---|---|---|---|---|---|
| 13708 | B26 | Cuadro clínico | ¿Presentó inflamación de glándulas parótidas? | `b26_presento_inflamacion_de_glandulas_parotidas` | `b26_presento_inflamacion_de_glandulas_parotidas` | 13708 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:103<br>app/Views/partials/cuadro-clinico-b26.php:107<br>public/js/ficha.js:2778<br>public/js/ficha.js:334 |
| 13709 | B26 | Cuadro clínico | Fecha de inicio de parotiditis | `b26_fecha_de_inicio_de_parotiditis` | `b26_fecha_de_inicio_de_parotiditis` | 13709 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:119 |
| 13710 | B26 | Cuadro clínico | N.° de días de duración | `b26_n_de_dias_de_duracion` | `b26_n_de_dias_de_duracion` | 13710 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:125 |
| 13711 | B26 | Cuadro clínico | Localización | `b26_localizacion` | `b26_localizacion` | 13711 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:133<br>app/Views/partials/cuadro-clinico-b26.php:137 |
| 13712 | B26 | Cuadro clínico | Inflamación de glándulas submandibulares | `b26_inflamacion_de_glandulas_submandibulares` | `b26_inflamacion_de_glandulas_submandibulares` | 13712 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:155<br>app/Views/partials/cuadro-clinico-b26.php:159 |
| 13713 | B26 | Cuadro clínico | Inflamación de glándulas sublinguales | `b26_inflamacion_de_glandulas_sublinguales` | `b26_inflamacion_de_glandulas_sublinguales` | 13713 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:170<br>app/Views/partials/cuadro-clinico-b26.php:174 |
| 13714 | B26 | Complicaciones | Orquitis | `b26_orquitis` | `b26_orquitis` | 13714 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:192<br>app/Views/partials/cuadro-clinico-b26.php:198 |
| 13715 | B26 | Complicaciones | Ooforitis | `b26_ooforitis` | `b26_ooforitis` | 13715 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:206<br>app/Views/partials/cuadro-clinico-b26.php:212 |
| 13716 | B26 | Complicaciones | Pérdida de audición | `b26_perdida_de_audicion` | `b26_perdida_de_audicion` | 13716 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:220<br>app/Views/partials/cuadro-clinico-b26.php:226 |
| 13717 | B26 | Complicaciones | Encefalitis | `b26_encefalitis` | `b26_encefalitis` | 13717 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:234<br>app/Views/partials/cuadro-clinico-b26.php:240 |
| 13718 | B26 | Complicaciones | Meningitis | `b26_meningitis` | `b26_meningitis` | 13718 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:248<br>app/Views/partials/cuadro-clinico-b26.php:254 |
| 13719 | B26 | Complicaciones | Otras | `b26_otras` | `b26_otras` | 13719 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:262<br>app/Views/partials/cuadro-clinico-b26.php:269<br>app/Views/partials/cuadro-clinico-b26.php:275 |
| 13720 | B26 | Hospitalización y egreso | Hospitalización | `b26_hospitalizacion` | `b26_hospitalizacion` | 13720 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:293<br>app/Views/partials/cuadro-clinico-b26.php:297<br>public/js/ficha.js:2778<br>public/js/ficha.js:357 |
| 13721 | B26 | Hospitalización y egreso | Establecimiento | `b26_establecimiento` | `b26_establecimiento` | 13721 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:309 |
| 13722 | B26 | Hospitalización y egreso | Fecha de hospitalización | `b26_fecha_de_hospitalizacion` | `b26_fecha_de_hospitalizacion` | 13722 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:315 |
| 13723 | B26 | Hospitalización y egreso | N.° de días | `b26_n_de_dias` | `b26_n_de_dias` | 13723 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:321 |
| 13724 | B26 | Hospitalización y egreso | Condición de egreso | `b26_condicion_de_egreso` | `b26_condicion_de_egreso` | 13724 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:336<br>app/Views/partials/cuadro-clinico-b26.php:340<br>app/Views/partials/cuadro-clinico-b26.php:344<br>app/Views/partials/cuadro-clinico-b26.php:348<br>public/js/ficha.js:2778<br>public/js/ficha.js:368 |
| 13725 | B26 | Hospitalización y egreso | Fecha de egreso | `b26_fecha_de_egreso` | `b26_fecha_de_egreso` | 13725 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:360 |
| 13726 | B26 | Hospitalización y egreso | Referido a | `b26_referido_a` | `b26_referido_a` | 13726 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:370 |
| 13727 | B26 | Hospitalización y egreso | Causa de muerte | `b26_causa_de_muerte` | `b26_causa_de_muerte` | 13727 | 0 |  | app/Views/partials/cuadro-clinico-b26.php:380 |
| 13728 | B26 | Lugar probable de infección | ¿En las últimas 2 a 4 semanas estuvo en contacto con otro caso de parotiditis? | `b26_en_las_ultimas_2_a_4_semanas_estuvo_en_contacto_con` | `b26_en_las_ultimas_2_a_4_semanas_estuvo_en_contacto_con` | 13728 | 0 |  | app/Views/partials/lugar-probable-infeccion-b26.php:172<br>app/Views/partials/lugar-probable-infeccion-b26.php:176<br>app/Views/partials/lugar-probable-infeccion-b26.php:180<br>public/js/ficha.js:2775<br>public/js/ficha.js:287 |
| 13729 | B26 | Lugar probable de infección | Contactos por lugar | `b26_contactos_por_lugar` | `b26_contactos_por_lugar` | 13729 | 0 |  | app/Controllers/CasosController.php:932 |
| 13730 | B26 | Lugar probable de infección | ¿Tuvo contacto con gestante? | `b26_tuvo_contacto_con_gestante` | `b26_tuvo_contacto_con_gestante` | 13730 | 0 |  | app/Views/partials/lugar-probable-infeccion-b26.php:216<br>app/Views/partials/lugar-probable-infeccion-b26.php:220<br>app/Views/partials/lugar-probable-infeccion-b26.php:224<br>public/js/ficha.js:2775<br>public/js/ficha.js:297 |
| 13731 | B26 | Lugar probable de infección | Trimestre de gestación (contacto) | `b26_trimestre_de_gestacion_contacto` | `b26_trimestre_de_gestacion_contacto` | 13731 | 0 |  | app/Views/partials/lugar-probable-infeccion-b26.php:242 |
| 14300 | V99 | Datos del vehículo | Aseguradora | `v99_aseguradora` | `v99_aseguradora` | 14300 | 0 |  | public/js/ficha.js:2658<br>public/js/ficha.js:2817 |
| 14301 | — | — | — | `(no existe)` | `—` | — | — | **muerto_sin_sucesor** — Ninguna clave "hora_notificacion" existe en ninguna ficha; $campoId===14301 es codigo inalcanzable (no existe fila campo_def con ese id, para ninguna enfermedad) | app/Controllers/CasosController.php:947 |
| 14302 | — | — | — | `(no existe)` | `—` | — | — | **muerto_sin_sucesor** — Ninguna clave "identificado_por" existe en ninguna ficha; mismo problema que 14301 | app/Controllers/CasosController.php:953 |
| 14304 | O95 | Datos del fallecimiento (Anexo 1) | Momento del fallecimiento | `o95_momento_del_fallecimiento` | `o95_momento_del_fallecimiento` | 14304 | 0 |  | app/Views/partials/datos-fallecimiento-o95.php:47<br>public/js/ficha.js:2479<br>public/js/ficha.js:2845 |
| 14305 | O95 | Datos del fallecimiento (Anexo 1) | Edad gestacional (Semanas) | `o95_edad_gestacional_al_momento_del_fallecimiento` | `o95_edad_gestacional_al_momento_del_fallecimiento` | 14305 | 0 |  | app/Views/partials/datos-fallecimiento-o95.php:76 |
| 14306 | — | — | — | `(no existe)` | `o95_fecha_de_fallecimiento` | 16116 | — | **muerto_con_sucesor** — Reemplazo integral (no adyacente), no un simple corrimiento | app/Views/partials/datos-fallecimiento-o95.php:92<br>public/js/ficha.js:1789<br>public/js/ficha.js:1971 |
| 14307 | O95 | Datos del fallecimiento (Anexo 1) | ¿Dónde ocurrió el fallecimiento? | `o95_lugar_del_fallecimiento` | `o95_lugar_del_fallecimiento` | 14307 | 0 |  | app/Views/partials/datos-fallecimiento-o95.php:109<br>public/js/ficha.js:1841<br>public/js/ficha.js:1942<br>public/js/ficha.js:1968 |
| 14309 | O95 | Referencia (Anexo 1) | ¿Referida? | `o95_referida` | `o95_referida` | 14309 | 0 |  | app/Views/partials/referencia-o95.php:66<br>public/js/ficha.js:1975<br>public/js/ficha.js:2849 |
| 14310 | O95 | Referencia (Anexo 1) | EE.SS. de origen de la referencia | `o95_ee_ss_de_origen_de_la_referencia` | `o95_ee_ss_de_origen_de_la_referencia` | 14310 | 0 |  | app/Views/partials/referencia-o95.php:78 |
| 14311 | O95 | Causas de defunción (Anexo 1) | Causa final probable | `o95_causa_final_probable` | `o95_causa_final_probable` | 14311 | 0 |  | app/Views/partials/causas-defuncion-o95.php:51 |
| 14312 | O95 | Causas de defunción (Anexo 1) | Causa intermedia probable | `o95_causa_intermedia_probable` | `o95_causa_intermedia_probable` | 14312 | 0 |  | app/Views/partials/causas-defuncion-o95.php:64 |
| 14313 | O95 | Causas de defunción (Anexo 1) | Causa básica probable | `o95_causa_basica_probable` | `o95_causa_basica_probable` | 14313 | 0 |  | app/Views/partials/causas-defuncion-o95.php:77 |
| 14314 | O95 | Causas de defunción (Anexo 1) | Causa genérica | `o95_causa_generica` | `o95_causa_generica` | 14314 | 0 |  | app/Views/partials/causas-defuncion-o95.php:122<br>public/js/ficha.js:2297 |
| 14315 | O95 | Causas de defunción (Anexo 1) | Clasificación inicial | `o95_clasificacion_inicial` | `o95_clasificacion_inicial` | 14315 | 0 |  | app/Views/partials/causas-defuncion-o95.php:146<br>public/js/ficha.js:2109<br>public/js/ficha.js:2804 |
| 14316 | O95 | Datos del fallecimiento (Anexo 1) | Idioma | `o95_idioma` | `o95_idioma` | 14316 | 0 |  | app/Views/partials/datos-paciente-nucleo.php:94<br>public/js/ficha.js:1963 |
| 14317 | O95 | Datos del fallecimiento (Anexo 1) | Nivel educativo | `o95_nivel_educativo` | `o95_nivel_educativo` | 14317 | 0 |  | app/Views/partials/datos-paciente-nucleo.php:114 |
| 14318 | O95 | Datos del fallecimiento (Anexo 1) | Estado civil | `o95_estado_civil` | `o95_estado_civil` | 14318 | 0 |  | app/Views/partials/datos-paciente-nucleo.php:131 |
| 14319 | O95 | Datos del fallecimiento (Anexo 1) | Tipo de seguro | `o95_tipo_de_seguro` | `o95_tipo_de_seguro` | 14319 | 0 |  | app/Views/partials/datos-paciente-nucleo.php:154<br>public/js/ficha.js:1963 |
| 14320 | O95 | Datos del fallecimiento (Anexo 1) | Fase del puerperio | `o95_fase_del_puerperio_en_que_fallecio` | `o95_fase_del_puerperio_en_que_fallecio` | 14320 | 0 |  | app/Views/partials/datos-fallecimiento-o95.php:60 |
| 14321 | — | — | — | `(no existe)` | `o95_categoria_del_ee_ss` | 16128 | — | **muerto_con_sucesor** — Anexo 2; reemplazo integral | app/Views/partials/datos-fallecimiento-o95.php:217 |
| 14322 | — | — | — | `(no existe)` | `o95_fecha_y_hora_de_ingreso_al_ee_ss` | 16129 | — | **muerto_con_sucesor** — Anexo 2; reemplazo integral | app/Views/partials/datos-fallecimiento-o95.php:231 |
| 14323 | — | — | — | `(no existe)` | `o95_responsable_de_la_atencion` | 16130 | — | **muerto_con_sucesor** — Anexo 2; reemplazo integral | app/Views/partials/datos-fallecimiento-o95.php:241 |
| 14324 | O95 | Antecedentes patológicos y obstétricos | Antecedentes patológicos | `o95_antecedentes_patologicos` | `o95_antecedentes_patologicos` | 14324 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:67 |
| 14325 | O95 | Antecedentes patológicos y obstétricos | N.° de gestaciones previas | `o95_n_de_gestaciones_previas` | `o95_n_de_gestaciones_previas` | 14325 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:96 |
| 14326 | O95 | Antecedentes patológicos y obstétricos | N.° de partos | `o95_n_de_partos` | `o95_n_de_partos` | 14326 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:103 |
| 14327 | O95 | Antecedentes patológicos y obstétricos | N.° de cesáreas | `o95_n_de_cesareas` | `o95_n_de_cesareas` | 14327 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:110 |
| 14328 | O95 | Antecedentes patológicos y obstétricos | N.° de abortos | `o95_n_de_abortos` | `o95_n_de_abortos` | 14328 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:117 |
| 14329 | O95 | Antecedentes patológicos y obstétricos | N.° de nacidos vivos | `o95_n_de_nacidos_vivos` | `o95_n_de_nacidos_vivos` | 14329 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:124 |
| 14330 | O95 | Antecedentes patológicos y obstétricos | N.° de nacidos muertos | `o95_n_de_nacidos_muertos` | `o95_n_de_nacidos_muertos` | 14330 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:131 |
| 14331 | O95 | Antecedentes patológicos y obstétricos | N.° de hijos que viven | `o95_n_de_hijos_que_viven` | `o95_n_de_hijos_que_viven` | 14331 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:138 |
| 14333 | O95 | Antecedentes patológicos y obstétricos | Uso de método anticonceptivo previo | `o95_uso_de_metodo_anticonceptivo_previo` | `o95_uso_de_metodo_anticonceptivo_previo` | 14333 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:179 |
| 14334 | O95 | Atención prenatal | ¿Recibió APN? | `o95_recibio_apn` | `o95_recibio_apn` | 14334 | 0 |  | app/Views/partials/atencion-prenatal-o95.php:29<br>app/Views/partials/atencion-prenatal-o95.php:33<br>public/js/ficha.js:2861 |
| 14335 | O95 | Atención prenatal | Primera atención (trimestre) | `o95_primera_atencion_trimestre` | `o95_primera_atencion_trimestre` | 14335 | 0 |  | app/Views/partials/atencion-prenatal-o95.php:45 |
| 14336 | O95 | Atención prenatal | Número de APN | `o95_numero_de_apn` | `o95_numero_de_apn` | 14336 | 0 |  | app/Views/partials/atencion-prenatal-o95.php:57 |
| 14337 | O95 | Atención prenatal | EE.SS. con mayor cantidad de atenciones | `o95_ee_ss_con_mayor_cantidad_de_atenciones` | `o95_ee_ss_con_mayor_cantidad_de_atenciones` | 14337 | 0 |  | app/Views/partials/atencion-prenatal-o95.php:66 |
| 14338 | O95 | Atención prenatal | ¿Se realizaron visitas domiciliarias? | `o95_se_realizaron_visitas_domiciliarias` | `o95_se_realizaron_visitas_domiciliarias` | 14338 | 0 |  | app/Views/partials/atencion-prenatal-o95.php:110<br>app/Views/partials/atencion-prenatal-o95.php:114<br>public/js/ficha.js:2862 |
| 14339 | O95 | Atención prenatal | Número de visitas domiciliarias | `o95_numero_de_visitas_domiciliarias` | `o95_numero_de_visitas_domiciliarias` | 14339 | 0 |  | app/Views/partials/atencion-prenatal-o95.php:123 |
| 14340 | O95 | Atención prenatal | ¿Se realizó plan de parto completo? | `o95_se_realizo_plan_de_parto_completo` | `o95_se_realizo_plan_de_parto_completo` | 14340 | 0 |  | app/Views/partials/atencion-prenatal-o95.php:133<br>app/Views/partials/atencion-prenatal-o95.php:137 |
| 14341 | O95 | Atención prenatal | Responsable de la APN | `o95_responsable_de_la_apn` | `o95_responsable_de_la_apn` | 14341 | 0 |  | app/Views/partials/atencion-prenatal-o95.php:87<br>public/js/ficha.js:2864 |
| 14342 | O95 | Complicaciones | Complicaciones del embarazo | `o95_complicaciones_del_embarazo` | `o95_complicaciones_del_embarazo` | 14342 | 0 |  | app/Views/partials/complicaciones-o95.php:83 |
| 14343 | O95 | Complicaciones | Complicaciones del parto | `o95_complicaciones_del_parto` | `o95_complicaciones_del_parto` | 14343 | 0 |  | app/Views/partials/complicaciones-o95.php:121 |
| 14344 | O95 | Complicaciones | Complicaciones del puerperio | `o95_complicaciones_del_puerperio` | `o95_complicaciones_del_puerperio` | 14344 | 0 |  | app/Views/partials/complicaciones-o95.php:159 |
| 14346 | O95 | Referencia (Anexo 1) | N.° de referencias institucionales | `o95_n_de_referencias_institucionales` | `o95_n_de_referencias_institucionales` | 14346 | 0 |  | app/Views/partials/referencia-o95.php:114 |
| 14347 | O95 | Hospitalizaciones | ¿Hospitalizaciones en la gestación/puerperio? | `o95_hospitalizaciones_en_la_gestacion_puerperio` | `o95_hospitalizaciones_en_la_gestacion_puerperio` | 14347 | 0 |  | app/Views/partials/hospitalizaciones-o95.php:20<br>app/Views/partials/hospitalizaciones-o95.php:24<br>public/js/ficha.js:2858 |
| 14348 | O95 | Hospitalizaciones | Cuántas hospitalizaciones | `o95_cuantas_hospitalizaciones` | `o95_cuantas_hospitalizaciones` | 14348 | 0 |  | app/Views/partials/hospitalizaciones-o95.php:34 |
| 14349 | O95 | Hospitalizaciones | ¿Requirió transfusión de sangre? | `o95_requirio_transfusion_de_sangre` | `o95_requirio_transfusion_de_sangre` | 14349 | 0 |  | app/Views/partials/hospitalizaciones-o95.php:43<br>app/Views/partials/hospitalizaciones-o95.php:47 |
| 14350 | O95 | Hospitalizaciones | ¿Expansores plasmáticos? | `o95_expansores_plasmaticos` | `o95_expansores_plasmaticos` | 14350 | 0 |  | app/Views/partials/hospitalizaciones-o95.php:58<br>app/Views/partials/hospitalizaciones-o95.php:62 |
| 14351 | O95 | Parto o aborto | Fecha de parto o aborto | `o95_fecha_de_parto_o_aborto` | `o95_fecha_de_parto_o_aborto` | 14351 | 0 |  | app/Views/partials/parto-aborto-o95.php:39 |
| 14352 | O95 | Parto o aborto | Lugar de parto o aborto | `o95_lugar_de_parto_o_aborto` | `o95_lugar_de_parto_o_aborto` | 14352 | 0 |  | app/Views/partials/parto-aborto-o95.php:59 |
| 14353 | O95 | Parto o aborto | Tipo de parto | `o95_tipo_de_parto` | `o95_tipo_de_parto` | 14353 | 0 |  | app/Views/partials/parto-aborto-o95.php:93 |
| 14354 | O95 | Parto o aborto | Responsable de la atención del parto o aborto | `o95_responsable_de_la_atencion_del_parto_o_aborto` | `o95_responsable_de_la_atencion_del_parto_o_aborto` | 14354 | 0 |  | app/Views/partials/parto-aborto-o95.php:108 |
| 14355 | O95 | Parto o aborto | ¿Necropsia? | `o95_necropsia` | `o95_necropsia` | 14355 | 0 |  | app/Views/partials/parto-aborto-o95.php:140<br>app/Views/partials/parto-aborto-o95.php:144<br>public/js/ficha.js:2844 |
| 14356 | O95 | Parto o aborto | Diagnóstico / causa CIE-10 (necropsia) | `o95_diagnostico_causa_cie_10_necropsia` | `o95_diagnostico_causa_cie_10_necropsia` | 14356 | 0 |  | app/Views/partials/parto-aborto-o95.php:154 |
| 14357 | O95 | Entorno social y comunitario | ¿Identificaron signos de peligro? | `o95_identificaron_signos_de_peligro` | `o95_identificaron_signos_de_peligro` | 14357 | 0 |  | app/Views/partials/entorno-social-o95.php:56<br>app/Views/partials/entorno-social-o95.php:60<br>public/js/ficha.js:2820 |
| 14358 | O95 | Entorno social y comunitario | Persona que identificó signos de peligro | `o95_persona_que_identifico_signos_de_peligro` | `o95_persona_que_identifico_signos_de_peligro` | 14358 | 0 |  | app/Views/partials/entorno-social-o95.php:71 |
| 14359 | O95 | Entorno social y comunitario | ¿Buscaron ayuda? | `o95_buscaron_ayuda` | `o95_buscaron_ayuda` | 14359 | 0 |  | app/Views/partials/entorno-social-o95.php:94<br>app/Views/partials/entorno-social-o95.php:98<br>public/js/ficha.js:2822 |
| 14360 | O95 | Entorno social y comunitario | Quién tomó la decisión de buscar ayuda | `o95_quien_tomo_la_decision_de_buscar_ayuda` | `o95_quien_tomo_la_decision_de_buscar_ayuda` | 14360 | 0 |  | app/Views/partials/entorno-social-o95.php:109 |
| 14362 | O95 | Entorno social y comunitario | ¿Hubo dificultad con el acceso a servicios de salud? | `o95_hubo_dificultad_con_el_acceso_a_servicios_de_salud` | `o95_hubo_dificultad_con_el_acceso_a_servicios_de_salud` | 14362 | 0 |  | app/Views/partials/entorno-social-o95.php:151<br>app/Views/partials/entorno-social-o95.php:155<br>public/js/ficha.js:2824 |
| 14363 | O95 | Entorno social y comunitario | Especificar dificultad de acceso | `o95_especificar_dificultad_de_acceso` | `o95_especificar_dificultad_de_acceso` | 14363 | 0 |  | app/Views/partials/entorno-social-o95.php:177 |
| 14365 | O95 | Entorno social y comunitario | ¿Tuvo dificultades para ser atendida en el EE.SS.? | `o95_tuvo_dificultades_para_ser_atendida_en_el_ee_ss` | `o95_tuvo_dificultades_para_ser_atendida_en_el_ee_ss` | 14365 | 0 |  | app/Views/partials/entorno-social-o95.php:216<br>app/Views/partials/entorno-social-o95.php:220<br>public/js/ficha.js:2826 |
| 14366 | O95 | Entorno social y comunitario | Especificar dificultad de atención | `o95_especificar_dificultad_de_atencion` | `o95_especificar_dificultad_de_atencion` | 14366 | 0 |  | app/Views/partials/entorno-social-o95.php:243 |
| 14368 | O95 | Entorno social y comunitario | Persona que brindó la información | `o95_persona_que_brindo_la_informacion` | `o95_persona_que_brindo_la_informacion` | 14368 | 0 |  | app/Views/partials/entorno-social-o95.php:283 |
| 14369 | O95 | Datos comunitarios | Sintomatología o molestias | `o95_sintomatologia_o_molestias` | `o95_sintomatologia_o_molestias` | 14369 | 0 |  | app/Views/partials/datos-comunitarios-o95.php:82 |
| 14370 | O95 | Datos comunitarios | Maniobras usadas durante el parto | `o95_maniobras_usadas_durante_el_parto` | `o95_maniobras_usadas_durante_el_parto` | 14370 | 0 |  | app/Views/partials/datos-comunitarios-o95.php:113 |
| 14371 | O95 | Datos comunitarios | Maniobras usadas para retirar la placenta | `o95_maniobras_usadas_para_retirar_la_placenta` | `o95_maniobras_usadas_para_retirar_la_placenta` | 14371 | 0 |  | app/Views/partials/datos-comunitarios-o95.php:144 |
| 14373 | O95 | Datos comunitarios | Tipo de establecimiento más cercano | `o95_tipo_de_establecimiento_mas_cercano` | `o95_tipo_de_establecimiento_mas_cercano` | 14373 | 0 |  | app/Views/partials/datos-comunitarios-o95.php:183 |
| 14377 | O95 | Causas de defunción (Anexo 1) | Causa asociada | `o95_causa_asociada` | `o95_causa_asociada` | 14377 | 0 |  | app/Views/partials/causas-defuncion-o95.php:90 |
| 14379 | O95 | Causas de defunción (Anexo 1) | Clasificación final de la muerte | `o95_clasificacion_final_de_la_muerte` | `o95_clasificacion_final_de_la_muerte` | 14379 | 0 |  | app/Views/partials/causas-defuncion-o95.php:159<br>public/js/ficha.js:2108<br>public/js/ficha.js:2804 |
| 14380 | O95 | Las cuatro demoras | 1.ª demora: en la identificación del problema | `o95_1_demora_en_la_identificacion_del_problema` | `o95_1_demora_en_la_identificacion_del_problema` | 14380 | 0 |  | app/Views/partials/demoras-o95.php:23 |
| 14381 | O95 | Las cuatro demoras | 2.ª demora: en la decisión de buscar ayuda | `o95_2_demora_en_la_decision_de_buscar_ayuda` | `o95_2_demora_en_la_decision_de_buscar_ayuda` | 14381 | 0 |  | app/Views/partials/demoras-o95.php:31 |
| 14382 | O95 | Las cuatro demoras | 3.ª demora: en acceder a los servicios de salud | `o95_3_demora_en_acceder_a_los_servicios_de_salud` | `o95_3_demora_en_acceder_a_los_servicios_de_salud` | 14382 | 0 |  | app/Views/partials/demoras-o95.php:39 |
| 14383 | O95 | Las cuatro demoras | 4.ª demora: en recibir tratamiento adecuado y oportuno | `o95_4_demora_en_recibir_tratamiento_adecuado_y_oportuno` | `o95_4_demora_en_recibir_tratamiento_adecuado_y_oportuno` | 14383 | 0 |  | app/Views/partials/demoras-o95.php:47 |
| 15672 | — | — | — | `(no existe)` | `—` | — | — | **muerto_inerte** — Guard redundante en sincronizarDescartadoPfa(): la condicion real ya la resuelve el texto CIE "A80"; este OR nunca es true y no cambia el comportamiento | public/js/ficha.js:97 |
| 16052 | B05 | Antecedentes vacunales | Estado vacunal | `b05_estado_vacunal` | `b05_estado_vacunal` | 16052 | 0 |  | public/js/ficha.js:1532<br>public/js/ficha.js:1553 |
| 16072 | B05 | Investigación epidemiológica | Casas abiertas | `b05_casas_abiertas` | `b05_casas_abiertas` | 16072 | 0 |  | public/js/ficha.js:2939<br>public/js/ficha.js:2998<br>public/js/ficha.js:3007 |
| 16073 | B05 | Investigación epidemiológica | Casas cerradas | `b05_casas_cerradas` | `b05_casas_cerradas` | 16073 | 0 |  | public/js/ficha.js:2940<br>public/js/ficha.js:2998<br>public/js/ficha.js:3007 |
| 16074 | B05 | Investigación epidemiológica | Casas abandonadas | `b05_casas_abandonadas` | `b05_casas_abandonadas` | 16074 | 0 |  | public/js/ficha.js:2941<br>public/js/ficha.js:2998<br>public/js/ficha.js:3007 |
| 16075 | B05 | Investigación epidemiológica | Total de casas | `b05_total_de_casas` | `b05_total_de_casas` | 16075 | 0 |  | public/js/ficha.js:2942 |
| 16080 | B05 | Investigación epidemiológica | Número de vacunados en el bloqueo | `b05_numero_de_vacunados_en_el_bloqueo` | `b05_numero_de_vacunados_en_el_bloqueo` | 16080 | 0 |  | public/js/ficha.js:2963 |
| 16084 | B05 | Clasificación final | Clasificación | `b05_clasificacion` | `b05_clasificacion` | 16084 | 0 |  | public/js/ficha.js:2909<br>public/js/ficha.js:2929 |
| 16095 | B05 | Lugar probable de infección | Paciente viajó entre los 7 a 30 días antes del inicio de la erupción | `paciente_viajo_7_30_dias` | `paciente_viajo_7_30_dias` | 16095 | 0 |  | app/Views/partials/secciones-clinicas.php:107<br>public/js/ficha.js:1565<br>public/js/ficha.js:1586 |
| 16104 | B05 | Investigación epidemiológica | Número de vacunados en bloqueo (< 1 año) | `vacunados_bloqueo_menor_1` | `vacunados_bloqueo_menor_1` | 16104 | 0 |  | public/js/ficha.js:2959<br>public/js/ficha.js:2999<br>public/js/ficha.js:3008 |
| 16105 | B05 | Investigación epidemiológica | Número de vacunados en bloqueo (1 - 4 años) | `vacunados_bloqueo_1_4` | `vacunados_bloqueo_1_4` | 16105 | 0 |  | public/js/ficha.js:2960<br>public/js/ficha.js:2999<br>public/js/ficha.js:3008 |
| 16106 | B05 | Investigación epidemiológica | Número de vacunados en bloqueo (5 - 14 años) | `vacunados_bloqueo_5_14` | `vacunados_bloqueo_5_14` | 16106 | 0 |  | public/js/ficha.js:2961<br>public/js/ficha.js:2999<br>public/js/ficha.js:3008 |
| 16107 | B05 | Investigación epidemiológica | Número de vacunados en bloqueo (> 15 años) | `vacunados_bloqueo_mayor_15` | `vacunados_bloqueo_mayor_15` | 16107 | 0 |  | public/js/ficha.js:2962<br>public/js/ficha.js:2999<br>public/js/ficha.js:3008 |
| 16108 | B05 | Investigación epidemiológica | Fecha de último día de seguimiento de contactos | `b05_fecha_ultimo_dia_seguimiento_contactos` | `b05_fecha_ultimo_dia_seguimiento_contactos` | 16108 | 0 |  | app/Views/partials/secciones-clinicas.php:148 |
| 16109 | — | — | — | `(no existe)` | `—` | — | — | **muerto_no_universal** — N.HC existe solo en 8/24 fichas (B05, A37.0, A95, Y59.0, B57, A35, A33, A80), cada una con su propia clave. B26 y O95 NO tienen este campo en absoluto | app/Views/fichas/editar.php:91<br>app/Views/nueva/index.php:128 |
| 16110 | O95 | Datos del fallecimiento (Anexo 1) | Grupo étnico | `o95_grupo_etnico` | `o95_grupo_etnico` | 16110 | 0 |  | app/Views/partials/datos-paciente-nucleo.php:71<br>public/js/ficha.js:1754 |
| 16111 | O95 | Datos del fallecimiento (Anexo 1) | Etnia / Pueblo étnico | `o95_etnia_pueblo_etnico` | `o95_etnia_pueblo_etnico` | 16111 | 0 |  | app/Views/partials/datos-paciente-nucleo.php:84 |
| 16112 | O95 | Datos del fallecimiento (Anexo 1) | Ocupación | `o95_ocupacion` | `o95_ocupacion` | 16112 | 0 |  | app/Views/partials/datos-paciente-nucleo.php:147 |
| 16113 | O95 | Datos del fallecimiento (Anexo 1) | Especificar otro idioma | `o95_idioma_otra` | `o95_idioma_otra` | 16113 | 0 |  | app/Views/partials/datos-paciente-nucleo.php:107 |
| 16114 | O95 | Datos del fallecimiento (Anexo 1) | Especificar otro tipo de seguro | `o95_tipo_de_seguro_otro` | `o95_tipo_de_seguro_otro` | 16114 | 0 |  | app/Views/partials/datos-paciente-nucleo.php:168 |
| 16115 | O95 | Datos del fallecimiento (Anexo 1) | Edad gestacional desconocida | `o95_edad_gestacional_desconocida` | `o95_edad_gestacional_desconocida` | 16115 | 0 |  | app/Views/partials/datos-fallecimiento-o95.php:81 |
| 16116 | O95 | Datos del fallecimiento (Anexo 1) | Fecha de fallecimiento | `o95_fecha_de_fallecimiento` | `o95_hora_de_fallecimiento` | 16117 | -1 | **divergente** — HTML dice "Hora de fallecimiento" (type=time); la ID 16116 es hoy o95_fecha_de_fallecimiento (FECHA) | app/Views/partials/datos-fallecimiento-o95.php:98<br>public/js/ficha.js:1790<br>public/js/ficha.js:1972 |
| 16117 | O95 | Datos del fallecimiento (Anexo 1) | Hora de fallecimiento | `o95_hora_de_fallecimiento` | `o95_tipo_eess_fallecimiento` | 16118 | -1 | **divergente** — HTML dice "Tipo de establecimiento de salud" (select); la ID 16117 es hoy o95_hora_de_fallecimiento (TEXTO) | app/Views/partials/datos-fallecimiento-o95.php:121 |
| 16118 | O95 | Datos del fallecimiento (Anexo 1) | Tipo de establecimiento de salud | `o95_tipo_eess_fallecimiento` | `o95_nombre_eess_fallecimiento` | 16120 | -2 | **divergente** — HTML dice "Nombre del establecimiento" (texto libre); la ID 16118 es hoy o95_tipo_eess_fallecimiento (SELECT) | app/Views/partials/datos-fallecimiento-o95.php:152 |
| 16119 | O95 | Datos del fallecimiento (Anexo 1) | Establecimiento Sanidad PNP | `o95_eess_fallecimiento_id` | `o95_eess_fallecimiento_id` | 16119 | 0 |  | app/Views/partials/datos-fallecimiento-o95.php:135 |
| 16120 | O95 | Datos del fallecimiento (Anexo 1) | Nombre del establecimiento | `o95_nombre_eess_fallecimiento` | `o95_fallecimiento_dep_id` | 16121 | -1 | **divergente** — input hidden de "departamento" del ubigeo; la ID 16120 es hoy o95_nombre_eess_fallecimiento | app/Views/partials/datos-fallecimiento-o95.php:272 |
| 16121 | O95 | Datos del fallecimiento (Anexo 1) | Departamento (Fallecimiento) | `o95_fallecimiento_dep_id` | `o95_fallecimiento_prov_id` | 16122 | -1 | **divergente** — input hidden de "provincia" del ubigeo; la ID 16121 es hoy o95_fallecimiento_dep_id | app/Views/partials/datos-fallecimiento-o95.php:273 |
| 16122 | O95 | Datos del fallecimiento (Anexo 1) | Provincia (Fallecimiento) | `o95_fallecimiento_prov_id` | `o95_fallecimiento_dist_id` | 16123 | -1 | **divergente** — select de "distrito" del ubigeo ($nombreCampoDistrito); la ID 16122 es hoy o95_fallecimiento_prov_id | app/Views/partials/datos-fallecimiento-o95.php:267 |
| 16123 | O95 | Datos del fallecimiento (Anexo 1) | Distrito (Fallecimiento) | `o95_fallecimiento_dist_id` | `o95_permanencia_dias` | 16124 | -1 | **divergente** — HTML dice "Dias" de permanencia; la ID 16123 es hoy o95_fallecimiento_dist_id (Distrito) | app/Views/partials/datos-fallecimiento-o95.php:164<br>public/js/ficha.js:1791<br>public/js/ficha.js:1998 |
| 16124 | O95 | Datos del fallecimiento (Anexo 1) | Permanencia (Días) | `o95_permanencia_dias` | `o95_permanencia_horas` | 16125 | -1 | **divergente** — HTML dice "Horas" de permanencia; la ID 16124 es hoy o95_permanencia_dias | app/Views/partials/datos-fallecimiento-o95.php:170<br>public/js/ficha.js:1792<br>public/js/ficha.js:1998 |
| 16125 | O95 | Datos del fallecimiento (Anexo 1) | Permanencia (Horas) | `o95_permanencia_horas` | `o95_permanencia_minutos` | 16126 | -1 | **divergente** — HTML dice "Minutos" de permanencia; la ID 16125 es hoy o95_permanencia_horas | app/Views/partials/datos-fallecimiento-o95.php:176<br>public/js/ficha.js:1793<br>public/js/ficha.js:1998 |
| 16126 | O95 | Datos del fallecimiento (Anexo 1) | Permanencia (Minutos) | `o95_permanencia_minutos` | `o95_lugar_fallecimiento_otro_especificar` | 16127 | -1 | **divergente** — HTML dice "Especificar lugar" (Otro/Trayecto); la ID 16126 es hoy o95_permanencia_minutos | app/Views/partials/datos-fallecimiento-o95.php:188<br>app/Views/partials/datos-fallecimiento-o95.php:198 |
| 16131 | O95 | Referencia (Anexo 1) | Departamento (Origen de la referencia) | `o95_referencia_dep_id` | `o95_referencia_dep_id` | 16131 | 0 |  | app/Views/partials/referencia-o95.php:95 |
| 16132 | O95 | Referencia (Anexo 1) | Provincia (Origen de la referencia) | `o95_referencia_prov_id` | `o95_referencia_prov_id` | 16132 | 0 |  | app/Views/partials/referencia-o95.php:96 |
| 16133 | O95 | Referencia (Anexo 1) | Distrito (Origen de la referencia) | `o95_referencia_dist_id` | `o95_referencia_dist_id` | 16133 | 0 |  | app/Views/partials/referencia-o95.php:97 |
| 16134 | O95 | Causas de defunción (Anexo 1) | CIE-10 Causa final | `o95_causa_final_cie10` | `o95_causa_final_cie10` | 16134 | 0 |  | app/Views/partials/causas-defuncion-o95.php:46 |
| 16135 | O95 | Causas de defunción (Anexo 1) | CIE-10 Causa intermedia | `o95_causa_intermedia_cie10` | `o95_causa_intermedia_cie10` | 16135 | 0 |  | app/Views/partials/causas-defuncion-o95.php:59 |
| 16136 | O95 | Causas de defunción (Anexo 1) | CIE-10 Causa básica | `o95_causa_basica_cie10` | `o95_causa_basica_cie10` | 16136 | 0 |  | app/Views/partials/causas-defuncion-o95.php:72 |
| 16137 | O95 | Causas de defunción (Anexo 1) | Especificar otra causa genérica | `o95_causa_generica_otra` | `o95_causa_generica_otra` | 16137 | 0 |  | app/Views/partials/causas-defuncion-o95.php:137 |
| 16138 | O95 | Antecedentes patológicos y obstétricos | Especificar otro antecedente patológico | `o95_antecedentes_patologicos_otra` | `o95_antecedentes_patologicos_otra` | 16138 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:77 |
| 16139 | O95 | Antecedentes patológicos y obstétricos | Período intergenésico (Años) | `o95_periodo_intergenesico_anios` | `o95_periodo_intergenesico_anios` | 16139 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:145 |
| 16140 | O95 | Antecedentes patológicos y obstétricos | Período intergenésico (Meses) | `o95_periodo_intergenesico_meses` | `o95_periodo_intergenesico_meses` | 16140 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:149 |
| 16141 | O95 | Antecedentes patológicos y obstétricos | Especificar otro método anticonceptivo | `o95_metodo_anticonceptivo_otro` | `o95_metodo_anticonceptivo_otro` | 16141 | 0 |  | app/Views/partials/antecedentes-patologicos-obstetricos-o95.php:189 |
| 16142 | O95 | Atención prenatal | Categoría del EE.SS. | `o95_categoria_eess_apn` | `o95_categoria_eess_apn` | 16142 | 0 |  | app/Views/partials/atencion-prenatal-o95.php:73 |
| 16143 | O95 | Atención prenatal | Especificar otro responsable de APN | `o95_responsable_apn_otro` | `o95_responsable_apn_otro` | 16143 | 0 |  | app/Views/partials/atencion-prenatal-o95.php:99 |
| 16144 | O95 | Complicaciones | Especificar otra complicación del embarazo | `o95_complicaciones_embarazo_otro` | `o95_complicaciones_embarazo_otro` | 16144 | 0 |  | app/Views/partials/complicaciones-o95.php:92 |
| 16145 | O95 | Complicaciones | Especificar otra complicación del parto | `o95_complicaciones_parto_otro` | `o95_complicaciones_parto_otro` | 16145 | 0 |  | app/Views/partials/complicaciones-o95.php:130 |
| 16146 | O95 | Complicaciones | Especificar otra complicación del puerperio | `o95_complicaciones_puerperio_otro` | `o95_complicaciones_puerperio_otro` | 16146 | 0 |  | app/Views/partials/complicaciones-o95.php:168 |
| 16147 | O95 | Complicaciones | ¿Tuvo complicaciones? | `o95_tuvo_complicaciones` | `o95_tuvo_complicaciones` | 16147 | 0 |  | app/Views/partials/complicaciones-o95.php:38<br>app/Views/partials/complicaciones-o95.php:42<br>app/Views/partials/complicaciones-o95.php:46<br>public/js/ficha.js:2867 |
| 16148 | O95 | Referencia (Anexo 1) | Fecha de ingreso al EE.SS. origen de la referencia | `o95_fecha_ingreso_eess_origen` | `o95_fecha_ingreso_eess_origen` | 16148 | 0 |  | app/Views/partials/referencia-o95.php:123 |
| 16149 | O95 | Referencia (Anexo 1) | Hora de ingreso al EE.SS. origen de la referencia | `o95_hora_ingreso_eess_origen` | `o95_hora_ingreso_eess_origen` | 16149 | 0 |  | app/Views/partials/referencia-o95.php:129 |
| 16150 | O95 | Referencia (Anexo 1) | Fecha de egreso del EE.SS. origen de la referencia | `o95_fecha_egreso_eess_origen` | `o95_fecha_egreso_eess_origen` | 16150 | 0 |  | app/Views/partials/referencia-o95.php:139 |
| 16151 | O95 | Referencia (Anexo 1) | Hora de egreso del EE.SS. origen de la referencia | `o95_hora_egreso_eess_origen` | `o95_hora_egreso_eess_origen` | 16151 | 0 |  | app/Views/partials/referencia-o95.php:145 |
| 16152 | O95 | Referencia (Anexo 1) | Tiempo de demora (días) | `o95_demora_referencia_dias` | `o95_demora_referencia_dias` | 16152 | 0 |  | app/Views/partials/referencia-o95.php:156 |
| 16153 | O95 | Referencia (Anexo 1) | Tiempo de demora (horas) | `o95_demora_referencia_horas` | `o95_demora_referencia_horas` | 16153 | 0 |  | app/Views/partials/referencia-o95.php:162 |
| 16154 | O95 | Referencia (Anexo 1) | Responsable de la atención en EE.SS. origen | `o95_responsable_atencion_eess_origen` | `o95_responsable_atencion_eess_origen` | 16154 | 0 |  | app/Views/partials/referencia-o95.php:174<br>public/js/ficha.js:2851 |
| 16155 | O95 | Referencia (Anexo 1) | Especificar otro responsable en EE.SS. origen | `o95_responsable_eess_origen_otro` | `o95_responsable_eess_origen_otro` | 16155 | 0 |  | app/Views/partials/referencia-o95.php:186 |
| 16156 | O95 | Referencia (Anexo 1) | Institución destino de la referencia | `o95_institucion_destino_referencia` | `o95_institucion_destino_referencia` | 16156 | 0 |  | app/Views/partials/referencia-o95.php:196 |
| 16157 | O95 | Referencia (Anexo 1) | EE.SS. destino de la referencia | `o95_eess_destino_referencia` | `o95_eess_destino_referencia` | 16157 | 0 |  | app/Views/partials/referencia-o95.php:208 |
| 16158 | O95 | Referencia (Anexo 1) | Fecha de ingreso al EE.SS. destino de la referencia | `o95_fecha_ingreso_eess_destino` | `o95_fecha_ingreso_eess_destino` | 16158 | 0 |  | app/Views/partials/referencia-o95.php:218 |
| 16159 | O95 | Referencia (Anexo 1) | Hora de ingreso al EE.SS. destino de la referencia | `o95_hora_ingreso_eess_destino` | `o95_hora_ingreso_eess_destino` | 16159 | 0 |  | app/Views/partials/referencia-o95.php:224 |
| 16160 | O95 | Parto o aborto | Fecha de parto o aborto desconocida | `o95_fecha_parto_desconocida` | `o95_fecha_parto_desconocida` | 16160 | 0 |  | app/Views/partials/parto-aborto-o95.php:43 |
| 16161 | O95 | Parto o aborto | Fecha de parto o aborto no aplica | `o95_fecha_parto_no_aplica` | `o95_fecha_parto_no_aplica` | 16161 | 0 |  | app/Views/partials/parto-aborto-o95.php:48 |
| 16162 | O95 | Parto o aborto | Nombre del EE.SS. del parto o aborto | `o95_lugar_parto_eess_nombre` | `o95_lugar_parto_eess_nombre` | 16162 | 0 |  | app/Views/partials/parto-aborto-o95.php:75 |
| 16163 | O95 | Parto o aborto | Especificar otro lugar de parto o aborto | `o95_lugar_parto_otro` | `o95_lugar_parto_otro` | 16163 | 0 |  | app/Views/partials/parto-aborto-o95.php:82 |
| 16164 | O95 | Parto o aborto | Especificar otro responsable del parto o aborto | `o95_responsable_parto_otro` | `o95_responsable_parto_otro` | 16164 | 0 |  | app/Views/partials/parto-aborto-o95.php:131 |
| 16165 | O95 | Entorno social y comunitario | Especificar otra persona que identificó signos | `o95_persona_identifico_otro` | `o95_persona_identifico_otro` | 16165 | 0 |  | app/Views/partials/entorno-social-o95.php:83 |
| 16166 | O95 | Entorno social y comunitario | Especificar otra persona que tomó la decisión de buscar ayuda | `o95_decision_buscar_ayuda_otro` | `o95_decision_buscar_ayuda_otro` | 16166 | 0 |  | app/Views/partials/entorno-social-o95.php:121 |
| 16167 | O95 | Entorno social y comunitario | Tiempo en buscar ayuda (horas) | `o95_tiempo_buscar_ayuda_horas` | `o95_tiempo_buscar_ayuda_horas` | 16167 | 0 |  | app/Views/partials/entorno-social-o95.php:132 |
| 16168 | O95 | Entorno social y comunitario | Tiempo en buscar ayuda (minutos) | `o95_tiempo_buscar_ayuda_minutos` | `o95_tiempo_buscar_ayuda_minutos` | 16168 | 0 |  | app/Views/partials/entorno-social-o95.php:138 |
| 16169 | O95 | Entorno social y comunitario | Especificar otra dificultad de acceso | `o95_dificultad_acceso_otro` | `o95_dificultad_acceso_otro` | 16169 | 0 |  | app/Views/partials/entorno-social-o95.php:187 |
| 16170 | O95 | Entorno social y comunitario | Tiempo hasta llegar al EE.SS. (horas) | `o95_tiempo_llegar_eess_horas` | `o95_tiempo_llegar_eess_horas` | 16170 | 0 |  | app/Views/partials/entorno-social-o95.php:197 |
| 16171 | O95 | Entorno social y comunitario | Tiempo hasta llegar al EE.SS. (minutos) | `o95_tiempo_llegar_eess_minutos` | `o95_tiempo_llegar_eess_minutos` | 16171 | 0 |  | app/Views/partials/entorno-social-o95.php:203 |
| 16172 | O95 | Entorno social y comunitario | Especificar otra dificultad de atención | `o95_dificultad_atencion_otro` | `o95_dificultad_atencion_otro` | 16172 | 0 |  | app/Views/partials/entorno-social-o95.php:253 |
| 16173 | O95 | Entorno social y comunitario | Tiempo hasta ser atendida (horas) | `o95_tiempo_hasta_atendida_horas` | `o95_tiempo_hasta_atendida_horas` | 16173 | 0 |  | app/Views/partials/entorno-social-o95.php:263 |
| 16174 | O95 | Entorno social y comunitario | Tiempo hasta ser atendida (minutos) | `o95_tiempo_hasta_atendida_minutos` | `o95_tiempo_hasta_atendida_minutos` | 16174 | 0 |  | app/Views/partials/entorno-social-o95.php:269 |
| 16175 | O95 | Entorno social y comunitario | Especificar otra persona que brindó información | `o95_persona_brindo_info_otro` | `o95_persona_brindo_info_otro` | 16175 | 0 |  | app/Views/partials/entorno-social-o95.php:299 |
| 16176 | O95 | Datos comunitarios | Especificar otra sintomatología o molestia | `o95_sintomatologia_otro` | `o95_sintomatologia_otro` | 16176 | 0 |  | app/Views/partials/datos-comunitarios-o95.php:91 |
| 16177 | O95 | Datos comunitarios | Especificar otra maniobra durante el parto | `o95_maniobras_parto_otro` | `o95_maniobras_parto_otro` | 16177 | 0 |  | app/Views/partials/datos-comunitarios-o95.php:122 |
| 16178 | O95 | Datos comunitarios | Especificar otra maniobra para retirar placenta | `o95_maniobras_placenta_otro` | `o95_maniobras_placenta_otro` | 16178 | 0 |  | app/Views/partials/datos-comunitarios-o95.php:153 |
| 16179 | O95 | Datos comunitarios | Tiempo del domicilio al EE.SS. (horas) | `o95_tiempo_domicilio_eess_horas` | `o95_tiempo_domicilio_eess_horas` | 16179 | 0 |  | app/Views/partials/datos-comunitarios-o95.php:166 |
| 16180 | O95 | Datos comunitarios | Tiempo del domicilio al EE.SS. (minutos) | `o95_tiempo_domicilio_eess_minutos` | `o95_tiempo_domicilio_eess_minutos` | 16180 | 0 |  | app/Views/partials/datos-comunitarios-o95.php:172 |
| 16181 | O95 | Causas de defunción (Anexo 1) | CIE-10 Causa asociada | `o95_causa_asociada_cie10` | `o95_causa_asociada_cie10` | 16181 | 0 |  | app/Views/partials/causas-defuncion-o95.php:85 |
| 16182 | O95 | Las cuatro demoras | Observaciones: Anote información adicional relevante | `o95_observaciones_demoras` | `o95_observaciones_demoras` | 16182 | 0 |  | app/Views/partials/demoras-o95.php:198 |
