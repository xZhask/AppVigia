<?php
namespace App\Models;

/**
 * Lo que dice la NTS N° 163-MINSA/2020/CDC (IAAS), en un solo lugar: los
 * servicios (UPSS) y qué factores de riesgo vigila cada uno (Anexo 2), los
 * tipos de IAAS y sus criterios (Anexo 3), y las opciones de los Anexos 5 y 8.
 * Las vistas y los modelos del módulo leen de aquí; no repetir listas.
 */
class IaasNorma
{
    public const UPSS = [
        'UCI_ADULTOS'        => 'UCI adultos',
        'UCI_PEDIATRICA'     => 'UCI pediátrica',
        'UCI_NEONATAL'       => 'UCI neonatal',
        'NEONATOLOGIA'       => 'Neonatología',
        'GINECO_OBSTETRICIA' => 'Gineco-obstetricia',
        'CIRUGIA'            => 'Cirugía',
        'TRAUMATOLOGIA'      => 'Traumatología',
        'MEDICINA'           => 'Medicina',
    ];

    /** Qué se vigila en cada UPSS, en una línea (pantalla de configuración). */
    public const UPSS_DESCRIPCION = [
        'UCI_ADULTOS'        => 'CVC, NPT, catéter de hemodiálisis, CUP y ventilador',
        'UCI_PEDIATRICA'     => 'CVC, NPT, CUP y ventilador',
        'UCI_NEONATAL'       => 'CVC, CVP, NPT y ventilador · por peso al nacer',
        'NEONATOLOGIA'       => 'CVC y CVP · por peso al nacer',
        'GINECO_OBSTETRICIA' => 'Partos vaginales y cesáreas (Anexo 6)',
        'CIRUGIA'            => 'Colecistectomías, hernioplastías (Anexo 7) y CUP',
        'TRAUMATOLOGIA'      => 'Prótesis de cadera (Anexo 7)',
        'MEDICINA'           => 'Catéter urinario permanente (CUP)',
    ];

    public const DISPOSITIVOS = [
        'CVC' => 'Catéter venoso central',
        'CVP' => 'Catéter venoso periférico',
        'NPT' => 'Nutrición parenteral total',
        'HD'  => 'Catéter para hemodiálisis',
        'CUP' => 'Catéter urinario permanente',
        'VM'  => 'Ventilador mecánico',
    ];

    /** Sigla corta para chips y encabezados. */
    public const DISPOSITIVOS_SIGLA = ['CVC' => 'CVC', 'CVP' => 'CVP', 'NPT' => 'NPT', 'HD' => 'Hemodiálisis', 'CUP' => 'CUP', 'VM' => 'VM'];

    /** Anexo 2: dispositivos vigilados por UPSS. */
    public const DISPOSITIVOS_POR_UPSS = [
        'UCI_ADULTOS'        => ['CVC', 'NPT', 'HD', 'CUP', 'VM'],
        'UCI_PEDIATRICA'     => ['CVC', 'NPT', 'CUP', 'VM'],
        'UCI_NEONATAL'       => ['CVC', 'CVP', 'NPT', 'VM'],
        'NEONATOLOGIA'       => ['CVC', 'CVP'],
        'GINECO_OBSTETRICIA' => [],
        'CIRUGIA'            => ['CUP'],
        'TRAUMATOLOGIA'      => [],
        'MEDICINA'           => ['CUP'],
    ];

    public const PROCEDIMIENTOS = [
        'PARTO_VAGINAL'     => 'Partos vaginales',
        'CESAREA'           => 'Cesáreas',
        'COLE_LAPAROTOMIA'  => 'Colecistectomías sin laparoscopía',
        'COLE_LAPAROSCOPIA' => 'Colecistectomías con laparoscopía',
        'HERNIOPLASTIA'     => 'Hernioplastías inguinales',
        'PROTESIS_CADERA'   => 'Cirugías de cadera',
    ];

