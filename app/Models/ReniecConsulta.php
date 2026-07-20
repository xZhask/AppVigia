<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Trazabilidad institucional de cada consulta externa a RENIEC: quién,
 * cuándo, con qué DNI y si hubo resultado.
 */
class ReniecConsulta extends Model
{
    protected static string $tabla = 'reniec_consulta';

    public static function registrar(int $usuarioId, string $dni, bool $encontrado): void
    {
        self::crear([
            'usuario_id' => $usuarioId,
            'dni'        => $dni,
            'encontrado' => $encontrado ? 1 : 0,
        ]);
    }
}
