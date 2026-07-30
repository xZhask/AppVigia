# VIGÍA · Petición 1 — Respaldo verificado y esquema reproducible

Objetivo: que la base de datos deje de existir **solo** en la máquina de
desarrollo y vuelva a poder reconstruirse desde el repositorio.

Siguen vigentes las reglas de diseño de `PLAN_CLAUDE_CODE.md`.

> Ejecutar las fases en orden, **deteniéndose a validar entre cada una**.
> Ninguna fase de esta petición toca `app/`, `public/` ni `theme.css`.

---

## Por qué

Ocho scripts de `scratch/` aplicaron `ALTER TABLE` directo contra la base viva.
`sql/01_esquema_actual.sql` quedó congelado el 2026-07-23 y no los contiene.
El código de la aplicación **sí** usa esas columnas. Verificado:

| Columna | ¿En `01_esquema_actual.sql`? | ¿La usa `app/`? |
|---|---|---|
| `caso.investigador_profesion` | no | sí |
| `caso_viaje.transporte_ida` | no | sí |
| `caso_viaje.transporte_retorno` | no | sí |
| `caso_vacuna.fuente_informacion` | no | sí |
| `caso_contacto.direccion` | no | sí |
| `caso_lugar_infeccion.direccion` | no | sí |
| columnas nuevas de `caso_muestra` (B05, PFA) | no | sí |
| columnas nuevas de núcleo del paciente | no | sí |

Consecuencia: **una instalación limpia desde el repositorio no arranca**, y el
respaldo más reciente en `backups/` es del 2026-07-22 — anterior a todo el
trabajo de validación de A36, B26, A80, O95, B05 y P35.0.

---

## Fase 1 — Respaldo íntegro y verificado

Antes que nada. Es lo único de todo el proyecto que no se puede recuperar.

1. Generar un dump completo (esquema + datos) de la base `vigia`:

   ```
   mysqldump --user=root --single-transaction --routines --triggers \
     --default-character-set=utf8mb4 \
     --result-file=backups/vigia_YYYYMMDD_HHMMSS.sql vigia
   ```

2. **Usar `--result-file`, no redirección ni tubería.** `scripts/respaldo_bd.ps1`
   hoy hace `| Out-File -Encoding utf8`, que en Windows PowerShell 5 reescribe
   el flujo y puede introducir BOM y saltos CRLF dentro del dump. Corregir el
   script para que use `--result-file` y dejarlo así de forma permanente.

3. **Verificar que el respaldo sirve**, no solo que existe. Restaurarlo en una
   base temporal `vigia_verificacion` y comparar contra la original:

   - número de tablas
   - `COUNT(*)` de `enfermedad`, `seccion_def`, `campo_def`, `catalogo`,
     `catalogo_item`, `caso`, `caso_valor`
   - que `SHOW CREATE TABLE caso` sea idéntico en ambas

   Reportar la comparación. Si algo no cuadra, **detenerse aquí**.

4. Solo después de verificar, borrar `vigia_verificacion`.

**Validación de la fase:** existe un dump nuevo en `backups/`, restaurado y
comparado con éxito, y el reporte de comparación está a la vista.

---

## Fase 2 — Inventario de la deriva de esquema

Sin modificar nada todavía.

1. Extraer de `sql/01_esquema_actual.sql` la lista de columnas por tabla.
2. Extraer de la base viva la misma lista (`information_schema.columns`).
3. Producir `INFORME_DERIVA_ESQUEMA.md` con tres secciones:

   - **Columnas en la base que faltan en el dump** — la deriva a corregir.
     Para cada una, indicar el script de `scratch/` que la creó (buscar por
     nombre de columna en `scratch/*.php`) y su tipo real (`SHOW CREATE TABLE`).
   - **Columnas en el dump que ya no están en la base** — si las hay, son
     borrados no registrados; hay que entender cada uno antes de seguir.
   - **Diferencias de tipo, nulabilidad, índices o claves foráneas** en columnas
     presentes en ambos lados.