    /** En singular, para la ficha (3.6/3.7) y el índice de riesgo. */
    public const PROCEDIMIENTO_SINGULAR = [
        'PARTO_VAGINAL'     => 'Parto vaginal',
        'CESAREA'           => 'Cesárea',
        'COLE_LAPAROTOMIA'  => 'Colecistectomía sin laparoscopía (laparotomía)',
        'COLE_LAPAROSCOPIA' => 'Colecistectomía con laparoscopía',
        'HERNIOPLASTIA'     => 'Hernioplastía inguinal',
        'PROTESIS_CADERA'   => 'Prótesis de cadera',
        'OTRA'              => 'Otra cirugía (vigilancia local)',
    ];

    /** Anexo 2: procedimientos (denominadores de los Anexos 6 y 7) por UPSS. */
    public const PROCEDIMIENTOS_POR_UPSS = [
        'GINECO_OBSTETRICIA' => ['PARTO_VAGINAL', 'CESAREA'],
        'CIRUGIA'            => ['COLE_LAPAROTOMIA', 'COLE_LAPAROSCOPIA', 'HERNIOPLASTIA'],
        'TRAUMATOLOGIA'      => ['PROTESIS_CADERA'],
    ];

    /** 5.1.2.1: neonatos, estratificados por peso al nacer. */
    public const UPSS_NEONATALES = ['UCI_NEONATAL', 'NEONATOLOGIA'];

    public const CATEGORIAS_PESO = [
        'P1' => '≤ 750 g',
        'P2' => '751 – 1000 g',
        'P3' => '1001 – 1500 g',
        'P4' => '1501 – 2500 g',
        'P5' => '> 2500 g',
    ];

    public const TIPOS_IAAS = [
        'ITS'      => 'Infección del torrente sanguíneo',
        'ITU'      => 'Infección del tracto urinario',
        'NEU'      => 'Neumonía',
        'ISQ_SUP'  => 'Infección de sitio quirúrgico superficial',
        'ISQ_PROF' => 'Infección de sitio quirúrgico profunda',
        'END'      => 'Endometritis puerperal',
    ];

    public const TIPOS_IAAS_SIGLA = ['ITS' => 'ITS', 'ITU' => 'ITU', 'NEU' => 'NEU', 'ISQ_SUP' => 'ISQ sup.', 'ISQ_PROF' => 'ISQ prof.', 'END' => 'END'];

    /** Qué factor de riesgo (sección III) corresponde a cada tipo de IAAS. */
    public const FACTOR_POR_TIPO = [
        'ITS' => 'dispositivo', 'ITU' => 'dispositivo', 'NEU' => 'dispositivo',
        'ISQ_SUP' => 'cirugia', 'ISQ_PROF' => 'cirugia', 'END' => 'parto',
    ];

    /** Anexo 2: dispositivos asociados a cada tipo de IAAS. */
    public const DISPOSITIVOS_POR_TIPO = [
        'ITS' => ['CVC', 'CVP', 'NPT', 'HD'],
        'ITU' => ['CUP'],
        'NEU' => ['VM'],
    ];

    /** Cirugías vigiladas para ISQ (la cesárea incluida) y partos para END. */
    public const CIRUGIAS_ISQ = ['CESAREA', 'COLE_LAPAROTOMIA', 'COLE_LAPAROSCOPIA', 'HERNIOPLASTIA', 'PROTESIS_CADERA', 'OTRA'];
    public const PARTOS_END = ['PARTO_VAGINAL', 'CESAREA'];

