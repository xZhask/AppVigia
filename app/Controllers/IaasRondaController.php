<?php
namespace App\Controllers;

use App\Models\IaasCaso;
use App\Models\IaasNorma;
use App\Models\IaasRonda;
use App\Models\IaasSala;
use App\Models\Persona;
use App\Services\PersonaService;
use DateTimeImmutable;

/**
 * Ronda diaria (Anexos 4, 6 y 7) y configuración de servicios del módulo
 * IAAS. La pantalla muestra HOY por defecto; la vista del mes reproduce la
 * grilla del papel para revisar, corregir e imprimir. Ver IaasRonda.
 */
class IaasRondaController extends IaasControlador
{
    /** /iaas: la ronda, o la exposición laboral si no hay hospitalización. */
    public function inicio(): void
    {
        $this->exigirEstablecimiento();
        if (!$this->configuracion) {
            header('Location: /iaas/servicios');
        } elseif ($this->configuracion['sin_hospitalizacion']) {
            header('Location: /iaas/exposicion');
        } else {
            header('Location: /iaas/ronda');
        }
        exit;
    }

    // ------------------------------------------------------------ servicios

    public function servicios(): void
    {
        $this->exigirEstablecimiento();
        $todas = IaasSala::delEstablecimiento($this->estId(), false);
        $salasPorUpss = [];
        foreach ($todas as $s) {
            if ($s['activo']) {
                $salasPorUpss[$s['upss']][] = $s['nombre'];
            }
        }
        $this->vista('iaas/servicios', $this->datosVista('Servicios vigilados', 'iaas/servicios', [
            'salasPorUpss'     => $salasPorUpss,
            'pacientesActivos' => IaasSala::pacientesActivos($this->estId()),
            'todas'            => $todas,
            'primeraVez'       => $this->configuracion === null,
        ]));
    }

    public function guardarServicios(): void
    {
        $this->exigirEstablecimiento();
        $this->exigirCsrf();
        $sinHospitalizacion = ($_POST['modo'] ?? '') === 'sin';
        $salasPorUpss = [];
        if (!$sinHospitalizacion) {
            foreach (array_keys(IaasNorma::UPSS) as $upss) {
                if (empty($_POST['upss'][$upss])) {
                    continue;
                }
                $nombres = [];
                foreach (explode("\n", (string) ($_POST['salas'][$upss] ?? '')) as $nombre) {
                    $nombre = mb_substr(trim(preg_replace('/\s+/u', ' ', $nombre)), 0, 80);
                    if ($nombre !== '' && !in_array(mb_strtolower($nombre), array_map('mb_strtolower', $nombres), true)) {
                        $nombres[] = $nombre;
                    }
                }
                $salasPorUpss[$upss] = $nombres ?: [IaasNorma::UPSS[$upss]];
            }
            if (!$salasPorUpss) {
                $this->responder(false, 'Marca al menos un servicio, o indica que el establecimiento no tiene hospitalización.', '/iaas/servicios');
            }
        }

        // No se puede quitar una sala con pacientes sin egreso.
        $activos = IaasSala::pacientesActivos($this->estId());
        foreach (IaasSala::delEstablecimiento($this->estId()) as $sala) {
            $sigue = in_array(mb_strtolower($sala['nombre']), array_map('mb_strtolower', $salasPorUpss[$sala['upss']] ?? []), true);
            if (!$sigue && ($activos[$sala['id']] ?? 0) > 0) {
                $this->responder(false, 'No se puede quitar ' . IaasNorma::nombreSala($sala) . ': tiene ' . $activos[$sala['id']] . ' paciente(s) sin egreso. Regístrales el egreso primero.', '/iaas/servicios');
            }
        }

        IaasSala::guardarConfiguracion($this->estId(), $sinHospitalizacion, $salasPorUpss, (int) $this->usuario['id']);
        $this->responder(true, 'Servicios guardados.', $sinHospitalizacion ? '/iaas/exposicion' : '/iaas/ronda');
    }

    // ------------------------------------------------------------ ronda

