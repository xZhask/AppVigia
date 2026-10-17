<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\MailService;
use App\Models\Enfermedad;
use App\Models\Establecimiento;
use App\Models\NotificacionSemanal;
use App\Models\RedSalud;
use DateTimeImmutable;

/**
 * Notificación semanal (2026-10-17). El REGISTRADOR cierra cada SE de su
 * establecimiento, tenga casos o no (las enfermedades sin fichas quedan
 * notificadas sin casos); el ADMIN sigue el cumplimiento nacional, recuerda
 * a los pendientes y reabre semanas con motivo. Ver NotificacionSemanal.
 */
class NotificacionSemanalController extends Controller
{
    /** Semanas del historial del registrador y de la mini-serie del ADMIN. */
    private const SEMANAS_HISTORIAL = 12;

    public function index(): void
    {
        $usuario = Auth::usuario();
        $ahora = new DateTimeImmutable();
        $actual = semanaEpidemiologica($ahora->format('Y-m-d'));
        [$anio, $semana] = $this->semanaPedida($_GET, $actual);
        $historial = $this->semanasHasta($actual, self::SEMANAS_HISTORIAL);
        $datos = [
            'tituloVista' => 'Notificación semanal',
            'rutaActual'  => 'notificacion-semanal',
            'vistaAncha'  => true,
            'anio'        => $anio,
            'semana'      => $semana,
            'actual'      => $actual,
            'enCurso'     => [$anio, $semana] === [$actual['anio'], $actual['semana']],
            'plazo'       => NotificacionSemanal::plazo($anio, $semana),
            'ahora'       => $ahora,
            'primeraSe'   => $historial[0],
        ];

        if ($usuario['rol'] === 'REGISTRADOR') {
            $this->vistaRegistrador($usuario, $datos, $historial);
        } else {
            $this->vistaAdmin($datos);
        }
    }

    private function vistaRegistrador(array $usuario, array $datos, array $historial): void
    {
        $establecimientoId = (int) $usuario['establecimiento_id'];
        $establecimiento = $this->establecimientoConRed($establecimientoId);
        ['anio' => $anio, 'semana' => $semana] = $datos;
        $cierre = NotificacionSemanal::deSemana($establecimientoId, $anio, $semana);
        $fichas = NotificacionSemanal::fichasPorEnfermedad($establecimientoId, $anio, $semana);
        $resumen = NotificacionSemanal::fichasDeSemana($anio, $semana, $establecimientoId)[$establecimientoId] ?? ['fichas' => 0, 'tardias' => 0];

        $cierres = NotificacionSemanal::delRango($establecimientoId, [$historial[0]['anio'], $historial[0]['semana']], [end($historial)['anio'], end($historial)['semana']]);
        $semanas = [];
        foreach ($historial as $s) {
            $semanas[] = $s + ['estado' => NotificacionSemanal::estado($cierres[$establecimientoId . '|' . $s['anio'] . '-' . $s['semana']] ?? null, $s['anio'], $s['semana'], $datos['ahora'])];
        }

        $this->vista('notificacion/registrador', $datos + [
            'establecimiento' => $establecimiento,
            'cierre'          => $cierre,
            'estado'          => NotificacionSemanal::estado($cierre, $anio, $semana, $datos['ahora']),
            // Las de notificación mensual (B24) no se cierran por semana: solo
            // aparecen si tienen fichas en esta SE.
            'enfermedades'    => array_values(array_filter(Enfermedad::activas(), fn($en) => $en['tipo_notif'] !== 'MENSUAL' || !empty($fichas[$en['id']]))),
            'fichas'          => $fichas,
            'tardias'         => $resumen['tardias'],
            'historial'       => $semanas,
        ]);
    }

