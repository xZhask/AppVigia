<?php
namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;

/**
 * Indicadores de los catálogos rediseñados (2026-10-19): Establecimientos
 * (registradores, cumplimiento de cierre, última notificación), Usuarios
 * (último acceso, fichas, actividad) y Enfermedades (casos, corredor,
 * secciones de la ficha). Todo es de lectura y de alcance nacional: estos
 * catálogos son solo del ADMIN.
 */
class ResumenCatalogo
{
    /** Semanas de cumplimiento que se muestran por establecimiento. */
    public const SEMANAS = 12;
    /** Días sin acceso a partir de los cuales se marca a un usuario. */
    public const DIAS_SIN_ACCESO = 90;

    /** Las últimas SE hasta la actual (incluida). */
    public static function semanas(int $n = self::SEMANAS): array
    {
        $hoy = new DateTimeImmutable('today');
        $desde = semanaEpidemiologica($hoy->modify('-' . (7 * ($n - 1)) . ' days')->format('Y-m-d'));
        $hasta = semanaEpidemiologica($hoy->format('Y-m-d'));

        return semanasEnRango($desde['anio'], $desde['semana'], $hasta['anio'], $hasta['semana']);
    }

    /**
     * Cumplimiento del cierre semanal por establecimiento: estado de cada SE
     * (NotificacionSemanal::estado) y % de semanas cerradas (a tiempo o
     * tarde) entre las ya vencidas.
     *
     * @return array<int, array{serie: array, porcentaje: int|null}>
     */
    public static function cumplimiento(array $establecimientoIds, array $semanas): array
    {
        $primera = $semanas[0];
        $ultima = end($semanas);
        $cierres = NotificacionSemanal::delRango(null, [$primera['anio'], $primera['semana']], [$ultima['anio'], $ultima['semana']]);
        $ahora = new DateTimeImmutable();
        $resultado = [];
        foreach ($establecimientoIds as $id) {
            $serie = [];
            $evaluadas = 0;
            $cerradas = 0;
            foreach ($semanas as $s) {
                $estado = NotificacionSemanal::estado($cierres[$id . '|' . $s['anio'] . '-' . $s['semana']] ?? null, $s['anio'], $s['semana'], $ahora);
                $serie[] = $s + ['estado' => $estado];
                if ($estado !== 'P') {
                    $evaluadas++;
                    $cerradas += in_array($estado, ['T', 'L'], true) ? 1 : 0;
                }
            }
            $resultado[$id] = ['serie' => $serie, 'porcentaje' => $evaluadas ? (int) round($cerradas / $evaluadas * 100) : null];
        }

        return $resultado;
    }

    /** Fecha de notificación más reciente (fichas no anuladas) por establecimiento. */
    public static function ultimaNotificacion(): array
    {
        return array_column(Database::conexion()->query(
            'SELECT establecimiento_id, MAX(fecha_notif) AS ultima FROM caso WHERE anulado = 0 GROUP BY establecimiento_id'
        )->fetchAll(), 'ultima', 'establecimiento_id');
    }

    /** Usuarios (activos e inactivos) agrupados por establecimiento, con su último acceso. */
    public static function usuariosPorEstablecimiento(): array
    {
        $resultado = [];
        foreach (Usuario::conEstablecimiento() as $u) {
            if ($u['establecimiento_id'] !== null) {
                $resultado[(int) $u['establecimiento_id']][] = $u;
            }
        }

        return $resultado;
    }

    /** Fichas no anuladas registradas por cada usuario en un año epidemiológico. */
    public static function fichasPorUsuario(int $anio): array
    {
        $consulta = Database::conexion()->prepare('SELECT usuario_id, COUNT(*) AS n FROM caso WHERE anulado = 0 AND anio_epi = :a GROUP BY usuario_id');
        $consulta->execute(['a' => $anio]);

        return array_map('intval', array_column($consulta->fetchAll(), 'n', 'usuario_id'));
    }

