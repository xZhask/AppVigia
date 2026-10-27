<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Ficha de investigación epidemiológica de IAAS (Anexo 5). Una por
 * infección; nace "por completar" desde la ronda ("Inicia IAAS") o completa
 * desde el listado. Las fichas con dispositivo marcan como × (IAAS en
 * tratamiento) los días desde la fecha de la IAAS hasta el fin del
 * tratamiento, que no suman al denominador (IaasRonda::recalcularEstados).
 */
class IaasCaso extends Model
{
    protected static string $tabla = 'iaas_caso';

    /** Columnas que guarda el formulario (sin las de control). */
    public const COLUMNAS = [
        'sala_id', 'historia_clinica', 'peso_nacer_g', 'fecha_ingreso_ipress', 'fecha_ingreso_servicio',
        'fecha_egreso_servicio', 'condicion_egreso', 'dx1_cie10', 'dx2_cie10', 'dx3_cie10',
        'tipo_iaas', 'fecha_iaas', 'criterio', 'its_secundaria',
        'dispositivo_tipo', 'fecha_instalacion', 'fecha_retiro',
        'procedimiento_tipo', 'procedimiento_nombre', 'fecha_procedimiento', 'duracion_min',
        'clase_herida', 'asa', 'implante', 'fecha_fin_tratamiento', 'observaciones',
    ];

    public static function codigo(array $caso): string
    {
        return 'IAAS-' . str_pad((string) $caso['id'], 5, '0', STR_PAD_LEFT);
    }

