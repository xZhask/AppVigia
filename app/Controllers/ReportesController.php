<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Caso;
use App\Models\Enfermedad;
use App\Models\Establecimiento;
use App\Models\RegistroBusquedaActivaVih;

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
     * "Formulario de registro de casos de gestantes con VIH y niños nacidos
     * expuestos al VIH identificados por búsqueda activa institucional" (pág.
     * 15 del PDF), armado con las fichas Z21 del periodo. Ver
     * RegistroBusquedaActivaVih.
     */
    public function busquedaActivaVih(): void
    {
        $filtros = $this->leerFiltrosBusquedaActivaVih();
        $casos = RegistroBusquedaActivaVih::casos($filtros);

        $this->vista('reportes/busqueda-activa-vih', [
            'tituloVista'      => RegistroBusquedaActivaVih::NOMBRE,
            'rutaActual'       => 'reportes',
            'filtros'          => $filtros,
            'establecimientos' => Establecimiento::todos('nombre'),
            'puedeElegirEstablecimiento' => !Auth::tieneRol('REGISTRADOR'),
            'encabezado'       => RegistroBusquedaActivaVih::encabezado($filtros['establecimiento_id']),
            'casos'            => $casos,
            'resumen'          => RegistroBusquedaActivaVih::resumen($casos),
        ]);
    }

    /**
     * El mismo formato en CSV (; y BOM UTF-8, como exportarExcel()): encabezado,
     * resumen y tabla de casos. Solo lo que trae el formato en papel: código del
     * paciente e historia clínica, sin nombres ni documento.
     */
    public function exportarBusquedaActivaVih(): void
    {
        $filtros = $this->leerFiltrosBusquedaActivaVih();
        $casos = RegistroBusquedaActivaVih::casos($filtros);
        $resumen = RegistroBusquedaActivaVih::resumen($casos);
        $encabezado = RegistroBusquedaActivaVih::encabezado($filtros['establecimiento_id']);
        $servicios = RegistroBusquedaActivaVih::SERVICIOS;
        $institucion = ['MINSA' => 'MINSA', 'ESSALUD' => 'EsSalud', 'FFAA_SANIDAD' => 'FFAA/FFPP', 'PRIVADO' => 'Privado'];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="formulario_registro_gestantes_vih_ninos_expuestos_busqueda_activa_' . $filtros['desde'] . '_' . $filtros['hasta'] . '.csv"');
        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        $linea = fn(array $celdas) => fputcsv($salida, $celdas, ';', '"', '');

        $linea([RegistroBusquedaActivaVih::NOMBRE]);
        $linea(['DISA/DIRESA/GERESA', $encabezado['diresa'], 'Red', $encabezado['red'], 'Microrred', '']);
        $linea(['Establecimiento de salud', $encabezado['establecimiento'] ?: 'Todos', 'Institución', $institucion[$encabezado['institucion']] ?? $encabezado['institucion']]);
        $linea(['Departamento', $encabezado['departamento'], 'Provincia', $encabezado['provincia'], 'Distrito', $encabezado['distrito']]);
        $linea(['Periodo de búsqueda activa', 'Desde', fechaIsoADmy($filtros['desde']), 'Hasta', fechaIsoADmy($filtros['hasta'])]);
        $linea([]);

        $linea(array_merge(['Casos', 'Total'], array_map(fn($codigo) => $servicios[$codigo]['larga'], RegistroBusquedaActivaVih::ORDEN_SERVICIOS_RESUMEN), ['Servicio sin dato', 'Notificados', 'No notificados', 'Notificación sin dato']));
        foreach (['GESTANTE' => 'Gestantes con VIH', 'NINO' => 'Niños nacidos expuestos'] as $tipo => $etiqueta) {
            $fila = $resumen[$tipo];
            $linea(array_merge(
                [$etiqueta, $fila['total']],
                array_values($fila['servicio']),
                [$fila['notificado']['SI'], $fila['notificado']['NO'], $fila['notificado']['SIN_DATO']]
            ));
        }
        $linea([]);

        $linea(['N.°', 'Código del paciente', 'N.° de historia clínica', 'Edad', 'Tipo de edad', 'Sexo', 'Servicio', 'Clasificación de caso', 'Fecha de defunción', 'Notificado', 'Observaciones', 'Ficha VIGÍA']);
        foreach ($casos as $indice => $caso) {
            $linea([
                $indice + 1,
                $caso['codigo'],
                $caso['historia_clinica'],
                $caso['edad'],
                $caso['tipo_edad'],
                $caso['sexo'],
                $servicios[$caso['servicio']]['corta'] ?? '',
                $caso['clasificacion'],
                fechaIsoADmy($caso['fecha_defuncion']),
                ['SI' => 'Sí', 'NO' => 'No'][$caso['notificado']] ?? '',
                $caso['observaciones'],
                $caso['codigo_ficha'],
            ]);
        }
        $linea([]);
        $linea(['1 Tipo de edad: días (d), meses (m), años (a); 2 Clasificación de caso: Gestante con VIH (1), Aborto (2), Mortinato (3), Niño nacido expuesto al VIH (4); 3 Fecha de defunción de gestante, niño expuesto, de aborto o mortinato']);

        fclose($salida);
        exit;
    }

    /**
     * Periodo (por fecha de notificación; por defecto, del 1 del mes a hoy) y
     * establecimiento. Un REGISTRADOR queda fijo en el suyo.
     *
     * @return array{desde: string, hasta: string, establecimiento_id: ?int, usuario: array}
     */
    private function leerFiltrosBusquedaActivaVih(): array
    {
        $usuario = Auth::usuario();
        $desde = fechaIsoValida((string) ($_GET['desde'] ?? '')) ?: date('Y-m-01');
        $hasta = fechaIsoValida((string) ($_GET['hasta'] ?? '')) ?: date('Y-m-d');
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $establecimientoId = Auth::tieneRol('REGISTRADOR')
            ? (int) $usuario['establecimiento_id']
            : (!empty($_GET['establecimiento_id']) ? (int) $_GET['establecimiento_id'] : null);

        return ['desde' => $desde, 'hasta' => $hasta, 'establecimiento_id' => $establecimientoId ?: null, 'usuario' => $usuario];
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