    /**
     * Actividad reciente por usuario: movimientos de fichas (caso_bitacora) y
     * cierres semanales, los $limite más recientes.
     *
     * @return array<int, array<int, array{texto: string, fecha: string}>>
     */
    public static function actividad(int $limite = 6): array
    {
        $acciones = ['CREACION' => 'Registró la ficha', 'EDICION' => 'Editó la ficha', 'ACTUALIZACION' => 'Editó la ficha', 'ANULACION' => 'Anuló la ficha', 'CAMBIO_ESTADO' => 'Cambió el estado de la ficha'];
        $eventos = [];
        foreach (Database::conexion()->query(
            'SELECT b.usuario_id, b.accion, b.fecha, c.codigo, e.nombre_corto, e.nombre
               FROM caso_bitacora b JOIN caso c ON c.id = b.caso_id JOIN enfermedad e ON e.id = c.enfermedad_id
              WHERE b.usuario_id IS NOT NULL ORDER BY b.fecha DESC LIMIT 2000'
        )->fetchAll() as $fila) {
            $verbo = $acciones[$fila['accion']] ?? ucfirst(strtolower(str_replace('_', ' ', $fila['accion']))) . ' ·';
            $eventos[(int) $fila['usuario_id']][] = ['texto' => $verbo . ' ' . $fila['codigo'] . ' (' . ($fila['nombre_corto'] ?: $fila['nombre']) . ')', 'fecha' => $fila['fecha']];
        }
        foreach (Database::conexion()->query(
            'SELECT usuario_id, anio_epi, semana_epi, creado_en, tardia FROM notificacion_semanal WHERE anulada_en IS NULL ORDER BY creado_en DESC LIMIT 2000'
        )->fetchAll() as $fila) {
            $eventos[(int) $fila['usuario_id']][] = ['texto' => 'Cerró la SE ' . $fila['semana_epi'] . ' · ' . $fila['anio_epi'] . ($fila['tardia'] ? ' (fuera de plazo)' : ''), 'fecha' => $fila['creado_en']];
        }
        foreach ($eventos as &$lista) {
            usort($lista, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));
            $lista = array_slice($lista, 0, $limite);
        }

        return $eventos;
    }

    /**
     * Casos por enfermedad: del año en curso por SE y años anteriores con
     * datos (los que alimentan el corredor endémico del Panel).
     *
     * @return array<int, array{anio: int, se: int, serie: array<int,int>, por_semana: array<string,int>, anios_historia: int[]}>
     */
    public static function casosPorEnfermedad(array $actual): array
    {
        $resultado = [];
        $consulta = Database::conexion()->prepare(
            'SELECT enfermedad_id, anio_epi, semana_epi, COUNT(*) AS n FROM caso
              WHERE anulado = 0 AND anio_epi <= :a GROUP BY enfermedad_id, anio_epi, semana_epi'
        );
        $consulta->execute(['a' => $actual['anio']]);
        foreach ($consulta->fetchAll() as $fila) {
            $id = (int) $fila['enfermedad_id'];
            $resultado[$id] ??= ['anio' => 0, 'se' => 0, 'serie' => [], 'por_semana' => [], 'anios_historia' => []];
            $resultado[$id]['por_semana'][$fila['anio_epi'] . '-' . $fila['semana_epi']] = (int) $fila['n'];
            if ((int) $fila['anio_epi'] === $actual['anio']) {
                $resultado[$id]['anio'] += (int) $fila['n'];
                $resultado[$id]['serie'][(int) $fila['semana_epi']] = (int) $fila['n'];
                if ((int) $fila['semana_epi'] === $actual['semana']) {
                    $resultado[$id]['se'] = (int) $fila['n'];
                }
            } else {
                $resultado[$id]['anios_historia'][(int) $fila['anio_epi']] = (int) $fila['anio_epi'];
            }
        }
        foreach ($resultado as &$r) {
            $r['anios_historia'] = array_values($r['anios_historia']);
            sort($r['anios_historia']);
        }

        return $resultado;
    }

    /** Secciones de la ficha de cada enfermedad con su número de campos. */
    public static function seccionesPorEnfermedad(): array
    {
        $resultado = [];
        foreach (Database::conexion()->query(
            'SELECT s.enfermedad_id, s.nombre, COUNT(c.id) AS campos
               FROM seccion_def s LEFT JOIN campo_def c ON c.seccion_id = s.id
           GROUP BY s.id ORDER BY s.enfermedad_id, s.orden, s.id'
        )->fetchAll() as $fila) {
            $resultado[(int) $fila['enfermedad_id']][] = ['nombre' => $fila['nombre'], 'campos' => (int) $fila['campos']];
        }

        return $resultado;
    }

    /** "hoy, 08:12", "ayer", "hace 5 días", "12/03/2026" o "Nunca ingresó". */
    public static function textoAcceso(?string $fecha): string
    {
        if (!$fecha) {
            return 'Nunca ingresó';
        }
        $momento = new DateTimeImmutable($fecha);
        $dias = (int) (new DateTimeImmutable('today'))->diff($momento->setTime(0, 0))->days;
        return match (true) {
            $dias === 0 => 'hoy, ' . $momento->format('H:i'),
            $dias === 1 => 'ayer, ' . $momento->format('H:i'),
            $dias < 30  => 'hace ' . $dias . ' días',
            default     => $momento->format('d/m/Y'),
        };
    }

    /** Días desde el último acceso; null si nunca ingresó. */
    public static function diasSinAcceso(?string $fecha): ?int
    {
        return $fecha ? (int) (new DateTimeImmutable('today'))->diff((new DateTimeImmutable($fecha))->setTime(0, 0))->days : null;
    }
}
