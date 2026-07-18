<?php
namespace App\Core;

use PDO;

abstract class Model
{
    protected static string $tabla;
    protected static string $clave = 'id';

    public static function todos(string $orden = ''): array
    {
        $sql = 'SELECT * FROM ' . static::$tabla;
        if ($orden !== '') {
            $sql .= ' ORDER BY ' . $orden;
        }

        return Database::conexion()->query($sql)->fetchAll();
    }

    public static function buscar(int $id): ?array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM ' . static::$tabla . ' WHERE ' . static::$clave . ' = :id'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    public static function crear(array $datos): int
    {
        $columnas = array_keys($datos);
        $marcadores = array_map(fn($c) => ':' . $c, $columnas);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::$tabla,
            implode(', ', $columnas),
            implode(', ', $marcadores)
        );

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($datos);

        return (int) Database::conexion()->lastInsertId();
    }

    public static function actualizar(int $id, array $datos): void
    {
        $asignaciones = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($datos)));

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :id_registro',
            static::$tabla,
            $asignaciones,
            static::$clave
        );

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([...$datos, 'id_registro' => $id]);
    }

    public static function eliminar(int $id): void
    {
        $consulta = Database::conexion()->prepare(
            'DELETE FROM ' . static::$tabla . ' WHERE ' . static::$clave . ' = :id'
        );
        $consulta->execute(['id' => $id]);
    }
}
