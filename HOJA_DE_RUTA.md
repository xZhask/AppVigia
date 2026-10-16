# Hoja de ruta — estado al 2026-10-15

Punto de partida para retomar desde cualquier estación. `PENDIENTES.md` es la
bitácora histórica (más de 7.000 líneas); esto es lo que sigue vigente.

## 1. Base de datos

Servidor: MySQL 8.4.3 de Laragon. La app se conecta según
`config/config.php` (`127.0.0.1:3306`, base `vigia`, `root` sin clave).

### 1.1 Instalar la base desde cero (estación sin `vigia`)

`sql/01_esquema_actual.sql` trae la estructura de las 32 tablas y la
configuración de las 24 fichas (enfermedades, secciones, campos, catálogos,
ubigeo, establecimientos, grados), ya cargada con el manifiesto del 15/10. No
trae casos, personas ni usuarios. Desde la raíz del proyecto:

```
mysql --host=127.0.0.1 -uroot -e "CREATE DATABASE vigia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql --host=127.0.0.1 -uroot --default-character-set=utf8mb4 vigia -e "source sql/01_esquema_actual.sql"
php scripts/crear_admin.php tu-correo@dominio "Tu nombre"
php verificar_fichas.php
php verificar_claves.php
php verificar_render.php
```

- `mysql` es `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe` (ruta
  completa, o esa carpeta en el PATH). Se usa `source` y no `<` porque
  PowerShell no admite esa redirección; así funciona igual en PowerShell, cmd
  y Git Bash (probado en PowerShell el 15/10).
- `crear_admin.php` imprime una contraseña aleatoria una sola vez. Al entrar,
  la app pide completar el perfil.
- No hace falta correr las migraciones ni el cargador: el archivo ya los trae
  aplicados (si se corren, no cambian nada).
- Los tres verificadores deben salir en verde, sin huérfanos.

### 1.2 Mantener `sql/01_esquema_actual.sql` instalable

El 15/10 el archivo dejó de instalarse: se agregó `enfermedad.tablas_hijas` a
mano al `CREATE TABLE`, pero los `INSERT` de `enfermedad` seguían sin esa
columna ("Column count doesn't match value count"). Se regeneró el mismo día.
Para que no vuelva a pasar:

- **Nunca editar a mano** el `CREATE TABLE` ni los `INSERT` del archivo. Todo
  cambio de esquema va como migración en `sql/migraciones/`, se aplica y luego
  **se regenera el archivo completo** en el mismo commit.
- Regenerar cuando cambie el esquema **o** el contenido de las fichas (después
  de `cargar_fichas.php --apply`), porque el archivo transporta ambos.
- Procedimiento (`mysqldump` de la 8.4.3, siempre con `--result-file`):

  ```
  mysqldump --host=127.0.0.1 -uroot --column-statistics=0 --single-transaction --routines --triggers --set-gtid-purged=OFF --default-character-set=utf8mb4 --no-data --result-file=estructura.sql vigia
  mysqldump --host=127.0.0.1 -uroot --column-statistics=0 --single-transaction --set-gtid-purged=OFF --default-character-set=utf8mb4 --no-create-info --skip-triggers --result-file=datos.sql vigia campo_def catalogo catalogo_item departamento distrito enfermedad establecimiento grado_pnp provincia red_salud seccion_def
  ```

  Armar el archivo en este orden: la cabecera de comentarios (agregar arriba
  una entrada con la fecha y qué cambió), `estructura.sql` hasta antes de la
  línea `SET TIME_ZONE=@OLD_TIME_ZONE`, los bloques `-- Dumping data` de
  `datos.sql` (sin su prólogo ni su epílogo) y, al final, el resto de
  `estructura.sql`.
