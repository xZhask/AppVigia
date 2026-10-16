<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Catálogo CIE-10 oficial del MINSA (tabla `cie10`, 2026-10-16; ver
 * sql/migraciones/add_cie10.php). Lo usan los campos TEXTO con
 * "formato": "cie10".
 */
class Cie10 extends Model
{
    protected static string $tabla = 'cie10';

    /** Código sin puntos ni espacios, en mayúsculas (la columna `clave`). */
    public static function clave(string $codigo): string
    {
        return strtoupper(preg_replace('/[\s.]/', '', $codigo));
    }

    /** Fila del catálogo para lo escrito ("p219", "P21.9", "a18 51"), o null. */
    public static function buscarPorCodigo(string $codigo): ?array
    {
        $clave = self::clave($codigo);
        if ($clave === '') {
            return null;
        }
        $consulta = Database::conexion()->prepare('SELECT * FROM cie10 WHERE clave = ?');
        $consulta->execute([$clave]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    /** ¿El catálogo está cargado? Sin él solo se valida la forma del código. */
    public static function hayCatalogo(): bool
    {
        static $hay = null;
        if ($hay === null) {
            try {
                $hay = (bool) Database::conexion()->query('SELECT 1 FROM cie10 LIMIT 1')->fetchColumn();
            } catch (\PDOException) {
                $hay = false;
            }
        }

        return $hay;
    }

    /**
     * Códigos activos para el buscador: primero los que empiezan por lo
     * escrito (como código), luego los que lo contienen en la descripción
     * (sin distinguir tildes ni mayúsculas, por la colación).
     *
     * @return array<int, array{codigo: string, descripcion: string}>
     */
    public static function sugerencias(string $texto, int $limite = 15): array
    {
        $texto = trim($texto);
        if (mb_strlen($texto) < 2) {
            return [];
        }
        $consulta = Database::conexion()->prepare(
            'SELECT codigo, descripcion FROM cie10
              WHERE activo = 1 AND (clave LIKE :prefijo OR descripcion LIKE :contiene)
              ORDER BY (clave LIKE :prefijo2) DESC, codigo
              LIMIT ' . max(1, min(50, $limite))
        );
        $prefijo = self::clave($texto) . '%';
        $consulta->execute([
            'prefijo'  => $prefijo,
            'prefijo2' => $prefijo,
            'contiene' => '%' . $texto . '%',
        ]);

        return $consulta->fetchAll();
    }
}
