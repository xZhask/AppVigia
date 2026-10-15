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
```

- La migración agrega `enfermedad.tablas_hijas`. Sin ella, el cargador falla al
  escribir esa columna.
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
- **B01:** un registro por tipo de muestra. Revisadas las demás candidatas:
  repiten tipo a propósito y no lo llevan.
- **Motor nuevo:** `indicacion` de campo (nota del papel antes del campo),
  `alguno_mayor_que` con casillas, reglas `mostrar` aplicadas hasta un punto
  fijo.

## 3. Pendiente — decisiones del usuario

No tocar sin una respuesta explícita.

1. **Clasificación duplicada en 11 fichas.** Muestran el chip genérico
   "Clasificación del caso" y además su propio campo: A97, A95, B57, B04X, A00,
   A35, A33, A80, B05, P35.0, O95. En A37.0 se resolvió ocultando el chip y
   calculando `caso.clasificacion` desde el campo propio (reglas `clasificar`).
   Antes, el usuario había rechazado ocultar el chip en general (`PENDIENTES.md`
   ítem 11); el 14/10 lo aprobó para A37.0. Decidir si se aplica a las 11 o
   ficha por ficha. Ojo: A97, A80, B57, Y59.0 y A00 tienen vocabularios propios
   que no caben en las 4 clasificaciones genéricas.
2. **Fechas que podrían derivarse del campo propio de cada ficha.**
   - `caso.fecha_inicio_sintomas` queda vacía en 10 fichas que la piden con
     otro nombre (P35.0, A35, A37.0, B01, A97, B57, A95, B55, B04X, A00). Los
     reportes por fecha de síntomas no las ven.
   - `fecha_notif` se pide aparte aunque la ficha tenga su cadena de fechas
     de notificación (`PENDIENTES.md` ítem 5).
   - Propuesta posible: declarar por ficha qué campo es la fecha canónica.
3. **Códigos de zona del PDF** (urbanización, AA.HH., cooperativa…): segundo eje
   del domicilio, distinto de Urbano/Rural. Afecta a A00, A95, B57, A37.0 y
   P35.0. Requiere columna nueva en `persona`.
4. **Z21:** si Aborto y Mortinato tienen ficha propia; hoy `caso.clasificacion`
   de Z21 queda en SOSPECHOSO por defecto.

## 4. Pendiente — técnico, sin decisión de por medio

- **O95, datos del Anexo 2 en la tarjeta de identidad** (grupo étnico, idioma,
  nivel educativo, estado civil, ocupación, seguro): se ocultan con Anexo 1,
  pero no están en las secciones condicionadas; probablemente se guardan si
  llegan en el POST. Verificar y, si es así, condicionarlos.
- **O95:** departamento y provincia del ubigeo de fallecimiento y referencia no
  se validan (el distrito sí).
- **"Ver":**
  - No aplica `campos_notificacion` (Z21 muestra "Tipo de registro" en una
    tarjeta aparte).
  - Por verificar si `b26_contactos_por_lugar` se lee bien o sale como JSON.
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