- **Probar que se instala antes de commitear:** crear `vigia_limpia`, importar
  el archivo tal cual y comparar contra `vigia` las columnas, índices y claves
  foráneas (`information_schema`) y el contenido de las 11 tablas de
  configuración. Luego borrar `vigia_limpia`. El 15/10 dio 403 columnas,
  115 índices y 53 claves foráneas idénticas, 24 enfermedades, 195 secciones,
  1216 campos, 338 catálogos y 1861 ítems.

### 1.3 Sincronizar una estación que ya tiene `vigia` (después de `git pull`)

En este orden:

```
php sql/migraciones/add_tablas_hijas_enfermedad.php
php cargar_fichas.php --apply --confirmo-apply
php sql/migraciones/set_o95_anexo2_campos_condicionados.php
php sql/migraciones/set_o95_ubigeo_formatos.php
php sql/migraciones/set_o95_condiciones_lugar_referencia.php
php sql/migraciones/set_o95_fecha_inicio_desde_fallecimiento.php
php sql/migraciones/set_b26_condiciones.php
php sql/migraciones/set_p350_clasificacion_propia.php
php sql/migraciones/set_clasificacion_propia.php A35 A33 B57 A95
```

- La migración agrega `enfermedad.tablas_hijas`. Sin ella, el cargador falla al
  escribir esa columna.
- `set_o95_anexo2_campos_condicionados.php` (15/10, después del cargador):
  condiciona a Anexo 2 los 10 campos de O95 descritos en la sección 2. Hace
  falta porque, si O95 tiene casos, el cargador la bloquea y no aplica el
  cambio del manifiesto. Actualiza esos `campo_def` (no los recrea) y borra
  los valores de Anexo 2 que hayan quedado grabados en casos que no son de
  Anexo 2. Se puede correr más de una vez.
- `set_o95_ubigeo_formatos.php` (15/10, después del cargador): mismo motivo;
  fija el `config` de los 6 campos de ubigeo de O95 (ver sección 2) y corrige
  en los casos guardados el departamento o la provincia que no correspondan
  al nivel de abajo. Se puede correr más de una vez.
- `set_o95_condiciones_lugar_referencia.php` (15/10, después de la anterior):
  mismo motivo; toma del manifiesto los `depende_de`, el `con_hora` y las
  `reglas_campos` de "Datos del fallecimiento" y "Referencia" (ver sección 2),
  los fija en los `campo_def` existentes y borra en los casos guardados los
  valores que ya no corresponden. Se puede correr más de una vez.
- `set_o95_fecha_inicio_desde_fallecimiento.php` (15/10): mismo motivo; fija
  `nucleo_ajustes.fecha_inicio_sintomas_desde`, vuelve obligatoria la fecha de
  fallecimiento y copia esa fecha a `caso.fecha_inicio_sintomas` en los casos
  guardados. Se puede correr más de una vez.
- `set_b26_condiciones.php` (15/10): mismo motivo para B26; fija sus
  `depende_de`, pasa "¿Tuvo contacto con gestante?" a SELECT (de sus valores
  viejos, `1` → `SI` y `0` se borra) y borra los valores que ya no aplican.
  Se puede correr más de una vez.
- `set_p350_clasificacion_propia.php` (16/10): mismo motivo para P35.0; copia
  del manifiesto `nucleo_omitidos`, `reglas_campos` y `bloques_condicionales`
  y recalcula `caso.clasificacion`. Se puede correr más de una vez.
- `set_clasificacion_propia.php <fichas>` (16/10): lo mismo, genérico, para
  las fichas que se van pasando a clasificación única (hoy A35, A33, B57, A95):
  copia `nucleo_omitidos`, `reglas_campos` y la obligatoriedad de sus campos,
  y recalcula `caso.clasificacion`. Al sumar una ficha, agregarla a la línea.
- El cargador va **sin** `--cie10`: el 15/10 cambió `tablas_hijas` en las 24
  fichas. Si una ficha sale "bloqueada por datos capturados", igual recibe sus
  tablas hijas (el cargador las aplica aparte); el resto de esa ficha queda
  como estaba.
