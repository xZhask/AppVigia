<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CasoExamenAuxiliar extends Model
{
    protected static string $tabla = 'caso_examen_auxiliar';

    public const COLUMNAS = ['grupo_sanguineo', 'plaquetas', 'hematies', 'tgo', 'tgp', 'fosfatasa_alcalina', 'bilirrubina_directa', 'bilirrubina_indirecta', 'bilirrubina_total', 'urea', 'glucosa', 'creatinina', 'leucocitos_totales', 'segmentados', 'abastonados', 'linfocitos', 'monocitos', 'eosinofilos', 'basofilos', 'blastos', 'aglutinacion_tifico_o', 'aglutinacion_tifico_h', 'paratifico_a', 'paratifico_b', 'brucellas'];

    /**
     * Reemplaza todos los registros de exámenes auxiliares de un caso (A44).
     * Debe ejecutarse dentro de la transacción abierta por el llamador.
     */
    public static function reemplazarTodos(int $casoId, array $filas): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM caso_examen_auxiliar WHERE caso_id = :caso')->execute(['caso' => $casoId]);

        $columnas = array_merge(['caso_id', 'fecha'], self::COLUMNAS);
        $marcadores = implode(', ', array_map(fn($c) => ":{$c}", $columnas));
        $consulta = $pdo->prepare('INSERT INTO caso_examen_auxiliar (' . implode(', ', $columnas) . ') VALUES (' . $marcadores . ')');

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
            'SELECT * FROM caso_examen_auxiliar WHERE caso_id = :caso ORDER BY fecha IS NULL, fecha, id'
        );
        $consulta->execute(['caso' => $casoId]);

        return $consulta->fetchAll();
    }
}