    /**
     * Anexo 3, resumido para la ayuda de la ficha. La definición completa
     * está en la norma (págs. 27-35); esto solo orienta al elegir.
     */
    public const CRITERIOS = [
        'ITS' => [
            'BAC-1'   => ['Bacteriemia confirmada por laboratorio · Criterio 1', 'Agente patógeno aislado en 1 o más hemocultivos (no contaminante común de la piel), sin relación con infección en otro sitio.'],
            'BAC-2'   => ['Bacteriemia confirmada por laboratorio · Criterio 2', 'Fiebre > 38 °C, escalofríos o hipotensión (en neonatos: fiebre, hipotermia, apnea o bradicardia), y contaminante común de la piel en 2 o más hemocultivos de extracciones distintas.'],
            'SCLIN-1' => ['Sepsis clínica · Criterio 1', 'Signos o síntomas sin otra causa (≤ 1 año: fiebre, hipotermia, apnea o bradicardia), hemocultivo sin crecimiento o no realizado, sin infección aparente en otro sitio y el médico instituye tratamiento para sepsis.'],
        ],
        'ITU' => [
            'ITU-1' => ['Criterio 1', 'Al menos 1 signo (fiebre, urgencia, frecuencia, disuria o dolor suprapúbico) y urocultivo ≥ 10⁵ con ≤ 2 especies.'],
            'ITU-2' => ['Criterio 2', 'Al menos 2 signos y uno de: tira reactiva positiva, piuria, Gram positivo, ≤ 10⁵ de un uropatógeno en tratamiento, diagnóstico médico o tratamiento indicado.'],
            'ITU-3' => ['Criterio 3 · ≤ 1 año', 'Al menos 1 signo (fiebre, hipotermia, apnea, bradicardia, letargia, vómitos o dolor suprapúbico) y urocultivo positivo ≥ 10⁵ UFC con ≤ 2 especies.'],
            'ITU-4' => ['Criterio 4 · ≤ 1 año', 'Al menos 1 signo y uno de: tira reactiva positiva, piuria, Gram positivo, ≤ 10⁵ de un uropatógeno en tratamiento, diagnóstico médico o tratamiento indicado.'],
        ],
        'NEU' => [
            'NEU-1' => ['Criterio 1', '2 o más radiografías seriadas con infiltrado, consolidación o cavitación; 1 signo (fiebre, leucopenia/leucocitosis o, > 70 años, estado mental alterado) y 2 respiratorios.'],
            'NEU-2' => ['Criterio 2', 'Imágenes como el criterio 1, 1 signo, 1 respiratorio y al menos 1 dato de laboratorio (hemocultivo, líquido pleural, cultivo cuantitativo, lavado broncoalveolar o histopatología).'],
            'NEU-3' => ['Criterio 3 · ≤ 1 año', 'Imágenes (incluye neumatoceles), fiebre o leucopenia/leucocitosis, y 2 de: secreciones, tos/disnea/taquipnea, estertores o empeoramiento del intercambio de gases.'],
            'NEU-4' => ['Criterio 4 · ≤ 1 año', 'Imágenes, empeoramiento del intercambio de gases y por lo menos 3 signos (temperatura inestable, leucocitos, esputo, apnea/aleteo, sibilancias, tos, bradicardia o taquicardia).'],
            'NEU-5' => ['Criterio 5 · ≤ 1 año', 'Al menos 2 signos (apnea, taquipnea, sibilantes, roncantes, tos, bradicardia) y esputo purulento, hemocultivo positivo o cultivo por aspiración transtraqueal, cepillo o biopsia.'],
            'NEU-6' => ['Criterio 6 · > 1 y < 12 años', 'Imágenes según enfermedad de fondo, al menos 3 signos y empeoramiento del intercambio de gases (oximetría < 94 %, más oxígeno o más ventilador).'],
        ],
        'ISQ_SUP' => [
            'ISQS-1' => ['Criterio 1', 'Dentro de 30 días, solo piel o tejido subcutáneo de la incisión; drenaje purulento o cultivo positivo; y dolor, hinchazón, enrojecimiento, incisión abierta por el cirujano o su diagnóstico.'],
        ],
        'ISQ_PROF' => [
            'ISQP-1' => ['Criterio 1', 'Dentro de 30 días (1 año si hay implante), tejidos blandos profundos; drenaje purulento o dehiscencia con fiebre o dolor; y fiebre, dolor, absceso o diagnóstico del cirujano.'],
        ],
        'END' => [
            'END-1' => ['Criterio 1', 'Cultivo positivo del líquido o tejido endometrial, obtenido por cirugía, aspiración por aguja o biopsia por cepillado.'],
            'END-2' => ['Criterio 2', 'Al menos 2 sin otra causa: fiebre > 38 °C, dolor abdominal, sensibilidad uterina o drenaje purulento del útero.'],
        ],
    ];

