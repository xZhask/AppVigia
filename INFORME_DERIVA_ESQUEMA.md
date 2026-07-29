# Informe de deriva de esquema — `vigia` (base viva) vs `sql/01_esquema_actual.sql`

Generado 2026-07-29, Fase 2 de `PETICION_01_ESQUEMA_REPRODUCIBLE.md`.

Método: se cargó `sql/01_esquema_actual.sql` en una base temporal
(`vigia_esquema_dump`) y se comparó su `information_schema.columns` y
`SHOW CREATE TABLE` de las 27 tablas contra la base real `vigia`, en vez de
parsear el `.sql` a mano (más confiable). La base temporal fue borrada al
cerrar esta fase.

Hallazgo lateral: el servidor real es **MariaDB 11.8.2**, no MySQL — no afecta
este informe pero sí `mysqldump` (ver nota en Fase 1 / commit del script).

---

## 1) Columnas en la base viva que faltan en el dump

**27 columnas**, en 7 tablas. Para cada una: script de `scratch/` que la creó
(si existe) y tipo real según `SHOW CREATE TABLE vigia.<tabla>`.

| Tabla | Columna | Tipo real | Script de origen |
|---|---|---|---|
| `caso` | `investigador_profesion` | `varchar(100) DEFAULT NULL` | `scratch/add_investigador_profesion_col.php` |
| `caso_contacto` | `direccion` | `varchar(160) DEFAULT NULL` | `scratch/add_direccion_caso_contacto.php` |
| `caso_contacto` | `dosis_recibidas` | `varchar(30) DEFAULT NULL` | ⚠️ **ninguno** — ver nota abajo |
| `caso_contacto` | `fecha_colecta_heces` | `date DEFAULT NULL` | ⚠️ **ninguno** — ver nota abajo |
| `caso_contacto` | `fecha_envio` | `date DEFAULT NULL` | ⚠️ **ninguno** — ver nota abajo |
| `caso_contacto` | `fecha_resultado` | `date DEFAULT NULL` | ⚠️ **ninguno** — ver nota abajo |
| `caso_contacto` | `resultado_aislamiento` | `varchar(120) DEFAULT NULL` | ⚠️ **ninguno** — ver nota abajo |
| `caso_lugar_infeccion` | `direccion` | `varchar(255) DEFAULT NULL` | `scratch/add_direccion_lugar_inf.php` |
| `caso_muestra` | `numero_muestra` | `tinyint(1) DEFAULT 1` | `scratch/add_b05_muestra_cols.php` |
| `caso_muestra` | `fecha_recepcion_ins` | `date DEFAULT NULL` | `scratch/add_b05_muestra_cols.php` |
| `caso_muestra` | `resultado_pcr` | `varchar(40) DEFAULT NULL` | `scratch/add_b05_muestra_cols.php` |
| `caso_muestra` | `fecha_result_pcr` | `date DEFAULT NULL` | `scratch/add_b05_muestra_cols.php` |
| `caso_muestra` | `genotipo` | `varchar(80) DEFAULT NULL` | `scratch/add_b05_muestra_cols.php` |
| `caso_muestra` | `resultado_igm` | `varchar(40) DEFAULT NULL` | `scratch/add_b05_muestra_cols.php` |
| `caso_muestra` | `fecha_result_igm` | `date DEFAULT NULL` | `scratch/add_b05_muestra_cols.php` |
| `caso_muestra` | `resultado_igg` | `varchar(40) DEFAULT NULL` | `scratch/add_b05_muestra_cols.php` |
| `caso_muestra` | `fecha_result_igg` | `date DEFAULT NULL` | `scratch/add_b05_muestra_cols.php` |
| `caso_muestra` | `fecha_envio_ins` | `date DEFAULT NULL` | `scratch/add_pfa_muestra_cols.php` |
| `caso_muestra` | `agente_aislado` | `varchar(120) DEFAULT NULL` | `scratch/add_pfa_muestra_cols.php` |
| `caso_muestra` | `observaciones` | `varchar(255) DEFAULT NULL` | `scratch/add_pfa_muestra_cols.php` |
| `caso_vacuna` | `fuente_informacion` | `varchar(80) DEFAULT NULL` | `scratch/add_fuente_info_vacuna.php` |
| `caso_viaje` | `transporte_ida` | `varchar(40) DEFAULT NULL` | `scratch/add_transporte_cols_caso_viaje.php` |
| `caso_viaje` | `transporte_retorno` | `varchar(40) DEFAULT NULL` | `scratch/add_transporte_cols_caso_viaje.php` |
| `persona` | `etnia_otra` | `varchar(100) DEFAULT NULL` | `scratch/add_paciente_nucleo_cols.php` |
| `persona` | `nombre_tutor` | `varchar(160) DEFAULT NULL` | `scratch/add_paciente_nucleo_cols.php` |
| `persona` | `celular_tutor` | `varchar(20) DEFAULT NULL` | `scratch/add_paciente_nucleo_cols.php` |
| `persona` | `trimestre_gestacion` | `varchar(10) DEFAULT NULL` | `scratch/add_paciente_nucleo_cols.php` |

Esto cubre y amplía las 8 filas mínimas exigidas por la petición (una fila,
`caso_muestra`, se abre en 12 columnas reales repartidas en dos scripts —
`add_b05_muestra_cols.php` y `add_pfa_muestra_cols.php`).

