<?php
/**
 * Catálogo CIE-10 oficial del MINSA (2026-10-16): tabla `cie10` cargada desde
 * sql/datos/cie10_minsa_2025-01-27.csv (generado del "CIE-10_27_01_2025.xlsx"
 * que entregó el usuario: código, descripción, estado y cotejo por sexo).
 *
 * Los campos TEXTO con "formato": "cie10" (P96, V99, B24) ya no validan solo
 * la forma del código: tiene que estar en el catálogo y activo. `clave` es el
 * código sin puntos ni espacios, en mayúsculas: el catálogo trae códigos del
 * MINSA que no siguen el formato estándar ("A1851", "Z21X1", "AB4X4") y dos
 * repetidos con otra escritura ("A18 51" / "A1851", "U06.AG" / "U06AG"); de
 * cada repetido se guarda el escrito sin espacios o, si no, el primero en
 * orden de código.
 *
 * Crea la tabla si no existe y vuelve a cargar el contenido del CSV (pisa
 * descripción, estado y sexo). Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS cie10 (
        codigo      VARCHAR(10)  NOT NULL,
        clave       VARCHAR(10)  NOT NULL,
        descripcion VARCHAR(255) NOT NULL,
        activo      TINYINT(1)   NOT NULL DEFAULT 1,
        sexo        ENUM('AMBOS','MUJER','HOMBRE') NOT NULL DEFAULT 'AMBOS',
        PRIMARY KEY (codigo),
        UNIQUE KEY uq_cie10_clave (clave),
        KEY ix_cie10_descripcion (descripcion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$archivo = __DIR__ . '/../datos/cie10_minsa_2025-01-27.csv';
$manejador = fopen($archivo, 'r');
if ($manejador === false) {
    fwrite(STDERR, "No se puede leer {$archivo}.\n");
    exit(1);
}
fgetcsv($manejador, 0, ';', '"', ''); // cabecera

$insertar = $pdo->prepare(
    'INSERT INTO cie10 (codigo, clave, descripcion, activo, sexo) VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion), activo = VALUES(activo), sexo = VALUES(sexo)'
);
$existeClave = $pdo->prepare('SELECT codigo FROM cie10 WHERE clave = ?');
$cargados = 0;
$repetidos = [];
$pdo->beginTransaction();
while (($fila = fgetcsv($manejador, 0, ';', '"', '')) !== false) {
    [$codigo, $descripcion, $estado, $sexo] = array_pad($fila, 4, '');
    $clave = strtoupper(preg_replace('/[\s.]/', '', $codigo));
    $existeClave->execute([$clave]);
    $codigoConEsaClave = $existeClave->fetchColumn();
    if ($codigoConEsaClave !== false && $codigoConEsaClave !== $codigo) {
        // Del par repetido se queda el escrito sin espacios ("A1851", no "A18 51").
        if (preg_match('/\s/', $codigoConEsaClave) && !preg_match('/\s/', $codigo)) {
            $pdo->prepare('DELETE FROM cie10 WHERE codigo = ?')->execute([$codigoConEsaClave]);
            $cargados--;
            $repetidos[] = "{$codigoConEsaClave} (= {$codigo})";
        } else {
            $repetidos[] = "{$codigo} (= {$codigoConEsaClave})";
            continue;
        }
    }
    $insertar->execute([
        $codigo,
        $clave,
        $descripcion,
        $estado === 'ACTIVO' ? 1 : 0,
        in_array($sexo, ['AMBOS', 'MUJER', 'HOMBRE'], true) ? $sexo : 'AMBOS',
    ]);
    $cargados++;
}
$pdo->commit();
fclose($manejador);

echo "cie10: {$cargados} código(s) cargados del catálogo MINSA.\n";
if ($repetidos) {
    echo 'cie10: omitidos por repetir otro código con otra escritura: ' . implode(', ', $repetidos) . ".\n";
}
