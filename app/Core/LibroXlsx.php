<?php
namespace App\Core;

use RuntimeException;
use ZipArchive;

/**
 * Libro .xlsx con diseño (2026-10-30) para las descargas de Fichas y
 * Reportes, sin librerías externas. Reemplaza a los CSV, que no guardan
 * ningún formato. Arial como los formatos del Área de Epidemiología y los
 * colores del sistema (theme.css).
 *
 * Una hoja se arma de arriba abajo: membrete, título, datos del encabezado
 * ("Etiqueta: valor"), una o más tablas y notas; al final se agrega sola la
 * línea "Generado por VIGÍA PNP". Las tablas aceptan encabezados de varios
 * niveles: cada columna declara su "grupo" (uno o varios niveles por encima
 * de su título) y las celdas iguales y contiguas se combinan, como en los
 * formatos en papel.
 *
 * Columna: ['titulo' => 'Fecha', 'grupo' => 'Descanso médico 1' | [...],
 *           'tipo' => texto|centro|marca|entero|decimal|porcentaje|fecha|fechahora,
 *           'ancho' => 12 (opcional; si no, según el contenido), 'anchoMax' => 45]
 * Los valores de fecha van en ISO (2026-10-30); "porcentaje" recibe la
 * fracción (0.125 = 12,5 %). Un valor que no calza con su tipo se escribe
 * como texto, nunca se pierde.
 */
class LibroXlsx
{
    private const VERDE = 'FF0A5A51';
    private const VERDE_MEDIO = 'FF0E7A6E';
    private const VERDE_SUAVE = 'FFE3F1EE';
    private const TINTA = 'FF14202B';
    private const TINTA_2 = 'FF243441';
    private const TENUE = 'FF5F6E7A';
    private const BORDE = 'FFC9D3DA';
    private const CEBRA = 'FFF6F8FA';
    private const BLANCO = 'FFFFFFFF';
    /** Columnas mínimas que ocupan el título y las notas aunque la tabla sea angosta. */
    private const ANCHO_MINIMO = 6;

    private array $hojas = [];
    private int $actual = -1;

    // Estilos: cada combinación se registra una vez (ver estilo()).
    private array $fuentes = [];
    private array $rellenos = ['<fill><patternFill patternType="none"/></fill>', '<fill><patternFill patternType="gray125"/></fill>'];
    private array $indiceRelleno = [];
    private array $xfs = ['<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'];
    private array $indiceXf = [];

    public function __construct(private string $titulo, private string $autor = '')
    {
        $this->fuente(['sz' => 10]);
    }

