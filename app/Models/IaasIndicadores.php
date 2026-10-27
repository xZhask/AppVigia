<?php
namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;

/**
 * Indicadores de la vigilancia de IAAS (Anexo 10), el personal de salud por
 * año (Anexo 9) y la revisión de calidad antes de cerrar el mes.
 *
 *   Densidad de incidencia (× 1000): fichas con fecha de IAAS en el mes /
 *   días-dispositivo del mes (marcas ✓; las × no suman).
 *   Incidencia acumulada (× 100): ISQ y endometritis se cuentan en el mes del
 *   procedimiento, no en el de la infección (Anexo 3, notas de 4.2 y 5), sobre
 *   los partos y cirugías registrados ese mes; exposición laboral sobre el
 *   personal del año.
 */
class IaasIndicadores
{
    private static function pdo(): \PDO
    {
        return Database::conexion();
    }

    /** @return array{0: string, 1: string} primer y último día del mes */
    public static function rangoMes(int $anio, int $mes): array
    {
        $inicio = sprintf('%04d-%02d-01', $anio, $mes);
        return [$inicio, (new DateTimeImmutable($inicio))->modify('last day of this month')->format('Y-m-d')];
    }

    /**
     * @return array{filas: array, neonatos: array, aplica: array}
     */
    public static function calcular(int $establecimientoId, int $anio, int $mes, ?int $salaId, array $salas): array
    {
        [$inicio, $fin] = self::rangoMes($anio, $mes);
        $filtroSala = $salaId ? ' AND s.id = :sala' : '';
        $params = ['est' => $establecimientoId, 'inicio' => $inicio, 'fin' => $fin] + ($salaId ? ['sala' => $salaId] : []);

        // Qué se vigila según las salas activas (o la sala elegida).
        $salasVigiladas = $salaId ? array_values(array_filter($salas, fn($s) => (int) $s['id'] === $salaId)) : $salas;
        $dispositivosVigilados = [];
        $procedimientosVigilados = [];
        $hayNeonatal = false;
        foreach ($salasVigiladas as $s) {
            $dispositivosVigilados = array_merge($dispositivosVigilados, IaasNorma::DISPOSITIVOS_POR_UPSS[$s['upss']]);
            $procedimientosVigilados = array_merge($procedimientosVigilados, IaasNorma::PROCEDIMIENTOS_POR_UPSS[$s['upss']] ?? []);
            $hayNeonatal = $hayNeonatal || IaasNorma::esNeonatal($s['upss']);
        }

        // Denominador: días-dispositivo (marcas ✓), con el peso al nacer para estratificar.
        $consulta = self::pdo()->prepare(
            "SELECT d.tipo, s.upss, i.peso_nacer_g, COUNT(*) AS n
               FROM iaas_marca m
               JOIN iaas_dispositivo d ON d.id = m.dispositivo_id
               JOIN iaas_internacion i ON i.id = d.internacion_id
               JOIN iaas_sala s ON s.id = i.sala_id
              WHERE s.establecimiento_id = :est AND m.estado = 'E' AND m.fecha BETWEEN :inicio AND :fin $filtroSala
           GROUP BY d.tipo, s.upss, i.peso_nacer_g"
        );
        $consulta->execute($params);
        $dias = [];
        $diasNeo = [];
        foreach ($consulta->fetchAll() as $f) {
            $dias[$f['tipo']] = ($dias[$f['tipo']] ?? 0) + (int) $f['n'];
            if (IaasNorma::esNeonatal($f['upss'])) {
                $cat = IaasNorma::categoriaPeso($f['peso_nacer_g'] !== null ? (int) $f['peso_nacer_g'] : null) ?? 'SIN';
                $diasNeo[$f['tipo']][$cat] = ($diasNeo[$f['tipo']][$cat] ?? 0) + (int) $f['n'];
            }
        }

        // Numeradores: fichas no anuladas.
        $consulta = self::pdo()->prepare(
            "SELECT c.tipo_iaas, c.dispositivo_tipo, c.procedimiento_tipo, c.fecha_iaas, c.fecha_procedimiento, c.peso_nacer_g, s.upss
               FROM iaas_caso c JOIN iaas_sala s ON s.id = c.sala_id
              WHERE c.establecimiento_id = :est AND c.anulado_en IS NULL
                AND (c.fecha_iaas BETWEEN :inicio AND :fin OR c.fecha_procedimiento BETWEEN :inicio2 AND :fin2) $filtroSala"
        );
        $consulta->execute($params + ['inicio2' => $inicio, 'fin2' => $fin]);
        $casosDisp = [];
        $casosNeo = [];
        $casosProc = [];
        foreach ($consulta->fetchAll() as $c) {
            $factor = IaasNorma::FACTOR_POR_TIPO[$c['tipo_iaas']];
            if ($factor === 'dispositivo' && $c['dispositivo_tipo'] && $c['fecha_iaas'] >= $inicio && $c['fecha_iaas'] <= $fin) {
                $clave = $c['tipo_iaas'] . '|' . $c['dispositivo_tipo'];
                $casosDisp[$clave] = ($casosDisp[$clave] ?? 0) + 1;
                if (IaasNorma::esNeonatal($c['upss'])) {
                    $cat = IaasNorma::categoriaPeso($c['peso_nacer_g'] !== null ? (int) $c['peso_nacer_g'] : null) ?? 'SIN';
                    $casosNeo[$clave][$cat] = ($casosNeo[$clave][$cat] ?? 0) + 1;
                }
            } elseif ($factor !== 'dispositivo' && $c['procedimiento_tipo'] && $c['fecha_procedimiento'] >= $inicio && $c['fecha_procedimiento'] <= $fin) {
                $clave = ($factor === 'parto' ? 'END' : 'ISQ') . '|' . $c['procedimiento_tipo'];
                $casosProc[$clave] = ($casosProc[$clave] ?? 0) + 1;
            }
        }

        // Denominador de ISQ y END: partos y cirugías registrados.
        $consulta = self::pdo()->prepare(
            "SELECT p.tipo, SUM(p.cantidad) AS n FROM iaas_procedimiento p JOIN iaas_sala s ON s.id = p.sala_id
              WHERE s.establecimiento_id = :est AND p.fecha BETWEEN :inicio AND :fin $filtroSala GROUP BY p.tipo"
        );
        $consulta->execute($params);
        $procedimientos = array_map('intval', array_column($consulta->fetchAll(), 'n', 'tipo'));

        $filas = [];
        $densidad = [
            ['ITS', 'CVC', 1], ['ITS', 'CVP', 2], ['ITS', 'NPT', 3], ['ITS', 'HD', 4], ['ITU', 'CUP', 5], ['NEU', 'VM', 6],
        ];
        foreach ($densidad as [$tipo, $disp, $n]) {
            if (!in_array($disp, $dispositivosVigilados, true)) {
                continue;
            }
            $num = $casosDisp[$tipo . '|' . $disp] ?? 0;
            $den = $dias[$disp] ?? 0;
            $filas[] = [
                'n' => $n, 'grupo' => 'densidad', 'tipo_iaas' => $tipo, 'dispositivo' => $disp,
                'evento' => IaasNorma::TIPOS_IAAS_SIGLA[$tipo] . ' asociada a ' . IaasNorma::DISPOSITIVOS_SIGLA[$disp],
                'detalle' => IaasNorma::TIPOS_IAAS[$tipo] . ' · ' . IaasNorma::DISPOSITIVOS[$disp],
                'num' => $num, 'den' => $den, 'num_txt' => 'casos', 'den_txt' => 'días de ' . IaasNorma::DISPOSITIVOS_SIGLA[$disp],
                'factor' => 1000, 'tasa' => $den > 0 ? $num / $den * 1000 : null,
            ];
        }
        foreach (['CESAREA', 'COLE_LAPAROTOMIA', 'COLE_LAPAROSCOPIA', 'HERNIOPLASTIA', 'PROTESIS_CADERA'] as $proc) {
            if (!in_array($proc, $procedimientosVigilados, true)) {
                continue;
            }
            $num = $casosProc['ISQ|' . $proc] ?? 0;
            $den = $procedimientos[$proc] ?? 0;
            $filas[] = [
                'n' => 7, 'grupo' => 'acumulada', 'tipo_iaas' => 'ISQ', 'procedimiento' => $proc,
                'evento' => 'ISQ pos ' . mb_strtolower(IaasNorma::PROCEDIMIENTO_SINGULAR[$proc]),
                'detalle' => 'Infección de sitio quirúrgico (superficial o profunda)',
                'num' => $num, 'den' => $den, 'num_txt' => 'casos', 'den_txt' => mb_strtolower(IaasNorma::PROCEDIMIENTOS[$proc]),
                'factor' => 100, 'tasa' => $den > 0 ? $num / $den * 100 : null,
            ];
        }
        foreach (['PARTO_VAGINAL', 'CESAREA'] as $proc) {
            if (!in_array($proc, $procedimientosVigilados, true)) {
                continue;
            }
            $num = $casosProc['END|' . $proc] ?? 0;
            $den = $procedimientos[$proc] ?? 0;
            $filas[] = [
                'n' => 8, 'grupo' => 'acumulada', 'tipo_iaas' => 'END', 'procedimiento' => $proc,
                'evento' => 'Endometritis pos ' . ($proc === 'CESAREA' ? 'cesárea' : 'parto vaginal'),
                'detalle' => 'Endometritis puerperal',
                'num' => $num, 'den' => $den, 'num_txt' => 'casos', 'den_txt' => mb_strtolower(IaasNorma::PROCEDIMIENTOS[$proc]),
                'factor' => 100, 'tasa' => $den > 0 ? $num / $den * 100 : null,
            ];
        }

        // Exposición laboral: del establecimiento entero (no es por sala), sobre el personal del año.
        $personal = self::personal($establecimientoId, $anio);
        $totalPersonal = $personal ? self::totalPersonal($personal) : 0;
        $consulta = self::pdo()->prepare(
            'SELECT factor, COUNT(*) AS n FROM iaas_exposicion
              WHERE establecimiento_id = ? AND anulado_en IS NULL AND fecha BETWEEN ? AND ? GROUP BY factor'
        );
        $consulta->execute([$establecimientoId, $inicio, $fin]);
        $exposiciones = array_map('intval', array_column($consulta->fetchAll(), 'n', 'factor'));
        foreach (['PUNZOCORTANTE' => 'Accidentes punzocortantes', 'SALPICADURA' => 'Exposición de mucosa o piel no intacta'] as $factor => $evento) {
            $num = $exposiciones[$factor] ?? 0;
            $filas[] = [
                'n' => 9, 'grupo' => 'acumulada', 'tipo_iaas' => 'EXPO', 'factor_expo' => $factor,
                'evento' => $evento, 'detalle' => 'Exposición laboral a agentes patógenos de la sangre',
                'num' => $num, 'den' => $totalPersonal, 'num_txt' => 'casos', 'den_txt' => 'personal de salud ' . $anio,
                'factor' => 100, 'tasa' => $totalPersonal > 0 ? $num / $totalPersonal * 100 : null,
            ];
        }

        // Neonatos por peso al nacer (5.1.2.1).
        $neonatos = [];
        if ($hayNeonatal) {
            foreach ($densidad as [$tipo, $disp]) {
                $neonatal = array_filter($salasVigiladas, fn($s) => IaasNorma::esNeonatal($s['upss']) && in_array($disp, IaasNorma::DISPOSITIVOS_POR_UPSS[$s['upss']], true));
                if (!$neonatal) {
                    continue;
                }
                $celdas = [];
                foreach (array_merge(array_keys(IaasNorma::CATEGORIAS_PESO), ['SIN']) as $cat) {
                    $num = $casosNeo[$tipo . '|' . $disp][$cat] ?? 0;
                    $den = $diasNeo[$disp][$cat] ?? 0;
                    $celdas[$cat] = ['num' => $num, 'den' => $den, 'tasa' => $den > 0 ? $num / $den * 1000 : null];
                }
                $neonatos[] = ['evento' => IaasNorma::TIPOS_IAAS_SIGLA[$tipo] . ' asociada a ' . IaasNorma::DISPOSITIVOS_SIGLA[$disp], 'celdas' => $celdas];
            }
        }

        return ['filas' => $filas, 'neonatos' => $neonatos, 'personal' => $personal, 'total_personal' => $totalPersonal];
    }