    public const CONDICION_EGRESO = ['V' => 'Vivo', 'F' => 'Fallecido', 'T' => 'Transferido'];

    public const TIPOS_CULTIVO = [
        'HEMOCULTIVO'         => 'Hemocultivo',
        'UROCULTIVO'          => 'Urocultivo',
        'SECRECION_BRONQUIAL' => 'Secreción bronquial',
        'SECRECION_HERIDA'    => 'Secreción de herida',
        'OTRO'                => 'Otro',
    ];

    public const FAMILIAS_MICROORGANISMO = [
        1 => 'Cocos gram positivos',
        2 => 'Bacilos gram positivos',
        3 => 'Enterobacterias',
        4 => 'Bacilos gram negativos no fermentadores',
        5 => 'Levaduras y otros hongos unicelulares',
        6 => 'Hongos filamentosos',
        7 => 'Otros microorganismos',
    ];

    public const CLASE_HERIDA = [
        'L'  => ['Limpia', 'No infectada, no inflamada; no se abren los tractos respiratorio, digestivo, genital ni urinario.'],
        'LC' => ['Limpia contaminada', 'Se abren esos tractos en condiciones controladas, sin contaminación inusual.'],
        'C'  => ['Contaminada', 'Heridas abiertas, frescas o accidentales; quiebres de la técnica estéril; inflamación aguda no purulenta.'],
        'I'  => ['Infectada o sucia', 'Heridas traumáticas no recientes, infección clínica existente o víscera perforada.'],
    ];

    public const ASA = [
        1 => 'Paciente sano normal',
        2 => 'Enfermedad sistémica leve',
        3 => 'Enfermedad sistémica grave, no potencialmente mortal',
        4 => 'Enfermedad sistémica grave, amenaza constante para la vida',
        5 => 'Moribundo, no se espera que sobreviva sin la operación',
    ];

    // ---------- Anexo 8 ----------
    public const PROFESIONES = [
        'MEDICINA' => 'Medicina', 'ENFERMERIA' => 'Enfermería', 'TEC_ENFERMERIA' => 'Técnico de enfermería',
        'TEC_LABORATORIO' => 'Técnico de laboratorio', 'LIMPIEZA' => 'Limpieza', 'OTRO' => 'Otra',
    ];

    /** También son las columnas del Anexo 9. */
    public const CONDICIONES_PERSONAL = [
        'ESTUDIANTE' => 'Estudiante', 'INTERNO' => 'Interno', 'RESIDENTE' => 'Residente',
        'NOMBRADO' => 'Nombrado', 'CONTRATADO' => 'Contratado', 'OTRO' => 'Otro',
    ];

    public const SERVICIOS_EXPOSICION = [
        'EMERGENCIA' => 'Emergencia', 'OBSTETRICIA' => 'Obstetricia', 'CENTRO_QUIRURGICO' => 'Centro quirúrgico',
        'UCI' => 'Unidad de cuidados intensivos', 'CONSULTORIOS' => 'Consultorios externos',
        'BANCO_SANGRE' => 'Banco de sangre', 'LABORATORIO' => 'Laboratorio', 'OTRO' => 'Otro',
    ];

    public const FACTORES_EXPOSICION = ['PUNZOCORTANTE' => 'Accidente punzocortante', 'SALPICADURA' => 'Salpicadura'];

