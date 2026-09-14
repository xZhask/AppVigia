<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Caso;
use App\Models\Enfermedad;
use App\Models\Establecimiento;
use App\Models\ListadoMuerteFetalNeonatal;
use App\Models\NotificacionNegativa;
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
     * "Ficha de notificación de muerte fetal y neonatal" (pág. 28 del PDF,
     * Anexo 1 de la R.M. 279-2009/MINSA): el listado semanal de un
     * establecimiento, armado con sus fichas P96 (ver
     * ListadoMuerteFetalNeonatal). Con un establecimiento elegido y ningún
     * caso en la semana, deja notificar la semana sin casos.
     */
    public function muerteFetalNeonatal(): void
    {
        $filtros = ListadoMuerteFetalNeonatal::filtros($_GET, Auth::usuario());
        $casos = ListadoMuerteFetalNeonatal::casos($filtros);
        $enfermedad = Enfermedad::buscarPorCie10(ListadoMuerteFetalNeonatal::CIE10);
        $negativa = ($enfermedad && $filtros['establecimiento_id'])
            ? NotificacionNegativa::buscar((int) $enfermedad['id'], $filtros['establecimiento_id'], $filtros['anio'], $filtros['semana'])
            : null;

        $this->vista('reportes/muerte-fetal-neonatal', [
            'tituloVista'      => ListadoMuerteFetalNeonatal::NOMBRE,
            'rutaActual'       => 'reportes',
            'filtros'          => $filtros,
            'establecimientos' => Establecimiento::todos('nombre'),
            'puedeElegirEstablecimiento' => !Auth::tieneRol('REGISTRADOR'),
            'encabezado'       => Establecimiento::encabezadoFormulario($filtros['establecimiento_id']),
            'responsable'      => (string) (Auth::usuario()['nombre'] ?? ''),
            'rangoSemana'      => ListadoMuerteFetalNeonatal::rangoDeSemana($filtros['anio'], $filtros['semana']),
            'casos'            => $casos,
            'negativa'         => $negativa && $negativa['anulada_en'] === null ? $negativa : null,
            'puedeNotificarNegativa' => $enfermedad && $filtros['establecimiento_id'] && !$casos && Auth::tieneRol('ADMIN', 'REGISTRADOR'),
            'puedeAnularNegativa'    => $negativa && $negativa['anulada_en'] === null
                && (Auth::tieneRol('ADMIN') || (int) $negativa['usuario_id'] === (int) Auth::usuario()['id']),
        ]);
    }

    /**
     * El mismo listado en CSV (; y BOM UTF-8, como exportarExcel()), con las
     * columnas y notas del Anexo 1.
     */
    public function exportarMuerteFetalNeonatal(): void
    {
        $filtros = ListadoMuerteFetalNeonatal::filtros($_GET, Auth::usuario());
        $casos = ListadoMuerteFetalNeonatal::casos($filtros);
        $encabezado = Establecimiento::encabezadoFormulario($filtros['establecimiento_id']);
        $enfermedad = Enfermedad::buscarPorCie10(ListadoMuerteFetalNeonatal::CIE10);
        $negativa = ($enfermedad && $filtros['establecimiento_id'])
            ? NotificacionNegativa::buscar((int) $enfermedad['id'], $filtros['establecimiento_id'], $filtros['anio'], $filtros['semana'])
            : null;
        $marca = fn(bool $marcado): string => $marcado ? 'X' : '';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="ficha_notificacion_muerte_fetal_neonatal_' . $filtros['anio'] . '_SE' . str_pad((string) $filtros['semana'], 2, '0', STR_PAD_LEFT) . '.csv"');
        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        $linea = fn(array $celdas) => fputcsv($salida, $celdas, ';', '"', '');

        $linea([ListadoMuerteFetalNeonatal::NOMBRE]);
        $linea([ListadoMuerteFetalNeonatal::SUBSISTEMA]);
        $linea(['DISA / DIRESA / GERESA', $encabezado['diresa'], 'Distrito', $encabezado['distrito'], 'Semana epidemiológica', 'SE ' . $filtros['semana'] . ' - ' . $filtros['anio']]);
        $linea(['Establecimiento notificante', $encabezado['establecimiento'] ?: 'Todos', 'Responsable', (string) (Auth::usuario()['nombre'] ?? '')]);
        $linea([]);

        $linea(['N.°', 'Apellidos y nombres', 'Sexo (1)', 'Edad gestacional (semanas)', 'Nacimiento: fecha', 'Nacimiento: hora', 'Muerte: fecha', 'Muerte: hora', 'Peso al nacer (gramos)', 'Tipo de muerte: fetal', 'Tipo de muerte: neonatal', 'Causa básica de muerte (2)', 'Diagnóstico CIE10', 'N.° días de estancia hospitalaria *', 'Lugar del parto (3)', 'Momento: anteparto', 'Momento: intra-parto', 'Momento: post-parto', 'Lugar de la muerte (4)', 'Residencia habitual de la madre: Dpto.', 'Prov.', 'Distrito', 'Establecimiento', 'Ficha VIGÍA']);
        foreach ($casos as $indice => $caso) {
            $linea([
                $indice + 1,
                $caso['apellidos_nombres'],
                $caso['sexo'],
                $caso['edad_gestacional'],
                fechaIsoADmy($caso['fecha_nacimiento']),
                $caso['hora_nacimiento'],
                fechaIsoADmy($caso['fecha_muerte']),
                $caso['hora_muerte'],
                $caso['peso'],
                $marca($caso['tipo'] === 'FETAL'),
                $marca($caso['tipo'] === 'NEONATAL'),
                $caso['causa_basica'],
                $caso['cie10'],
                $caso['dias_estancia'],
                $caso['lugar_parto'],
                $marca($caso['momento'] === 'ANTEPARTO'),
                $marca($caso['momento'] === 'INTRAPARTO'),
                $marca($caso['momento'] === 'POSTPARTO'),
                $caso['lugar_muerte'],
                $caso['departamento'],
                $caso['provincia'],
                $caso['distrito'],
                $caso['establecimiento'],
                $caso['codigo_ficha'],
            ]);
        }
        if (!$casos && $negativa && $negativa['anulada_en'] === null) {
            $linea(['', 'SIN CASOS: notificación negativa registrada el ' . fechaIsoADmy($negativa['creado_en']) . ' por ' . $negativa['usuario_nombre']]);
        }
        $linea([]);
        $linea(['Anexo 1 de la R.M. 279-2009/MINSA']);
        $linea(['* Número de días de estancia hospitalaria: consignar solo para los casos de muerte neonatal.']);
        $linea(['(1) SEXO: F = FEMENINO M = MASCULINO']);
        $linea(['(2) CAUSA BÁSICA DE MUERTE: Es la entidad que inicia la cadena de acontecimientos que conducen a la muerte fetal o neonatal (CIE X). Solo se anotará una causa que aparece como causa básica en el certificado de defunción']);
        $linea(['(3) LUGAR DEL PARTO: Colocar PI cuando es parto institucional y PD cuando sea parto domiciliario']);
        $linea(['(4) LUGAR DE LA MUERTE: Consignar ES cuando la muerte ocurrió en un establecimiento de Salud o CC cuando la muerte ocurrió en la comunidad']);

        fclose($salida);
        exit;
    }

    /**
     * Notifica la semana sin casos (POST). Solo con un establecimiento, una
     * semana existente y no futura, y ningún caso P96 no anulado en ella: se
     * vuelve a contar acá, no se confía en lo que mostraba la pantalla.
     */
    public function notificarSinCasosMuerteFetalNeonatal(): void
    {
        Auth::exigirRol('ADMIN', 'REGISTRADOR');
        $filtros = ListadoMuerteFetalNeonatal::filtros($_POST, Auth::usuario());
        $volver = '/reportes/muerte-fetal-neonatal?' . http_build_query(['anio' => $filtros['anio'], 'semana' => $filtros['semana'], 'establecimiento_id' => $filtros['establecimiento_id'] ?? '']);
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: ' . $volver);
            exit;
        }

        $enfermedad = Enfermedad::buscarPorCie10(ListadoMuerteFetalNeonatal::CIE10);
        $semanaPedida = [(int) ($_POST['anio'] ?? 0), (int) ($_POST['semana'] ?? 0)];
        if (!$enfermedad || !$filtros['establecimiento_id'] || !Establecimiento::buscar($filtros['establecimiento_id'])) {
            Flash::set('Elige un establecimiento para notificar la semana sin casos.');
        } elseif ($semanaPedida !== [$filtros['anio'], $filtros['semana']]) {
            Flash::set('No se puede notificar una semana epidemiológica inexistente o futura.');
        } elseif (ListadoMuerteFetalNeonatal::casos(array_merge($filtros, ['usuario' => ['rol' => 'ADMIN']]))) {
            Flash::set('Esta semana ya tiene muertes fetales o neonatales notificadas: no puede notificarse sin casos.');
        } else {
            NotificacionNegativa::registrar((int) $enfermedad['id'], $filtros['establecimiento_id'], $filtros['anio'], $filtros['semana'], (int) Auth::usuario()['id']);
            Flash::set('Semana ' . $filtros['semana'] . ' de ' . $filtros['anio'] . ' notificada sin casos.');
        }
        header('Location: ' . $volver);
        exit;
    }

    /** Deja sin efecto la notificación sin casos (POST): su autor o un ADMIN. */
    public function anularSinCasosMuerteFetalNeonatal(): void
    {
        Auth::exigirRol('ADMIN', 'REGISTRADOR');
        $filtros = ListadoMuerteFetalNeonatal::filtros($_POST, Auth::usuario());
        $volver = '/reportes/muerte-fetal-neonatal?' . http_build_query(['anio' => $filtros['anio'], 'semana' => $filtros['semana'], 'establecimiento_id' => $filtros['establecimiento_id'] ?? '']);
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: ' . $volver);
            exit;
        }

        $enfermedad = Enfermedad::buscarPorCie10(ListadoMuerteFetalNeonatal::CIE10);
        $negativa = ($enfermedad && $filtros['establecimiento_id'])
            ? NotificacionNegativa::buscar((int) $enfermedad['id'], $filtros['establecimiento_id'], $filtros['anio'], $filtros['semana'])
            : null;
        $usuario = Auth::usuario();
        if (!$negativa || $negativa['anulada_en'] !== null) {
            Flash::set('No hay una notificación sin casos activa para esa semana.');
        } elseif (!Auth::tieneRol('ADMIN') && (int) $negativa['usuario_id'] !== (int) $usuario['id']) {
            Flash::set('Solo quien notificó la semana sin casos, o un administrador, puede anularla.');
        } else {
            NotificacionNegativa::anular((int) $negativa['id'], (int) $usuario['id']);
            Flash::set('Notificación sin casos anulada.');
        }
        header('Location: ' . $volver);
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
