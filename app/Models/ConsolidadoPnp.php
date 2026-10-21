<?php
namespace App\Models;

/**
 * "Registro semanal de notificación epidemiológica consolidada" del Área de
 * Epidemiología PNP (hoja CONSOLIDADA TITULAR, 2026-10-21). Decisión del
 * usuario: cada caso se registra (registro de otras enfermedades; el cólera,
 * en la ficha A00) y el consolidado los cuenta. Qué códigos van en cada grupo
 * lo dice cie10_vigilancia.consolidado.
 *
 * Tres tablas, con una fila por situación (T titular, F familiar; vacía si es
 * particular), provincia y distrito de residencia:
 *   - EDA y cólera: diarrea aguda, sospechosos de cólera y disentería; casos,
 *     defunciones y hospitalizados por <1a, 1-4a y 5a+.
 *   - IRA, asma y SOB: IRA (no neumonía), neumonía, neumonía grave /
 *     enfermedad muy grave (NG+EMG), hospitalizados NG+EMG, defunciones
 *     intra y extrahospitalarias (N+NG+EMG) en menores de 5 años; SOB y asma
 *     por <2a, 2-4a y 5a+.
 *   - Neumonía de 5 años a más: casos, hospitalizados y defunciones intra y
 *     extrahospitalarias por 5-9, 10-19, 20-59 y 60+.
 * Los casos descartados no cuentan.
 */
class ConsolidadoPnp
{
    public const NOMBRE = 'Registro semanal de notificación epidemiológica consolidada';

    /**
     * Columnas de cada tabla, como en el Excel del área: grupo => [subgrupo
     * => [etiqueta => clave]] (EDA, tres niveles) o grupo => [etiqueta =>
     * clave] (IRA y neumonía, dos niveles). Las comparten la pantalla y el CSV.
     */
    public static function columnas(): array
    {
        $edadesEda = ['<1a' => 'm1', '1-4a' => 'a1_4', '5a+' => 'a5'];
        $eda = [];
        foreach (['EDA' => 'Diarrea aguda (no incluye sospechosos de cólera)', 'COLERA' => 'Sospechosos de cólera', 'DISENTERIA' => 'Disentería'] as $grupo => $titulo) {
            foreach (['casos' => 'N.° de casos', 'def' => 'N.° de def.', 'hosp' => 'N.° de hosp.'] as $medida => $subtitulo) {
                $eda[$titulo][$subtitulo] = array_map(fn($rango) => "$grupo.$medida.$rango", $edadesEda);
            }
        }
        $menores5 = fn(string $prefijo) => array_map(fn($rango) => "$prefijo.$rango", ['<2m' => 'm0_1', '2-11m' => 'm2_11', '1-4a' => 'a1_4']);
        $sobAsma = fn(string $grupo) => array_map(fn($rango) => "$grupo.casos.$rango", ['<2a' => 'a0_1', '2-4a' => 'a2_4', '5a+' => 'a5']);
        $neumonia = [];
        foreach (['a5_9' => '5 - 9 años', 'a10_19' => '10 - 19 años', 'a20_59' => '20 - 59 años', 'a60' => '60 años a más'] as $rango => $titulo) {
            $neumonia[$titulo] = ['Casos' => "casos.$rango", 'Hosp.' => "hosp.$rango", 'Def. int.' => "def_int.$rango", 'Def. ext.' => "def_ext.$rango"];
        }

        return [
            'eda' => $eda,
            'ira' => [
                'Casos de IRA (no neumonía)' => ['<2m' => 'IRA.casos.m0_1', '2-11m' => 'IRA.casos.m2_11', '1-4a' => 'IRA.casos.a1_4', '5a+' => 'IRA.casos.a5'],
                'Casos de neumonía' => ['2-11m' => 'N.casos.m2_11', '1-4a' => 'N.casos.a1_4'],
                'Neumonía grave / enf. muy grave' => $menores5('NG.casos'),
                'Hospitalizados NG + EMG' => $menores5('NG.hosp'),
                'Defunción intrahospitalaria (N + NG + EMG)' => $menores5('N.def_int'),
                'Defunción extrahospitalaria (N + NG + EMG)' => $menores5('N.def_ext'),
                'Casos de SOB' => $sobAsma('SOBA'),
                'Casos de asma' => $sobAsma('ASMA'),
            ],
            'neumonia' => $neumonia,
        ];
    }

    /** Claves de columna de una tabla, en orden (aplana columnas()). */
    public static function clavesDe(array $grupos): array
    {
        $claves = [];
        array_walk_recursive($grupos, function ($clave) use (&$claves) {
            $claves[] = $clave;
        });

        return $claves;
    }

