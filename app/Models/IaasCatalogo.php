<?php
namespace App\Models;

use App\Core\Database;

/**
 * Parámetros del módulo IAAS que el ADMIN puede ajustar: microorganismos
 * (Anexo 11), antimicrobianos del antibiograma (Anexo 5, 4.5) y horas t del
 * índice de riesgo de ISQ. Se cargan con add_modulo_iaas.php.
 */
class IaasCatalogo
{
    public static function microorganismos(bool $soloActivos = true): array
    {
        return Database::conexion()->query(
            'SELECT * FROM iaas_microorganismo' . ($soloActivos ? ' WHERE activo = 1' : '') . ' ORDER BY familia, orden, nombre'
        )->fetchAll();
    }

    public static function antimicrobianos(bool $soloActivos = true): array
    {
        return Database::conexion()->query(
            'SELECT * FROM iaas_antimicrobiano' . ($soloActivos ? ' WHERE activo = 1' : '') . ' ORDER BY orden, id'
        )->fetchAll();
    }

    /** @return array<string, float> procedimiento => horas */
    public static function horasT(): array
    {
        $filas = Database::conexion()->query('SELECT procedimiento, horas FROM iaas_horas_t')->fetchAll();
        return array_map('floatval', array_column($filas, 'horas', 'procedimiento'));
    }

    public static function horasTConDetalle(): array
    {
        return Database::conexion()->query(
            'SELECT h.*, u.nombre AS usuario_nombre FROM iaas_horas_t h LEFT JOIN usuario u ON u.id = h.actualizado_por'
        )->fetchAll();
    }

    public static function guardarHorasT(string $procedimiento, float $horas, int $usuarioId): void
    {
        Database::conexion()->prepare(
            'INSERT INTO iaas_horas_t (procedimiento, horas, actualizado_por) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE horas = VALUES(horas), actualizado_por = VALUES(actualizado_por)'
        )->execute([$procedimiento, $horas, $usuarioId]);
    }

    public static function guardarAntimicrobiano(?int $id, string $nombre, bool $activo): void
    {
        $pdo = Database::conexion();
        if ($id) {
            $pdo->prepare('UPDATE iaas_antimicrobiano SET nombre = ?, activo = ? WHERE id = ?')->execute([$nombre, $activo ? 1 : 0, $id]);
            return;
        }
        $orden = (int) $pdo->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM iaas_antimicrobiano')->fetchColumn();
        $pdo->prepare('INSERT INTO iaas_antimicrobiano (nombre, orden, activo) VALUES (?, ?, ?)')->execute([$nombre, $orden, $activo ? 1 : 0]);
    }

    public static function guardarMicroorganismo(?int $id, int $familia, string $nombre, string $abreviatura, bool $activo): void
    {
        $pdo = Database::conexion();
        if ($id) {
            $pdo->prepare('UPDATE iaas_microorganismo SET familia = ?, nombre = ?, abreviatura = ?, activo = ? WHERE id = ?')
                ->execute([$familia, $nombre, $abreviatura, $activo ? 1 : 0, $id]);
            return;
        }
        $orden = (int) $pdo->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM iaas_microorganismo')->fetchColumn();
        $pdo->prepare('INSERT INTO iaas_microorganismo (familia, nombre, abreviatura, orden, activo) VALUES (?, ?, ?, ?, ?)')
            ->execute([$familia, $nombre, $abreviatura, $orden, $activo ? 1 : 0]);
    }
}
