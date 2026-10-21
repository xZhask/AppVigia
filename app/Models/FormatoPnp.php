<?php
namespace App\Models;

use App\Core\Database;

/**
 * Formatos del Área de Epidemiología de la Dirección de Salud Policial
 * ("FICHAS NOTIFICACION EPIDEMIOLOGIA 2026.xlsx", 2026-10-21). No capturan
 * nada: leen las fichas y los registros PNP de una semana epidemiológica de
 * un establecimiento.
 *   - individual: "Registro semanal de notificación epidemiológica
 *     individual" (hoja INDIV_TITULAR y FAMILIAR): las fichas MINSA, salvo
 *     las de VIH, y los registros de otras enfermedades.
 *   - VIH: el mismo formato para VIH/SIDA (hoja INDIVIDUAL VIH_SIDA), con
 *     las iniciales del paciente en vez del nombre: fichas B24 y Z21.
 *   - febriles: "Formato de registro diario para la vigilancia de febriles".
 *
 * Los campos de los registros se leen por CLAVE, como en
 * ListadoMuerteFetalNeonatal.
 */
class FormatoPnp
{
    public const INDIVIDUAL = 'Registro semanal de notificación epidemiológica individual';
    public const VIH = 'Registro semanal de notificación epidemiológica individual VIH/SIDA';
    public const FEBRILES = 'Formato de registro diario para la vigilancia de febriles';

    /** Fichas del formato de VIH/SIDA; las demás van al individual. */
    public const CIE10_VIH = ['B24', 'Z21'];

    /** Tipo de Dx del formato (leyenda: P probable, C confirmado, D descartado). */
    private const TIPO_DX = ['SOSPECHOSO' => 'P', 'PROBABLE' => 'P', 'CONFIRMADO' => 'C', 'DESCARTADO' => 'D'];

    private const SITUACION_PNP = ['ACTIVIDAD' => 'ACT', 'RETIRO' => 'RET', 'DISPONIBILIDAD' => 'DISP'];

    private const SERVICIOS = ['CONSULTA_EXTERNA' => 'Cons. externa', 'EMERGENCIA' => 'Emergencia', 'HOSPITALIZACION' => 'Hospitalización'];

    /**
     * Filas del registro individual (o del de VIH/SIDA con $vih) de la semana.
     *
     * @param array{anio: int, semana: int, establecimiento_id: ?int, usuario: array} $filtros
     */
    public static function individual(array $filtros, bool $vih = false): array
    {
        $parametros = [];
        $marcadores = self::lista(self::CIE10_VIH, $parametros, 'vih');
        $condicion = $vih
            ? "e.cie10 IN ($marcadores)"
            : "((e.modalidad = 'FICHA' AND e.cie10 NOT IN ($marcadores)) OR e.cie10 = :otras)";
        if (!$vih) {
            $parametros['otras'] = Cie10Vigilancia::REGISTRO_OTRAS;
        }
        // Los registros de notificación colectiva (EDA, IRA, neumonía, SOB,
        // asma) no van uno por uno: se cuentan en el consolidado.
        $filas = array_values(array_filter(
            self::casos($filtros, $condicion, $parametros),
            fn(array $fila) => $fila['modalidad'] !== 'REGISTRO' || Cie10Vigilancia::notificacionDe((string) $fila['cie10_dx']) !== 'COLECTIVA'
        ));
        $valores = self::valores(array_column($filas, 'id'));

        return array_map(fn(array $fila) => self::filaIndividual($fila, $valores[(int) $fila['id']] ?? [], $vih), $filas);
    }

