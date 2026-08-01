<?php
// Funciones de ayuda para las vistas. Sin namespace: de uso global.

use App\Models\CampoDef;
use App\Models\CatalogoItem;
use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Provincia;

/**
 * Arma las opciones iniciales del selector encadenado de UBIGEO a partir
 * de un distrito ya guardado, para que el formulario de edición muestre
 * los tres niveles (departamento, provincia, distrito) correctamente.
 */
function contextoUbigeo(?string $distritoId): array
{
    $departamentoId = '';
    $provinciaId = '';
    $provinciasIniciales = [];
    $distritosIniciales = [];

    if (!empty($distritoId)) {
        $distrito = Distrito::buscarPorId($distritoId);
        if ($distrito) {
            $departamentoId = $distrito['departamento_id'];
            $provinciaId = $distrito['provincia_id'];
            $provinciasIniciales = Provincia::porDepartamento($departamentoId);
            $distritosIniciales = Distrito::porProvincia($provinciaId);
        }
    }

    return [
        'departamentos'            => Departamento::todosOrdenados(),
        'provinciasIniciales'      => $provinciasIniciales,
        'distritosIniciales'       => $distritosIniciales,
        'departamentoSeleccionado' => $departamentoId,
        'provinciaSeleccionada'    => $provinciaId,
        'distritoSeleccionado'     => $distritoId ?? '',
    ];
}

/**
 * Resolver de "columnas_sujeto" (PETICION_P35_RUBEOLA_CONGENITA.md Fase 2):
 * qué columnas de caso_sujeto declara el manifiesto para un rol secundario
 * (hoy MADRE en P35.0 y P96). Recibe el JSON crudo de
 * enfermedad.columnas_sujeto (o caso.enfermedad_columnas_sujeto), no el
 * array de $enfermedad completo, para no acoplarse a si el llamador tiene
 * una fila de enfermedad o una fila de caso con el join ya hecho.
 */
function columnasSujeto(?string $columnasSujetoJson, string $rol): array
{
    if (!$columnasSujetoJson) {
        return [];
    }
    $decodificado = json_decode($columnasSujetoJson, true);
    return is_array($decodificado[$rol] ?? null) ? $decodificado[$rol] : [];
}

/**
 * true si la ficha tiene bloque propio (identidad/residencia) para ese rol
 * -- la sola presencia del rol como clave de columnas_sujeto es lo que lo
 * activa, no hay un booleano aparte (mismo idioma que nucleo_omitidos).
 */
function tieneSujeto(?string $columnasSujetoJson, string $rol): bool
{
    return columnasSujeto($columnasSujetoJson, $rol) !== [];
}

/**
 * Metadatos columna → control para el bloque de identidad/residencia de un
 * sujeto secundario (PETICION_P35_RUBEOLA_CONGENITA.md Fase 2). El orden de
 * las claves es el orden canónico de render -- ver
 * tablas-hijas/residencia-madre.php, que lo usa vía array_intersect_key()
 * para que el bloque se pinte siempre en este orden sin importar el orden
 * en que columnas_sujeto las declare en el manifiesto. Sigue el orden de
 * los ítems 23-29 del PDF de P35.0 (identidad), con direccion/distrito_id
 * al final (el caso de P96, residencia solamente).
 */
function metaColumnasSujeto(): array
{
    return [
        'tipo_doc'         => ['label' => 'Tipo de documento', 'kind' => 'tipo_doc'],
        'doc'              => ['label' => 'N.° de documento', 'kind' => 'texto'],
        'apellidos'        => ['label' => 'Apellidos', 'kind' => 'texto'],
        'nombres'          => ['label' => 'Nombres', 'kind' => 'texto'],
        'sexo'             => ['label' => 'Sexo', 'kind' => 'sexo'],
        'edad'             => ['label' => 'Edad (años)', 'kind' => 'numero'],
        'fecha_nacimiento' => ['label' => 'Fecha de nacimiento', 'kind' => 'fecha'],
        'nacionalidad'     => ['label' => 'Nacionalidad', 'kind' => 'texto'],
        'ocupacion'        => ['label' => 'Ocupación', 'kind' => 'texto'],
        'direccion'        => ['label' => 'Dirección', 'kind' => 'texto_wide'],
        'distrito_id'      => ['label' => null, 'kind' => 'ubigeo'],
    ];
}

/**
 * Título del bloque de un sujeto secundario, con default explícito cuando
 * la ficha declara columnas_sujeto para el rol pero no titulo_sujeto
 * (verificar_fichas.php avisa de este caso, no bloquea -- ver Fase 2b).
 */
