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

}