    /** Filas del registro diario de febriles de la semana (y del día, si se pide). */
    public static function febriles(array $filtros, ?string $dia = null): array
    {
        $parametros = ['feb' => Cie10Vigilancia::REGISTRO_FEBRILES];
        $condicion = 'e.cie10 = :feb';
        if ($dia !== null) {
            $condicion .= ' AND c.fecha_notif = :dia';
            $parametros['dia'] = $dia;
        }
        $filas = self::casos($filtros, $condicion, $parametros);
        $valores = self::valores(array_column($filas, 'id'));

        return array_map(function (array $fila) use ($valores): array {
            $v = $valores[(int) $fila['id']] ?? [];

            return self::situacion($fila) + [
                'id'          => (int) $fila['id'],
                'codigo'      => $fila['codigo'],
                'establecimiento' => (string) $fila['establecimiento'],
                'historia'    => (string) $fila['n_historia_clinica'],
                'fecha'       => (string) $fila['fecha_notif'],
                'nombre'      => self::nombre($fila),
                'dni'         => $fila['tipo_doc'] === 'DNI' ? (string) $fila['num_doc'] : trim($fila['tipo_doc'] . ' ' . $fila['num_doc']),
                'edad'        => self::edad($fila),
                'sexo'        => (string) $fila['sexo'],
                'direccion'   => (string) $fila['direccion'],
                'distrito'    => (string) $fila['distrito_residencia'],
                'temperatura' => (string) ($v['feb_temperatura']['valor'] ?? ''),
                'cie10'       => (string) ($fila['cie10_dx'] ?? ''),
                'diagnostico' => $fila['cie10_dx'] ? (string) (descripcionCie10((string) $fila['cie10_dx']) ?? '') : '',
                'servicio'    => self::SERVICIOS[$v['feb_servicio']['valor'] ?? ''] ?? '',
            ];
        }, $filas);
    }

    /**
     * Cabecera de los formatos: código RENIPRESS, red (cabecera de red),
     * DIRESA, establecimiento y su categoría.
     */
    public static function encabezado(?int $establecimientoId): array
    {
        $vacio = ['codigo' => '', 'red' => '', 'diresa' => '', 'establecimiento' => '', 'categoria' => ''];
        if (!$establecimientoId) {
            return $vacio;
        }
        $consulta = Database::conexion()->prepare(
            'SELECT es.cod_renipress AS codigo, es.nombre AS establecimiento, es.categoria, r.nombre AS red, r.diresa
               FROM establecimiento es LEFT JOIN red_salud r ON r.id = es.red_id WHERE es.id = :id'
        );
        $consulta->execute(['id' => $establecimientoId]);

        return array_map(fn($v) => (string) $v, ($consulta->fetch() ?: []) + $vacio);
    }

