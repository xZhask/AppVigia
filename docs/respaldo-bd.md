# Respaldo de la base de datos

VIGÍA no respalda datos automáticamente por sí solo: el respaldo se hace a
nivel de infraestructura, con el script `scripts/respaldo_bd.ps1` incluido en
este repositorio.

## Qué hace el script

- Vuelca la base `vigia` completa con `mysqldump` (estructura + datos).
- Comprime el volcado a `.zip` y borra el `.sql` sin comprimir.
- Guarda el resultado en `backups\vigia_AAAAMMDD_HHmmss.zip` (carpeta fuera
  del docroot público, se crea sola si no existe).
- Borra automáticamente los respaldos con más de 30 días (`$DiasRetencion`
  al inicio del script).

## Antes de usarlo en otra máquina

Ajusta al inicio de `scripts/respaldo_bd.ps1`:

- `$MysqlDumpExe` — ruta al `mysqldump.exe` de esa instalación de Laragon/MySQL.
- `$Usuario` / `$Clave` — credenciales de un usuario de MySQL con permiso de
  lectura sobre `vigia` (no hace falta que sea `root`; basta `SELECT`, `LOCK
  TABLES` y `SHOW VIEW`).
- `$CarpetaDestino` — dónde guardar los `.zip`. Debe estar fuera de
  `public/` y, si es posible, en un disco distinto al de la BD.

## Probarlo manualmente

```powershell
powershell -ExecutionPolicy Bypass -File "C:\laragon\www\AppVigia\scripts\respaldo_bd.ps1"
```

Debe imprimir `Respaldo creado: ...` y dejar el `.zip` en `backups\`.

## Programarlo (Windows Task Scheduler)

Este paso no se ejecuta solo — regístralo manualmente para elegir el horario
y la cuenta correctos:

1. Abre **Programador de tareas** → *Crear tarea básica*.
2. Desencadenador: **Diario**, a una hora de baja actividad (p. ej. 2:00 a.m.).
3. Acción: **Iniciar un programa**.
   - Programa: `powershell.exe`
   - Argumentos: `-ExecutionPolicy Bypass -File "C:\laragon\www\AppVigia\scripts\respaldo_bd.ps1"`
4. En las propiedades de la tarea, marca **"Ejecutar tanto si el usuario
   inició sesión como si no"** para que corra aunque nadie tenga sesión
   abierta en la máquina.
5. Guarda y haz clic en **Ejecutar** una vez para confirmar que genera el
   `.zip` correctamente antes de dejarla desatendida.

## Restaurar un respaldo

```powershell
Expand-Archive backups\vigia_AAAAMMDD_HHmmss.zip -DestinationPath .
mysql -u root -p vigia < vigia_AAAAMMDD_HHmmss.sql
```

Restaura siempre sobre una base de datos vacía o de prueba primero para
confirmar que el volcado es válido antes de sobrescribir producción.

## Qué NO hace este script

- No copia los `.zip` fuera de la máquina (a otro servidor, nube, etc.). Si
  DIRSAPOL exige respaldo fuera de sitio, agrega un paso de subida (robocopy
  a una unidad de red, `aws s3 cp`, etc.) después de `Compress-Archive`.
- No respalda archivos subidos fuera de la BD — VIGÍA no guarda ningún
  archivo en disco (los `.xlsx` de importación se procesan en memoria y no
  se conservan), así que no hace falta respaldar nada más que la BD.