    private function vistaAdmin(array $datos): void
    {
        ['anio' => $anio, 'semana' => $semana, 'ahora' => $ahora] = $datos;
        $texto = fn(string $clave): string => is_string($_GET[$clave] ?? null) ? trim($_GET[$clave]) : '';
        $redId = ctype_digit($texto('red')) ? (int) $texto('red') : null;
        $filtroEstado = in_array($texto('estado'), ['T', 'L', 'P'], true) ? $texto('estado') : '';
        $busqueda = $texto('q');

        // Mini-serie: las 11 SE anteriores a la elegida.
        $previas = array_slice($this->semanasHasta(['anio' => $anio, 'semana' => $semana], self::SEMANAS_HISTORIAL), 0, -1);
        $cierres = NotificacionSemanal::delRango(null, [$previas[0]['anio'], $previas[0]['semana']], [$anio, $semana]);
        $fichas = NotificacionSemanal::fichasDeSemana($anio, $semana);
        $registradores = NotificacionSemanal::registradores();

        $filas = [];
        $conteo = ['T' => 0, 'L' => 0, 'P' => 0];
        foreach (NotificacionSemanal::establecimientos($redId) as $establecimiento) {
            $id = (int) $establecimiento['id'];
            $cierre = $cierres[$id . '|' . $anio . '-' . $semana] ?? null;
            $estado = NotificacionSemanal::estado($cierre, $anio, $semana, $ahora);
            $conteo[$estado === 'X' ? 'P' : $estado]++;
            $filas[] = $establecimiento + [
                'estado'        => $estado,
                'cierre'        => $cierre && $cierre['anulada_en'] === null ? $cierre : null,
                'reabierta'     => $cierre && $cierre['anulada_en'] !== null ? $cierre : null,
                'solicitud'     => $cierre && $cierre['anulada_en'] === null && $cierre['reapertura_solicitada_en'] !== null,
                'fichas'        => $fichas[$id]['fichas'] ?? 0,
                'tardias'       => $fichas[$id]['tardias'] ?? 0,
                'registradores' => array_column($registradores[$id] ?? [], 'nombre'),
                'serie'         => array_map(fn($s) => $s + ['estado' => NotificacionSemanal::estado($cierres[$id . '|' . $s['anio'] . '-' . $s['semana']] ?? null, $s['anio'], $s['semana'], $ahora)], $previas),
            ];
        }
        $total = count($filas);
        $visibles = array_values(array_filter($filas, function (array $fila) use ($filtroEstado, $busqueda): bool {
            if ($filtroEstado !== '' && ($fila['estado'] === 'X' ? 'P' : $fila['estado']) !== $filtroEstado) {
                return false;
            }
            return $busqueda === '' || mb_stripos($fila['nombre'], $busqueda) !== false || mb_stripos((string) $fila['red_nombre'], $busqueda) !== false;
        }));
        // Lo que hay que atender, arriba: solicitudes, pendientes, tardías, a tiempo.
        $orden = ['X' => 1, 'P' => 1, 'L' => 2, 'T' => 3];
        usort($visibles, fn($a, $b) => [$a['solicitud'] ? 0 : $orden[$a['estado']], $a['nombre']] <=> [$b['solicitud'] ? 0 : $orden[$b['estado']], $b['nombre']]);

        $this->vista('notificacion/admin', $datos + [
            'filas'        => $visibles,
            'total'        => $total,
            'conteo'       => $conteo,
            'previas'      => $previas,
            'redes'        => RedSalud::todos('nombre'),
            'redId'        => $redId,
            'filtroEstado' => $filtroEstado,
            'busqueda'     => $busqueda,
            'solicitudes'  => count(array_filter($filas, fn($f) => $f['solicitud'])),
        ]);
    }

    /** POST: el registrador cierra una SE de su establecimiento. */
    public function cerrar(): void
    {
        $usuario = Auth::usuario();
        [$anio, $semana, $volver] = $this->entradaPost();
        Auth::exigirRol('REGISTRADOR');
        $establecimientoId = (int) $usuario['establecimiento_id'];
        $plazo = NotificacionSemanal::plazo($anio, $semana);
        $tardia = new DateTimeImmutable() > $plazo;
        $motivo = trim((string) ($_POST['motivo_tardio'] ?? ''));
        $cierre = NotificacionSemanal::deSemana($establecimientoId, $anio, $semana);

        if ($cierre && $cierre['anulada_en'] === null) {
            Flash::set("La SE $semana ya estaba cerrada.");
        } elseif (($_POST['confirmo'] ?? '') !== '1') {
            Flash::set('Marca la confirmación de que no hay más casos por notificar.');
        } elseif ($tardia && $motivo === '') {
            Flash::set('El plazo venció: indica el motivo del cierre tardío.');
        } else {
            NotificacionSemanal::cerrar($establecimientoId, $anio, $semana, (int) $usuario['id'], $tardia, $tardia ? mb_substr($motivo, 0, 255) : null);
            Flash::set("SE $semana cerrada" . ($tardia ? ' fuera de plazo.' : '.'));
        }
        header('Location: ' . $volver);
        exit;
    }

