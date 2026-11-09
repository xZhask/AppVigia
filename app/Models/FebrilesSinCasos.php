<?php
namespace App\Models;

use App\Core\Database;

/**
 * Notificación negativa diaria del registro de febriles (2026-11-09): "este
 * día el establecimiento no tuvo febriles". Una fila por establecimiento +
 * fecha (sql/migraciones/add_febriles_sin_casos.php); anularla no la borra.
 * Es opcional: el cierre de la SE solo avisa de los días que quedaron sin
 * febriles ni negativa (ver estadoDias()).
 */
class FebrilesSinCasos
{
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
    public static function registrar(int $establecimientoId, string $fecha, int $usuarioId): void
    {
        Database::conexion()->prepare(
            'INSERT INTO febriles_sin_casos (establecimiento_id, fecha, usuario_id)
             VALUES (:establecimiento, :fecha, :usuario)
             ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id), creado_en = CURRENT_TIMESTAMP,
                                     anulada_por = NULL, anulada_en = NULL'
        )->execute(['establecimiento' => $establecimientoId, 'fecha' => $fecha, 'usuario' => $usuarioId]);
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
     * día: se anula a nombre de quien registró. true si había una activa.
     */
    public static function anularPorFebril(int $establecimientoId, string $fecha, int $usuarioId): bool
    {
        $consulta = Database::conexion()->prepare(
            'UPDATE febriles_sin_casos SET anulada_por = :usuario, anulada_en = CURRENT_TIMESTAMP
              WHERE establecimiento_id = :establecimiento AND fecha = :fecha AND anulada_en IS NULL'
        );
        $consulta->execute(['usuario' => $usuarioId, 'establecimiento' => $establecimientoId, 'fecha' => $fecha]);

        return $consulta->rowCount() > 0;
    }

    /**
     * Estado de cada día de $desde a $hasta para un establecimiento:
     * 'CON' (tiene febriles), 'SIN' (negativa activa), 'PEND' (ya pasó o es
     * hoy, sin nada) o 'FUT' (aún no llega). Los febriles se cuentan por
     * fecha de notificación, igual que la hoja del día (FormatoPnp::febriles).
     *
     * @return array<string, array{estado: string, febriles: int}>
     */
    public static function estadoDias(int $establecimientoId, string $desde, string $hasta): array
    {
        $pdo = Database::conexion();
        $consulta = $pdo->prepare(
            'SELECT c.fecha_notif AS fecha, COUNT(*) AS n
               FROM caso c JOIN enfermedad e ON e.id = c.enfermedad_id
              WHERE e.cie10 = :feb AND c.anulado = 0 AND c.establecimiento_id = :establecimiento
                AND c.fecha_notif BETWEEN :desde AND :hasta
           GROUP BY c.fecha_notif'
        );
        $consulta->execute(['feb' => Cie10Vigilancia::REGISTRO_FEBRILES, 'establecimiento' => $establecimientoId, 'desde' => $desde, 'hasta' => $hasta]);
        $febriles = array_map('intval', $consulta->fetchAll(\PDO::FETCH_KEY_PAIR));

        $consulta = $pdo->prepare(
            'SELECT fecha FROM febriles_sin_casos
              WHERE establecimiento_id = :establecimiento AND fecha BETWEEN :desde AND :hasta AND anulada_en IS NULL'
        );
        $consulta->execute(['establecimiento' => $establecimientoId, 'desde' => $desde, 'hasta' => $hasta]);
        $negativas = array_flip($consulta->fetchAll(\PDO::FETCH_COLUMN));

        $hoy = date('Y-m-d');
        $dias = [];
        for ($d = new \DateTimeImmutable($desde); $d->format('Y-m-d') <= $hasta; $d = $d->modify('+1 day')) {
            $f = $d->format('Y-m-d');
            $n = $febriles[$f] ?? 0;
            $dias[$f] = [
                'estado'   => $n > 0 ? 'CON' : (isset($negativas[$f]) ? 'SIN' : ($f > $hoy ? 'FUT' : 'PEND')),
                'febriles' => $n,
            ];
        }

        return $dias;
    }
}
