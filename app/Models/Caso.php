<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Caso extends Model
{
    protected static string $tabla = 'caso';

    public static function contarTodos(): int
    {
        return (int) Database::conexion()->query('SELECT COUNT(*) FROM caso')->fetchColumn();
    }

    /**
     * Inserta el caso y le asigna un código correlativo derivado de su propio id
     * (F-00001, F-00002...), evitando condiciones de carrera con contadores aparte.
     * Debe ejecutarse dentro de una transacción abierta por el llamador.
     */
    public static function crearConCodigo(array $datos): int
    {
        $id = self::crear($datos + ['codigo' => 'TMP-' . bin2hex(random_bytes(8))]);

        $codigo = sprintf('F-%05d', $id);
        self::actualizar($id, ['codigo' => $codigo]);

        return $id;
    }

    /**
     * Listado paginado con filtros para la vista de fichas. $filtros admite:
     * q, enfermedad_id, clasificacion, estado, desde, hasta, establecimiento_id.
     *
     * @return array{filas: array, total: int}
     */
    public static function listarPaginado(array $filtros, int $pagina, int $porPagina): array
    {
        $condiciones = ['1=1'];
        $parametros = [];

        if (!empty($filtros['q'])) {
            $condiciones[] = '(c.codigo LIKE :q1 OR p.num_doc LIKE :q2 OR p.apellidos_nombres LIKE :q3)';
            $comodin = '%' . $filtros['q'] . '%';
            $parametros['q1'] = $comodin;
            $parametros['q2'] = $comodin;
            $parametros['q3'] = $comodin;
        }
        if (!empty($filtros['enfermedad_id'])) {
            $condiciones[] = 'c.enfermedad_id = :enfermedad_id';
            $parametros['enfermedad_id'] = (int) $filtros['enfermedad_id'];
        }
        if (!empty($filtros['clasificacion'])) {
            $condiciones[] = 'c.clasificacion = :clasificacion';
            $parametros['clasificacion'] = $filtros['clasificacion'];
        }
        if (!empty($filtros['estado'])) {
            $condiciones[] = 'c.estado = :estado';
            $parametros['estado'] = $filtros['estado'];
        }
        if (!empty($filtros['desde'])) {
            $condiciones[] = 'c.fecha_notif >= :desde';
            $parametros['desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $condiciones[] = 'c.fecha_notif <= :hasta';
            $parametros['hasta'] = $filtros['hasta'];
        }
        if (!empty($filtros['establecimiento_id'])) {
            $condiciones[] = 'c.establecimiento_id = :establecimiento_id';
            $parametros['establecimiento_id'] = (int) $filtros['establecimiento_id'];
        }

        $where = implode(' AND ', $condiciones);
        $pdo = Database::conexion();

        $sqlTotal = "SELECT COUNT(*) FROM caso c JOIN paciente p ON p.id = c.paciente_id WHERE $where";
        $consultaTotal = $pdo->prepare($sqlTotal);
        $consultaTotal->execute($parametros);
        $total = (int) $consultaTotal->fetchColumn();

        $porPagina = max(1, $porPagina);
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina = max(1, min($pagina, $totalPaginas));
        $offset = ($pagina - 1) * $porPagina;

        $sql = "SELECT c.*, p.tipo_doc, p.num_doc, p.apellidos_nombres, p.sexo, p.fecha_nac,
                       e.nombre AS enfermedad_nombre, e.cie10,
                       est.nombre AS establecimiento_nombre, r.nombre AS red_nombre
                  FROM caso c
                  JOIN paciente p        ON p.id = c.paciente_id
                  JOIN enfermedad e      ON e.id = c.enfermedad_id
                  JOIN establecimiento est ON est.id = c.establecimiento_id
             LEFT JOIN red_salud r       ON r.id = est.red_id
                 WHERE $where
              ORDER BY c.fecha_notif DESC, c.id DESC
                 LIMIT $porPagina OFFSET $offset";

        $consulta = $pdo->prepare($sql);
        $consulta->execute($parametros);

        return [
            'filas'        => $consulta->fetchAll(),
            'total'        => $total,
            'pagina'       => $pagina,
            'totalPaginas' => $totalPaginas,
        ];
    }

    /**
     * Detalle completo de un caso para Ver/Editar: cabecera + paciente +
     * enfermedad + establecimiento + quién lo registró.
     */
    public static function conDetalle(int $id): ?array
    {
        $sql = 'SELECT c.*, p.tipo_doc, p.num_doc, p.apellidos_nombres, p.sexo, p.fecha_nac,
                       p.distrito_id, p.es_pnp, p.cip, p.situacion_pnp, p.grado_id, p.unidad_id,
                       p.tipo_beneficiario,
                       e.nombre AS enfermedad_nombre, e.cie10, e.tipo_notif,
                       est.nombre AS establecimiento_nombre, est.id AS establecimiento_id,
                       r.nombre AS red_nombre,
                       u.nombre AS usuario_nombre,
                       g.nombre AS grado_nombre,
                       un.nombre AS unidad_nombre,
                       d.nombre AS distrito_nombre
                  FROM caso c
                  JOIN paciente p        ON p.id = c.paciente_id
                  JOIN enfermedad e      ON e.id = c.enfermedad_id
                  JOIN establecimiento est ON est.id = c.establecimiento_id
             LEFT JOIN red_salud r       ON r.id = est.red_id
                  JOIN usuario u         ON u.id = c.usuario_id
             LEFT JOIN grado_pnp g       ON g.id = p.grado_id
             LEFT JOIN unidad_pnp un     ON un.id = p.unidad_id
             LEFT JOIN distrito d        ON d.id = p.distrito_id
                 WHERE c.id = :id';

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    /**
     * Busca un caso previo de la misma enfermedad y documento dentro de una
     * ventana de ~30 días alrededor de la fecha de notificación, para el
     * aviso de posible duplicado (no bloqueante).
     */
    public static function buscarDuplicado(
        int $enfermedadId,
        string $tipoDoc,
        string $numDoc,
        string $fechaNotifIso,
        ?int $excluirCasoId = null
    ): ?array {
        $sql = "SELECT c.id, c.codigo, c.semana_epi, c.anio_epi, c.fecha_notif, est.nombre AS establecimiento_nombre
                  FROM caso c
                  JOIN paciente p ON p.id = c.paciente_id
                  JOIN establecimiento est ON est.id = c.establecimiento_id
                 WHERE c.enfermedad_id = :enfermedad_id
                   AND p.tipo_doc = :tipo_doc AND p.num_doc = :num_doc
                   AND c.anulado = 0
                   AND ABS(DATEDIFF(c.fecha_notif, :fecha)) <= 30";
        $parametros = [
            'enfermedad_id' => $enfermedadId,
            'tipo_doc'      => $tipoDoc,
            'num_doc'       => $numDoc,
            'fecha'         => $fechaNotifIso,
        ];

        if ($excluirCasoId !== null) {
            $sql .= ' AND c.id != :excluir';
            $parametros['excluir'] = $excluirCasoId;
        }

        $sql .= ' ORDER BY c.fecha_notif DESC LIMIT 1';

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    public static function cambiarEstado(int $id, string $nuevoEstado): void
    {
        self::actualizar($id, ['estado' => $nuevoEstado]);
    }

    public static function anular(int $id, string $motivo): void
    {
        self::actualizar($id, ['anulado' => 1, 'motivo_anulacion' => $motivo]);
    }

    /**
     * Métricas de las 4 tarjetas del panel. $actual y $anterior son
     * ['anio' => int, 'semana' => int] (la SE de hoy y la de hace 7 días,
     * ya calculadas por el llamador con semanaEpidemiologica()).
     */
    public static function metricasPanel(array $actual, array $anterior): array
    {
        $sql = 'SELECT
                  SUM(CASE WHEN anio_epi = :aa AND semana_epi = :sa THEN 1 ELSE 0 END) AS fichas_se_actual,
                  SUM(CASE WHEN anio_epi = :ap AND semana_epi = :sp THEN 1 ELSE 0 END) AS fichas_se_anterior,
                  SUM(CASE WHEN estado = "ABIERTA" THEN 1 ELSE 0 END) AS abiertas,
                  SUM(CASE WHEN clasificacion = "CONFIRMADO" AND anio_epi = :aa2 AND semana_epi = :sa2 THEN 1 ELSE 0 END) AS confirmados_se_actual,
                  SUM(CASE WHEN clasificacion = "CONFIRMADO" AND anio_epi = :ap2 AND semana_epi = :sp2 THEN 1 ELSE 0 END) AS confirmados_se_anterior,
                  SUM(CASE WHEN estado = "VALIDACION" THEN 1 ELSE 0 END) AS en_validacion,
                  COUNT(DISTINCT CASE WHEN estado = "VALIDACION" THEN establecimiento_id END) AS en_validacion_establecimientos
                FROM caso
                WHERE anulado = 0';

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([
            'aa' => $actual['anio'], 'sa' => $actual['semana'],
            'ap' => $anterior['anio'], 'sp' => $anterior['semana'],
            'aa2' => $actual['anio'], 'sa2' => $actual['semana'],
            'ap2' => $anterior['anio'], 'sp2' => $anterior['semana'],
        ]);

        $fila = $consulta->fetch();

        return [
            'fichas_se_actual'               => (int) $fila['fichas_se_actual'],
            'fichas_se_anterior'              => (int) $fila['fichas_se_anterior'],
            'abiertas'                        => (int) $fila['abiertas'],
            'confirmados_se_actual'           => (int) $fila['confirmados_se_actual'],
            'confirmados_se_anterior'         => (int) $fila['confirmados_se_anterior'],
            'en_validacion'                   => (int) $fila['en_validacion'],
            'en_validacion_establecimientos'  => (int) $fila['en_validacion_establecimientos'],
        ];
    }

    /**
     * Condición SQL para un rango de semana epidemiológica que puede cruzar
     * de año, comparando la tupla (anio_epi, semana_epi) de forma que el
     * optimizador pueda usar el índice ix_caso_se (anio_epi, semana_epi).
     */
    private static function condicionRangoSE(
        int $anioDesde,
        int $seDesde,
        int $anioHasta,
        int $seHasta,
        array &$parametros
    ): string {
        // Marcadores únicos por ocurrencia: con sentencias preparadas reales
        // (PDO::ATTR_EMULATE_PREPARES=false) no se puede reusar un mismo
        // nombre de parámetro más de una vez en la misma consulta.
        $parametros['rango_ad1'] = $anioDesde;
        $parametros['rango_ad2'] = $anioDesde;
        $parametros['rango_sd'] = $seDesde;
        $parametros['rango_ah1'] = $anioHasta;
        $parametros['rango_ah2'] = $anioHasta;
        $parametros['rango_sh'] = $seHasta;

        return '(anio_epi > :rango_ad1 OR (anio_epi = :rango_ad2 AND semana_epi >= :rango_sd))
            AND (anio_epi < :rango_ah1 OR (anio_epi = :rango_ah2 AND semana_epi <= :rango_sh))';
    }

    /**
     * Casos por semana epidemiológica dentro de un rango, agregando todas
     * las enfermedades si $enfermedadId es null. Solo trae semanas con al
     * menos un caso: el llamador rellena de cero las semanas ausentes con
     * semanasEnRango() para no perder continuidad en la curva.
     */
    public static function serieSemanal(
        ?int $enfermedadId,
        int $anioDesde,
        int $seDesde,
        int $anioHasta,
        int $seHasta
    ): array {
        $parametros = [];
        $condicionSE = self::condicionRangoSE($anioDesde, $seDesde, $anioHasta, $seHasta, $parametros);

        $condicionEnf = '';
        if ($enfermedadId !== null) {
            $condicionEnf = ' AND enfermedad_id = :enfermedad_id';
            $parametros['enfermedad_id'] = $enfermedadId;
        }

        $sql = "SELECT anio_epi, semana_epi, COUNT(*) AS total
                  FROM caso
                 WHERE anulado = 0 AND $condicionSE $condicionEnf
              GROUP BY anio_epi, semana_epi
              ORDER BY anio_epi, semana_epi";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    /**
     * Conteos históricos (años anteriores a $anioActual) agrupados solo por
     * semana_epi, para construir el canal endémico por percentiles: para
     * cada SE del rango mostrado, da la lista de conteos de esa misma SE en
     * cada año previo con datos.
     *
     * @return array<int, array<int, int>> semana_epi => lista de conteos anuales
     */
    public static function serieHistoricaPorSemana(?int $enfermedadId, int $anioActual): array
    {
        $parametros = ['anio_actual' => $anioActual];
        $condicionEnf = '';
        if ($enfermedadId !== null) {
            $condicionEnf = ' AND enfermedad_id = :enfermedad_id';
            $parametros['enfermedad_id'] = $enfermedadId;
        }

        $sql = "SELECT anio_epi, semana_epi, COUNT(*) AS total
                  FROM caso
                 WHERE anulado = 0 AND anio_epi < :anio_actual $condicionEnf
              GROUP BY anio_epi, semana_epi";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        $porSemana = [];
        foreach ($consulta->fetchAll() as $fila) {
            $porSemana[(int) $fila['semana_epi']][] = (int) $fila['total'];
        }

        return $porSemana;
    }

    /**
     * Cuántos años anteriores a $anioActual tienen al menos un caso
     * registrado, para decidir si ya hay historia suficiente para el canal
     * endémico por percentiles.
     */
    public static function aniosConDatos(?int $enfermedadId, int $anioActual): int
    {
        $parametros = ['anio_actual' => $anioActual];
        $condicionEnf = '';
        if ($enfermedadId !== null) {
            $condicionEnf = ' AND enfermedad_id = :enfermedad_id';
            $parametros['enfermedad_id'] = $enfermedadId;
        }

        $sql = "SELECT COUNT(DISTINCT anio_epi) FROM caso WHERE anulado = 0 AND anio_epi < :anio_actual $condicionEnf";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        return (int) $consulta->fetchColumn();
    }

    /**
     * Distribución por enfermedad dentro de un rango de SE, para la tarjeta
     * "Por enfermedad" del panel.
     */
    public static function distribucionPorEnfermedad(int $anioDesde, int $seDesde, int $anioHasta, int $seHasta): array
    {
        $parametros = [];
        $condicionSE = self::condicionRangoSE($anioDesde, $seDesde, $anioHasta, $seHasta, $parametros);

        $sql = "SELECT e.nombre, COUNT(*) AS total
                  FROM caso c
                  JOIN enfermedad e ON e.id = c.enfermedad_id
                 WHERE c.anulado = 0 AND $condicionSE
              GROUP BY e.id, e.nombre
              ORDER BY total DESC";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    /**
     * Distribución por clasificación, con los mismos filtros opcionales que
     * reportePorAgrupacion (enfermedad_id y rango de SE). Sin filtros,
     * agrega todo el histórico.
     */
    public static function distribucionPorClasificacion(array $filtros = []): array
    {
        $condiciones = ['anulado = 0'];
        $parametros = [];

        if (!empty($filtros['enfermedad_id'])) {
            $condiciones[] = 'enfermedad_id = :enfermedad_id';
            $parametros['enfermedad_id'] = (int) $filtros['enfermedad_id'];
        }
        if (!empty($filtros['rango_se'])) {
            $r = $filtros['rango_se'];
            $condiciones[] = self::condicionRangoSE($r['anio_desde'], $r['se_desde'], $r['anio_hasta'], $r['se_hasta'], $parametros);
        }

        $where = implode(' AND ', $condiciones);
        $sql = "SELECT clasificacion, COUNT(*) AS total
                  FROM caso
                 WHERE $where
              GROUP BY clasificacion
              ORDER BY FIELD(clasificacion, 'SOSPECHOSO','PROBABLE','CONFIRMADO','DESCARTADO')";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        $filas = $consulta->fetchAll();
        $porClasificacion = ['SOSPECHOSO' => 0, 'PROBABLE' => 0, 'CONFIRMADO' => 0, 'DESCARTADO' => 0];
        foreach ($filas as $fila) {
            $porClasificacion[$fila['clasificacion']] = (int) $fila['total'];
        }

        return $porClasificacion;
    }

    /**
     * Tabla del reporte, agrupada por establecimiento, red, semana
     * epidemiológica o clasificación, con el desglose de clasificación
     * (o de estado, para el agrupado por clasificación) resuelto en SQL.
     * Alimenta tanto la tabla en pantalla como la exportación a Excel.
     */
    public static function reportePorAgrupacion(string $agrupacion, array $filtros): array
    {
        $condiciones = ['c.anulado = 0'];
        $parametros = [];

        if (!empty($filtros['enfermedad_id'])) {
            $condiciones[] = 'c.enfermedad_id = :enfermedad_id';
            $parametros['enfermedad_id'] = (int) $filtros['enfermedad_id'];
        }
        $r = $filtros['rango_se'];
        $condiciones[] = self::condicionRangoSE($r['anio_desde'], $r['se_desde'], $r['anio_hasta'], $r['se_hasta'], $parametros);

        $sumaClasificacion = "
            SUM(CASE WHEN c.clasificacion = 'SOSPECHOSO' THEN 1 ELSE 0 END) AS sospechoso,
            SUM(CASE WHEN c.clasificacion = 'PROBABLE'   THEN 1 ELSE 0 END) AS probable,
            SUM(CASE WHEN c.clasificacion = 'CONFIRMADO' THEN 1 ELSE 0 END) AS confirmado,
            SUM(CASE WHEN c.clasificacion = 'DESCARTADO' THEN 1 ELSE 0 END) AS descartado,
            COUNT(*) AS total";

        switch ($agrupacion) {
            case 'red':
                $sql = "SELECT COALESCE(r.nombre, 'Sin red') AS etiqueta, $sumaClasificacion
                          FROM caso c
                          JOIN establecimiento est ON est.id = c.establecimiento_id
                     LEFT JOIN red_salud r         ON r.id = est.red_id
                         WHERE " . implode(' AND ', $condiciones) . '
                      GROUP BY r.id, etiqueta
                      ORDER BY total DESC';
                break;
            case 'semana':
                $sql = "SELECT CONCAT('SE ', c.semana_epi, ' · ', c.anio_epi) AS etiqueta, c.anio_epi, c.semana_epi, $sumaClasificacion
                          FROM caso c
                         WHERE " . implode(' AND ', $condiciones) . '
                      GROUP BY c.anio_epi, c.semana_epi, etiqueta
                      ORDER BY c.anio_epi, c.semana_epi';
                break;
            case 'clasificacion':
                $sql = "SELECT c.clasificacion AS etiqueta, $sumaClasificacion
                          FROM caso c
                         WHERE " . implode(' AND ', $condiciones) . "
                      GROUP BY c.clasificacion
                      ORDER BY FIELD(c.clasificacion, 'SOSPECHOSO','PROBABLE','CONFIRMADO','DESCARTADO')";
                break;
            case 'establecimiento':
            default:
                $sql = "SELECT est.nombre AS etiqueta, $sumaClasificacion
                          FROM caso c
                          JOIN establecimiento est ON est.id = c.establecimiento_id
                         WHERE " . implode(' AND ', $condiciones) . '
                      GROUP BY est.id, etiqueta
                      ORDER BY total DESC';
                break;
        }

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }
}
