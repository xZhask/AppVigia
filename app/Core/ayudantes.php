<?php
// Funciones de ayuda para las vistas. Sin namespace: de uso global.

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
