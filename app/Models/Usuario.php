<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Usuario extends Model
{
    protected static string $tabla = 'usuario';

    public static function buscarPorEmail(string $email): ?array
    {
        $filas = self::buscarParaSesion('u.email = :valor', $email);

        return $filas[0] ?? null;
    }

    /**
     * Usuario cuyo documento (el de su persona) es $numDoc, para el ingreso
     * con DNI y contraseña. Si dos usuarios comparten el número con tipos
     * de documento distintos (un DNI y un carné de extranjería iguales, por
     * ejemplo), no devuelve ninguno: entrar con uno de ellos sería adivinar.
     */
    public static function buscarPorDocumento(string $numDoc): ?array
    {
        $filas = self::buscarParaSesion('p.num_doc = :valor', $numDoc);

        return count($filas) === 1 ? $filas[0] : null;
    }

    /** $condicion es siempre un literal de esta clase, nunca del usuario. */
    private static function buscarParaSesion(string $condicion, string $valor): array
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
             WHERE ' . $condicion . '
        ');
        $consulta->execute(['valor' => $valor]);
        $filas = $consulta->fetchAll();

        foreach ($filas as &$fila) {
            if ($fila['persona_id']) {
                // Mapear aliases a las claves esperadas por nombreCompletoPnp()
                $fila['nombres'] = $fila['persona_nombres'] ?? '';
                $fila['nombre'] = Persona::nombreCompletoPnp($fila);
            }
        }

        return $filas;
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

    /** true si otro usuario ya tiene una persona con ese número de documento. */
    public static function existeDocumento(string $numDoc, ?int $excluirId = null): bool
    {
        $sql = 'SELECT u.id FROM usuario u JOIN persona p ON p.id = u.persona_id WHERE p.num_doc = :num_doc';
        $parametros = ['num_doc' => $numDoc];

        if ($excluirId !== null) {
            $sql .= ' AND u.id != :id';
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
        $sql = 'SELECT u.*, e.nombre AS establecimiento_nombre, e.nombre_corto AS establecimiento_corto, e.activo AS establecimiento_activo,
                       e.red_id, r.nombre AS red_nombre,
                       p.tipo_doc AS persona_tipo_doc, p.num_doc AS persona_num_doc,
                       p.apellido_paterno, p.apellido_materno,
                       p.nombres AS persona_nombres, p.condicion,
                       p.grado_id, p.categoria_pnp, p.situacion_pnp, p.cip,
                       p.unidad_id,
                       g.abreviatura AS grado_abreviatura, g.nombre AS grado_nombre
                  FROM usuario u
             LEFT JOIN establecimiento e ON e.id = u.establecimiento_id
             LEFT JOIN red_salud r ON r.id = e.red_id
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
