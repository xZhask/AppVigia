<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Models\Caso;
use App\Models\Establecimiento;
use App\Models\RedSalud;
use App\Models\Tablero;

/**
 * Panel de vigilancia (rediseño 2026-10-17): un periodo global por SE para
 * todas las tarjetas, tabla de enfermedades con mini-curvas, curva de UNA
 * enfermedad con su corredor endémico, mapa de cumplimiento de notificación
 * (con la notificación semanal negativa), clasificación, oportunidad e
 * inmediatas recientes. Todo dentro del alcance del usuario.
 */
class PanelController extends Controller
{
    /** Columnas máximas del mapa de cumplimiento (el año completo no cabe legible). */
    private const SEMANAS_MAX_MAPA = 26;
    /** Filas visibles del mapa antes de "Ver los demás". */
    private const FILAS_MAPA = 12;

    public function index(): void
    {
        $usuario = Auth::usuario();
        if (Caso::contarTodos() === 0) {
            $this->vista('panel/index', ['tituloVista' => 'Panel de vigilancia', 'rutaActual' => '', 'totalFichas' => 0]);
            return;
        }

        $periodo = Tablero::periodo(is_string($_GET['periodo'] ?? null) ? $_GET['periodo'] : '4');
        $actual = $periodo['actual'];
        $config = require __DIR__ . '/../../config/config.php';
        $minimoAnios = (int) ($config['reportes']['anios_minimos_corredor'] ?? 2);

        // Semanas de las series: desde lo más antiguo entre la SE 1 del año,
        // las últimas 12 SE (mini-curvas) y el inicio del periodo.
        $hace11 = semanaEpidemiologica(date('Y-m-d', strtotime('-77 days')));
        $inicios = [[$actual['anio'], 1], [$hace11['anio'], $hace11['semana']], $periodo['desde']];
        usort($inicios, fn($a, $b) => $a <=> $b);
        $desdeSeries = $inicios[0];
        $semanasSeries = semanasEnRango($desdeSeries[0], $desdeSeries[1], $actual['anio'], $actual['semana']);
        $series = Tablero::seriesPorEnfermedad($usuario, $desdeSeries, $periodo['hasta']);

        $clavesPeriodo = array_map(fn($s) => $s['anio'] . '-' . $s['semana'], $periodo['semanas']);
        $claves12 = array_slice(array_map(fn($s) => $s['anio'] . '-' . $s['semana'], $semanasSeries), -12);
        $enfermedades = [];
        foreach ($series as $id => $serie) {
            $corredor = Tablero::corredor($serie['historia'], $minimoAnios);
            $enPeriodo = 0;
            $sobre = 0;
            foreach ($periodo['semanas'] as $semana) {
                $n = $serie['serie'][$semana['anio'] . '-' . $semana['semana']] ?? 0;
                $enPeriodo += $n;
                if ($corredor && $n > 0 && $n > $corredor[$semana['semana']]['q3']) {
                    $sobre++;
                }
            }
            $anio = 0;
            foreach ($serie['serie'] as $clave => $n) {
                if (str_starts_with($clave, $actual['anio'] . '-')) {
                    $anio += $n;
                }
            }
            $enfermedades[] = [
                'id'        => $id,
                'nombre'    => $serie['enfermedad']['nombre'],
                'cie10'     => $serie['enfermedad']['cie10'],
                'inmediata' => $serie['enfermedad']['tipo_notif'] === 'INMEDIATA',
                'mini'      => array_map(fn($c) => ['clave' => $c, 'n' => $serie['serie'][$c] ?? 0,
                    'sobre' => $corredor && ($serie['serie'][$c] ?? 0) > $corredor[(int) explode('-', $c)[1]]['q3']], $claves12),
                'periodo'   => $enPeriodo,
                'anio'      => $anio,
                'situacion' => $corredor === null ? 'sin_historia' : ($sobre ? 'sobre' : 'esperado'),
            ];
        }
        // Lo activo arriba: casos del periodo, luego del año, luego el nombre.
        usort($enfermedades, fn($a, $b) => [-$a['periodo'], -$a['anio'], $a['nombre']] <=> [-$b['periodo'], -$b['anio'], $b['nombre']]);

        $idElegida = (int) ($_GET['enfermedad'] ?? 0);
        if (!isset($series[$idElegida])) {
            $idElegida = (int) $enfermedades[0]['id'];
        }
        $curva = $this->curva($series[$idElegida], $semanasSeries, $periodo, $actual, $minimoAnios);

        // Mapa de cumplimiento: las semanas del periodo (al menos 8, a lo más 26).
        $semanasMapa = array_slice($semanasSeries, -max(8, min(self::SEMANAS_MAX_MAPA, $periodo['n'])));
        $redId = $usuario['rol'] !== 'REGISTRADOR' && ctype_digit((string) ($_GET['red'] ?? '')) ? (int) $_GET['red'] : null;
        $cumplimiento = Tablero::cumplimiento($usuario, $semanasMapa, $redId);
        $notificaronActual = 0;
        foreach ($cumplimiento['filas'] as $fila) {
            $notificaronActual += in_array(end($fila['celdas'])['estado'], ['C', 'N'], true) ? 1 : 0;
        }

        $this->vista('panel/index', [
            'tituloVista'   => 'Panel de vigilancia',
            'rutaActual'    => '',
            'vistaAncha'    => true,
            'totalFichas'   => 1,
            'periodo'       => $periodo,
            'metricas'      => Tablero::metricas($usuario, $periodo),
            'enfermedades'  => $enfermedades,
            'idElegida'     => $idElegida,
            'curva'         => $curva,
            'cumplimiento'  => $cumplimiento,
            'notificaronActual' => $notificaronActual,
            'filasMapa'     => self::FILAS_MAPA,
            'redes'         => $usuario['rol'] === 'REGISTRADOR' ? [] : RedSalud::todos('nombre'),
            'redId'         => $redId,
            'clasificacion' => Tablero::clasificacion($usuario, $periodo),
            'dias'          => Tablero::diasHastaNotificar($usuario, $periodo),
            'inmediatas'    => Tablero::inmediatasRecientes($usuario),
            'esRegistrador' => $usuario['rol'] === 'REGISTRADOR',
        ]);
    }

