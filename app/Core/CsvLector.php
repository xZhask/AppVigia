<?php
namespace App\Core;

use RuntimeException;

/**
 * Lee un .csv (BOM UTF-8 opcional, delimitador ; o , autodetectado por la
 * fila de encabezados). Devuelve la misma forma que XlsxLector::leer() —
 * filas de ['valor' => string, 'numerico' => false] — para que el
 * controlador de importación trate ambos formatos de manera idéntica.
 */
class CsvLector
{
    public static function leer(string $ruta): array
    {
        $contenido = file_get_contents($ruta);
        if ($contenido === false || trim($contenido) === '') {
            throw new RuntimeException('El archivo CSV está vacío o no se pudo leer.');
        }

        if (str_starts_with($contenido, "\xEF\xBB\xBF")) {
            $contenido = substr($contenido, 3);
        }

        $primeraLinea = strtok($contenido, "\r\n");
        $delimitador = substr_count($primeraLinea, ';') >= substr_count($primeraLinea, ',') ? ';' : ',';

        $lineas = preg_split('/\r\n|\r|\n/', $contenido);
        $filas = [];

        foreach ($lineas as $linea) {
            if ($linea === '') {
                continue;
            }
            $valores = str_getcsv($linea, $delimitador, '"', '\\');
            $filas[] = array_map(fn($valor) => ['valor' => trim((string) $valor), 'numerico' => false], $valores);
        }

        if (empty($filas)) {
            throw new RuntimeException('El archivo CSV no tiene filas de datos.');
        }

        return $filas;
    }
}
