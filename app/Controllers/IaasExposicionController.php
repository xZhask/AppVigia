<?php
namespace App\Controllers;

use App\Models\IaasExposicion;
use App\Models\IaasNorma;

/**
 * Exposición laboral a agentes patógenos de la sangre (Anexo 8). Aplica a
 * todos los servicios y a toda IPRESS, tenga o no hospitalización: no pide
 * haber configurado servicios.
 */
class IaasExposicionController extends IaasControlador
{
    public function index(): void
    {
        $this->exigirEstablecimiento();
        $anio = ctype_digit((string) ($_GET['anio'] ?? '')) ? (int) $_GET['anio'] : (int) date('Y');
        $mes = ctype_digit((string) ($_GET['mes'] ?? '')) && (int) $_GET['mes'] >= 1 && (int) $_GET['mes'] <= 12 ? (int) $_GET['mes'] : null;
        $anuladas = ($_GET['ver'] ?? '') === 'anuladas';
        $this->vista('iaas/exposicion/index', $this->datosVista('Exposición laboral', 'iaas/exposicion', [
            'registros' => IaasExposicion::listado($this->estId(), $anio, $mes, $anuladas),
            'delAnio'   => IaasExposicion::listado($this->estId(), $anio, null, false),
            'anio'      => $anio,
            'mes'       => $mes,
            'anuladas'  => $anuladas,
            'vistaAncha' => true,
        ]));
    }

    public function nueva(): void
    {
        $this->exigirEstablecimiento();
        $this->mostrar(['id' => null, 'fecha' => date('Y-m-d'), 'factor' => 'PUNZOCORTANTE'], []);
    }

    public function ver(string $id): void
    {
        $this->exigirEstablecimiento();
        $registro = IaasExposicion::delEstablecimiento((int) $id, $this->estId());
        if (!$registro) {
            $this->responder(false, 'El registro no existe en este establecimiento.', '/iaas/exposicion');
        }
        $this->mostrar($registro, []);
    }

    private function mostrar(array $registro, array $errores): void
    {
        $this->vista('iaas/exposicion/formulario', $this->datosVista($registro['id'] ? 'Exposición laboral N.º ' . $registro['id'] : 'Registrar exposición laboral', 'iaas/exposicion', [
            'registro' => $registro,
            'errores'  => $errores,
        ]));
    }

    public function crear(): void
    {
        $this->exigirEstablecimiento();
        $this->exigirCsrf();
        $this->guardar(null);
    }

    public function actualizar(string $id): void
    {
        $this->exigirEstablecimiento();
        $this->exigirCsrf();
        $registro = IaasExposicion::delEstablecimiento((int) $id, $this->estId());
        if (!$registro || $registro['anulado_en']) {
            $this->responder(false, 'El registro no existe o está anulado.', '/iaas/exposicion');
        }
        $this->guardar($registro);
    }

    private function guardar(?array $registro): void
    {
        $errores = [];
        $factor = $this->opcion('factor', array_keys(IaasNorma::FACTORES_EXPOSICION));
        $punzo = $factor === 'PUNZOCORTANTE';
        $hora = trim((string) ($_POST['hora'] ?? ''));
        $epp = array_values(array_intersect(array_keys(IaasNorma::EPP), (array) ($_POST['epp'] ?? [])));
        $siNo = fn(string $c) => isset($_POST[$c]) && in_array($_POST[$c], ['0', '1'], true) ? (int) $_POST[$c] : null;
        $datos = [
            'identificador'   => $this->texto('identificador', 30),
            'edad_anios'      => $this->entero('edad_anios', 14, 99),
            'sexo'            => $this->opcion('sexo', ['M', 'F']),
            'profesion'       => $this->opcion('profesion', array_keys(IaasNorma::PROFESIONES)),
            'profesion_otra'  => ($_POST['profesion'] ?? '') === 'OTRO' ? $this->texto('profesion_otra', 80) : null,
            'condicion'       => $this->opcion('condicion', array_keys(IaasNorma::CONDICIONES_PERSONAL)),
            'servicio'        => $this->opcion('servicio', array_keys(IaasNorma::SERVICIOS_EXPOSICION)),
            'servicio_otro'   => ($_POST['servicio'] ?? '') === 'OTRO' ? $this->texto('servicio_otro', 80) : null,
            'factor'          => $factor,
            'fecha'           => $this->fechaPasada($_POST['fecha'] ?? ''),
            'hora'            => $hora !== '' ? horaValida($hora) : null,
            'objeto'          => $punzo ? $this->texto('objeto', 120) : null,
            'profundidad'     => $punzo ? $this->opcion('profundidad', array_keys(IaasNorma::PROFUNDIDADES)) : null,
            'fluido'          => $this->opcion('fluido', array_keys(IaasNorma::FLUIDOS)),
            'fluido_otro'     => ($_POST['fluido'] ?? '') === 'OTRO' ? $this->texto('fluido_otro', 80) : null,
            'epp'             => $epp ? implode(',', $epp) : null,
            'localizacion'    => $this->opcion('localizacion', array_keys(IaasNorma::LOCALIZACIONES)),
            'paciente_fuente' => $siNo('paciente_fuente'),
            'procedimiento'   => $this->opcion('procedimiento', array_keys(IaasNorma::PROCEDIMIENTOS_EXPOSICION)),
            'procedimiento_otro' => ($_POST['procedimiento'] ?? '') === 'OTRO' ? $this->texto('procedimiento_otro', 80) : null,
            'profilaxis'      => $siNo('profilaxis'),
            'profilaxis_medicamento' => $siNo('profilaxis') === 1 ? $this->texto('profilaxis_medicamento', 160) : null,
            'observaciones'   => $this->texto('observaciones', 255),
        ];
        foreach (['profesion' => 'la profesión u ocupación', 'condicion' => 'la condición', 'servicio' => 'el servicio o lugar', 'factor' => 'el factor de riesgo', 'fecha' => 'la fecha (no futura)'] as $campo => $etq) {
            if ($datos[$campo] === null) {
                $errores[] = 'Indica ' . $etq . '.';
            }
        }
        if ($hora !== '' && $datos['hora'] === null) {
            $errores[] = 'La hora no es válida (formato 24 h, por ejemplo 14:30).';
        }
        if ($errores) {
            http_response_code(422);
            $this->mostrar(['id' => $registro['id'] ?? null] + $datos, $errores);
            return;
        }
        if ($registro) {
            IaasExposicion::actualizar((int) $registro['id'], $datos);
            $id = (int) $registro['id'];
        } else {
            $id = IaasExposicion::crear($datos + ['establecimiento_id' => $this->estId(), 'creado_por' => (int) $this->usuario['id']]);
        }
        $this->responder(true, 'Exposición laboral N.º ' . $id . ' guardada.', '/iaas/exposicion');
    }

    public function anular(string $id): void
    {
        $this->exigirEstablecimiento();
        $this->exigirCsrf();
        $registro = IaasExposicion::delEstablecimiento((int) $id, $this->estId());
        if (!$registro || $registro['anulado_en']) {
            $this->responder(false, 'El registro no existe o ya está anulado.', '/iaas/exposicion');
        }
        IaasExposicion::anular((int) $id, (int) $this->usuario['id']);
        $this->responder(true, 'Registro N.º ' . (int) $id . ' anulado. Ya no cuenta en el indicador.', '/iaas/exposicion');
    }
}
