<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use DateTimeImmutable;

/**
 * Cierre semanal de la notificación (2026-10-17). Cada establecimiento cierra
 * cada semana epidemiológica, tenga casos o no: así DIRSAPOL distingue "sin
 * casos" de "no reportó". Las enfermedades sin fichas de esa SE quedan
 * notificadas como sin casos (notificación negativa).
 *
 * Estados de un establecimiento en una SE:
 *   T  cerrada a tiempo        L  cerrada fuera de plazo
 *   P  sin cerrar, plazo vigente   X  sin cerrar, plazo vencido (no notificó)
 *
 * Una fila con anulada_en es una semana REABIERTA por el ADMIN: cuenta como
 * sin cerrar hasta que se vuelva a cerrar (se reutiliza la fila). Las fichas
 * registradas después del cierre no la reabren: quedan como tardías.
 */
class NotificacionSemanal extends Model
{
    protected static string $tabla = 'notificacion_semanal';

    public const ETIQUETAS = [
        'T' => 'Cerrada a tiempo',
        'L' => 'Cerrada fuera de plazo',
        'P' => 'Pendiente',
        'X' => 'No notificó',
    ];

    /** Plazo de cierre de una SE: config notificacion_semanal (por defecto, lunes siguiente 12:00). */
    public static function plazo(int $anio, int $semana): DateTimeImmutable
    {
        static $config = null;
        if ($config === null) {
            $config = (require __DIR__ . '/../../config/config.php')['notificacion_semanal'] ?? [];
        }
        $dias = 6 + (int) ($config['plazo_dias'] ?? 2);
        $hora = horaValida((string) ($config['plazo_hora'] ?? '12:00')) ?? '12:00';
        $fecha = inicioSemanaEpidemiologica($anio, $semana)->modify("+$dias days")->format('Y-m-d');

        return new DateTimeImmutable("$fecha $hora");
    }

    /**
     * La SE que toca cerrar ahora: la anterior mientras su plazo no venza (el
     * domingo y el lunes por la mañana todavía se cierra la que terminó), si
     * no la SE en curso.
     */
    public static function semanaObjetivo(?DateTimeImmutable $ahora = null): array
    {
        $ahora ??= new DateTimeImmutable();
        $actual = semanaEpidemiologica($ahora->format('Y-m-d'));
        $anterior = semanaEpidemiologica($ahora->modify('-7 days')->format('Y-m-d'));

        return $ahora <= self::plazo($anterior['anio'], $anterior['semana']) ? $anterior : $actual;
    }

    public static function estado(?array $cierre, int $anio, int $semana, ?DateTimeImmutable $ahora = null): string
    {
        if ($cierre && $cierre['anulada_en'] === null) {
            return (int) $cierre['tardia'] ? 'L' : 'T';
        }

        return ($ahora ?? new DateTimeImmutable()) > self::plazo($anio, $semana) ? 'X' : 'P';
    }

    /**
     * Cierres (vigentes o reabiertos) de un rango de SE, por "est|anio-semana".
     * $establecimientoId null = todos.
     */
    public static function delRango(?int $establecimientoId, array $desde, array $hasta): array
    {
        $parametros = ['ad1' => $desde[0], 'ad2' => $desde[0], 'sd' => $desde[1], 'ah1' => $hasta[0], 'ah2' => $hasta[0], 'sh' => $hasta[1]];
        $sql = 'SELECT ns.*, u.nombre AS usuario_nombre
                  FROM notificacion_semanal ns
                  JOIN usuario u ON u.id = ns.usuario_id
                 WHERE (ns.anio_epi > :ad1 OR (ns.anio_epi = :ad2 AND ns.semana_epi >= :sd))
                   AND (ns.anio_epi < :ah1 OR (ns.anio_epi = :ah2 AND ns.semana_epi <= :sh))';
        if ($establecimientoId !== null) {
            $sql .= ' AND ns.establecimiento_id = :e';
            $parametros['e'] = $establecimientoId;
        }
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);
        $cierres = [];
        foreach ($consulta->fetchAll() as $fila) {
            $cierres[$fila['establecimiento_id'] . '|' . $fila['anio_epi'] . '-' . $fila['semana_epi']] = $fila;
        }

