<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CatalogoItem extends Model
{
    protected static string $tabla = 'catalogo_item';

    public static function porCatalogo(int $catalogoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM catalogo_item WHERE catalogo_id = :cat ORDER BY orden, id'
        );
        $consulta->execute(['cat' => $catalogoId]);

        return $consulta->fetchAll();
    }
}
