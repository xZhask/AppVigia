<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use App\Core\ReniecService;

/**
 * Trazabilidad institucional de cada consulta externa a RENIEC: quién,
 * cuándo, con qué DNI y qué respondió el proveedor. Cada fila es una
 * llamada real (las que se evitan no se registran), así que también es la
 * cuenta de lo gastado del plan desde esta base.
 */
class ReniecConsulta extends Model
{
    protected static string $tabla = 'reniec_consulta';

    public static function registrar(int $usuarioId, string $dni, string $resultado): void
    {
        self::crear([
            'usuario_id' => $usuarioId,
            'dni'        => $dni,
            'encontrado' => $resultado === ReniecService::ENCONTRADO ? 1 : 0,
            'resultado'  => $resultado,
        ]);
    }

    /**
     * Por qué no conviene consultar ahora, o null si se puede. Los tres
     * motivos evitan gastar el plan en llamadas que no traerán nada.
     */
    public static function motivoParaNoConsultar(int $usuarioId, string $dni): ?string
    {
        $reniec = ReniecService::config();
        $pdo = Database::conexion();

        // Pausa general: el proveedor rechazó hace poco (cuota agotada o
        // token inválido) y lo hará igual para cualquier usuario.
        $horas = (int) $reniec['pausa_rechazo_horas'];
        if ($horas > 0) {
            $consulta = $pdo->prepare("SELECT 1 FROM reniec_consulta
                WHERE resultado = 'RECHAZADA' AND fecha >= NOW() - INTERVAL :horas HOUR LIMIT 1");
            $consulta->execute(['horas' => $horas]);
            if ($consulta->fetchColumn()) {
                return 'pausa';
            }
        }

        // Este DNI ya se consultó hace poco y RENIEC no lo tenía.
        $dias = (int) $reniec['recordar_no_encontrado_dias'];
        if ($dias > 0) {
            $consulta = $pdo->prepare("SELECT 1 FROM reniec_consulta
                WHERE dni = :dni AND resultado = 'NO_ENCONTRADO' AND fecha >= NOW() - INTERVAL :dias DAY LIMIT 1");
            $consulta->execute(['dni' => $dni, 'dias' => $dias]);
            if ($consulta->fetchColumn()) {
                return 'no_encontrado_reciente';
            }
        }

        // Tope por usuario y día, para que un solo usuario no agote el plan.
        $limite = (int) $reniec['limite_diario_usuario'];
        if ($limite > 0) {
            $consulta = $pdo->prepare('SELECT COUNT(*) FROM reniec_consulta
                WHERE usuario_id = :usuario AND fecha >= CURDATE()');
            $consulta->execute(['usuario' => $usuarioId]);
            if ((int) $consulta->fetchColumn() >= $limite) {
                return 'limite_diario';
            }
        }

        return null;
    }
}
