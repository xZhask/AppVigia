<?php
namespace App\Core;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Lee un .xlsx real (ZipArchive + SimpleXML) sin ninguna librería externa.
 * Devuelve cada celda como ['valor' => string, 'numerico' => bool] — el
 * llamador decide, columna por columna, si un valor numérico es en realidad
 * un número serial de fecha de Excel (vía serialAFecha()) o un número común
 * (p. ej. un campo NUMERO del motor de fichas). Los huecos que Excel omite
 * se rellenan con celdas vacías; nunca se asume la celda N-ésima por
 * posición secuencial, siempre se ubica por el atributo `r` de la celda.
 */
class XlsxLector
{
    public static function leer(string $ruta): array
    {
        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) {
            throw new RuntimeException('El archivo no es un .xlsx válido (no se pudo abrir como zip).');
        }

        try {
            $compartidas = self::leerCadenasCompartidas($zip);
            $rutaHoja = self::resolverPrimeraHoja($zip);
            $xmlHoja = $zip->getFromName($rutaHoja);
            if ($xmlHoja === false) {
                throw new RuntimeException('El .xlsx no tiene una hoja de cálculo legible.');
            }

            $hoja = self::cargarXml($xmlHoja);
        } finally {
            $zip->close();
        }

        return self::filasDesdeHoja($hoja, $compartidas);
    }

    /**
     * Convierte un número serial de fecha de Excel (época 1900) a 'Y-m-d'.
     * 25569 = días entre 1900-01-01 y 1970-01-01, ya ajustado para el "29 de
     * febrero de 1900" ficticio que Excel arrastra por compatibilidad.
     */
    public static function serialAFecha(string $valor): ?string
    {
        if (!is_numeric($valor)) {
            return null;
        }

        $serial = (float) $valor;
        if ($serial <= 0) {
            return null;
        }

        $timestamp = ($serial - 25569) * 86400;

        return gmdate('Y-m-d', (int) round($timestamp));
    }

    private static function cargarXml(string $xml): SimpleXMLElement
    {
        $anterior = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $elemento = simplexml_load_string($xml);
        $errores = libxml_get_errors();
        libxml_use_internal_errors($anterior);

        if ($elemento === false || !empty($errores)) {
            throw new RuntimeException('El .xlsx tiene un XML interno corrupto o incompleto.');
        }

        return $elemento;
    }

    private static function leerCadenasCompartidas(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $sxml = self::cargarXml($xml);
        $compartidas = [];
        foreach ($sxml->si as $si) {
            if (isset($si->t)) {
                $compartidas[] = (string) $si->t;
                continue;
            }
            $texto = '';
            foreach ($si->r as $tramo) {
                $texto .= (string) $tramo->t;
            }
            $compartidas[] = $texto;
        }

        return $compartidas;
    }

    /**
     * Resuelve la ruta real de la primera hoja vía workbook.xml y su .rels
     * (no se asume "sheet1.xml": un .xlsx ajeno puede nombrarla distinto).
     */
    private static function resolverPrimeraHoja(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            throw new RuntimeException('El .xlsx no tiene la estructura mínima esperada (workbook.xml).');
        }

        $workbook = self::cargarXml($workbookXml);
        $primeraHoja = $workbook->sheets->sheet[0] ?? null;
        if ($primeraHoja === null) {
            throw new RuntimeException('El .xlsx no tiene ninguna hoja.');
        }

        $atributosR = $primeraHoja->attributes('r', true);
        $rId = (string) $atributosR['id'];

        $rels = self::cargarXml($relsXml);
        foreach ($rels->Relationship as $rel) {
            if ((string) $rel['Id'] === $rId) {
                return 'xl/' . ltrim((string) $rel['Target'], '/');
            }
        }

        throw new RuntimeException('No se pudo ubicar la hoja dentro del .xlsx.');
    }

    private static function columnaDesdeRef(string $ref): int
    {
        preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
        $letras = $m[1] ?? 'A';

        $indice = 0;
        foreach (str_split($letras) as $letra) {
            $indice = $indice * 26 + (ord($letra) - 64);
        }

        return $indice - 1; // 0-based
    }

    private static function filasDesdeHoja(SimpleXMLElement $hoja, array $compartidas): array
    {
        $filasCrudas = [];
        $maxColumna = -1;

        foreach ($hoja->sheetData->row as $row) {
            $numeroFila = (int) $row['r'];
            $celdas = [];

            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $columna = self::columnaDesdeRef($ref);
                $tipo = (string) $c['t'];

                if ($tipo === 's') {
                    $indice = (int) $c->v;
                    $valor = $compartidas[$indice] ?? '';
                    $celdas[$columna] = ['valor' => $valor, 'numerico' => false];
                } elseif ($tipo === 'inlineStr') {
                    $valor = (string) ($c->is->t ?? '');
                    $celdas[$columna] = ['valor' => $valor, 'numerico' => false];
                } elseif ($tipo === 'str') {
                    $celdas[$columna] = ['valor' => (string) $c->v, 'numerico' => false];
                } else {
                    $valor = isset($c->v) ? (string) $c->v : '';
                    $celdas[$columna] = ['valor' => $valor, 'numerico' => $valor !== ''];
                }

                $maxColumna = max($maxColumna, $columna);
            }

            $filasCrudas[$numeroFila] = $celdas;
        }

        if (empty($filasCrudas)) {
            return [];
        }

        $vacia = ['valor' => '', 'numerico' => false];
        $filas = [];
        foreach (range(1, max(array_keys($filasCrudas))) as $numeroFila) {
            $celdasFila = $filasCrudas[$numeroFila] ?? [];
            $fila = [];
            foreach (range(0, $maxColumna) as $columna) {
                $fila[] = $celdasFila[$columna] ?? $vacia;
            }
            $filas[] = $fila;
        }

        return $filas;
    }
}
