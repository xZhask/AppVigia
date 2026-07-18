<?php
namespace App\Core;

/**
 * Funciones estadísticas puras (sin BD) para la curva epidemiológica.
 */
class Estadistica
{
    /**
     * Percentil por interpolación lineal (método "linear interpolation
     * between closest ranks", el mismo que usa Excel/NumPy por defecto).
     */
    public static function percentil(array $valores, float $p): float
    {
        if (empty($valores)) {
            return 0.0;
        }

        $ordenados = $valores;
        sort($ordenados, SORT_NUMERIC);
        $n = count($ordenados);

        if ($n === 1) {
            return (float) $ordenados[0];
        }

        $rango = ($p / 100) * ($n - 1);
        $indiceBajo = (int) floor($rango);
        $indiceAlto = (int) ceil($rango);

        if ($indiceBajo === $indiceAlto) {
            return (float) $ordenados[$indiceBajo];
        }

        $fraccion = $rango - $indiceBajo;

        return $ordenados[$indiceBajo] + $fraccion * ($ordenados[$indiceAlto] - $ordenados[$indiceBajo]);
    }

    /**
     * @return array{promedio: float, desviacion: float}
     */
    public static function promedioDesviacion(array $valores): array
    {
        $n = count($valores);
        if ($n === 0) {
            return ['promedio' => 0.0, 'desviacion' => 0.0];
        }

        $promedio = array_sum($valores) / $n;

        if ($n === 1) {
            return ['promedio' => $promedio, 'desviacion' => 0.0];
        }

        $sumaCuadrados = 0.0;
        foreach ($valores as $valor) {
            $sumaCuadrados += ($valor - $promedio) ** 2;
        }

        return ['promedio' => $promedio, 'desviacion' => sqrt($sumaCuadrados / $n)];
    }
}