- Comprobar después: `php verificar_fichas.php`, `php verificar_claves.php` y
  `php verificar_render.php` (los tres en verde, sin huérfanos).

## 2. Qué quedó hecho (13–15 de octubre)

- **Auditoría ficha por ficha contra el PDF: completa.** Las 24 fichas están
  cotejadas. Las últimas: A35 (datos del paciente, investigador, ítem III.2),
  A37.0 (X/XI, ítem 53, regla de dosis documentadas en HIS, clasificación
  calculada) y B55 (tiempo de permanencia con unidad, dibujos ORL, rostros,
  silueta según el sexo y el mapa de lesiones en "ver").
- **Reglas de dosis** en A35, A33 y A37.0: la fecha de última dosis solo con
  alguna dosis marcada; dosis secuenciales.
- **Seguridad de datos en el servidor:**
  - Solo se guardan las tablas hijas que la ficha declara (`tablas_hijas`).
  - En muestras, solo las columnas declaradas.
  - Una persona existente ya no pierde los datos que pidió otra ficha (antes
    registrar un caso de A36 borraba el estado civil guardado por B57).
  - Lo que llega forjado para un dato que la ficha no pide se descarta.
- **O95:** el Anexo 2 son secciones condicionadas ("ver" y la validación ya
  respetan el anexo); el distrito de fallecimiento y de referencia se valida
  contra el padrón.
- **O95, datos de Anexo 2 fuera de las secciones de Anexo 2 (cerrado el 15/10,
  segunda vuelta):** 10 campos de "Datos del fallecimiento (Anexo 1)" se
  ocultaban con Anexo 1, pero igual se guardaban: los 7 de la tarjeta de
  identidad (grupo étnico, pueblo étnico, idioma, nivel educativo, estado
  civil, ocupación, tipo de seguro) y los 3 de "Datos ampliados del
  fallecimiento" (categoría del EE.SS., fecha y hora de ingreso, responsable
  de la atención). No hacía falta un POST forjado: bastaba marcar Anexo 2,
  llenar y volver a Anexo 1. Ahora dependen de `o95_tipo_de_ficha = ANEXO_2`
  en el manifiesto ("especificar otro idioma/seguro" caen en cadena).
  - Verificado con Playwright al registrar (Anexo 2 → Anexo 1, POST forjado y
    control positivo con Anexo 2) y al editar (Anexo 2 → Anexo 1 borra los
    datos), por la migración y por el cargador. El HTML de "nueva", "editar" y
    "ver" queda idéntico byte a byte.
  - Los valores grabados antes del arreglo en casos de Anexo 1 los borra la
    misma migración (verificado: borra solo esos campos y solo en casos que no
    son de Anexo 2).
- **O95, departamento y provincia del ubigeo** (fallecimiento y origen de la
  referencia): formatos nuevos `departamento` y `provincia`, y `dentro_de` en
  provincia y distrito (la clave del nivel de arriba). El servidor valida los
  tres contra el padrón y deriva el nivel de arriba del de abajo, así que un
  departamento o una provincia que no corresponden no se guardan; el error
  sale bajo el selector. "Ver" muestra el nombre en vez del id. Verificado con
  Playwright (coherente, dos POST forjados y un id inexistente) y la migración
  con datos sembrados.
