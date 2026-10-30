<?php
namespace App\Models;

use App\Core\Database;

/**
 * Consolidado de Reportes (rediseño 2026-10-17): tabla cruzada de fichas no
 * anuladas por dos dimensiones (filas × columnas), dentro del alcance del
 * usuario (Caso::alcance) y de un rango de SE.
 */
class Consolidado
{
    /** Etapas de vida del MINSA. */
    public const ETAPAS = [
        'NINO'         => 'Niño (0–11 años)',
        'ADOLESCENTE'  => 'Adolescente (12–17)',
        'JOVEN'        => 'Joven (18–29)',
        'ADULTO'       => 'Adulto (30–59)',
        'ADULTO_MAYOR' => 'Adulto mayor (60 a más)',
    ];

    /**
     * Dimensiones: etiqueta, expresión SQL del valor y, si tiene, la lista
     * fija de valores (que se muestran siempre, aunque estén en cero).
     * "columna" = se puede usar como columnas (pocos valores).
     */
    public static function dimensiones(): array
    {
        $edad = "COALESCE(TIMESTAMPDIFF(YEAR, p.fecha_nac, c.fecha_notif),
                          CASE WHEN c.edad_valor IS NULL THEN NULL WHEN c.edad_unidad = 'ANIOS' THEN c.edad_valor ELSE 0 END)";
        return [
            'se'              => ['etiqueta' => 'Semana epidemiológica', 'sql' => "CONCAT(c.anio_epi, '-', LPAD(c.semana_epi, 2, '0'))", 'columna' => false],
            'enfermedad'      => ['etiqueta' => 'Enfermedad', 'sql' => 'e.nombre', 'columna' => false],
            // Diagnóstico (2026-10-30): separa lo que una ficha agrupa (A97:
            // dengue, chikungunya, zika; B05: sarampión, rubéola). El código
            // sale del diagnóstico propio (registros PNP) o de la opción elegida
            // que lo trae ("Dengue grave (CIE-10: A97.2)", como en
            // FormatoPnp::filaIndividual); si no, "FICHA:<cie10>" = la ficha.
            'diagnostico'     => ['etiqueta' => 'Diagnóstico (CIE-10)', 'columna' => false,
                'sql' => "COALESCE(NULLIF(c.cie10_dx, ''),
                           (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(ci.etiqueta, '(CIE-10: ', -1), ')', 1)
                              FROM caso_valor cv
                              JOIN campo_def cdx ON cdx.id = cv.campo_def_id
                              JOIN catalogo_item ci ON ci.catalogo_id = cdx.catalogo_id AND ci.valor = cv.valor
                             WHERE cv.caso_id = c.id AND ci.etiqueta LIKE '%(CIE-10: %'
                          ORDER BY cdx.orden LIMIT 1),
                           CONCAT('FICHA:', e.cie10))"],
            'red'             => ['etiqueta' => 'Red', 'sql' => "COALESCE(r.nombre, 'Sin red')", 'columna' => false],
            'establecimiento' => ['etiqueta' => 'Establecimiento', 'sql' => 'est.nombre', 'columna' => false],
            'distrito'        => ['etiqueta' => 'Distrito de residencia', 'sql' => "COALESCE(CONCAT(d.nombre, ' (', pr.nombre, ')'), 'Sin dato')", 'columna' => false],
            'etapa'           => ['etiqueta' => 'Etapa de vida', 'columna' => true,
                'sql' => "CASE WHEN $edad IS NULL THEN 'SIN_DATO' WHEN $edad < 12 THEN 'NINO' WHEN $edad < 18 THEN 'ADOLESCENTE'
                               WHEN $edad < 30 THEN 'JOVEN' WHEN $edad < 60 THEN 'ADULTO' ELSE 'ADULTO_MAYOR' END",
                'valores' => self::ETAPAS],
            'sexo'            => ['etiqueta' => 'Sexo', 'sql' => "COALESCE(p.sexo, 'SIN_DATO')", 'columna' => true,
                'valores' => ['M' => 'Masculino', 'F' => 'Femenino']],
            'condicion'       => ['etiqueta' => 'Condición del paciente', 'sql' => 'p.condicion', 'columna' => true,
                'valores' => ['EFECTIVO' => 'Efectivo PNP', 'DERECHOHABIENTE' => 'Derechohabiente', 'PARTICULAR' => 'Particular']],
            'clasificacion'   => ['etiqueta' => 'Clasificación', 'sql' => 'c.clasificacion', 'columna' => true,
                'valores' => ['SOSPECHOSO' => 'Sospechoso', 'PROBABLE' => 'Probable', 'CONFIRMADO' => 'Confirmado', 'DESCARTADO' => 'Descartado']],
            'categoria_pnp'   => ['etiqueta' => 'Categoría PNP', 'sql' => "COALESCE(p.categoria_pnp, 'SIN_DATO')", 'columna' => true,
                'valores' => ['ARMAS' => 'Armas', 'SERVICIOS' => 'Servicios', 'ASIMILADO' => 'Asimilado']],
            'nivel'           => ['etiqueta' => 'Nivel de grado PNP', 'sql' => "COALESCE(g.nivel, 'SIN_DATO')", 'columna' => true,
                'valores' => ['OFICIAL_GENERAL' => 'Oficial general', 'OFICIAL_SUPERIOR' => 'Oficial superior', 'OFICIAL_SUBALTERNO' => 'Oficial subalterno',
                              'SUBOFICIAL' => 'Suboficial', 'EMPLEADO_CIVIL' => 'Empleado civil', 'CADETE' => 'Cadete', 'ALUMNO' => 'Alumno']],
        ];
    }

    /** Etiqueta para mostrar de un valor de una dimensión. */
    public static function etiqueta(string $dimension, string $valor, bool $variosAnios = false): string
    {
        if ($valor === 'SIN_DATO') {
            return 'Sin dato';
        }
        if ($dimension === 'se') {
            [$anio, $semana] = explode('-', $valor);
            return 'SE ' . (int) $semana . ($variosAnios ? ' · ' . $anio : '');
        }
        if ($dimension === 'clasificacion') {
            return datosClasificacion($valor)['etiqueta'];
        }
        if ($dimension === 'establecimiento') {
            return nombreEnOracion($valor);
        }
        if ($dimension === 'diagnostico') {
            return self::etiquetaDiagnostico($valor);
        }
        return self::dimensiones()[$dimension]['valores'][$valor] ?? $valor;
    }

    /**
     * "A92.5 · Chikungunya grave": el texto de la opción de la ficha (el mismo
     * que eligió quien registró) o, si no viene de una opción, la descripción
     * del catálogo CIE-10. "FICHA:A97" = la ficha sin diagnóstico específico;
     * en las que sí lo piden (A97, B05) es la opción genérica ("Otras
     * arbovirosis"), que se muestra aparte para no sumarla al específico.
     */
    private static function etiquetaDiagnostico(string $valor): string
    {
        static $opciones = null, $fichas = null, $conOpcion = null;
        if ($opciones === null) {
            $opciones = $fichas = $conOpcion = [];
            $consulta = Database::conexion()->query(
                "SELECT DISTINCT e.cie10, ci.etiqueta FROM catalogo_item ci
                   JOIN campo_def cd ON cd.catalogo_id = ci.catalogo_id
                   JOIN seccion_def s ON s.id = cd.seccion_id
                   JOIN enfermedad e ON e.id = s.enfermedad_id
                  WHERE ci.etiqueta LIKE '%(CIE-10: %'"
            );
            foreach ($consulta->fetchAll() as $fila) {
                if (preg_match('/^(.*?)\s*\(CIE-10: ([A-Z0-9.]+)\)/', $fila['etiqueta'], $m)) {
                    $opciones[$m[2]] ??= $m[1];
                    $conOpcion[$fila['cie10']] = true;
                }
            }
            foreach (Database::conexion()->query('SELECT cie10, nombre FROM enfermedad')->fetchAll() as $fila) {
                $fichas[$fila['cie10']] = $fila['nombre'];
            }
        }

        if (str_starts_with($valor, 'FICHA:')) {
            $cie10 = substr($valor, 6);
            return $cie10 . ' · ' . ($fichas[$cie10] ?? $cie10) . (isset($conOpcion[$cie10]) ? ' (otro, sin código específico)' : '');
        }
        return $valor . ' · ' . ($opciones[$valor] ?? descripcionCie10($valor) ?? $fichas[$valor] ?? 'Sin descripción');
    }

    /**
     * Tabla cruzada. $columna null = solo totales por fila.
     *
     * @return array{filas: string[], columnas: string[], celdas: array<string, array<string, int>>, totalFila: array, totalColumna: array, total: int}
     */
    public static function cruzar(array $usuario, ?int $enfermedadId, array $desde, array $hasta, string $fila, ?string $columna): array
    {
        $dims = self::dimensiones();
        [$condiciones, $parametros] = Caso::alcance($usuario, ['enfermedad_id' => $enfermedadId ?: '', 'se_desde' => $desde, 'se_hasta' => $hasta]);
        $condiciones[] = 'c.anulado = 0';
        $sqlColumna = $columna ? $dims[$columna]['sql'] : "''";
        $sql = 'SELECT ' . $dims[$fila]['sql'] . ' AS f, ' . $sqlColumna . ' AS k, COUNT(*) AS n ' . Caso::FROM_LISTADO . '
             LEFT JOIN distrito d   ON d.id = p.distrito_id
             LEFT JOIN provincia pr ON pr.id = d.provincia_id
             LEFT JOIN grado_pnp g  ON g.id = p.grado_id
                 WHERE ' . implode(' AND ', $condiciones) . ' GROUP BY f, k';
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        $celdas = [];
        $totalFila = [];
        $totalColumna = [];
        $total = 0;
        foreach ($consulta->fetchAll() as $r) {
            $f = (string) $r['f'];
            $k = (string) $r['k'];
            $n = (int) $r['n'];
            $celdas[$f][$k] = ($celdas[$f][$k] ?? 0) + $n;
            $totalFila[$f] = ($totalFila[$f] ?? 0) + $n;
            $totalColumna[$k] = ($totalColumna[$k] ?? 0) + $n;
            $total += $n;
        }

        // Columnas: las fijas de la dimensión siempre (estructura estable del
        // Excel), más las que traigan los datos (otras clasificaciones, "Sin dato").
        $columnas = [''];
        if ($columna) {
            $columnas = array_keys($dims[$columna]['valores'] ?? []);
            $extra = array_diff(array_keys($totalColumna), $columnas);
            if ($columna === 'clasificacion') {
                $extra = array_values(array_intersect(array_keys(CATALOGO_CLASIFICACION), $extra));
            }
            sort($extra);
            $columnas = array_merge($columnas, array_values(array_diff($extra, ['SIN_DATO'])), in_array('SIN_DATO', $extra, true) ? ['SIN_DATO'] : []);
        }

        // Filas: SE en orden y completas; dimensiones con valores fijos en su
        // orden (y en cero si no hay); el resto, de más a menos casos.
        if ($fila === 'se') {
            $filas = array_map(fn($s) => sprintf('%d-%02d', $s['anio'], $s['semana']), semanasEnRango($desde[0], $desde[1], $hasta[0], $hasta[1]));
        } elseif (isset($dims[$fila]['valores'])) {
            $filas = array_keys($dims[$fila]['valores']);
            $extra = array_diff(array_keys($totalFila), $filas);
            if ($fila === 'clasificacion') {
                $extra = array_values(array_intersect(array_keys(CATALOGO_CLASIFICACION), $extra));
            }
            $filas = array_merge($filas, array_values(array_diff($extra, ['SIN_DATO'])), in_array('SIN_DATO', $extra, true) ? ['SIN_DATO'] : []);
        } else {
            $filas = array_keys($totalFila);
            usort($filas, fn($a, $b) => [$a === 'SIN_DATO' || $a === 'Sin dato', -$totalFila[$a], $a] <=> [$b === 'SIN_DATO' || $b === 'Sin dato', -$totalFila[$b], $b]);
        }

        return compact('filas', 'columnas', 'celdas', 'totalFila', 'totalColumna', 'total');
    }

    /** Etapa de vida × sexo, para la pirámide del gráfico. */
    public static function piramide(array $usuario, ?int $enfermedadId, array $desde, array $hasta): array
    {
        $cruce = self::cruzar($usuario, $enfermedadId, $desde, $hasta, 'etapa', 'sexo');
        $resultado = [];
        foreach (array_keys(self::ETAPAS) as $etapa) {
            $resultado[] = ['etapa' => self::ETAPAS[$etapa], 'M' => $cruce['celdas'][$etapa]['M'] ?? 0, 'F' => $cruce['celdas'][$etapa]['F'] ?? 0];
        }
        return $resultado;
    }
}