function tituloSujeto(?string $tituloSujetoJson, string $rol): string
{
    $decodificado = $tituloSujetoJson ? json_decode($tituloSujetoJson, true) : null;
    $titulo = is_array($decodificado) ? ($decodificado[$rol] ?? null) : null;
    return $titulo ?? ('Datos de ' . mb_strtolower($rol));
}

function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function seleccionado(mixed $actual, mixed $opcion): string
{
    return (string) $actual === (string) $opcion ? 'selected' : '';
}

function marcado(mixed $valor): string
{
    return $valor ? 'checked' : '';
}

/**
 * Valores de clasificación final permitidos para una ficha: por defecto las
 * 4 genéricas, o el subconjunto propio que defina
 * enfermedad.opciones_clasificacion (CSV) — ej. difteria solo admite
 * Confirmado/Descartado (AUDITORIA_FICHA_DIFTERIA.md, punto 7).
 *
 * @return string[]
 */
function opcionesClasificacionPara(array $enfermedad): array
{
    if (($enfermedad['cie10'] ?? '') === 'O95') {
        return ['DIRECTA', 'INDIRECTA', 'INCIDENTAL', 'POR_DETERMINAR'];
    }
    $genericas = ['SOSPECHOSO', 'PROBABLE', 'CONFIRMADO', 'DESCARTADO'];
    $restriccion = trim($enfermedad['opciones_clasificacion'] ?? '');
    if ($restriccion === '') {
        return $genericas;
    }

    $permitidas = array_map('trim', explode(',', $restriccion));
    return array_values(array_intersect($genericas, $permitidas));
}

/**
 * Evalúa si un campo con `depende_de` debe mostrarse, según el valor ya
 * asignado a su campo padre en $valoresCampos (mismo formato que usa
 * partials/secciones-clinicas.php: MULTISELECT como array, el resto como
 * string). Sin dependencia, siempre visible.
 */
function campoVisiblePorDependencia(array $campo, array $valoresCampos): bool
{
    if (empty($campo['depende_de'])) {
        return true;
    }

    $padre = CampoDef::buscar((int) $campo['depende_de']);
    if (!$padre) {
        return true; // dependencia rota (dato inconsistente): no ocultar por error ajeno al usuario
    }

    $valorPadre = $valoresCampos[(int) $padre['id']] ?? null;

    if ($padre['tipo'] === 'MULTISELECT') {
        return is_array($valorPadre) && in_array($campo['valor_activador'], $valorPadre, true);
    }

    $activadores = array_map('trim', explode(',', (string) $campo['valor_activador']));
    return in_array((string) $valorPadre, $activadores, true);
}

/**
 * Normaliza un nombre propio guardado en mayúsculas sostenidas (ej. nombres
 * de establecimiento en el padrón RENIPRESS) a capitalización de título,
 * para no mostrarlo "GRITANDO" en la interfaz.
 */
function capitalizarNombre(string $texto): string
{
    return mb_convert_case(mb_strtolower($texto, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

function iniciales(string $nombreCompleto): string
{
    $palabras = preg_split('/\s+/', trim($nombreCompleto));
    $palabras = array_filter($palabras);

    if (empty($palabras)) {
        return '';
    }

    if (count($palabras) === 1) {
        return mb_strtoupper(mb_substr($palabras[0], 0, 2));
    }

    $primera = mb_substr(reset($palabras), 0, 1);
    $ultima = mb_substr(end($palabras), 0, 1);

    return mb_strtoupper($primera . $ultima);
}

/**
 * Convierte dd/mm/aaaa a aaaa-mm-dd. Devuelve null si el texto no es una
 * fecha real (rechaza cosas como 31/02/2026, no solo un formato inválido).
 */
function fechaDmyAIso(string $dmy): ?string
{
    $dmy = trim($dmy);
    if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $dmy, $m)) {
        return null;
    }
    [, $dia, $mes, $anio] = $m;
    if (!checkdate((int) $mes, (int) $dia, (int) $anio)) {
        return null;
    }

    return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
}

/**
 * Valida una fecha aaaa-mm-dd (formato nativo de <input type="date">).
 * Devuelve la misma cadena si es una fecha real, o null si no lo es
 * (incluye 31/02, o cualquier texto fuera de formato que llegue a un POST
 * manipulado a mano, ya que el navegador no garantiza el formato).
 *
 * También rechaza años fuera de [1900, año actual + 1]: checkdate() por sí
 * solo acepta cualquier año (1500, 2206...) como "fecha real". Los atributos
 * min/max del <input type="date"> ya evitan esto al usar el navegador; este
 * límite es la misma regla aplicada en el servidor, por si el POST no pasó
 * por el control del navegador.
 */
function fechaIsoValida(?string $iso): ?string
{
    $iso = trim((string) $iso);
    if (!preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $iso, $m)) {
        return null;
    }
    [, $anio, $mes, $dia] = $m;
    if (!checkdate((int) $mes, (int) $dia, (int) $anio)) {
        return null;
    }
    if ((int) $anio < 1900 || (int) $anio > ((int) date('Y') + 1)) {
        return null;
    }

    return $iso;
}

