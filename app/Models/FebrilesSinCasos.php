<?php
namespace App\Models;

use App\Core\Database;

/**
 * Notificación negativa diaria del registro de febriles (2026-11-09): "este
 * día el establecimiento no tuvo febriles". Una fila por establecimiento +
 * fecha (sql/migraciones/add_febriles_sin_casos.php); anularla no la borra.
 *
 * Decisiones de Epidemiología (2026-11-11):
 * - Es obligatoria desde OBLIGATORIA_DESDE: la SE no se cierra mientras
 *   quede un día sin febriles ni negativa (ver pendientesParaCierre()).
 * - IPRESS de nivel I: los domingos y feriados nacionales figuran "sin
 *   atención" solos; otro día sin atención (no laborable decretado) se
 *   notifica con tipo SIN_ATENCION. Nivel II y III reportan todos los días.
 * - Plazo: hasta el día siguiente; después se acepta como tardía.
 */
class FebrilesSinCasos
{
    /** Primer día obligatorio: domingo de la SE 46 de 2026, la primera completa tras la decisión. */
    public const OBLIGATORIA_DESDE = '2026-11-15';

    public const TIPOS = ['SIN_FEBRILES' => 'sin febriles', 'SIN_ATENCION' => 'sin atención'];

    /** Texto de cada estado de día (estadoDias()). */
    public const ESTADOS = [
        'CON'  => 'con febriles',
        'SIN'  => 'notificado sin febriles',
        'ATN'  => 'sin atención',
        'PEND' => 'pendiente',
        'FUT'  => 'aún no llega',
    ];

