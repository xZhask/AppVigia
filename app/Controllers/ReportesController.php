<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\LibroXlsx;
use App\Models\Caso;
use App\Models\Consolidado;
use App\Models\ConsolidadoPnp;
use App\Models\Enfermedad;
use App\Models\Establecimiento;
use App\Models\FormatoPnp;
use App\Models\ListadoMuerteFetalNeonatal;
use App\Models\NotificacionNegativa;
use App\Models\RegistroBusquedaActivaVih;

class ReportesController extends Controller
{
    /** Membrete de las descargas en Excel (2026-10-30). */
    private const MEMBRETE = 'VIGÍA PNP · ÁREA DE EPIDEMIOLOGÍA · DIRSAPOL';
    /** Membrete de los formatos del Área de Epidemiología PNP (individual, consolidado, febriles). */
    private const MEMBRETE_PNP = ['POLICÍA NACIONAL DEL PERÚ · DIRECCIÓN DE SALUD POLICIAL', 'ÁREA DE EPIDEMIOLOGÍA'];

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
     * La misma tabla cruzada de la pantalla, en .xlsx con diseño (2026-10-30;
     * antes CSV): la dimensión de columnas va como encabezado agrupado.
     */
    public function exportarExcel(): void
    {
        $usuario = Auth::usuario();
        $f = $this->leerFiltros();
        $enfermedad = $f['enfermedad_id'] ? Enfermedad::buscar($f['enfermedad_id']) : null;
        $cruce = Consolidado::cruzar($usuario, $f['enfermedad_id'], $f['desde'], $f['hasta'], $f['filas'], $f['columnas']);
        $dims = Consolidado::dimensiones();
        $variosAnios = $f['desde'][0] !== $f['hasta'][0];
        $total = (int) $cruce['total'];

        $columnas = [['titulo' => $dims[$f['filas']]['etiqueta'], 'anchoMax' => 40]];
        foreach ($f['columnas'] ? $cruce['columnas'] : [] as $k) {
            $columnas[] = ['titulo' => Consolidado::etiqueta($f['columnas'], $k), 'grupo' => $dims[$f['columnas']]['etiqueta'], 'tipo' => 'entero', 'anchoMax' => 18];
        }
        $columnas[] = ['titulo' => 'Total', 'tipo' => 'entero'];
        $columnas[] = ['titulo' => '%', 'tipo' => 'porcentaje'];

        $filas = [];
        foreach ($cruce['filas'] as $fila) {
            $celdas = [Consolidado::etiqueta($f['filas'], $fila, $variosAnios)];
            foreach ($f['columnas'] ? $cruce['columnas'] : [] as $k) {
                $celdas[] = $cruce['celdas'][$fila][$k] ?? 0;
            }
            $totalFila = $cruce['totalFila'][$fila] ?? 0;
            $filas[] = array_merge($celdas, [$totalFila, $total ? $totalFila / $total : 0]);
        }
        $pie = ['Total'];
        foreach ($f['columnas'] ? $cruce['columnas'] : [] as $k) {
            $pie[] = $cruce['totalColumna'][$k] ?? 0;
        }

        $this->libro('Reporte de casos')
            ->membrete([self::MEMBRETE])
            ->titulo($this->tituloReporte($f, $enfermedad))
            ->datos([
                'Enfermedad' => $enfermedad ? $enfermedad['nombre'] : 'Todas',
                'Periodo'    => $f['etiquetaPeriodo'],
                'Ámbito'     => $usuario['rol'] === 'REGISTRADOR' ? ($usuario['establecimiento_nombre'] ?? '') : 'Nacional',
                'Casos'      => $total,
            ], 4)
            ->tabla($columnas, $filas, ['totales' => array_merge($pie, [$total, $total ? 1 : 0]), 'congelar' => true, 'vacio' => 'Sin casos en el periodo'])
            ->descargar('reporte_' . $f['filas'] . ($f['columnas'] ? '_' . $f['columnas'] : '') . '_' . date('Y-m-d'));
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
     * El mismo formato en .xlsx (2026-10-30; antes CSV): encabezado, resumen y
     * tabla de casos. Solo lo que trae el formato en papel: código del
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

        $columnasResumen = [['titulo' => 'Casos'], ['titulo' => 'Total', 'tipo' => 'entero']];
        foreach (RegistroBusquedaActivaVih::ORDEN_SERVICIOS_RESUMEN as $codigo) {
            $columnasResumen[] = ['titulo' => $servicios[$codigo]['larga'], 'grupo' => 'Servicio', 'tipo' => 'entero', 'anchoMax' => 16];
        }
        $columnasResumen[] = ['titulo' => 'Sin dato', 'grupo' => 'Servicio', 'tipo' => 'entero'];
        foreach (['Notificados', 'No notificados', 'Sin dato'] as $titulo) {
            $columnasResumen[] = ['titulo' => $titulo, 'grupo' => 'Notificación', 'tipo' => 'entero', 'anchoMax' => 14];
        }
        $filasResumen = [];
        foreach (['GESTANTE' => 'Gestantes con VIH', 'NINO' => 'Niños nacidos expuestos'] as $tipo => $etiqueta) {
            $fila = $resumen[$tipo];
            $filasResumen[] = array_merge([$etiqueta, $fila['total']], array_values($fila['servicio']),
                [$fila['notificado']['SI'], $fila['notificado']['NO'], $fila['notificado']['SIN_DATO']]);
        }

        $filas = [];
        foreach ($casos as $indice => $caso) {
            $filas[] = [
                $indice + 1, $caso['codigo'], $caso['historia_clinica'], $caso['edad'], $caso['tipo_edad'], $caso['sexo'],
                $servicios[$caso['servicio']]['corta'] ?? '', $caso['clasificacion'], $caso['fecha_defuncion'],
                ['SI' => 'Sí', 'NO' => 'No'][$caso['notificado']] ?? '', $caso['observaciones'], $caso['codigo_ficha'],
            ];
        }

        $this->libro(RegistroBusquedaActivaVih::NOMBRE)
            ->membrete([self::MEMBRETE])
            ->titulo(RegistroBusquedaActivaVih::NOMBRE)
            ->datos([
                'DISA/DIRESA/GERESA' => $encabezado['diresa'], 'Red' => $encabezado['red'], 'Microrred' => '',
                'Establecimiento de salud' => $encabezado['establecimiento'] ?: 'Todos', 'Institución' => $institucion[$encabezado['institucion']] ?? $encabezado['institucion'],
                'Departamento' => $encabezado['departamento'], 'Provincia' => $encabezado['provincia'], 'Distrito' => $encabezado['distrito'],
                'Periodo de búsqueda activa' => 'del ' . fechaIsoADmy($filtros['desde']) . ' al ' . fechaIsoADmy($filtros['hasta']),
            ], 3)
            ->seccion('Resumen')
            ->tabla($columnasResumen, $filasResumen)
            ->seccion('Casos')
            ->tabla([
                ['titulo' => 'N.°', 'tipo' => 'centro'],
                ['titulo' => 'Código del paciente'],
                ['titulo' => 'N.° de historia clínica'],
                ['titulo' => 'Edad', 'tipo' => 'entero'],
                ['titulo' => 'Tipo de edad (1)', 'tipo' => 'centro'],
                ['titulo' => 'Sexo', 'tipo' => 'centro'],
                ['titulo' => 'Servicio'],
                ['titulo' => 'Clasificación de caso (2)', 'tipo' => 'centro'],
                ['titulo' => 'Fecha de defunción (3)', 'tipo' => 'fecha'],
                ['titulo' => 'Notificado', 'tipo' => 'centro'],
                ['titulo' => 'Observaciones', 'anchoMax' => 40],
                ['titulo' => 'Ficha VIGÍA PNP'],
            ], $filas, ['vacio' => 'Sin casos en el periodo'])
            ->nota('(1) Tipo de edad: días (d), meses (m), años (a).')
            ->nota('(2) Clasificación de caso: Gestante con VIH (1), Aborto (2), Mortinato (3), Niño nacido expuesto al VIH (4).')
            ->nota('(3) Fecha de defunción de gestante, niño expuesto, de aborto o mortinato.')
            ->descargar('formulario_registro_gestantes_vih_ninos_expuestos_busqueda_activa_' . $filtros['desde'] . '_' . $filtros['hasta']);
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
     * El mismo listado en .xlsx (2026-10-30; antes CSV), con las columnas
     * agrupadas y las notas del Anexo 1.
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

        $filas = [];
        foreach ($casos as $indice => $caso) {
            $filas[] = [
                $indice + 1, $caso['apellidos_nombres'], $caso['sexo'], $caso['edad_gestacional'],
                $caso['fecha_nacimiento'], $caso['hora_nacimiento'], $caso['fecha_muerte'], $caso['hora_muerte'], $caso['peso'],
                $caso['tipo'] === 'FETAL', $caso['tipo'] === 'NEONATAL',
                $caso['causa_basica'], $caso['cie10'], $caso['dias_estancia'], $caso['lugar_parto'],
                $caso['momento'] === 'ANTEPARTO', $caso['momento'] === 'INTRAPARTO', $caso['momento'] === 'POSTPARTO',
                $caso['lugar_muerte'], $caso['departamento'], $caso['provincia'], $caso['distrito'], $caso['establecimiento'], $caso['codigo_ficha'],
            ];
        }
        $vacio = $negativa && $negativa['anulada_en'] === null
            ? 'SIN CASOS: notificación negativa registrada el ' . fechaIsoADmy($negativa['creado_en']) . ' por ' . $negativa['usuario_nombre']
            : 'Sin casos en la semana';

        $this->libro(ListadoMuerteFetalNeonatal::NOMBRE)
            ->membrete([self::MEMBRETE])
            ->titulo(ListadoMuerteFetalNeonatal::NOMBRE)
            ->subtitulo(ListadoMuerteFetalNeonatal::SUBSISTEMA)
            ->datos([
                'DISA / DIRESA / GERESA' => $encabezado['diresa'], 'Distrito' => $encabezado['distrito'],
                'Semana epidemiológica' => $this->textoSemana($filtros),
                'Establecimiento notificante' => $encabezado['establecimiento'] ?: 'Todos', 'Responsable' => (string) (Auth::usuario()['nombre'] ?? ''),
            ], 3)
            ->tabla([
                ['titulo' => 'N.°', 'tipo' => 'centro'],
                ['titulo' => 'Apellidos y nombres', 'anchoMax' => 32],
                ['titulo' => 'Sexo (1)', 'tipo' => 'centro'],
                ['titulo' => 'Edad gestacional (semanas)', 'tipo' => 'entero'],
                ['titulo' => 'Fecha', 'grupo' => 'Nacimiento', 'tipo' => 'fecha'],
                ['titulo' => 'Hora', 'grupo' => 'Nacimiento', 'tipo' => 'centro'],
                ['titulo' => 'Fecha', 'grupo' => 'Muerte', 'tipo' => 'fecha'],
                ['titulo' => 'Hora', 'grupo' => 'Muerte', 'tipo' => 'centro'],
                ['titulo' => 'Peso al nacer (gramos)', 'tipo' => 'entero'],
                ['titulo' => 'Fetal', 'grupo' => 'Tipo de muerte', 'tipo' => 'marca'],
                ['titulo' => 'Neonatal', 'grupo' => 'Tipo de muerte', 'tipo' => 'marca'],
                ['titulo' => 'Causa básica de muerte (2)', 'anchoMax' => 32],
                ['titulo' => 'Diagnóstico CIE-10', 'tipo' => 'centro'],
                ['titulo' => 'N.° días de estancia hospitalaria *', 'tipo' => 'entero'],
                ['titulo' => 'Lugar del parto (3)', 'tipo' => 'centro'],
                ['titulo' => 'Anteparto', 'grupo' => 'Momento', 'tipo' => 'marca'],
                ['titulo' => 'Intraparto', 'grupo' => 'Momento', 'tipo' => 'marca'],
                ['titulo' => 'Postparto', 'grupo' => 'Momento', 'tipo' => 'marca'],
                ['titulo' => 'Lugar de la muerte (4)', 'tipo' => 'centro'],
                ['titulo' => 'Dpto.', 'grupo' => 'Residencia habitual de la madre'],
                ['titulo' => 'Prov.', 'grupo' => 'Residencia habitual de la madre'],
                ['titulo' => 'Distrito', 'grupo' => 'Residencia habitual de la madre'],
                ['titulo' => 'Establecimiento', 'anchoMax' => 30],
                ['titulo' => 'Ficha VIGÍA PNP'],
            ], $filas, ['congelar' => true, 'filtro' => true, 'vacio' => $vacio])
            ->nota('Anexo 1 de la R.M. 279-2009/MINSA.')
            ->nota('* Número de días de estancia hospitalaria: consignar solo para los casos de muerte neonatal.')
            ->nota('(1) SEXO: F = femenino, M = masculino.')
            ->nota('(2) CAUSA BÁSICA DE MUERTE: es la entidad que inicia la cadena de acontecimientos que conducen a la muerte fetal o neonatal (CIE X). Solo se anotará una causa que aparece como causa básica en el certificado de defunción.')
            ->nota('(3) LUGAR DEL PARTO: colocar PI cuando es parto institucional y PD cuando sea parto domiciliario.')
            ->nota('(4) LUGAR DE LA MUERTE: consignar ES cuando la muerte ocurrió en un establecimiento de salud o CC cuando la muerte ocurrió en la comunidad.')
            ->descargar('ficha_notificacion_muerte_fetal_neonatal_' . $filtros['anio'] . '_SE' . str_pad((string) $filtros['semana'], 2, '0', STR_PAD_LEFT));
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

    /**
     * El mismo registro en .xlsx (2026-10-30; antes CSV), con los encabezados
     * agrupados del formato del Área de Epidemiología PNP.
     */
    public function exportarRegistroIndividual(bool $vih): void
    {
        $filtros = ListadoMuerteFetalNeonatal::filtros($_GET, Auth::usuario());
        $casos = FormatoPnp::individual($filtros, $vih);
        $titulo = $vih ? FormatoPnp::VIH : FormatoPnp::INDIVIDUAL;

        $filas = [];
        foreach ($casos as $i => $c) {
            $filas[] = [
                $i + 1, $c['grado'], $c['act_ret'], (bool) $c['familiar'], $c['nombre'],
                $c['sexo'] === 'M' ? $c['edad'] : '', $c['sexo'] === 'F' ? $c['edad'] : '', $c['cip'], $c['dni'],
                $c['lugar_provincia'], $c['lugar_distrito'], $c['lugar_localidad'], $c['direccion'], $c['cie10'],
                $c['tipo_dx'] === 'P', $c['tipo_dx'] === 'C', $c['tipo_dx'] === 'D',
                $c['protegido'] === 'SI', $c['protegido'] === 'NO', $c['protegido'] === 'IG',
                $c['inicio_sintomas'], $c['difusion'], $c['notificacion'],
                $c['investigacion'] === 'SI', $c['investigacion'] === 'NO',
                $c['vigilancia'] === 'PAS', $c['vigilancia'] === 'ACT',
                $c['unidad'], $c['situacion'],
                $c['descanso1_inicio'], $c['descanso1_fin'], $c['descanso2_inicio'], $c['descanso2_fin'], $c['fecha_alta'],
                $c['observaciones'], $c['codigo'],
            ];
        }
        $marca = fn(string $titulo, string $grupo) => ['titulo' => $titulo, 'grupo' => $grupo, 'tipo' => 'marca'];
        $fecha = fn(string $titulo, ?string $grupo = null) => ['titulo' => $titulo, 'grupo' => $grupo, 'tipo' => 'fecha'];

        $this->cabeceraPnp($this->libro($titulo), $titulo, FormatoPnp::encabezado($filtros['establecimiento_id']), $filtros)
            ->tabla([
                ['titulo' => 'N.°', 'tipo' => 'centro'],
                ['titulo' => 'Grado', 'grupo' => 'Situación'],
                ['titulo' => 'ACT/RET', 'grupo' => 'Situación', 'tipo' => 'centro'],
                $marca('F', 'Situación'),
                ['titulo' => $vih ? 'Iniciales del paciente' : 'Apellidos y nombres', 'anchoMax' => 32],
                ['titulo' => 'M', 'grupo' => 'Edad', 'tipo' => 'entero'],
                ['titulo' => 'F', 'grupo' => 'Edad', 'tipo' => 'entero'],
                ['titulo' => 'N.° CIP'],
                ['titulo' => 'N.° DNI'],
                ['titulo' => 'Provincia', 'grupo' => 'Lugar probable de infección'],
                ['titulo' => 'Distrito', 'grupo' => 'Lugar probable de infección'],
                ['titulo' => 'Localidad', 'grupo' => 'Lugar probable de infección'],
                ['titulo' => 'Dirección de residencia', 'anchoMax' => 32],
                ['titulo' => 'Diagnóstico CIE-10', 'tipo' => 'centro'],
                $marca('P', 'Tipo de Dx.'), $marca('C', 'Tipo de Dx.'), $marca('D', 'Tipo de Dx.'),
                $marca('SI', 'Protegido (vacuna)'), $marca('NO', 'Protegido (vacuna)'), $marca('IG', 'Protegido (vacuna)'),
                $fecha('Inicio de síntomas', 'Fecha de'), $fecha('Difusión', 'Fecha de'), $fecha('Notificación', 'Fecha de'),
                $marca('SI', 'Ficha de investig.'), $marca('NO', 'Ficha de investig.'),
                $marca('Vig. pas.', 'Tipo de vigilancia'), $marca('Bús. act.', 'Tipo de vigilancia'),
                ['titulo' => 'Unidad de origen', 'anchoMax' => 24],
                ['titulo' => 'Situación del paciente', 'anchoMax' => 24],
                $fecha('Inicio', 'Descanso médico 1'), $fecha('Término', 'Descanso médico 1'),
                $fecha('Inicio', 'Descanso médico 2'), $fecha('Término', 'Descanso médico 2'),
                $fecha('Fecha de alta'),
                ['titulo' => 'Observaciones', 'anchoMax' => 36],
                ['titulo' => 'Ficha VIGÍA PNP'],
            ], $filas, ['congelar' => true, 'filtro' => true, 'vacio' => 'Sin casos en la semana'])
            ->nota('Tipo de diagnóstico: P = probable, C = confirmado, D = descartado. Situación: F = familiar.')
            ->descargar(($vih ? 'registro_individual_vih_' : 'registro_individual_') . $filtros['anio'] . '_SE' . str_pad((string) $filtros['semana'], 2, '0', STR_PAD_LEFT));
        exit;
    }

    /**
     * Registro semanal consolidado del Área de Epidemiología PNP (2026-10-21):
     * EDA, cólera, IRA, neumonía, SOB y asma contados de los casos de la
     * semana (ver ConsolidadoPnp).
     */
    public function consolidadoPnp(): void
    {
        $filtros = ListadoMuerteFetalNeonatal::filtros($_GET, Auth::usuario());

        $this->vista('reportes/consolidado-pnp', [
            'tituloVista'      => 'Registro consolidado',
            'rutaActual'       => 'reportes',
            'vistaAncha'       => true,
            'filtros'          => $filtros,
            'establecimientos' => Establecimiento::todos('nombre'),
            'puedeElegirEstablecimiento' => !Auth::tieneRol('REGISTRADOR'),
            'encabezado'       => FormatoPnp::encabezado($filtros['establecimiento_id']),
            'rangoSemana'      => ListadoMuerteFetalNeonatal::rangoDeSemana($filtros['anio'], $filtros['semana']),
            'datos'            => ConsolidadoPnp::semana($filtros),
        ]);
    }

    /**
     * El consolidado en .xlsx (2026-10-30; antes CSV): las tres tablas, una
     * debajo de otra como en el formato, con los encabezados de tres niveles
     * (grupo, medida y edad) combinados y una fila de totales.
     */
    public function exportarConsolidadoPnp(): void
    {
        $filtros = ListadoMuerteFetalNeonatal::filtros($_GET, Auth::usuario());
        $datos = ConsolidadoPnp::semana($filtros);
        $libro = $this->cabeceraPnp($this->libro(ConsolidadoPnp::NOMBRE), ConsolidadoPnp::NOMBRE, FormatoPnp::encabezado($filtros['establecimiento_id']), $filtros);
        $titulos = ['eda' => 'Consolidado semanal de casos de EDA y cólera', 'ira' => 'Consolidado semanal de IRA, asma y SOB', 'neumonia' => 'Neumonía (5 años a más)'];

        foreach (ConsolidadoPnp::columnas() as $nombre => $grupos) {
            $columnas = [
                ['titulo' => 'T', 'grupo' => 'Situación', 'tipo' => 'marca'],
                ['titulo' => 'F', 'grupo' => 'Situación', 'tipo' => 'marca'],
                ['titulo' => 'Provincia', 'anchoMax' => 18],
                ['titulo' => 'Distrito', 'anchoMax' => 18],
            ];
            foreach ($grupos as $grupo => $contenido) {
                foreach ($contenido as $etiqueta => $valor) {
                    foreach (is_array($valor) ? array_keys($valor) : [null] as $edad) {
                        $columnas[] = $edad === null
                            ? ['titulo' => $etiqueta, 'grupo' => $grupo, 'tipo' => 'entero', 'ancho' => 7]
                            : ['titulo' => $edad, 'grupo' => [$grupo, $etiqueta], 'tipo' => 'entero', 'ancho' => 6];
                    }
                }
            }
            $claves = ConsolidadoPnp::clavesDe($grupos);
            $filas = [];
            $totales = array_fill_keys($claves, 0);
            foreach ($datos[$nombre] as $fila) {
                $filas[] = array_merge(
                    [$fila['situacion'] === 'T', $fila['situacion'] === 'F', $fila['provincia'], $fila['distrito']],
                    array_map(fn($clave) => $fila['n'][$clave] ?? '', $claves)
                );
                foreach ($claves as $clave) {
                    $totales[$clave] += (int) ($fila['n'][$clave] ?? 0);
                }
            }
            $libro->seccion(mb_strtoupper($titulos[$nombre]))
                ->tabla($columnas, $filas, [
                    'totales' => $filas ? array_merge(['Total', '', '', ''], array_values($totales)) : null,
                    'vacio'   => 'Sin casos en la semana',
                ]);
        }
        $libro->descargar('registro_consolidado_' . $filtros['anio'] . '_SE' . str_pad((string) $filtros['semana'], 2, '0', STR_PAD_LEFT));
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

        $filas = [];
        foreach ($casos as $i => $c) {
            $filas[] = [
                $i + 1, $c['grado'], $c['act_ret'], (bool) $c['familiar'], $c['historia'], $c['fecha'], $c['nombre'], $c['dni'],
                $c['sexo'] === 'M' ? $c['edad'] : '', $c['sexo'] === 'F' ? $c['edad'] : '', $c['direccion'], $c['distrito'],
                $c['temperatura'], $c['cie10'], $c['diagnostico'], $c['servicio'], $c['codigo'],
            ];
        }

        $this->libro(FormatoPnp::FEBRILES)
            ->membrete(self::MEMBRETE_PNP)
            ->titulo(mb_strtoupper(FormatoPnp::FEBRILES))
            ->datos([
                'Dirección de salud' => 'DIRSAPOL', 'Red de salud' => $encabezado['red'], 'Establecimiento' => $encabezado['establecimiento'] ?: 'Todos',
                'Semana N.°' => $this->textoSemana($filtros), 'Día' => $dia ? fechaIsoADmy($dia) : 'Toda la semana',
            ], 3)
            ->tabla([
                ['titulo' => 'N.°', 'tipo' => 'centro'],
                ['titulo' => 'Grado', 'grupo' => 'Condición del usuario'],
                ['titulo' => 'ACT/RET', 'grupo' => 'Condición del usuario', 'tipo' => 'centro'],
                ['titulo' => 'F', 'grupo' => 'Condición del usuario', 'tipo' => 'marca'],
                ['titulo' => 'H.CL'],
                ['titulo' => 'Fecha', 'tipo' => 'fecha'],
                ['titulo' => 'Apellidos y nombres', 'anchoMax' => 32],
                ['titulo' => 'DNI'],
                ['titulo' => 'M', 'grupo' => 'Edad', 'tipo' => 'entero'],
                ['titulo' => 'F', 'grupo' => 'Edad', 'tipo' => 'entero'],
                ['titulo' => 'Dirección', 'anchoMax' => 32],
                ['titulo' => 'Distrito de residencia', 'anchoMax' => 20],
                ['titulo' => 'Temperatura', 'tipo' => 'decimal'],
                ['titulo' => 'Dx CIE-10', 'tipo' => 'centro'],
                ['titulo' => 'Diagnóstico', 'anchoMax' => 32],
                ['titulo' => 'Servicio', 'anchoMax' => 20],
                ['titulo' => 'Registro VIGÍA PNP'],
            ], $filas, ['congelar' => true, 'filtro' => true, 'vacio' => $dia ? 'Sin febriles registrados ese día' : 'Sin febriles registrados en la semana'])
            ->descargar('registro_febriles_' . $filtros['anio'] . '_SE' . str_pad((string) $filtros['semana'], 2, '0', STR_PAD_LEFT) . ($dia ? '_' . $dia : ''));
        exit;
    }

    /** ?dia= dentro de la semana elegida, o null (toda la semana). */
    private function diaDeLaSemana(array $rango): ?string
    {
        $dia = fechaIsoValida((string) ($_GET['dia'] ?? ''));

        return $dia !== null && $dia >= $rango[0] && $dia <= $rango[1] ? $dia : null;
    }

    /** Libro .xlsx de un reporte, firmado con el usuario que lo descarga. */
    private function libro(string $titulo): LibroXlsx
    {
        return new LibroXlsx($titulo, (string) (Auth::usuario()['nombre'] ?? ''));
    }

    /** Cabecera de los formatos del Área de Epidemiología PNP. */
    private function cabeceraPnp(LibroXlsx $libro, string $titulo, array $encabezado, array $filtros): LibroXlsx
    {
        return $libro->membrete(self::MEMBRETE_PNP)
            ->titulo(mb_strtoupper($titulo))
            ->datos([
                'Código de establecimiento' => $encabezado['codigo'],
                'Semana de notificación'    => $this->textoSemana($filtros),
                'Cabecera de red'           => $encabezado['red'],
                'Establecimiento'           => $encabezado['establecimiento'] ?: 'Todos',
                'Tipo de establecimiento'   => $encabezado['categoria'],
            ], 3);
    }

    /** "SE 43 - 2026 (del 25/10 al 31/10)". */
    private function textoSemana(array $filtros): string
    {
        [$desde, $hasta] = ListadoMuerteFetalNeonatal::rangoDeSemana($filtros['anio'], $filtros['semana']);
        return 'SE ' . $filtros['semana'] . ' - ' . $filtros['anio'] . ' (del ' . date('d/m', strtotime($desde)) . ' al ' . date('d/m', strtotime($hasta)) . ')';
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
