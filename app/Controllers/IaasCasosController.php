<?php
namespace App\Controllers;

use App\Models\IaasCaso;
use App\Models\IaasCatalogo;
use App\Models\IaasNorma;
use App\Models\IaasRonda;
use App\Models\IaasSala;

/**
 * Casos IAAS: listado y ficha de investigación (Anexo 5). La ficha se guarda
 * aunque esté incompleta; queda "completa" sola cuando tiene lo que pide la
 * norma (IaasCaso::faltantes) y el listado separa las que faltan.
 */
class IaasCasosController extends IaasControlador
{
    public function index(): void
    {
        $this->exigirConfiguracion();
        $pestana = in_array($_GET['pestana'] ?? '', ['TODAS', 'POR_COMPLETAR', 'COMPLETO', 'ANULADAS'], true) ? $_GET['pestana'] : 'TODAS';
        $anio = ctype_digit((string) ($_GET['anio'] ?? '')) ? (int) $_GET['anio'] : (int) date('Y');
        $mes = ctype_digit((string) ($_GET['mes'] ?? '')) && (int) $_GET['mes'] >= 1 && (int) $_GET['mes'] <= 12 ? (int) $_GET['mes'] : null;
        $tipo = in_array($_GET['tipo'] ?? '', ['ITS', 'ITU', 'NEU', 'ISQ', 'END'], true) ? $_GET['tipo'] : '';
        $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
        $filtros = ['anio' => $anio, 'mes' => $mes, 'tipo' => $tipo, 'q' => $q];

        $this->vista('iaas/casos/index', $this->datosVista('Casos IAAS', 'iaas/casos', [
            'casos'   => IaasCaso::listado($this->estId(), ['pestana' => $pestana] + $filtros),
            'conteos' => IaasCaso::conteos($this->estId(), $filtros),
            'pestana' => $pestana,
            'filtros' => $filtros,
            'vistaAncha' => true,
        ]));
    }

    public function nuevo(): void
    {
        $this->exigirConfiguracion();
        $internacion = isset($_GET['internacion']) ? IaasRonda::internacion((int) $_GET['internacion'], $this->estId()) : null;
        $caso = [
            'id' => null, 'estado' => 'POR_COMPLETAR', 'sala_id' => $internacion['sala_id'] ?? ($this->salas[0]['id'] ?? null),
            'historia_clinica' => $internacion['historia_clinica'] ?? null, 'peso_nacer_g' => $internacion['peso_nacer_g'] ?? null,
            'fecha_ingreso_servicio' => $internacion['fecha_ingreso'] ?? null, 'fecha_iaas' => date('Y-m-d'),
        ];
        $this->mostrarFormulario($caso, $internacion, [], []);
    }

    public function ver(string $id): void
    {
        $this->exigirConfiguracion();
        $caso = IaasCaso::delEstablecimiento((int) $id, $this->estId());
        if (!$caso) {
            $this->responder(false, 'La ficha no existe en este establecimiento.', '/iaas/casos');
        }
        $this->mostrarFormulario($caso, null, IaasCaso::cultivos((int) $caso['id']), []);
    }

    private function mostrarFormulario(array $caso, ?array $internacion, array $cultivos, array $errores): void
    {
        $salasCaso = $this->salas;
        // Una ficha vieja puede apuntar a una sala que hoy está desactivada.
        if (!empty($caso['sala_id']) && !in_array((int) $caso['sala_id'], array_map('intval', array_column($salasCaso, 'id')), true)) {
            $vieja = IaasSala::delEstablecimientoPorId($this->estId(), (int) $caso['sala_id']);
            if ($vieja) {
                $salasCaso[] = $vieja;
            }
        }
        $titulo = $caso['id'] ? IaasCaso::codigo($caso) : 'Nueva ficha IAAS';
        $this->vista('iaas/casos/formulario', $this->datosVista($titulo, 'iaas/casos', [
            'caso'            => $caso,
            'internacion'     => $internacion,
            'cultivos'        => $cultivos,
            'errores'         => $errores,
            'salasCaso'       => $salasCaso,
            'microorganismos' => IaasCatalogo::microorganismos(),
            'antimicrobianos' => IaasCatalogo::antimicrobianos(),
            'horasT'          => IaasCatalogo::horasT(),
            'faltan'          => $caso['id'] ? IaasCaso::faltantes($caso, $cultivos) : [],
            'dispositivosPaciente' => !empty($caso['internacion_id']) ? IaasRonda::dispositivosDe([(int) $caso['internacion_id']]) : ($internacion ? IaasRonda::dispositivosDe([(int) $internacion['id']]) : []),
        ]));
    }

