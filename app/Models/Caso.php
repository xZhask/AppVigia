<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Caso extends Model
{
    protected static string $tabla = 'caso';

    /**
     * CIE-10 de las fichas de confidencialidad reforzada (VIH/SIDA, gestante
     * con VIH y, desde el 2026-09-14, sífilis materna y congénita: la Ley
     * 26626 cubre VIH e ITS). listarPaginado() arma su filtro SQL con esta
     * misma lista. Violencia familiar se identifica por nombre, no tiene
     * CIE-10 fijo aquí.
     */
    public const CIE10_PRIVADOS = ['B24', 'Z21', 'A50'];

    /**
     * Una ficha es "privada": un REGISTRADOR solo puede listar, ver o editar
     * las que él mismo registró; ADMIN no tiene esta restricción.
     */
    public static function esPrivada(array $caso): bool
    {
        return in_array($caso['cie10'] ?? null, self::CIE10_PRIVADOS, true)
            || stripos($caso['enfermedad_nombre'] ?? '', 'Violencia') !== false;
    }

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

    /** Clasificaciones que todavía no son finales (badge "pendientes" del menú). */
    public const CLASIFICACIONES_NO_FINALES = ['SOSPECHOSO', 'PROBABLE', 'POR_DETERMINAR'];

    /** Pestañas del listado de fichas: estado del caso, con la anulación aparte. */
    public const PESTANAS_LISTADO = ['ABIERTA', 'VALIDACION', 'CERRADA', 'ANULADA', 'TODAS'];

    /**
     * Condiciones comunes del listado de fichas (2026-10-17): todo menos la
     * pestaña y la clasificación, que se cuentan aparte sobre esta base.
     * $filtros admite q, enfermedad_id, red_id, establecimiento_id, el
     * periodo ya resuelto (se_desde/se_hasta como [anio, semana]) y la
     * privacidad (privacidad_rol, privacidad_usuario_id).
     *
     * @return array{0: string[], 1: array}
     */
    private static function condicionesListado(array $filtros): array
    {
        $condiciones = ['1=1'];
        $parametros = [];

        if (!empty($filtros['q'])) {
            $condiciones[] = "(c.codigo LIKE :q1 OR p.num_doc LIKE :q2 OR CONCAT_WS(' ', p.apellido_paterno, p.apellido_materno, p.nombres) LIKE :q3)";
            $comodin = '%' . $filtros['q'] . '%';
            $parametros['q1'] = $comodin;
            $parametros['q2'] = $comodin;
            $parametros['q3'] = $comodin;
        }
        if (!empty($filtros['enfermedad_id'])) {
            $condiciones[] = 'c.enfermedad_id = :enfermedad_id';
            $parametros['enfermedad_id'] = (int) $filtros['enfermedad_id'];
        } else {
            // Registros PNP (2026-10-21): no son casos de una enfermedad con
            // ficha y no suman en el listado, el panel ni el consolidado salvo
            // que se elija ese registro como enfermedad.
            $condiciones[] = "e.modalidad = 'FICHA'";
        }
        if (!empty($filtros['red_id'])) {
            $condiciones[] = 'est.red_id = :red_id';
            $parametros['red_id'] = (int) $filtros['red_id'];
        }
        if (!empty($filtros['establecimiento_id'])) {
            $condiciones[] = 'c.establecimiento_id = :establecimiento_id';
            $parametros['establecimiento_id'] = (int) $filtros['establecimiento_id'];
        }
        if (!empty($filtros['se_desde']) && !empty($filtros['se_hasta'])) {
            $condiciones[] = '(' . self::condicionRangoSE(
                $filtros['se_desde'][0], $filtros['se_desde'][1],
                $filtros['se_hasta'][0], $filtros['se_hasta'][1],
                $parametros
            ) . ')';
        }
        if (($filtros['privacidad_rol'] ?? '') === 'REGISTRADOR') {
            $condiciones[] = '(e.cie10 NOT IN (' . implode(', ', array_map(fn(string $cie10): string => '"' . $cie10 . '"', self::CIE10_PRIVADOS)) . ') AND e.nombre NOT LIKE "%Violencia%" OR c.usuario_id = :priv_uid)';
            $parametros['priv_uid'] = (int) $filtros['privacidad_usuario_id'];
        }

        return [$condiciones, $parametros];
    }

    public const FROM_LISTADO = 'FROM caso c
                  JOIN persona p         ON p.id = c.persona_id
                  JOIN enfermedad e      ON e.id = c.enfermedad_id
                  JOIN establecimiento est ON est.id = c.establecimiento_id
             LEFT JOIN red_salud r       ON r.id = est.red_id';

    /**
     * Alcance de un usuario para panel y reportes (2026-10-17), con las
     * mismas reglas que el listado: el REGISTRADOR ve su establecimiento y no
     * ve las fichas privadas de otros. Condiciones sobre los alias de
     * FROM_LISTADO (c, p, e, est, r); no excluye anuladas.
     *
     * @return array{0: string[], 1: array}
     */
    public static function alcance(array $usuario, array $filtros = []): array
    {
        $filtros['privacidad_rol'] = $usuario['rol'];
        $filtros['privacidad_usuario_id'] = $usuario['id'];
        if ($usuario['rol'] === 'REGISTRADOR') {
            $filtros['red_id'] = '';
            $filtros['establecimiento_id'] = $usuario['establecimiento_id'];
        }
        return self::condicionesListado($filtros);
    }

    private static function condicionPestana(string $pestana): string
    {
        return match ($pestana) {
            'ANULADA' => 'c.anulado = 1',
            'TODAS'   => '1=1',
            default   => "c.anulado = 0 AND c.estado = '" . $pestana . "'", // ya validada contra PESTANAS_LISTADO
        };
    }

    /**
     * Listado de fichas (2026-10-17): una pestaña por estado (Abiertas, En
     * validación, Cerradas, Anuladas, Todas) con su conteo, conteos por
     * clasificación dentro de la pestaña (filtran, combinables) y orden por
     * columna. $filtros: los de condicionesListado() + pestana, clasificaciones
     * (string[]), orden y dir. Sin $porPagina devuelve todas las filas (CSV).
     *
     * @return array{filas: array, total: int, pagina: int, totalPaginas: int, porPestana: array<string,int>, porClasificacion: array<string,int>}
     */
    public static function listarPaginado(array $filtros, int $pagina, ?int $porPagina): array
    {
        [$condiciones, $parametros] = self::condicionesListado($filtros);
        $pdo = Database::conexion();
        $from = self::FROM_LISTADO;
        $whereBase = implode(' AND ', $condiciones);

        $consulta = $pdo->prepare("SELECT SUM(c.anulado = 0 AND c.estado = 'ABIERTA') AS ABIERTA,
                                          SUM(c.anulado = 0 AND c.estado = 'VALIDACION') AS VALIDACION,
                                          SUM(c.anulado = 0 AND c.estado = 'CERRADA') AS CERRADA,
                                          SUM(c.anulado = 1) AS ANULADA, COUNT(*) AS TODAS
                                     $from WHERE $whereBase");
        $consulta->execute($parametros);
        $porPestana = array_map('intval', $consulta->fetch() ?: []);

        $pestana = in_array($filtros['pestana'] ?? '', self::PESTANAS_LISTADO, true) ? $filtros['pestana'] : 'ABIERTA';
        $condiciones[] = self::condicionPestana($pestana);
        $whereTab = implode(' AND ', $condiciones);

        $consulta = $pdo->prepare("SELECT c.clasificacion, COUNT(*) AS n $from WHERE $whereTab GROUP BY c.clasificacion");
        $consulta->execute($parametros);
        $porClasificacion = [];
        foreach ($consulta->fetchAll() as $fila) {
            if ((string) $fila['clasificacion'] !== '') {
                $porClasificacion[$fila['clasificacion']] = (int) $fila['n'];
            }
        }

        $clasificaciones = array_values(array_filter((array) ($filtros['clasificaciones'] ?? []), 'is_string'));
        if ($clasificaciones) {
            $marcadores = [];
            foreach ($clasificaciones as $i => $codigo) {
                $marcadores[] = ':clas' . $i;
                $parametros['clas' . $i] = $codigo;
            }
            $condiciones[] = 'c.clasificacion IN (' . implode(', ', $marcadores) . ')';
        }
        $where = implode(' AND ', $condiciones);

        $consulta = $pdo->prepare("SELECT COUNT(*) $from WHERE $where");
        $consulta->execute($parametros);
        $total = (int) $consulta->fetchColumn();

        $direccion = ($filtros['dir'] ?? '') === 'asc' ? 'ASC' : 'DESC';
        $ordenClasificacion = 'FIELD(c.clasificacion, ' . implode(', ', array_map(fn(string $c): string => "'" . $c . "'", array_keys(CATALOGO_CLASIFICACION))) . ')';
        $orden = match ($filtros['orden'] ?? '') {
            'codigo'        => "c.id $direccion",
            'paciente'      => "p.apellido_paterno $direccion, p.apellido_materno $direccion, p.nombres $direccion, c.id DESC",
            'enfermedad'    => "e.nombre $direccion, c.fecha_notif DESC, c.id DESC",
            'clasificacion' => "$ordenClasificacion $direccion, c.fecha_notif DESC, c.id DESC",
            default         => "c.fecha_notif $direccion, c.id $direccion",
        };

        if ($porPagina === null) {
            $pagina = 1;
            $totalPaginas = 1;
            $limite = '';
        } else {
            $porPagina = max(1, $porPagina);
            $totalPaginas = max(1, (int) ceil($total / $porPagina));
            $pagina = max(1, min($pagina, $totalPaginas));
            $limite = 'LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina);
        }

        $sql = "SELECT c.*, p.tipo_doc, p.num_doc, p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo, p.fecha_nac,
                       e.nombre AS enfermedad_nombre, e.cie10, e.unidades_edad AS enfermedad_unidades_edad,
                       -- nucleo_ajustes.edad_a_la_fecha (P96, 2026-10-16): la edad se cuenta hasta esa fecha.
                       (SELECT cv_edad.valor FROM caso_valor cv_edad JOIN campo_def cd_edad ON cd_edad.id = cv_edad.campo_def_id
                         WHERE cv_edad.caso_id = c.id AND cd_edad.clave = JSON_UNQUOTE(JSON_EXTRACT(e.nucleo_ajustes, '$.edad_a_la_fecha'))) AS edad_fecha_referencia,
                       est.nombre AS establecimiento_nombre, r.nombre AS red_nombre
                  $from
                 WHERE $where
              ORDER BY $orden
                 $limite";

        $consulta = $pdo->prepare($sql);
        $consulta->execute($parametros);

        return [
            'filas'            => $consulta->fetchAll(),
            'total'            => $total,
            'pagina'           => $pagina,
            'totalPaginas'     => $totalPaginas,
            'pestana'          => $pestana,
            'porPestana'       => $porPestana + array_fill_keys(self::PESTANAS_LISTADO, 0),
            'porClasificacion' => $porClasificacion,
        ];
    }

    /**
     * Badge del menú lateral (2026-10-17): fichas abiertas, no anuladas, que
     * todavía no tienen clasificación final, con el mismo alcance que el
     * listado (el REGISTRADOR, su establecimiento y sin las privadas ajenas).
     */
    public static function contarPendientes(array $usuario): int
    {
        $filtros = ['privacidad_rol' => $usuario['rol'], 'privacidad_usuario_id' => $usuario['id']];
        if ($usuario['rol'] === 'REGISTRADOR') {
            $filtros['establecimiento_id'] = $usuario['establecimiento_id'];
        }
        [$condiciones, $parametros] = self::condicionesListado($filtros);
        $condiciones[] = self::condicionPestana('ABIERTA');
        $condiciones[] = "(c.clasificacion IS NULL OR c.clasificacion IN ('', '" . implode("', '", self::CLASIFICACIONES_NO_FINALES) . "'))";

        $consulta = Database::conexion()->prepare('SELECT COUNT(*) ' . self::FROM_LISTADO . ' WHERE ' . implode(' AND ', $condiciones));
        $consulta->execute($parametros);

        return (int) $consulta->fetchColumn();
    }

    /**
     * Detalle completo de un caso para Ver/Editar: cabecera + persona +
     * datos_clinicos (EAV) + contactos + muestras + vacunas + viajes.
     * enfermedad + establecimiento + quién lo registró.
     */
    public static function conDetalle(int $id): ?array
    {
        $sql = 'SELECT c.*, p.tipo_doc, p.num_doc, p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo, p.fecha_nac,
                       p.distrito_id, p.condicion, p.cip, p.situacion_pnp, p.grado_id, p.categoria_pnp, p.unidad_id,
                       p.titular_id, p.vinculo_titular,
                       p.celular, p.nacionalidad, p.direccion, p.referencia_localizar, p.tipo_zona, p.tipo_asentamiento, p.nombre_zona, p.tipo_via, p.nombre_via, p.numero, p.mz_lote, p.tiempo_residencia, p.n_historia_clinica, p.localidad, p.etnia, p.etnia_otra, p.pueblo_etnico, p.ocupacion, p.estado_civil, p.nombre_tutor, p.celular_tutor, p.gestante, p.fur, p.semanas_gestacion, p.trimestre_gestacion,
                       p.tiempo_reside_anios, p.tiempo_reside_meses, p.anterior_distrito_id, p.anterior_tipo_zona, p.anterior_tipo_asentamiento, p.anterior_nombre_zona, p.anterior_tipo_via, p.anterior_nombre_via, p.anterior_numero, p.anterior_mz_lote,
                       p.nacimiento_distrito_id,
                       e.nombre AS enfermedad_nombre, e.cie10, e.tipo_notif, e.modalidad AS enfermedad_modalidad,
                       e.multi_sujeto AS enfermedad_multi_sujeto, e.roles_sujeto AS enfermedad_roles_sujeto,
                       e.columnas_sujeto AS enfermedad_columnas_sujeto, e.titulo_sujeto AS enfermedad_titulo_sujeto,
                       e.unidades_edad AS enfermedad_unidades_edad,
                       (SELECT cv_edad.valor FROM caso_valor cv_edad JOIN campo_def cd_edad ON cd_edad.id = cv_edad.campo_def_id
                         WHERE cv_edad.caso_id = c.id AND cd_edad.clave = JSON_UNQUOTE(JSON_EXTRACT(e.nucleo_ajustes, \'$.edad_a_la_fecha\'))) AS edad_fecha_referencia,
                       est.nombre AS establecimiento_nombre, est.id AS establecimiento_id,
                       r.nombre AS red_nombre,
                       u.nombre AS usuario_nombre,
                       g.nombre AS grado_nombre,
                       un.nombre AS unidad_nombre,
                       d.nombre AS distrito_nombre,
                       ad.nombre AS anterior_distrito_nombre,
                       nd.nombre AS nacimiento_distrito_nombre,
                       lcd.nombre AS lugar_contagio_distrito_nombre,
                       t.apellido_paterno AS titular_apellido_paterno,
                       t.apellido_materno AS titular_apellido_materno,
                       t.nombres AS titular_nombres,
                       tg.abreviatura AS titular_grado_abreviatura
                  FROM caso c
                  JOIN persona p         ON p.id = c.persona_id
                  JOIN enfermedad e      ON e.id = c.enfermedad_id
                  JOIN establecimiento est ON est.id = c.establecimiento_id
             LEFT JOIN red_salud r       ON r.id = est.red_id
                  JOIN usuario u         ON u.id = c.usuario_id
             LEFT JOIN grado_pnp g       ON g.id = p.grado_id
             LEFT JOIN unidad_pnp un     ON un.id = p.unidad_id
             LEFT JOIN distrito d        ON d.id = p.distrito_id
             LEFT JOIN distrito ad       ON ad.id = p.anterior_distrito_id
             LEFT JOIN distrito nd       ON nd.id = p.nacimiento_distrito_id
             LEFT JOIN distrito lcd      ON lcd.id = c.lugar_contagio_distrito_id
             LEFT JOIN persona t         ON t.id = p.titular_id
             LEFT JOIN grado_pnp tg      ON tg.id = t.grado_id
                 WHERE c.id = :id';

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    /**
     * Ficha MINSA abierta desde un registro PNP ("Pasar a ficha MINSA",
     * 2026-10-21; caso.caso_origen_id), sin las anuladas: código, id y
     * enfermedad, o null.
     */
    public static function fichaDesdeRegistro(int $registroId): ?array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT c.id, c.codigo, e.nombre_corto, e.nombre FROM caso c JOIN enfermedad e ON e.id = c.enfermedad_id
              WHERE c.caso_origen_id = :id AND c.anulado = 0 ORDER BY c.id LIMIT 1'
        );
        $consulta->execute(['id' => $registroId]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    /**
     * Ids de los casos que apuntan a este vía caso_vinculado_id -- en Z21,
     * los niños nacidos expuestos vinculados a la ficha de su madre (uno o
     * varios si el embarazo fue múltiple).
     */
    public static function idsVinculados(int $casoId): array
    {
        $consulta = Database::conexion()->prepare('SELECT id FROM caso WHERE caso_vinculado_id = :id ORDER BY id');
        $consulta->execute(['id' => $casoId]);

        return array_map('intval', array_column($consulta->fetchAll(), 'id'));
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
                  JOIN persona p ON p.id = c.persona_id
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

}