    /**
     * Casos no anulados de la semana con lo que piden los formatos PNP,
     * filtrados por $condicion (SQL sobre los alias c, e, p) y con el alcance
     * del usuario. También los usa ConsolidadoPnp.
     */
    public static function casos(array $filtros, string $condicion, array $parametros): array
    {
        $condiciones = ['c.anulado = 0', 'c.anio_epi = :anio', 'c.semana_epi = :semana', $condicion];
        $parametros += ['anio' => $filtros['anio'], 'semana' => $filtros['semana']];
        if (!empty($filtros['establecimiento_id'])) {
            $condiciones[] = 'c.establecimiento_id = :establecimiento_id';
            $parametros['establecimiento_id'] = (int) $filtros['establecimiento_id'];
        }
        // Misma regla que CasosController::puedeVerCaso(): un REGISTRADOR ve
        // su establecimiento y, de las fichas privadas, solo las suyas.
        $usuario = $filtros['usuario'];
        if (($usuario['rol'] ?? '') === 'REGISTRADOR') {
            $condiciones[] = 'c.establecimiento_id = :establecimiento_usuario';
            $parametros['establecimiento_usuario'] = (int) $usuario['establecimiento_id'];
            $privados = self::lista(Caso::CIE10_PRIVADOS, $parametros, 'priv');
            $condiciones[] = "((e.cie10 NOT IN ($privados) AND e.nombre NOT LIKE '%Violencia%') OR c.usuario_id = :usuario_id)";
            $parametros['usuario_id'] = (int) $usuario['id'];
        }

        $consulta = Database::conexion()->prepare(
            'SELECT c.id, c.codigo, c.cie10_dx, c.clasificacion, c.fecha_notif, c.fecha_inicio_sintomas, c.hospitalizado, c.fallecido,
                    c.tipo_captacion, c.edad_valor, c.edad_unidad, c.lugar_contagio_localidad,
                    c.descanso1_inicio, c.descanso1_fin, c.descanso2_inicio, c.descanso2_fin, c.fecha_alta,
                    e.cie10 AS enfermedad_cie10, e.modalidad, e.nombre AS enfermedad,
                    p.tipo_doc, p.num_doc, p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo, p.fecha_nac,
                    p.condicion, p.cip, p.situacion_pnp, p.direccion, p.n_historia_clinica,
                    g.abreviatura AS grado, un.nombre AS unidad, d.nombre AS distrito_residencia, prr.nombre AS provincia_residencia,
                    lcd.nombre AS lugar_distrito, lcp.nombre AS lugar_provincia, es.nombre AS establecimiento
               FROM caso c
               JOIN enfermedad e       ON e.id = c.enfermedad_id
               JOIN persona p          ON p.id = c.persona_id
               JOIN establecimiento es ON es.id = c.establecimiento_id
          LEFT JOIN grado_pnp g        ON g.id = p.grado_id
          LEFT JOIN unidad_pnp un      ON un.id = p.unidad_id
          LEFT JOIN distrito d         ON d.id = p.distrito_id
          LEFT JOIN provincia prr      ON prr.id = d.provincia_id
          LEFT JOIN distrito lcd       ON lcd.id = c.lugar_contagio_distrito_id
          LEFT JOIN provincia lcp      ON lcp.id = lcd.provincia_id
              WHERE ' . implode(' AND ', $condiciones) . '
           ORDER BY c.fecha_notif, c.id'
        );
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    /**
     * Valores guardados por caso y clave, con la etiqueta de la opción elegida
     * (de ahí sale el CIE-10 de las fichas que lo traen en una opción:
     * "Dengue sin signos de alarma (CIE-10: A97.0)").
     *
     * @return array<int, array<string, array{valor: string, etiqueta: ?string}>>
     */
    public static function valores(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $parametros = [];
        $marcadores = self::lista(array_map('intval', $ids), $parametros, 'c');
        $consulta = Database::conexion()->prepare(
            "SELECT cv.caso_id, cd.clave, cv.valor, ci.etiqueta
               FROM caso_valor cv
               JOIN campo_def cd ON cd.id = cv.campo_def_id
          LEFT JOIN catalogo_item ci ON ci.catalogo_id = cd.catalogo_id AND ci.valor = cv.valor
              WHERE cv.caso_id IN ($marcadores)"
        );
        $consulta->execute($parametros);
        $valores = [];
        foreach ($consulta->fetchAll() as $fila) {
            $valores[(int) $fila['caso_id']][$fila['clave']] = ['valor' => (string) $fila['valor'], 'etiqueta' => $fila['etiqueta']];
        }

        return $valores;
    }

    private static function filaIndividual(array $fila, array $v, bool $vih): array
    {
        $esRegistro = $fila['modalidad'] === 'REGISTRO';
        $valor = fn(string $clave): string => (string) ($v[$clave]['valor'] ?? '');

        $cie10 = (string) ($fila['cie10_dx'] ?? '');
        if ($cie10 === '') {
            foreach ($v as $dato) {
                if (preg_match('/\(CIE-10: ([A-Z0-9.]+)\)/', (string) $dato['etiqueta'], $m)) {
                    $cie10 = $m[1];
                }
            }
        }
        if ($cie10 === '') {
            $cie10 = (string) $fila['enfermedad_cie10'];
        }

        if ($esRegistro) {
            $situacion = ['AMBULATORIO' => 'Ambulatorio', 'HOSPITALIZADO' => 'Hospitalizado', 'FALLECIDO' => 'Fallecido'][$valor('reg_situacion')] ?? '';
            $vigilancia = ['VIGILANCIA_PASIVA' => 'PAS', 'BUSQUEDA_ACTIVA' => 'ACT'][$valor('reg_tipo_vigilancia')] ?? '';
            $protegido = ['SI' => 'SI', 'NO' => 'NO', 'IGNORADO' => 'IG'][$valor('reg_protegido')] ?? '';
            $investigacion = ['SI' => 'SI', 'NO' => 'NO'][$valor('reg_ficha_investigacion')] ?? '';
        } else {
            $situacion = $fila['fallecido'] ? 'Fallecido' : ($fila['hospitalizado'] ? 'Hospitalizado' : '');
            $vigilancia = ['PASIVA' => 'PAS', 'ACTIVA' => 'ACT'][$fila['tipo_captacion'] ?? ''] ?? '';
            $protegido = '';
            // Una ficha MINSA es la ficha de investigación del caso.
            $investigacion = 'SI';
        }

        return self::situacion($fila) + [
            'id'            => (int) $fila['id'],
            'codigo'        => $fila['codigo'],
            'establecimiento' => (string) $fila['establecimiento'],
            'enfermedad'    => (string) $fila['enfermedad'],
            'nombre'        => $vih ? self::iniciales($fila) : self::nombre($fila),
            'edad'          => self::edad($fila),
            'sexo'          => (string) $fila['sexo'],
            'cip'           => (string) $fila['cip'],
            'dni'           => $fila['tipo_doc'] === 'DNI' ? (string) $fila['num_doc'] : '',
            'lugar_provincia' => (string) $fila['lugar_provincia'],
            'lugar_distrito'  => (string) $fila['lugar_distrito'],
            'lugar_localidad' => (string) $fila['lugar_contagio_localidad'],
            'direccion'     => trim(implode(' · ', array_filter([(string) $fila['direccion'], (string) $fila['distrito_residencia']]))),
            'cie10'         => $cie10,
            'tipo_dx'       => self::TIPO_DX[$fila['clasificacion']] ?? '',
            'protegido'     => $protegido,
            'inicio_sintomas' => (string) $fila['fecha_inicio_sintomas'],
            // "Difusión" (respuesta del área, 2026-10-21): la fecha en que se
            // hace saber al área de epidemiología del establecimiento;
            // comúnmente la misma de notificación, que es la que se registra.
            'difusion'      => (string) $fila['fecha_notif'],
            'notificacion'  => (string) $fila['fecha_notif'],
            'descanso1_inicio' => (string) $fila['descanso1_inicio'],
            'descanso1_fin'    => (string) $fila['descanso1_fin'],
            'descanso2_inicio' => (string) $fila['descanso2_inicio'],
            'descanso2_fin'    => (string) $fila['descanso2_fin'],
            'fecha_alta'       => (string) $fila['fecha_alta'],
            'investigacion' => $investigacion,
            'vigilancia'    => $vigilancia,
            'unidad'        => (string) $fila['unidad'],
            'situacion'     => $situacion,
            'observaciones' => $valor('reg_observaciones'),
        ];
    }

    /** Columnas "Situación": grado y ACT/RET del efectivo, o F si es familiar. */
    private static function situacion(array $fila): array
    {
        $efectivo = $fila['condicion'] === 'EFECTIVO';

        return [
            'grado'    => $efectivo ? (string) $fila['grado'] : '',
            'act_ret'  => $efectivo ? (self::SITUACION_PNP[$fila['situacion_pnp'] ?? ''] ?? '') : '',
            'familiar' => $fila['condicion'] === 'DERECHOHABIENTE',
        ];
    }

    private static function nombre(array $fila): string
    {
        $apellidos = trim($fila['apellido_paterno'] . ' ' . $fila['apellido_materno']);

        return trim($apellidos . ', ' . $fila['nombres'], ', ');
    }

    /** Iniciales del paciente (formato de VIH/SIDA): apellidos y nombres. */
    private static function iniciales(array $fila): string
    {
        $letras = '';
        foreach ([$fila['apellido_paterno'], $fila['apellido_materno'], $fila['nombres']] as $parte) {
            foreach (preg_split('/\s+/u', trim((string) $parte), -1, PREG_SPLIT_NO_EMPTY) as $palabra) {
                $letras .= mb_strtoupper(mb_substr($palabra, 0, 1));
            }
        }

        return strtr($letras, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U']);
    }

    /** Edad a la fecha de notificación ("36a", "5m"); sin fecha de nacimiento, la declarada. */
    private static function edad(array $fila): string
    {
        $edad = edadLegible($fila['fecha_nac'], $fila['fecha_notif'], true);
        if ($edad !== null) {
            return $edad;
        }
        $unidades = ['ANIOS' => 'a', 'MESES' => 'm', 'DIAS' => 'd', 'HORAS' => 'h', 'MINUTOS' => 'min'];

        return $fila['edad_valor'] !== null ? $fila['edad_valor'] . ($unidades[$fila['edad_unidad']] ?? '') : '';
    }

    /** ":p0, :p1..." para un IN, agregando los valores a $parametros. */
    private static function lista(array $valores, array &$parametros, string $prefijo): string
    {
        $marcadores = [];
        foreach (array_values($valores) as $i => $valor) {
            $parametros[$prefijo . $i] = $valor;
            $marcadores[] = ':' . $prefijo . $i;
        }

        return implode(', ', $marcadores);
    }
}