    public function crear(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $this->guardar(null);
    }

    public function actualizar(string $id): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $caso = IaasCaso::delEstablecimiento((int) $id, $this->estId());
        if (!$caso || $caso['anulado_en']) {
            $this->responder(false, 'La ficha no existe o está anulada.', '/iaas/casos');
        }
        $this->guardar($caso);
    }

    private function guardar(?array $caso): void
    {
        [$datos, $cultivos, $errores] = $this->leerFormulario();
        $internacion = null;
        if (!$caso) {
            $internacion = isset($_POST['internacion_id']) && $_POST['internacion_id'] !== '' ? IaasRonda::internacion((int) $_POST['internacion_id'], $this->estId()) : null;
        }
        $sala = IaasSala::delEstablecimientoPorId($this->estId(), (int) ($datos['sala_id'] ?? 0));
        if (!$sala) {
            $errores[] = 'Elige el servicio y la sala donde se vigila al paciente (1.7 y 1.8).';
        }

        // Dispositivo de la ronda (si la ficha viene de un paciente vigilado).
        $dispositivoId = $caso['dispositivo_id'] ?? null;
        $internacionId = $caso['internacion_id'] ?? ($internacion['id'] ?? null);
        if (isset($_POST['dispositivo_id'])) {
            $dispositivoId = null;
            if ($_POST['dispositivo_id'] !== '' && $internacionId) {
                $d = IaasRonda::dispositivo((int) $_POST['dispositivo_id'], $this->estId());
                $dispositivoId = $d && (int) $d['internacion_id'] === (int) $internacionId ? (int) $d['id'] : null;
            }
        }
        if (IaasNorma::FACTOR_POR_TIPO[$datos['tipo_iaas'] ?? 'ITS'] !== 'dispositivo') {
            $dispositivoId = null;
        }

        if ($errores) {
            $vista = ($caso ?? ['id' => null, 'estado' => 'POR_COMPLETAR']) ;
            $vista = array_merge($vista, $datos, ['dispositivo_id' => $dispositivoId, 'internacion_id' => $internacionId]);
            foreach (['tipo_doc', 'num_doc', 'apellido_paterno', 'apellido_materno', 'nombres', 'sexo', 'fecha_nac'] as $c) {
                $vista[$c] = $vista[$c] ?? ($_POST[$c] ?? null);
            }
            http_response_code(422);
            $this->mostrarFormulario($vista, $internacion, $cultivos, $errores);
            return;
        }

        $personaId = $caso['persona_id'] ?? ($internacion['persona_id'] ?? null);
        if (!$personaId) {
            [$personaId, $erroresPersona] = $this->personaDelFormulario();
            if ($erroresPersona) {
                $vista = array_merge(['id' => null, 'estado' => 'POR_COMPLETAR'], $datos, $_POST);
                http_response_code(422);
                $this->mostrarFormulario($vista, null, $cultivos, ['Falta ' . implode(', ', $erroresPersona) . '.']);
                return;
            }
        }

        $estado = IaasCaso::faltantes($datos, $cultivos) ? 'POR_COMPLETAR' : 'COMPLETO';
        $id = IaasCaso::guardar($caso ? (int) $caso['id'] : null, $datos, [
            'establecimiento_id' => $this->estId(), 'persona_id' => $personaId, 'internacion_id' => $internacionId,
            'dispositivo_id' => $dispositivoId, 'estado' => $estado, 'creado_por' => (int) $this->usuario['id'],
        ], $cultivos);
        $nueva = IaasCaso::buscar($id);
        $this->responder(true, IaasCaso::codigo($nueva) . ($estado === 'COMPLETO' ? ' guardada y completa.' : ' guardada. Aún le faltan datos: quedó «por completar».'), '/iaas/casos/' . $id);
    }

    /** @return array{0: array, 1: array, 2: string[]} datos, cultivos, errores */
    private function leerFormulario(): array
    {
        $errores = [];
        $fecha = function (string $campo, string $etiqueta) use (&$errores): ?string {
            $valor = trim((string) ($_POST[$campo] ?? ''));
            if ($valor === '') {
                return null;
            }
            $f = $this->fechaPasada($valor);
            if (!$f) {
                $errores[] = "$etiqueta: fecha no válida o futura.";
            }
            return $f;
        };
        $tipo = $this->opcion('tipo_iaas', array_keys(IaasNorma::TIPOS_IAAS));
        $factor = IaasNorma::FACTOR_POR_TIPO[$tipo ?? ''] ?? null;
        $datos = [
            'sala_id'                => $this->entero('sala_id', 1, PHP_INT_MAX),
            'historia_clinica'       => $this->texto('historia_clinica', 30),
            'peso_nacer_g'           => $this->entero('peso_nacer_g', 300, 7000),
            'fecha_ingreso_ipress'   => $fecha('fecha_ingreso_ipress', '1.9 Fecha de ingreso a la IPRESS'),
            'fecha_ingreso_servicio' => $fecha('fecha_ingreso_servicio', '1.10 Fecha de ingreso al servicio'),
            'fecha_egreso_servicio'  => $fecha('fecha_egreso_servicio', '1.11 Fecha de egreso del servicio'),
            'condicion_egreso'       => $this->opcion('condicion_egreso', array_keys(IaasNorma::CONDICION_EGRESO)),
            'tipo_iaas'              => $tipo,
            'fecha_iaas'             => $fecha('fecha_iaas', '2.2 Fecha de IAAS'),
            'criterio'               => $tipo ? $this->opcion('criterio', array_keys(IaasNorma::CRITERIOS[$tipo])) : null,
            'its_secundaria'         => $tipo === 'ITS' ? (isset($_POST['its_secundaria']) && in_array($_POST['its_secundaria'], ['0', '1'], true) ? (int) $_POST['its_secundaria'] : null) : null,
            'dispositivo_tipo'       => $factor === 'dispositivo' ? $this->opcion('dispositivo_tipo', IaasNorma::DISPOSITIVOS_POR_TIPO[$tipo]) : null,
            'fecha_instalacion'      => $factor === 'dispositivo' ? $fecha('fecha_instalacion', '3.2 Fecha de instalación') : null,
            'fecha_retiro'           => $factor === 'dispositivo' ? $fecha('fecha_retiro', '3.3 Fecha de retiro') : null,
            // Cirugía y parto usan campos con nombre propio: los dos bloques están en la página.
            'procedimiento_tipo'     => $factor === 'cirugia' ? $this->opcion('procedimiento_tipo_cir', IaasNorma::CIRUGIAS_ISQ) : ($factor === 'parto' ? $this->opcion('procedimiento_tipo_parto', IaasNorma::PARTOS_END) : null),
            'procedimiento_nombre'   => $factor === 'cirugia' ? $this->texto('procedimiento_nombre', 120) : null,
            'fecha_procedimiento'    => $factor === 'cirugia' ? $fecha('fecha_procedimiento_cir', '3.8 Fecha de la cirugía') : ($factor === 'parto' ? $fecha('fecha_procedimiento_parto', '3.5 Fecha del procedimiento') : null),
            'duracion_min'           => $factor === 'cirugia' ? $this->entero('duracion_min', 1, 1440) : null,
            'clase_herida'           => $factor === 'cirugia' ? $this->opcion('clase_herida', array_keys(IaasNorma::CLASE_HERIDA)) : null,
            'asa'                    => $factor === 'cirugia' ? $this->entero('asa', 1, 5) : null,
            'implante'               => $factor === 'cirugia' && isset($_POST['implante']) && in_array($_POST['implante'], ['0', '1'], true) ? (int) $_POST['implante'] : null,
            'fecha_fin_tratamiento'  => $fecha('fecha_fin_tratamiento', 'Fin del tratamiento'),
            'observaciones'          => $this->texto('observaciones', 500),
        ];
        foreach (['dx1_cie10', 'dx2_cie10', 'dx3_cie10'] as $n => $campo) {
            $valor = trim((string) ($_POST[$campo] ?? ''));
            $codigo = $valor !== '' ? codigoCie10DelCatalogo($valor) : null;
            if ($valor !== '' && $codigo === null) {
                $errores[] = '1.13 Diagnóstico ' . ($n + 1) . ': ' . errorCodigoCie10($valor);
            }
            $datos[$campo] = $codigo;
        }
        if (!$tipo) {
            $errores[] = 'Elige el tipo de IAAS (2.1).';
        }
        if (!$datos['fecha_iaas'] && !in_array('2.2 Fecha de IAAS: fecha no válida o futura.', $errores, true)) {
            $errores[] = 'Indica la fecha de la IAAS (2.2).';
        }
        $orden = [
            ['fecha_ingreso_ipress', 'fecha_ingreso_servicio', 'el ingreso al servicio no puede ser anterior al ingreso a la IPRESS'],
            ['fecha_ingreso_servicio', 'fecha_egreso_servicio', 'el egreso no puede ser anterior al ingreso al servicio'],
            ['fecha_instalacion', 'fecha_retiro', 'el retiro no puede ser anterior a la instalación'],
            ['fecha_instalacion', 'fecha_iaas', 'la IAAS no puede ser anterior a la instalación del dispositivo'],
            ['fecha_procedimiento', 'fecha_iaas', 'la IAAS no puede ser anterior al procedimiento'],
            ['fecha_iaas', 'fecha_fin_tratamiento', 'el fin del tratamiento no puede ser anterior a la IAAS'],
        ];
        foreach ($orden as [$antes, $despues, $mensaje]) {
            if ($datos[$antes] && $datos[$despues] && $datos[$despues] < $datos[$antes]) {
                $errores[] = ucfirst($mensaje) . '.';
            }
        }

        // Cultivos (hasta 2): uno vale si trae algo escrito.
        $cultivos = [];
        $antimicrobianos = array_column(IaasCatalogo::antimicrobianos(false), 'id');
        foreach ([1, 2] as $orden) {
            $c = $_POST['cultivo'][$orden] ?? [];
            if (!is_array($c)) {
                continue;
            }
            $tipoCultivo = in_array($c['tipo_cultivo'] ?? '', array_keys(IaasNorma::TIPOS_CULTIVO), true) ? $c['tipo_cultivo'] : null;
            $micro = ctype_digit((string) ($c['microorganismo_id'] ?? '')) ? (int) $c['microorganismo_id'] : null;
            $atb = [];
            foreach ((array) ($c['atb'] ?? []) as $atbId => $resultado) {
                if (in_array((int) $atbId, array_map('intval', $antimicrobianos), true) && in_array($resultado, ['S', 'I', 'R'], true)) {
                    $atb[(int) $atbId] = $resultado;
                }
            }
            $fechaExtraccion = trim((string) ($c['fecha_extraccion'] ?? ''));
            $fechaOk = $fechaExtraccion === '' ? null : $this->fechaPasada($fechaExtraccion);
            if ($fechaExtraccion !== '' && !$fechaOk) {
                $errores[] = "Cultivo $orden: fecha de extracción no válida o futura.";
            }
            $cultivo = [
                'tipo_cultivo' => $tipoCultivo,
                'tipo_cultivo_otro' => $tipoCultivo === 'OTRO' ? mb_substr(trim((string) ($c['tipo_cultivo_otro'] ?? '')), 0, 80) ?: null : null,
                'fecha_extraccion' => $fechaOk,
                'microorganismo_id' => $micro,
                'microorganismo_otro' => mb_substr(trim((string) ($c['microorganismo_otro'] ?? '')), 0, 120) ?: null,
                'blee' => in_array($c['blee'] ?? '', ['SI', 'NO'], true) ? $c['blee'] : null,
                'otro_mecanismo' => mb_substr(trim((string) ($c['otro_mecanismo'] ?? '')), 0, 160) ?: null,
                'antibiograma' => $atb,
            ];
            $tieneAlgo = $tipoCultivo || $micro || $cultivo['microorganismo_otro'] || $fechaOk || $atb || $cultivo['blee'] || $cultivo['otro_mecanismo'];
            if ($tieneAlgo) {
                $cultivos[$orden] = $cultivo;
            }
        }
        if (isset($cultivos[2]) && !isset($cultivos[1])) {
            $cultivos = [1 => $cultivos[2]];
        }
        return [$datos, $cultivos, $errores];
    }

    public function anular(string $id): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $caso = IaasCaso::delEstablecimiento((int) $id, $this->estId());
        $motivo = $this->texto('motivo', 255);
        if (!$caso || $caso['anulado_en'] || !$motivo) {
            $this->responder(false, 'Indica el motivo para anular la ficha.', '/iaas/casos/' . (int) $id);
        }
        IaasCaso::anular($caso, (int) $this->usuario['id'], $motivo);
        $this->responder(true, IaasCaso::codigo($caso) . ' anulada. Sus días vuelven a contar en la ronda.', '/iaas/casos');
    }
}
