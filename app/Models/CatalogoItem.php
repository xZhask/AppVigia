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

    /**
     * Igual que porCatalogo(), pero por nombre de catalogo en vez de id fijo
     * -- para catálogos como los de vacuna (PENDIENTES_POST_FASE5.md punto 2)
     * cuyo id no está pineado en el esquema base (a diferencia de sexo/si_no/
     * resultado_lab/tipo_muestra/tipo_prueba, sembrados con id explícito).
     */
    public static function porNombreCatalogo(string $nombre): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT ci.* FROM catalogo_item ci JOIN catalogo c ON c.id = ci.catalogo_id
             WHERE c.nombre = :nombre ORDER BY ci.orden, ci.id'
        );
        $consulta->execute(['nombre' => $nombre]);

        return $consulta->fetchAll();
    }
}
