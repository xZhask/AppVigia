<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $conexion = null;

    public static function conexion(): PDO
    {
        if (self::$conexion === null) {
            $config = require __DIR__ . '/../../config/config.php';
            $db = $config['db'];

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $db['host'],
                $db['puerto'],
                $db['nombre'],
                $db['charset']
            );

            try {
                self::$conexion = new PDO($dsn, $db['usuario'], $db['clave'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                // El charset del DSN ya fija la codificación de la conexión,
                // pero se deja explícito (con la collation del esquema) para
                // que quede a prueba de clientes/drivers que lo ignoren.
                self::$conexion->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (PDOException $e) {
                error_log('Error de conexión a BD: ' . $e->getMessage());
                throw new PDOException('No se pudo conectar a la base de datos.');
            }
        }

        return self::$conexion;
    }
}