    /** La negativa de ese día, activa o anulada, con los nombres. null si nunca se notificó. */
    public static function buscar(int $establecimientoId, string $fecha): ?array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT f.*, u.nombre AS usuario_nombre, ua.nombre AS anulada_por_nombre
               FROM febriles_sin_casos f
               JOIN usuario u  ON u.id = f.usuario_id
          LEFT JOIN usuario ua ON ua.id = f.anulada_por
              WHERE f.establecimiento_id = :establecimiento AND f.fecha = :fecha'
        );
        $consulta->execute(['establecimiento' => $establecimientoId, 'fecha' => $fecha]);

        return $consulta->fetch() ?: null;
    }

    /** Registra (o reactiva, si estaba anulada) la negativa de ese día. */
    public static function registrar(int $establecimientoId, string $fecha, int $usuarioId, string $tipo = 'SIN_FEBRILES'): void
    {
        Database::conexion()->prepare(
            'INSERT INTO febriles_sin_casos (establecimiento_id, fecha, tipo, usuario_id)
             VALUES (:establecimiento, :fecha, :tipo, :usuario)
             ON DUPLICATE KEY UPDATE tipo = VALUES(tipo), usuario_id = VALUES(usuario_id), creado_en = CURRENT_TIMESTAMP,
                                     anulada_por = NULL, anulada_en = NULL'
        )->execute(['establecimiento' => $establecimientoId, 'fecha' => $fecha, 'tipo' => isset(self::TIPOS[$tipo]) ? $tipo : 'SIN_FEBRILES', 'usuario' => $usuarioId]);
    }

    public static function anular(int $id, int $usuarioId): void
    {
        Database::conexion()->prepare(
            'UPDATE febriles_sin_casos SET anulada_por = :usuario, anulada_en = CURRENT_TIMESTAMP
              WHERE id = :id AND anulada_en IS NULL'
        )->execute(['usuario' => $usuarioId, 'id' => $id]);
    }

    /**
     * Registrar (o mover a esa fecha) un febril contradice la negativa del
     * día: se anula a nombre de quien registró. Devuelve el tipo que tenía
     * la negativa activa, o null si no había.
     */
    public static function anularPorFebril(int $establecimientoId, string $fecha, int $usuarioId): ?string
    {
        $negativa = self::buscar($establecimientoId, $fecha);
        if (!$negativa || $negativa['anulada_en'] !== null) {
            return null;
        }
        self::anular((int) $negativa['id'], $usuarioId);

        return $negativa['tipo'];
    }

    /** Nivel I (I-1 a I-4): puede tener días sin atención. */
    public static function esNivelI(?string $categoria): bool
    {
        return str_starts_with((string) $categoria, 'I-');
    }

    /** Último día del plazo para notificar $fecha: el día siguiente. */
    public static function plazo(string $fecha): string
    {
        return (new \DateTimeImmutable($fecha))->modify('+1 day')->format('Y-m-d');
    }

    /** Notificada después del día siguiente. */
    public static function esTardia(string $fecha, string $creadoEn): bool
    {
        return substr($creadoEn, 0, 10) > self::plazo($fecha);
    }

    /**
     * Para una IPRESS de nivel I, por qué ese día figura sin atención sin
     * notificarlo: "Domingo" o el nombre del feriado nacional. null si no.
     */
    public static function sinAtencionAutomatica(string $fecha, ?string $categoria): ?string
    {
        if (!self::esNivelI($categoria)) {
            return null;
        }
        if (date('w', strtotime($fecha)) === '0') {
            return 'Domingo';
        }

        return self::feriado($fecha);
    }

    /**
     * Feriados nacionales del Perú (calendario vigente a 2026; si una ley
     * agrega o quita uno, se corrige esta lista), o null. Los días
     * no laborables que decreta el Ejecutivo cambian cada año: esos los
     * notifica la IPRESS como "sin atención".
     */
    public static function feriado(string $fecha): ?string
    {
        $fijos = [
            '01-01' => 'Año Nuevo', '05-01' => 'Día del Trabajo', '06-07' => 'Batalla de Arica y Día de la Bandera',
            '06-29' => 'San Pedro y San Pablo', '07-23' => 'Día de la Fuerza Aérea del Perú', '07-28' => 'Fiestas Patrias',
            '07-29' => 'Fiestas Patrias', '08-06' => 'Batalla de Junín', '08-30' => 'Santa Rosa de Lima',
            '10-08' => 'Combate de Angamos', '11-01' => 'Todos los Santos', '12-08' => 'Inmaculada Concepción',
            '12-09' => 'Batalla de Ayacucho', '12-25' => 'Navidad',
        ];
        if (isset($fijos[substr($fecha, 5)])) {
            return $fijos[substr($fecha, 5)];
        }
        $pascua = self::domingoDePascua((int) substr($fecha, 0, 4));
        if ($fecha === $pascua->modify('-3 days')->format('Y-m-d')) {
            return 'Jueves Santo';
        }
        if ($fecha === $pascua->modify('-2 days')->format('Y-m-d')) {
            return 'Viernes Santo';
        }

        return null;
    }

    /** Domingo de Pascua (algoritmo de Meeus/Jones/Butcher; no depende de la extensión calendar). */
    private static function domingoDePascua(int $anio): \DateTimeImmutable
    {
        $a = $anio % 19;
        $b = intdiv($anio, 100);
        $c = $anio % 100;
        $h = (19 * $a + $b - intdiv($b, 4) - intdiv($b - intdiv($b + 8, 25) + 1, 3) + 15) % 30;
        $l = (32 + 2 * ($b % 4) + 2 * intdiv($c, 4) - $h - $c % 4) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = ($h + $l - 7 * $m + 114) % 31 + 1;

        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $anio, $mes, $dia));
    }

    /**
     * Estado de cada día de $desde a $hasta para un establecimiento. Ver estadoVarios().
     *
     * @return array<string, array{estado: string, febriles: int, tardia: bool, auto: ?string, negativa: ?array}>
     */
    public static function estadoDias(int $establecimientoId, string $desde, string $hasta): array
    {
        return self::estadoVarios([$establecimientoId], $desde, $hasta)[$establecimientoId] ?? [];
    }

    /**
     * Estado de cada día por establecimiento: 'CON' (tiene febriles), 'SIN'
     * (negativa sin febriles), 'ATN' (sin atención: notificada, o domingo o
     * feriado de una IPRESS de nivel I, con 'auto' = el motivo), 'PEND' (ya
     * pasó o es hoy, sin nada) o 'FUT' (aún no llega). 'tardia' marca la
     * negativa notificada después del plazo. Los febriles se cuentan por
     * fecha de notificación, igual que la hoja del día (FormatoPnp::febriles).
     *
     * @param int[] $establecimientoIds
     * @return array<int, array<string, array{estado: string, febriles: int, tardia: bool, auto: ?string, negativa: ?array}>>
     */
    public static function estadoVarios(array $establecimientoIds, string $desde, string $hasta): array
    {
        $ids = array_values(array_unique(array_map('intval', $establecimientoIds)));
        if (!$ids) {
            return [];
        }
        $pdo = Database::conexion();
        $lista = implode(',', $ids);

        $consulta = $pdo->prepare(
            "SELECT c.establecimiento_id, c.fecha_notif AS fecha, COUNT(*) AS n
               FROM caso c JOIN enfermedad e ON e.id = c.enfermedad_id
              WHERE e.cie10 = :feb AND c.anulado = 0 AND c.establecimiento_id IN ($lista)
                AND c.fecha_notif BETWEEN :desde AND :hasta
           GROUP BY c.establecimiento_id, c.fecha_notif"
        );
        $consulta->execute(['feb' => Cie10Vigilancia::REGISTRO_FEBRILES, 'desde' => $desde, 'hasta' => $hasta]);
        $febriles = [];
        foreach ($consulta as $fila) {
            $febriles[(int) $fila['establecimiento_id']][$fila['fecha']] = (int) $fila['n'];
        }

        $consulta = $pdo->prepare(
            "SELECT f.id, f.establecimiento_id, f.fecha, f.tipo, f.usuario_id, f.creado_en, u.nombre AS usuario_nombre
               FROM febriles_sin_casos f JOIN usuario u ON u.id = f.usuario_id
              WHERE f.establecimiento_id IN ($lista) AND f.fecha BETWEEN :desde AND :hasta AND f.anulada_en IS NULL"
        );
        $consulta->execute(['desde' => $desde, 'hasta' => $hasta]);
        $negativas = [];
        foreach ($consulta as $fila) {
            $negativas[(int) $fila['establecimiento_id']][$fila['fecha']] = $fila;
        }

        $categorias = $pdo->query("SELECT id, categoria FROM establecimiento WHERE id IN ($lista)")->fetchAll(\PDO::FETCH_KEY_PAIR);

        $hoy = date('Y-m-d');
        $resultado = [];
        foreach ($ids as $id) {
            for ($d = new \DateTimeImmutable($desde); $d->format('Y-m-d') <= $hasta; $d = $d->modify('+1 day')) {
                $f = $d->format('Y-m-d');
                $n = $febriles[$id][$f] ?? 0;
                $negativa = $negativas[$id][$f] ?? null;
                $auto = self::sinAtencionAutomatica($f, $categorias[$id] ?? null);
                if ($n > 0) {
                    $estado = 'CON';
                } elseif ($negativa) {
                    $estado = $negativa['tipo'] === 'SIN_ATENCION' ? 'ATN' : 'SIN';
                } elseif ($auto !== null) {
                    $estado = 'ATN';
                } else {
                    $estado = $f > $hoy ? 'FUT' : 'PEND';
                }
                $resultado[$id][$f] = [
                    'estado'   => $estado,
                    'febriles' => $n,
                    'tardia'   => $n === 0 && $negativa !== null && self::esTardia($f, $negativa['creado_en']),
                    'auto'     => $n === 0 && $negativa === null ? $auto : null,
                    'negativa' => $n === 0 ? $negativa : null,
                ];
            }
        }

        return $resultado;
    }

    /**
     * Días de la SE que impiden cerrarla: desde OBLIGATORIA_DESDE, los que
     * no tienen febriles, negativa ni sin atención (incluidos los que aún no
     * llegan, si se cierra antes del sábado).
     *
     * @return string[] fechas
     */
    public static function pendientesParaCierre(int $establecimientoId, int $anio, int $semana): array
    {
        $inicio = inicioSemanaEpidemiologica($anio, $semana);
        $dias = self::estadoDias($establecimientoId, $inicio->format('Y-m-d'), $inicio->modify('+6 days')->format('Y-m-d'));

        return array_keys(array_filter($dias, fn($d, $f) => $f >= self::OBLIGATORIA_DESDE && in_array($d['estado'], ['PEND', 'FUT'], true), ARRAY_FILTER_USE_BOTH));
    }
}
