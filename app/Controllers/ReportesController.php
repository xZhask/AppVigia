<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Caso;
use App\Models\Consolidado;
use App\Models\Enfermedad;
use App\Models\Establecimiento;
use App\Models\FormatoPnp;
use App\Models\ListadoMuerteFetalNeonatal;
use App\Models\NotificacionNegativa;
use App\Models\RegistroBusquedaActivaVih;

class ReportesController extends Controller
{
    /**
     * Consolidado (rediseño 2026-10-17): tabla cruzada filas × columnas con
     * totales, vista Tabla | Gráfico y título armado con los filtros. Los
     * formatos del PDF (Z21, P96) van en su propia pestaña.
     */
    public function index(): void
    {
        $usuario = Auth::usuario();
        $f = $this->leerFiltros();
        $enfermedad = $f['enfermedad_id'] ? Enfermedad::buscar($f['enfermedad_id']) : null;
        $cruce = Consolidado::cruzar($usuario, $f['enfermedad_id'], $f['desde'], $f['hasta'], $f['filas'], $f['columnas']);

        $this->vista('reportes/index', [
            'tituloVista'  => 'Reportes',
            'rutaActual'   => 'reportes',
            'vistaAncha'   => true,
            'pestana'      => ($_GET['vista'] ?? '') === 'formatos' ? 'formatos' : 'consolidado',
            'enfermedades' => Enfermedad::todos('nombre'),
            'enfermedad'   => $enfermedad,
            'filtros'      => $f,
            'dimensiones'  => Consolidado::dimensiones(),
            'cruce'        => $cruce,
            'piramide'     => $f['filas'] === 'etapa' && in_array($f['columnas'], [null, 'sexo'], true)
                ? Consolidado::piramide($usuario, $f['enfermedad_id'], $f['desde'], $f['hasta']) : null,
            'titulo'       => $this->tituloReporte($f, $enfermedad),
            'esRegistrador' => $usuario['rol'] === 'REGISTRADOR',
        ]);
    }

    /**
     * La misma tabla cruzada de la pantalla, en CSV (delimitador ; y BOM
     * UTF-8, para que Excel en configuración regional es-PE lo abra directo).
     */
    public function exportarExcel(): void
    {
        $usuario = Auth::usuario();
        $f = $this->leerFiltros();
        $enfermedad = $f['enfermedad_id'] ? Enfermedad::buscar($f['enfermedad_id']) : null;
        $cruce = Consolidado::cruzar($usuario, $f['enfermedad_id'], $f['desde'], $f['hasta'], $f['filas'], $f['columnas']);
        $dims = Consolidado::dimensiones();
        $variosAnios = $f['desde'][0] !== $f['hasta'][0];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="reporte_' . $f['filas'] . ($f['columnas'] ? '_' . $f['columnas'] : '') . '_' . date('Y-m-d') . '.csv"');
        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        $linea = fn(array $celdas) => fputcsv($salida, $celdas, ';', '"', '');

        $linea([$this->tituloReporte($f, $enfermedad)]);
        $linea([]);
        $encabezado = [$dims[$f['filas']]['etiqueta']];
        if ($f['columnas']) {
            foreach ($cruce['columnas'] as $k) {
                $encabezado[] = Consolidado::etiqueta($f['columnas'], $k);
            }
        }
        $linea(array_merge($encabezado, ['Total', '%']));
        foreach ($cruce['filas'] as $fila) {
            $celdas = [Consolidado::etiqueta($f['filas'], $fila, $variosAnios)];
            if ($f['columnas']) {
                foreach ($cruce['columnas'] as $k) {
                    $celdas[] = $cruce['celdas'][$fila][$k] ?? 0;
                }
            }
            $totalFila = $cruce['totalFila'][$fila] ?? 0;
            $linea(array_merge($celdas, [$totalFila, $cruce['total'] ? number_format($totalFila / $cruce['total'] * 100, 1, ',', '') : '0,0']));
        }
        $pie = ['Total'];
        if ($f['columnas']) {
            foreach ($cruce['columnas'] as $k) {
                $pie[] = $cruce['totalColumna'][$k] ?? 0;
            }
        }
        $linea(array_merge($pie, [$cruce['total'], '100,0']));
        fclose($salida);
        exit;
    }