    public function ronda(): void
    {
        $this->exigirConfiguracion();
        if ($this->configuracion['sin_hospitalizacion'] || !$this->salas) {
            header('Location: ' . ($this->configuracion['sin_hospitalizacion'] ? '/iaas/exposicion' : '/iaas/servicios'));
            exit;
        }
        $hoy = date('Y-m-d');
        $fecha = $this->fechaPasada($_GET['fecha'] ?? '') ?? $hoy;
        $resumen = IaasRonda::resumenSalas($this->estId(), $fecha);
        $salaId = (int) ($_GET['sala'] ?? $_SESSION['iaas_sala'] ?? 0);
        $sala = null;
        foreach ($this->salas as $s) {
            if ((int) $s['id'] === $salaId) {
                $sala = $s;
            }
        }
        $sala ??= $this->salas[0];
        $_SESSION['iaas_sala'] = (int) $sala['id'];

        $vistaMes = ($_GET['vista'] ?? '') === 'mes';
        $datos = [
            'sala'        => $sala,
            'fecha'       => $fecha,
            'hoy'         => $hoy,
            'resumen'     => $resumen,
            'vistaMes'    => $vistaMes,
            'neonatal'    => IaasNorma::esNeonatal($sala['upss']),
            'dispositivosSala' => IaasNorma::DISPOSITIVOS_POR_UPSS[$sala['upss']],
            'procedimientosSala' => IaasNorma::PROCEDIMIENTOS_POR_UPSS[$sala['upss']] ?? [],
            'vistaAncha'  => true,
        ];

        if ($vistaMes) {
            $mes = preg_match('/^(\d{4})-(\d{2})$/', (string) ($_GET['mes'] ?? ''), $m) ? [(int) $m[1], (int) $m[2]] : [(int) substr($fecha, 0, 4), (int) substr($fecha, 5, 2)];
            if (sprintf('%04d-%02d', ...$mes) > substr($hoy, 0, 7) || $mes[1] < 1 || $mes[1] > 12) {
                $mes = [(int) substr($hoy, 0, 4), (int) substr($hoy, 5, 2)];
            }
            [$anio, $nMes] = $mes;
            $grilla = IaasRonda::mes((int) $sala['id'], $anio, $nMes);
            $datos += [
                'anio'   => $anio,
                'mes'    => $nMes,
                'grilla' => $grilla,
                'cierre' => IaasRonda::cierre((int) $sala['id'], $anio, $nMes),
                'procedimientosMes' => $datos['procedimientosSala'] ? IaasRonda::procedimientosDelMes((int) $sala['id'], $grilla['inicio'], $grilla['fin']) : [],
                'infeccionesMes'    => $datos['procedimientosSala'] ? IaasRonda::infeccionesPorProcedimiento((int) $sala['id'], $grilla['inicio'], $grilla['fin']) : [],
            ];
        } else {
            $datos += [
                'pacientes'      => IaasRonda::delDia((int) $sala['id'], $fecha),
                'faltantes'      => IaasRonda::faltantes((int) $sala['id'], $fecha),
                'procedimientos' => IaasRonda::procedimientosDelDia((int) $sala['id'], $fecha),
                'mesCerrado'     => IaasRonda::mesCerrado((int) $sala['id'], $fecha),
            ];
        }

        $this->vista('iaas/ronda', $this->datosVista('Ronda diaria', 'iaas/ronda', $datos));
    }

    /** Sala del establecimiento por id (POST), o termina con error. */
    private function salaPost(string $volver): array
    {
        $sala = IaasSala::delEstablecimientoPorId($this->estId(), (int) ($_POST['sala_id'] ?? 0));
        if (!$sala || !$sala['activo']) {
            $this->responder(false, 'La sala no existe en este establecimiento.', $volver);
        }
        return $sala;
    }

    private function volverRonda(int $salaId, ?string $fecha = null): string
    {
        $volver = trim((string) ($_POST['volver'] ?? ''));
        if ($volver !== '' && str_starts_with($volver, '/iaas/')) {
            return $volver;
        }
        return '/iaas/ronda?' . http_build_query(array_filter(['sala' => $salaId, 'fecha' => $fecha !== date('Y-m-d') ? $fecha : null]));
    }