        return $cierres;
    }

    /** Un cierre con los nombres de quien cerró, reabrió o pidió reabrir. */
    public static function deSemana(int $establecimientoId, int $anio, int $semana): ?array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT ns.*, u.nombre AS usuario_nombre, ua.nombre AS anulada_por_nombre, us.nombre AS solicitada_por_nombre
               FROM notificacion_semanal ns
               JOIN usuario u ON u.id = ns.usuario_id
          LEFT JOIN usuario ua ON ua.id = ns.anulada_por
          LEFT JOIN usuario us ON us.id = ns.reapertura_solicitada_por
              WHERE ns.establecimiento_id = :e AND ns.anio_epi = :a AND ns.semana_epi = :s'
        );
        $consulta->execute(['e' => $establecimientoId, 'a' => $anio, 's' => $semana]);

        return $consulta->fetch() ?: null;
    }

    /** Cierra (o vuelve a cerrar una semana reabierta, en la misma fila). */
    public static function cerrar(int $establecimientoId, int $anio, int $semana, int $usuarioId, bool $tardia, ?string $motivo): void
    {
        Database::conexion()->prepare(
            'INSERT INTO notificacion_semanal (establecimiento_id, anio_epi, semana_epi, usuario_id, tardia, motivo_tardio)
             VALUES (:e, :a, :s, :u, :t, :m)
             ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id), creado_en = CURRENT_TIMESTAMP,
                 tardia = VALUES(tardia), motivo_tardio = VALUES(motivo_tardio), anulada_por = NULL, anulada_en = NULL,
                 reapertura_solicitada_por = NULL, reapertura_solicitada_en = NULL, motivo_solicitud = NULL'
        )->execute(['e' => $establecimientoId, 'a' => $anio, 's' => $semana, 'u' => $usuarioId, 't' => $tardia ? 1 : 0, 'm' => $motivo]);
    }

    /** Reapertura por el ADMIN. Devuelve false si la semana no estaba cerrada. */
    public static function reabrir(int $establecimientoId, int $anio, int $semana, int $usuarioId, string $motivo): bool
    {
        $consulta = Database::conexion()->prepare(
            'UPDATE notificacion_semanal SET anulada_por = :u, anulada_en = CURRENT_TIMESTAMP, motivo_reapertura = :m,
                    reapertura_solicitada_por = NULL, reapertura_solicitada_en = NULL, motivo_solicitud = NULL
              WHERE establecimiento_id = :e AND anio_epi = :a AND semana_epi = :s AND anulada_en IS NULL'
        );
        $consulta->execute(['u' => $usuarioId, 'm' => $motivo, 'e' => $establecimientoId, 'a' => $anio, 's' => $semana]);

        return $consulta->rowCount() > 0;
    }

    /** El registrador pide reabrir una semana cerrada. Devuelve false si no estaba cerrada. */
    public static function solicitarReapertura(int $establecimientoId, int $anio, int $semana, int $usuarioId, string $motivo): bool
    {
        $consulta = Database::conexion()->prepare(
            'UPDATE notificacion_semanal SET reapertura_solicitada_por = :u, reapertura_solicitada_en = CURRENT_TIMESTAMP, motivo_solicitud = :m
              WHERE establecimiento_id = :e AND anio_epi = :a AND semana_epi = :s AND anulada_en IS NULL'
        );
        $consulta->execute(['u' => $usuarioId, 'm' => $motivo, 'e' => $establecimientoId, 'a' => $anio, 's' => $semana]);

        return $consulta->rowCount() > 0;
    }

    /** Fichas no anuladas de un establecimiento en una SE, por enfermedad. */
    public static function fichasPorEnfermedad(int $establecimientoId, int $anio, int $semana): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT enfermedad_id, COUNT(*) AS n FROM caso
              WHERE anulado = 0 AND establecimiento_id = :e AND anio_epi = :a AND semana_epi = :s
           GROUP BY enfermedad_id'
        );
        $consulta->execute(['e' => $establecimientoId, 'a' => $anio, 's' => $semana]);

        return array_map('intval', array_column($consulta->fetchAll(), 'n', 'enfermedad_id'));
    }

    /**
     * Fichas y fichas tardías (registradas después del cierre vigente) por
     * establecimiento en una SE: [est_id => ['fichas' => n, 'tardias' => n]].
     */
    public static function fichasDeSemana(int $anio, int $semana, ?int $establecimientoId = null): array
    {
        $sql = 'SELECT c.establecimiento_id, COUNT(*) AS fichas,
                       SUM(ns.id IS NOT NULL AND c.creado_en > ns.creado_en) AS tardias
                  FROM caso c
             LEFT JOIN notificacion_semanal ns ON ns.establecimiento_id = c.establecimiento_id
                       AND ns.anio_epi = c.anio_epi AND ns.semana_epi = c.semana_epi AND ns.anulada_en IS NULL
                 WHERE c.anulado = 0 AND c.anio_epi = :a AND c.semana_epi = :s';
        $parametros = ['a' => $anio, 's' => $semana];
        if ($establecimientoId !== null) {
            $sql .= ' AND c.establecimiento_id = :e';
            $parametros['e'] = $establecimientoId;
        }
        $consulta = Database::conexion()->prepare($sql . ' GROUP BY c.establecimiento_id');
        $consulta->execute($parametros);
        $resultado = [];
        foreach ($consulta->fetchAll() as $fila) {
            $resultado[(int) $fila['establecimiento_id']] = ['fichas' => (int) $fila['fichas'], 'tardias' => (int) $fila['tardias']];
        }

        return $resultado;
    }

    /** ¿La ficha se registró después de que su establecimiento cerró esa SE? */
    public static function fichaEsTardia(array $caso): ?array
    {
        $cierre = self::deSemana((int) $caso['establecimiento_id'], (int) $caso['anio_epi'], (int) $caso['semana_epi']);
        if (!$cierre || $cierre['anulada_en'] !== null || $caso['creado_en'] <= $cierre['creado_en']) {
            return null;
        }

        return $cierre;
    }

    /** Registradores activos por establecimiento: [est_id => [['nombre', 'email'], ...]]. */
    public static function registradores(): array
    {
        $resultado = [];
        foreach (Database::conexion()->query(
            "SELECT establecimiento_id, nombre, email FROM usuario
              WHERE rol = 'REGISTRADOR' AND activo = 1 AND establecimiento_id IS NOT NULL ORDER BY nombre"
        )->fetchAll() as $fila) {
            $resultado[(int) $fila['establecimiento_id']][] = $fila;
        }

        return $resultado;
    }

    /** Establecimientos activos con su red (para el seguimiento del ADMIN). */
    public static function establecimientos(?int $redId = null): array
    {
        $sql = 'SELECT es.id, es.nombre, es.red_id, r.nombre AS red_nombre
                  FROM establecimiento es LEFT JOIN red_salud r ON r.id = es.red_id
                 WHERE es.activo = 1' . ($redId ? ' AND es.red_id = :red' : '') . ' ORDER BY es.nombre';
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($redId ? ['red' => $redId] : []);

        return $consulta->fetchAll();
    }
}
