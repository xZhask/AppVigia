<?php
namespace App\Models;

use App\Core\Database;
use DateTime;
use RuntimeException;

/**
 * "Formulario de registro de casos de gestantes con VIH y niños nacidos
 * expuestos al VIH identificados por búsqueda activa institucional" (pág. 15
 * del PDF): una fila por ficha Z21 del periodo, con su resumen por tipo,
 * servicio de captación y estado de notificación.
 *
 * No captura nada: lee lo que la ficha Z21 ya guarda. Los campos se resuelven
 * por CLAVE (estable entre recargas del manifiesto), nunca por id. La
 * clasificación de caso es caso.clasificacion, que la ficha calcula al guardar
 * (reglas "clasificar" de enfermedad.reglas_campos).
 */
class RegistroBusquedaActivaVih
{
    public const CIE10 = 'Z21';

    /**
     * Nombre exacto del formato en el PDF (pedido del usuario 2026-09-13):
     * así se llama en la pantalla, la pestaña, el enlace de Reportes y el
     * Excel, para no confundirlo con otros reportes sobre VIH (B24).
     */
    public const NOMBRE = 'Formulario de registro de casos de gestantes con VIH y niños nacidos expuestos al VIH identificados por búsqueda activa institucional';

    private const CLAVES = [
        'tipo'           => 'z21_tipo_de_registro',
        'codigo'         => 'z21_codigo',
        'servicio'       => 'z21_servicio_de_captacion',
        'notificado'     => 'z21_notificado_al_sistema_de_vigilancia',
        'culminacion'    => 'z21_fecha_de_culminacion_del_embarazo',
        'def_gestante'   => 'z21_fecha_de_defuncion_de_la_gestante',
        'def_nino'       => 'z21_fecha_de_defuncion_del_nino',
        'observaciones'  => 'z21_observaciones',
    ];

    /** Clasificación de caso -> número de la columna del formato (nota 2). */
    public const NUMERO_CLASIFICACION = [
        'GESTANTE_CON_VIH'  => 1,
        'ABORTO'            => 2,
        'MORTINATO'         => 3,
        'NINO_EXPUESTO_VIH' => 4,
    ];

    /** Servicio de captación -> abreviatura de la columna del formato. */
    public const SERVICIOS = [
        'CONSULTORIO_EXTERNO' => ['corta' => 'C. Ext.', 'larga' => 'Consultorio externo'],
        'HOSPITALIZACION'     => ['corta' => 'Hosp.',   'larga' => 'Hospitalización'],
        'EMERGENCIA'          => ['corta' => 'EMG',     'larga' => 'Emergencia'],
    ];

    /** Orden de los servicios en el cuadro resumen del formato (distinto al de la tabla). */
    public const ORDEN_SERVICIOS_RESUMEN = ['CONSULTORIO_EXTERNO', 'EMERGENCIA', 'HOSPITALIZACION'];

    /**
     * Fichas Z21 no anuladas notificadas en el periodo, ya traducidas a las
     * columnas del formato.
     *
     * @param array{desde: string, hasta: string, establecimiento_id: ?int, usuario: array} $filtros
     */
    public static function casos(array $filtros): array
    {
        $enfermedad = Enfermedad::buscarPorCie10(self::CIE10);
        if (!$enfermedad) {
            return [];
        }

        $columnas = [];
        $parametros = [
            'enfermedad_id' => (int) $enfermedad['id'],
            'desde'         => $filtros['desde'],
            'hasta'         => $filtros['hasta'],
        ];
        foreach (self::CLAVES as $alias => $clave) {
            $campo = CampoDef::porClave((int) $enfermedad['id'], $clave);
            if (!$campo) {
                throw new RuntimeException("RegistroBusquedaActivaVih: la clave {$clave} no existe en la ficha Z21 (¿cambió el manifiesto?).");
            }
            $columnas[] = "MAX(CASE WHEN cv.campo_def_id = :id_{$alias} THEN cv.valor END) AS {$alias}";
            $parametros["id_{$alias}"] = (int) $campo['id'];
        }

        $condiciones = [
            'c.enfermedad_id = :enfermedad_id',
            'c.anulado = 0',
            'c.fecha_notif BETWEEN :desde AND :hasta',
        ];
        if (!empty($filtros['establecimiento_id'])) {
            $condiciones[] = 'c.establecimiento_id = :establecimiento_id';
            $parametros['establecimiento_id'] = (int) $filtros['establecimiento_id'];
        }
        // Misma regla que CasosController::puedeVerCaso(): Z21 es ficha
        // privada, un REGISTRADOR solo ve las suyas y de su establecimiento.
        $usuario = $filtros['usuario'];
        if (($usuario['rol'] ?? '') === 'REGISTRADOR') {
            $condiciones[] = 'c.usuario_id = :usuario_id AND c.establecimiento_id = :establecimiento_usuario';
            $parametros['usuario_id'] = (int) $usuario['id'];
            $parametros['establecimiento_usuario'] = (int) $usuario['establecimiento_id'];
        }

        $sql = 'SELECT c.id, c.codigo AS codigo_ficha, c.fecha_notif, c.clasificacion,
                       p.n_historia_clinica, p.fecha_nac, p.sexo, ' . implode(', ', $columnas) . '
                  FROM caso c
                  JOIN persona p ON p.id = c.persona_id
             LEFT JOIN caso_valor cv ON cv.caso_id = c.id
                 WHERE ' . implode(' AND ', $condiciones) . '
              GROUP BY c.id
              ORDER BY c.fecha_notif, c.id';
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        return array_map([self::class, 'filaDelFormato'], $consulta->fetchAll());
    }