    /** POST: el registrador pide reabrir una SE cerrada; avisa a los ADMIN por correo si está configurado. */
    public function solicitarReapertura(): void
    {
        $usuario = Auth::usuario();
        [$anio, $semana, $volver] = $this->entradaPost();
        Auth::exigirRol('REGISTRADOR');
        $motivo = trim((string) ($_POST['motivo'] ?? ''));
        $establecimientoId = (int) $usuario['establecimiento_id'];

        if ($motivo === '') {
            Flash::set('Indica el motivo de la solicitud.');
        } elseif (!NotificacionSemanal::solicitarReapertura($establecimientoId, $anio, $semana, (int) $usuario['id'], mb_substr($motivo, 0, 255))) {
            Flash::set("La SE $semana no está cerrada: no hace falta reabrirla.");
        } else {
            $establecimiento = Establecimiento::buscar($establecimientoId);
            $admins = Database::conexion()->query("SELECT email FROM usuario WHERE rol = 'ADMIN' AND activo = 1")->fetchAll();
            foreach ($admins as $admin) {
                MailService::enviar($admin['email'], "VIGÍA: solicitud de reapertura de la SE $semana",
                    "{$usuario['nombre']} (" . ($establecimiento['nombre'] ?? '') . ") solicita reabrir la SE $semana · $anio.\n\nMotivo: $motivo\n\nRevísala en Notificación semanal.");
            }
            Flash::set('Solicitud enviada. La semana sigue cerrada hasta que un administrador la reabra.');
        }
        header('Location: ' . $volver);
        exit;
    }

    /** POST (ADMIN): reabre la SE de un establecimiento, con motivo. */
    public function reabrir(): void
    {
        $usuario = Auth::usuario();
        [$anio, $semana, $volver] = $this->entradaPost();
        Auth::exigirRol('ADMIN');
        $motivo = trim((string) ($_POST['motivo'] ?? ''));
        $establecimiento = Establecimiento::buscar((int) ($_POST['establecimiento_id'] ?? 0));

        if (!$establecimiento) {
            Flash::set('Establecimiento no válido.');
        } elseif ($motivo === '') {
            Flash::set('Indica el motivo de la reapertura.');
        } elseif (!NotificacionSemanal::reabrir((int) $establecimiento['id'], $anio, $semana, (int) $usuario['id'], mb_substr($motivo, 0, 255))) {
            Flash::set('Esa semana no estaba cerrada.');
        } else {
            Flash::set('SE ' . $semana . ' de ' . nombreEnOracion($establecimiento['nombre']) . ' reabierta.');
        }
        header('Location: ' . $volver);
        exit;
    }