    /**
     * @param array{anio: int, semana: int, establecimiento_id: ?int, usuario: array} $filtros
     * @return array{eda: array, ira: array, neumonia: array, total: int}
     */
    public static function semana(array $filtros): array
    {
        $filas = FormatoPnp::casos(
            $filtros,
            "((e.cie10 = :otras AND c.cie10_dx IS NOT NULL) OR e.cie10 = 'A00') AND c.clasificacion <> 'DESCARTADO'",
            ['otras' => Cie10Vigilancia::REGISTRO_OTRAS]
        );
        $valores = FormatoPnp::valores(array_column($filas, 'id'));

        $tablas = ['eda' => [], 'ira' => [], 'neumonia' => []];
        $total = 0;
        foreach ($filas as $fila) {
            $grupo = $fila['modalidad'] === 'FICHA' ? 'COLERA' : Cie10Vigilancia::grupoConsolidado((string) $fila['cie10_dx']);
            $edad = self::edad($fila);
            if ($grupo === null || $edad === null) {
                continue;
            }
            $v = $valores[(int) $fila['id']] ?? [];
            $fallecido = (bool) $fila['fallecido'];
            $lugarDefuncion = $v['reg_lugar_defuncion']['valor'] ?? '';
            $intra = $fallecido && $lugarDefuncion === 'INTRAHOSPITALARIA';
            $extra = $fallecido && $lugarDefuncion === 'EXTRAHOSPITALARIA';
            $hospitalizado = (bool) $fila['hospitalizado'] || $intra;
            $clave = implode('|', [self::situacion($fila), (string) $fila['provincia_residencia'], (string) $fila['distrito_residencia']]);
            $sumar = function (string $tabla, string $columna) use (&$tablas, $clave): void {
                $tablas[$tabla][$clave][$columna] = ($tablas[$tabla][$clave][$columna] ?? 0) + 1;
            };
            $total++;

            if (in_array($grupo, ['EDA', 'COLERA', 'DISENTERIA'], true)) {
                $rango = $edad['anios'] < 1 ? 'm1' : ($edad['anios'] < 5 ? 'a1_4' : 'a5');
                $sumar('eda', "$grupo.casos.$rango");
                if ($fallecido) {
                    $sumar('eda', "$grupo.def.$rango");
                }
                if ($hospitalizado) {
                    $sumar('eda', "$grupo.hosp.$rango");
                }
            } elseif ($grupo === 'IRA') {
                $sumar('ira', 'IRA.casos.' . self::rangoMenor5($edad, true));
            } elseif ($grupo === 'SOBA' || $grupo === 'ASMA') {
                $sumar('ira', "$grupo.casos." . ($edad['anios'] < 2 ? 'a0_1' : ($edad['anios'] < 5 ? 'a2_4' : 'a5')));
            } elseif ($grupo === 'NEUMONIA' && $edad['anios'] < 5) {
                $rango = self::rangoMenor5($edad, false);
                // En un menor de 2 meses toda neumonía es grave (AIEPI): el
                // formato no tiene columna de neumonía <2m.
                $grave = $rango === 'm0_1' || ($v['reg_neumonia_grave']['valor'] ?? '') === 'SI';
                $sumar('ira', ($grave ? 'NG' : 'N') . '.casos.' . $rango);
                if ($grave && $hospitalizado) {
                    $sumar('ira', 'NG.hosp.' . $rango);
                }
                if ($intra || $extra) {
                    $sumar('ira', 'N.' . ($intra ? 'def_int' : 'def_ext') . '.' . $rango);
                }
            } elseif ($grupo === 'NEUMONIA') {
                $rango = $edad['anios'] < 10 ? 'a5_9' : ($edad['anios'] < 20 ? 'a10_19' : ($edad['anios'] < 60 ? 'a20_59' : 'a60'));
                $sumar('neumonia', "casos.$rango");
                if ($hospitalizado) {
                    $sumar('neumonia', "hosp.$rango");
                }
                if ($intra || $extra) {
                    $sumar('neumonia', ($intra ? 'def_int' : 'def_ext') . ".$rango");
                }
            }
        }

        foreach ($tablas as $nombre => $porFila) {
            ksort($porFila);
            $tablas[$nombre] = [];
            foreach ($porFila as $clave => $columnas) {
                [$situacion, $provincia, $distrito] = explode('|', $clave);
                $tablas[$nombre][] = ['situacion' => $situacion, 'provincia' => $provincia, 'distrito' => $distrito, 'n' => $columnas];
            }
        }

        return $tablas + ['total' => $total];
    }

    /** <2m, 2-11m, 1-4a (y 5a+ si $con5): claves de columna. */
    private static function rangoMenor5(array $edad, bool $con5): string
    {
        if ($edad['anios'] >= 5) {
            return $con5 ? 'a5' : 'a1_4';
        }
        if ($edad['anios'] >= 1) {
            return 'a1_4';
        }

        return $edad['meses'] < 2 ? 'm0_1' : 'm2_11';
    }

    private static function situacion(array $fila): string
    {
        return ['EFECTIVO' => 'T', 'DERECHOHABIENTE' => 'F'][$fila['condicion'] ?? ''] ?? '';
    }

    /**
     * Edad a la fecha de notificación en años cumplidos y meses totales; sin
     * fecha de nacimiento, la edad declarada. null si no hay ninguna.
     *
     * @return array{anios: int, meses: int}|null
     */
    private static function edad(array $fila): ?array
    {
        $nacimiento = $fila['fecha_nac'] ? \DateTime::createFromFormat('Y-m-d', substr((string) $fila['fecha_nac'], 0, 10)) : false;
        $referencia = \DateTime::createFromFormat('Y-m-d', (string) $fila['fecha_notif']);
        if ($nacimiento && $referencia && $nacimiento <= $referencia) {
            $diferencia = $nacimiento->diff($referencia);

            return ['anios' => $diferencia->y, 'meses' => $diferencia->y * 12 + $diferencia->m];
        }
        if ($fila['edad_valor'] === null) {
            return null;
        }
        $valor = (int) $fila['edad_valor'];
        $meses = match ($fila['edad_unidad']) {
            'ANIOS' => $valor * 12,
            'MESES' => $valor,
            default => 0, // días, horas, minutos
        };

        return ['anios' => intdiv($meses, 12), 'meses' => $meses];
    }
}