- **O95, datos que dependen del lugar del fallecimiento y de la referencia:**
  los bloques se ocultaban solo en el navegador y lo escrito se guardaba igual
  (p. ej. el ubigeo con lugar = Domicilio). Ahora 26 campos declaran
  `depende_de` (lugar, tipo de EE.SS., ¿Referida?) y `reglas_campos`
  "mostrar" exige además Anexo 2 donde corresponde (categoría y fecha y hora
  de ingreso al EE.SS., detalle de la referencia). Con Sanidad PNP el ubigeo
  sigue guardándose (sale de la IPRESS).
  - "Especificar trayecto" no se guardaba nunca (`name=…_trayecto`): ahora
    usa el campo real, como "Especificar lugar"; solo viaja el visible.
  - "Fecha y hora de ingreso al EE.SS." (Anexo 2) bloqueaba el registro sin
    mensaje: el `datetime-local` mandaba fecha y hora a un FECHA. Motor
    nuevo: `con_hora` en FECHA (se guarda `AAAA-MM-DDTHH:MM`, "ver" muestra
    `dd/mm/aaaa HH:MM`); el error ya se ve bajo el campo, y el sustituto de
    la fecha de inicio de síntomas toma solo la fecha.
  - Verificado con Playwright: 6 escenarios al registrar (positivos por la
    interfaz y POST forjados), editar (Anexo 2 → Domicilio y No referida
    borra los datos), "ver" y "editar"; la migración con datos sembrados.
- **O95, fecha de inicio de síntomas:** la ficha no la pinta pero el
  servidor la exigía (la tomaba del primer FECHA lleno; sin ninguno, no se
  registraba y el aviso pedía un campo que no está en pantalla). Ajuste nuevo
  `nucleo_ajustes.fecha_inicio_sintomas_desde`: `caso.fecha_inicio_sintomas`
  sale de la fecha de fallecimiento, ahora obligatoria y con su error en
  pantalla. Lo que llegue en el POST para la genérica se ignora. Sirve para
  resolver ficha por ficha el ítem 2 de la sección 3.
- **B26 (Parotiditis), bugs que perdían datos:**
  - El formulario repetía las tarjetas "Lugar probable de infección" y
    "Antecedentes de vacunación": 11 `campo_def` creados el 10/08 nunca se
    sumaron a la lista de claves que pintan los partials de B26, y el input
    genérico vacío (mismo `name`, más abajo) pisaba lo escrito arriba: se
    perdían dirección, ubigeo y localidad del lugar de infección, fecha de
    contacto con gestante, dosis, fecha y EE.SS. de la vacuna SPR y
    observaciones.
  - La fecha de inicio de síntomas salía dos veces; con la de "Cuadro
    clínico" sola no se registraba. Queda solo esa, y en "editar" ya trae la
    guardada (antes salía vacía).
  - Los radios Sí/No mandan `SI` y el servidor solo aceptaba `1` para un
    BOOLEANO: parótidas, hospitalización y vacunación SPR se guardaban "No"
    aunque se marcara Sí. El servidor ahora acepta `SI`.
  - 15 campos declaran `depende_de` (sus bloques se ocultaban solo en el
    navegador) y "¿Tuvo contacto con gestante?" es SELECT Sí/No/Ignorado (el
    BOOLEANO perdía "Ignorado").
  - El input de fecha de B26, que se pinta oculto en todas las fichas, va
    deshabilitado fuera de B26. Recorrido de las 24 fichas: sin `name`
    repetidos que lleguen al servidor (los de O95 son a propósito o de otra
    ficha).
- **"Ver" con `campos_notificacion`:** esos campos se leen en la tarjeta
  "Notificación", como en el formulario (Z21 ya no muestra "Registro" aparte;
  en A80 las fechas de notificación pasan a esa tarjeta).
- **"Ver", contactos por lugar (B26, B01, A37.0):** una línea por lugar
  ("Colegio: IE 123 · sanos 20 · enfermos 3") en vez de "Registrado (n
  ítems)"; aprobado por el usuario el 16/10.
- **"Ver" sin campos que no aplican:** los campos sueltos con `depende_de` o
  con una regla `mostrar` que no se cumple ya no salen con "—" (24 fichas;
  helper `camposOcultosDelCaso()`, mismas reglas que al guardar). Un dato
  guardado se muestra igual. Con todas las de una sección ocultas, la sección
  no se pinta. Comparado antes/después en todos los casos de la base: solo
  desaparecen campos vacíos.
