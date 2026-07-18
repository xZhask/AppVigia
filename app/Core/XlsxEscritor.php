<?php
namespace App\Core;

use RuntimeException;
use ZipArchive;

/**
 * Genera un .xlsx real (un zip con XML) sin ninguna librería externa, para
 * la plantilla de importación. Una sola hoja, sin fórmulas, celdas como
 * cadenas en línea (inlineStr) — no hace falta sharedStrings.xml para
 * escribir. Las columnas indicadas quedan preformateadas como texto para
 * que Excel no recorte ceros iniciales ni reconvierta fechas.
 */
class XlsxEscritor
{
    /**
     * @param string[] $encabezados fila 1
     * @param string[] $filaEjemplo fila 2, mismo orden/longitud que $encabezados
     * @param int[]    $indicesTexto índices 0-based de columnas a forzar como texto
     */
    public static function generar(array $encabezados, array $filaEjemplo, array $indicesTexto): string
    {
        return self::generarConFilas($encabezados, [$filaEjemplo], $indicesTexto);
    }

    /**
     * Igual que generar(), pero acepta varias filas de datos (fila 2 en
     * adelante) en vez de una sola fila de ejemplo. Usado también por las
     * pruebas de la Fase 6 para construir archivos de prueba con varias filas.
     *
     * @param string[]   $encabezados
     * @param string[][] $filas
     * @param int[]      $indicesTexto
     */
    public static function generarConFilas(array $encabezados, array $filas, array $indicesTexto): string
    {
        $rutaTemporal = tempnam(sys_get_temp_dir(), 'vigia_xlsx_');
        $zip = new ZipArchive();

        if ($zip->open($rutaTemporal, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo .xlsx temporal.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::relsRaiz());
        $zip->addFromString('xl/workbook.xml', self::workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/styles.xml', self::estilos());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::hoja($encabezados, $filas, $indicesTexto));
        $zip->close();

        $bytes = file_get_contents($rutaTemporal);
        unlink($rutaTemporal);

        return $bytes;
    }

    private static function columnaLetra(int $indiceCero): string
    {
        $letra = '';
        $n = $indiceCero + 1;
        while ($n > 0) {
            $resto = ($n - 1) % 26;
            $letra = chr(65 + $resto) . $letra;
            $n = intdiv($n - 1, 26);
        }

        return $letra;
    }

    private static function textoXml(string $valor): string
    {
        return htmlspecialchars($valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function celdaTexto(int $indiceColumna, int $fila, string $valor, ?int $estilo = null): string
    {
        $ref = self::columnaLetra($indiceColumna) . $fila;
        $atributoEstilo = $estilo !== null ? " s=\"$estilo\"" : '';

        return "<c r=\"$ref\" t=\"inlineStr\"$atributoEstilo><is><t xml:space=\"preserve\">" . self::textoXml($valor) . '</t></is></c>';
    }

    /**
     * @param string[][] $filas
     */
    private static function hoja(array $encabezados, array $filas, array $indicesTexto): string
    {
        $indicesTexto = array_flip($indicesTexto);
        $totalColumnas = count($encabezados);

        $cols = '<cols>';
        for ($i = 0; $i < $totalColumnas; $i++) {
            $estiloColumna = isset($indicesTexto[$i]) ? 1 : 0;
            $col = $i + 1;
            $cols .= "<col min=\"$col\" max=\"$col\" width=\"20\" style=\"$estiloColumna\" customWidth=\"1\"/>";
        }
        $cols .= '</cols>';

        $sheetData = '<row r="1">';
        foreach (array_values($encabezados) as $i => $texto) {
            $sheetData .= self::celdaTexto($i, 1, $texto, 2);
        }
        $sheetData .= '</row>';

        foreach ($filas as $j => $fila) {
            $numeroFila = $j + 2;
            $sheetData .= "<row r=\"$numeroFila\">";
            foreach (array_values($fila) as $i => $texto) {
                $sheetData .= self::celdaTexto($i, $numeroFila, (string) $texto);
            }
            $sheetData .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $cols
            . '<sheetData>' . $sheetData . '</sheetData>'
            . '</worksheet>';
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function relsRaiz(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Datos" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function estilos(): string
    {
        // xfId 0 = General, 1 = Texto (numFmtId 49, el "Text" nativo de Excel),
        // 2 = encabezado (negrita).
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }
}