    public const PROFUNDIDADES = [
        'SUPERFICIAL' => ['Superficial', 'sin sangrado'],
        'MODERADA'    => ['Moderada', 'perforación superficial, exceso de sangrado'],
        'GRAVE'       => ['Grave', 'perforación profunda, sangrado profuso'],
    ];

    public const FLUIDOS = ['SANGRE' => 'Sangre', 'ORINA' => 'Orina', 'VOMITO' => 'Vómito', 'ESPUTO' => 'Esputo', 'OTRO' => 'Otro'];

    public const EPP = ['GUANTES' => 'Guantes', 'LENTES' => 'Lentes o protector ocular', 'MANDIL' => 'Mandil', 'MASCARILLA' => 'Mascarilla', 'RESPIRADOR' => 'Respirador'];

    public const LOCALIZACIONES = ['MANO' => 'Mano', 'OTRA' => 'Otra parte del cuerpo'];

    public const PROCEDIMIENTOS_EXPOSICION = [
        'DESCONOCIDO'     => 'Desconocido / no aplicable',
        'INYECCION_IM_SC' => 'Inyección intramuscular o subcutánea',
        'OTRA_INYECCION'  => 'Otra inyección',
        'VIA_IV'          => 'Colocar o conectar vía intravenosa',
        'SUTURA'          => 'Sutura',
        'TOMA_MUESTRA'    => 'Toma de muestra de fluido corporal o tejido',
        'OTRO'            => 'Otro',
    ];

    public const MESES = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public static function esNeonatal(string $upss): bool
    {
        return in_array($upss, self::UPSS_NEONATALES, true);
    }

    public static function categoriaPeso(?int $gramos): ?string
    {
        if (!$gramos) {
            return null;
        }
        return match (true) {
            $gramos <= 750  => 'P1',
            $gramos <= 1000 => 'P2',
            $gramos <= 1500 => 'P3',
            $gramos <= 2500 => 'P4',
            default         => 'P5',
        };
    }

    /** Tipo de IAAS que corresponde a un dispositivo (para "Inicia IAAS" en la ronda). */
    public static function tipoPorDispositivo(string $dispositivo): string
    {
        foreach (self::DISPOSITIVOS_POR_TIPO as $tipo => $dispositivos) {
            if (in_array($dispositivo, $dispositivos, true)) {
                return $tipo;
            }
        }
        return 'ITS';
    }

    /** "Sala A · UCI adultos", o solo el servicio si la sala se llama igual. */
    public static function nombreSala(array $sala): string
    {
        $servicio = self::UPSS[$sala['upss']] ?? $sala['upss'];
        return mb_strtolower(trim($sala['nombre'])) === mb_strtolower($servicio) ? $servicio : $servicio . ' · ' . $sala['nombre'];
    }

    public static function nombreMes(int $mes, int $anio, bool $capital = true): string
    {
        $nombre = self::MESES[$mes] ?? '';
        return ($capital ? mb_strtoupper(mb_substr($nombre, 0, 1)) . mb_substr($nombre, 1) : $nombre) . ' ' . $anio;
    }

    /**
     * Índice de riesgo de ISQ (Anexo 5, ítem 32): herida contaminada o sucia
     * (1), ASA 3-5 (1) y duración mayor que las horas t de esa cirugía (1).
     * Devuelve null si falta algún dato o la cirugía no tiene horas t.
     *
     * @return array{indice: int, herida: int, asa: int, duracion: int, horas_t: float}|null
     */
    public static function indiceRiesgo(?string $herida, ?int $asa, ?int $duracionMin, ?float $horasT): ?array
    {
        if ($herida === null || $herida === '' || !$asa || $duracionMin === null || $horasT === null) {
            return null;
        }
        $puntos = [
            'herida'   => in_array($herida, ['C', 'I'], true) ? 1 : 0,
            'asa'      => $asa >= 3 ? 1 : 0,
            'duracion' => $duracionMin > $horasT * 60 ? 1 : 0,
        ];
        return ['indice' => array_sum($puntos)] + $puntos + ['horas_t' => $horasT];
    }
}