- **B01:** un registro por tipo de muestra. Revisadas las demás candidatas:
  repiten tipo a propósito y no lo llevan.
- **Motor nuevo:** `indicacion` de campo (nota del papel antes del campo),
  `alguno_mayor_que` con casillas, reglas `mostrar` aplicadas hasta un punto
  fijo.

## 3. Pendiente — decisiones del usuario

No tocar sin una respuesta explícita.

1. **Clasificación duplicada en 6 fichas.** Muestran el chip genérico
   "Clasificación del caso" y además su propio campo: A97, B04X, A00, A80,
   B05, O95. Resueltas: A37.0 (14/10), P35.0, A35, A33, B57 y A95 (16/10; en
   las cuatro últimas el campo propio pasó a obligatorio, porque el chip exigía
   elegir Confirmado o Descartado). Se resolvió
   ocultando el chip y calculando `caso.clasificacion` desde el campo propio
   (reglas `clasificar`); el usuario aprobó ir ficha por ficha. En P35.0,
   "Infección congénita" queda en Sospechoso (como ya hacía ficha.js) y el
   bloque de seguimiento de excreción viral depende ahora de la clasificación
   propia (`bloques_condicionales.depende_de` acepta la clave de un SELECT). Ojo: A97, A80, B57, Y59.0 y A00 tienen vocabularios propios
   que no caben en las 4 clasificaciones genéricas.
2. **Fechas que podrían derivarse del campo propio de cada ficha.**
   - `caso.fecha_inicio_sintomas` queda vacía en 10 fichas que la piden con
     otro nombre (P35.0, A35, A37.0, B01, A97, B57, A95, B55, B04X, A00). Los
     reportes por fecha de síntomas no las ven.
   - Ya existe la forma declarativa: `nucleo_ajustes.fecha_inicio_sintomas_desde`
     (estrenada en O95 con la fecha de fallecimiento). Falta decidir el campo
     de cada una de las 10 fichas.
   - `fecha_notif` se pide aparte aunque la ficha tenga su cadena de fechas
     de notificación (`PENDIENTES.md` ítem 5).
   - Propuesta posible: declarar por ficha qué campo es la fecha canónica.
3. **Códigos de zona del PDF** (urbanización, AA.HH., cooperativa…): segundo eje
   del domicilio, distinto de Urbano/Rural. Afecta a A00, A95, B57, A37.0 y
   P35.0. Requiere columna nueva en `persona`.
4. **Z21:** si Aborto y Mortinato tienen ficha propia; hoy `caso.clasificacion`
   de Z21 queda en SOSPECHOSO por defecto.

## 4. Pendiente — técnico, sin decisión de por medio

- **Reportes:** el orden y las columnas por clasificación solo conocen las 4
  genéricas; el filtro del listado de fichas también.
- **Padrón RENIPRESS:** revisar categorías dudosas (Centro de Salud Mental
  Comunitario PNP figura I-2). A50 "Nivel del establecimiento" y O95 "Categoría
  del EE.SS." podrían tomarse del padrón.
- **Menores, de interfaz:**
  - Hueco vertical en datos de la persona cuando una rama oculta Etnia y
    Residencia.
  - Falta estilo para `input[readonly]`.
  - En P96 la edad muestra "0 años".
  - Una persona sin documento no puede recibir uno después.
  - Falta un catálogo CIE-10 para el diagnóstico de P96.

## 5. Cómo se ha trabajado (para mantener el criterio)

- Variaciones por ficha: como dato en `manifiesto_fichas.json`, no con
  `if ($cie10 === ...)` en código compartido.
- Antes de commitear, avisar si algo cambia en las fichas revisadas (A36, B26,
  A80, O95, B05, P35.0, B01, A95).
- Verificar en el navegador real (Playwright) con crear → ver → editar, una
  prueba negativa por POST con control positivo, y borrar los casos de prueba.
- Los casos de la base son de prueba.
- Push solo cuando el usuario lo pide.