    /** Empieza una hoja nueva (la primera se crea sola). */
    public function hoja(string $nombre): self
    {
        $nombre = mb_substr(trim(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $nombre)) ?: 'Hoja', 0, 31);
        $usados = array_column($this->hojas, 'nombre');
        for ($base = $nombre, $n = 2; in_array($nombre, $usados, true); $n++) {
            $nombre = mb_substr($base, 0, 28) . " ($n)";
        }
        $this->hojas[] = [
            'nombre' => $nombre, 'celdas' => [], 'alturas' => [], 'anchos' => [], 'combinadas' => [],
            'fila' => 1, 'columnas' => 0, 'anchoCompleto' => [], 'congelar' => null, 'filtro' => null, 'titulosImpresion' => null,
        ];
        $this->actual = count($this->hojas) - 1;
        return $this;
    }

    /** Líneas institucionales pequeñas sobre el título ("POLICÍA NACIONAL DEL PERÚ"). */
    public function membrete(array $lineas): self
    {
        foreach ($lineas as $linea) {
            $this->lineaCompleta($linea, ['b' => true, 'sz' => 9, 'color' => self::VERDE]);
        }
        return $this;
    }

    public function titulo(string $texto): self
    {
        $this->lineaCompleta($texto, ['b' => true, 'sz' => 14, 'color' => self::TINTA], 22);
        return $this;
    }

    public function subtitulo(string $texto): self
    {
        $this->lineaCompleta($texto, ['sz' => 10, 'color' => self::TENUE]);
        return $this;
    }

    /**
     * Datos del encabezado: "Etiqueta: valor", varios por línea.
     *
     * @param array $pares [etiqueta => valor] o lista de [etiqueta, valor]
     */
    public function datos(array $pares, int $porLinea = 3): self
    {
        $lista = array_is_list($pares) ? $pares : array_map(null, array_keys($pares), array_values($pares));
        foreach (array_chunk($lista, max(1, $porLinea)) as $grupo) {
            $tramos = [];
            foreach ($grupo as $i => [$etiqueta, $valor]) {
                $tramos[] = [($i > 0 ? '      ' : '') . $etiqueta . ': ', ['b' => true, 'color' => self::TENUE]];
                $tramos[] = [(string) ($valor ?? '') !== '' ? (string) $valor : '—', ['color' => self::TINTA]];
            }
            $this->lineaCompleta($tramos, ['sz' => 10]);
        }
        return $this->espacio();
    }

    /** Título de una tabla dentro de la hoja (el consolidado tiene tres). */
    public function seccion(string $texto): self
    {
        $this->lineaCompleta($texto, ['b' => true, 'sz' => 11, 'color' => self::VERDE], 18);
        return $this;
    }

    public function nota(string $texto): self
    {
        $this->lineaCompleta($texto, ['sz' => 9, 'color' => self::TENUE, 'wrap' => true, 'v' => 'top']);
        return $this;
    }

    public function espacio(int $filas = 1): self
    {
        $this->hojaActual();
        $this->hojas[$this->actual]['fila'] += $filas;
        return $this;
    }

    /**
     * @param array $columnas ver la descripción de la clase
     * @param array $filas    listas de valores, en el orden de $columnas
     * @param array $opciones totales (fila con la etiqueta en la primera celda),
     *                        congelar (fija el encabezado), filtro (filtros de Excel),
     *                        vacio (texto cuando no hay filas)
     */
    public function tabla(array $columnas, array $filas, array $opciones = []): self
    {
        $h = &$this->hojaActual();
        $columnas = array_values($columnas);
        $n = count($columnas);
        $h['columnas'] = max($h['columnas'], $n);
        $rutas = array_map(fn($c) => array_merge(array_values((array) ($c['grupo'] ?? [])), [$c['titulo']]), $columnas);
        $niveles = max(array_map('count', $rutas));
        $inicio = $h['fila'];
        $ultimaCabecera = $inicio + $niveles - 1;

        // Anchos: según el contenido, salvo que la columna traiga el suyo.
        foreach ($columnas as $c => $col) {
            if (isset($col['ancho'])) {
                $ancho = (float) $col['ancho'];
            } else {
                $necesario = self::anchoEncabezado((string) $col['titulo']);
                foreach ($filas as $fila) {
                    $necesario = max($necesario, self::anchoValor($fila[$c] ?? null, $col['tipo'] ?? 'texto'));
                }
                foreach ([$opciones['totales'] ?? []] as $tot) {
                    $necesario = max($necesario, self::anchoValor($tot[$c] ?? null, 'texto'));
                }
                $ancho = min(max($necesario * 1.1 + 2, 5), (float) ($col['anchoMax'] ?? 45));
            }
            $h['anchos'][$c] = max($h['anchos'][$c] ?? 0, $ancho);
        }

        // Encabezado de varios niveles.
        for ($l = 0; $l < $niveles; $l++) {
            $estilo = $this->estilo([
                'b' => true, 'sz' => 9, 'color' => self::BLANCO, 'relleno' => $l === 0 ? self::VERDE : self::VERDE_MEDIO,
                'borde' => true, 'h' => 'center', 'wrap' => true,
            ]);
            for ($c = 0; $c < $n;) {
                if (count($rutas[$c]) <= $l) {
                    $c++;
                    continue;
                }
                $prefijo = array_slice($rutas[$c], 0, $l + 1);
                $fin = $c;
                while ($fin + 1 < $n && count($rutas[$fin + 1]) > $l && array_slice($rutas[$fin + 1], 0, $l + 1) === $prefijo) {
                    $fin++;
                }
                $filaFin = count($rutas[$c]) === $l + 1 ? $ultimaCabecera : $inicio + $l;
                for ($f = $inicio + $l; $f <= $filaFin; $f++) {
                    for ($k = $c; $k <= $fin; $k++) {
                        $h['celdas'][$f][$k] = $f === $inicio + $l && $k === $c
                            ? self::celdaTexto($f, $k, (string) $rutas[$c][$l], $estilo)
                            : self::celdaVacia($f, $k, $estilo);
                    }
                }
                if ($fin > $c || $filaFin > $inicio + $l) {
                    $h['combinadas'][] = self::ref($inicio + $l, $c) . ':' . self::ref($filaFin, $fin);
                }
                // Alto del renglón: el texto se ajusta al ancho de lo combinado.
                $anchoSpan = 0;
                for ($k = $c; $k <= $fin; $k++) {
                    $anchoSpan += $h['anchos'][$k];
                }
                $lineas = self::lineas((string) $rutas[$c][$l], $anchoSpan) / ($filaFin - ($inicio + $l) + 1);
                $h['alturas'][$inicio + $l] = max($h['alturas'][$inicio + $l] ?? 15, min(ceil($lineas) * 12 + 4, 80));
                $c = $fin + 1;
            }
        }

        // Datos, con filas alternadas.
        $f = $ultimaCabecera + 1;
        $primeraDato = $f;
        if (!$filas && isset($opciones['vacio'])) {
            $estilo = $this->estilo(['i' => true, 'color' => self::TENUE, 'borde' => true, 'h' => 'center']);
            for ($k = 0; $k < $n; $k++) {
                $h['celdas'][$f][$k] = $k === 0 ? self::celdaTexto($f, 0, (string) $opciones['vacio'], $estilo) : self::celdaVacia($f, $k, $estilo);
            }
            $h['combinadas'][] = self::ref($f, 0) . ':' . self::ref($f, $n - 1);
            $f++;
        }
        foreach ($filas as $i => $fila) {
            $this->escribirFila($h, $f, $columnas, array_values($fila), $i % 2 === 1 ? self::CEBRA : null, false);
            $f++;
        }
        if (!empty($opciones['totales'])) {
            $this->escribirFila($h, $f, $columnas, array_values($opciones['totales']), self::VERDE_SUAVE, true);
            $f++;
        }
        $ultimaDato = $f - 1;

        if (!empty($opciones['congelar']) && $h['congelar'] === null) {
            $h['congelar'] = $ultimaCabecera;
            $h['titulosImpresion'] = [$inicio, $ultimaCabecera];
        }
        if (!empty($opciones['filtro']) && $h['filtro'] === null && $filas) {
            $h['filtro'] = self::ref($ultimaCabecera, 0) . ':' . self::ref(max($ultimaDato, $ultimaCabecera), $n - 1);
        }
        $h['fila'] = $f + 1;
        return $this;
    }

    /** El archivo .xlsx en memoria. */
    public function bytes(): string
    {
        $this->hojaActual();
        $rutaTemporal = tempnam(sys_get_temp_dir(), 'vigia_libro_');
        $zip = new ZipArchive();
        if ($zip->open($rutaTemporal, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo .xlsx temporal.');
        }
        $hojasXml = [];
        foreach ($this->hojas as $i => $hoja) {
            $hojasXml[$i] = $this->hojaXml($hoja, $i);
        }
        $zip->addFromString('[Content_Types].xml', $this->tiposContenido());
        $zip->addFromString('_rels/.rels', self::XML . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '</Relationships>');
        $zip->addFromString('docProps/core.xml', $this->propiedades());
        $zip->addFromString('xl/workbook.xml', $this->libroXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->libroRels());
        $zip->addFromString('xl/styles.xml', $this->estilosXml());
        foreach ($hojasXml as $i => $xml) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $xml);
        }
        $zip->close();
        $bytes = file_get_contents($rutaTemporal);
        unlink($rutaTemporal);
        return $bytes;
    }

    /** Envía el archivo al navegador. El llamador termina con exit. */
    public function descargar(string $nombreSinExtension): void
    {
        $bytes = $this->bytes();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $nombreSinExtension) . '.xlsx"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: private, max-age=0');
        echo $bytes;
    }

    // ------------------------------------------------------------ escritura

    private const XML = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';

    private function &hojaActual(): array
    {
        if ($this->actual < 0) {
            $this->hoja($this->titulo);
        }
        return $this->hojas[$this->actual];
    }

    /**
     * Línea que ocupa todo el ancho de la hoja (se combina al armarla, cuando
     * ya se sabe cuántas columnas tiene la tabla). $texto puede ser una lista
     * de tramos [texto, estilo de fuente] para mezclar negrita y normal.
     */
    private function lineaCompleta(string|array $texto, array $estilo, ?float $alto = null): void
    {
        $h = &$this->hojaActual();
        $f = $h['fila'];
        $h['anchoCompleto'][$f] = ['texto' => $texto, 'estilo' => $estilo, 'alto' => $alto];
        $h['fila']++;
    }

    private function escribirFila(array &$h, int $f, array $columnas, array $valores, ?string $relleno, bool $total): void
    {
        $necesarias = 1;
        foreach ($columnas as $c => $col) {
            $tipo = $col['tipo'] ?? 'texto';
            $valor = $valores[$c] ?? null;
            $base = ['borde' => true, 'relleno' => $relleno, 'b' => $total, 'color' => $total ? self::TINTA : self::TINTA_2];
            [$xml, $largo] = $this->celdaDato($f, $c, $valor, $tipo, $base, $h['anchos'][$c] ?? 10);
            $h['celdas'][$f][$c] = $xml;
            $necesarias = max($necesarias, $largo);
        }
        if ($necesarias > 1) {
            $h['alturas'][$f] = min($necesarias * 12.5 + 3, 120);
        }
    }

    /** @return array{0: string, 1: int} xml de la celda y líneas que ocupa */
    private function celdaDato(int $f, int $c, mixed $valor, string $tipo, array $base, float $ancho): array
    {
        if ($valor === null || $valor === '' || $valor === false) {
            return [self::celdaVacia($f, $c, $this->estilo(array_merge($base, ['h' => in_array($tipo, ['centro', 'marca'], true) ? 'center' : null]))), 1];
        }
        if ($tipo === 'marca') {
            return [self::celdaTexto($f, $c, $valor === true ? 'X' : (string) $valor, $this->estilo(array_merge($base, ['h' => 'center', 'b' => true]))), 1];
        }
        // "centro" con un entero de PHP (N.°, SE, año): número sin separador de
        // miles. Un texto como "0123" sigue como texto y conserva el cero.
        if ($tipo === 'centro' && is_int($valor)) {
            return [self::celdaNumero($f, $c, (float) $valor, $this->estilo(array_merge($base, ['fmt' => 1, 'h' => 'center']))), 1];
        }
        $numerico = ['entero' => 3, 'decimal' => 164, 'porcentaje' => 166];
        if (isset($numerico[$tipo]) && is_numeric($valor)) {
            return [self::celdaNumero($f, $c, (float) $valor, $this->estilo(array_merge($base, ['fmt' => $numerico[$tipo], 'h' => 'right']))), 1];
        }
        if (in_array($tipo, ['fecha', 'fechahora'], true) && ($serial = self::serialFecha((string) $valor)) !== null) {
            $fmt = $tipo === 'fechahora' && str_contains((string) $valor, ':') ? 167 : 165;
            return [self::celdaNumero($f, $c, $serial, $this->estilo(array_merge($base, ['fmt' => $fmt, 'h' => 'center']))), 1];
        }
        $texto = (string) $valor;
        $lineas = self::lineas($texto, $ancho);
        $estilo = $this->estilo($base + [
            'fmt' => 49,
            'h' => $tipo === 'centro' ? 'center' : (isset($numerico[$tipo]) ? 'right' : null),
            'wrap' => $lineas > 1,
        ]);
        return [self::celdaTexto($f, $c, $texto, $estilo), $lineas];
    }

    private function hojaXml(array $h, int $indice): string
    {
        $columnas = max($h['columnas'], self::ANCHO_MINIMO);
        for ($c = 0; $c < $columnas; $c++) {
            $h['anchos'][$c] ??= 12;
        }
        ksort($h['anchos']);
        $anchoTotal = array_sum($h['anchos']);

        // Líneas de ancho completo: combinadas sobre todas las columnas.
        foreach ($h['anchoCompleto'] as $f => $linea) {
            $estilo = $this->estilo($linea['estilo'] + ['wrap' => true, 'v' => $linea['estilo']['v'] ?? 'center']);
            $h['celdas'][$f][0] = is_array($linea['texto'])
                ? $this->celdaRica($f, 0, $linea['texto'], $estilo, $linea['estilo'])
                : self::celdaTexto($f, 0, $linea['texto'], $estilo);
            $h['combinadas'][] = self::ref($f, 0) . ':' . self::ref($f, $columnas - 1);
            $texto = is_array($linea['texto']) ? implode('', array_column($linea['texto'], 0)) : $linea['texto'];
            $tamano = $linea['estilo']['sz'] ?? 10;
            $h['alturas'][$f] = $linea['alto'] ?? max(15, self::lineas($texto, $anchoTotal * 10 / $tamano) * ($tamano + 3) + 2);
        }

        // Pie: quién y cuándo.
        $f = $h['fila'] + 1;
        $pie = 'Generado por VIGÍA PNP el ' . date('d/m/Y H:i') . ($this->autor !== '' ? ' · ' . $this->autor : '');
        $h['celdas'][$f][0] = self::celdaTexto($f, 0, $pie, $this->estilo(['i' => true, 'sz' => 8, 'color' => self::TENUE]));
        $h['combinadas'][] = self::ref($f, 0) . ':' . self::ref($f, $columnas - 1);

        $vista = '<sheetView workbookViewId="0" showGridLines="0" zoomScale="100"' . ($indice === 0 ? ' tabSelected="1"' : '') . '>';
        if ($h['congelar'] !== null) {
            $vista .= '<pane ySplit="' . $h['congelar'] . '" topLeftCell="A' . ($h['congelar'] + 1) . '" activePane="bottomLeft" state="frozen"/>'
                . '<selection pane="bottomLeft" activeCell="A' . ($h['congelar'] + 1) . '" sqref="A' . ($h['congelar'] + 1) . '"/>';
        }
        $vista .= '</sheetView>';

        $cols = '<cols>';
        foreach ($h['anchos'] as $c => $ancho) {
            $cols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . round($ancho, 2) . '" customWidth="1"/>';
        }
        $cols .= '</cols>';

        ksort($h['celdas']);
        $datos = '';
        foreach ($h['celdas'] as $fila => $celdas) {
            ksort($celdas);
            $alto = isset($h['alturas'][$fila]) ? ' ht="' . round($h['alturas'][$fila], 2) . '" customHeight="1"' : '';
            $datos .= '<row r="' . $fila . '"' . $alto . '>' . implode('', $celdas) . '</row>';
        }

        $titulo = self::xml($this->titulo);
        return self::XML . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<sheetViews>' . $vista . '</sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $cols
            . '<sheetData>' . $datos . '</sheetData>'
            . ($h['filtro'] ? '<autoFilter ref="' . $h['filtro'] . '"/>' : '')
            . ($h['combinadas'] ? '<mergeCells count="' . count($h['combinadas']) . '">' . implode('', array_map(fn($r) => '<mergeCell ref="' . $r . '"/>', $h['combinadas'])) . '</mergeCells>' : '')
            . '<printOptions horizontalCentered="1"/>'
            . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.6" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . '<headerFooter><oddFooter>&amp;L&amp;8VIGÍA PNP · ' . $titulo . '&amp;R&amp;8Página &amp;P de &amp;N</oddFooter></headerFooter>'
            . '</worksheet>';
    }

    private function libroXml(): string
    {
        $hojas = '';
        $nombres = '';
        foreach ($this->hojas as $i => $h) {
            $hojas .= '<sheet name="' . self::xml($h['nombre']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
            $citada = "'" . str_replace("'", "''", $h['nombre']) . "'";
            if ($h['filtro']) {
                [$desde, $hasta] = explode(':', $h['filtro']);
                $nombres .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">'
                    . self::xml($citada . '!' . self::absoluta($desde) . ':' . self::absoluta($hasta)) . '</definedName>';
            }
            if ($h['titulosImpresion']) {
                $nombres .= '<definedName name="_xlnm.Print_Titles" localSheetId="' . $i . '">'
                    . self::xml($citada . '!$' . $h['titulosImpresion'][0] . ':$' . $h['titulosImpresion'][1]) . '</definedName>';
            }
        }
        return self::XML . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<bookViews><workbookView activeTab="0"/></bookViews>'
            . '<sheets>' . $hojas . '</sheets>'
            . ($nombres ? '<definedNames>' . $nombres . '</definedNames>' : '')
            . '</workbook>';
    }

    private function libroRels(): string
    {
        $rels = '';
        foreach ($this->hojas as $i => $h) {
            $rels .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . (count($this->hojas) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return self::XML . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    private function tiposContenido(): string
    {
        $hojas = '';
        foreach (array_keys($this->hojas) as $i) {
            $hojas .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return self::XML . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . $hojas
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '</Types>';
    }

    private function propiedades(): string
    {
        $ahora = gmdate('Y-m-d\TH:i:s\Z');
        return self::XML . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . self::xml($this->titulo) . '</dc:title>'
            . '<dc:creator>' . self::xml($this->autor !== '' ? $this->autor : 'VIGÍA PNP') . '</dc:creator>'
            . '<cp:keywords>VIGÍA PNP</cp:keywords>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $ahora . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $ahora . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    // ------------------------------------------------------------ estilos

    /**
     * Índice de un estilo de celda: fuente (b, i, sz, color), relleno, borde
     * fino, formato numérico (fmt) y alineación (h, v, wrap).
     */
    private function estilo(array $p): int
    {
        $p += ['b' => false, 'i' => false, 'sz' => 10, 'color' => self::TINTA_2, 'relleno' => null, 'borde' => false, 'fmt' => 0, 'h' => null, 'v' => 'center', 'wrap' => false];
        $clave = json_encode($p);
        if (isset($this->indiceXf[$clave])) {
            return $this->indiceXf[$clave];
        }
        $fuente = $this->fuente($p);
        $relleno = 0;
        if ($p['relleno']) {
            $relleno = $this->indiceRelleno[$p['relleno']] ??= array_push($this->rellenos,
                '<fill><patternFill patternType="solid"><fgColor rgb="' . $p['relleno'] . '"/><bgColor indexed="64"/></patternFill></fill>') - 1;
        }
        $alineacion = '<alignment' . ($p['h'] ? ' horizontal="' . $p['h'] . '"' : '') . ' vertical="' . $p['v'] . '"' . ($p['wrap'] ? ' wrapText="1"' : '') . '/>';
        $this->xfs[] = '<xf numFmtId="' . $p['fmt'] . '" fontId="' . $fuente . '" fillId="' . $relleno . '" borderId="' . ($p['borde'] ? 1 : 0) . '" xfId="0"'
            . ' applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">' . $alineacion . '</xf>';
        return $this->indiceXf[$clave] = count($this->xfs) - 1;
    }

    private function fuente(array $p): int
    {
        $xml = '<font>' . (!empty($p['b']) ? '<b/>' : '') . (!empty($p['i']) ? '<i/>' : '')
            . '<sz val="' . ($p['sz'] ?? 10) . '"/><color rgb="' . ($p['color'] ?? self::TINTA_2) . '"/><name val="Arial"/><family val="2"/></font>';
        $i = array_search($xml, $this->fuentes, true);
        if ($i === false) {
            $this->fuentes[] = $xml;
            $i = count($this->fuentes) - 1;
        }
        return $i;
    }

    private function estilosXml(): string
    {
        $borde = '<border><left style="thin"><color rgb="' . self::BORDE . '"/></left><right style="thin"><color rgb="' . self::BORDE . '"/></right>'
            . '<top style="thin"><color rgb="' . self::BORDE . '"/></top><bottom style="thin"><color rgb="' . self::BORDE . '"/></bottom><diagonal/></border>';
        return self::XML . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="4"><numFmt numFmtId="164" formatCode="0.0"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy"/>'
            . '<numFmt numFmtId="166" formatCode="0.0%"/><numFmt numFmtId="167" formatCode="dd/mm/yyyy hh:mm"/></numFmts>'
            . '<fonts count="' . count($this->fuentes) . '">' . implode('', $this->fuentes) . '</fonts>'
            . '<fills count="' . count($this->rellenos) . '">' . implode('', $this->rellenos) . '</fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>' . $borde . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($this->xfs) . '">' . implode('', $this->xfs) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    // ------------------------------------------------------------ celdas

    private static function celdaTexto(int $f, int $c, string $texto, int $estilo): string
    {
        return '<c r="' . self::ref($f, $c) . '" s="' . $estilo . '" t="inlineStr"><is><t xml:space="preserve">' . self::xml($texto) . '</t></is></c>';
    }

    private static function celdaNumero(int $f, int $c, float $numero, int $estilo): string
    {
        return '<c r="' . self::ref($f, $c) . '" s="' . $estilo . '"><v>' . rtrim(rtrim(sprintf('%.10F', $numero), '0'), '.') . '</v></c>';
    }

    private static function celdaVacia(int $f, int $c, int $estilo): string
    {
        return '<c r="' . self::ref($f, $c) . '" s="' . $estilo . '"/>';
    }

    /** Texto con tramos de distinto estilo: "Red de salud: " en negrita y el valor normal. */
    private function celdaRica(int $f, int $c, array $tramos, int $estilo, array $base): string
    {
        $runs = '';
        foreach ($tramos as [$texto, $formato]) {
            $p = $formato + $base;
            $runs .= '<r><rPr>' . (!empty($p['b']) ? '<b/>' : '') . (!empty($p['i']) ? '<i/>' : '')
                . '<sz val="' . ($p['sz'] ?? 10) . '"/><color rgb="' . ($p['color'] ?? self::TINTA_2) . '"/><rFont val="Arial"/><family val="2"/></rPr>'
                . '<t xml:space="preserve">' . self::xml((string) $texto) . '</t></r>';
        }
        return '<c r="' . self::ref($f, $c) . '" s="' . $estilo . '" t="inlineStr"><is>' . $runs . '</is></c>';
    }

    // ------------------------------------------------------------ utilidades

    private static function ref(int $fila, int $columnaCero): string
    {
        $letra = '';
        for ($n = $columnaCero + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letra = chr(65 + ($n - 1) % 26) . $letra;
        }
        return $letra . $fila;
    }

    private static function absoluta(string $ref): string
    {
        return preg_replace('/^([A-Z]+)(\d+)$/', '\$$1\$$2', $ref);
    }

    private static function xml(string $texto): string
    {
        // Quita los caracteres de control que XML no admite (rompen el archivo).
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $texto) ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** 2026-10-30 (o con hora) → número de serie de Excel; null si no es fecha. */
    private static function serialFecha(string $valor): ?float
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/', trim($valor), $m)
            || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }
        $dias = gmmktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]) / 86400 + 25569;
        $fraccion = isset($m[4]) ? ((int) $m[4] * 3600 + (int) $m[5] * 60 + (int) ($m[6] ?? 0)) / 86400 : 0;
        return $dias + $fraccion;
    }

    /** Ancho que pide un título de columna que puede partirse en líneas. */
    private static function anchoEncabezado(string $titulo): float
    {
        $palabras = preg_split('/\s+/', trim($titulo)) ?: [''];
        return max(max(array_map('mb_strlen', $palabras)), min(mb_strlen($titulo), 14));
    }

    private static function anchoValor(mixed $valor, string $tipo): float
    {
        if ($valor === null || $valor === '') {
            return 0;
        }
        if (in_array($tipo, ['fecha', 'fechahora'], true) && self::serialFecha((string) $valor) !== null) {
            return $tipo === 'fechahora' && str_contains((string) $valor, ':') ? 16 : 10;
        }
        if ($tipo === 'porcentaje' && is_numeric($valor)) {
            return 6;
        }
        return mb_strlen((string) $valor);
    }

    /** Líneas que ocupa un texto en un ancho de columna (aproximado, Arial 10). */
    private static function lineas(string $texto, float $ancho): int
    {
        $porLinea = max(1, (int) floor(($ancho - 1) * 1.05));
        $total = 0;
        foreach (explode("\n", $texto) as $parrafo) {
            $total += max(1, (int) ceil(mb_strlen($parrafo) / $porLinea));
        }
        return $total;
    }
}