    public static function delEstablecimiento(int $id, int $establecimientoId): ?array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT c.*, s.upss, s.nombre AS sala_nombre,
                    p.tipo_doc, p.num_doc, p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo, p.fecha_nac,
                    u.nombre AS creado_nombre, a.nombre AS anulado_nombre
               FROM iaas_caso c
               JOIN iaas_sala s ON s.id = c.sala_id
               JOIN persona p ON p.id = c.persona_id
               JOIN usuario u ON u.id = c.creado_por
          LEFT JOIN usuario a ON a.id = c.anulado_por
              WHERE c.id = :id AND c.establecimiento_id = :est'
        );
        $consulta->execute(['id' => $id, 'est' => $establecimientoId]);
        return $consulta->fetch() ?: null;
    }

    /**
     * @param array{pestana: string, anio: int, mes: int|null, tipo: string, q: string} $filtros
     */
    public static function listado(int $establecimientoId, array $filtros): array
    {
        $where = ['c.establecimiento_id = :est'];
        $params = ['est' => $establecimientoId];
        $where[] = match ($filtros['pestana']) {
            'POR_COMPLETAR' => "c.anulado_en IS NULL AND c.estado = 'POR_COMPLETAR'",
            'COMPLETO'      => "c.anulado_en IS NULL AND c.estado = 'COMPLETO'",
            'ANULADAS'      => 'c.anulado_en IS NOT NULL',
            default         => 'c.anulado_en IS NULL',
        };
        if ($filtros['mes']) {
            $where[] = 'YEAR(c.fecha_iaas) = :anio AND MONTH(c.fecha_iaas) = :mes';
            $params += ['anio' => $filtros['anio'], 'mes' => $filtros['mes']];
        } else {
            $where[] = 'YEAR(c.fecha_iaas) = :anio';
            $params['anio'] = $filtros['anio'];
        }
        if ($filtros['tipo'] !== '') {
            $where[] = $filtros['tipo'] === 'ISQ' ? "c.tipo_iaas IN ('ISQ_SUP','ISQ_PROF')" : 'c.tipo_iaas = :tipo';
            if ($filtros['tipo'] !== 'ISQ') {
                $params['tipo'] = $filtros['tipo'];
            }
        }
        if ($filtros['q'] !== '') {
            $where[] = "(CONCAT_WS(' ', p.apellido_paterno, p.apellido_materno, p.nombres) LIKE :q OR p.num_doc LIKE :q2 OR c.historia_clinica LIKE :q3)";
            $params += ['q' => '%' . $filtros['q'] . '%', 'q2' => $filtros['q'] . '%', 'q3' => $filtros['q'] . '%'];
        }
        $consulta = Database::conexion()->prepare(
            'SELECT c.*, s.upss, s.nombre AS sala_nombre,
                    p.tipo_doc, p.num_doc, p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo, p.fecha_nac,
                    (SELECT m.abreviatura FROM iaas_cultivo cu JOIN iaas_microorganismo m ON m.id = cu.microorganismo_id
                      WHERE cu.caso_id = c.id ORDER BY cu.orden LIMIT 1) AS microorganismo
               FROM iaas_caso c
               JOIN iaas_sala s ON s.id = c.sala_id
               JOIN persona p ON p.id = c.persona_id
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY c.fecha_iaas DESC, c.id DESC'
        );
        $consulta->execute($params);
        return $consulta->fetchAll();
    }

    /** Conteo por pestaña con los mismos filtros de periodo, tipo y búsqueda. */
    public static function conteos(int $establecimientoId, array $filtros): array
    {
        $conteo = [];
        foreach (['TODAS', 'POR_COMPLETAR', 'COMPLETO', 'ANULADAS'] as $pestana) {
            $conteo[$pestana] = count(self::listado($establecimientoId, ['pestana' => $pestana] + $filtros));
        }
        return $conteo;
    }

    public static function contarPorCompletar(int $establecimientoId): int
    {
        $consulta = Database::conexion()->prepare("SELECT COUNT(*) FROM iaas_caso WHERE establecimiento_id = ? AND anulado_en IS NULL AND estado = 'POR_COMPLETAR'");
        $consulta->execute([$establecimientoId]);
        return (int) $consulta->fetchColumn();
    }

    /** @return array<int, array> orden (1, 2) => cultivo con 'antibiograma' => [antimicrobiano_id => S/I/R] */
    public static function cultivos(int $casoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT cu.*, m.nombre AS microorganismo_nombre, m.abreviatura AS microorganismo_abreviatura
               FROM iaas_cultivo cu LEFT JOIN iaas_microorganismo m ON m.id = cu.microorganismo_id
              WHERE cu.caso_id = ? ORDER BY cu.orden'
        );
        $consulta->execute([$casoId]);
        $cultivos = [];
        foreach ($consulta->fetchAll() as $c) {
            $atb = Database::conexion()->prepare('SELECT antimicrobiano_id, resultado FROM iaas_antibiograma WHERE cultivo_id = ?');
            $atb->execute([$c['id']]);
            $c['antibiograma'] = array_column($atb->fetchAll(), 'resultado', 'antimicrobiano_id');
            $cultivos[(int) $c['orden']] = $c;
        }
        return $cultivos;
    }

    /**
     * Crea o actualiza la ficha con sus cultivos, en una transacción.
     *
     * @param array $control establecimiento_id, persona_id, internacion_id, dispositivo_id, estado, creado_por
     * @param array<int, array> $cultivos orden => datos del cultivo + 'antibiograma'
     */
    public static function guardar(?int $id, array $datos, array $control, array $cultivos): int
    {
        $pdo = Database::conexion();
        $pdo->beginTransaction();
        try {
            $anterior = $id ? self::buscar($id) : null;
            $fila = array_intersect_key($datos, array_flip(self::COLUMNAS));
            $fila['estado'] = $control['estado'];
            $fila['dispositivo_id'] = $control['dispositivo_id'];
            $fila['internacion_id'] = $control['internacion_id'];
            if ($id) {
                self::actualizar($id, $fila);
            } else {
                $id = self::crear($fila + [
                    'establecimiento_id' => $control['establecimiento_id'],
                    'persona_id'         => $control['persona_id'],
                    'creado_por'         => $control['creado_por'],
                ]);
            }

            $pdo->prepare('DELETE FROM iaas_cultivo WHERE caso_id = ?')->execute([$id]);
            $insertar = $pdo->prepare(
                'INSERT INTO iaas_cultivo (caso_id, orden, tipo_cultivo, tipo_cultivo_otro, fecha_extraccion, microorganismo_id, microorganismo_otro, blee, otro_mecanismo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $atb = $pdo->prepare('INSERT INTO iaas_antibiograma (cultivo_id, antimicrobiano_id, resultado) VALUES (?, ?, ?)');
            foreach ($cultivos as $orden => $c) {
                $insertar->execute([$id, $orden, $c['tipo_cultivo'], $c['tipo_cultivo_otro'], $c['fecha_extraccion'], $c['microorganismo_id'], $c['microorganismo_otro'], $c['blee'], $c['otro_mecanismo']]);
                $cultivoId = (int) $pdo->lastInsertId();
                foreach ($c['antibiograma'] as $antimicrobianoId => $resultado) {
                    $atb->execute([$cultivoId, $antimicrobianoId, $resultado]);
                }
            }

            foreach (array_unique(array_filter([$anterior['dispositivo_id'] ?? null, $control['dispositivo_id']])) as $dispositivoId) {
                IaasRonda::recalcularEstados((int) $dispositivoId);
            }
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** "Inicia IAAS" en la ronda: ficha por completar, con lo que la ronda ya sabe. */
    public static function crearDesdeRonda(array $internacion, array $dispositivo, string $fechaIaas, int $establecimientoId, int $usuarioId): int
    {
        $id = self::crear([
            'establecimiento_id'     => $establecimientoId,
            'sala_id'                => $internacion['sala_id'],
            'internacion_id'         => $internacion['id'],
            'dispositivo_id'         => $dispositivo['id'],
            'persona_id'             => $internacion['persona_id'],
            'estado'                 => 'POR_COMPLETAR',
            'historia_clinica'       => $internacion['historia_clinica'],
            'peso_nacer_g'           => $internacion['peso_nacer_g'],
            'fecha_ingreso_servicio' => $internacion['fecha_ingreso'],
            'tipo_iaas'              => IaasNorma::tipoPorDispositivo($dispositivo['tipo']),
            'fecha_iaas'             => $fechaIaas,
            'dispositivo_tipo'       => $dispositivo['tipo'],
            'fecha_instalacion'      => $dispositivo['fecha_instalacion'],
            'fecha_retiro'           => $dispositivo['fecha_retiro'],
            'creado_por'             => $usuarioId,
        ]);
        IaasRonda::recalcularEstados((int) $dispositivo['id']);
        return $id;
    }

    public static function terminarTratamiento(array $caso, string $fecha): void
    {
        self::actualizar((int) $caso['id'], ['fecha_fin_tratamiento' => $fecha]);
        if ($caso['dispositivo_id']) {
            IaasRonda::recalcularEstados((int) $caso['dispositivo_id']);
        }
    }

    public static function anular(array $caso, int $usuarioId, string $motivo): void
    {
        Database::conexion()->prepare('UPDATE iaas_caso SET anulado_por = ?, anulado_en = CURRENT_TIMESTAMP, motivo_anulacion = ? WHERE id = ?')
            ->execute([$usuarioId, $motivo, $caso['id']]);
        if ($caso['dispositivo_id']) {
            IaasRonda::recalcularEstados((int) $caso['dispositivo_id']);
        }
    }

    /** Lo que falta para dar la ficha por completa (lista legible; vacía = completa). */
    public static function faltantes(array $datos, array $cultivos): array
    {
        $faltan = [];
        $requeridos = [
            'fecha_ingreso_servicio' => '1.10 Fecha de ingreso al servicio',
            'tipo_iaas' => '2.1 Tipo de IAAS', 'fecha_iaas' => '2.2 Fecha de IAAS', 'criterio' => '2.3 Criterio de definición de caso',
        ];
        $factor = IaasNorma::FACTOR_POR_TIPO[$datos['tipo_iaas'] ?? ''] ?? null;
        if ($factor === 'dispositivo') {
            $requeridos += ['dispositivo_tipo' => '3.1 Dispositivo médico', 'fecha_instalacion' => '3.2 Fecha de instalación'];
        } elseif ($factor === 'cirugia') {
            $requeridos += ['procedimiento_tipo' => '3.7 Tipo de cirugía', 'fecha_procedimiento' => '3.8 Fecha de la cirugía',
                'duracion_min' => '3.9 Duración de la cirugía', 'clase_herida' => '3.10 Clase de herida', 'asa' => '3.11 Clase de ASA'];
        } elseif ($factor === 'parto') {
            $requeridos += ['procedimiento_tipo' => '3.4 Procedimiento', 'fecha_procedimiento' => '3.5 Fecha del procedimiento'];
        }
        if (($datos['tipo_iaas'] ?? '') === 'ITS') {
            $requeridos['its_secundaria'] = '2.4 ITS secundaria a IAAS';
        }
        foreach ($requeridos as $campo => $etiqueta) {
            if (($datos[$campo] ?? null) === null || ($datos[$campo] ?? '') === '') {
                $faltan[] = $etiqueta;
            }
        }
        foreach ($cultivos as $orden => $c) {
            if (!$c['tipo_cultivo'] || (!$c['microorganismo_id'] && !$c['microorganismo_otro'])) {
                $faltan[] = "Cultivo $orden: tipo de cultivo y microorganismo";
            }
        }
        return $faltan;
    }
}
