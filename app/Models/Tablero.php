<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Estadistica;

/**
 * Datos del Panel de vigilancia (rediseño 2026-10-17). Todo respeta el
 * alcance del usuario (Caso::alcance) y un periodo global por semana
 * epidemiológica: SE actual, últimas 4 SE, últimas 12 SE o el año.
 */
class Tablero
{
    public const PERIODOS = ['actual' => 'SE actual', '4' => 'Últimas 4 SE', '12' => 'Últimas 12 SE', 'anio' => 'Año'];

    /**
     * Periodo pedido y el anterior del mismo largo (para comparar). Las SE
     * son [anio, semana].
     */
    public static function periodo(string $clave): array
    {
        $clave = array_key_exists($clave, self::PERIODOS) ? $clave : '4';
        $hoy = new \DateTimeImmutable('today');
        $actual = semanaEpidemiologica($hoy->format('Y-m-d'));
        $hasta = [$actual['anio'], $actual['semana']];
        if ($clave === 'anio') {
            $desde = [$actual['anio'], 1];
            $semanas = semanasEnRango($desde[0], 1, $hasta[0], $hasta[1]);
            $n = count($semanas);
            // El año anterior hasta la misma SE.
            $anterior = [[$actual['anio'] - 1, 1], [$actual['anio'] - 1, min($actual['semana'], semanasEpidemiologicasDelAnio($actual['anio'] - 1))]];
            $etiqueta = 'SE 1–' . $actual['semana'] . ' · ' . $actual['anio'];
            $corta = 'Año ' . $actual['anio'];
        } else {
            $n = $clave === 'actual' ? 1 : (int) $clave;
            $inicio = semanaEpidemiologica($hoy->modify('-' . (7 * ($n - 1)) . ' days')->format('Y-m-d'));
            $desde = [$inicio['anio'], $inicio['semana']];
            $semanas = semanasEnRango($desde[0], $desde[1], $hasta[0], $hasta[1]);
            $antHasta = semanaEpidemiologica($hoy->modify('-' . (7 * $n) . ' days')->format('Y-m-d'));
            $antDesde = semanaEpidemiologica($hoy->modify('-' . (7 * (2 * $n - 1)) . ' days')->format('Y-m-d'));
            $anterior = [[$antDesde['anio'], $antDesde['semana']], [$antHasta['anio'], $antHasta['semana']]];
            $etiqueta = $n === 1
                ? 'SE ' . $actual['semana'] . ' · ' . $actual['anio']
                : 'SE ' . $desde[1] . ($desde[0] !== $hasta[0] ? ' · ' . $desde[0] : '') . '–' . $hasta[1] . ' · ' . $hasta[0];
            $corta = $n === 1 ? 'SE ' . $actual['semana'] : $n . ' SE';
        }

        return [
            'clave'    => $clave,
            'desde'    => $desde,
            'hasta'    => $hasta,
            'semanas'  => $semanas,
            'n'        => $n,
            'anterior' => $anterior,
            'etiqueta' => $etiqueta,
            'corta'    => $corta,
            'actual'   => $actual,
        ];
    }

