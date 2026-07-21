<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Usuario extends Model
{
    protected static string $tabla = 'usuario';

    public static function buscarPorEmail(string $email): ?array
    {
        $consulta = Database::conexion()->prepare('
            SELECT u.*, e.nombre AS establecimiento_nombre,
                   p.tipo_doc AS persona_tipo_doc, p.num_doc AS persona_num_doc,
                   p.apellido_paterno, p.apellido_materno,
                   p.nombres AS persona_nombres, p.condicion,
                   p.grado_id, p.categoria_pnp, p.situacion_pnp, p.cip,
                   p.unidad_id,
                   g.abreviatura AS grado_abreviatura, g.nombre AS grado_nombre
              FROM usuario u
         LEFT JOIN establecimiento e ON e.id = u.establecimiento_id
         LEFT JOIN persona p ON p.id = u.persona_id
         LEFT JOIN grado_pnp g ON g.id = p.grado_id
             WHERE u.email = :email
        ');
        $consulta->execute(['email' => $email]);
        $fila = $consulta->fetch();

        if ($fila && $fila['persona_id']) {
            // Mapear aliases a las claves esperadas por nombreCompletoPnp()
            $fila['nombres'] = $fila['persona_nombres'] ?? '';
            $fila['nombre'] = Persona::nombreCompletoPnp($fila);
        }

        return $fila ?: null;
    }

    public static function existeEmail(string $email, ?int $excluirId = null): bool
    {
        $sql = 'SELECT id FROM usuario WHERE email = :email';
        $parametros = ['email' => $email];

        if ($excluirId !== null) {
            $sql .= ' AND id != :id';
            $parametros['id'] = $excluirId;
        }

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        return (bool) $consulta->fetch();
    }

    public static function existePersona(int $personaId, ?int $excluirId = null): bool
    {
        $sql = 'SELECT id FROM usuario WHERE persona_id = :persona_id';
        $parametros = ['persona_id' => $personaId];

        if ($excluirId !== null) {
            $sql .= ' AND id != :id';
            $parametros['id'] = $excluirId;
        }

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        return (bool) $consulta->fetch();
    }

    public static function conEstablecimiento(): array
    {
        $sql = 'SELECT u.*, e.nombre AS establecimiento_nombre,
                       p.tipo_doc AS persona_tipo_doc, p.num_doc AS persona_num_doc,
                       p.apellido_paterno, p.apellido_materno,
                       p.nombres AS persona_nombres, p.condicion,
                       p.grado_id, p.categoria_pnp, p.situacion_pnp, p.cip,
                       p.unidad_id,
                       g.abreviatura AS grado_abreviatura, g.nombre AS grado_nombre
                  FROM usuario u
             LEFT JOIN establecimiento e ON e.id = u.establecimiento_id
             LEFT JOIN persona p ON p.id = u.persona_id
             LEFT JOIN grado_pnp g ON g.id = p.grado_id
              ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres';

        $filas = Database::conexion()->query($sql)->fetchAll();
        foreach ($filas as &$fila) {
            if ($fila['persona_id']) {
                $fila['nombres'] = $fila['persona_nombres'] ?? '';
                $fila['nombre'] = Persona::nombreCompletoPnp($fila);
            }
        }
        return $filas;
    }
}
