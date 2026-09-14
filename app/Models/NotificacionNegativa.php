<?php
namespace App\Models;

use App\Core\Database;

/**
 * Notificación negativa (P96, pedido del usuario 2026-09-13): "en esta semana
 * epidemiológica este establecimiento no tuvo casos que notificar" de una
 * ficha que se reporta como listado semanal. Una fila por enfermedad +
 * establecimiento + año + semana (sql/migraciones/add_notificacion_negativa.php):
 * anularla no la borra (queda quién y cuándo), y volver a notificar reutiliza
 * la misma fila.
 */
class NotificacionNegativa
{
    /**
     * La notificación de esa semana, activa o anulada, con los nombres de
     * quién la registró y quién la anuló. null si nunca se notificó.
     */
    public static function buscar(int $enfermedadId, int $establecimientoId, int $anio, int $semana): ?array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT nn.*, u.nombre AS usuario_nombre, ua.nombre AS anulada_por_nombre
               FROM notificacion_negativa nn
               JOIN usuario u       ON u.id = nn.usuario_id
          LEFT JOIN usuario ua      ON ua.id = nn.anulada_por
              WHERE nn.enfermedad_id = :enfermedad AND nn.establecimiento_id = :establecimiento
                AND nn.anio_epi = :anio AND nn.semana_epi = :semana'
        );
        $consulta->execute([
            'enfermedad'      => $enfermedadId,
            'establecimiento' => $establecimientoId,
            'anio'            => $anio,
            'semana'          => $semana,
        ]);

        return $consulta->fetch() ?: null;
    }

    /** Registra (o reactiva, si estaba anulada) la notificación de esa semana. */
    public static function registrar(int $enfermedadId, int $establecimientoId, int $anio, int $semana, int $usuarioId): void
    {
        Database::conexion()->prepare(
            'INSERT INTO notificacion_negativa (enfermedad_id, establecimiento_id, anio_epi, semana_epi, usuario_id)
             VALUES (:enfermedad, :establecimiento, :anio, :semana, :usuario)
             ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id), creado_en = CURRENT_TIMESTAMP,
                                     anulada_por = NULL, anulada_en = NULL'
        )->execute([
            'enfermedad'      => $enfermedadId,
            'establecimiento' => $establecimientoId,
            'anio'            => $anio,
            'semana'          => $semana,
            'usuario'         => $usuarioId,
        ]);
    }

    public static function anular(int $id, int $usuarioId): void
    {
        Database::conexion()->prepare(
            'UPDATE notificacion_negativa SET anulada_por = :usuario, anulada_en = CURRENT_TIMESTAMP
              WHERE id = :id AND anulada_en IS NULL'
        )->execute(['usuario' => $usuarioId, 'id' => $id]);
    }
}