    /** Condición de rango de SE sobre c.anio_epi/c.semana_epi, con marcadores propios. */
    private static function rango(array $desde, array $hasta, array &$parametros, string $sufijo): string
    {
        $parametros["ad1$sufijo"] = $desde[0];
        $parametros["ad2$sufijo"] = $desde[0];
        $parametros["sd$sufijo"] = $desde[1];
        $parametros["ah1$sufijo"] = $hasta[0];
        $parametros["ah2$sufijo"] = $hasta[0];
        $parametros["sh$sufijo"] = $hasta[1];

        return "((c.anio_epi > :ad1$sufijo OR (c.anio_epi = :ad2$sufijo AND c.semana_epi >= :sd$sufijo))
             AND (c.anio_epi < :ah1$sufijo OR (c.anio_epi = :ah2$sufijo AND c.semana_epi <= :sh$sufijo)))";
    }

    /** @return array{0: string, 1: array} WHERE con el alcance y sin anuladas. */
    private static function base(array $usuario): array
    {
        [$condiciones, $parametros] = Caso::alcance($usuario);
        $condiciones[] = 'c.anulado = 0';
        return [implode(' AND ', $condiciones), $parametros];
    }

    private static function consultar(string $sql, array $parametros): array
    {
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }

    /** KPI del panel: los de siempre, pero sobre el periodo elegido y con su comparación. */
    public static function metricas(array $usuario, array $periodo): array
    {
        [$where, $parametros] = self::base($usuario);
        $actual = self::rango($periodo['desde'], $periodo['hasta'], $parametros, 'a');
        $anterior = self::rango($periodo['anterior'][0], $periodo['anterior'][1], $parametros, 'p');
        $noFinales = "'" . implode("','", Caso::CLASIFICACIONES_NO_FINALES) . "'";
        // Cada rango se evalúa una vez por fila en la subconsulta: con
        // sentencias preparadas reales un marcador no se puede repetir.
        $fila = self::consultar(
            "SELECT COALESCE(SUM(t.en_act), 0) AS fichas, COALESCE(SUM(t.en_ant), 0) AS fichas_ant,
                    COALESCE(SUM(t.en_act AND t.clasificacion = 'CONFIRMADO'), 0) AS confirmados,
                    COALESCE(SUM(t.en_ant AND t.clasificacion = 'CONFIRMADO'), 0) AS confirmados_ant,
                    COALESCE(SUM(t.estado = 'ABIERTA'), 0) AS abiertas,
                    COALESCE(SUM(t.estado = 'ABIERTA' AND t.clasificacion IN ('', $noFinales)), 0) AS sin_final,
                    COALESCE(SUM(t.estado = 'VALIDACION'), 0) AS en_validacion,
                    COUNT(DISTINCT CASE WHEN t.estado = 'VALIDACION' THEN t.establecimiento_id END) AS en_validacion_est
               FROM (SELECT c.estado, c.clasificacion, c.establecimiento_id, $actual AS en_act, $anterior AS en_ant
                       " . Caso::FROM_LISTADO . " WHERE $where) t",
            $parametros
        )[0] ?? [];

        return array_map('intval', $fila);
    }

    /**
     * Casos por enfermedad y semana desde $desde hasta la SE actual, y la
     * historia de años anteriores para el corredor endémico.
     *
     * @return array<int, array> enfermedad_id => [enfermedad, serie[clave "anio-se"] => n, historia[anio][se] => n]
     */
    public static function seriesPorEnfermedad(array $usuario, array $desde, array $hasta): array
    {
        [$where, $parametros] = self::base($usuario);
        $rango = self::rango($desde, $hasta, $parametros, 's');
        $series = [];
        foreach (Enfermedad::todos('nombre') as $enfermedad) {
            if ($enfermedad['activo']) {
                $series[(int) $enfermedad['id']] = ['enfermedad' => $enfermedad, 'serie' => [], 'historia' => []];
            }
        }
        foreach (self::consultar(
            "SELECT c.enfermedad_id, c.anio_epi, c.semana_epi, COUNT(*) AS n " . Caso::FROM_LISTADO
            . " WHERE $where AND $rango GROUP BY c.enfermedad_id, c.anio_epi, c.semana_epi",
            $parametros
        ) as $fila) {
            if (isset($series[(int) $fila['enfermedad_id']])) {
                $series[(int) $fila['enfermedad_id']]['serie'][$fila['anio_epi'] . '-' . $fila['semana_epi']] = (int) $fila['n'];
            }
        }

        [$where, $parametros] = self::base($usuario);
        $parametros['anio_hist'] = $hasta[0];
        foreach (self::consultar(
            "SELECT c.enfermedad_id, c.anio_epi, c.semana_epi, COUNT(*) AS n " . Caso::FROM_LISTADO
            . " WHERE $where AND c.anio_epi < :anio_hist GROUP BY c.enfermedad_id, c.anio_epi, c.semana_epi",
            $parametros
        ) as $fila) {
            if (isset($series[(int) $fila['enfermedad_id']])) {
                $series[(int) $fila['enfermedad_id']]['historia'][(int) $fila['anio_epi']][(int) $fila['semana_epi']] = (int) $fila['n'];
            }
        }

        return $series;
    }

    /**
     * Corredor endémico por SE (cuartiles de los años anteriores con datos;
     * una semana sin casos en un año con datos cuenta como 0). null si no
     * hay $minimoAnios de historia.
     *
     * @return ?array<int, array{q1: float, q2: float, q3: float}> semana => cuartiles
     */
    public static function corredor(array $historia, int $minimoAnios): ?array
    {
        if (count($historia) < $minimoAnios) {
            return null;
        }
        $corredor = [];
        for ($se = 1; $se <= 53; $se++) {
            $valores = [];
            foreach ($historia as $porSemana) {
                $valores[] = $porSemana[$se] ?? 0;
            }
            $corredor[$se] = [
                'q1' => Estadistica::percentil($valores, 25),
                'q2' => Estadistica::percentil($valores, 50),
                'q3' => Estadistica::percentil($valores, 75),
            ];
        }
        return $corredor;
    }

    /** Clasificación de las fichas del periodo: las 4 genéricas siempre, más las que haya. */
    public static function clasificacion(array $usuario, array $periodo): array
    {
        [$where, $parametros] = self::base($usuario);
        $rango = self::rango($periodo['desde'], $periodo['hasta'], $parametros, 'k');
        $totales = array_column(self::consultar(
            "SELECT c.clasificacion, COUNT(*) AS n " . Caso::FROM_LISTADO . " WHERE $where AND $rango GROUP BY c.clasificacion",
            $parametros
        ), 'n', 'clasificacion');
        $resultado = [];
        foreach (array_keys(CATALOGO_CLASIFICACION) as $codigo) {
            if (in_array($codigo, ['SOSPECHOSO', 'PROBABLE', 'CONFIRMADO', 'DESCARTADO'], true) || !empty($totales[$codigo])) {
                $resultado[$codigo] = (int) ($totales[$codigo] ?? 0);
            }
        }
        return $resultado;
    }

    /** Días entre el inicio de síntomas y la notificación, de las fichas del periodo que tienen ambas fechas. */
    public static function diasHastaNotificar(array $usuario, array $periodo): array
    {
        [$where, $parametros] = self::base($usuario);
        $rango = self::rango($periodo['desde'], $periodo['hasta'], $parametros, 'o');
        return array_map('intval', array_column(self::consultar(
            "SELECT GREATEST(0, DATEDIFF(c.fecha_notif, c.fecha_inicio_sintomas)) AS dias " . Caso::FROM_LISTADO
            . " WHERE $where AND $rango AND c.fecha_inicio_sintomas IS NOT NULL ORDER BY dias",
            $parametros
        ), 'dias'));
    }

    /** Las últimas fichas de enfermedades de notificación inmediata. */
    public static function inmediatasRecientes(array $usuario, int $limite = 5): array
    {
        [$where, $parametros] = self::base($usuario);
        return self::consultar(
            "SELECT c.id, c.codigo, c.fecha_notif, c.clasificacion, e.nombre AS enfermedad_nombre, e.cie10,
                    est.nombre AS establecimiento_nombre " . Caso::FROM_LISTADO
            . " WHERE $where AND e.tipo_notif = 'INMEDIATA' ORDER BY c.fecha_notif DESC, c.id DESC LIMIT " . (int) $limite,
            $parametros
        );
    }

    /**
     * Cumplimiento de notificación por establecimiento y SE, según el cierre
     * semanal (NotificacionSemanal): 'T' cerrada a tiempo, 'L' fuera de
     * plazo, 'X' sin cerrar con el plazo vencido (no notificó), 'P' sin
     * cerrar con el plazo vigente. Cada celda trae sus fichas.
     *
     * @return array{semanas: array, filas: array}
     */
    public static function cumplimiento(array $usuario, array $semanas, ?int $redId): array
    {
        $db = Database::conexion();
        if ($usuario['rol'] === 'REGISTRADOR') {
            $consulta = $db->prepare('SELECT es.id, es.nombre, r.nombre AS red_nombre FROM establecimiento es LEFT JOIN red_salud r ON r.id = es.red_id WHERE es.id = :id');
            $consulta->execute(['id' => (int) $usuario['establecimiento_id']]);
        } else {
            $consulta = $db->prepare('SELECT es.id, es.nombre, r.nombre AS red_nombre FROM establecimiento es LEFT JOIN red_salud r ON r.id = es.red_id
                                       WHERE es.activo = 1' . ($redId ? ' AND es.red_id = :red' : '') . ' ORDER BY es.nombre');
            $consulta->execute($redId ? ['red' => $redId] : []);
        }
        $establecimientos = $consulta->fetchAll();
        if (!$establecimientos || !$semanas) {
            return ['semanas' => $semanas, 'filas' => []];
        }
        $primera = $semanas[0];
        $ultima = end($semanas);

        $parametros = [];
        $rango = self::rango([$primera['anio'], $primera['semana']], [$ultima['anio'], $ultima['semana']], $parametros, 'h');
        $fichas = [];
        foreach (self::consultar("SELECT c.establecimiento_id, c.anio_epi, c.semana_epi, COUNT(*) AS n FROM caso c WHERE c.anulado = 0 AND $rango GROUP BY 1, 2, 3", $parametros) as $fila) {
            $fichas[$fila['establecimiento_id'] . '|' . $fila['anio_epi'] . '-' . $fila['semana_epi']] = (int) $fila['n'];
        }
        $cierres = NotificacionSemanal::delRango($usuario['rol'] === 'REGISTRADOR' ? (int) $usuario['establecimiento_id'] : null, [$primera['anio'], $primera['semana']], [$ultima['anio'], $ultima['semana']]);

        $ahora = new \DateTimeImmutable();
        $filas = [];
        foreach ($establecimientos as $establecimiento) {
            $celdas = [];
            $cumplidas = 0;
            $evaluadas = 0;
            foreach ($semanas as $semana) {
                $clave = $establecimiento['id'] . '|' . $semana['anio'] . '-' . $semana['semana'];
                $estado = NotificacionSemanal::estado($cierres[$clave] ?? null, $semana['anio'], $semana['semana'], $ahora);
                if ($estado !== 'P') {
                    $evaluadas++;
                    $cumplidas += $estado === 'X' ? 0 : 1;
                }
                $celdas[] = ['semana' => $semana, 'estado' => $estado, 'fichas' => $fichas[$clave] ?? 0];
            }
            $filas[] = $establecimiento + [
                'celdas'     => $celdas,
                'porcentaje' => $evaluadas ? (int) round($cumplidas / $evaluadas * 100) : null,
            ];
        }
        // De menor a mayor cumplimiento: lo que hay que atender, arriba.
        usort($filas, fn($a, $b) => [$a['porcentaje'] ?? 101, $a['nombre']] <=> [$b['porcentaje'] ?? 101, $b['nombre']]);

        return ['semanas' => $semanas, 'filas' => $filas];
    }
}