    /**
     * POST (ADMIN): recordatorio por correo a los registradores de un
     * establecimiento, o de todos los que no cerraron la SE.
     */
    public function recordar(): void
    {
        [$anio, $semana, $volver] = $this->entradaPost();
        Auth::exigirRol('ADMIN');
        $soloId = (int) ($_POST['establecimiento_id'] ?? 0);
        $cierres = NotificacionSemanal::delRango($soloId ?: null, [$anio, $semana], [$anio, $semana]);
        $registradores = NotificacionSemanal::registradores();
        $plazo = NotificacionSemanal::plazo($anio, $semana);

        $establecimientos = 0;
        $sinCorreo = 0;
        $enviados = 0;
        foreach (NotificacionSemanal::establecimientos() as $establecimiento) {
            $id = (int) $establecimiento['id'];
            if (($soloId && $id !== $soloId) || in_array(NotificacionSemanal::estado($cierres[$id . '|' . $anio . '-' . $semana] ?? null, $anio, $semana), ['T', 'L'], true)) {
                continue;
            }
            $establecimientos++;
            if (empty($registradores[$id])) {
                $sinCorreo++;
                continue;
            }
            foreach ($registradores[$id] as $registrador) {
                $enviados += MailService::enviar($registrador['email'], "VIGÍA: falta cerrar la SE $semana",
                    "Hola, {$registrador['nombre']}:\n\n" . nombreEnOracion($establecimiento['nombre']) . " todavía no cerró la SE $semana · $anio"
                    . ' (plazo: ' . $plazo->format('d/m/Y H:i') . ").\n\nCerrar la semana es obligatorio aunque no haya casos: entra a VIGÍA, Notificación semanal.") ? 1 : 0;
            }
        }

        if ($establecimientos === 0) {
            Flash::set('No hay establecimientos pendientes en esa semana.');
        } elseif ($enviados === 0) {
            Flash::set('No se envió ningún recordatorio: ' . ($sinCorreo === $establecimientos ? 'los establecimientos pendientes no tienen registradores activos.' : 'el correo no está configurado (config: mail.desde).'));
        } else {
            Flash::set("Recordatorio enviado a $enviados registrador" . ($enviados === 1 ? '' : 'es') . ($sinCorreo ? " · $sinCorreo establecimiento(s) sin registrador activo." : '.'));
        }
        header('Location: ' . $volver);
        exit;
    }

    /** CSRF, SE válida y no futura, y la URL de vuelta (siempre esta pantalla). */
    private function entradaPost(): array
    {
        $anio = (int) ($_POST['anio'] ?? 0);
        $semana = (int) ($_POST['semana'] ?? 0);
        $volver = '/notificacion-semanal';
        if (is_string($_POST['volver'] ?? null) && str_starts_with($_POST['volver'], '?')) {
            $volver .= $_POST['volver'];
        }
        $actual = semanaEpidemiologica(date('Y-m-d'));
        $error = null;
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            $error = 'La sesión del formulario expiró. Vuelve a intentarlo.';
        } elseif ($anio < 2000 || $semana < 1 || $semana > semanasEpidemiologicasDelAnio($anio)) {
            $error = 'Semana no válida.';
        } elseif ([$anio, $semana] > [$actual['anio'], $actual['semana']]) {
            $error = 'Esa semana todavía no empieza.';
        }
        if ($error !== null) {
            Flash::set($error);
            header('Location: ' . $volver);
            exit;
        }

        return [$anio, $semana, $volver];
    }

    /** SE de ?anio=&semana= (no futura); si no viene, la que toca cerrar. */
    private function semanaPedida(array $get, array $actual): array
    {
        $anio = (int) ($get['anio'] ?? 0);
        $semana = (int) ($get['semana'] ?? 0);
        if ($anio < 2000 || $semana < 1 || $semana > semanasEpidemiologicasDelAnio(max(2000, $anio)) || [$anio, $semana] > [$actual['anio'], $actual['semana']]) {
            $objetivo = NotificacionSemanal::semanaObjetivo();
            return [$objetivo['anio'], $objetivo['semana']];
        }

        return [$anio, $semana];
    }

    /** Las $n SE que terminan en $hasta (incluida). */
    private function semanasHasta(array $hasta, int $n): array
    {
        $desde = semanaEpidemiologica(inicioSemanaEpidemiologica($hasta['anio'], $hasta['semana'])->modify('-' . (7 * ($n - 1)) . ' days')->format('Y-m-d'));

        return semanasEnRango($desde['anio'], $desde['semana'], $hasta['anio'], $hasta['semana']);
    }

    private function establecimientoConRed(int $id): array
    {
        $consulta = Database::conexion()->prepare('SELECT es.*, r.nombre AS red_nombre FROM establecimiento es LEFT JOIN red_salud r ON r.id = es.red_id WHERE es.id = :id');
        $consulta->execute(['id' => $id]);

        return $consulta->fetch() ?: ['id' => $id, 'nombre' => '', 'red_nombre' => null];
    }
}
