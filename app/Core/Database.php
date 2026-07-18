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
            } catch (PDOException $e) {
                error_log('Error de conexión a BD: ' . $e->getMessage());
                throw new PDOException('No se pudo conectar a la base de datos.');
            }
        }

        return self::$conexion;
    }
}