4. Incluir también las diferencias de `CREATE TABLE` completas para
   `caso`, `caso_muestra`, `caso_contacto`, `caso_viaje`, `caso_vacuna`,
   `caso_lugar_infeccion` y `caso_sujeto`, que son las que más se tocaron.

**Validación de la fase:** `INFORME_DERIVA_ESQUEMA.md` existe y su lista de
columnas faltantes incluye, como mínimo, las ocho filas de la tabla de arriba.
Si aparecen menos, la extracción está mal hecha.

---

## Fase 3 — Regenerar el esquema como fuente de verdad

1. Regenerar `sql/01_esquema_actual.sql` con un `mysqldump` **de la base viva**,
   con la misma forma que el archivo actual: esquema completo más los `INSERT`
   de las tablas de configuración que ya incluía —`enfermedad`, `seccion_def`,
   `campo_def`, `catalogo`, `catalogo_item`, `departamento`, `provincia`,
   `distrito`, `red_salud`, `establecimiento`, `grado_pnp`— y **sin** datos de
   `caso` ni de sus tablas hijas.

2. Encabezar el archivo con la fecha de generación y la versión del manifiesto
   de la que es contemporáneo, igual que hoy.

3. Verificar que es realmente instalable: crear una base vacía
   `vigia_limpia`, aplicar el archivo, y comprobar que:

   - todas las tablas se crean sin error
   - las columnas del inventario de la Fase 2 están presentes
   - `SELECT COUNT(*) FROM campo_def` coincide con la base viva

4. Borrar `vigia_limpia`.

**Validación de la fase:** una base vacía + `sql/01_esquema_actual.sql` produce
un esquema idéntico al de desarrollo. Reportar los conteos lado a lado.

---

## Fase 4 — Cerrar la puerta a que vuelva a pasar

1. **Retirar de `git` los scripts desechables de `scratch/`.** Hoy hay 193
   archivos, 186 versionados. No aportan historia útil —muchos son
   `check_*` de un solo uso, y hay pares `fix_`/`revert_` que se anulan— y
   ocultan los cambios reales en cada diff.

   - Agregar `/scratch/` a `.gitignore`.
   - `git rm -r --cached scratch/` (los archivos siguen en disco).
   - **Excepción:** conservar versionados los que aplicaron `ALTER TABLE`
     (`add_b05_muestra_cols.php`, `add_direccion_caso_contacto.php`,
     `add_direccion_lugar_inf.php`, `add_fuente_info_vacuna.php`,
     `add_investigador_profesion_col.php`, `add_paciente_nucleo_cols.php`,
     `add_pfa_muestra_cols.php`, `add_transporte_cols_caso_viaje.php`)
     moviéndolos a `sql/migraciones/` como registro de lo que ocurrió.

2. **Normalizar los finales de línea.** Hoy `git status` muestra 75 archivos
   modificados, de los cuales solo 9 tienen cambios reales; el resto es ruido
   CRLF que hace ilegible cualquier revisión. Agregar un `.gitattributes`:

   ```
   * text=auto eol=lf
   *.ps1 text eol=crlf
   ```

   Luego `git add --renormalize .` en un commit **separado y exclusivo**, para
   que no se mezcle con cambios de código.

3. **Regla nueva, a respetar de aquí en adelante:** ningún `ALTER TABLE` se
   ejecuta desde `scratch/`. Todo cambio de esquema se escribe primero como
   archivo numerado en `sql/migraciones/NN_descripcion.sql`, se aplica desde
   ahí, y se refleja en `sql/01_esquema_actual.sql` en el mismo commit.
   Dejarlo escrito en `PLAN_CLAUDE_CODE.md`.

**Validación de la fase:** `git status` limpio salvo los cambios intencionales;
`scratch/` fuera del índice; `sql/migraciones/` con los ocho scripts de ALTER.

---

## Fuera de alcance

No tocar `app/`, `public/`, `theme.css` ni `manifiesto_fichas.json` en esta
petición. Los IDs hardcodeados se atienden en la Petición 2, que **depende de
que esta se haya completado** (necesita el respaldo de la Fase 1 como red).
