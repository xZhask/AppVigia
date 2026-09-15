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
 * Todos los roles que columnas_sujeto declara para esta ficha (hoy casi
 * siempre uno solo, MADRE, pero el mecanismo no asume eso).
 */
function rolesSujetoDeclarados(?string $columnasSujetoJson): array
{
    $decodificado = $columnasSujetoJson ? json_decode($columnasSujetoJson, true) : null;
    return is_array($decodificado) ? array_keys($decodificado) : [];
}

/**
 * De los roles declarados, cuáles NO tienen sección propia en el
 * manifiesto -- esos son los que secciones-clinicas.php no puede anclar
 * (no tiene antes de qué sección pintarlos) y siguen renderizando en la
 * tarjeta de siempre ("Antecedentes epidemiológicos"). $rolesConSeccionPropia
 * viene de CampoDef::rolesConSeccionPropia($enfermedadId).
 */
function rolesSujetoSinAnclaje(?string $columnasSujetoJson, array $rolesConSeccionPropia): array
{
    return array_values(array_diff(rolesSujetoDeclarados($columnasSujetoJson), $rolesConSeccionPropia));
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

/**
 * URL de un asset estático (public/...) con ?v=<mtime> para romper la caché
 * del navegador en cada edición -- sin esto, un cambio en ficha.js puede
 * quedar invisible en un navegador que ya lo tenía cacheado, indistinguible
 * de un bug real (PENDIENTES.md ítem V, adenda). $rutaPublica empieza con
 * "/", p. ej. "/js/ficha.js".
 */
function asset(string $rutaPublica): string
{
    $rutaDisco = __DIR__ . '/../../public' . $rutaPublica;
    $mtime = @filemtime($rutaDisco);
    return $rutaPublica . ($mtime ? '?v=' . $mtime : '');
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
 * Categorías de establecimiento de RENIPRESS (NTS N.° 021-MINSA/DGSP) y el
 * tipo de EE.SS. que les corresponde en las fichas en papel ("Hospital /
 * Centro de Salud / Puesto de Salud"). Es la lista válida de
 * establecimiento.categoria (sql/migraciones/add_categoria_establecimiento.php):
 * el tipo no se captura en la ficha, se toma del establecimiento elegido,
 * igual que DISA/DIRESA e institución.
 */
const CATEGORIAS_ESTABLECIMIENTO = [
    'I-1'   => 'Puesto de Salud',
    'I-2'   => 'Puesto de Salud',
    'I-3'   => 'Centro de Salud',
    'I-4'   => 'Centro de Salud',
    'II-1'  => 'Hospital',
    'II-2'  => 'Hospital',
    'II-E'  => 'Hospital',
    'III-1' => 'Hospital',
    'III-E' => 'Hospital',
    // La norma llama a III-2 "instituto de salud especializado", no hospital:
    // cae en el "Otro" del papel. Ninguna IPRESS PNP del padrón es III-2.
    'III-2' => 'Otro',
];

/** Tipo de EE.SS. de una categoría RENIPRESS; null si no tiene categoría. */
function tipoEstablecimientoPorCategoria(?string $categoria): ?string
{
    return CATEGORIAS_ESTABLECIMIENTO[(string) $categoria] ?? null;
}

/**
 * Catálogo único de valores de clasificación final que puede mostrar el chip
 * "Clasificación del caso" (clasificacion-chips.php), con su etiqueta y color
 * de punto. Cada ficha filtra/ordena un subconjunto vía
 * enfermedad.opciones_clasificacion (CSV) — ej. difteria solo admite
 * Confirmado/Descartado (AUDITORIA_FICHA_DIFTERIA.md, punto 7), O95 usa un
 * subconjunto propio (Directa/Indirecta/Incidental/Por determinar) en vez de
 * las 4 genéricas. Ya no hay excepciones hardcodeadas por CIE-10 aquí: todo
 * ficha nueva que necesite valores propios solo agrega entradas al catálogo
 * y su CSV en la BD.
 */
const CATALOGO_CLASIFICACION = [
    'SOSPECHOSO'     => ['etiqueta' => 'Sospechoso',      'dot' => 'dot-sos'],
    'PROBABLE'       => ['etiqueta' => 'Probable',        'dot' => 'dot-pro'],
    'CONFIRMADO'     => ['etiqueta' => 'Confirmado',      'dot' => 'dot-con'],
    // 'COMPATIBLE' (2026-09-07, cotejo A00 / EDA grave-cólera, "VI.
    // CLASIFICACIÓN" de la pág. 51): caso que no se pudo confirmar ni
    // descartar por laboratorio y se cierra por criterio clínico-
    // epidemiológico. Va entre Confirmado y Descartado porque el orden de
    // salida de opcionesClasificacionPara() es el de esta constante, no el
    // del CSV de la ficha. Sólo lo ve A00 (única con COMPATIBLE en su
    // enfermedad.opciones_clasificacion); las demás no cambian.
    'COMPATIBLE'     => ['etiqueta' => 'Compatible',      'dot' => 'dot-pro'],
    'DESCARTADO'     => ['etiqueta' => 'Descartado',      'dot' => 'dot-des'],
    'DIRECTA'        => ['etiqueta' => 'Directa',         'dot' => 'dot-con'],
    'INDIRECTA'      => ['etiqueta' => 'Indirecta',       'dot' => 'dot-pro'],
    'INCIDENTAL'     => ['etiqueta' => 'Incidental',      'dot' => 'dot-sos'],
    'POR_DETERMINAR' => ['etiqueta' => 'Por determinar',  'dot' => 'dot-des'],
    // Z21 (2026-09-13): "Clasificación de caso" del registro por búsqueda
    // activa (pág. 15 del PDF). No se elige: la calculan las reglas
    // "clasificar" de enfermedad.reglas_campos (ver clasificacionDerivada()).
    'GESTANTE_CON_VIH'  => ['etiqueta' => 'Gestante con VIH',             'dot' => 'dot-con'],
    'ABORTO'            => ['etiqueta' => 'Aborto',                       'dot' => 'dot-des'],
    'MORTINATO'         => ['etiqueta' => 'Mortinato',                    'dot' => 'dot-des'],
    'NINO_EXPUESTO_VIH' => ['etiqueta' => 'Niño nacido expuesto al VIH',  'dot' => 'dot-pro'],
    // P96 (2026-09-13): "Tipo de muerte" del Anexo 1 de la R.M.
    // 279-2009/MINSA. Tampoco se elige: la calcula la regla "clasificar".
    'MUERTE_FETAL'      => ['etiqueta' => 'Muerte fetal',                 'dot' => 'dot-des'],
    'MUERTE_NEONATAL'   => ['etiqueta' => 'Muerte neonatal',              'dot' => 'dot-con'],
];

/**
 * Etiqueta y punto de color de una clasificación guardada, para las vistas
 * que la muestran fuera de la ficha (listado, panel, reportes, ver). Antes
 * cada una tenía su propio mapa con solo las 4 genéricas, y los valores de
 * O95/A00 (y ahora Z21) salían sin etiqueta y con avisos de índice
 * inexistente. Un código desconocido se muestra tal cual.
 *
 * @return array{etiqueta: string, dot: string, color: string}
 */
function datosClasificacion(string $codigo): array
{
    $coloresPorDot = [
        'dot-sos' => 'var(--s-sospechoso)', 'dot-pro' => 'var(--s-probable)',
        'dot-con' => 'var(--s-confirmado)', 'dot-des' => 'var(--s-descartado)',
    ];
    $datos = CATALOGO_CLASIFICACION[$codigo] ?? ['etiqueta' => $codigo, 'dot' => 'dot-sos'];

    return $datos + ['color' => $coloresPorDot[$datos['dot']] ?? 'var(--s-sospechoso)'];
}

/**
 * reglas_campos "clasificar" (Z21, 2026-09-13): la clasificación del caso que
 * corresponde a los valores capturados -- la de la PRIMERA regla cuya
 * condición se cumple, en el orden del manifiesto. null si la ficha no
 * declara reglas "clasificar" o ninguna se cumple.
 */
function clasificacionDerivada(array $enfermedad, callable $valorPorClave): ?string
{
    foreach (jsonDeEnfermedad($enfermedad, 'reglas_campos') as $regla) {
        if (isset($regla['clasificar']) && condicionReglaCampos($regla['si'], $valorPorClave)) {
            return $regla['clasificar'];
        }
    }
    return null;
}

/**
 * reglas_campos "fallecido" (A50, 2026-09-14): el caso es una defunción cuando
 * se cumple alguna de estas reglas (estado vital "Nació vivo, luego falleció",
 * "Mortinato" o "Aborto"). 1 o 0; null si la ficha no declara ninguna, así
 * las demás siguen guardando caso.fallecido como antes.
 */
function fallecidoDerivado(array $enfermedad, callable $valorPorClave): ?int
{
    $declara = false;
    foreach (jsonDeEnfermedad($enfermedad, 'reglas_campos') as $regla) {
        if (empty($regla['fallecido'])) {
            continue;
        }
        $declara = true;
        if (condicionReglaCampos($regla['si'], $valorPorClave)) {
            return 1;
        }
    }
    return $declara ? 0 : null;
}

/** ¿La ficha calcula la clasificación del caso en vez de pedirla? */
function fichaDerivaClasificacion(array $enfermedad): bool
{
    foreach (jsonDeEnfermedad($enfermedad, 'reglas_campos') as $regla) {
        if (isset($regla['clasificar'])) {
            return true;
        }
    }
    return false;
}

/**
 * Valores de clasificación final permitidos para una ficha: por defecto las
 * 4 genéricas, o el subconjunto/orden propio que defina
 * enfermedad.opciones_clasificacion (CSV), intersectado contra
 * CATALOGO_CLASIFICACION (así el orden de salida es siempre el del catálogo,
 * no el de la BD).
 *
 * @return string[]
 */
function opcionesClasificacionPara(array $enfermedad): array
{
    $restriccion = trim($enfermedad['opciones_clasificacion'] ?? '');
    if ($restriccion === '') {
        return ['SOSPECHOSO', 'PROBABLE', 'CONFIRMADO', 'DESCARTADO'];
    }

    $permitidas = array_map('trim', explode(',', $restriccion));
    return array_values(array_intersect(array_keys(CATALOGO_CLASIFICACION), $permitidas));
}

/**
 * Una ficha "requiere elección explícita" de Clasificación del caso cuando
 * su primera opción permitida (opcionesClasificacionPara()[0]) ya es un
 * resultado definitivo tipo CONFIRMADO, en vez de un estado neutral tipo
 * SOSPECHOSO/PROBABLE. Hoy eso pasa con las fichas restringidas a solo
 * "CONFIRMADO,DESCARTADO" (A33/A35/A36/A95/B57, sin ninguna opción
 * intermedia): pre-marcar "Confirmado" por defecto en un caso nuevo dejaba
 * que un registro se guardara como Confirmado sin que nadie lo hubiera
 * elegido -- distorsiona reportes que cuentan por caso.clasificacion.
 * Para estas fichas, "nuevo()" no pre-marca ningún chip y "crear()" exige
 * que el usuario elija Confirmado o Descartado antes de guardar.
 */
function clasificacionRequiereEleccionExplicita(array $enfermedad): bool
{
    return (opcionesClasificacionPara($enfermedad)[0] ?? null) === 'CONFIRMADO';
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

    // SI_NO (campos/si-no.php) guarda su valor como array ['marcado' => 'SI'|
    // 'NO'|'IGNORADO'], no como escalar -- sin este caso, (string) $valorPadre
    // convertía el array entero al literal "Array" (con warning), la
    // comparación nunca coincidía y cualquier hijo de un padre SI_NO se
    // guardaba vacío en silencio aunque el navegador lo mostrara lleno.
    // Hallado 2026-09-02 al dar "Recibe TAR"/"¿Utilizó red social...?" (B04X,
    // Sección V) hijos por primera vez -- el único SI_NO anterior (B55) no
    // tenía ninguno, por eso el bug estaba dormido.
    if ($padre['tipo'] === 'SI_NO') {
        $valorPadre = is_array($valorPadre) ? ($valorPadre['marcado'] ?? '') : $valorPadre;
    }

    $activadores = array_map('trim', explode(',', (string) $campo['valor_activador']));
    return in_array((string) $valorPadre, $activadores, true);
}

/**
 * reglas_campos (Z21, 2026-09-13, "Culminación del embarazo"): ¿se cumple la
 * condición "si" de una regla? Dos formas, las que valida cargar_fichas.php:
 *   {"clave": ..., "valores": [...]}             el valor del campo es uno de esos
 *   {"claves": [...], "alguno_mayor_que": N}     algún NUMERO de la lista supera N
 * $valorPorClave(clave) devuelve el valor crudo del campo; un SI_NO llega como
 * ['marcado' => 'SI'|'NO'] y se compara por su marca. Un MULTISELECT (A50,
 * 2026-09-14) llega como lista y cumple si alguna opción marcada está en
 * "valores". La misma lógica vive en condicionReglaCumplida() de ficha.js.
 */
function condicionReglaCampos(array $si, callable $valorPorClave): bool
{
    $escalar = function (mixed $valor): string {
        if (is_array($valor)) {
            $valor = $valor['marcado'] ?? '';
        }
        return trim((string) $valor);
    };

    if (isset($si['clave'])) {
        $valores = array_map('strval', $si['valores'] ?? []);
        $valor = $valorPorClave($si['clave']);
        if (is_array($valor) && array_is_list($valor)) {
            return (bool) array_intersect(array_map('strval', $valor), $valores);
        }
        return in_array($escalar($valor), $valores, true);
    }

    foreach ($si['claves'] ?? [] as $clave) {
        $valor = $escalar($valorPorClave($clave));
        if (is_numeric($valor) && (float) $valor > (float) ($si['alguno_mayor_que'] ?? 0)) {
            return true;
        }
    }
    return false;
}

/**
 * Lista u objeto JSON declarado en una columna de `enfermedad`
 * (nucleo_omitidos, campos_notificacion, vinculo_caso, nucleo_condicional...),
 * ya decodificado; [] si la ficha no declara nada o el JSON no es válido.
 */
function jsonDeEnfermedad(array $enfermedad, string $columna): array
{
    $crudo = $enfermedad[$columna] ?? null;
    if (empty($crudo)) {
        return [];
    }
    $decodificado = json_decode((string) $crudo, true);
    return is_array($decodificado) ? $decodificado : [];
}

/**
 * ¿La ficha declara este campo/bloque en nucleo_omitidos? Además de los
 * campos de persona de siempre (celular, etnia...), desde el cotejo de Z21
 * (2026-09-11) admite 4 bloques enteros del formulario: 'captacion',
 * 'clasificacion', 'investigador' y 'fecha_inicio_sintomas' -- la versión
 * declarativa de las listas de CIE-10 que ya ocultaban esos bloques en
 * nueva/index.php, fichas/editar.php, secciones-clinicas.php y
 * CasosController (esas listas siguen igual para las fichas que ya las usan).
 */
function nucleoOmitido(array $enfermedad, string $campoNucleo): bool
{
    return in_array($campoNucleo, jsonDeEnfermedad($enfermedad, 'nucleo_omitidos'), true);
}

/**
 * nucleo_ajustes (P96, muerte fetal y neonatal, 2026-09-13): valor de un
 * ajuste de la tarjeta de identidad o del caso que la ficha declara en el
 * manifiesto -- "sin_documento", "nombres_opcionales", "fallecido",
 * "registrar_y_agregar_otra" (bool), "titulo_persona", "titulo_residencia"
 * (texto) o "condiciones_paciente" (lista). null si no lo declara, así las
 * fichas que no lo usan siguen igual. cargar_fichas.php valida las claves.
 *
 * Ajustes por rama (A50, 2026-09-14): una regla de nucleo_condicional puede
 * declarar "ajustes" que solo valen con cierto valor de su campo (en A50, la
 * rama "Sífilis congénita" admite persona sin documento y sin nombres, y no
 * admite efectivo PNP; la de la madre, no). Con $valoresCampos (id de campo =>
 * valor: el POST o lo guardado) gana el ajuste de la rama elegida; sin rama que
 * lo declare, el de toda la ficha.
 */
function nucleoAjuste(array $enfermedad, string $clave, ?array $valoresCampos = null): mixed
{
    if ($valoresCampos !== null) {
        foreach (reglasAjusteNucleo($enfermedad, $clave) as $regla) {
            if (in_array((string) ($valoresCampos[$regla['campo_id']] ?? ''), $regla['valores'], true)) {
                return $regla['valor'];
            }
        }
    }

    return jsonDeEnfermedad($enfermedad, 'nucleo_ajustes')[$clave] ?? null;
}

/**
 * Reglas de nucleo_condicional que declaran este ajuste por rama: campo que
 * decide la rama, valores con los que aplica y valor del ajuste. [] en las
 * fichas que no lo declaran.
 *
 * @return array<int, array{campo_id: int, valores: string[], valor: mixed}>
 */
function reglasAjusteNucleo(array $enfermedad, string $clave): array
{
    $reglas = [];
    foreach (jsonDeEnfermedad($enfermedad, 'nucleo_condicional') as $regla) {
        if (!is_array($regla['ajustes'] ?? null) || !array_key_exists($clave, $regla['ajustes'])) {
            continue;
        }
        $campo = CampoDef::porClave((int) $enfermedad['id'], (string) ($regla['clave'] ?? ''));
        if ($campo) {
            $reglas[] = [
                'campo_id' => (int) $campo['id'],
                'valores'  => array_map('strval', $regla['valores'] ?? []),
                'valor'    => $regla['ajustes'][$clave],
            ];
        }
    }

    return $reglas;
}

/**
 * Atributos de un trozo de la tarjeta de identidad que se muestra u oculta
 * según la rama (ficha.js, actualizarPorRama()): la casilla "Sin documento",
 * el asterisco de Nombres, la etiqueta de la fecha de nacimiento, una tarjeta
 * de condición del paciente. $excepto: se ve cuando el campo NO tiene ninguno
 * de esos valores. $visible: estado con el que se pinta.
 */
function atributosRama(int $campoId, array $valores, bool $excepto, bool $visible): string
{
    return ' data-rama-campo="campo_' . $campoId . '" data-rama-valores="' . e(implode(',', $valores)) . '"'
        . ($excepto ? ' data-rama-excepto' : '') . ($visible ? '' : ' hidden');
}

/**
 * Asterisco de "Nombres" en la tarjeta de identidad: no va con
 * nucleo_ajustes.nombres_opcionales (P96) y, si el ajuste es por rama (A50),
 * se muestra u oculta según la rama. Sin ajuste, el de siempre.
 */
function marcaObligatorioNombres(array $enfermedad, array $valoresCampos): string
{
    $reglas = reglasAjusteNucleo($enfermedad, 'nombres_opcionales');
    if (!$reglas) {
        return nucleoAjuste($enfermedad, 'nombres_opcionales') ? '' : ' <span class="req">*</span>';
    }
    $valoresOpcionales = array_merge(...array_column($reglas, 'valores'));
    $actual = (string) ($valoresCampos[$reglas[0]['campo_id']] ?? '');

    return ' <span class="req"' . atributosRama($reglas[0]['campo_id'], $valoresOpcionales, true, !in_array($actual, $valoresOpcionales, true)) . '>*</span>';
}

/**
 * Etiqueta de la fecha de nacimiento del núcleo. Con
 * nucleo_condicional.ajustes.etiqueta_fecha_nac (A50: "Fecha de parto /
 * culminación del embarazo" en la rama del producto) se pintan las dos
 * etiquetas y la rama decide cuál se ve. Sin ajuste, el texto de siempre.
 */
function etiquetaFechaNacimiento(array $enfermedad, array $valoresCampos): string
{
    $reglas = reglasAjusteNucleo($enfermedad, 'etiqueta_fecha_nac');
    if (!$reglas) {
        return 'Fecha de nacimiento';
    }
    $actual = (string) ($valoresCampos[$reglas[0]['campo_id']] ?? '');
    $html = '';
    $valoresConEtiqueta = [];
    foreach ($reglas as $regla) {
        $html .= '<span' . atributosRama($regla['campo_id'], $regla['valores'], false, in_array($actual, $regla['valores'], true)) . '>'
            . e((string) $regla['valor']) . '</span>';
        $valoresConEtiqueta = array_merge($valoresConEtiqueta, $regla['valores']);
    }

    return $html . '<span' . atributosRama($reglas[0]['campo_id'], $valoresConEtiqueta, true, !in_array($actual, $valoresConEtiqueta, true)) . '>Fecha de nacimiento</span>';
}

/** Valor que se guarda en una FECHA o NUMERO con "desconocido": true cuando se marca "Desconocido" (A50). */
const VALOR_DESCONOCIDO = 'DESCONOCIDO';

/**
 * La ficha ofrece la casilla "Desconocido" junto a la fecha de nacimiento del
 * núcleo (nucleo_ajustes.fecha_nac_desconocida, o por rama en
 * nucleo_condicional.ajustes). A50, 2026-09-14: ítem 13, la fecha de parto o
 * culminación del embarazo del producto. Marcada, el caso guarda
 * caso.fecha_nac_desconocida = 1 y la persona queda sin fecha de nacimiento.
 */
function fichaAdmiteFechaNacDesconocida(array $enfermedad): bool
{
    return nucleoAjuste($enfermedad, 'fecha_nac_desconocida') === true
        || reglasAjusteNucleo($enfermedad, 'fecha_nac_desconocida') !== [];
}

/**
 * Casilla "Desconocido" de la fecha de nacimiento, con el mismo aspecto que la
 * de campos/fecha.php y el mismo manejo en ficha.js (aplicarDesconocidos():
 * deshabilita y vacía el input marcado con data-con-desconocido). Por rama, se
 * ve solo en esa rama y ficha.js la desmarca al cambiar a otra; va envuelta en
 * un div porque .sym (display:flex) ganaría al atributo hidden. '' en las
 * fichas que no la declaran.
 */
function casillaFechaNacDesconocida(array $enfermedad, array $valoresCampos, bool $marcada): string
{
    if (!fichaAdmiteFechaNacDesconocida($enfermedad)) {
        return '';
    }
    $atributos = '';
    $reglas = reglasAjusteNucleo($enfermedad, 'fecha_nac_desconocida');
    if ($reglas) {
        $valores = array_merge(...array_column($reglas, 'valores'));
        $atributos = atributosRama($reglas[0]['campo_id'], $valores, false, in_array((string) ($valoresCampos[$reglas[0]['campo_id']] ?? ''), $valores, true));
    }

    return '<div' . $atributos . ' style="margin-top:4px"><label class="sym"><input type="checkbox" name="fecha_nac_desconocida" value="1" data-casilla-desconocido'
        . ($marcada ? ' checked' : '') . '> Desconocido</label></div>';
}

/** Atributos del input de la fecha de nacimiento cuando la ficha admite "Desconocido" ('' si no). */
function atributosFechaNacDesconocida(array $enfermedad, bool $marcada): string
{
    return fichaAdmiteFechaNacDesconocida($enfermedad) ? ' data-con-desconocido' . ($marcada ? ' disabled' : '') : '';
}

/**
 * Campos de la tarjeta núcleo "Investigador" (partials/investigador.php), en
 * su orden y con su etiqueta de siempre.
 */
const CAMPOS_INVESTIGADOR = [
    'nombre'              => 'Nombres y apellidos de quién investiga',
    'cargo'               => 'Cargo',
    'profesion'           => 'Profesión',
    'fecha_investigacion' => 'Fecha de investigación',
    'telefono'            => 'Teléfono',
    'email'               => 'Email',
];

/**
 * Etiqueta con la que se pinta un campo de la tarjeta "Investigador", o null
 * si la ficha no lo pide (y entonces tampoco se guarda). Con
 * nucleo_ajustes.investigador la ficha declara qué campos lleva la tarjeta,
 * con su etiqueta o true para la de siempre (A50, 2026-09-14: solo "Nombres y
 * apellidos del notificador", lo que pide el PDF). Sin ajuste, los seis; si
 * la ficha omite la tarjeta (nucleo_omitidos), ninguno.
 */
function campoInvestigador(array $enfermedad, string $campo): ?string
{
    if (nucleoOmitido($enfermedad, 'investigador')) {
        return null;
    }
    $ajuste = nucleoAjuste($enfermedad, 'investigador');
    if (!is_array($ajuste)) {
        return CAMPOS_INVESTIGADOR[$campo] ?? null;
    }
    $declarado = $ajuste['campos'][$campo] ?? null;
    if ($declarado === null) {
        return null;
    }

    return is_string($declarado) ? $declarado : (CAMPOS_INVESTIGADOR[$campo] ?? null);
}

/**
 * Etiqueta de un campo del núcleo: la que declara nucleo_ajustes.etiquetas
 * (B24, 2026-09-14: "Sexo al nacer", "Comunidad") o la de siempre.
 */
function etiquetaNucleo(array $enfermedad, string $campo, string $porDefecto): string
{
    $etiquetas = nucleoAjuste($enfermedad, 'etiquetas');

    return is_array($etiquetas) && is_string($etiquetas[$campo] ?? null) ? $etiquetas[$campo] : $porDefecto;
}

/** Título de la tarjeta "Investigador" (nucleo_ajustes.investigador.titulo: "Notificador" en A50). */
function tituloInvestigador(array $enfermedad): string
{
    $ajuste = nucleoAjuste($enfermedad, 'investigador');

    return is_array($ajuste) && is_string($ajuste['titulo'] ?? null) ? $ajuste['titulo'] : 'Investigador';
}

/**
 * Condición del paciente (partials/condicion-paciente.php), en el orden en que
 * se pintan las tarjetas.
 */
const CONDICIONES_PACIENTE = [
    'EFECTIVO'        => 'Efectivo PNP',
    'DERECHOHABIENTE' => 'Derechohabiente',
    'PARTICULAR'      => 'Particular',
];

/**
 * nucleo_ajustes.condiciones_paciente (P96, 2026-09-14): condiciones que la
 * ficha admite, siempre en el orden de CONDICIONES_PACIENTE. Un fallecido
 * fetal o neonatal no puede ser efectivo PNP. Sin el ajuste, las tres. Con
 * $valoresCampos, las de la rama elegida (ver nucleoAjuste()).
 */
function condicionesPacientePermitidas(array $enfermedad, ?array $valoresCampos = null): array
{
    $declaradas = nucleoAjuste($enfermedad, 'condiciones_paciente', $valoresCampos);
    $todas = array_keys(CONDICIONES_PACIENTE);
    return is_array($declaradas) ? array_values(array_intersect($todas, $declaradas)) : $todas;
}

/**
 * Condición con la que abre una ficha nueva: Particular, o la primera que la
 * ficha admita si no admite Particular.
 */
function condicionPacientePorDefecto(array $enfermedad, ?array $valoresCampos = null): string
{
    $permitidas = condicionesPacientePermitidas($enfermedad, $valoresCampos);
    return in_array('PARTICULAR', $permitidas, true) ? 'PARTICULAR' : $permitidas[0];
}

/**
 * Documento de una persona para mostrar ("DNI 76540319"). Con
 * nucleo_ajustes.sin_documento una persona puede no tener documento (tipo
 * SIN_DOCUMENTO, número NULL): antes todas lo tenían y enmascararDocumento()
 * recibía siempre un texto.
 */
function documentoParaMostrar(?string $tipoDoc, ?string $numDoc, bool $enmascarar = false): string
{
    if ($tipoDoc === 'SIN_DOCUMENTO') {
        return 'Sin documento';
    }
    $numero = (string) $numDoc;

    return $tipoDoc . ' ' . ($enmascarar ? enmascararDocumento($numero) : $numero);
}

/**
 * campos_persona (Z21, 2026-09-12): claves de campo_def que se pintan dentro
 * de la tarjeta de identidad. Admite dos formas: una lista (van en la fila del
 * documento, como en Z21) o, desde P96 (2026-09-13), un objeto por fila:
 * {"documento": [...], "nacimiento": [...]} -- "nacimiento" es la fila de Sexo
 * y Fecha de nacimiento ("Hora de nacimiento" junto a su fecha). Sin $fila
 * devuelve todas, para quien solo necesita saber qué campos ya se pintaron.
 *
 * @return string[]
 */
function clavesCamposPersona(array $enfermedad, ?string $fila = null): array
{
    $declarados = jsonDeEnfermedad($enfermedad, 'campos_persona');
    $porFila = array_is_list($declarados) ? ['documento' => $declarados] : $declarados;
    if ($fila !== null) {
        return array_values($porFila[$fila] ?? []);
    }
    $todas = [];
    foreach ($porFila as $clavesDeLaFila) {
        $todas = array_merge($todas, array_values((array) $clavesDeLaFila));
    }

    return $todas;
}

/**
 * Hora "HH:MM" de 24 horas (campo TEXTO con "formato": "hora"), normalizada
 * con cero a la izquierda ("7:05" -> "07:05"). null si no es una hora válida.
 */
function horaValida(string $hora): ?string
{
    if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim($hora), $partes)) {
        return null;
    }

    return sprintf('%02d:%s', (int) $partes[1], $partes[2]);
}

/**
 * Código CIE-10 (campo TEXTO con "formato": "cie10"): letra, dos dígitos y,
 * opcional, un punto con uno o dos caracteres ("P21.9", "P95"). Se normaliza
 * a mayúsculas y sin espacios, y se agrega el punto si falta ("p219" ->
 * "P21.9"). null si no tiene forma de código. Solo valida la forma: el
 * catálogo CIE-10 todavía no está en el sistema.
 */
function codigoCie10Normalizado(string $codigo): ?string
{
    $limpio = strtoupper(preg_replace('/\s+/', '', $codigo));
    if (!preg_match('/^([A-Z]\d{2})\.?([0-9A-Z]{1,2})?$/', $limpio, $partes)) {
        return null;
    }

    return $partes[1] . (isset($partes[2]) && $partes[2] !== '' ? '.' . $partes[2] : '');
}

/**
 * Campo TEXTO "calculado": "iniciales_fecha_nac" (B24, 2026-09-14). Código del
 * paciente de la NTS 115 (Anexo 3, ítem 6): primera letra del apellido paterno
 * (AP), del materno (AM), del primer nombre (N1) y del segundo nombre (N2), y
 * la fecha de nacimiento con dos dígitos para día, mes y año ("PMJC150390").
 * Cada letra es la primera palabra de su campo tal como está escrita, sin
 * tilde; la que falta (sin apellido materno o sin segundo nombre) queda como
 * guion, igual que el recuadro vacío del papel, y sin fecha de nacimiento van
 * seis guiones. La misma cuenta hace codigoInicialesFechaNac() en ficha.js.
 */
function codigoInicialesFechaNac(string $apellidoPaterno, string $apellidoMaterno, string $nombres, ?string $fechaNacIso): string
{
    $inicial = function (string $texto, int $palabra): string {
        $palabras = preg_split('/\s+/u', trim($texto), -1, PREG_SPLIT_NO_EMPTY);
        if (!isset($palabras[$palabra])) {
            return '-';
        }
        $letra = mb_strtoupper(mb_substr($palabras[$palabra], 0, 1, 'UTF-8'), 'UTF-8');

        return strtr($letra, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U']);
    };
    $fecha = $fechaNacIso !== null ? fechaIsoValida($fechaNacIso) : null;

    return $inicial($apellidoPaterno, 0) . $inicial($apellidoMaterno, 0) . $inicial($nombres, 0) . $inicial($nombres, 1)
        . ($fecha !== null ? substr($fecha, 8, 2) . substr($fecha, 5, 2) . substr($fecha, 2, 2) : '------');
}

/**
 * nucleo_condicional (cotejo Z21, 2026-09-11): un bloque del núcleo
 * ('etnia', 'residencia') que solo aplica cuando un campo_def de la misma
 * ficha toma uno de ciertos valores -- en Z21, Etnia y Residencia habitual
 * solo los pide la Sección I (gestante), no la II (niño). null si el bloque
 * no está condicionado (o si la clave declarada ya no existe).
 *
 * @return array{campo: array, valores: string[]}|null
 */
function condicionNucleo(array $enfermedad, string $bloque): ?array
{
    foreach (jsonDeEnfermedad($enfermedad, 'nucleo_condicional') as $regla) {
        if (!in_array($bloque, $regla['bloques'] ?? [], true)) {
            continue;
        }
        $campoDisparador = CampoDef::porClave((int) $enfermedad['id'], (string) ($regla['clave'] ?? ''));
        return $campoDisparador ? ['campo' => $campoDisparador, 'valores' => array_map('strval', $regla['valores'] ?? [])] : null;
    }
    return null;
}

/**
 * nucleo_condicional.valor_fijo (pedido del usuario, 2026-09-12): qué se
 * guarda en un bloque del núcleo cuando la rama elegida NO lo pregunta --
 * en Z21, sexo = F en la ficha de la gestante, que por definición es mujer.
 * null si la ficha no declara un valor fijo para ese bloque.
 */
function valorFijoNucleo(array $enfermedad, string $bloque): ?string
{
    foreach (jsonDeEnfermedad($enfermedad, 'nucleo_condicional') as $regla) {
        if (!in_array($bloque, $regla['bloques'] ?? [], true)) {
            continue;
        }
        $fijo = $regla['valor_fijo'][$bloque] ?? null;
        return is_string($fijo) && $fijo !== '' ? $fijo : null;
    }

    return null;
}

/**
 * nucleo_condicional, bloque 'persona' (pedido del usuario, 2026-09-12): la
 * tarjeta "Datos de la persona" solo tiene sentido cuando ya se sabe DE QUIÉN
 * son esos datos -- en Z21, la gestante o el niño nacido expuesto. Devuelve los
 * trozos de HTML de esa tarjeta: un aviso que la reemplaza mientras no hay
 * valor elegido, los atributos que la ocultan, y su título según la rama.
 * En las fichas que no lo declaran, aviso y atributos son '' y el título es el
 * genérico: su HTML no cambia.
 *
 * No usa .dep-wrap a propósito: evaluarDependencias() (ficha.js) BORRA los
 * valores de todo lo que oculta, y al abrir la ficha sin rama elegida dejaría
 * en blanco el tipo de documento y la condición del paciente. El conmutador
 * propio, actualizarTarjetaPersona(), solo muestra u oculta.
 *
 * @return array{aviso: string, atributos: string, titulo: string}
 */
function tarjetaPersonaCondicional(array $enfermedad, array $valoresCampos, string $numeroSeccion = '2'): array
{
    // nucleo_ajustes.titulo_persona (P96, 2026-09-13): "Datos del fallecido".
    $tituloGenerico = nucleoAjuste($enfermedad, 'titulo_persona') ?? 'Datos de la persona';
    $regla = null;
    foreach (jsonDeEnfermedad($enfermedad, 'nucleo_condicional') as $candidata) {
        if (in_array('persona', $candidata['bloques'] ?? [], true)) {
            $regla = $candidata;
            break;
        }
    }
    $campoDisparador = $regla ? CampoDef::porClave((int) $enfermedad['id'], (string) ($regla['clave'] ?? '')) : null;
    if (!$campoDisparador) {
        return ['aviso' => '', 'atributos' => '', 'titulo' => e($tituloGenerico)];
    }

    $valores = array_map('strval', $regla['valores'] ?? []);
    $titulos = is_array($regla['titulos'] ?? null) ? $regla['titulos'] : [];
    $valorActual = (string) ($valoresCampos[(int) $campoDisparador['id']] ?? '');
    $visible = in_array($valorActual, $valores, true);

    $titulo = '';
    foreach ($titulos as $valorTitulo => $textoTitulo) {
        $esActual = $visible && (string) $valorTitulo === $valorActual;
        $titulo .= '<span data-titulo-para="' . e((string) $valorTitulo) . '"' . ($esActual ? '' : ' hidden') . '>' . e((string) $textoTitulo) . '</span>';
    }
    $hayTituloActual = $visible && array_key_exists($valorActual, $titulos);
    $titulo .= '<span data-titulo-para=""' . ($hayTituloActual ? ' hidden' : '') . '>' . e($tituloGenerico) . '</span>';

    $atributos = ' data-tarjeta-persona="campo_' . (int) $campoDisparador['id'] . '" data-valores="' . e(implode(',', $valores)) . '"'
        . ($visible ? '' : ' hidden');

    $aviso = '<div class="card section" data-tarjeta-persona-aviso' . ($visible ? ' hidden' : '') . '>'
        . '<div class="section-head"><span class="section-num">' . e($numeroSeccion) . '</span><h3>' . e($tituloGenerico) . '</h3></div>'
        . '<div class="section-body"><p style="color:var(--muted);font-size:13px;margin:0">'
        . e((string) ($regla['aviso'] ?? 'Completa primero el campo que indica de quién son estos datos.'))
        . '</p></div></div>';

    return ['aviso' => $aviso, 'atributos' => $atributos, 'titulo' => $titulo];
}

/**
 * Envoltura .dep-wrap para un bloque del núcleo condicionado: misma mecánica
 * que cualquier depende_de (ficha.js lo muestra/oculta y limpia sus valores
 * al ocultarse). ['', ''] si el bloque no está condicionado, así el HTML de
 * las fichas que no lo declaran no cambia.
 *
 * @return array{0: string, 1: string}
 */
function envolturaNucleoCondicional(array $enfermedad, string $bloque, array $valoresCampos): array
{
    $condicion = condicionNucleo($enfermedad, $bloque);
    if (!$condicion) {
        return ['', ''];
    }
    $idDisparador = (int) $condicion['campo']['id'];
    $visible = in_array((string) ($valoresCampos[$idDisparador] ?? ''), $condicion['valores'], true);
    $abre = '<div class="dep-wrap" data-depende-de="campo_' . $idDisparador . '" data-valor-activador="'
        . e(implode(',', $condicion['valores'])) . '"' . ($visible ? '' : ' hidden') . '>';
    return [$abre, '</div>'];
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
 * Primer día (domingo) de una semana epidemiológica del calendario del MINSA
 * (CDC Perú): las semanas van de domingo a sábado y la SE 1 de un año es la que
 * contiene el 4 de enero, es decir, la primera con al menos 4 días de ese año.
 * Así la SE 1 de 2025 empieza el 29/12/2024 y la de 2026 el 04/01/2026, como
 * en los calendarios epidemiológicos de pared de la DGE. Una semana mayor que
 * las que tiene el año sigue contando en el siguiente.
 */
function inicioSemanaEpidemiologica(int $anio, int $semana = 1): DateTimeImmutable
{
    $cuatroDeEnero = new DateTimeImmutable(sprintf('%04d-01-04', $anio), new DateTimeZone('UTC'));
    $domingoSemana1 = $cuatroDeEnero->modify('-' . $cuatroDeEnero->format('w') . ' days');

    return $domingoSemana1->modify('+' . (($semana - 1) * 7) . ' days');
}

/** Semanas epidemiológicas del año: 52, o 53 cuando le toca (2025 tiene 53). */
function semanasEpidemiologicasDelAnio(int $anio): int
{
    return intdiv(inicioSemanaEpidemiologica($anio)->diff(inicioSemanaEpidemiologica($anio + 1))->days, 7);
}

/**
 * Semana epidemiológica de una fecha, según el calendario del MINSA (ver
 * inicioSemanaEpidemiologica()). El año es el de la semana, no el de la fecha:
 * el 01/01/2026 es SE 53 de 2025.
 *
 * Hasta el 2026-09-14 se usaba la semana ISO-8601 (lunes a domingo), que en
 * 2026 numeraba casi todos los días una semana de más; los casos guardados se
 * recalcularon con sql/migraciones/recalcular_semana_epidemiologica_minsa.php.
 */
function semanaEpidemiologica(string $fechaIso): array
{
    $fecha = new DateTimeImmutable((new DateTime($fechaIso))->format('Y-m-d'), new DateTimeZone('UTC'));
    $anio = (int) $fecha->format('Y');
    if ($fecha >= inicioSemanaEpidemiologica($anio + 1)) {
        $anio++;
    } elseif ($fecha < inicioSemanaEpidemiologica($anio)) {
        $anio--;
    }

    return [
        'anio'   => $anio,
        'semana' => intdiv(inicioSemanaEpidemiologica($anio)->diff($fecha)->days, 7) + 1,
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
    // "desconocido" (A50, 2026-09-14): FECHA o NUMERO marcado "Desconocido".
    if ($valorCrudo === VALOR_DESCONOCIDO && in_array($campo['tipo'], ['FECHA', 'NUMERO'], true)) {
        return 'Desconocido';
    }
    switch ($campo['tipo']) {
        case 'BOOLEANO':
            return $valorCrudo === '1' ? 'Sí' : 'No';
        case 'SI_NO':
            $decodSiNo = json_decode($valorCrudo, true);
            $marcado = is_array($decodSiNo) ? ($decodSiNo['marcado'] ?? '') : '';
            return ['SI' => 'Sí', 'NO' => 'No', 'IGNORADO' => 'Ignorado'][$marcado] ?? '—';
        case 'FECHA':
            return fechaIsoADmy($valorCrudo) ?: '—';
        case 'SELECT':
        case 'MULTISELECT':
            $opciones = $campo['catalogo_id'] ? CatalogoItem::porCatalogo((int) $campo['catalogo_id']) : [];
            $mapa = array_column($opciones, 'etiqueta', 'valor');
            $valores = $campo['tipo'] === 'MULTISELECT' ? explode(',', $valorCrudo) : [$valorCrudo];
            return implode(', ', array_map(fn($v) => $mapa[$v] ?? $v, $valores));
        case 'MATRIZ':
            $decoded = json_decode($valorCrudo, true);
            if (!is_array($decoded) || empty($decoded)) {
                return '—';
            }
            if (($campo['clave'] ?? '') === 'b55_lesiones') {
                $items = [];
                foreach ($decoded as $idx => $les) {
                    $n = $idx + 1;
                    $tipo = $les['tipo'] ?? 'Lesión';
                    $loc = $les['localizacion'] ?? '—';
                    $dim = (!empty($les['d1']) && !empty($les['d2'])) ? " ({$les['d1']}×{$les['d2']} mm)" : '';
                    $items[] = "#{$n}: {$tipo} en {$loc}{$dim}";
                }
                return implode(' | ', $items) ?: '—';
            }
            if (($campo['clave'] ?? '') === 'b55_compromiso_de_estructuras') {
                // Mismo diccionario clave->etiqueta que
                // partials/campos/leishmaniasis-mucosa.php (la única fuente
                // real de esas claves -- el "filas" del manifiesto para este
                // campo no se usa, ver [[cotejo_leishmaniasis_b55_revision]]).
                $etiquetasEstructuras = [
                    'nariz_narinas' => 'Narinas', 'nariz_1_3_anterior' => '1/3 anterior',
                    'nariz_septo' => 'Septo nasal', 'nariz_cornetes' => 'Cornetes',
                    'boca_labios' => 'Labios', 'boca_arcada' => 'Arcada dental',
                    'boca_paladar' => 'Paladar (duro / blando)', 'boca_uvula' => 'Úvula',
                    'faringe' => 'Faringe / Rinofaringe', 'epiglotis' => 'Epiglotis',
                    'cuerdas_vocales' => 'Cuerdas vocales', 'otras_estructuras' => 'Otras estructuras',
                ];
                $afectadas = [];
                foreach ($decoded as $est => $vals) {
                    if (!empty($vals['compromiso']) || !empty($vals['eritema']) || !empty($vals['ulcera']) || !empty($vals['edema']) || !empty($vals['infiltracion'])) {
                        $signos = [];
                        if (!empty($vals['eritema'])) $signos[] = 'Eritema';
                        if (!empty($vals['edema'])) $signos[] = 'Edema';
                        if (!empty($vals['infiltracion'])) $signos[] = 'Infiltración';
                        if (!empty($vals['ulcera'])) $signos[] = 'Úlcera';
                        $lblSignos = $signos ? ' [' . implode(', ', $signos) . ']' : '';
                        $afectadas[] = ($etiquetasEstructuras[$est] ?? ucfirst(str_replace('_', ' ', $est))) . $lblSignos;
                    }
                }
                return $afectadas ? implode('; ', $afectadas) : 'Sin compromiso de estructuras mucosas';
            }
            // MATRIZ con "opciones_por_fila" (cotejo Z21, 2026-09-11): acá sí
            // se puede armar una línea legible por fila -- cada fila tiene
            // nombre propio y pocas columnas ("1.er PCR: 01/09/2026,
            // Positivo"). El resto de las matrices (A80, B26...) conserva el
            // resumen de siempre, para no cambiar lo que ya se revisó.
            $configMatrizTexto = json_decode((string) ($campo['config'] ?? '{}'), true) ?: [];
            if (!empty($configMatrizTexto['opciones_por_fila'])) {
                $filasMatrizTexto = $configMatrizTexto['filas'] ?? [];
                $columnasMatrizTexto = $configMatrizTexto['columnas'] ?? [];
                $lineasMatrizTexto = [];
                foreach ($decoded as $indiceFila => $celdasFila) {
                    if (!is_array($celdasFila)) {
                        continue;
                    }
                    $partesFila = [];
                    foreach ($celdasFila as $indiceColumna => $valorCelda) {
                        if (!ctype_digit((string) $indiceColumna)) {
                            continue; // subclaves internas (_radio y compañía)
                        }
                        $valorCelda = trim((string) $valorCelda);
                        if ($valorCelda === '') {
                            continue;
                        }
                        $etiquetaColumna = (string) ($columnasMatrizTexto[(int) $indiceColumna] ?? '');
                        $partesFila[] = str_contains(mb_strtoupper($etiquetaColumna), 'FECHA')
                            ? (fechaIsoADmy($valorCelda) ?: $valorCelda)
                            : $valorCelda;
                    }
                    if ($partesFila) {
                        $lineasMatrizTexto[] = ($filasMatrizTexto[$indiceFila] ?? ('#' . ((int) $indiceFila + 1)))
                            . ': ' . implode(', ', $partesFila);
                    }
                }
                return $lineasMatrizTexto ? implode(' | ', $lineasMatrizTexto) : '—';
            }
            return is_array($decoded) ? 'Registrado (' . count($decoded) . ' ítems)' : $valorCrudo;
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
    // Se avanza de domingo en domingo (calendario del MINSA) y cada semana se
    // vuelve a numerar con semanaEpidemiologica(): así los años de 53 semanas
    // salen solos.
    $cursor = inicioSemanaEpidemiologica($anioDesde, $seDesde);
    $fin = inicioSemanaEpidemiologica($anioHasta, $seHasta);

    $semanas = [];
    while ($cursor <= $fin) {
        $semanas[] = semanaEpidemiologica($cursor->format('Y-m-d'));
        $cursor = $cursor->modify('+7 days');
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

/**
 * Edad (valor + unidad) calculada desde fecha de nacimiento hasta una
 * fecha de referencia (por defecto hoy), para fichas que declaran
 * `unidades_edad` (Entrada F, PETICION_MAPEO_Y_EDAD.md Parte 2 -- híbrido
 * decidido el 2026-08-27: si hay fecha de nacimiento, la edad se calcula;
 * el input manual queda solo de respaldo cuando no se conoce).
 *
 * Elige la unidad más gruesa de $unidadesPermitidas con valor > 0 (evita
 * "0 años" cuando en realidad son "8 meses"); si todas dan 0 (nacido el
 * mismo día de la referencia), usa la más gruesa disponible con su valor
 * real. "Meses" es el total de meses transcurridos (no el resto tras
 * restar años): necesario cuando la ficha no admite ANIOS (P35.0) y la
 * persona ya tiene más de un año -- no se trunca a 0-11.
 *
 * Solo contempla ANIOS/MESES/DIAS -- ninguna ficha declara hoy HORAS/
 * MINUTOS (reservadas para Y59.0 a futuro); si $unidadesPermitidas no
 * trae ninguna de las 3, devuelve null.
 *
 * @param string[] $unidadesPermitidas
 * @return array{valor:int, unidad:string}|null
 */
function edadConUnidadDesdeFecha(string $fechaNacIso, ?string $fechaReferenciaIso, array $unidadesPermitidas): ?array
{
    $nacimiento = DateTime::createFromFormat('Y-m-d', substr($fechaNacIso, 0, 10));
    if (!$nacimiento) {
        return null;
    }
    $referencia = $fechaReferenciaIso ? DateTime::createFromFormat('Y-m-d', substr($fechaReferenciaIso, 0, 10)) : null;
    if (!$referencia) {
        $referencia = new DateTime();
    }
    if ($referencia < $nacimiento) {
        return null; // fecha de nacimiento posterior a la referencia: dato inconsistente, no calcular
    }

    $diff = $nacimiento->diff($referencia);
    $valoresPorUnidad = [
        'ANIOS' => $diff->y,
        'MESES' => $diff->y * 12 + $diff->m,
        'DIAS'  => (int) $diff->days,
    ];

    $disponibles = array_values(array_intersect(['ANIOS', 'MESES', 'DIAS'], $unidadesPermitidas));
    if (!$disponibles) {
        return null;
    }
    foreach ($disponibles as $unidad) {
        if ($valoresPorUnidad[$unidad] > 0) {
            return ['valor' => $valoresPorUnidad[$unidad], 'unidad' => $unidad];
        }
    }

    return ['valor' => $valoresPorUnidad[$disponibles[0]], 'unidad' => $disponibles[0]];
}