### ⚠️ Hallazgo no previsto: 5 columnas de `caso_contacto` sin script de origen

`caso_contacto.dosis_recibidas`, `fecha_colecta_heces`, `fecha_envio`,
`fecha_resultado` y `resultado_aislamiento` existen en la base viva, las usa
`app/Models/CasoContacto.php` y `app/Controllers/CasosController.php` (confirmado
por `git show 708821f`, commit "PFA y Sarampión Ok"), pero **ningún archivo en
`scratch/` las crea** — se buscó por nombre exacto de columna y por
`ALTER TABLE.*caso_contacto` en todo el repositorio (incluido
`_ARCHIVO_PARA_ELIMINAR/`) sin resultado. Los únicos 8 scripts en `scratch/`
que ejecutan `ALTER TABLE` son exactamente los 8 que la petición ya lista para
mover a `sql/migraciones/` en la Fase 4; estos no están entre ellos.

Se aplicaron por algún medio no versionado (cliente SQL directo, phpMyAdmin,
sesión interactiva). No hay forma de reconstruir ese `ALTER TABLE` desde el
repositorio — solo desde el estado actual de la base, que es justamente lo que
la Fase 3 va a congelar en el nuevo `sql/01_esquema_actual.sql`. Para la Fase 4
esto implica que la "excepción" de 8 scripts a versionar en
`sql/migraciones/` queda incompleta: estas 5 columnas no van a tener un
archivo de migración que documente su origen, solo van a aparecer ya
incluidas en el esquema regenerado. Si se quiere registrar su historia habría
que escribir manualmente un `sql/migraciones/09_contacto_pfa_columnas.sql`
con el `ALTER TABLE` reconstruido desde el tipo real (arriba) — no estaba en
el alcance original de la petición, se deja como decisión pendiente para
quien la ejecuta.

---

## 2) Columnas en el dump que ya no están en la base

**Ninguna.** Las 230 columnas restantes de `sql/01_esquema_actual.sql`
existen todas en la base viva. No hay borrados no registrados.

---

## 3) Diferencias de tipo, nulabilidad, índices o llaves foráneas

**Ninguna**, en las 230 columnas presentes en ambos lados: tipo, nulabilidad y
`DEFAULT` idénticos columna por columna. `SHOW CREATE TABLE` de las 27 tablas
no muestra diferencias de índices ni de `CONSTRAINT` — los únicos diffs son
exactamente las columnas nuevas listadas en la sección 1 y los contadores
`AUTO_INCREMENT` (crecieron por el uso normal de la app desde que el dump se
congeló el 2026-07-23; no es deriva de esquema, es dato):

| Tabla | `AUTO_INCREMENT` en dump | en base viva |
|---|---|---|
| `campo_def` | 12779 | 16205 |
| `catalogo` | 525 | 544 |
| `catalogo_item` | 2941 | 3164 |
| `login_intento` | 30 | 41 |
| `persona` | 15 | 16 |
| `reniec_consulta` | 18 | 19 |
| `seccion_def` | 2246 | 2783 |

---

## 4) `CREATE TABLE` completo — tablas más tocadas

Diff línea a línea (`vigia` viva vs. dump congelado) de las 7 tablas que
pide la petición. `<` = solo en la base viva, `>` = solo en el dump.

### `caso`
```
16d15
<   `investigador_profesion` varchar(100) DEFAULT NULL,
```

### `caso_muestra`
```
12,23d11
<   `fecha_envio_ins` date DEFAULT NULL,
<   `agente_aislado` varchar(120) DEFAULT NULL,
<   `observaciones` varchar(255) DEFAULT NULL,
<   `numero_muestra` tinyint(1) DEFAULT 1,
<   `fecha_recepcion_ins` date DEFAULT NULL,
<   `resultado_pcr` varchar(40) DEFAULT NULL,
<   `fecha_result_pcr` date DEFAULT NULL,
<   `genotipo` varchar(80) DEFAULT NULL,
<   `resultado_igm` varchar(40) DEFAULT NULL,
<   `fecha_result_igm` date DEFAULT NULL,
<   `resultado_igg` varchar(40) DEFAULT NULL,
<   `fecha_result_igg` date DEFAULT NULL,
```

### `caso_contacto`
```
19,24d18
<   `dosis_recibidas` varchar(30) DEFAULT NULL,
<   `fecha_colecta_heces` date DEFAULT NULL,
<   `fecha_envio` date DEFAULT NULL,
<   `fecha_resultado` date DEFAULT NULL,
<   `resultado_aislamiento` varchar(120) DEFAULT NULL,
<   `direccion` varchar(160) DEFAULT NULL,
```

### `caso_viaje`
```
10,11d9
<   `transporte_ida` varchar(40) DEFAULT NULL,
<   `transporte_retorno` varchar(40) DEFAULT NULL,
```

### `caso_vacuna`
```
16d15
<   `fuente_informacion` varchar(80) DEFAULT NULL,
```

### `caso_lugar_infeccion`
```
10d9
<   `direccion` varchar(255) DEFAULT NULL,
```

### `caso_sujeto`
Sin diferencias — idéntica en ambos lados.

---

## Validación de la fase

La lista de columnas faltantes (sección 1) contiene 27 filas, que cubren y
superan las 8 filas mínimas de la tabla de la petición. Se documenta además
un hallazgo fuera del alcance original: 5 columnas de `caso_contacto` sin
script de origen rastreable en el repositorio.