    /**
     * Resumen "Total de casos identificados": por tipo (gestantes / niños),
     * según servicio de captación y según estado de notificación. Las fichas
     * sin ese dato van a "SIN_DATO" para que los totales cuadren.
     */
    public static function resumen(array $casos): array
    {
        $vacio = [
            'total'      => 0,
            'servicio'   => array_fill_keys(array_merge(self::ORDEN_SERVICIOS_RESUMEN, ['SIN_DATO']), 0),
            'notificado' => ['SI' => 0, 'NO' => 0, 'SIN_DATO' => 0],
        ];
        $resumen = ['GESTANTE' => $vacio, 'NINO' => $vacio];
        foreach ($casos as $caso) {
            if (!isset($resumen[$caso['tipo']])) {
                continue;
            }
            $fila = &$resumen[$caso['tipo']];
            $fila['total']++;
            $fila['servicio'][$caso['servicio'] ?: 'SIN_DATO']++;
            $fila['notificado'][$caso['notificado'] ?: 'SIN_DATO']++;
            unset($fila);
        }

        return $resumen;
    }

    /**
     * Encabezado del formato para un establecimiento (DISA, red, institución,
     * ubicación). Todo vacío si no se eligió uno: el reporte abarca varios.
     */
    public static function encabezado(?int $establecimientoId): array
    {
        return Establecimiento::encabezadoFormulario($establecimientoId);
    }

    private static function filaDelFormato(array $fila): array
    {
        $tipo = match ($fila['tipo']) {
            'GESTANTE_CON_VIH'            => 'GESTANTE',
            'NINO_NACIDO_EXPUESTO_AL_VIH' => 'NINO',
            default                       => '',
        };
        $numeroClasificacion = self::NUMERO_CLASIFICACION[$fila['clasificacion']] ?? null;

        // Nota 3 del formato: fecha de defunción de la gestante, del niño
        // expuesto, o del aborto/mortinato (= culminación del embarazo).
        $fechaDefuncion = match ($numeroClasificacion) {
            2, 3    => $fila['culminacion'],
            4       => $fila['def_nino'],
            default => $fila['def_gestante'],
        };
        $observaciones = [];
        if (in_array($numeroClasificacion, [2, 3], true) && !empty($fila['def_gestante'])) {
            $observaciones[] = 'Gestante fallecida el ' . fechaIsoADmy($fila['def_gestante']) . '.';
        }
        if (trim((string) $fila['observaciones']) !== '') {
            $observaciones[] = trim((string) $fila['observaciones']);
        }

        [$edad, $tipoEdad] = self::edadConTipo($fila['fecha_nac'], $fila['fecha_notif']);
        $notificado = json_decode((string) $fila['notificado'], true)['marcado'] ?? '';

        return [
            'id'                  => (int) $fila['id'],
            'codigo_ficha'        => $fila['codigo_ficha'],
            'fecha_notif'         => $fila['fecha_notif'],
            'tipo'                => $tipo,
            'codigo'              => (string) $fila['codigo'],
            'historia_clinica'    => (string) $fila['n_historia_clinica'],
            'edad'                => $edad,
            'tipo_edad'           => $tipoEdad,
            'sexo'                => (string) $fila['sexo'],
            'servicio'            => isset(self::SERVICIOS[$fila['servicio']]) ? $fila['servicio'] : '',
            'clasificacion'       => $numeroClasificacion,
            'fecha_defuncion'     => (string) $fechaDefuncion,
            'notificado'          => in_array($notificado, ['SI', 'NO'], true) ? $notificado : '',
            'observaciones'       => implode(' ', $observaciones),
        ];
    }

    /**
     * Nota 1 del formato: edad en días (d), meses (m) o años (a), a la fecha
     * de notificación. [null, ''] sin fecha de nacimiento.
     *
     * @return array{0: ?int, 1: string}
     */
    private static function edadConTipo(?string $fechaNac, ?string $fechaReferencia): array
    {
        $nacimiento = $fechaNac ? DateTime::createFromFormat('!Y-m-d', substr($fechaNac, 0, 10)) : false;
        $referencia = $fechaReferencia ? DateTime::createFromFormat('!Y-m-d', substr($fechaReferencia, 0, 10)) : false;
        if (!$nacimiento || !$referencia || $nacimiento > $referencia) {
            return [null, ''];
        }
        $diferencia = $nacimiento->diff($referencia);
        if ($diferencia->y >= 1) {
            return [$diferencia->y, 'a'];
        }
        if ($diferencia->m >= 1) {
            return [$diferencia->m, 'm'];
        }

        return [(int) $diferencia->days, 'd'];
    }
}
