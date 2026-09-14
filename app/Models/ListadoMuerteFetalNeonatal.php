<?php
namespace App\Models;

use App\Core\Database;
use RuntimeException;

/**
 * "Ficha de notificación de muerte fetal y neonatal" (pág. 28 del PDF, Anexo 1
 * de la R.M. 279-2009/MINSA): el listado semanal de un establecimiento, una
 * fila por fallecido. No captura nada: lee las fichas P96 notificadas en esa
 * semana epidemiológica (caso.anio_epi/semana_epi, que salen de la fecha de
 * notificación, igual que en las demás fichas).
 *
 * Los campos se resuelven por CLAVE (estable entre recargas del manifiesto),
 * nunca por id, igual que RegistroBusquedaActivaVih.
 */
class ListadoMuerteFetalNeonatal
{
    public const CIE10 = 'P96';

    /** Nombre exacto del formato en el PDF: pantalla, enlace de Reportes y Excel. */
    public const NOMBRE = 'Ficha de notificación de muerte fetal y neonatal';

    public const SUBSISTEMA = 'Subsistema Nacional de Vigilancia Epidemiológica Perinatal y Neonatal';

    /** Lo que se imprime cuando el fallecido no tiene nombres (decisión del usuario 2026-09-13). */
    public const SIN_NOMBRE = 'NN';

    private const CLAVES = [
        'tipo'           => 'p96_tipo_de_muerte',
        'edad_gestacional' => 'p96_edad_gestacional_semanas',
        'hora_nacimiento' => 'p96_hora_de_nacimiento',
        'fecha_muerte'   => 'p96_fecha_de_muerte',
        'hora_muerte'    => 'p96_hora_de_muerte',
        'peso'           => 'p96_peso_al_nacer_gramos',
        'causa_basica'   => 'p96_causa_basica_de_muerte',
        'cie10'          => 'p96_cie10_causa_basica',
        'dias_estancia'  => 'p96_n_de_dias_de_estancia_hospitalaria',
        'lugar_parto'    => 'p96_lugar_del_parto',
        'momento'        => 'p96_momento_de_ocurrencia_del_fallecimiento',
        'lugar_muerte'   => 'p96_lugar_de_la_muerte',
    ];

    /** Códigos de las opciones -> lo que el PDF anota en la celda (notas 3 y 4). */
    private const LUGAR_PARTO = ['PI_PARTO_INSTITUCIONAL' => 'PI', 'PD_PARTO_DOMICILIARIO' => 'PD'];
    private const LUGAR_MUERTE = ['ES_ESTABLECIMIENTO_DE_SALUD' => 'ES', 'CC_COMUNIDAD' => 'CC'];

    /**
     * Fichas P96 no anuladas notificadas en la semana, ya traducidas a las
     * columnas del formato.
     *
     * @param array{anio: int, semana: int, establecimiento_id: ?int, usuario: array} $filtros
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
            'anio'          => $filtros['anio'],
            'semana'        => $filtros['semana'],
        ];
        foreach (self::CLAVES as $alias => $clave) {
            $campo = CampoDef::porClave((int) $enfermedad['id'], $clave);
            if (!$campo) {
                throw new RuntimeException("ListadoMuerteFetalNeonatal: la clave {$clave} no existe en la ficha P96 (¿cambió el manifiesto?).");
            }
            $columnas[] = "MAX(CASE WHEN cv.campo_def_id = :id_{$alias} THEN cv.valor END) AS {$alias}";
            $parametros["id_{$alias}"] = (int) $campo['id'];
        }

        $condiciones = [
            'c.enfermedad_id = :enfermedad_id',
            'c.anulado = 0',
            'c.anio_epi = :anio',
            'c.semana_epi = :semana',
        ];
        if (!empty($filtros['establecimiento_id'])) {
            $condiciones[] = 'c.establecimiento_id = :establecimiento_id';
            $parametros['establecimiento_id'] = (int) $filtros['establecimiento_id'];
        }
        // Misma regla que CasosController::puedeVerCaso(): un REGISTRADOR ve
        // las fichas de su establecimiento (y, en las privadas, solo las suyas).
        $usuario = $filtros['usuario'];
        if (($usuario['rol'] ?? '') === 'REGISTRADOR') {
            $condiciones[] = 'c.establecimiento_id = :establecimiento_usuario';
            $parametros['establecimiento_usuario'] = (int) $usuario['establecimiento_id'];
            if (in_array(self::CIE10, Caso::CIE10_PRIVADOS, true)) {
                $condiciones[] = 'c.usuario_id = :usuario_id';
                $parametros['usuario_id'] = (int) $usuario['id'];
            }
        }

        $sql = 'SELECT c.id, c.codigo AS codigo_ficha, c.fecha_notif, es.nombre AS establecimiento,
                       p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo, p.fecha_nac,
                       d.nombre AS distrito, pr.nombre AS provincia, dep.nombre AS departamento,
                       ' . implode(', ', $columnas) . '
                  FROM caso c
                  JOIN persona p          ON p.id = c.persona_id
                  JOIN establecimiento es ON es.id = c.establecimiento_id
             LEFT JOIN distrito d         ON d.id = p.distrito_id
             LEFT JOIN provincia pr       ON pr.id = d.provincia_id
             LEFT JOIN departamento dep   ON dep.id = d.departamento_id
             LEFT JOIN caso_valor cv      ON cv.caso_id = c.id
                 WHERE ' . implode(' AND ', $condiciones) . '
              GROUP BY c.id
              ORDER BY c.fecha_notif, c.id';
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($parametros);

        return array_map([self::class, 'filaDelFormato'], $consulta->fetchAll());
    }

    /**
     * Semana epidemiológica pedida (por defecto, la actual) y establecimiento.
     * Un REGISTRADOR queda fijo en el suyo. Semana inexistente o futura ->
     * la actual.
     *
     * @return array{anio: int, semana: int, establecimiento_id: ?int, usuario: array}
     */
    public static function filtros(array $consulta, array $usuario): array
    {
        $actual = semanaEpidemiologica(date('Y-m-d'));
        $anio = (int) ($consulta['anio'] ?? $actual['anio']);
        $semana = (int) ($consulta['semana'] ?? $actual['semana']);
        if (!self::semanaValida($anio, $semana)) {
            [$anio, $semana] = [$actual['anio'], $actual['semana']];
        }
        $establecimientoId = ($usuario['rol'] ?? '') === 'REGISTRADOR'
            ? (int) $usuario['establecimiento_id']
            : (!empty($consulta['establecimiento_id']) ? (int) $consulta['establecimiento_id'] : null);

        return ['anio' => $anio, 'semana' => $semana, 'establecimiento_id' => $establecimientoId ?: null, 'usuario' => $usuario];
    }