    private function exigirMesAbierto(int $salaId, string $fecha, string $volver): void
    {
        if (IaasRonda::mesCerrado($salaId, $fecha)) {
            $this->responder(false, 'El mes de ' . IaasNorma::nombreMes((int) substr($fecha, 5, 2), (int) substr($fecha, 0, 4), false) . ' está cerrado para esta sala. Un administrador puede reabrirlo desde Indicadores.', $volver);
        }
    }

    public function marcar(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $dispositivo = IaasRonda::dispositivo((int) ($_POST['dispositivo_id'] ?? 0), $this->estId());
        $fecha = $this->fechaPasada($_POST['fecha'] ?? '');
        $volver = $this->volverRonda((int) ($dispositivo['sala_id'] ?? 0), $fecha);
        if (!$dispositivo || !$fecha) {
            $this->responder(false, 'No se pudo registrar la marca: dispositivo o fecha no válidos (no se marcan días futuros).', $volver);
        }
        $instalado = $dispositivo['fecha_instalacion'] <= $fecha && ($dispositivo['fecha_retiro'] === null || $fecha < $dispositivo['fecha_retiro']);
        if (!$instalado) {
            $this->responder(false, 'Ese día el dispositivo no estaba instalado.', $volver);
        }
        $this->exigirMesAbierto((int) $dispositivo['sala_id'], $fecha, $volver);
        $estado = IaasRonda::marcar((int) $dispositivo['id'], $fecha, ($_POST['marcado'] ?? '1') === '1', (int) $this->usuario['id']);
        $this->responder(true, $estado === null ? 'Marca quitada.' : 'Día marcado.', $volver, ['estado' => $estado]);
    }

    public function confirmar(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $sala = $this->salaPost('/iaas/ronda');
        $fecha = $this->fechaPasada($_POST['fecha'] ?? '') ?? date('Y-m-d');
        $volver = $this->volverRonda((int) $sala['id'], $fecha);
        $this->exigirMesAbierto((int) $sala['id'], $fecha, $volver);
        $n = IaasRonda::confirmarSala((int) $sala['id'], $fecha, (int) $this->usuario['id']);
        $this->responder(true, $n ? "Sala confirmada: $n dispositivo(s) marcados." : 'No había nada pendiente en esta sala.', $volver, ['marcados' => $n]);
    }

    public function completar(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $sala = $this->salaPost('/iaas/ronda');
        $fecha = $this->fechaPasada($_POST['fecha'] ?? '') ?? date('Y-m-d');
        $n = IaasRonda::completarFaltantes((int) $sala['id'], $fecha, (int) $this->usuario['id']);
        $this->responder(true, "Días completados: $n marca(s) como «sigue igual».", $this->volverRonda((int) $sala['id'], $fecha));
    }

