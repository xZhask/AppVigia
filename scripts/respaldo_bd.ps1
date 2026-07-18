# VIGÍA · Respaldo programado de la base de datos.
# Genera un dump comprimido de "vigia" con fecha en el nombre y borra los
# respaldos con más de $DiasRetencion días. Pensado para registrarse en el
# Programador de tareas de Windows (ver docs/respaldo-bd.md).

$ErrorActionPreference = "Stop"

$MysqlDumpExe   = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe"
$BaseDatos      = "vigia"
$Usuario        = "root"
$Clave          = ""
$CarpetaDestino = "C:\laragon\www\AppVigia\backups"
$DiasRetencion  = 30

if (-not (Test-Path $CarpetaDestino)) {
    New-Item -ItemType Directory -Path $CarpetaDestino | Out-Null
}

$fecha = Get-Date -Format "yyyyMMdd_HHmmss"
$rutaSql = Join-Path $CarpetaDestino "vigia_$fecha.sql"
$rutaZip = Join-Path $CarpetaDestino "vigia_$fecha.zip"

$argumentos = @("--user=$Usuario", "--single-transaction", "--routines", "--triggers", $BaseDatos)
if ($Clave -ne "") {
    $argumentos = @("--password=$Clave") + $argumentos
}

try {
    & $MysqlDumpExe @argumentos | Out-File -FilePath $rutaSql -Encoding utf8

    Compress-Archive -Path $rutaSql -DestinationPath $rutaZip -Force
    Remove-Item $rutaSql

    Get-ChildItem $CarpetaDestino -Filter "vigia_*.zip" |
        Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$DiasRetencion) } |
        Remove-Item -Force

    Write-Output "Respaldo creado: $rutaZip"
} catch {
    Write-Error "Fallo el respaldo de VIGIA: $($_.Exception.Message)"
    exit 1
}