    /**
     * Revisión de calidad del mes, por sala: días-dispositivo sin marcar,
     * días sin registrar partos o cirugías y fichas por completar.
     */
    public static function calidad(int $establecimientoId, int $anio, int $mes, array $salas): array
    {
        [$inicio, $fin] = self::rangoMes($anio, $mes);
        $hasta = min($fin, date('Y-m-d'));
        $resultado = [];
        foreach ($salas as $sala) {
            $salaId = (int) $sala['id'];
            $grilla = IaasRonda::mes($salaId, $anio, $mes);
            $sinMarcar = 0;
            foreach ($grilla['tablas'] as $filas) {
                foreach ($filas as $fila) {
                    foreach ($fila['celdas'] as $c) {
                        $sinMarcar += $c['sim'] === 'F' ? 1 : 0;
                    }
                }
            }
            $diasSinProcedimientos = 0;
            if (!empty(IaasNorma::PROCEDIMIENTOS_POR_UPSS[$sala['upss']]) && $inicio <= $hasta) {
                $registrados = IaasRonda::procedimientosDelMes($salaId, $inicio, $hasta);
                $total = (new DateTimeImmutable($inicio))->diff(new DateTimeImmutable($hasta))->days + 1;
                $diasSinProcedimientos = $total - count($registrados);
            }
            $consulta = self::pdo()->prepare(
                "SELECT COUNT(*) FROM iaas_caso WHERE sala_id = ? AND anulado_en IS NULL AND estado = 'POR_COMPLETAR'
                   AND (fecha_iaas BETWEEN ? AND ? OR fecha_procedimiento BETWEEN ? AND ?)"
            );
            $consulta->execute([$salaId, $inicio, $fin, $inicio, $fin]);
            $resultado[$salaId] = [
                'sala'           => $sala,
                'sin_marcar'     => $sinMarcar,
                'sin_procedim'   => $diasSinProcedimientos,
                'por_completar'  => (int) $consulta->fetchColumn(),
                'cierre'         => IaasRonda::cierre($salaId, $anio, $mes),
            ];
        }
        return $resultado;
    }