    /** ¿Existe esa semana epidemiológica y no es futura? */
    public static function semanaValida(int $anio, int $semana): bool
    {
        // semanasEpidemiologicasDelAnio(): la 53 solo existe en algunos años.
        if ($anio < 2000 || $semana < 1 || $semana > semanasEpidemiologicasDelAnio($anio)) {
            return false;
        }
        $actual = semanaEpidemiologica(date('Y-m-d'));

        return $anio * 100 + $semana <= $actual['anio'] * 100 + $actual['semana'];
    }

    /** Domingo y sábado de la semana (calendario epidemiológico del MINSA), para mostrar el periodo. */
    public static function rangoDeSemana(int $anio, int $semana): array
    {
        $domingo = inicioSemanaEpidemiologica($anio, $semana);

        return [$domingo->format('Y-m-d'), $domingo->modify('+6 days')->format('Y-m-d')];
    }

    private static function filaDelFormato(array $fila): array
    {
        $apellidos = trim(trim((string) $fila['apellido_paterno']) . ' ' . trim((string) $fila['apellido_materno']));
        $nombres = trim((string) $fila['nombres']);

        return [
            'id'               => (int) $fila['id'],
            'codigo_ficha'     => $fila['codigo_ficha'],
            'establecimiento'  => (string) $fila['establecimiento'],
            'apellidos_nombres' => $apellidos . ', ' . ($nombres !== '' ? $nombres : self::SIN_NOMBRE),
            'sexo'             => in_array($fila['sexo'], ['F', 'M'], true) ? $fila['sexo'] : '',
            'edad_gestacional' => (string) $fila['edad_gestacional'],
            'fecha_nacimiento' => (string) $fila['fecha_nac'],
            'hora_nacimiento'  => (string) $fila['hora_nacimiento'],
            'fecha_muerte'     => (string) $fila['fecha_muerte'],
            'hora_muerte'      => (string) $fila['hora_muerte'],
            'peso'             => (string) $fila['peso'],
            'tipo'             => in_array($fila['tipo'], ['FETAL', 'NEONATAL'], true) ? $fila['tipo'] : '',
            'causa_basica'     => (string) $fila['causa_basica'],
            'cie10'            => (string) $fila['cie10'],
            'dias_estancia'    => (string) $fila['dias_estancia'],
            'lugar_parto'      => self::LUGAR_PARTO[$fila['lugar_parto']] ?? '',
            'momento'          => in_array($fila['momento'], ['ANTEPARTO', 'INTRAPARTO', 'POSTPARTO'], true) ? $fila['momento'] : '',
            'lugar_muerte'     => self::LUGAR_MUERTE[$fila['lugar_muerte']] ?? '',
            'departamento'     => (string) $fila['departamento'],
            'provincia'        => (string) $fila['provincia'],
            'distrito'         => (string) $fila['distrito'],
        ];
    }
}
