<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Lectura de error_registro para /sistema/errores (2026-10-27). Lo escribe
 * App\Core\RegistroErrores. Las repeticiones del mismo error comparten
 * "huella" y se muestran como un grupo; resolver marca todo el grupo y, si
 * vuelve a ocurrir, el grupo reaparece como pendiente.
 */
class ErrorRegistro extends Model
{
    protected static string $tabla = 'error_registro';

    public const NIVELES = [
        'EXCEPCION'  => 'Excepción',
        'FATAL'      => 'Caída grave',
        'WARNING'    => 'Aviso',
        'NOTICE'     => 'Aviso menor',
        'DEPRECATED' => 'Obsoleto',
        'ERROR'      => 'Error',
        'JS'         => 'JavaScript',
    ];

    /**
     * Un grupo por huella, con la última ocurrencia y cuántas veces, a cuántos
     * usuarios y a cuántas IPRESS afectó.
     *
     * @param string $estado PENDIENTES | RESUELTOS | TODOS
     */
    public static function grupos(string $estado, ?string $origen): array
    {
        $donde = $origen ? 'WHERE origen = :origen' : '';
        $tener = match ($estado) {
            'PENDIENTES' => 'HAVING pendientes > 0',
            'RESUELTOS'  => 'HAVING pendientes = 0',
            default      => '',
        };
        $consulta = Database::conexion()->prepare(
            "SELECT g.*, r.origen, r.nivel, r.mensaje, r.archivo, r.linea, r.ruta
               FROM (SELECT huella, MAX(id) AS ultimo_id, COUNT(*) AS veces, MIN(creado_en) AS primero, MAX(creado_en) AS ultimo,
                            COUNT(DISTINCT usuario_id) AS usuarios, COUNT(DISTINCT establecimiento_id) AS ipress,
                            SUM(resuelto_en IS NULL) AS pendientes, MAX(resuelto_en) AS resuelto_en
                       FROM error_registro $donde GROUP BY huella $tener) g
               JOIN error_registro r ON r.id = g.ultimo_id
              ORDER BY g.pendientes > 0 DESC, g.ultimo DESC
              LIMIT 300"
        );
        $consulta->execute($origen ? ['origen' => $origen] : []);
        return $consulta->fetchAll();
    }

    /** Grupos pendientes y resueltos, y ocurrencias de las últimas 24 horas. */
    public static function resumen(): array
    {
        return Database::conexion()->query(
            "SELECT COUNT(DISTINCT CASE WHEN resuelto_en IS NULL THEN huella END) AS pendientes,
                    COUNT(DISTINCT huella) - COUNT(DISTINCT CASE WHEN resuelto_en IS NULL THEN huella END) AS resueltos,
                    SUM(creado_en >= NOW() - INTERVAL 1 DAY) AS ultimas_24h
               FROM error_registro"
        )->fetch();
    }

    /** Para la insignia del menú. */
    public static function contarPendientes(): int
    {
        return (int) Database::conexion()->query('SELECT COUNT(DISTINCT huella) FROM error_registro WHERE resuelto_en IS NULL')->fetchColumn();
    }

    /** Una ocurrencia con los nombres de usuario, IPRESS y de quien lo resolvió. */
    public static function detalle(int $id): ?array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT r.*, u.nombre AS usuario_nombre, u.email AS usuario_email, u.rol AS usuario_rol,
                    COALESCE(es.nombre_corto, es.nombre) AS establecimiento_nombre, rp.nombre AS resuelto_por_nombre
               FROM error_registro r
               LEFT JOIN usuario u ON u.id = r.usuario_id
               LEFT JOIN establecimiento es ON es.id = r.establecimiento_id
               LEFT JOIN usuario rp ON rp.id = r.resuelto_por
              WHERE r.id = :id'
        );
        $consulta->execute(['id' => $id]);
        return $consulta->fetch() ?: null;
    }

    /** Ocurrencias del mismo error, la más reciente primero. */
    public static function ocurrencias(string $huella, int $limite = 50): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT r.id, r.creado_en, r.ruta, r.resuelto_en, u.nombre AS usuario_nombre, COALESCE(es.nombre_corto, es.nombre) AS establecimiento_nombre
               FROM error_registro r
               LEFT JOIN usuario u ON u.id = r.usuario_id
               LEFT JOIN establecimiento es ON es.id = r.establecimiento_id
              WHERE r.huella = :huella
              ORDER BY r.id DESC
              LIMIT ' . $limite
        );
        $consulta->execute(['huella' => $huella]);
        return $consulta->fetchAll();
    }

    public static function totalOcurrencias(string $huella): int
    {
        $consulta = Database::conexion()->prepare('SELECT COUNT(*) FROM error_registro WHERE huella = :huella');
        $consulta->execute(['huella' => $huella]);
        return (int) $consulta->fetchColumn();
    }

    /** Marca resueltas todas las ocurrencias pendientes del grupo. */
    public static function resolverGrupo(string $huella, int $usuarioId): int
    {
        $consulta = Database::conexion()->prepare(
            'UPDATE error_registro SET resuelto_en = CURRENT_TIMESTAMP, resuelto_por = :usuario WHERE huella = :huella AND resuelto_en IS NULL'
        );
        $consulta->execute(['usuario' => $usuarioId, 'huella' => $huella]);
        return $consulta->rowCount();
    }
}