    // ------------------------------------------------------------ Anexo 9

    public static function personal(int $establecimientoId, int $anio): ?array
    {
        $consulta = self::pdo()->prepare('SELECT * FROM iaas_personal WHERE establecimiento_id = ? AND anio = ?');
        $consulta->execute([$establecimientoId, $anio]);
        return $consulta->fetch() ?: null;
    }

    public static function totalPersonal(array $personal): int
    {
        return array_sum(array_map(fn($c) => (int) $personal[strtolower($c)], array_keys(IaasNorma::CONDICIONES_PERSONAL)));
    }

    public static function guardarPersonal(int $establecimientoId, int $anio, array $cantidades, int $usuarioId): void
    {
        self::pdo()->prepare(
            'INSERT INTO iaas_personal (establecimiento_id, anio, estudiante, interno, residente, nombrado, contratado, otro, actualizado_por)
             VALUES (:est, :anio, :estudiante, :interno, :residente, :nombrado, :contratado, :otro, :usr)
             ON DUPLICATE KEY UPDATE estudiante = VALUES(estudiante), interno = VALUES(interno), residente = VALUES(residente),
                 nombrado = VALUES(nombrado), contratado = VALUES(contratado), otro = VALUES(otro), actualizado_por = VALUES(actualizado_por)'
        )->execute(['est' => $establecimientoId, 'anio' => $anio, 'usr' => $usuarioId] + $cantidades);
    }

    /** Insignia del menú: salas con el mes anterior sin cerrar (desde el día 1 del mes siguiente). */
    public static function salasSinCerrarMesAnterior(int $establecimientoId): int
    {
        $anterior = new DateTimeImmutable('first day of last month');
        $consulta = self::pdo()->prepare(
            'SELECT COUNT(*) FROM iaas_sala s
          LEFT JOIN iaas_cierre_mes c ON c.sala_id = s.id AND c.anio = ? AND c.mes = ? AND c.reabierto_en IS NULL
              WHERE s.establecimiento_id = ? AND s.activo = 1 AND c.id IS NULL AND s.creado_en < ?'
        );
        $consulta->execute([(int) $anterior->format('Y'), (int) $anterior->format('n'), $establecimientoId, (new DateTimeImmutable('first day of this month'))->format('Y-m-d')]);
        return (int) $consulta->fetchColumn();
    }
}
