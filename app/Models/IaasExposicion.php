<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Exposición laboral a agentes patógenos de la sangre en el personal de la
 * salud (Anexo 8). Son datos de salud del propio personal: cada
 * establecimiento ve solo los suyos. El papel no pide el nombre, solo
 * HC/autogenerado/DNI, edad y sexo; aquí tampoco.
 */
class IaasExposicion extends Model
{
    protected static string $tabla = 'iaas_exposicion';

    public const COLUMNAS = [
        'identificador', 'edad_anios', 'sexo', 'profesion', 'profesion_otra', 'condicion', 'servicio', 'servicio_otro',
        'factor', 'fecha', 'hora', 'objeto', 'profundidad', 'fluido', 'fluido_otro', 'epp', 'localizacion',
        'paciente_fuente', 'procedimiento', 'procedimiento_otro', 'profilaxis', 'profilaxis_medicamento', 'observaciones',
    ];

    public static function delEstablecimiento(int $id, int $establecimientoId): ?array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT x.*, u.nombre AS creado_nombre FROM iaas_exposicion x JOIN usuario u ON u.id = x.creado_por
              WHERE x.id = ? AND x.establecimiento_id = ?'
        );
        $consulta->execute([$id, $establecimientoId]);
        return $consulta->fetch() ?: null;
    }

    public static function listado(int $establecimientoId, int $anio, ?int $mes, bool $anuladas): array
    {
        $sql = 'SELECT * FROM iaas_exposicion WHERE establecimiento_id = :est AND YEAR(fecha) = :anio'
             . ($mes ? ' AND MONTH(fecha) = :mes' : '')
             . ($anuladas ? ' AND anulado_en IS NOT NULL' : ' AND anulado_en IS NULL')
             . ' ORDER BY fecha DESC, hora DESC, id DESC';
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute(['est' => $establecimientoId, 'anio' => $anio] + ($mes ? ['mes' => $mes] : []));
        return $consulta->fetchAll();
    }

    public static function anular(int $id, int $usuarioId): void
    {
        Database::conexion()->prepare('UPDATE iaas_exposicion SET anulado_por = ?, anulado_en = CURRENT_TIMESTAMP WHERE id = ?')->execute([$usuarioId, $id]);
    }
}