    /** "Difteria · casos por red y clasificación · SE 15–41 · 2026". */
    private function tituloReporte(array $f, ?array $enfermedad): string
    {
        $dims = Consolidado::dimensiones();
        // Solo la inicial en minúscula: "Categoría PNP" -> "categoría PNP".
        $minuscula = fn(string $t): string => mb_strtolower(mb_substr($t, 0, 1)) . mb_substr($t, 1);
        return ($enfermedad ? $enfermedad['nombre'] : 'Todas las enfermedades')
            . ' · casos por ' . $minuscula($dims[$f['filas']]['etiqueta'])
            . ($f['columnas'] ? ' y ' . $minuscula($dims[$f['columnas']]['etiqueta']) : '')
            . ' · ' . $f['etiquetaPeriodo'];
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
     * Registro semanal de notificación epidemiológica individual del Área de
     * Epidemiología PNP (2026-10-21), o el de VIH/SIDA con $vih: fichas y
     * registros de la semana de un establecimiento (ver FormatoPnp).
     */
    public function registroIndividual(bool $vih): void
    {
        $filtros = ListadoMuerteFetalNeonatal::filtros($_GET, Auth::usuario());

        $this->vista('reportes/registro-individual', [
            'tituloVista'      => $vih ? 'Registro individual VIH/SIDA' : 'Registro individual',
            'rutaActual'       => 'reportes',
            'vistaAncha'       => true,
            'vih'              => $vih,
            'filtros'          => $filtros,
            'establecimientos' => Establecimiento::todos('nombre'),
            'puedeElegirEstablecimiento' => !Auth::tieneRol('REGISTRADOR'),
            'encabezado'       => FormatoPnp::encabezado($filtros['establecimiento_id']),
            'rangoSemana'      => ListadoMuerteFetalNeonatal::rangoDeSemana($filtros['anio'], $filtros['semana']),
            'casos'            => FormatoPnp::individual($filtros, $vih),
        ]);
    }

    /** El mismo registro en CSV (; y BOM UTF-8, como exportarMuerteFetalNeonatal()). */
    public function exportarRegistroIndividual(bool $vih): void
    {
        $filtros = ListadoMuerteFetalNeonatal::filtros($_GET, Auth::usuario());
        $casos = FormatoPnp::individual($filtros, $vih);
        $encabezado = FormatoPnp::encabezado($filtros['establecimiento_id']);
        $marca = fn(bool $marcado): string => $marcado ? 'X' : '';

        $salida = $this->abrirCsv(($vih ? 'registro_individual_vih_' : 'registro_individual_') . $filtros['anio'] . '_SE' . str_pad((string) $filtros['semana'], 2, '0', STR_PAD_LEFT));
        $linea = fn(array $celdas) => fputcsv($salida, $celdas, ';', '"', '');
        $this->cabeceraCsvPnp($linea, $vih ? FormatoPnp::VIH : FormatoPnp::INDIVIDUAL, $encabezado, $filtros);

        $linea(['N.°', 'Grado', 'ACT/RET', 'F', $vih ? 'Iniciales del paciente' : 'Apellidos y nombres', 'Edad M', 'Edad F', 'N.° CIP', 'N.° DNI',
            'Lugar probable de infección: provincia', 'Distrito', 'Localidad', 'Dirección de residencia', 'Diagnóstico CIE-10',
            'Tipo de Dx P', 'C', 'D', 'Protegido SI', 'NO', 'IG', 'Fecha de inicio de síntomas', 'Fecha de defunción', 'Fecha de notificación',
            'Ficha de investigación SI', 'NO', 'Vigilancia pasiva', 'Búsqueda activa', 'Unidad de origen', 'Situación del paciente', 'Observaciones', 'Ficha VIGÍA']);
        foreach ($casos as $i => $c) {
            $linea([
                $i + 1, $c['grado'], $c['act_ret'], $marca($c['familiar']), $c['nombre'],
                $c['sexo'] === 'M' ? $c['edad'] : '', $c['sexo'] === 'F' ? $c['edad'] : '', $c['cip'], $c['dni'],
                $c['lugar_provincia'], $c['lugar_distrito'], $c['lugar_localidad'], $c['direccion'], $c['cie10'],
                $marca($c['tipo_dx'] === 'P'), $marca($c['tipo_dx'] === 'C'), $marca($c['tipo_dx'] === 'D'),
                $marca($c['protegido'] === 'SI'), $marca($c['protegido'] === 'NO'), $marca($c['protegido'] === 'IG'),
                fechaIsoADmy($c['inicio_sintomas']), fechaIsoADmy($c['defuncion']), fechaIsoADmy($c['notificacion']),
                $marca($c['investigacion'] === 'SI'), $marca($c['investigacion'] === 'NO'),
                $marca($c['vigilancia'] === 'PAS'), $marca($c['vigilancia'] === 'ACT'),
                $c['unidad'], $c['situacion'], $c['observaciones'], $c['codigo'],
            ]);
        }
        $linea([]);
        $linea(['Tipo de diagnóstico: P: probable · C: confirmado · D: descartado']);
        fclose($salida);
        exit;
    }

    /** Formato de registro diario para la vigilancia de febriles (2026-10-21). */
    /**
     * $delDia (menú "Registro de febriles", 2026-10-21): la misma hoja en
     * /febriles, abierta en el día de hoy si no se pide otra semana o día.
     */
    public function febriles(bool $delDia = false): void
    {
        if ($delDia && !isset($_GET['anio']) && !isset($_GET['semana']) && !isset($_GET['dia'])) {
            $_GET['dia'] = date('Y-m-d');
        }
        $filtros = ListadoMuerteFetalNeonatal::filtros($_GET, Auth::usuario());
        $rango = ListadoMuerteFetalNeonatal::rangoDeSemana($filtros['anio'], $filtros['semana']);
        $dia = $this->diaDeLaSemana($rango);
        $febriles = Enfermedad::buscarPorCie10(\App\Models\Cie10Vigilancia::REGISTRO_FEBRILES);

        $this->vista('reportes/febriles', [
            'tituloVista'      => $delDia ? 'Registro de febriles' : 'Registro diario de febriles',
            'rutaActual'       => $delDia ? 'febriles' : 'reportes',
            'base'             => $delDia ? '/febriles' : '/reportes/febriles',
            'vistaAncha'       => true,
            'filtros'          => $filtros,
            'dia'              => $dia,
            'establecimientos' => Establecimiento::todos('nombre'),
            'puedeElegirEstablecimiento' => !Auth::tieneRol('REGISTRADOR'),
            'encabezado'       => FormatoPnp::encabezado($filtros['establecimiento_id']),
            'rangoSemana'      => $rango,
            'casos'            => FormatoPnp::febriles($filtros, $dia),
            'febrilesId'       => $febriles ? (int) $febriles['id'] : null,
        ]);
    }

    public function exportarFebriles(): void
    {
        $filtros = ListadoMuerteFetalNeonatal::filtros($_GET, Auth::usuario());
        $dia = $this->diaDeLaSemana(ListadoMuerteFetalNeonatal::rangoDeSemana($filtros['anio'], $filtros['semana']));
        $casos = FormatoPnp::febriles($filtros, $dia);
        $encabezado = FormatoPnp::encabezado($filtros['establecimiento_id']);
        $marca = fn(bool $marcado): string => $marcado ? 'X' : '';

        $salida = $this->abrirCsv('registro_febriles_' . $filtros['anio'] . '_SE' . str_pad((string) $filtros['semana'], 2, '0', STR_PAD_LEFT) . ($dia ? '_' . $dia : ''));
        $linea = fn(array $celdas) => fputcsv($salida, $celdas, ';', '"', '');
        $linea([mb_strtoupper(FormatoPnp::FEBRILES)]);
        $linea(['Dirección de salud', 'DIRSAPOL', 'Red de salud', $encabezado['red'], 'Establecimiento', $encabezado['establecimiento'] ?: 'Todos']);
        $linea(['Semana N.°', $filtros['semana'] . ' - ' . $filtros['anio'], 'Día', $dia ? fechaIsoADmy($dia) : 'Toda la semana']);
        $linea([]);
        $linea(['N.°', 'Grado', 'ACT/RET', 'F', 'H.CL', 'Fecha', 'Apellidos y nombres', 'DNI', 'Edad M', 'Edad F', 'Dirección', 'Distrito de residencia', 'Temperatura', 'Dx CIE-10', 'Diagnóstico', 'Servicio', 'Registro VIGÍA']);
        foreach ($casos as $i => $c) {
            $linea([
                $i + 1, $c['grado'], $c['act_ret'], $marca($c['familiar']), $c['historia'], fechaIsoADmy($c['fecha']), $c['nombre'], $c['dni'],
                $c['sexo'] === 'M' ? $c['edad'] : '', $c['sexo'] === 'F' ? $c['edad'] : '', $c['direccion'], $c['distrito'],
                $c['temperatura'], $c['cie10'], $c['diagnostico'], $c['servicio'], $c['codigo'],
            ]);
        }
        fclose($salida);
        exit;
    }

    /** ?dia= dentro de la semana elegida, o null (toda la semana). */
    private function diaDeLaSemana(array $rango): ?string
    {
        $dia = fechaIsoValida((string) ($_GET['dia'] ?? ''));

        return $dia !== null && $dia >= $rango[0] && $dia <= $rango[1] ? $dia : null;
    }

    /** Respuesta CSV para Excel (BOM UTF-8). */
    private function abrirCsv(string $nombre)
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '.csv"');
        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");

