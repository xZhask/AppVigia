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
php sql/migraciones/add_tipo_asentamiento_persona.php
php cargar_fichas.php --apply --confirmo-apply
php sql/migraciones/set_o95_anexo2_campos_condicionados.php
php sql/migraciones/set_o95_ubigeo_formatos.php
php sql/migraciones/set_o95_condiciones_lugar_referencia.php
php sql/migraciones/set_o95_fecha_inicio_desde_fallecimiento.php
php sql/migraciones/set_b26_condiciones.php
php sql/migraciones/set_p350_clasificacion_propia.php
php sql/migraciones/set_clasificacion_propia.php A35 A33 B57 A95 A00 B04X A97 B05 A80 O95
php sql/migraciones/set_fecha_inicio_sintomas_desde.php A35 A37.0 A97 B57 B04X A00 B01 A95 B55 P35.0 A80 B05 A44
php sql/migraciones/set_fecha_notif_en.php A37.0 B01 B26 A35 A33 A80 P35.0
php sql/migraciones/set_o95_categoria_desde_ipress.php
```

- La migración agrega `enfermedad.tablas_hijas`. Sin ella, el cargador falla al
  escribir esa columna.
- `add_tipo_asentamiento_persona.php` (16/10, antes del cargador): agrega
  `persona.tipo_asentamiento` y `persona.anterior_tipo_asentamiento` (sección
  3, ítem 3) y copia `detalle_domicilio` y `nucleo_ajustes` de A00, A95 y B57.
  Se puede correr más de una vez.
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
  las fichas pasadas a clasificación única (A35, A33, B57, A95, A00, B04X,
  A97, B05, A80 y O95):
  copia `nucleo_omitidos`, `reglas_campos` y la obligatoriedad de sus campos,
  y recalcula `caso.clasificacion`. En las 6 últimas el campo propio vacío
  también da una clasificación, así que un caso viejo con el chip elegido a
  mano y el campo propio vacío pasa a la de "vacío" (los casos son de prueba).
- `set_fecha_inicio_sintomas_desde.php <fichas>` (16/10): mismo motivo para la
  fecha de inicio de síntomas por ficha (sección 3, ítem 2); copia
  `nucleo_ajustes` y `nucleo_omitidos` y recalcula
  `caso.fecha_inicio_sintomas` como al editar y guardar. Se puede correr más
  de una vez.
- `set_fecha_notif_en.php <fichas>` (16/10): mismo motivo para la fecha de
  notificación en la cadena del PDF (sección 3, ítem 2); copia
  `nucleo_ajustes` y pone `caso.fecha_notif` en ese campo de los casos
  guardados. Se puede correr más de una vez.
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
  pantalla. Lo que llegue en el POST para la genérica se ignora. Desde el
  16/10 acepta también una lista de campos FECHA o SI_NO_FECHA (toma la más
  temprana; un SI_NO_FECHA cuenta solo marcado Sí), se calcula después de
  validar (un campo oculto no cuenta) y el campo ya no tiene que ser
  obligatorio.
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

1. **Clasificación duplicada — resuelta (16/10).** Ninguna ficha muestra ya
   el chip genérico "Clasificación del caso" junto a su propio campo. En las
   12 que lo tenían (A37.0 el 14/10; P35.0, A35, A33, B57, A95, A00, B04X,
   A97, B05, A80 y O95 el 16/10) el chip se ocultó y `caso.clasificacion` se
   calcula del campo propio con reglas `clasificar`, la misma equivalencia que
   aplicaba ficha.js. El usuario la aprobó ficha por ficha.
   - A35, A33, B57 y A95: el campo propio pasó a obligatorio, porque el chip
     exigía elegir Confirmado o Descartado.
   - Con el campo propio vacío: Sospechoso (P35.0, A37.0, A00, B04X, A97,
     B05), Probable en A80 (caso de PFA notificado) y Por determinar en O95.
   - A80: las 4 polio, "compatible" incluida, van a Confirmado. O95: manda la
     clasificación final; sin ella, la inicial. A97: "Síndrome febril" va a
     Sospechoso.
   - P35.0: "Infección congénita" queda en Sospechoso y el bloque de
     seguimiento de excreción viral depende de la clasificación propia
     (`bloques_condicionales.depende_de` acepta la clave de un SELECT).
2. **Fechas que podrían derivarse del campo propio de cada ficha.**
   - **Fecha de inicio de síntomas — resuelta (16/10).** Las 10 fichas que la
     piden con otro nombre la declaran en
     `nucleo_ajustes.fecha_inicio_sintomas_desde`, sin pedir nada nuevo ni
     volver obligatorio ningún campo: A35, A37.0, A97, B57 y B04X su "Fecha de
     inicio de síntomas"; A00 la de inicio de la diarrea; B01 la de inicio de
     la erupción dérmica; A95 la más temprana de los síntomas marcados Sí; B55
     solo la de la enfermedad mucosa (las fechas de las lesiones cutáneas no
     sirven: el mapa las rellena con la de hoy). P35.0 (congénita) la omite.
     Después, A80 (fecha de la deficiencia motora), B05 (inicio de la
     erupción) y A44 (inicio de la enfermedad): tampoco pintaban la genérica y
     el servidor tomaba la primera fecha llena de la ficha (en A80 y B05, una
     de notificación); sin ninguna, la ficha no se registraba. Se quitó la
     lista fija de CIE-10 del controlador y de la vista (queda solo B26, que
     la pinta en su partial), y "ver" ya no pinta la fila vacía. Ningún reporte usaba esa fecha (la semana
     epidemiológica sale de `fecha_notif`).
   - **Fecha de notificación — resuelta (16/10).** A37.0, B01, B26, A35, A33,
     A80 y P35.0 la pedían dos veces: la del núcleo en la tarjeta
     "Notificación" y su "Fecha de notificación EE.SS. a Red/Microred".
     Ahora declaran `nucleo_ajustes.fecha_notif_en`: la del núcleo
     (`caso.fecha_notif`: SE, edad, duplicados) se pinta en el sitio de la de
     la cadena, con su etiqueta, y el servidor guarda el mismo valor en ese
     campo; "ver" la muestra una sola vez. Las demás no tienen una fecha
     equivalente (A95/B57: conocimiento local; B05: identificación local;
     Y59.0: DIRESA a CDC) y siguen igual; O95 ya usaba la del núcleo. Z21 no
     se tocó: su "Fecha de reporte de la ficha" es otra fecha.
3. **Códigos de zona del PDF — resuelta (16/10).** Dato nuevo del domicilio
   `TIPO_ASENTAMIENTO` (`persona.tipo_asentamiento` y
   `anterior_tipo_asentamiento`): Urbanización, Villa, Cooperativa, Proyecto
   municipal de vivienda, PPJJ/AA.HH., Otro (los códigos del pie de A00).
   - A00: lo suma; su Urbano/Rural se llama "Zona de residencia", como en el
     papel (`nucleo_ajustes.etiquetas.tipo_zona`, nuevo campo ajustable).
   - A95 y B57: su "TIPO DE ZONA" va junto a "NOMBRE DE ZONA" (formato de
     dirección), así que es el tipo de asentamiento, en el domicilio actual y
     en el anterior; ya no preguntan Urbano/Rural (su papel no lo pide).
   - A37.0 y P35.0 no cambian: su papel dice "Tipo de zona: 1 = Urbano;
     2 = Periurbano; 3 = Rural", que es lo que ya tenían.
   - El tipo de vía sigue en texto libre (los códigos de A00 son un atajo del
     papel). "Ver" muestra el tipo de zona y el de asentamiento solo si la
     ficha los pide (la persona es compartida).
4. **Z21 — ya estaba resuelta (13/09; la hoja la arrastraba).** Aborto y
   Mortinato no son fichas propias: son la clasificación calculada de la ficha
   de la gestante (reglas `clasificar`, 4 categorías del registro de búsqueda
   activa de la pág. 15: Gestante con VIH, Aborto, Mortinato, Niño nacido
   expuesto al VIH). Sospechoso no es una opción de Z21 y "Tipo de registro"
   es obligatorio, así que todo caso queda clasificado. El cuadro está en
   Reportes ("Registro de Búsqueda Activa"). Comprobado de nuevo el 16/10.

## 4. Pendiente — técnico, sin decisión de por medio

- ~~**Reportes:** el orden y las columnas por clasificación solo conocen las 4
  genéricas; el filtro del listado de fichas también.~~ Resuelto el 16/10:
  `clasificacionesParaMostrar()` (las de la enfermedad filtrada o las 4
  genéricas, más las que traigan los datos, en el orden del catálogo) en la
  tabla y el Excel del reporte, la distribución del reporte y del panel, el
  agrupado por clasificación y el filtro del listado.
- ~~**Padrón RENIPRESS:** revisar categorías dudosas (Centro de Salud Mental
  Comunitario PNP figura I-2). A50 "Nivel del establecimiento" y O95 "Categoría
  del EE.SS." podrían tomarse del padrón.~~ Cerrado el 16/10:
  - El I-2 del Centro de Salud Mental Comunitario PNP es correcto (el usuario
    lo confirmó con la fuente oficial).
  - A50 "Nivel del establecimiento" es el del parto (cualquier EE.SS., escrito
    a mano): el padrón solo tiene las 82 IPRESS PNP, así que sigue capturado.
    Lo mismo la categoría del EE.SS. de la atención prenatal de O95.
  - O95, Anexo 2: si falleció en una IPRESS de Sanidad PNP, la categoría no se
    pedía ni se guardaba. Ahora el campo declara `"categoria_de_ipress"` y el
    servidor guarda `establecimiento.categoria` de la IPRESS elegida (después
    de las reglas; "ver" la muestra). MINSA/EsSalud/Privado la siguen
    capturando. Bases con casos: `set_o95_categoria_desde_ipress.php`.
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