function fechaIsoADmy(?string $iso): string
{
    if (empty($iso)) {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d', substr($iso, 0, 10));

    return $dt ? $dt->format('d/m/Y') : '';
}

/**
 * Semana epidemiológica (aproximación ISO-8601: semana de lunes a domingo,
 * año de la semana en los bordes de diciembre/enero). MINSA usa un calendario
 * epidemiológico propio con pequeñas diferencias; ajustar aquí si se necesita
 * el cálculo oficial exacto.
 */
function semanaEpidemiologica(string $fechaIso): array
{
    $dt = new DateTime($fechaIso);

    return [
        'anio'   => (int) $dt->format('o'),
        'semana' => (int) $dt->format('W'),
    ];
}

/**
 * Reconstruye el query string de un listado paginado conservando los
 * filtros activos y solo cambiando la página (o lo que se pase en $sobrescribir).
 */
function queryConPagina(array $filtros, array $sobrescribir = []): string
{
    return http_build_query(array_filter(array_merge($filtros, $sobrescribir), fn($v) => $v !== '' && $v !== null));
}

/**
 * Enmascara un número de documento para listados: primer dígito, cuatro
 * puntos fijos y los últimos tres dígitos (mismo patrón que el mockup).
 */
function enmascararDocumento(string $numDoc): string
{
    $largo = mb_strlen($numDoc);
    if ($largo <= 4) {
        return $numDoc;
    }

    return mb_substr($numDoc, 0, 1) . '••••' . mb_substr($numDoc, -3);
}

/**
 * Texto legible de un valor guardado en caso_valor, para la vista de solo
 * lectura de la ficha (Ver). $campo es una fila de campo_def.
 */
function campoValorTexto(array $campo, ?string $valorCrudo): string
{
    if ($valorCrudo === null || $valorCrudo === '') {
        return '—';
    }
    switch ($campo['tipo']) {
        case 'BOOLEANO':
            return $valorCrudo === '1' ? 'Sí' : 'No';
        case 'FECHA':
            return fechaIsoADmy($valorCrudo) ?: '—';
        case 'SELECT':
        case 'MULTISELECT':
            $opciones = $campo['catalogo_id'] ? CatalogoItem::porCatalogo((int) $campo['catalogo_id']) : [];
            $mapa = array_column($opciones, 'etiqueta', 'valor');
            $valores = $campo['tipo'] === 'MULTISELECT' ? explode(',', $valorCrudo) : [$valorCrudo];
            return implode(', ', array_map(fn($v) => $mapa[$v] ?? $v, $valores));
        default:
            return $valorCrudo;
    }
}

/**
 * Secuencia ordenada de semanas epidemiológicas entre dos pares (año, SE),
 * inclusive, para rellenar de ceros las semanas sin casos en la curva
 * epidemiológica (el conteo real de cada semana lo resuelve SQL; esto solo
 * genera el calendario de semanas a mostrar).
 *
 * @return array<int, array{anio: int, semana: int}>
 */
function semanasEnRango(int $anioDesde, int $seDesde, int $anioHasta, int $seHasta): array
{
    // Cualquier día de esa semana ISO sirve como ancla; el jueves siempre
    // cae dentro de la semana correcta (regla ISO-8601).
    $cursor = new DateTime();
    $cursor->setISODate($anioDesde, $seDesde, 4);
    $fin = new DateTime();
    $fin->setISODate($anioHasta, $seHasta, 4);

    $semanas = [];
    while ($cursor <= $fin) {
        $semanas[] = ['anio' => (int) $cursor->format('o'), 'semana' => (int) $cursor->format('W')];
        $cursor->modify('+7 days');
    }

    return $semanas;
}

function edadDesdeFecha(?string $fechaNacIso): ?int
{
    if (empty($fechaNacIso)) {
        return null;
    }
    $nacimiento = DateTime::createFromFormat('Y-m-d', substr($fechaNacIso, 0, 10));
    if (!$nacimiento) {
        return null;
    }

    return $nacimiento->diff(new DateTime())->y;
}
