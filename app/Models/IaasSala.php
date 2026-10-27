<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Servicios (UPSS) y salas que vigila cada establecimiento, y si está
 * configurado como "sin hospitalización" (entonces solo le aplica la
 * exposición laboral, Anexo 8). Ver IaasNorma::DISPOSITIVOS_POR_UPSS.
 */
class IaasSala extends Model
{
    protected static string $tabla = 'iaas_sala';

    /** null = el establecimiento todavía no respondió qué servicios tiene. */
    public static function configuracion(int $establecimientoId): ?array
    {
        $consulta = Database::conexion()->prepare('SELECT * FROM iaas_establecimiento WHERE establecimiento_id = :id');
        $consulta->execute(['id' => $establecimientoId]);
        return $consulta->fetch() ?: null;
    }

    /** Salas activas, en el orden de la norma (UCI primero) y luego por nombre. */
    public static function delEstablecimiento(int $establecimientoId, bool $soloActivas = true): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM iaas_sala WHERE establecimiento_id = :id' . ($soloActivas ? ' AND activo = 1' : '')
        );
        $consulta->execute(['id' => $establecimientoId]);
        $salas = $consulta->fetchAll();
        $orden = array_flip(array_keys(IaasNorma::UPSS));
        usort($salas, fn($a, $b) => [$orden[$a['upss']], $a['nombre']] <=> [$orden[$b['upss']], $b['nombre']]);
        return $salas;
    }

    public static function delEstablecimientoPorId(int $establecimientoId, int $salaId): ?array
    {
        $consulta = Database::conexion()->prepare('SELECT * FROM iaas_sala WHERE id = :sala AND establecimiento_id = :est');
        $consulta->execute(['sala' => $salaId, 'est' => $establecimientoId]);
        return $consulta->fetch() ?: null;
    }

    /**
     * Guarda la respuesta de la configuración: las salas por UPSS
     * (['UCI_ADULTOS' => ['UCI adultos'], 'CIRUGIA' => ['Sala A', 'Sala B']])
     * o sin hospitalización. Las salas que ya no vienen se desactivan, no se
     * borran: pueden tener pacientes y marcas.
     */
    public static function guardarConfiguracion(int $establecimientoId, bool $sinHospitalizacion, array $salasPorUpss, int $usuarioId): void
    {
        $pdo = Database::conexion();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO iaas_establecimiento (establecimiento_id, sin_hospitalizacion, actualizado_por)
                 VALUES (:est, :sin, :usr)
                 ON DUPLICATE KEY UPDATE sin_hospitalizacion = VALUES(sin_hospitalizacion), actualizado_por = VALUES(actualizado_por)'
            )->execute(['est' => $establecimientoId, 'sin' => $sinHospitalizacion ? 1 : 0, 'usr' => $usuarioId]);

            $existentes = self::delEstablecimiento($establecimientoId, false);
            $vigentes = [];
            if (!$sinHospitalizacion) {
                $activar = $pdo->prepare(
                    'INSERT INTO iaas_sala (establecimiento_id, upss, nombre) VALUES (:est, :upss, :nombre)
                     ON DUPLICATE KEY UPDATE activo = 1'
                );
                foreach ($salasPorUpss as $upss => $nombres) {
                    foreach ($nombres as $nombre) {
                        $activar->execute(['est' => $establecimientoId, 'upss' => $upss, 'nombre' => $nombre]);
                        $vigentes[$upss . '|' . mb_strtolower($nombre)] = true;
                    }
                }
            }
            $desactivar = $pdo->prepare('UPDATE iaas_sala SET activo = 0 WHERE id = :id');
            foreach ($existentes as $sala) {
                if (!isset($vigentes[$sala['upss'] . '|' . mb_strtolower($sala['nombre'])])) {
                    $desactivar->execute(['id' => $sala['id']]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Pacientes activos por sala (para avisar antes de desactivar una sala con gente). */
    public static function pacientesActivos(int $establecimientoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT s.id, COUNT(i.id) AS n
               FROM iaas_sala s
               JOIN iaas_internacion i ON i.sala_id = s.id AND i.fecha_egreso IS NULL
              WHERE s.establecimiento_id = :est
           GROUP BY s.id'
        );
        $consulta->execute(['est' => $establecimientoId]);
        return array_map('intval', array_column($consulta->fetchAll(), 'n', 'id'));
    }

    /** Establecimientos con el módulo configurado (selector del ADMIN). */
    public static function establecimientosConfigurados(): array
    {
        return Database::conexion()->query(
            'SELECT es.id, es.nombre, es.nombre_corto, ie.sin_hospitalizacion,
                    (SELECT COUNT(*) FROM iaas_sala s WHERE s.establecimiento_id = es.id AND s.activo = 1) AS salas
               FROM establecimiento es
               JOIN iaas_establecimiento ie ON ie.establecimiento_id = es.id
           ORDER BY es.nombre'
        )->fetchAll();
    }
}
