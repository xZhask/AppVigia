<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Distrito extends Model
{
    protected static string $tabla = 'distrito';

    public static function porProvincia(string $provinciaId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM distrito WHERE provincia_id = :prov ORDER BY nombre'
        );
        $consulta->execute(['prov' => $provinciaId]);

        return $consulta->fetchAll();
    }

    /**
     * Busca por id preservando el string tal cual (a diferencia de
     * Model::buscar(int), que trunca el cero inicial de departamentos 01-09
     * y deja de encontrar la fila con sentencias preparadas reales).
     */
    public static function buscarPorId(string $id): ?array
    {
        $consulta = Database::conexion()->prepare('SELECT * FROM distrito WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }
}