    /** GET JSON: busca una persona por documento para "Agregar paciente". */
    public function buscarPersona(): void
    {
        $this->exigirConfiguracion();
        header('Content-Type: application/json; charset=utf-8');
        $tipo = in_array($_GET['tipo_doc'] ?? '', self::TIPOS_DOC, true) ? $_GET['tipo_doc'] : 'DNI';
        $num = trim((string) ($_GET['num_doc'] ?? ''));
        if ($num === '' || $tipo === 'SIN_DOCUMENTO') {
            echo json_encode(['persona' => null]);
            return;
        }
        $persona = PersonaService::buscarOCrear($tipo, $num);
        $abierta = $persona ? IaasRonda::internacionAbierta((int) $persona['id'], $this->estId()) : null;
        echo json_encode([
            'persona' => $persona ? [
                'apellido_paterno' => $persona['apellido_paterno'], 'apellido_materno' => $persona['apellido_materno'],
                'nombres' => $persona['nombres'], 'nombre' => Persona::nombreCompleto($persona),
                'sexo' => $persona['sexo'], 'fecha_nac' => $persona['fecha_nac'], 'historia_clinica' => $persona['n_historia_clinica'],
            ] : null,
            'abierta' => $abierta ? IaasNorma::nombreSala($abierta) . ($abierta['n_cama'] ? ', cama ' . $abierta['n_cama'] : '') : null,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function ingresar(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $sala = $this->salaPost('/iaas/ronda');
        $volver = $this->volverRonda((int) $sala['id'], $this->fechaPasada($_POST['fecha_ronda'] ?? ''));
        $errores = [];

        $fechaIngreso = $this->fechaPasada($_POST['fecha_ingreso'] ?? '');
        if (!$fechaIngreso) {
            $errores[] = 'la fecha de ingreso al servicio (no puede ser futura)';
        }
        $neonatal = IaasNorma::esNeonatal($sala['upss']);
        $peso = $this->entero('peso_nacer_g', 300, 7000);
        if ($neonatal && !$peso) {
            $errores[] = 'el peso al nacer en gramos (entre 300 y 7000)';
        }

        $dispositivos = [];
        foreach (IaasNorma::DISPOSITIVOS_POR_UPSS[$sala['upss']] as $tipo) {
            if (!empty($_POST['disp'][$tipo])) {
                $instalacion = $this->fechaPasada($_POST['disp_fecha'][$tipo] ?? '');
                if (!$instalacion || ($fechaIngreso && $instalacion < $fechaIngreso)) {
                    $errores[] = 'la fecha de instalación de ' . IaasNorma::DISPOSITIVOS_SIGLA[$tipo] . ' (desde el ingreso, no futura)';
                } else {
                    $dispositivos[$tipo] = $instalacion;
                }
            }
        }
        if ($sala['upss'] !== 'GINECO_OBSTETRICIA' && $sala['upss'] !== 'TRAUMATOLOGIA' && !$dispositivos && IaasNorma::DISPOSITIVOS_POR_UPSS[$sala['upss']]) {
            $errores[] = 'al menos un dispositivo (en esta sala se vigilan pacientes con dispositivo)';
        }

        if ($errores) {
            $this->responder(false, 'Falta ' . implode(', ', $errores) . '.', $volver);
        }
        [$personaId, $erroresPersona] = $this->personaDelFormulario();
        if ($erroresPersona) {
            $this->responder(false, 'Falta ' . implode(', ', $erroresPersona) . '.', $volver);
        }
        if ($abierta = IaasRonda::internacionAbierta($personaId, $this->estId())) {
            $this->responder(false, 'Este paciente ya está vigilado en ' . IaasNorma::nombreSala($abierta) . '. Regístrale el egreso (transferido) allí antes de ingresarlo aquí.', $volver);
        }

        IaasRonda::ingresar((int) $sala['id'], $personaId, [
            'n_cama' => $this->texto('n_cama', 10), 'historia_clinica' => $this->texto('historia_clinica', 30), 'fecha_ingreso' => $fechaIngreso,
            'peso_nacer_g' => $neonatal ? $peso : null, 'diagnostico_ingreso' => $this->texto('diagnostico_ingreso', 200),
            'diagnostico_vigilado' => $this->texto('diagnostico_vigilado', 200),
        ], $dispositivos, (int) $this->usuario['id']);
        $this->responder(true, 'Paciente agregado a ' . IaasNorma::nombreSala($sala) . '.', $volver);
    }

    /** Internación del establecimiento por id (POST), o termina con error. */
    private function internacionPost(): array
    {
        $internacion = IaasRonda::internacion((int) ($_POST['internacion_id'] ?? 0), $this->estId());
        if (!$internacion) {
            $this->responder(false, 'El paciente no existe en este establecimiento.', '/iaas/ronda');
        }
        return $internacion;
    }

    private function dispositivoPost(): array
    {
        $dispositivo = IaasRonda::dispositivo((int) ($_POST['dispositivo_id'] ?? 0), $this->estId());
        if (!$dispositivo) {
            $this->responder(false, 'El dispositivo no existe en este establecimiento.', '/iaas/ronda');
        }
        return $dispositivo;
    }

    public function editarInternacion(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $i = $this->internacionPost();
        $volver = $this->volverRonda((int) $i['sala_id']);
        $peso = $this->entero('peso_nacer_g', 300, 7000);
        if (IaasNorma::esNeonatal($i['upss']) && !$peso) {
            $this->responder(false, 'Falta el peso al nacer en gramos (entre 300 y 7000).', $volver);
        }
        IaasRonda::actualizarInternacion((int) $i['id'], [
            'n_cama' => $this->texto('n_cama', 10), 'historia_clinica' => $this->texto('historia_clinica', 30),
            'peso_nacer_g' => IaasNorma::esNeonatal($i['upss']) ? $peso : null,
            'diagnostico_ingreso' => $this->texto('diagnostico_ingreso', 200), 'diagnostico_vigilado' => $this->texto('diagnostico_vigilado', 200),
            'observaciones' => $this->texto('observaciones', 255),
        ]);
        $this->responder(true, 'Datos del paciente actualizados.', $volver);
    }

    public function agregarDispositivo(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $i = $this->internacionPost();
        $volver = $this->volverRonda((int) $i['sala_id'], $this->fechaPasada($_POST['fecha_ronda'] ?? ''));
        $tipo = $this->opcion('tipo', IaasNorma::DISPOSITIVOS_POR_UPSS[$i['upss']]);
        $fecha = $this->fechaPasada($_POST['fecha'] ?? '');
        if (!$tipo || !$fecha || $fecha < $i['fecha_ingreso'] || ($i['fecha_egreso'] && $fecha >= $i['fecha_egreso'])) {
            $this->responder(false, 'Elige el dispositivo y una fecha de instalación dentro de la hospitalización (no futura).', $volver);
        }
        foreach (IaasRonda::dispositivosDe([(int) $i['id']]) as $d) {
            if ($d['tipo'] === $tipo && ($d['fecha_retiro'] === null || $d['fecha_retiro'] > $fecha)) {
                $this->responder(false, 'El paciente ya tiene ' . IaasNorma::DISPOSITIVOS_SIGLA[$tipo] . ' instalado en esa fecha.', $volver);
            }
        }
        IaasRonda::instalar((int) $i['id'], $tipo, $fecha);
        $this->responder(true, IaasNorma::DISPOSITIVOS_SIGLA[$tipo] . ' agregado.', $volver);
    }

    public function retirar(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $d = $this->dispositivoPost();
        $volver = $this->volverRonda((int) $d['sala_id'], $this->fechaPasada($_POST['fecha_ronda'] ?? ''));
        if (!empty($_POST['deshacer'])) {
            IaasRonda::deshacerRetiro((int) $d['id']);
            $this->responder(true, 'Retiro deshecho: ' . IaasNorma::DISPOSITIVOS_SIGLA[$d['tipo']] . ' sigue instalado.', $volver);
        }
        $fecha = $this->fechaPasada($_POST['fecha'] ?? '');
        if (!$fecha || $fecha <= $d['fecha_instalacion']) {
            $this->responder(false, 'La fecha de retiro debe ser posterior a la instalación (' . fechaIsoADmy($d['fecha_instalacion']) . ') y no futura.', $volver);
        }
        $this->exigirMesAbierto((int) $d['sala_id'], $fecha, $volver);
        IaasRonda::retirar((int) $d['id'], $fecha);
        $this->responder(true, IaasNorma::DISPOSITIVOS_SIGLA[$d['tipo']] . ' retirado el ' . fechaIsoADmy($fecha) . '.', $volver);
    }

    public function egresar(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $i = $this->internacionPost();
        $volver = $this->volverRonda((int) $i['sala_id'], $this->fechaPasada($_POST['fecha_ronda'] ?? ''));
        if (!empty($_POST['deshacer'])) {
            IaasRonda::deshacerEgreso((int) $i['id']);
            $this->responder(true, 'Egreso deshecho. Revisa los dispositivos: los que se retiraron con el egreso siguen retirados.', $volver);
        }
        $fecha = $this->fechaPasada($_POST['fecha'] ?? '');
        $condicion = $this->opcion('condicion', array_keys(IaasNorma::CONDICION_EGRESO));
        if (!$fecha || !$condicion || $fecha < $i['fecha_ingreso']) {
            $this->responder(false, 'Indica la fecha de egreso (desde el ingreso, no futura) y la condición: vivo, fallecido o transferido.', $volver);
        }
        $this->exigirMesAbierto((int) $i['sala_id'], $fecha, $volver);
        IaasRonda::egresar((int) $i['id'], $fecha, $condicion);
        $this->responder(true, 'Egreso registrado (' . mb_strtolower(IaasNorma::CONDICION_EGRESO[$condicion]) . '). Sus dispositivos quedaron retirados.', $volver);
    }

    /** "Inicia IAAS": crea la ficha por completar y, si se pide, la abre. */
    public function iniciarIaas(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $d = $this->dispositivoPost();
        $volver = $this->volverRonda((int) $d['sala_id'], $this->fechaPasada($_POST['fecha_ronda'] ?? ''));
        $fecha = $this->fechaPasada($_POST['fecha'] ?? '');
        if (!$fecha || $fecha < $d['fecha_instalacion']) {
            $this->responder(false, 'La fecha de la IAAS debe ser desde la instalación (' . fechaIsoADmy($d['fecha_instalacion']) . ') y no futura.', $volver);
        }
        $internacion = IaasRonda::internacion((int) $d['internacion_id'], $this->estId());
        $casoId = IaasCaso::crearDesdeRonda($internacion, $d, $fecha, $this->estId(), (int) $this->usuario['id']);
        if (!empty($_POST['completar'])) {
            $this->responder(true, 'Ficha IAAS creada. Complétala con los datos de la infección y del cultivo.', '/iaas/casos/' . $casoId);
        }
        $this->responder(true, 'IAAS registrada. La ficha quedó «por completar» en Casos IAAS.', $volver);
    }

    public function finTratamiento(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $caso = IaasCaso::delEstablecimiento((int) ($_POST['caso_id'] ?? 0), $this->estId());
        $volver = $this->volverRonda((int) ($caso['sala_id'] ?? 0), $this->fechaPasada($_POST['fecha_ronda'] ?? ''));
        $fecha = $this->fechaPasada($_POST['fecha'] ?? '');
        if (!$caso || !$fecha || $fecha < $caso['fecha_iaas']) {
            $this->responder(false, 'La fecha de fin del tratamiento debe ser desde la fecha de la IAAS y no futura.', $volver);
        }
        IaasCaso::terminarTratamiento($caso, $fecha);
        $this->responder(true, 'Tratamiento terminado el ' . fechaIsoADmy($fecha) . '. Desde el día siguiente los días vuelven a contar.', $volver);
    }

    public function procedimientos(): void
    {
        $this->exigirConfiguracion();
        $this->exigirCsrf();
        $sala = $this->salaPost('/iaas/ronda');
        $fecha = $this->fechaPasada($_POST['fecha'] ?? '');
        $volver = $this->volverRonda((int) $sala['id'], $fecha);
        $tipos = IaasNorma::PROCEDIMIENTOS_POR_UPSS[$sala['upss']] ?? [];
        if (!$fecha || !$tipos) {
            $this->responder(false, 'Fecha no válida o la sala no registra partos ni cirugías.', $volver);
        }
        $this->exigirMesAbierto((int) $sala['id'], $fecha, $volver);
        $cantidades = [];
        foreach ($tipos as $tipo) {
            $n = trim((string) ($_POST['cantidad'][$tipo] ?? '0'));
            if (!ctype_digit($n) || (int) $n > 500) {
                $this->responder(false, 'Las cantidades deben ser números enteros entre 0 y 500.', $volver);
            }
            $cantidades[$tipo] = (int) $n;
        }
        IaasRonda::guardarProcedimientos((int) $sala['id'], $fecha, $cantidades, (int) $this->usuario['id']);
        $this->responder(true, 'Registro del día guardado.', $volver);
    }

    /** Fecha legible para la ronda: «lunes 26 de octubre». */
    public static function nombreDia(string $fecha): string
    {
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $d = new DateTimeImmutable($fecha);
        return $dias[(int) $d->format('w')] . ' ' . (int) $d->format('j') . ' de ' . IaasNorma::MESES[(int) $d->format('n')];
    }
}
