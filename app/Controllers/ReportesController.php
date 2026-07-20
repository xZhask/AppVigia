<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Caso;
use App\Models\Enfermedad;

class ReportesController extends Controller
{
    private const AGRUPACIONES_VALIDAS = ['establecimiento', 'red', 'semana', 'clasificacion', 'categoria_pnp', 'nivel'];

    private const ETIQUETAS_AGRUPACION = [
        'establecimiento' => 'Establecimiento',
        'red'             => 'Red de salud',
        'semana'          => 'Semana epidemiológica',
        'clasificacion'   => 'Clasificación',
        'categoria_pnp'   => 'Categoría PNP',
        'nivel'           => 'Nivel de grado PNP',
    ];

    public function index(): void
    {
        [$enfermedadId, $agrupacion, $rangoSe] = $this->leerFiltros();

        $filtros = ['enfermedad_id' => $enfermedadId, 'rango_se' => $rangoSe];
        $filas = Caso::reportePorAgrupacion($agrupacion, $filtros);
        $totalEstablecimientos = count(Caso::reportePorAgrupacion('establecimiento', $filtros));

        $this->vista('reportes/index', [
            'tituloVista' => 'Reportes',
            'rutaActual'  => 'reportes',
            'enfermedades' => Enfermedad::todos('nombre'),
            'enfermedadId' => $enfermedadId,
            'agrupacion'  => $agrupacion,
            'etiquetaColumna' => self::ETIQUETAS_AGRUPACION[$agrupacion],
            'rangoSe'     => $rangoSe,
            'filas'       => $filas,
            'distribucionClasificacion' => Caso::distribucionPorClasificacion($filtros),
            'totalPeriodo' => array_sum(array_column($filas, 'total')),
            'totalEstablecimientos' => $totalEstablecimientos,
        ]);
    }

    /**
     * Repite la misma consulta agregada que ve el usuario en pantalla y la
     * exporta como CSV (delimitador ; y BOM UTF-8, para que Excel en
     * configuración regional es-PE lo abra directamente con acentos y
     * columnas correctas).
     */
    public function exportarExcel(): void
    {
        [$enfermedadId, $agrupacion, $rangoSe] = $this->leerFiltros();
        $filas = Caso::reportePorAgrupacion($agrupacion, ['enfermedad_id' => $enfermedadId, 'rango_se' => $rangoSe]);

        $nombreArchivo = 'reporte_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF"); // BOM UTF-8

        fputcsv($salida, [self::ETIQUETAS_AGRUPACION[$agrupacion], 'Sospechoso', 'Probable', 'Confirmado', 'Descartado', 'Total'], ';', '"', '');
        foreach ($filas as $fila) {
            fputcsv($salida, [
                $fila['etiqueta'],
                $fila['sospechoso'],
                $fila['probable'],
                $fila['confirmado'],
                $fila['descartado'],
                $fila['total'],
            ], ';', '"', '');
        }

        fclose($salida);
        exit;
    }

    /**
     * @return array{0: ?int, 1: string, 2: array}
     */
    private function leerFiltros(): array
    {
        $enfermedadId = !empty($_GET['enfermedad_id']) ? (int) $_GET['enfermedad_id'] : null;

        $agrupacion = $_GET['agrupar_por'] ?? 'establecimiento';
        if (!in_array($agrupacion, self::AGRUPACIONES_VALIDAS, true)) {
            $agrupacion = 'establecimiento';
        }

        $actual = semanaEpidemiologica(date('Y-m-d'));
        $inicioPorDefecto = semanaEpidemiologica((new \DateTime())->modify('-3 weeks')->format('Y-m-d'));

        $rangoSe = [
            'anio_desde' => (int) ($_GET['anio_desde'] ?? $inicioPorDefecto['anio']),
            'se_desde'   => (int) ($_GET['se_desde'] ?? $inicioPorDefecto['semana']),
            'anio_hasta' => (int) ($_GET['anio_hasta'] ?? $actual['anio']),
            'se_hasta'   => (int) ($_GET['se_hasta'] ?? $actual['semana']),
        ];

        return [$enfermedadId, $agrupacion, $rangoSe];
    }
}
