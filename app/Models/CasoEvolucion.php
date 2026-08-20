<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoEvolucion extends Model
{
    protected static string $tabla = 'caso_evolucion';

    private const ANTIBIOTICOS = ['penicilina', 'cloranfenicol', 'rifampicina', 'ciprofloxacina', 'eritromicina', 'cotrimoxazol', 'ceftriaxona', 'otros'];

    /**
     * Reemplaza todos los registros de evolución clínica de un caso (A44).
     * Debe ejecutarse dentro de la transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_evolucion WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $columnas = ['caso_id', 'fecha', 'temperatura', 'hemoglobina', 'hematocrito', 'transfusiones', 'frotis', 'hemocultivo_muestra_tomada', 'hemocultivo_fecha_toma', 'hemocultivo_resultado', 'hemocultivo_fecha_resultado'];
        foreach (self::ANTIBIOTICOS as $atb) {
            $columnas[] = "atb_{$atb}_usado";
            $columnas[] = "atb_{$atb}_dosis";
        }
        $columnas[] = 'atb_otros_especificar';

        $marcadores = implode(', ', array_map(fn($c) => ":{$c}", $columnas));
        $consulta = $pdo->prepare('INSERT INTO caso_evolucion (' . implode(', ', $columnas) . ') VALUES (' . $marcadores . ')');

        foreach ($filas as $fila) {
            $parametros = ['caso_id' => $casoId];
            foreach ($columnas as $col) {
                if ($col === 'caso_id') {
                    continue;
                }
                $parametros[$col] = $fila[$col] ?? null;
            }
            $consulta->execute($parametros);
        }
    }

    public static function porCaso(int $casoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM caso_evolucion WHERE caso_id = :caso ORDER BY fecha IS NULL, fecha, id'
        );
        $consulta->execute(['caso' => $casoId]);

        return $consulta->fetchAll();
    }
}
