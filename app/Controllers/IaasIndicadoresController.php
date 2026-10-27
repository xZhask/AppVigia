<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\IaasIndicadores;
use App\Models\IaasNorma;
use App\Models\IaasRonda;
use App\Models\IaasSala;

/**
 * Indicadores del mes (Anexo 10), revisión de calidad, cierre mensual por
 * sala y personal de salud del año (Anexo 9). Sirve también a los
 * establecimientos sin hospitalización (solo el indicador 9).
 */
class IaasIndicadoresController extends IaasControlador
{
    public function index(): void
    {
        $this->exigirEstablecimiento();
        $hoy = date('Y-m-d');
        // Por defecto, el mes anterior: es el que toca revisar y cerrar.
        $porDefecto = date('Y-m', strtotime('first day of last month'));
        $pedido = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['mes'] ?? '')) ? $_GET['mes'] : $porDefecto;
        if ($pedido > substr($hoy, 0, 7)) {
            $pedido = substr($hoy, 0, 7);
        }
        [$anio, $mes] = array_map('intval', explode('-', $pedido));
        if ($mes < 1 || $mes > 12) {
            [$anio, $mes] = array_map('intval', explode('-', $porDefecto));
        }
        $salaId = ctype_digit((string) ($_GET['sala'] ?? '')) ? (int) $_GET['sala'] : null;
        if ($salaId && !IaasSala::delEstablecimientoPorId($this->estId(), $salaId)) {
            $salaId = null;
        }
        [, $fin] = IaasIndicadores::rangoMes($anio, $mes);

        $this->vista('iaas/indicadores', $this->datosVista('Indicadores', 'iaas/indicadores', [
            'anio'       => $anio,
            'mes'        => $mes,
            'salaId'     => $salaId,
            'mesTerminado' => $fin < $hoy,
            'resultado'  => IaasIndicadores::calcular($this->estId(), $anio, $mes, $salaId, $this->salas),
            'calidad'    => IaasIndicadores::calidad($this->estId(), $anio, $mes, $salaId ? array_values(array_filter($this->salas, fn($s) => (int) $s['id'] === $salaId)) : $this->salas),
            'vistaAncha' => true,
        ]));
    }

    private function volver(int $anio, int $mes): string
    {
        $sala = ctype_digit((string) ($_POST['filtro_sala'] ?? '')) ? (int) $_POST['filtro_sala'] : null;
        return '/iaas/indicadores?' . http_build_query(array_filter(['mes' => sprintf('%04d-%02d', $anio, $mes), 'sala' => $sala]));
    }

    /** @return array{0: array, 1: int, 2: int} sala, año, mes */
    private function salaYMes(): array
    {
        $anio = (int) ($_POST['anio'] ?? 0);
        $mes = (int) ($_POST['mes'] ?? 0);
        $sala = IaasSala::delEstablecimientoPorId($this->estId(), (int) ($_POST['sala_id'] ?? 0));
        if (!$sala || $anio < 2020 || $mes < 1 || $mes > 12) {
            $this->responder(false, 'Sala o mes no válidos.', '/iaas/indicadores');
        }
        return [$sala, $anio, $mes];
    }

    public function cerrar(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        [$sala, $anio, $mes] = $this->salaYMes();
        [, $fin] = IaasIndicadores::rangoMes($anio, $mes);
        $volver = $this->volver($anio, $mes);
        if ($fin >= date('Y-m-d')) {
            $this->responder(false, 'El mes todavía no termina: se cierra desde el día 1 del mes siguiente.', $volver);
        }
        IaasRonda::cerrarMes((int) $sala['id'], $anio, $mes, (int) $this->usuario['id']);
        $this->responder(true, IaasNorma::nombreSala($sala) . ': ' . IaasNorma::nombreMes($mes, $anio, false) . ' cerrado.', $volver);
    }

    public function reabrir(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        Auth::exigirRol('ADMIN');
        [$sala, $anio, $mes] = $this->salaYMes();
        $motivo = $this->texto('motivo', 255);
        $volver = $this->volver($anio, $mes);
        if (!$motivo) {
            $this->responder(false, 'Indica el motivo de la reapertura.', $volver);
        }
        IaasRonda::reabrirMes((int) $sala['id'], $anio, $mes, (int) $this->usuario['id'], $motivo);
        $this->responder(true, IaasNorma::nombreSala($sala) . ': ' . IaasNorma::nombreMes($mes, $anio, false) . ' reabierto.', $volver);
    }

    public function guardarPersonal(): void
    {
        $this->exigirEstablecimiento();
        $this->exigirCsrf();
        $anio = (int) ($_POST['anio'] ?? 0);
        $volver = '/iaas/indicadores?' . http_build_query(array_filter(['mes' => $_POST['volver_mes'] ?? null]));
        if ($anio < 2020 || $anio > (int) date('Y')) {
            $this->responder(false, 'Año no válido.', $volver);
        }
        $cantidades = [];
        foreach (array_keys(IaasNorma::CONDICIONES_PERSONAL) as $condicion) {
            $n = $this->entero('personal_' . strtolower($condicion), 0, 20000);
            if ($n === null && trim((string) ($_POST['personal_' . strtolower($condicion)] ?? '')) !== '') {
                $this->responder(false, 'Las cantidades de personal deben ser números enteros.', $volver);
            }
            $cantidades[strtolower($condicion)] = $n ?? 0;
        }
        if (!array_sum($cantidades)) {
            $this->responder(false, 'Ingresa al menos una cantidad de personal.', $volver);
        }
        IaasIndicadores::guardarPersonal($this->estId(), $anio, $cantidades, (int) $this->usuario['id']);
        $this->responder(true, 'Personal de salud ' . $anio . ' guardado: ' . array_sum($cantidades) . ' en total.', $volver);
    }
}