    /**
     * Curva de una enfermedad: casos por SE con el corredor endémico (zonas
     * de éxito, seguridad, alarma y epidemia por cuartiles de los años
     * anteriores) o, sin historia suficiente, sin bandas y con una nota.
     */
    private function curva(array $serie, array $semanas, array $periodo, array $actual, int $minimoAnios): array
    {
        $corredor = Tablero::corredor($serie['historia'], $minimoAnios);
        $clavesPeriodo = array_map(fn($s) => $s['anio'] . '-' . $s['semana'], $periodo['semanas']);
        $puntos = [];
        foreach ($semanas as $semana) {
            $clave = $semana['anio'] . '-' . $semana['semana'];
            $n = $serie['serie'][$clave] ?? 0;
            $zona = $corredor[$semana['semana']] ?? null;
            $puntos[] = [
                'semana'   => $semana,
                'n'        => $n,
                'zona'     => $zona,
                'sobre'    => $zona && $n > 0 && $n > $zona['q3'],
                'periodo'  => in_array($clave, $clavesPeriodo, true),
            ];
        }
        return [
            'enfermedad' => $serie['enfermedad'],
            'puntos'     => $puntos,
            'conCorredor' => $corredor !== null,
            'aniosHistoria' => count($serie['historia']),
            'minimoAnios' => $minimoAnios,
        ];
    }

    /**
     * Notificación semanal negativa (2026-10-17): "esta SE el establecimiento
     * no tuvo casos". Registrar o anular, desde el mapa de cumplimiento.
     */
    public function notificacionSemanal(): void
    {
        $usuario = Auth::usuario();
        $volver = '/' . (is_string($_POST['volver'] ?? null) && str_starts_with($_POST['volver'], '?') ? $_POST['volver'] : '');
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: ' . $volver);
            exit;
        }
        $establecimientoId = (int) ($_POST['establecimiento_id'] ?? 0);
        $anio = (int) ($_POST['anio'] ?? 0);
        $semana = (int) ($_POST['semana'] ?? 0);
        $accion = $_POST['accion'] ?? '';
        $actual = semanaEpidemiologica(date('Y-m-d'));
        $establecimiento = Establecimiento::buscar($establecimientoId);

        $error = null;
        if (!$establecimiento || $semana < 1 || $semana > semanasEpidemiologicasDelAnio(max(2000, $anio)) || $anio < 2000) {
            $error = 'Datos de la notificación no válidos.';
        } elseif ($usuario['rol'] === 'REGISTRADOR' && $establecimientoId !== (int) $usuario['establecimiento_id']) {
            $error = 'Solo puedes notificar por tu establecimiento.';
        } elseif ([$anio, $semana] > [$actual['anio'], $actual['semana']]) {
            $error = 'No se puede notificar una semana que todavía no empieza.';
        }

        $db = Database::conexion();
        $etiqueta = 'SE ' . $semana . ' · ' . $anio . ' de ' . nombreEnOracion($establecimiento['nombre'] ?? '');
        if ($error === null && $accion === 'registrar') {
            $consulta = $db->prepare('SELECT COUNT(*) FROM caso WHERE anulado = 0 AND establecimiento_id = :e AND anio_epi = :a AND semana_epi = :s');
            $consulta->execute(['e' => $establecimientoId, 'a' => $anio, 's' => $semana]);
            if ((int) $consulta->fetchColumn() > 0) {
                $error = 'La ' . $etiqueta . ' ya tiene fichas: no se puede notificar como semana sin casos.';
            } else {
                $db->prepare('INSERT INTO notificacion_semanal (establecimiento_id, anio_epi, semana_epi, usuario_id)
                              VALUES (:e, :a, :s, :u)
                              ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id), creado_en = CURRENT_TIMESTAMP, anulada_por = NULL, anulada_en = NULL')
                   ->execute(['e' => $establecimientoId, 'a' => $anio, 's' => $semana, 'u' => $usuario['id']]);
                Flash::set('Notificada la ' . $etiqueta . ' sin casos.');
            }
        } elseif ($error === null && $accion === 'anular') {
            $db->prepare('UPDATE notificacion_semanal SET anulada_por = :u, anulada_en = CURRENT_TIMESTAMP
                           WHERE establecimiento_id = :e AND anio_epi = :a AND semana_epi = :s AND anulada_en IS NULL')
               ->execute(['u' => $usuario['id'], 'e' => $establecimientoId, 'a' => $anio, 's' => $semana]);
            Flash::set('Anulada la notificación sin casos de la ' . $etiqueta . '.');
        } elseif ($error === null) {
            $error = 'Acción no válida.';
        }
        if ($error !== null) {
            Flash::set($error);
        }
        header('Location: ' . $volver . '#cumplimiento');
        exit;
    }
}