        return $salida;
    }

    private function cabeceraCsvPnp(callable $linea, string $titulo, array $encabezado, array $filtros): void
    {
        $linea(['POLICÍA NACIONAL DEL PERÚ', '', 'ÁREA DE EPIDEMIOLOGÍA']);
        $linea(['DIRECCIÓN DE SALUD POLICIAL']);
        $linea([mb_strtoupper($titulo)]);
        $linea(['Código de establecimiento', $encabezado['codigo'], 'Semana de notificación', $filtros['semana'] . ' - ' . $filtros['anio']]);
        $linea(['Cabecera de red', $encabezado['red']]);
        $linea(['Establecimiento', $encabezado['establecimiento'] ?: 'Todos']);
        $linea(['Tipo de establecimiento', $encabezado['categoria']]);
        $linea([]);
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
     * Filtros del consolidado: enfermedad, filas, columnas y periodo por SE
     * (4 SE, 12 SE, año o un rango, que puede cruzar de año). Acepta los
     * parámetros viejos (agrupar_por) de enlaces guardados.
     */
    private function leerFiltros(): array
    {
        $dims = Consolidado::dimensiones();
        $texto = fn(string $clave): string => is_string($_GET[$clave] ?? null) ? trim($_GET[$clave]) : '';
        $compat = ['semana' => 'se', 'establecimiento' => 'establecimiento', 'red' => 'red', 'clasificacion' => 'clasificacion', 'categoria_pnp' => 'categoria_pnp', 'nivel' => 'nivel'];

        $filas = $texto('filas') ?: ($compat[$texto('agrupar_por')] ?? 'establecimiento');
        $filas = isset($dims[$filas]) ? $filas : 'establecimiento';
        $columnas = $texto('columnas') === '' && $texto('agrupar_por') === '' ? 'clasificacion' : $texto('columnas');
        $columnas = isset($dims[$columnas]) && $dims[$columnas]['columna'] && $columnas !== $filas ? $columnas : null;

        $actual = semanaEpidemiologica(date('Y-m-d'));
        $periodo = in_array($texto('periodo'), ['4', '12', 'anio', 'rango'], true) ? $texto('periodo') : ($texto('se_desde') !== '' ? 'rango' : 'anio');
        if ($periodo === 'rango') {
            $desde = [(int) $texto('anio_desde') ?: $actual['anio'], max(1, min(53, (int) $texto('se_desde') ?: 1))];
            $hasta = [(int) $texto('anio_hasta') ?: $actual['anio'], max(1, min(53, (int) $texto('se_hasta') ?: $actual['semana']))];
            $desde[1] = min($desde[1], semanasEpidemiologicasDelAnio($desde[0]));
            $hasta[1] = min($hasta[1], semanasEpidemiologicasDelAnio($hasta[0]));
            if ($desde > $hasta) {
                [$desde, $hasta] = [$hasta, $desde];
            }
        } elseif ($periodo === 'anio') {
            $desde = [$actual['anio'], 1];
            $hasta = [$actual['anio'], $actual['semana']];
        } else {
            $inicio = semanaEpidemiologica(date('Y-m-d', strtotime('-' . (7 * ((int) $periodo - 1)) . ' days')));
            $desde = [$inicio['anio'], $inicio['semana']];
            $hasta = [$actual['anio'], $actual['semana']];
        }
        $etiquetaPeriodo = 'SE ' . $desde[1] . ($desde[0] !== $hasta[0] ? ' · ' . $desde[0] : '') . '–' . $hasta[1] . ' · ' . $hasta[0];

        return [
            'enfermedad_id'   => ctype_digit($texto('enfermedad_id')) ? (int) $texto('enfermedad_id') : null,
            'filas'           => $filas,
            'columnas'        => $columnas,
            'periodo'         => $periodo,
            'desde'           => $desde,
            'hasta'           => $hasta,
            'etiquetaPeriodo' => $etiquetaPeriodo,
            'modo'            => $texto('modo') === 'grafico' ? 'grafico' : 'tabla',
        ];
    }
}
