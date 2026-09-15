<?php
/**
 * cargar_fichas.php
 *
 * Cargador único e idempotente de definiciones de ficha (seccion_def /
 * campo_def / catalogo / catalogo_item) a partir de manifiesto_fichas.json.
 * Reemplaza a los ~12 SQL sueltos de sql/*lote*.sql (ver INFORME_CARGADOR.md).
 *
 * Requisitos que implementa (RECARGA_FICHAS.md, Fase 2):
 *   1. Idempotente por diseño: por cada enfermedad, dentro de una
 *      transacción, borra sus seccion_def (cascada a campo_def por la FK)
 *      y vuelve a insertar desde el manifiesto. Correrlo dos veces deja el
 *      mismo resultado.
 *   2. Falla dura: si un campo SELECT/MULTISELECT/GRUPO_SI_NO/CRONOLOGIA no
 *      trae "opciones" en el manifiesto, o si un "tipo" no es reconocido,
 *      el script aborta con excepción ANTES de escribir nada (se valida
 *      todo el manifiesto primero). Nunca inserta con catalogo_id NULL.
 *   3. Convención de clave única: si el campo trae "clave" en el
 *      manifiesto, es autoritativa y se usa tal cual (validada única por
 *      ficha en validarManifiesto()); si no la trae, se deriva como
 *      "{cie10}_{slug(etiqueta)}". La derivada cambia si se reescribe la
 *      etiqueta (p. ej. al cotejar contra el PDF MINSA) -- por eso el
 *      código que necesita una clave estable entre recargas debe fijarla
 *      explícita en el manifiesto, no confiar en la derivación.
 *   4. Protege datos capturados: si una enfermedad tiene caso_valor
 *      asociados a sus campo_def actuales, NO se borra — se reporta y hay
 *      que confirmar explícitamente con --confirmar-perdida=<CIE10>.
 *   5. Catálogos: reutiliza un catálogo existente si su lista de opciones
 *      ya existe (por contenido, no por nombre), en vez de duplicarlo por
 *      ficha. Los catálogos genéricos (Sí/No, Sí/No/Ignorado, etc.) se
 *      nombran "Compartido: ..." para que se note que no son de una sola
 *      ficha.
 *   6. Campos condicionales: un campo puede traer "depende_de" (la etiqueta
 *      de OTRO campo de la misma ficha) y "valor_activador" (el código de
 *      catálogo -mismo formato que catalogo_item.valor- que lo activa).
 *      Se resuelve en una segunda pasada, una vez insertados todos los
 *      campos de la ficha (CIERRE_RECARGA_Y_FASE5.md Parte 0: la Fase 3
 *      original no lo soportaba y perdió en silencio los 5 pares que
 *      existían antes de la recarga).
 *
 * MODO DE USO
 * -----------
 *   php cargar_fichas.php                        Dry-run de las 23 fichas:
 *                                                 hace todo el trabajo real
 *                                                 dentro de una transacción
 *                                                 por ficha y la revierte
 *                                                 (ROLLBACK) al final — no
 *                                                 queda nada escrito.
 *   php cargar_fichas.php --apply --confirmo-apply
 *                                                  Aplica de verdad (COMMIT).
 *                                                  --apply solo no alcanza:
 *                                                  hace falta también
 *                                                  --confirmo-apply, a
 *                                                  propósito, para que
 *                                                  aplicar de verdad nunca
 *                                                  sea un accidente de
 *                                                  copiar/pegar o de probar
 *                                                  otra bandera.
 *   php cargar_fichas.php --apply --confirmo-apply --cie10=A36,A37.0
 *                                                  Aplica solo esas fichas.
 *   php cargar_fichas.php --apply --confirmo-apply --confirmar-perdida=A97
 *                                                  Aplica y, además, permite
 *                                                  borrar/recargar una
 *                                                  enfermedad aunque tenga
 *                                                  caso_valor capturados
 *                                                  (los pierde a propósito).
 *   php cargar_fichas.php --json                   Salida en JSON en vez de
 *                                                   texto legible.
 *
 * El dry-run (modo por defecto) es seguro de correr las veces que se quiera:
 * usa la misma lógica que --apply pero nunca hace COMMIT.
 */

require __DIR__ . '/app/Core/Autoload.php';
// CATALOGO_CLASIFICACION: reglas_campos "clasificar" solo admite códigos que
// la app sabe mostrar (Z21, 2026-09-13).
require __DIR__ . '/app/Core/ayudantes.php';

use App\Core\Database;

// ============================================================================
// CLI
// ============================================================================
$aplicar = in_array('--apply', $argv, true);
if ($aplicar && !in_array('--confirmo-apply', $argv, true)) {
    fwrite(STDERR, "--apply requiere también --confirmo-apply, a propósito: esto borra e inserta de verdad seccion_def/campo_def/catalogo en la base de datos.\nCorré primero sin --apply para ver el plan (dry-run, no escribe nada).\n");
    exit(1);
}
$modoJson = in_array('--json', $argv, true);
$soloEstas = null;
$forzarPerdida = [];
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--cie10=')) {
        $soloEstas = array_map('trim', explode(',', substr($arg, 8)));
    }
    if (str_starts_with($arg, '--confirmar-perdida=')) {
        $forzarPerdida = array_map('trim', explode(',', substr($arg, 20)));
    }
}

$manifiestoPath = __DIR__ . '/manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($manifiestoPath), true);
if ($manifiesto === null) {
    fwrite(STDERR, "No se pudo leer/parsear {$manifiestoPath}: " . json_last_error_msg() . "\n");
    exit(1);
}

const TIPOS_CON_OPCIONES = ['SELECT', 'MULTISELECT', 'GRUPO_SI_NO', 'CRONOLOGIA'];
const TIPOS_VALIDOS = ['TEXTO', 'NUMERO', 'FECHA', 'BOOLEANO', 'SELECT', 'MULTISELECT', 'TEXTAREA', 'GRUPO_SI_NO', 'SI_NO_FECHA', 'SI_NO', 'MATRIZ', 'CRONOLOGIA'];

// Columnas reales de cada tabla hija que se pueden activar/desactivar por
// ficha (PENDIENTES_POST_FASE5.md punto 3) -- "nombres"/"vacuna"/
// "vacuna_otro"/"fecha" no están acá porque los widgets las muestran
// siempre (ver CasosController::COLUMNAS_HIJA_DEFECTO).
const COLUMNAS_TABLA_HIJA_VALIDAS = [
    // 'tipo_exposicion' (2026-08-29, B04X, ítem 33 del PDF): checklist de 6
    // códigos por contacto (1. Por contacto íntimo...6. Otro) -- gatea tanto
    // los 6 checkboxes como el texto libre de "6. Otro" (tipo_exposicion_otro,
    // no es una entrada aparte acá porque siempre va junto al checklist).
    'caso_contacto' => ['parentesco', 'edad', 'sexo', 'vacunado', 'fecha_vacunacion', 'profilaxis', 'doc', 'celular', 'fecha_contacto', 'lugar_contacto', 'fecha_inicio_erupcion', 'vacunado_72h', 'tipo_exposicion'],
    'caso_vacuna'   => ['dosis', 'via', 'sitio', 'adyuvante', 'fabricante', 'lote', 'fecha_vencimiento', 'establecimiento'],
    // 'distrito_id'/'direccion' (2026-08-13, A97, pág. 49 ítems 23-27): la
    // columna distrito_id ya existía en caso_viaje sin usarse -- ambas se
    // vuelven declarativas junto con las demás en vez de asumirse por defecto.
    // 'tiempo_permanencia' (2026-08-18, A44, pág. 42 del PDF): texto libre,
    // reemplaza al par fecha_salida/fecha_retorno + transporte cuando la
    // ficha solo pide "Fecha de viaje / Lugar / Tiempo de permanencia".
    'caso_viaje'    => ['pais', 'localidad', 'distrito_id', 'direccion', 'fecha_salida', 'fecha_retorno', 'tiempo_permanencia', 'semana_gestacion', 'transporte_ida', 'transporte_retorno'],
    // Las últimas 7 (resultado_pcr..fecha_result_igg) eran de serología de
    // B05, pintadas a mano en muestras.php dentro de un if ($esB05) --
    // PENDIENTES.md ítem C: se vuelven declarativas igual que el resto,
    // B05 las pasa a declarar acá en vez de estar hardcodeadas.
    // "numero_muestra" NO está acá a propósito: es un ordinal calculado
    // automáticamente en CasosController::filasMuestras() (cuenta repeticiones
    // de tipo_muestra dentro del propio POST), no una columna que una ficha
    // declare para pintar/ocultar -- no hay <select> ni <input> para ella.
    // 'fecha_envio_eess_red'/'fecha_envio_red_lrr'/'fecha_envio_lrr_ins'
    // (2026-08-09, B01): cadena de 3 fechas de envío EE.SS -> Red/Microred ->
    // LRR -> INS del PDF (pág. 3, secc. VI) -- distinta de 'fecha_envio_ins'
    // (una sola fecha genérica que ya usan otras fichas para el mismo
    // concepto, sin desglosar el tramo).
    // 'fecha_recepcion_ins' (2026-09-07, A00 pág. 51: "Fecha de recepción en
    // laboratorio"): la columna existía en caso_muestra y se guardaba, pero
    // sólo la pintaba la rama especial de B05 en muestras.php -- ninguna
    // ficha podía declararla. Se vuelve declarable como el resto.
    // 'establecimiento'/'serogrupo'/'serotipo' (2026-09-07, A00, misma
    // sección): 3 de las 6 columnas de la tabla del papel; ver
    // sql/migraciones/add_lab_colera_caso_muestra.php.
    'caso_muestra'  => ['tipo_muestra', 'tipo_prueba', 'recibio_antibiotico', 'resultado', 'fecha_toma', 'fecha_envio_eess_red', 'fecha_envio_red_lrr', 'fecha_envio_lrr_ins', 'fecha_result', 'fecha_envio_ins', 'fecha_recepcion_ins', 'agente_aislado', 'observaciones', 'resultado_pcr', 'fecha_result_pcr', 'genotipo', 'resultado_igm', 'fecha_result_igm', 'resultado_igg', 'fecha_result_igg', 'titulacion', 'establecimiento', 'serogrupo', 'serotipo'],
];

// Campos del núcleo compartido de "Datos del paciente" (columnas fijas de
// persona/caso, pintadas por datos-paciente-nucleo.php, no campo_def) que
// una ficha puede declarar que NO pide (Petición 2, sesión "núcleo
// declarativo"). Lista de OMISIONES, no de inclusiones: el default es
// "se muestran todos", así que agregar este mecanismo no cambia nada en
// ninguna ficha hasta que una declare una omisión explícita.
// 'referencia_localizar' (PENDIENTES.md ítem E, 2026-08-01): antes era un
// campo_def propio de B05 (b05_referencia_para_localizar_cerca_de_iglesia_fundo_co),
// no reutilizable por otra ficha sin declarar su propio campo_def -- ahora
// es núcleo real (persona.referencia_localizar). Para no cambiar nada
// visualmente en las 23 fichas que no lo pedían, todas declaran esta
// omisión salvo B05.
// Los 4 últimos (cotejo Z21, 2026-09-11) no son campos de `persona` sino
// BLOQUES enteros del formulario que hasta ahora se ocultaban con listas de
// CIE-10 escritas a mano en código compartido: 'captacion' (el bloque
// Tipo/Lugar/Clasificación en la captación de notificacion-captacion.php),
// 'clasificacion' (la tarjeta de chips "Clasificación del caso"),
// 'investigador' (la tarjeta "Investigador") y 'fecha_inicio_sintomas' (el
// campo genérico obligatorio de secciones-clinicas.php). Declararlos acá es
// la versión declarativa de esas listas; las fichas que ya estaban en ellas
// siguen igual, sin tocar nada.
const NUCLEO_OMITIBLES = ['celular', 'nacionalidad', 'localidad', 'direccion', 'referencia_localizar', 'etnia', 'pueblo_etnico', 'ocupacion', 'nombre_tutor', 'celular_tutor', 'gestante', 'captacion', 'clasificacion', 'investigador', 'fecha_inicio_sintomas'];

// nucleo_condicional (cotejo Z21, 2026-09-11): bloques del núcleo que una
// ficha pide solo cuando un campo_def suyo toma cierto valor -- Z21 pide
// Etnia y Residencia habitual en la Sección I (gestante) pero no en la II
// (niño nacido expuesto), y ambas secciones son ahora fichas distintas de la
// misma enfermedad. Distinto de nucleo_omitidos, que es fijo por ficha.
// 'sexo' (pedido del usuario, 2026-09-12): el ítem 4 del PDF de Z21 (datos de
// la gestante) no pregunta el sexo -- es una gestante --, pero el ítem 9
// (datos del niño nacido expuesto) sí. Con "valor_fijo" la rama que no lo
// pregunta guarda el valor que la ficha declare, en vez de dejarlo vacío.
// 'persona' (pedido del usuario, 2026-09-12): la tarjeta de identidad entera.
// No descarta nada al guardar (la identidad siempre se exige); solo decide
// cuándo se muestra la tarjeta y con qué título ("titulos" / "aviso").
const NUCLEO_CONDICIONABLES = ['etnia', 'residencia', 'sexo', 'persona'];

// Qué valores admite "valor_fijo" por bloque: lo que se guarda cuando el
// bloque NO aplica. Sin entrada acá, el bloque no admite valor fijo.
const NUCLEO_VALOR_FIJO_VALIDO = ['sexo' => ['F', 'M']];

// nucleo_ajustes (P96, muerte fetal y neonatal, 2026-09-13): ajustes de la
// tarjeta de identidad y del caso que no son omitir ni incluir un campo. Clave
// => tipo del valor. "sin_documento": la persona puede registrarse sin
// documento (un óbito fetal no tiene DNI); "nombres_opcionales": se exigen los
// apellidos pero no los nombres; "fallecido": todo caso de la ficha es una
// defunción (caso.fallecido = 1 siempre); "titulo_persona" y
// "titulo_residencia": títulos de la tarjeta de identidad y de su bloque de
// residencia ("Datos del fallecido", "Residencia habitual de la madre").
// Agregados el 2026-09-14 (P96): "condiciones_paciente", las condiciones del
// paciente que la ficha admite, de CONDICIONES_PACIENTE (un fallecido fetal o
// neonatal no puede ser efectivo PNP); "registrar_y_agregar_otra", el botón
// que guarda y abre la siguiente ficha con el mismo establecimiento y fecha de
// notificación.
const NUCLEO_AJUSTES_VALIDOS = [
    'sin_documento'      => 'bool',
    'nombres_opcionales' => 'bool',
    'fallecido'          => 'bool',
    'titulo_persona'     => 'texto',
    'titulo_residencia'  => 'texto',
    'condiciones_paciente' => 'condiciones',
    'registrar_y_agregar_otra' => 'bool',
    // A50, 2026-09-14: casilla "Desconocido" junto a la fecha de nacimiento
    // (caso.fecha_nac_desconocida) y tarjeta "Investigador" reducida y
    // retitulada ({"titulo": ..., "campos": {campo: etiqueta o true}}, campos
    // de CAMPOS_INVESTIGADOR): el notificador del PDF.
    'fecha_nac_desconocida' => 'bool',
    'investigador'         => 'investigador',
    // B24, 2026-09-14: etiquetas del PDF para campos del núcleo
    // ({"sexo": "Sexo al nacer", "localidad": "Comunidad"}), de ETIQUETAS_NUCLEO.
    'etiquetas'            => 'etiquetas',
];

// Campos del núcleo cuya etiqueta una ficha puede cambiar con
// nucleo_ajustes.etiquetas (B24, 2026-09-14). Solo cambia el texto: el dato y
// sus opciones son los mismos.
const ETIQUETAS_NUCLEO = ['sexo', 'localidad'];

// Ajustes por rama (A50, 2026-09-14): los que una regla de nucleo_condicional
// puede declarar en "ajustes" para que valgan solo con su valor (el producto de
// la gestación sin documento, sin nombres y sin efectivo PNP; la madre, no).
// "etiqueta_fecha_nac": cómo se llama la fecha de nacimiento del núcleo en esa
// rama ("Fecha de parto / culminación del embarazo").
const NUCLEO_AJUSTES_POR_RAMA = [
    'sin_documento'        => 'bool',
    'nombres_opcionales'   => 'bool',
    'condiciones_paciente' => 'condiciones',
    'etiqueta_fecha_nac'   => 'texto',
    'fecha_nac_desconocida' => 'bool',
];

// campos_persona en forma de objeto (P96, 2026-09-13): fila de la tarjeta de
// identidad donde se pinta cada campo. La forma de lista (Z21) equivale a
// {"documento": [...]}.
const FILAS_CAMPOS_PERSONA = ['documento', 'nacimiento'];

// "formato" de un campo TEXTO (P96, 2026-09-13): "hora" se pinta como
// <input type="time"> y se guarda HH:MM; "cie10" exige la forma de un código
// CIE-10 (P21.9) hasta que el catálogo CIE-10 esté en el sistema.
const FORMATOS_TEXTO = ['hora', 'cie10'];

// "calculado" de un campo TEXTO (B24, 2026-09-14): el valor no se escribe, lo
// arma el servidor al guardar con datos del núcleo y se pinta de solo lectura.
// "iniciales_fecha_nac": código del paciente de la NTS 115 (primera letra del
// apellido paterno, del materno, del primer y del segundo nombre, más la fecha
// de nacimiento ddmmaa); ver codigoInicialesFechaNac() en ayudantes.php.
const CALCULOS_TEXTO = ['iniciales_fecha_nac'];

// reglas_campos: datos del núcleo que puede leer la condición "si" de una regla
// en vez de un campo_def (B24, 2026-09-14: la identidad de género y "Gestante
// con VIH" según el sexo al nacer), con los valores que admiten.
const CONDICIONES_NUCLEO_REGLAS = ['nucleo:sexo' => ['F', 'M']];

// Simétrico de NUCLEO_OMITIBLES: campos del núcleo ocultos por defecto que
// una ficha declara para MOSTRAR (opt-in), en vez de mostrados por defecto
// y declarados para ocultar. PETICION_HC_Y_LABORATORIO.md, Parte 1:
// "N.° de historia clínica" solo lo pide el PDF en el bloque de identidad
// de 3 de las 24 fichas (P35.0, O95, A44) -- opt-out habría pintado el
// campo sin base documental en las 21 restantes.
// 'estado_civil' (cotejo B57, ítem 2.5 del PDF): mismo criterio que
// n_historia_clinica -- núcleo real (persona.estado_civil), pero opt-in:
// solo 1/24 fichas confirmada contra el PDF, opt-out lo pintaría sin base
// documental en las 23 restantes. No reemplaza a o95_estado_civil (campo_def
// propio de O95/Anexo 2, con opciones distintas), coexisten.
// 'nacimiento_distrito_id' (cotejo B55, "I. Datos generales" -- pág. 45 del
// PDF, "Lugar de Nacimiento: (Distrito, Provincia, Departamento)"): mismo
// criterio -- ninguna otra de las 24 fichas cotejadas lo pide, así que
// opt-out lo habría mostrado sin base documental en las demás.
// persona.nacimiento_distrito_id (add_nacimiento_distrito_persona.php);
// Provincia/Departamento se derivan por join, igual que distrito_id/
// anterior_distrito_id.
const NUCLEO_INCLUIBLES = ['n_historia_clinica', 'estado_civil', 'nacimiento_distrito_id'];

// Entrada F (PETICION_MAPEO_Y_EDAD.md, Parte 2): unidades válidas para
// "unidades_edad" -- opt-in, al revés que NUCLEO_OMITIBLES. Ausente = solo
// años (comportamiento actual, derivado de persona.fecha_nac). Debe
// coincidir con el ENUM de caso.edad_unidad (add_edad_valor_unidad_caso.php).
const UNIDADES_EDAD_VALIDAS = ['ANIOS', 'MESES', 'DIAS', 'HORAS', 'MINUTOS'];

// Entrada J acotada al bloque de domicilio (PETICION_MAPEO_Y_EDAD.md):
// detalle de dirección dentro del distrito -- opt-in, igual que
// unidades_edad, no NUCLEO_OMITIBLES. Con solo 2/24 fichas confirmadas
// contra el PDF (A37.0, P35.0), opt-out pintaría estos campos sin base
// documental en el resto. Debe coincidir con el ENUM de persona.tipo_zona
// (add_detalle_domicilio_persona.php).
// 'NOMBRE_ZONA' (cotejo B57, ítem 3.5 del PDF): hermano de TIPO_ZONA,
// mismo criterio opt-in. Se reutiliza también dentro de "domicilio
// anterior" (Migración) cuando la ficha declara migracion_reciente --
// mismo conjunto de campos habilitados para ambos bloques.
const DETALLE_DOMICILIO_VALIDO = ['TIPO_ZONA', 'NOMBRE_ZONA', 'TIPO_VIA', 'NOMBRE_VIA', 'NUMERO', 'MZ_LOTE', 'TIEMPO_RESIDENCIA'];

// Columnas reales de caso_sujeto que una ficha multi_sujeto puede declarar
// para un rol secundario (PETICION_P35_RUBEOLA_CONGENITA.md Fase 2):
// "columnas_sujeto": {"MADRE": [...]}. Excluye id/caso_id/persona_id/rol
// (no son datos que un formulario capture). La presencia del rol como
// clave es lo que activa el bloque -- no hay un booleano aparte, mismo
// idioma que nucleo_omitidos.
const COLUMNAS_SUJETO_VALIDAS = ['tipo_doc', 'doc', 'apellidos', 'nombres', 'sexo', 'edad', 'fecha_nacimiento', 'nacionalidad', 'ocupacion', 'distrito_id', 'direccion'];

// Listas de opciones tan genéricas que se comparten entre fichas en vez de
// crear un catálogo por ficha (se detectan por contenido exacto, no por
// nombre — cualquier campo con exactamente esta lista de opciones cae acá).
const CATALOGOS_COMPARTIDOS = [
    ['Sí', 'No'],
    ['Sí', 'No', 'Ignorado'],
    ['Sí', 'No', 'Desconocido'],
    ['Sí', 'No', 'No recuerda'],
    ['Bueno', 'Regular', 'Malo'],
    ['Completa', 'Incompleta'],
    ['I', 'II', 'III'],
];

// ============================================================================
// Utilidades
// ============================================================================
function slug(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $mapa = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'];
    $texto = strtr($texto, $mapa);
    $texto = preg_replace('/[^a-z0-9]+/', '_', $texto);
    return trim($texto, '_');
}

function claveCampo(string $cie10, string $etiqueta, array &$clavesUsadasEnFicha): string
{
    $prefijo = slug($cie10);
    $base = $prefijo . '_' . slug($etiqueta);
    $base = mb_substr($base, 0, 55); // deja margen para el sufijo de deduplicación
    $clave = $base;
    $n = 2;
    while (isset($clavesUsadasEnFicha[$clave])) {
        $clave = $base . '_' . $n;
        $n++;
    }
    $clavesUsadasEnFicha[$clave] = true;
    return mb_substr($clave, 0, 60);
}

function claveOpciones(array $opciones): string
{
    return implode('§', $opciones);
}

function esCatalogoCompartido(array $opciones): bool
{
    foreach (CATALOGOS_COMPARTIDOS as $generico) {
        if ($opciones === $generico) {
            return true;
        }
    }
    return false;
}

/**
 * Valida el manifiesto ENTERO antes de tocar la base de datos. Aborta con
 * excepción ante el primer campo SELECT/MULTISELECT/GRUPO_SI_NO/CRONOLOGIA
 * sin "opciones", MATRIZ sin "columnas", o "tipo" no reconocido.
 */
function validarManifiesto(array $manifiesto): void
{
    foreach ($manifiesto['fichas'] as $cie10 => $ficha) {
        // Cuántas veces se repite cada etiqueta dentro de la ficha, y qué
        // claves explícitas declara: "depende_de" admite las dos formas.
        $etiquetasFicha = [];
        $clavesFicha = [];
        $tiposPorClaveFicha = [];
        // "formato" por clave (P96, 2026-09-13): el efecto "comparar_fechas"
        // de reglas_campos lo valida en hora_desde/hora_hasta.
        $formatoPorClaveFicha = [];
        foreach ($ficha['secciones'] as $seccion) {
            foreach ($seccion['campos'] as $campo) {
                $etiquetasFicha[$campo['etiqueta']] = ($etiquetasFicha[$campo['etiqueta']] ?? 0) + 1;
                $claveDeclarada = trim((string) ($campo['clave'] ?? ''));
                if ($claveDeclarada !== '') {
                    $clavesFicha[$claveDeclarada] = true;
                    $tiposPorClaveFicha[$claveDeclarada] = (string) ($campo['tipo'] ?? '');
                    $formatoPorClaveFicha[$claveDeclarada] = $campo['formato'] ?? null;
                }
            }
        }

        // "depende_de" (de un campo o de una sección) apunta a otro campo de
        // la misma ficha por CLAVE o por etiqueta. La clave se admite desde
        // el cotejo de Z21 (2026-09-11): esa ficha repite tres etiquetas
        // entre sus dos ramas ("¿Recibió ARV?", "Fecha de inicio de ARV",
        // "¿Abandonó terapia ARV?"), y resolver por etiqueta habría
        // enganchado la dependencia al campo equivocado en silencio (gana el
        // último insertado, ver $idPorEtiqueta en procesarFicha()). Por eso
        // una etiqueta repetida pasa a ser un error explícito en vez de una
        // ruleta: hoy ninguna de las 24 fichas dependía de una etiqueta
        // repetida (verificado antes de agregar el chequeo).
        $validarReferenciaCampo = function (string $referencia, string $contexto) use ($cie10, $etiquetasFicha, $clavesFicha): void {
            if (isset($clavesFicha[$referencia])) {
                return;
            }
            if (!isset($etiquetasFicha[$referencia])) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / {$contexto} depende de \"{$referencia}\", que no existe como campo (ni por clave ni por etiqueta) de esta misma ficha.");
            }
            if ($etiquetasFicha[$referencia] > 1) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / {$contexto} depende de la etiqueta \"{$referencia}\", repetida {$etiquetasFicha[$referencia]} veces en la ficha: la dependencia sería ambigua. Referenciar ese campo por su \"clave\".");
            }
        };
        foreach ($ficha['secciones'] as $seccion) {
            foreach ($seccion['campos'] as $campo) {
                $tipo = $campo['tipo'] ?? null;
                $etiqueta = $campo['etiqueta'] ?? '(sin etiqueta)';
                if (!in_array($tipo, TIPOS_VALIDOS, true)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" tiene tipo desconocido: " . json_encode($tipo));
                }
                if (in_array($tipo, TIPOS_CON_OPCIONES, true) && empty($campo['opciones'])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" es {$tipo} pero no trae \"opciones\". El cargador nunca inserta catalogo_id NULL para estos tipos.");
                }
                if ($tipo === 'MATRIZ' && empty($campo['columnas'])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" es MATRIZ pero no trae \"columnas\".");
                }
                // "opciones_por_fila" (cotejo Z21, 2026-09-11): opciones
                // cerradas que cambian fila por fila dentro de una misma
                // columna -- {"Resultado": [["Positivo","Negativo"], ...]},
                // una lista por cada fila declarada. Ver campos/matriz.php.
                if (array_key_exists('opciones_por_fila', $campo)) {
                    if ($tipo !== 'MATRIZ') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" trae \"opciones_por_fila\" pero no es MATRIZ (es {$tipo}).");
                    }
                    $numFilasDeclaradas = is_array($campo['filas'] ?? null) ? count($campo['filas']) : 0;
                    if (!is_array($campo['opciones_por_fila']) || empty($campo['opciones_por_fila'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" tiene \"opciones_por_fila\" vacío o mal formado.");
                    }
                    foreach ($campo['opciones_por_fila'] as $columnaOpciones => $opcionesDeCadaFila) {
                        if (!in_array($columnaOpciones, $campo['columnas'], true)) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" declara \"opciones_por_fila\" para \"{$columnaOpciones}\", que no es una columna de este MATRIZ.");
                        }
                        if (!is_array($opcionesDeCadaFila) || count($opcionesDeCadaFila) !== $numFilasDeclaradas) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" / \"opciones_por_fila\".\"{$columnaOpciones}\" debe traer exactamente una lista de opciones por fila ({$numFilasDeclaradas}).");
                        }
                        foreach ($opcionesDeCadaFila as $opcionesFila) {
                            if (!is_array($opcionesFila) || empty($opcionesFila)) {
                                throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" / \"opciones_por_fila\".\"{$columnaOpciones}\" tiene una fila sin opciones.");
                            }
                            foreach ($opcionesFila as $opcionFila) {
                                if (!is_string($opcionFila) || trim($opcionFila) === '') {
                                    throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" / \"opciones_por_fila\".\"{$columnaOpciones}\" tiene una opción vacía.");
                                }
                            }
                        }
                    }
                }
                // "columnas_condicionadas" (A50, 2026-09-14): una columna libre
                // del MATRIZ que solo se habilita con cierto valor de otra
                // columna de la misma fila ("Otra prueba (cuál)" con "Tipo de
                // prueba" = Otra). Ver campos/matriz.php.
                if (array_key_exists('columnas_condicionadas', $campo)) {
                    if ($tipo !== 'MATRIZ') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" trae \"columnas_condicionadas\" pero no es MATRIZ (es {$tipo}).");
                    }
                    if (!is_array($campo['columnas_condicionadas']) || array_is_list($campo['columnas_condicionadas']) || !$campo['columnas_condicionadas']) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" / \"columnas_condicionadas\" debe ser un objeto {columna: {columna, valor}}.");
                    }
                    foreach ($campo['columnas_condicionadas'] as $columnaCondicionada => $condicionColumna) {
                        $dondeColumna = "{$cie10} / \"{$etiqueta}\" / \"columnas_condicionadas\".\"{$columnaCondicionada}\"";
                        if (!in_array($columnaCondicionada, $campo['columnas'], true)) {
                            throw new RuntimeException("Manifiesto inválido: {$dondeColumna} no es una columna de este MATRIZ.");
                        }
                        if (isset($campo['opciones_por_fila'][$columnaCondicionada])) {
                            throw new RuntimeException("Manifiesto inválido: {$dondeColumna} tiene opciones por fila: solo se condiciona una columna libre.");
                        }
                        if (!is_array($condicionColumna) || array_diff(array_keys($condicionColumna), ['columna', 'valor'])
                            || !is_string($condicionColumna['columna'] ?? null) || !is_string($condicionColumna['valor'] ?? null) || trim($condicionColumna['valor']) === '') {
                            throw new RuntimeException("Manifiesto inválido: {$dondeColumna} debe traer \"columna\" y \"valor\" (textos).");
                        }
                        if ($condicionColumna['columna'] === $columnaCondicionada || !in_array($condicionColumna['columna'], $campo['columnas'], true)) {
                            throw new RuntimeException("Manifiesto inválido: {$dondeColumna}.columna debe ser OTRA columna de este MATRIZ.");
                        }
                        foreach (($campo['opciones_por_fila'][$condicionColumna['columna']] ?? []) as $indiceFilaOpciones => $opcionesFilaCondicion) {
                            if (!in_array($condicionColumna['valor'], $opcionesFilaCondicion, true)) {
                                throw new RuntimeException("Manifiesto inválido: {$dondeColumna}.valor \"{$condicionColumna['valor']}\" no es una opción de \"{$condicionColumna['columna']}\" en la fila " . ($indiceFilaOpciones + 1) . '.');
                            }
                        }
                    }
                }
                // "obligatorio" (cotejo Z21, 2026-09-11): hasta ahora este
                // cargador insertaba SIEMPRE obligatorio=0 y las 89
                // declaraciones que ya traía el manifiesto (82 en B05, 7 en
                // P35.0) no las leía nadie. Son todas `false`, así que
                // empezar a respetarlas no cambia ninguna ficha existente.
                if (array_key_exists('obligatorio', $campo) && !is_bool($campo['obligatorio'])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" tiene \"obligatorio\" no booleano.");
                }
                if (array_key_exists('decimales', $campo)) {
                    if ($tipo !== 'NUMERO') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" trae \"decimales\" pero no es NUMERO (es {$tipo}).");
                    }
                    if (!is_bool($campo['decimales'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" tiene \"decimales\" no booleano.");
                    }
                }
                // "minimo" (Z21, 2026-09-13): piso de un NUMERO (conteos que no
                // pueden ser negativos). "grupo": rótulo de un bloque de campos
                // CONSECUTIVOS dentro de su sección ("Culminación del
                // embarazo"); el bloque termina en el primer campo sin ese grupo.
                if (array_key_exists('minimo', $campo)) {
                    if ($tipo !== 'NUMERO') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" trae \"minimo\" pero no es NUMERO (es {$tipo}).");
                    }
                    if (!is_int($campo['minimo']) && !is_float($campo['minimo'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" tiene \"minimo\" no numérico.");
                    }
                }
                // "desconocido" (A50, 2026-09-14): casilla "Desconocido" junto a
                // una fecha o un número (ítems del PDF con "Desconocido").
                if (array_key_exists('desconocido', $campo)) {
                    if (!in_array($tipo, ['FECHA', 'NUMERO'], true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" trae \"desconocido\" pero no es FECHA ni NUMERO (es {$tipo}).");
                    }
                    if ($campo['desconocido'] !== true) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" tiene \"desconocido\" distinto de true (para no usarlo, no se declara).");
                    }
                }
                if (array_key_exists('grupo', $campo) && (!is_string($campo['grupo']) || trim($campo['grupo']) === '')) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" tiene \"grupo\" vacío o no es texto.");
                }
                if (array_key_exists('formato', $campo)) {
                    if ($tipo !== 'TEXTO') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" trae \"formato\" pero no es TEXTO (es {$tipo}).");
                    }
                    if (!in_array($campo['formato'], FORMATOS_TEXTO, true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" tiene \"formato\" = " . json_encode($campo['formato']) . '. Válidos: ' . implode(', ', FORMATOS_TEXTO) . '.');
                    }
                }
                if (array_key_exists('calculado', $campo)) {
                    if ($tipo !== 'TEXTO') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" trae \"calculado\" pero no es TEXTO (es {$tipo}).");
                    }
                    if (!in_array($campo['calculado'], CALCULOS_TEXTO, true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" tiene \"calculado\" = " . json_encode($campo['calculado']) . '. Válidos: ' . implode(', ', CALCULOS_TEXTO) . '.');
                    }
                    if (array_key_exists('formato', $campo) || !empty($campo['obligatorio']) || !empty($campo['depende_de'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" es \"calculado\": no admite \"formato\", \"obligatorio\" ni \"depende_de\" (nadie lo escribe).");
                    }
                }
                if (array_key_exists('especificar', $campo)) {
                    if ($tipo !== 'SI_NO_FECHA') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" trae \"especificar\" pero no es SI_NO_FECHA (es {$tipo}).");
                    }
                    if (!is_bool($campo['especificar'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" tiene \"especificar\" no booleano.");
                    }
                }
                if (!empty($campo['depende_de'])) {
                    // No usar empty(): "0" es un valor_activador legítimo
                    // (ej. BOOLEANO en "No") y empty("0") === true en PHP lo
                    // rechazaría como si faltara (A97, ítems 29/30 del PDF:
                    // "Caso importado nacional/internacional" solo aplican
                    // si "Caso autóctono" = No).
                    if (!isset($campo['valor_activador']) || $campo['valor_activador'] === '') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" trae \"depende_de\" sin \"valor_activador\".");
                    }
                    $validarReferenciaCampo((string) $campo['depende_de'], "\"{$etiqueta}\"");
                    if ($campo['depende_de'] === $etiqueta || $campo['depende_de'] === ($campo['clave'] ?? null)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" depende de sí mismo.");
                    }
                }
            }
            if (!empty($seccion['depende_de'])) {
                $nombreSeccion = $seccion['nombre'] ?? '(sin nombre)';
                // Mismo motivo que el chequeo equivalente de campo, arriba:
                // no usar empty(), "0" es un valor_activador legítimo.
                if (!isset($seccion['valor_activador']) || $seccion['valor_activador'] === '') {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / sección \"{$nombreSeccion}\" trae \"depende_de\" sin \"valor_activador\".");
                }
                $validarReferenciaCampo((string) $seccion['depende_de'], "sección \"{$nombreSeccion}\"");
            }
        }

        // Orden explícito (Petición 2, Fase 6): todo o nada por ficha para
        // las secciones, y todo o nada por sección para sus campos. Mezclar
        // orden explícito e implícito produce colisiones y huecos
        // silenciosos -- mejor abortar acá que dejarlo pasar en silencio,
        // igual que ya se hace con los tipos desconocidos.
        $seccionesConOrden = 0;
        $ordenesSeccion = [];
        foreach ($ficha['secciones'] as $seccion) {
            if (array_key_exists('orden', $seccion)) {
                $seccionesConOrden++;
                $ordenesSeccion[] = $seccion['orden'];
            }
        }
        if ($seccionesConOrden > 0 && $seccionesConOrden < count($ficha['secciones'])) {
            throw new RuntimeException("Manifiesto inválido: {$cie10} mezcla secciones con \"orden\" explícito y secciones sin él. Todo o nada por ficha.");
        }
        if ($seccionesConOrden > 0 && count($ordenesSeccion) !== count(array_unique($ordenesSeccion, SORT_REGULAR))) {
            throw new RuntimeException("Manifiesto inválido: {$cie10} tiene dos o más secciones con el mismo \"orden\".");
        }

        foreach ($ficha['secciones'] as $seccion) {
            $nombreSeccion = $seccion['nombre'] ?? '(sin nombre)';
            $camposConOrden = 0;
            $ordenesCampo = [];
            foreach ($seccion['campos'] as $campo) {
                if (array_key_exists('orden', $campo)) {
                    $camposConOrden++;
                    $ordenesCampo[] = $campo['orden'];
                }
            }
            if ($camposConOrden > 0 && $camposConOrden < count($seccion['campos'])) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / sección \"{$nombreSeccion}\" mezcla campos con \"orden\" explícito y campos sin él. Todo o nada por sección.");
            }
            if ($camposConOrden > 0 && count($ordenesCampo) !== count(array_unique($ordenesCampo, SORT_REGULAR))) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / sección \"{$nombreSeccion}\" tiene dos o más campos con el mismo \"orden\".");
            }
        }

        if (!empty($ficha['columnas_tablas_hija'])) {
            foreach ($ficha['columnas_tablas_hija'] as $tabla => $declaracion) {
                if (!isset(COLUMNAS_TABLA_HIJA_VALIDAS[$tabla])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija tiene una tabla desconocida: \"{$tabla}\".");
                }

                // Forma plana (lista) -- compat con lo que ya declaraban A80/B05
                // antes de PETICION_HC_Y_LABORATORIO.md Parte 2 -- o forma objeto
                // {"columnas", "opciones", "texto_libre"}, hoy solo para
                // caso_muestra: "opciones" restringe el vocabulario de una
                // columna por ficha (reemplaza a la const PHP
                // OPCIONES_MUESTRA_POR_ENFERMEDAD), "texto_libre" la vuelve un
                // <input> en vez de <select> (hoy solo tiene sentido para
                // "genotipo"). No se valida el CONTENIDO de "opciones" contra
                // catalogo_item ni contra los arrays PHP de muestras.php (serían
                // dos fuentes de verdad más para mantener sincronizadas) -- solo
                // que la forma sea una lista no vacía de strings.
                if (array_is_list($declaracion)) {
                    $columnas = $declaracion;
                    $opciones = [];
                    $textoLibre = [];
                    $dependeDeColumna = [];
                    $unicoPorTipo = false;
                } else {
                    $clavesDesconocidas = array_diff(array_keys($declaracion), ['columnas', 'opciones', 'texto_libre', 'depende_de_columna', 'unico_por_tipo']);
                    if ($clavesDesconocidas) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla} tiene claves desconocidas: " . implode(', ', $clavesDesconocidas) . ".");
                    }
                    $columnas = $declaracion['columnas'] ?? [];
                    $opciones = $declaracion['opciones'] ?? [];
                    $textoLibre = $declaracion['texto_libre'] ?? [];
                    $dependeDeColumna = $declaracion['depende_de_columna'] ?? [];
                    $unicoPorTipo = $declaracion['unico_por_tipo'] ?? false;
                }

                foreach ($columnas as $col) {
                    if (!in_array($col, COLUMNAS_TABLA_HIJA_VALIDAS[$tabla], true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla} incluye \"{$col}\", que no es una columna configurable de esa tabla.");
                    }
                }

                if (($opciones || $textoLibre || $dependeDeColumna || $unicoPorTipo) && $tabla !== 'caso_muestra') {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla} declara \"opciones\"/\"texto_libre\"/\"depende_de_columna\"/\"unico_por_tipo\", que hoy solo están implementados para caso_muestra.");
                }

                // unico_por_tipo (2026-08-23, PETICION_HC_Y_LABORATORIO.md
                // cotejo A95): booleano simple -- 1 registro como máximo por
                // valor de "tipo_muestra" en el mismo caso. Solo tiene
                // sentido si la ficha además restringe "opciones.tipo_muestra"
                // a un vocabulario finito (si no, cualquier tipo_muestra del
                // catálogo compartido -id 4- cuenta como "categoría única",
                // lo que probablemente no es la intención de quien lo declare).
                if ($unicoPorTipo !== false && $unicoPorTipo !== true) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.unico_por_tipo debe ser booleano.");
                }
                if ($unicoPorTipo && empty($opciones['tipo_muestra'])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.unico_por_tipo=true requiere declarar opciones.tipo_muestra (un vocabulario finito) -- si no, no hay un conjunto cerrado de \"tipos\" contra el cual aplicar la regla.");
                }

                // depende_de_columna (capacidad 5, PETICION_HC_Y_LABORATORIO.md
                // Parte 2): visibilidad de UNA columna condicionada al valor de
                // OTRA columna de la MISMA fila -- mismo idioma que
                // depende_de/valor_activador de campo_def, pero resuelto por
                // columna dentro de una tabla hija en vez de por campo suelto.
                // Reemplaza al toggle hardcodeado de B05 ($esSuero/$esPcrGen en
                // muestras.php). Acotado a "un disparador, un conjunto de
                // valores" -- no es un motor de reglas general.
                foreach ($dependeDeColumna as $colDependiente => $regla) {
                    if (!in_array($colDependiente, COLUMNAS_TABLA_HIJA_VALIDAS[$tabla], true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.depende_de_columna incluye \"{$colDependiente}\", que no es una columna configurable de esa tabla.");
                    }
                    if (!is_array($regla) || array_diff(array_keys($regla), ['columna', 'valores_activadores'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.depende_de_columna.{$colDependiente} debe ser {\"columna\", \"valores_activadores\"}.");
                    }
                    $colDisparadora = $regla['columna'] ?? null;
                    $valoresActivadores = $regla['valores_activadores'] ?? null;
                    if (!is_string($colDisparadora) || $colDisparadora === '') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.depende_de_columna.{$colDependiente}.columna debe ser texto no vacío.");
                    }
                    if (!in_array($colDisparadora, COLUMNAS_TABLA_HIJA_VALIDAS[$tabla], true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.depende_de_columna.{$colDependiente}.columna (\"{$colDisparadora}\") no es una columna configurable de esa tabla.");
                    }
                    if ($colDisparadora === $colDependiente) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.depende_de_columna.{$colDependiente} depende de sí misma.");
                    }
                    if (!is_array($valoresActivadores) || empty($valoresActivadores) || !array_is_list($valoresActivadores)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.depende_de_columna.{$colDependiente}.valores_activadores debe ser una lista no vacía de valores.");
                    }
                    foreach ($valoresActivadores as $v) {
                        if (!is_string($v) || $v === '') {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.depende_de_columna.{$colDependiente}.valores_activadores tiene un valor no válido (debe ser texto no vacío).");
                        }
                    }
                }

                foreach ($opciones as $col => $valores) {
                    if (!in_array($col, COLUMNAS_TABLA_HIJA_VALIDAS[$tabla], true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.opciones incluye \"{$col}\", que no es una columna configurable de esa tabla.");
                    }
                    if (!is_array($valores) || empty($valores) || !array_is_list($valores)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.opciones.{$col} debe ser una lista no vacía de valores.");
                    }
                    foreach ($valores as $v) {
                        if (!is_string($v) || $v === '') {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.opciones.{$col} tiene un valor no válido (debe ser texto no vacío).");
                        }
                    }
                }

                foreach ($textoLibre as $col) {
                    if (!in_array($col, COLUMNAS_TABLA_HIJA_VALIDAS[$tabla], true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla}.texto_libre incluye \"{$col}\", que no es una columna configurable de esa tabla.");
                    }
                }

                $columnasEnConflicto = array_intersect(array_keys($opciones), $textoLibre);
                if ($columnasEnConflicto) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_tablas_hija.{$tabla} declara \"" . implode(', ', $columnasEnConflicto) . "\" en \"opciones\" y \"texto_libre\" a la vez; son excluyentes.");
                }
            }
        }

        // bloques_condicionales (capacidad 6, PETICION_HC_Y_LABORATORIO.md
        // Parte 2, ítem 43 de P35.0): un SEGUNDO conjunto de filas de una
        // tabla hija, distinguido por "contexto", visible solo cuando la
        // Clasificación del caso (núcleo, no campo_def) toma uno de
        // "valores_activadores". Acotado a caso_muestra -- mismo criterio de
        // "un disparador, un conjunto de valores" que depende_de_columna,
        // pero el disparador es del NÚCLEO ("clasificacion" literal), no un
        // campo_def con id numérico: no hay motor de reglas general, solo
        // este único caso de uso resuelto declarativamente.
        if (!empty($ficha['bloques_condicionales'])) {
            if (!array_is_list($ficha['bloques_condicionales'])) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / bloques_condicionales debe ser una lista.");
            }
            foreach ($ficha['bloques_condicionales'] as $i => $bloque) {
                $clavesDesconocidas = array_diff(array_keys($bloque), ['tabla', 'contexto', 'titulo', 'columnas', 'depende_de', 'valores_activadores']);
                if ($clavesDesconocidas) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / bloques_condicionales[{$i}] tiene claves desconocidas: " . implode(', ', $clavesDesconocidas) . ".");
                }
                $tabla = $bloque['tabla'] ?? null;
                if ($tabla !== 'caso_muestra') {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / bloques_condicionales[{$i}].tabla debe ser \"caso_muestra\" (única tabla implementada hoy).");
                }
                $contexto = $bloque['contexto'] ?? null;
                if (!is_string($contexto) || $contexto === '' || $contexto === 'inicial') {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / bloques_condicionales[{$i}].contexto debe ser texto no vacío distinto de \"inicial\" (reservado para las filas del bloque base).");
                }
                $titulo = $bloque['titulo'] ?? null;
                if (!is_string($titulo) || $titulo === '') {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / bloques_condicionales[{$i}].titulo debe ser texto no vacío.");
                }
                $columnasBloque = $bloque['columnas'] ?? null;
                if (!is_array($columnasBloque) || empty($columnasBloque) || !array_is_list($columnasBloque)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / bloques_condicionales[{$i}].columnas debe ser una lista no vacía.");
                }
                foreach ($columnasBloque as $col) {
                    if (!in_array($col, COLUMNAS_TABLA_HIJA_VALIDAS[$tabla], true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / bloques_condicionales[{$i}].columnas incluye \"{$col}\", que no es una columna configurable de {$tabla}.");
                    }
                }
                if (($bloque['depende_de'] ?? null) !== 'clasificacion') {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / bloques_condicionales[{$i}].depende_de debe ser \"clasificacion\" (único disparador implementado hoy: el núcleo, no un campo_def).");
                }
                $valoresActivadores = $bloque['valores_activadores'] ?? null;
                if (!is_array($valoresActivadores) || empty($valoresActivadores) || !array_is_list($valoresActivadores)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / bloques_condicionales[{$i}].valores_activadores debe ser una lista no vacía.");
                }
                foreach ($valoresActivadores as $v) {
                    if (!is_string($v) || $v === '') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / bloques_condicionales[{$i}].valores_activadores tiene un valor no válido (debe ser texto no vacío).");
                    }
                }
            }
        }

        if (!empty($ficha['nucleo_omitidos'])) {
            foreach ($ficha['nucleo_omitidos'] as $campoNucleo) {
                if (!in_array($campoNucleo, NUCLEO_OMITIBLES, true)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_omitidos incluye \"{$campoNucleo}\", que no es un campo omitible del núcleo. Válidos: " . implode(', ', NUCLEO_OMITIBLES) . ".");
                }
            }
        }

        if (!empty($ficha['nucleo_incluidos'])) {
            foreach ($ficha['nucleo_incluidos'] as $campoNucleo) {
                if (!in_array($campoNucleo, NUCLEO_INCLUIBLES, true)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_incluidos incluye \"{$campoNucleo}\", que no es un campo incluible del núcleo. Válidos: " . implode(', ', NUCLEO_INCLUIBLES) . ".");
                }
            }
        }

        // nucleo_ajustes (P96, 2026-09-13): ver NUCLEO_AJUSTES_VALIDOS.
        if (array_key_exists('nucleo_ajustes', $ficha)) {
            if (!is_array($ficha['nucleo_ajustes']) || array_is_list($ficha['nucleo_ajustes'])) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes debe ser un objeto {ajuste: valor}.");
            }
            foreach ($ficha['nucleo_ajustes'] as $ajuste => $valorAjuste) {
                if (str_starts_with((string) $ajuste, '_')) {
                    continue; // _nota
                }
                $tipoAjuste = NUCLEO_AJUSTES_VALIDOS[$ajuste] ?? null;
                if ($tipoAjuste === null) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste} no es un ajuste conocido. Válidos: " . implode(', ', array_keys(NUCLEO_AJUSTES_VALIDOS)) . '.');
                }
                if ($tipoAjuste === 'bool' && $valorAjuste !== true) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste} solo admite true (para no activarlo, no se declara).");
                }
                if ($tipoAjuste === 'texto' && (!is_string($valorAjuste) || trim($valorAjuste) === '')) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste} debe ser un texto no vacío.");
                }
                if ($tipoAjuste === 'condiciones') {
                    $condicionesValidas = array_keys(CONDICIONES_PACIENTE);
                    if (!is_array($valorAjuste) || !array_is_list($valorAjuste) || $valorAjuste === []
                        || count(array_unique($valorAjuste)) !== count($valorAjuste)
                        || array_diff($valorAjuste, $condicionesValidas) !== []) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste} debe ser una lista sin repetidos de: " . implode(', ', $condicionesValidas) . '.');
                    }
                    if (count($valorAjuste) === count($condicionesValidas)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste} admite las tres condiciones: para eso no se declara.");
                    }
                }
                if ($tipoAjuste === 'etiquetas') {
                    if (!is_array($valorAjuste) || array_is_list($valorAjuste) || $valorAjuste === []) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste} debe ser un objeto no vacío {campo: etiqueta}.");
                    }
                    foreach ($valorAjuste as $campoEtiqueta => $textoEtiqueta) {
                        if (!in_array($campoEtiqueta, ETIQUETAS_NUCLEO, true)) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste}.{$campoEtiqueta} no es un campo con etiqueta ajustable. Válidos: " . implode(', ', ETIQUETAS_NUCLEO) . '.');
                        }
                        if (!is_string($textoEtiqueta) || trim($textoEtiqueta) === '') {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste}.{$campoEtiqueta} debe ser un texto no vacío.");
                        }
                    }
                }
                if ($tipoAjuste === 'investigador') {
                    if (!is_array($valorAjuste) || array_is_list($valorAjuste) || array_diff(array_keys($valorAjuste), ['titulo', 'campos']) !== []) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste} debe ser un objeto {\"titulo\": texto, \"campos\": {campo: etiqueta o true}}.");
                    }
                    if (array_key_exists('titulo', $valorAjuste) && (!is_string($valorAjuste['titulo']) || trim($valorAjuste['titulo']) === '')) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste}.titulo debe ser un texto no vacío.");
                    }
                    if (!is_array($valorAjuste['campos'] ?? null) || array_is_list($valorAjuste['campos'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste}.campos debe ser un objeto no vacío {campo: etiqueta o true}.");
                    }
                    foreach ($valorAjuste['campos'] as $campoInvestigador => $etiquetaInvestigador) {
                        if (!array_key_exists($campoInvestigador, CAMPOS_INVESTIGADOR)) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste}.campos.{$campoInvestigador} no es un campo de la tarjeta. Válidos: " . implode(', ', array_keys(CAMPOS_INVESTIGADOR)) . '.');
                        }
                        if ($etiquetaInvestigador !== true && (!is_string($etiquetaInvestigador) || trim($etiquetaInvestigador) === '')) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste}.campos.{$campoInvestigador} debe ser su etiqueta (texto) o true.");
                        }
                    }
                    if (in_array('investigador', (array) ($ficha['nucleo_omitidos'] ?? []), true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.{$ajuste} ajusta una tarjeta que nucleo_omitidos quita.");
                    }
                }
            }
            if (!empty($ficha['nucleo_ajustes']['titulo_residencia']) && !empty($ficha['nucleo_condicional'])) {
                foreach ($ficha['nucleo_condicional'] as $reglaNucleoAjuste) {
                    if (in_array('residencia', $reglaNucleoAjuste['bloques'] ?? [], true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_ajustes.titulo_residencia no se combina con una residencia condicional (nucleo_condicional).");
                    }
                }
            }
        }

        // campos_notificacion (cotejo Z21, 2026-09-11): claves de campo_def
        // de esta misma ficha que se pintan dentro de la tarjeta fija "1.
        // Notificación" (partials/notificacion-campos-declarados.php) en vez
        // de generar su propia tarjeta. campos_persona (Z21, 2026-09-12): lo
        // mismo dentro de la tarjeta de identidad
        // (partials/persona-campos-declarados.php). Una clave va a una sola
        // tarjeta.
        $tarjetasCamposDeclarados = [
            'campos_notificacion' => 'la tarjeta "1. Notificación"',
            'campos_persona'      => 'la tarjeta de identidad',
        ];
        // campos_persona admite también un objeto por fila de la tarjeta
        // (P96, 2026-09-13): {"documento": [...], "nacimiento": [...]}. Se
        // valida su forma y se aplana para los mismos chequeos de la lista.
        $clavesCamposPersonaFicha = $ficha['campos_persona'] ?? [];
        if (is_array($clavesCamposPersonaFicha) && $clavesCamposPersonaFicha && !array_is_list($clavesCamposPersonaFicha)) {
            $planas = [];
            foreach ($clavesCamposPersonaFicha as $filaPersona => $clavesFilaPersona) {
                if (!in_array($filaPersona, FILAS_CAMPOS_PERSONA, true)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / campos_persona declara la fila \"{$filaPersona}\". Válidas: " . implode(', ', FILAS_CAMPOS_PERSONA) . '.');
                }
                if (!is_array($clavesFilaPersona) || !array_is_list($clavesFilaPersona) || !$clavesFilaPersona) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / campos_persona.{$filaPersona} debe ser una lista no vacía de claves.");
                }
                $planas = array_merge($planas, $clavesFilaPersona);
            }
            $clavesCamposPersonaFicha = $planas;
        }
        $listasCamposDeclarados = [
            'campos_notificacion' => $ficha['campos_notificacion'] ?? [],
            'campos_persona'      => $clavesCamposPersonaFicha,
        ];
        foreach ($tarjetasCamposDeclarados as $declaracionCampos => $tarjetaCampos) {
            $listaDeclarada = $listasCamposDeclarados[$declaracionCampos];
            if (empty($listaDeclarada)) {
                continue;
            }
            if (!is_array($listaDeclarada) || !array_is_list($listaDeclarada)) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / {$declaracionCampos} debe ser una lista de claves.");
            }
            foreach ($listaDeclarada as $claveDeclarada) {
                if (!is_string($claveDeclarada) || !isset($clavesFicha[$claveDeclarada])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / {$declaracionCampos} incluye \"{$claveDeclarada}\", que no es una \"clave\" explícita de esta ficha.");
                }
            }
            if (count($listaDeclarada) !== count(array_unique($listaDeclarada))) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / {$declaracionCampos} repite una clave.");
            }
            foreach ($ficha['secciones'] as $seccion) {
                foreach ($seccion['campos'] as $campo) {
                    if (in_array($campo['clave'] ?? '', $listaDeclarada, true) && !empty($campo['depende_de'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$declaracionCampos} incluye \"{$campo['clave']}\", que tiene \"depende_de\": {$tarjetaCampos} no pinta envolturas condicionales.");
                    }
                }
            }
        }
        $clavesEnDosTarjetas = array_intersect($ficha['campos_notificacion'] ?? [], $clavesCamposPersonaFicha);
        if ($clavesEnDosTarjetas) {
            throw new RuntimeException("Manifiesto inválido: {$cie10} / \"" . implode('", "', $clavesEnDosTarjetas) . "\" está a la vez en campos_notificacion y campos_persona: cada campo se pinta en una sola tarjeta.");
        }

        // vinculo_caso (cotejo Z21, 2026-09-11): enlace de un caso con otro
        // caso de la MISMA ficha (el niño nacido expuesto con la ficha de su
        // madre). "activador" dice con qué valor aplica el enlace;
        // "candidatos", qué casos se ofrecen; "copiar", qué datos del caso
        // vinculado se copian a campos de este.
        if (!empty($ficha['vinculo_caso'])) {
            $vinculo = $ficha['vinculo_caso'];
            foreach (['seccion', 'etiqueta'] as $claveTexto) {
                if (empty($vinculo[$claveTexto]) || !is_string($vinculo[$claveTexto])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.{$claveTexto} debe ser un texto no vacío.");
                }
            }
            $nombresSeccionesFicha = array_map(fn($s) => trim((string) ($s['nombre'] ?? '')), $ficha['secciones']);
            if (!in_array(trim($vinculo['seccion']), $nombresSeccionesFicha, true)) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.seccion \"{$vinculo['seccion']}\" no es una sección de esta ficha.");
            }
            foreach (['activador', 'candidatos'] as $parteVinculo) {
                if (empty($vinculo[$parteVinculo]['clave']) || !isset($clavesFicha[$vinculo[$parteVinculo]['clave']])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.{$parteVinculo}.clave debe ser una \"clave\" explícita de esta ficha.");
                }
                if (empty($vinculo[$parteVinculo]['valor']) || !is_string($vinculo[$parteVinculo]['valor'])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.{$parteVinculo}.valor debe ser un texto no vacío (el CÓDIGO de la opción, no su etiqueta).");
                }
            }
            // Mismo juego de columnas que CasosController::valorCopiadoDeVinculo().
            // "nombre_completo" (A50, 2026-09-14): apellidos y nombres juntos.
            $columnasPersonaCopiables = ['num_doc', 'tipo_doc', 'nombres', 'apellido_paterno', 'apellido_materno', 'fecha_nac', 'nombre_completo'];
            foreach (($vinculo['copiar'] ?? []) as $claveDestino => $origenCopia) {
                if (!isset($clavesFicha[$claveDestino])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.copiar apunta a \"{$claveDestino}\", que no es una \"clave\" explícita de esta ficha.");
                }
                if (!is_string($origenCopia) || (!str_starts_with($origenCopia, 'campo:') && !str_starts_with($origenCopia, 'persona:'))) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.copiar.\"{$claveDestino}\" debe ser \"campo:<clave>\" o \"persona:<columna>\".");
                }
                if (str_starts_with($origenCopia, 'campo:') && !isset($clavesFicha[substr($origenCopia, 6)])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.copiar.\"{$claveDestino}\" toma \"{$origenCopia}\", cuya clave no existe en esta ficha.");
                }
                if (str_starts_with($origenCopia, 'persona:') && !in_array(substr($origenCopia, 8), $columnasPersonaCopiables, true)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.copiar.\"{$claveDestino}\" toma \"{$origenCopia}\", que no es una columna copiable de persona. Válidas: " . implode(', ', $columnasPersonaCopiables) . '.');
                }
            }
            // fijar_por_procedencia / buscar (pedido del usuario, 2026-09-11):
            // el vínculo deja de elegirse en un desplegable con todos los
            // candidatos -- viene fijado de la ficha de la madre, o se
            // identifica por código de ficha / documento exacto.
            if (array_key_exists('fijar_por_procedencia', $vinculo) && !is_bool($vinculo['fijar_por_procedencia'])) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.fijar_por_procedencia debe ser true o false.");
            }
            if (!empty($vinculo['buscar'])) {
                if (empty($vinculo['fijar_por_procedencia'])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.buscar solo aplica con \"fijar_por_procedencia\": true (sin eso el vínculo se elige de la lista de candidatos).");
                }
                if (!is_array($vinculo['buscar'])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.buscar debe ser un objeto de textos.");
                }
                foreach ($vinculo['buscar'] as $claveBuscar => $textoBuscar) {
                    if (!in_array($claveBuscar, ['etiqueta', 'placeholder', 'ayuda', 'accion', 'no_encontrada', 'no_disponible', 'quitar'], true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.buscar.\"{$claveBuscar}\" no es una clave válida. Válidas: etiqueta, placeholder, ayuda, accion, no_encontrada, no_disponible, quitar.");
                    }
                    if (!is_string($textoBuscar) || trim($textoBuscar) === '') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.buscar.\"{$claveBuscar}\" debe ser un texto no vacío.");
                    }
                }
            }
            // encadenar: tras guardar un caso CANDIDATO (la gestante), cuántas
            // fichas vinculadas quedan por registrar según un campo numérico
            // suyo (N.º de nacidos vivos).
            if (!empty($vinculo['encadenar'])) {
                if (empty($vinculo['encadenar']['clave']) || !isset($clavesFicha[$vinculo['encadenar']['clave']])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.encadenar.clave debe ser una \"clave\" explícita de esta ficha.");
                }
                if (($tiposPorClaveFicha[$vinculo['encadenar']['clave']] ?? '') !== 'NUMERO') {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.encadenar.clave debe apuntar a un campo NUMERO (cuántas fichas vinculadas se esperan).");
                }
                foreach (['titulo_pendientes', 'mensaje'] as $claveEncadenar) {
                    if (empty($vinculo['encadenar'][$claveEncadenar]) || !is_string($vinculo['encadenar'][$claveEncadenar])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / vinculo_caso.encadenar.{$claveEncadenar} debe ser un texto no vacío.");
                    }
                }
            }
        }

        // nucleo_condicional (cotejo Z21, 2026-09-11): bloques del núcleo que
        // la ficha pide solo con cierto valor de uno de sus campo_def.
        if (!empty($ficha['nucleo_condicional'])) {
            if (!is_array($ficha['nucleo_condicional']) || !array_is_list($ficha['nucleo_condicional'])) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional debe ser una lista de reglas.");
            }
            $claveRamaAjustes = null;
            foreach ($ficha['nucleo_condicional'] as $reglaNucleo) {
                if (empty($reglaNucleo['clave']) || !isset($clavesFicha[$reglaNucleo['clave']])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.clave debe ser una \"clave\" explícita de esta ficha.");
                }
                if (empty($reglaNucleo['valores']) || !is_array($reglaNucleo['valores'])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.valores debe ser una lista no vacía (CÓDIGOS de opción).");
                }
                // "ajustes" (A50, 2026-09-14): ajustes de la tarjeta de identidad
                // que solo valen en esa rama (ver NUCLEO_AJUSTES_POR_RAMA). Una
                // regla puede traer solo ajustes, sin bloques.
                if (array_key_exists('ajustes', $reglaNucleo)) {
                    if (!is_array($reglaNucleo['ajustes']) || array_is_list($reglaNucleo['ajustes']) || !$reglaNucleo['ajustes']) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.ajustes debe ser un objeto {ajuste: valor}.");
                    }
                    foreach ($reglaNucleo['ajustes'] as $ajusteRama => $valorAjusteRama) {
                        $tipoAjusteRama = NUCLEO_AJUSTES_POR_RAMA[$ajusteRama] ?? null;
                        if ($tipoAjusteRama === null) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.ajustes.{$ajusteRama} no se admite por rama. Válidos: " . implode(', ', array_keys(NUCLEO_AJUSTES_POR_RAMA)) . '.');
                        }
                        if ($tipoAjusteRama === 'bool' && $valorAjusteRama !== true) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.ajustes.{$ajusteRama} solo admite true.");
                        }
                        if ($tipoAjusteRama === 'texto' && (!is_string($valorAjusteRama) || trim($valorAjusteRama) === '')) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.ajustes.{$ajusteRama} debe ser un texto no vacío.");
                        }
                        if ($tipoAjusteRama === 'condiciones') {
                            $condicionesValidasRama = array_keys(CONDICIONES_PACIENTE);
                            if (!is_array($valorAjusteRama) || !array_is_list($valorAjusteRama) || $valorAjusteRama === []
                                || count(array_unique($valorAjusteRama)) !== count($valorAjusteRama)
                                || array_diff($valorAjusteRama, $condicionesValidasRama) !== []
                                || count($valorAjusteRama) === count($condicionesValidasRama)) {
                                throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.ajustes.{$ajusteRama} debe ser una lista sin repetidos de " . implode(', ', $condicionesValidasRama) . ' que no las incluya todas.');
                            }
                        }
                        if (array_key_exists($ajusteRama, (array) ($ficha['nucleo_ajustes'] ?? []))) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$ajusteRama}\" está en nucleo_ajustes y en nucleo_condicional.ajustes: se declara para toda la ficha o por rama, no las dos.");
                        }
                    }
                    // La vista muestra u oculta por rama con un solo campo por
                    // ficha (atributosRama()).
                    $claveRamaAjustes ??= $reglaNucleo['clave'];
                    if ($reglaNucleo['clave'] !== $claveRamaAjustes) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.ajustes: todas las reglas con ajustes deben usar la misma \"clave\".");
                    }
                }
                if (!array_key_exists('ajustes', $reglaNucleo) && (empty($reglaNucleo['bloques']) || !is_array($reglaNucleo['bloques']))) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.bloques debe ser una lista no vacía.");
                }
                foreach (($reglaNucleo['bloques'] ?? []) as $bloqueNucleo) {
                    if (!in_array($bloqueNucleo, NUCLEO_CONDICIONABLES, true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional incluye el bloque \"{$bloqueNucleo}\", que no es condicionable. Válidos: " . implode(', ', NUCLEO_CONDICIONABLES) . '.');
                    }
                }
                // valor_fijo: qué se guarda en los bloques de esta regla
                // cuando NO aplican (Z21: sexo = F en la rama de la gestante).
                foreach (($reglaNucleo['valor_fijo'] ?? []) as $bloqueFijo => $valorFijo) {
                    if (!in_array($bloqueFijo, $reglaNucleo['bloques'] ?? [], true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.valor_fijo declara \"{$bloqueFijo}\", que no es uno de los bloques de esa misma regla.");
                    }
                    if (!isset(NUCLEO_VALOR_FIJO_VALIDO[$bloqueFijo])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.valor_fijo no aplica al bloque \"{$bloqueFijo}\". Lo admiten: " . implode(', ', array_keys(NUCLEO_VALOR_FIJO_VALIDO)) . '.');
                    }
                    if (!in_array($valorFijo, NUCLEO_VALOR_FIJO_VALIDO[$bloqueFijo], true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.valor_fijo.{$bloqueFijo} = \"{$valorFijo}\" no es un valor válido. Válidos: " . implode(', ', NUCLEO_VALOR_FIJO_VALIDO[$bloqueFijo]) . '.');
                    }
                }
                // titulos / aviso: solo para el bloque 'persona' -- la tarjeta
                // de identidad se titula según el valor elegido y, mientras no
                // hay ninguno, avisa qué falta completar.
                if ((array_key_exists('titulos', $reglaNucleo) || array_key_exists('aviso', $reglaNucleo))
                    && !in_array('persona', $reglaNucleo['bloques'] ?? [], true)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.titulos y .aviso solo aplican a una regla con el bloque \"persona\".");
                }
                foreach (($reglaNucleo['titulos'] ?? []) as $valorTitulo => $textoTitulo) {
                    if (!in_array((string) $valorTitulo, array_map('strval', $reglaNucleo['valores']), true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.titulos declara \"{$valorTitulo}\", que no está en \"valores\" de esa misma regla.");
                    }
                    if (!is_string($textoTitulo) || trim($textoTitulo) === '') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.titulos.\"{$valorTitulo}\" debe ser un texto no vacío.");
                    }
                }
                if (array_key_exists('aviso', $reglaNucleo) && (!is_string($reglaNucleo['aviso']) || trim($reglaNucleo['aviso']) === '')) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / nucleo_condicional.aviso debe ser un texto no vacío.");
                }
            }
        }

        // reglas_campos (Z21, 2026-09-13): reglas entre campos que depende_de
        // no expresa. Cada regla tiene una condición "si" y al menos un
        // efecto: "mostrar" (campos visibles solo si se cumple), "fijar"
        // (valores forzados mientras se cumple), "suma_maxima" (tope de una
        // suma de NUMERO, con su "mensaje") o "clasificar" (la clasificación
        // del caso; gana la primera regla que se cumple).
        if (!empty($ficha['reglas_campos'])) {
            if (!is_array($ficha['reglas_campos']) || !array_is_list($ficha['reglas_campos'])) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / reglas_campos debe ser una lista de reglas.");
            }
            $clavesEnTarjetasFijas = array_merge($ficha['campos_notificacion'] ?? [], $clavesCamposPersonaFicha);
            $exigirClavesNumero = function (mixed $claves, string $donde, int $minimo) use ($cie10, $clavesFicha, $tiposPorClaveFicha): void {
                if (!is_array($claves) || !array_is_list($claves) || count($claves) < $minimo) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde} debe ser una lista de al menos {$minimo} clave(s).");
                }
                foreach ($claves as $claveNumero) {
                    if (!is_string($claveNumero) || !isset($clavesFicha[$claveNumero])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde} incluye \"{$claveNumero}\", que no es una \"clave\" explícita de esta ficha.");
                    }
                    if ($tiposPorClaveFicha[$claveNumero] !== 'NUMERO') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde} incluye \"{$claveNumero}\", que no es NUMERO.");
                    }
                }
            };
            $clavesYaMostradas = [];
            foreach ($ficha['reglas_campos'] as $indiceRegla => $reglaCampos) {
                $donde = "reglas_campos[{$indiceRegla}]";
                if (!is_array($reglaCampos) || array_is_list($reglaCampos)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde} debe ser un objeto.");
                }
                $clavesDesconocidas = array_diff(array_keys($reglaCampos), ['si', 'mostrar', 'fijar', 'suma_maxima', 'clasificar', 'opciones', 'comparar_fechas', 'maximo_dias_entre', 'alguno_minimo', 'alguno_menor_que', 'fallecido', 'mensaje', '_nota']);
                if ($clavesDesconocidas) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde} trae \"" . implode('", "', $clavesDesconocidas) . "\". Válidas: si, mostrar, fijar, suma_maxima, clasificar, opciones, comparar_fechas, maximo_dias_entre, alguno_minimo, alguno_menor_que, fallecido, mensaje.");
                }
                $si = $reglaCampos['si'] ?? null;
                if (!is_array($si)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.si es obligatorio.");
                }
                if (isset($si['clave'])) {
                    // "nucleo:sexo" (B24, 2026-09-14): la condición lee el
                    // núcleo, no un campo_def (CONDICIONES_NUCLEO_REGLAS).
                    $condicionNucleo = is_string($si['clave']) && array_key_exists($si['clave'], CONDICIONES_NUCLEO_REGLAS);
                    if (array_diff(array_keys($si), ['clave', 'valores']) || (!$condicionNucleo && !isset($clavesFicha[$si['clave']]))) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.si con \"clave\" solo admite \"clave\" (de esta ficha o " . implode(', ', array_keys(CONDICIONES_NUCLEO_REGLAS)) . ") y \"valores\".");
                    }
                    if (empty($si['valores']) || !is_array($si['valores']) || !array_is_list($si['valores'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.si.valores debe ser una lista no vacía (CÓDIGOS de opción).");
                    }
                    if ($condicionNucleo && array_diff($si['valores'], CONDICIONES_NUCLEO_REGLAS[$si['clave']])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.si.valores de {$si['clave']} solo admite: " . implode(', ', CONDICIONES_NUCLEO_REGLAS[$si['clave']]) . '.');
                    }
                    // La clasificación y la defunción calculadas se derivan solo
                    // de los campos de la ficha (clasificacionDerivada() y
                    // fallecidoDerivado() no reciben el núcleo).
                    if ($condicionNucleo && (array_key_exists('clasificar', $reglaCampos) || array_key_exists('fallecido', $reglaCampos))) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}: \"clasificar\" y \"fallecido\" no admiten una condición sobre el núcleo.");
                    }
                    $clavesCondicion = $condicionNucleo ? [] : [$si['clave']];
                } elseif (isset($si['claves'])) {
                    if (array_diff(array_keys($si), ['claves', 'alguno_mayor_que']) || !array_key_exists('alguno_mayor_que', $si)
                        || (!is_int($si['alguno_mayor_que']) && !is_float($si['alguno_mayor_que']))) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.si con \"claves\" necesita \"alguno_mayor_que\" numérico.");
                    }
                    $exigirClavesNumero($si['claves'], "{$donde}.si.claves", 1);
                    $clavesCondicion = $si['claves'];
                } else {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.si debe traer \"clave\"+\"valores\" o \"claves\"+\"alguno_mayor_que\".");
                }
                if (!array_intersect(array_keys($reglaCampos), ['mostrar', 'fijar', 'suma_maxima', 'clasificar', 'opciones', 'comparar_fechas', 'maximo_dias_entre', 'alguno_minimo', 'alguno_menor_que', 'fallecido'])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde} no declara ningún efecto (mostrar, fijar, suma_maxima, clasificar, opciones, comparar_fechas, maximo_dias_entre, alguno_minimo, alguno_menor_que o fallecido).");
                }
                // "fallecido" (A50, 2026-09-14): con la condición cumplida, el
                // caso es una defunción (caso.fallecido = 1; si no se cumple
                // ninguna, 0).
                if (array_key_exists('fallecido', $reglaCampos) && $reglaCampos['fallecido'] !== true) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.fallecido solo admite true.");
                }
                if (array_key_exists('clasificar', $reglaCampos)
                    && (!is_string($reglaCampos['clasificar']) || !array_key_exists($reglaCampos['clasificar'], CATALOGO_CLASIFICACION))) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.clasificar = " . json_encode($reglaCampos['clasificar']) . " no está en CATALOGO_CLASIFICACION (app/Core/ayudantes.php); agrégalo ahí y al ENUM de caso.clasificacion primero.");
                }
                if (array_key_exists('mostrar', $reglaCampos)) {
                    if (!is_array($reglaCampos['mostrar']) || !array_is_list($reglaCampos['mostrar']) || !$reglaCampos['mostrar']) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.mostrar debe ser una lista no vacía de claves.");
                    }
                    foreach ($reglaCampos['mostrar'] as $claveMostrar) {
                        if (!is_string($claveMostrar) || !isset($clavesFicha[$claveMostrar])) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.mostrar incluye \"{$claveMostrar}\", que no es una \"clave\" explícita de esta ficha.");
                        }
                        if (in_array($claveMostrar, $clavesCondicion, true)) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.mostrar incluye \"{$claveMostrar}\", que es parte de su propia condición.");
                        }
                        if (in_array($claveMostrar, $clavesEnTarjetasFijas, true)) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.mostrar incluye \"{$claveMostrar}\", que se pinta en una tarjeta fija (campos_notificacion/campos_persona) sin envoltura condicional.");
                        }
                        if (isset($clavesYaMostradas[$claveMostrar])) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$claveMostrar}\" está en \"mostrar\" de dos reglas: cada campo lo condiciona una sola regla.");
                        }
                        $clavesYaMostradas[$claveMostrar] = true;
                    }
                }
                if (array_key_exists('fijar', $reglaCampos)) {
                    if (!is_array($reglaCampos['fijar']) || array_is_list($reglaCampos['fijar'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.fijar debe ser un objeto {clave: valor}.");
                    }
                    $exigirClavesNumero(array_keys($reglaCampos['fijar']), "{$donde}.fijar", 1);
                    foreach ($reglaCampos['fijar'] as $claveFijar => $valorFijar) {
                        if (!is_string($valorFijar) || !is_numeric($valorFijar)) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.fijar.{$claveFijar} debe ser un número escrito como texto (\"0\").");
                        }
                        if (in_array($claveFijar, $clavesCondicion, true)) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.fijar incluye \"{$claveFijar}\", que es parte de su propia condición.");
                        }
                    }
                }
                if (array_key_exists('suma_maxima', $reglaCampos)) {
                    $sumaMaxima = $reglaCampos['suma_maxima'];
                    if (!is_array($sumaMaxima) || array_diff(array_keys($sumaMaxima), ['claves', 'valor'])
                        || !array_key_exists('valor', $sumaMaxima) || (!is_int($sumaMaxima['valor']) && !is_float($sumaMaxima['valor']))) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.suma_maxima debe ser un objeto con \"claves\" y \"valor\" numérico.");
                    }
                    $exigirClavesNumero($sumaMaxima['claves'] ?? null, "{$donde}.suma_maxima.claves", 2);
                    if (empty($reglaCampos['mensaje']) || !is_string($reglaCampos['mensaje'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.suma_maxima necesita un \"mensaje\" para mostrar cuando se supera.");
                    }
                }
                if (array_key_exists('mensaje', $reglaCampos) && (!is_string($reglaCampos['mensaje']) || trim($reglaCampos['mensaje']) === '')) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.mensaje debe ser un texto no vacío.");
                }

                // Efectos nuevos de P96 (muerte fetal y neonatal, 2026-09-13).
                // Cada uno trae su propio "mensaje" adentro, así una regla puede
                // declarar varios sin que compartan el texto de error.
                $exigirMensajeEfecto = function (array $efecto, string $dondeEfecto) use ($cie10): void {
                    if (empty($efecto['mensaje']) || !is_string($efecto['mensaje']) || trim($efecto['mensaje']) === '') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeEfecto}.mensaje es obligatorio (el texto que ve el usuario cuando no se cumple).");
                    }
                };
                // Fecha referenciable: clave FECHA de esta ficha o una fecha del
                // núcleo ("nucleo:fecha_nac").
                $exigirFecha = function (mixed $referencia, string $dondeFecha) use ($cie10, $clavesFicha, $tiposPorClaveFicha, $clavesCondicion): void {
                    if ($referencia === 'nucleo:fecha_nac') {
                        return;
                    }
                    if (!is_string($referencia) || !isset($clavesFicha[$referencia]) || $tiposPorClaveFicha[$referencia] !== 'FECHA') {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeFecha} debe ser una clave FECHA de esta ficha o \"nucleo:fecha_nac\".");
                    }
                    if (in_array($referencia, $clavesCondicion, true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeFecha} es parte de la condición de su propia regla.");
                    }
                };

                // "opciones": mientras se cumple la condición, el SELECT solo
                // admite esos códigos; con uno solo, queda elegido solo.
                if (array_key_exists('opciones', $reglaCampos)) {
                    if (!is_array($reglaCampos['opciones']) || array_is_list($reglaCampos['opciones']) || !$reglaCampos['opciones']) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.opciones debe ser un objeto {clave: [códigos]}.");
                    }
                    foreach ($reglaCampos['opciones'] as $claveOpciones => $codigosPermitidos) {
                        // MULTISELECT (B24, 2026-09-14): solo se pueden marcar esos códigos.
                        if (!isset($clavesFicha[$claveOpciones]) || !in_array($tiposPorClaveFicha[$claveOpciones], ['SELECT', 'MULTISELECT'], true)) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.opciones incluye \"{$claveOpciones}\", que no es una clave SELECT o MULTISELECT de esta ficha.");
                        }
                        if (in_array($claveOpciones, $clavesCondicion, true)) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.opciones incluye \"{$claveOpciones}\", que es parte de su propia condición.");
                        }
                        if (!is_array($codigosPermitidos) || !array_is_list($codigosPermitidos) || !$codigosPermitidos) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.opciones.{$claveOpciones} debe ser una lista no vacía de códigos.");
                        }
                        foreach ($codigosPermitidos as $codigoPermitido) {
                            if (!is_string($codigoPermitido) || trim($codigoPermitido) === '') {
                                throw new RuntimeException("Manifiesto inválido: {$cie10} / {$donde}.opciones.{$claveOpciones} tiene un código vacío.");
                            }
                        }
                        // Que cada código exista de verdad se verifica al
                        // cargar (procesarFicha()), contra catalogo_item: un
                        // catálogo reutilizado puede traer códigos que no son
                        // el slug de su etiqueta ("Post-parto" -> POSTPARTO).
                    }
                }

                // "comparar_fechas": días entre "desde" y "hasta" dentro de
                // [dias_minimo, dias_maximo]; con "hora_desde"/"hora_hasta" el
                // mínimo se compara con la hora incluida.
                if (array_key_exists('comparar_fechas', $reglaCampos)) {
                    $comparar = $reglaCampos['comparar_fechas'];
                    $dondeComparar = "{$donde}.comparar_fechas";
                    if (!is_array($comparar) || array_is_list($comparar)
                        || array_diff(array_keys($comparar), ['desde', 'hasta', 'hora_desde', 'hora_hasta', 'dias_minimo', 'dias_maximo', 'mensaje'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeComparar} debe ser un objeto con desde, hasta, dias_minimo y/o dias_maximo, hora_desde/hora_hasta (opcionales) y mensaje.");
                    }
                    $exigirFecha($comparar['desde'] ?? null, "{$dondeComparar}.desde");
                    $exigirFecha($comparar['hasta'] ?? null, "{$dondeComparar}.hasta");
                    if ($comparar['desde'] === $comparar['hasta']) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeComparar} compara una fecha consigo misma.");
                    }
                    if (str_starts_with($comparar['desde'], 'nucleo:') && str_starts_with($comparar['hasta'], 'nucleo:')) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeComparar} necesita al menos una fecha de la ficha: ahí se muestra el error.");
                    }
                    if (!array_key_exists('dias_minimo', $comparar) && !array_key_exists('dias_maximo', $comparar)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeComparar} necesita dias_minimo y/o dias_maximo.");
                    }
                    foreach (['dias_minimo', 'dias_maximo'] as $limiteDias) {
                        if (array_key_exists($limiteDias, $comparar) && !is_int($comparar[$limiteDias])) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeComparar}.{$limiteDias} debe ser un entero.");
                        }
                    }
                    if (array_key_exists('hora_desde', $comparar) !== array_key_exists('hora_hasta', $comparar)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeComparar}: hora_desde y hora_hasta van juntas.");
                    }
                    foreach (['hora_desde', 'hora_hasta'] as $claveHora) {
                        if (array_key_exists($claveHora, $comparar) && (($formatoPorClaveFicha[$comparar[$claveHora]] ?? null) !== 'hora')) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeComparar}.{$claveHora} debe ser una clave TEXTO con \"formato\": \"hora\" de esta ficha.");
                        }
                    }
                    $exigirMensajeEfecto($comparar, $dondeComparar);
                }

                // "maximo_dias_entre": un NUMERO de días (estancia hospitalaria)
                // no puede pasar los días entre dos fechas contando ambas.
                if (array_key_exists('maximo_dias_entre', $reglaCampos)) {
                    $maximoDias = $reglaCampos['maximo_dias_entre'];
                    $dondeMaximo = "{$donde}.maximo_dias_entre";
                    if (!is_array($maximoDias) || array_is_list($maximoDias) || array_diff(array_keys($maximoDias), ['clave', 'desde', 'hasta', 'mensaje'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeMaximo} debe ser un objeto con clave, desde, hasta y mensaje.");
                    }
                    $exigirClavesNumero([$maximoDias['clave'] ?? null], "{$dondeMaximo}.clave", 1);
                    $exigirFecha($maximoDias['desde'] ?? null, "{$dondeMaximo}.desde");
                    $exigirFecha($maximoDias['hasta'] ?? null, "{$dondeMaximo}.hasta");
                    $exigirMensajeEfecto($maximoDias, $dondeMaximo);
                }

                // "alguno_minimo": entre los NUMERO con dato, al menos uno llega
                // a su mínimo (muerte fetal: 22 semanas o 500 g).
                // "alguno_menor_que" (A50, 2026-09-14): el espejo, al menos uno
                // queda por debajo de su límite (aborto: menos de 22 semanas o
                // menos de 500 g). Mismo formato.
                foreach (['alguno_minimo', 'alguno_menor_que'] as $efectoAlguno) {
                    if (!array_key_exists($efectoAlguno, $reglaCampos)) {
                        continue;
                    }
                    $algunoMinimo = $reglaCampos[$efectoAlguno];
                    $dondeAlguno = "{$donde}.{$efectoAlguno}";
                    if (!is_array($algunoMinimo) || array_is_list($algunoMinimo) || array_diff(array_keys($algunoMinimo), ['claves', 'mensaje'])
                        || !is_array($algunoMinimo['claves'] ?? null) || array_is_list($algunoMinimo['claves'])) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeAlguno} debe ser un objeto con \"claves\" ({clave: mínimo}) y \"mensaje\".");
                    }
                    $exigirClavesNumero(array_keys($algunoMinimo['claves']), "{$dondeAlguno}.claves", 2);
                    foreach ($algunoMinimo['claves'] as $claveMinimo => $valorMinimo) {
                        if (!is_int($valorMinimo) && !is_float($valorMinimo)) {
                            throw new RuntimeException("Manifiesto inválido: {$cie10} / {$dondeAlguno}.claves.{$claveMinimo} debe ser numérico.");
                        }
                    }
                    $exigirMensajeEfecto($algunoMinimo, $dondeAlguno);
                }
            }
        }

        if (!empty($ficha['unidades_edad'])) {
            foreach ($ficha['unidades_edad'] as $unidad) {
                if (!in_array($unidad, UNIDADES_EDAD_VALIDAS, true)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / unidades_edad incluye \"{$unidad}\", que no es una unidad válida. Válidas: " . implode(', ', UNIDADES_EDAD_VALIDAS) . ".");
                }
            }
            if (count($ficha['unidades_edad']) !== count(array_unique($ficha['unidades_edad']))) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / unidades_edad tiene unidades repetidas.");
            }
        }

        if (!empty($ficha['detalle_domicilio'])) {
            foreach ($ficha['detalle_domicilio'] as $campoDomicilio) {
                if (!in_array($campoDomicilio, DETALLE_DOMICILIO_VALIDO, true)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / detalle_domicilio incluye \"{$campoDomicilio}\", que no es un campo válido. Válidos: " . implode(', ', DETALLE_DOMICILIO_VALIDO) . ".");
                }
            }
            if (count($ficha['detalle_domicilio']) !== count(array_unique($ficha['detalle_domicilio']))) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / detalle_domicilio tiene campos repetidos.");
            }
        }

        if (!empty($ficha['columnas_sujeto'])) {
            foreach ($ficha['columnas_sujeto'] as $rol => $columnas) {
                if (!is_array($columnas) || empty($columnas)) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_sujeto.{$rol} debe ser una lista no vacía de columnas.");
                }
                foreach ($columnas as $col) {
                    if (!in_array($col, COLUMNAS_SUJETO_VALIDAS, true)) {
                        throw new RuntimeException("Manifiesto inválido: {$cie10} / columnas_sujeto.{$rol} incluye \"{$col}\", que no es una columna configurable de caso_sujeto. Válidas: " . implode(', ', COLUMNAS_SUJETO_VALIDAS) . ".");
                    }
                }
            }
        }

        if (!empty($ficha['titulo_sujeto'])) {
            foreach ($ficha['titulo_sujeto'] as $rol => $titulo) {
                if (!is_string($titulo) || trim($titulo) === '') {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / titulo_sujeto.{$rol} debe ser un texto no vacío.");
                }
                if (empty($ficha['columnas_sujeto'][$rol])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} / titulo_sujeto declara el rol \"{$rol}\", que no tiene columnas_sujeto -- un título sin bloque no tiene dónde mostrarse.");
                }
            }
        }

        // Clave explícita: si un campo trae "clave" en el manifiesto, es
        // autoritativa (insertarCampo() ya no la deriva de la etiqueta para
        // ese campo -- ver claveCampo()). Antes "clave" no lo leía nadie: la
        // etiqueta podía reescribirse para cotejar contra el PDF MINSA y la
        // clave real en campo_def cambiaba con ella en la siguiente recarga,
        // sin que el string literal ya escrito en las vistas se enterara
        // (auditoría de O95, 62/141 claves desincronizadas). Acá solo se
        // valida que dos campos de la misma ficha no declaren la misma
        // clave explícita -- la colisión real que ya existía en O95
        // ("categoria_del_ee_ss" en dos secciones) y en A80/A33/Y07/Z21.
        $clavesExplicitasFicha = [];
        foreach ($ficha['secciones'] as $seccion) {
            foreach ($seccion['campos'] as $campo) {
                $claveExplicita = trim((string) ($campo['clave'] ?? ''));
                if ($claveExplicita === '') {
                    continue;
                }
                if (isset($clavesExplicitasFicha[$claveExplicita])) {
                    throw new RuntimeException("Manifiesto inválido: {$cie10} tiene dos campos con la misma \"clave\" explícita: \"{$claveExplicita}\" (\"{$clavesExplicitasFicha[$claveExplicita]}\" y \"{$campo['etiqueta']}\").");
                }
                $clavesExplicitasFicha[$claveExplicita] = $campo['etiqueta'];
            }
        }
    }
}

/**
 * Resuelve (reutilizando si es posible) el catalogo_id para una lista de
 * opciones. $cache está indexada por claveOpciones() -> catalogo_id y se
 * precarga con los catálogos ya existentes en la BD antes de procesar
 * cualquier ficha, así que "reutilizar" incluye tanto catálogos creados en
 * corridas anteriores como los creados más temprano en esta misma corrida.
 */
function resolverCatalogo(PDO $pdo, array $opciones, string $cie10, string $nombreSugerido, array &$cache, array &$nombresUsados, array &$reporte): int
{
    $clave = claveOpciones($opciones);
    if (isset($cache[$clave])) {
        $reporte['catalogos_reutilizados'][] = ['nombre' => $cache[$clave]['nombre'], 'opciones' => $opciones];
        return $cache[$clave]['id'];
    }

    $compartido = esCatalogoCompartido($opciones);
    $nombreBase = $compartido
        ? 'Compartido: ' . implode('/', $opciones)
        : "{$cie10} - {$nombreSugerido}";
    $nombreBase = mb_substr($nombreBase, 0, 76);
    $nombre = $nombreBase;
    $n = 2;
    while (isset($nombresUsados[$nombre])) {
        $nombre = mb_substr($nombreBase, 0, 74) . " ({$n})";
        $n++;
    }
    $nombresUsados[$nombre] = true;

    $stmt = $pdo->prepare('INSERT INTO catalogo (nombre) VALUES (?)');
    $stmt->execute([$nombre]);
    $catalogoId = (int) $pdo->lastInsertId();

    $stmtItem = $pdo->prepare('INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES (?,?,?,?)');
    $orden = 1;
    foreach ($opciones as $opcion) {
        $valor = mb_strtoupper(slug($opcion));
        $stmtItem->execute([$catalogoId, mb_substr($valor, 0, 60), mb_substr($opcion, 0, 120), $orden]);
        $orden++;
    }

    $cache[$clave] = ['id' => $catalogoId, 'nombre' => $nombre];
    $reporte['catalogos_creados'][] = ['nombre' => $nombre, 'opciones' => $opciones];
    return $catalogoId;
}

function precargarCatalogos(PDO $pdo, array &$nombresUsados): array
{
    $cache = [];
    $catalogos = $pdo->query('SELECT id, nombre FROM catalogo')->fetchAll();
    $items = $pdo->query('SELECT catalogo_id, etiqueta, orden FROM catalogo_item ORDER BY catalogo_id, orden')->fetchAll();
    $itemsPorCatalogo = [];
    foreach ($items as $it) {
        $itemsPorCatalogo[$it['catalogo_id']][] = $it['etiqueta'];
    }
    foreach ($catalogos as $cat) {
        $nombresUsados[$cat['nombre']] = true;
        $opciones = $itemsPorCatalogo[$cat['id']] ?? [];
        if (!$opciones) {
            continue;
        }
        $clave = claveOpciones($opciones);
        // Si dos catálogos existentes tuvieran el mismo contenido, se
        // conserva el primero encontrado (más antiguo = probablemente el
        // "canónico"); no se fusionan acá, solo se elige cuál reutilizar de
        // ahora en adelante.
        if (!isset($cache[$clave])) {
            $cache[$clave] = ['id' => (int) $cat['id'], 'nombre' => $cat['nombre']];
        }
    }
    return $cache;
}

/**
 * Inserta un campo_def (y su catálogo si aplica) dentro de la sección dada.
 * Devuelve el id insertado (lo necesita procesarFicha() para resolver
 * "depende_de" en una segunda pasada, una vez que todos los campos de la
 * ficha ya tienen id).
 */
function insertarCampo(PDO $pdo, int $seccionId, string $cie10, array $campo, int $orden, string $rolSujeto, array &$clavesUsadas, array &$catalogCache, array &$nombresCatalogo, array &$reporte): int
{
    $tipo = $campo['tipo'];
    $etiqueta = $campo['etiqueta'];
    $claveExplicita = trim((string) ($campo['clave'] ?? ''));
    if ($claveExplicita !== '') {
        // Autoritativa: validarManifiesto() ya garantizó que no colisiona
        // con otra clave explícita de la misma ficha; esto además cubre que
        // no colisione con una clave derivada de otro campo sin "clave"
        // propia, en el orden que sea que se procesen.
        if (isset($clavesUsadas[$claveExplicita])) {
            throw new RuntimeException("Manifiesto inválido: {$cie10} / \"{$etiqueta}\" declara \"clave\": \"{$claveExplicita}\", que ya está en uso por otro campo de la misma ficha.");
        }
        $clave = mb_substr($claveExplicita, 0, 60);
        $clavesUsadas[$clave] = true;
    } else {
        $clave = claveCampo($cie10, $etiqueta, $clavesUsadas);
    }
    $sensible = !empty($campo['sensible']) ? 1 : 0;

    $catalogoId = null;
    $config = null;

    if (in_array($tipo, TIPOS_CON_OPCIONES, true)) {
        $catalogoId = resolverCatalogo($pdo, $campo['opciones'], $cie10, $etiqueta, $catalogCache, $nombresCatalogo, $reporte);
    }

    if ($tipo === 'MATRIZ') {
        $filas = $campo['filas'] ?? null;
        $config = json_encode([
            'columnas' => $campo['columnas'],
            'filas' => is_array($filas) ? $filas : null,
            'filas_nota' => is_string($filas) ? $filas : null,
            // "sin_gate_libres": true (opt-in, 2026-08-19, A44 "Lesiones
            // eruptivas") desactiva el gate por columna SI/No-realizado
            // (matriz.php:$gateSi/$gateNegativo) sobre las columnas LIBRES
            // de la fila -- pensado para cuando la columna radio es una
            // pregunta Sí/No independiente de las demás columnas (ej.
            // "Sangrante" no condiciona si tiene sentido contar lesiones
            // por localización), a diferencia de P35.0/A97 donde SÍ hay una
            // relación real ("fecha de manifestación" solo si esa fila
            // ocurrió). Ver memoria matriz_no_soporta_booleanos_independientes_por_fila.
            'sin_gate_libres' => !empty($campo['sin_gate_libres']),
            // "grupos_columnas": {"Sangrante": ["SI","NO"]} (opt-in,
            // 2026-08-19, mismo campo que sin_gate_libres arriba) fusiona
            // 2+ columnas radio-elegibles en una sola columna visual con un
            // único .seg (radios pegados) en vez de una columna de tabla
            // por opción -- ver matriz.php.
            'grupos_columnas' => $campo['grupos_columnas'] ?? [],
        ] + (
            // "opciones_por_fila" (cotejo Z21, 2026-09-11): solo se serializa
            // cuando la ficha lo declara, así el config JSON de las matrices
            // que ya existían no cambia ni un byte.
            array_key_exists('opciones_por_fila', $campo)
                ? ['opciones_por_fila' => $campo['opciones_por_fila']]
                : []
        ) + (
            // "columnas_condicionadas" (A50, 2026-09-14): mismo criterio.
            array_key_exists('columnas_condicionadas', $campo)
                ? ['columnas_condicionadas' => $campo['columnas_condicionadas']]
                : []
        ), JSON_UNESCAPED_UNICODE);
    }

    // NUMERO: por defecto entero (bloquea e/E/./,/+/- en el cliente, mismo
    // mecanismo que ya usaban a mano los campos .solo-enteros de O95);
    // "decimales": true es el opt-in explícito para los ~8 campos que sí
    // lo necesitan (temperaturas, peso en kg, hemoglobina/hematocrito,
    // porcentajes). No se serializa nada si no se declara -- numero.php
    // trata "sin config" igual que "config sin decimales".
    // "minimo" (Z21, 2026-09-13) va en el mismo config: numero.php lo pinta
    // como min= y validarCamposDinamicos() lo exige.
    if ($tipo === 'NUMERO' && (array_key_exists('decimales', $campo) || array_key_exists('minimo', $campo))) {
        $config = json_encode(
            array_intersect_key($campo, ['decimales' => true, 'minimo' => true]),
            JSON_UNESCAPED_UNICODE
        );
    }

    // SI_NO_FECHA: por defecto solo Sí/No + fecha (si Sí). "especificar":
    // true es el opt-in explícito para los "Otros" que además necesitan un
    // campo de texto libre -- se renderiza junto a la fecha, condicionado
    // al mismo Sí (campos/si-no-fecha.php), no como un campo_def aparte
    // siempre visible (ver A37.0, corregido 2026-08-06 tras detectar que
    // el TEXTO suelto no debía depender de nada).
    if ($tipo === 'SI_NO_FECHA' && array_key_exists('especificar', $campo)) {
        $config = json_encode(['especificar' => $campo['especificar']], JSON_UNESCAPED_UNICODE);
    }

    // SI_NO (campos/si-no.php): por defecto solo Sí/No. "ignorado": true
    // agrega una 3.ª opción (B55 b55_existen_otras_personas_con_lesiones_similares)
    // -- el partial ya lo soportaba pero este cargador nunca lo leía del
    // manifiesto (config quedaba siempre NULL), corregido acá.
    if ($tipo === 'SI_NO' && array_key_exists('ignorado', $campo)) {
        $config = json_encode(['ignorado' => $campo['ignorado']], JSON_UNESCAPED_UNICODE);
    }

    // TEXTO "formato" (P96, 2026-09-13): "hora" (campos/texto.php pinta
    // <input type="time">) o "cie10" (forma de código CIE-10). Solo se
    // serializa si se declara.
    if ($tipo === 'TEXTO' && array_key_exists('formato', $campo)) {
        $config = json_encode(['formato' => $campo['formato']], JSON_UNESCAPED_UNICODE);
    }

    // "obligatorio" (cotejo Z21, 2026-09-11): hasta acá siempre se insertaba
    // 0 y las 89 declaraciones que ya traía el manifiesto no las leía nadie;
    // como todas son `false`, empezar a respetarlas no cambia ninguna ficha
    // existente. Lo estrena "Tipo de registro" de Z21, que decide qué mitad
    // de la ficha se llena y no puede quedar sin responder.
    $obligatorio = !empty($campo['obligatorio']) ? 1 : 0;

    // "grupo" (Z21, 2026-09-13): vale para cualquier tipo, así que se agrega
    // al config que haya armado su tipo en vez de pisarlo. Solo se serializa
    // si se declara: el config de los demás campos no cambia.
    if (array_key_exists('grupo', $campo)) {
        $config = json_encode(
            (json_decode((string) $config, true) ?: []) + ['grupo' => $campo['grupo']],
            JSON_UNESCAPED_UNICODE
        );
    }

    // "desconocido" (A50, 2026-09-14): mismo criterio que "grupo", se suma al
    // config de su tipo (un NUMERO puede traer además "minimo").
    if (array_key_exists('desconocido', $campo)) {
        $config = json_encode(
            (json_decode((string) $config, true) ?: []) + ['desconocido' => true],
            JSON_UNESCAPED_UNICODE
        );
    }

    // "calculado" (B24, 2026-09-14): mismo criterio, se suma al config.
    if (array_key_exists('calculado', $campo)) {
        $config = json_encode(
            (json_decode((string) $config, true) ?: []) + ['calculado' => $campo['calculado']],
            JSON_UNESCAPED_UNICODE
        );
    }

    $stmt = $pdo->prepare(
        'INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, rol_sujeto, sensible, catalogo_id, config, origen, orden)
         VALUES (?,?,?,?,?,?,?,?,?,\'FICHA_MINSA\',?)'
    );
    $stmt->execute([$seccionId, $clave, $etiqueta, $tipo, $obligatorio, $rolSujeto, $sensible, $catalogoId, $config, $orden]);
    $campoId = (int) $pdo->lastInsertId();

    $reporte['campos_creados'][] = ['clave' => $clave, 'etiqueta' => $etiqueta, 'tipo' => $tipo];
    return $campoId;
}

/**
 * Procesa una ficha completa: verifica protección de datos, borra sus
 * secciones actuales y vuelve a insertar desde el manifiesto. Asume que el
 * llamador ya abrió la transacción correspondiente (ver main(): en modo
 * --apply es una transacción por ficha; en dry-run es una sola transacción
 * para todo el lote, así los catálogos creados por una ficha siguen
 * visibles para las siguientes dentro del mismo dry-run, igual que
 * quedarían visibles de verdad con --apply).
 */
function procesarFicha(PDO $pdo, string $cie10, array $fichaManifiesto, int $enfermedadId, array &$catalogCache, array &$nombresCatalogo, bool $forzarProtegida): array
{
    $reporte = [
        'cie10' => $cie10,
        'enfermedad' => $fichaManifiesto['enfermedad'],
        'enfermedad_id' => $enfermedadId,
        'bloqueada' => false,
        'motivo_bloqueo' => null,
        'secciones_borradas' => 0,
        'campos_borrados' => 0,
        'secciones_creadas' => [],
        'campos_creados' => [],
        'catalogos_creados' => [],
        'catalogos_reutilizados' => [],
    ];

    // Configuración de columnas de tabla hija (PENDIENTES_POST_FASE5.md
    // punto 3): no toca campo_def/seccion_def, así que se aplica siempre,
    // incluso si la ficha termina bloqueada por caso_valor más abajo. El
    // manifiesto es la fuente de verdad: si una ficha no declara
    // "columnas_tablas_hija" para una tabla, se deja NULL explícitamente
    // (el widget cae al mínimo por defecto), no se conserva lo que hubiera
    // quedado de una corrida anterior.
    $columnasDeclaradas = $fichaManifiesto['columnas_tablas_hija'] ?? [];
    $tablasHijas = $fichaManifiesto['tablas_hijas'] ?? [];
    // nucleo_omitidos (Petición 2, sesión "núcleo declarativo"): mismo
    // criterio que columnas_tablas_hija -- no toca campo_def/seccion_def, se
    // aplica siempre aunque la ficha esté bloqueada más abajo, y si el
    // manifiesto no la declara se deja NULL explícito (no se conserva un
    // valor de una corrida anterior).
    $nucleoOmitidosDeclarados = $fichaManifiesto['nucleo_omitidos'] ?? null;
    // nucleo_incluidos: mismo criterio que nucleo_omitidos, polaridad
    // invertida (ver PETICION_HC_Y_LABORATORIO.md, Parte 1).
    $nucleoIncluidosDeclarados = $fichaManifiesto['nucleo_incluidos'] ?? null;
    // columnas_sujeto/titulo_sujeto (PETICION_P35_RUBEOLA_CONGENITA.md Fase
    // 2b): mismo criterio exacto que nucleo_omitidos -- objeto por rol, se
    // aplica siempre, NULL explícito si la ficha no declara nada.
    $columnasSujetoDeclaradas = $fichaManifiesto['columnas_sujeto'] ?? null;
    $tituloSujetoDeclarado = $fichaManifiesto['titulo_sujeto'] ?? null;
    // unidades_edad (entrada F, PETICION_MAPEO_Y_EDAD.md Parte 2): mismo
    // criterio que nucleo_omitidos -- se aplica siempre, NULL explícito si
    // la ficha no lo declara (opt-in: NULL equivale al comportamiento actual).
    $unidadesEdadDeclaradas = $fichaManifiesto['unidades_edad'] ?? null;
    // detalle_domicilio (Entrada J acotada al bloque de domicilio): mismo
    // criterio que unidades_edad -- se aplica siempre, NULL explícito si la
    // ficha no lo declara (opt-in: NULL equivale al comportamiento actual).
    $detalleDomicilioDeclarado = $fichaManifiesto['detalle_domicilio'] ?? null;
    // bloques_condicionales (capacidad 6): mismo criterio -- opt-in, NULL
    // explícito si la ficha no declara ninguno.
    $bloquesCondicionalesDeclarados = $fichaManifiesto['bloques_condicionales'] ?? null;
    // migracion_reciente (cotejo B57, sección "IV. Migración"): booleano
    // simple, mismo criterio que tablas_hijas -- se aplica siempre, 0 si la
    // ficha no lo declara.
    $migracionReciente = $fichaManifiesto['migracion_reciente'] ?? false;
    // campos_notificacion / vinculo_caso / nucleo_condicional (cotejo Z21,
    // 2026-09-11): mismo criterio que nucleo_omitidos -- se aplican siempre y
    // quedan en NULL explícito si la ficha no los declara, para que quitar
    // una declaración del manifiesto la borre de verdad.
    $camposNotificacionDeclarados = $fichaManifiesto['campos_notificacion'] ?? null;
    $camposPersonaDeclarados = $fichaManifiesto['campos_persona'] ?? null;
    $vinculoCasoDeclarado = $fichaManifiesto['vinculo_caso'] ?? null;
    $nucleoCondicionalDeclarado = $fichaManifiesto['nucleo_condicional'] ?? null;
    $reglasCamposDeclaradas = $fichaManifiesto['reglas_campos'] ?? null;
    // nucleo_ajustes (P96, 2026-09-13): mismo criterio. Las notas "_..." no
    // se persisten.
    $nucleoAjustesDeclarados = array_filter(
        (array) ($fichaManifiesto['nucleo_ajustes'] ?? []),
        fn($ajuste) => !str_starts_with((string) $ajuste, '_'),
        ARRAY_FILTER_USE_KEY
    );
    $pdo->prepare('UPDATE enfermedad SET columnas_contacto = ?, columnas_muestra = ?, columnas_viaje = ?, columnas_vacuna = ?, usa_contactos = ?, usa_muestras = ?, usa_viajes = ?, usa_vacunas = ?, nucleo_omitidos = ?, nucleo_incluidos = ?, columnas_sujeto = ?, titulo_sujeto = ?, unidades_edad = ?, detalle_domicilio = ?, bloques_condicionales = ?, migracion_reciente = ?, campos_notificacion = ?, campos_persona = ?, vinculo_caso = ?, nucleo_condicional = ?, reglas_campos = ?, nucleo_ajustes = ? WHERE id = ?')->execute([
        isset($columnasDeclaradas['caso_contacto']) ? json_encode($columnasDeclaradas['caso_contacto'], JSON_UNESCAPED_UNICODE) : null,
        isset($columnasDeclaradas['caso_muestra']) ? json_encode($columnasDeclaradas['caso_muestra'], JSON_UNESCAPED_UNICODE) : null,
        isset($columnasDeclaradas['caso_viaje']) ? json_encode($columnasDeclaradas['caso_viaje'], JSON_UNESCAPED_UNICODE) : null,
        isset($columnasDeclaradas['caso_vacuna']) ? json_encode($columnasDeclaradas['caso_vacuna'], JSON_UNESCAPED_UNICODE) : null,
        !empty($tablasHijas['caso_contacto']) ? 1 : 0,
        !empty($tablasHijas['caso_muestra']) ? 1 : 0,
        !empty($tablasHijas['caso_viaje']) ? 1 : 0,
        !empty($tablasHijas['caso_vacuna']) ? 1 : 0,
        !empty($nucleoOmitidosDeclarados) ? json_encode($nucleoOmitidosDeclarados, JSON_UNESCAPED_UNICODE) : null,
        !empty($nucleoIncluidosDeclarados) ? json_encode($nucleoIncluidosDeclarados, JSON_UNESCAPED_UNICODE) : null,
        !empty($columnasSujetoDeclaradas) ? json_encode($columnasSujetoDeclaradas, JSON_UNESCAPED_UNICODE) : null,
        !empty($tituloSujetoDeclarado) ? json_encode($tituloSujetoDeclarado, JSON_UNESCAPED_UNICODE) : null,
        !empty($unidadesEdadDeclaradas) ? json_encode($unidadesEdadDeclaradas, JSON_UNESCAPED_UNICODE) : null,
        !empty($detalleDomicilioDeclarado) ? json_encode($detalleDomicilioDeclarado, JSON_UNESCAPED_UNICODE) : null,
        !empty($bloquesCondicionalesDeclarados) ? json_encode($bloquesCondicionalesDeclarados, JSON_UNESCAPED_UNICODE) : null,
        $migracionReciente ? 1 : 0,
        !empty($camposNotificacionDeclarados) ? json_encode($camposNotificacionDeclarados, JSON_UNESCAPED_UNICODE) : null,
        !empty($camposPersonaDeclarados) ? json_encode($camposPersonaDeclarados, JSON_UNESCAPED_UNICODE) : null,
        !empty($vinculoCasoDeclarado) ? json_encode($vinculoCasoDeclarado, JSON_UNESCAPED_UNICODE) : null,
        !empty($nucleoCondicionalDeclarado) ? json_encode($nucleoCondicionalDeclarado, JSON_UNESCAPED_UNICODE) : null,
        !empty($reglasCamposDeclaradas) ? json_encode($reglasCamposDeclaradas, JSON_UNESCAPED_UNICODE) : null,
        $nucleoAjustesDeclarados ? json_encode($nucleoAjustesDeclarados, JSON_UNESCAPED_UNICODE) : null,
        $enfermedadId,
    ]);

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM caso_valor cv
         JOIN campo_def cd ON cv.campo_def_id = cd.id
         JOIN seccion_def sd ON cd.seccion_id = sd.id
         WHERE sd.enfermedad_id = ?'
    );
    $stmt->execute([$enfermedadId]);
    $numValores = (int) $stmt->fetchColumn();

    if ($numValores > 0 && !$forzarProtegida) {
        $reporte['bloqueada'] = true;
        $reporte['motivo_bloqueo'] = "Hay {$numValores} caso_valor capturado(s) apuntando a campo_def de esta enfermedad. No se borra sin --confirmar-perdida={$cie10}.";
        return $reporte;
    }

    $stmt = $pdo->prepare('SELECT id FROM seccion_def WHERE enfermedad_id = ?');
    $stmt->execute([$enfermedadId]);
    $seccionesViejas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $reporte['secciones_borradas'] = count($seccionesViejas);

    if ($seccionesViejas) {
        $in = implode(',', array_fill(0, count($seccionesViejas), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM campo_def WHERE seccion_id IN ({$in})");
        $stmt->execute($seccionesViejas);
        $reporte['campos_borrados'] = (int) $stmt->fetchColumn();

        // campo_def.depende_de es una FK autorreferencial sin ON DELETE
        // CASCADE: si un campo con dependientes (p.ej. "especificar") se
        // borra antes que ellos, el DELETE en cascada de seccion_def choca
        // contra esa FK. Se rompen esas referencias primero.
        $pdo->prepare("UPDATE campo_def SET depende_de = NULL WHERE seccion_id IN ({$in})")->execute($seccionesViejas);

        // Mismo problema, pero con seccion_def.depende_de -> campo_def.id
        // (CIERRE_RECARGA_Y_FASE5.md Parte 1.5): si la sección disparadora
        // se borra en cascada antes que la sección dependiente, el DELETE de
        // seccion_def choca contra esa FK. Se rompe primero también.
        $pdo->prepare('UPDATE seccion_def SET depende_de = NULL WHERE enfermedad_id = ?')->execute([$enfermedadId]);
    }

    $pdo->prepare('DELETE FROM seccion_def WHERE enfermedad_id = ?')->execute([$enfermedadId]);

    $ordenSeccion = 1;
    $idPorEtiqueta = [];
    // Índice paralelo por CLAVE (cotejo Z21, 2026-09-11): "depende_de" puede
    // nombrar al campo padre por clave, que es única por ficha, en vez de por
    // etiqueta, que puede repetirse entre ramas de la misma ficha.
    $idPorClave = [];
    $pendientesDependencia = []; // [campoId => ['depende_de' => clave|etiqueta, 'valor_activador' => valor]]
    $pendientesDependenciaSeccion = []; // [seccionId => ['depende_de' => clave|etiqueta, 'valor_activador' => valor]]
    foreach ($fichaManifiesto['secciones'] as $seccion) {
        // "solo_tabla_hija" (2026-08-19, A44 "Laboratorio y evolución"):
        // opt-in para una sección sin NINGÚN campo_def cuyo contenido vive
        // 100% en una tabla hija hardcodeada en secciones-clinicas.php (ver
        // tablas-hijas/evolucion.php) -- sin esta excepción, la sección se
        // saltaba entera (sin seccion_def no hay card, y sin card el bloque
        // de tabla hija que depende de `trim($seccion['nombre']) === '...'`
        // nunca se alcanzaba). Sin el flag, una sección sin campos sigue
        // tratándose como informativa/pendiente y no genera seccion_def,
        // mismo comportamiento de siempre para las demás fichas.
        if (empty($seccion['campos']) && empty($seccion['solo_tabla_hija'])) {
            continue; // seccion informativa (contenido vive en tabla hija o queda pendiente): no genera seccion_def
        }
        // Orden explícito (Fase 6): si la sección lo trae, se usa tal cual
        // -- validarManifiesto() ya garantizó que es todo o nada por ficha,
        // así que este fallback a la posición del array solo se activa
        // cuando NINGUNA sección de esta ficha trae "orden".
        $ordenSeccionReal = $seccion['orden'] ?? $ordenSeccion;
        $stmt = $pdo->prepare('INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (?,?,?)');
        $stmt->execute([$enfermedadId, $seccion['nombre'], $ordenSeccionReal]);
        $seccionId = (int) $pdo->lastInsertId();
        $reporte['secciones_creadas'][] = $seccion['nombre'];
        if (!empty($seccion['depende_de'])) {
            $pendientesDependenciaSeccion[$seccionId] = [
                'depende_de' => $seccion['depende_de'],
                'valor_activador' => $seccion['valor_activador'],
            ];
        }

        $rolSujeto = $seccion['rol_sujeto'] ?? 'CASO_INDICE';
        $clavesUsadas = [];
        $ordenCampo = 1;
        foreach ($seccion['campos'] as $campo) {
            $ordenCampoReal = $campo['orden'] ?? $ordenCampo;
            $campoId = insertarCampo($pdo, $seccionId, $cie10, $campo, $ordenCampoReal, $rolSujeto, $clavesUsadas, $catalogCache, $nombresCatalogo, $reporte);
            $idPorEtiqueta[$campo['etiqueta']] = $campoId;
            $claveDelCampo = trim((string) ($campo['clave'] ?? ''));
            if ($claveDelCampo !== '') {
                $idPorClave[$claveDelCampo] = $campoId;
            }
            if (!empty($campo['depende_de'])) {
                $pendientesDependencia[$campoId] = [
                    'depende_de' => $campo['depende_de'],
                    'valor_activador' => $campo['valor_activador'],
                ];
            }
            $ordenCampo++;
        }
        $ordenSeccion++;
    }

    // Segunda pasada: recién ahora existen los id de TODOS los campos de la
    // ficha, así que se puede resolver "depende_de" (validarManifiesto() ya
    // garantizó que la referencia existe en esta misma ficha). La clave manda
    // sobre la etiqueta: es única por ficha (cotejo Z21, 2026-09-11).
    $resolverPadre = function (string $referencia) use ($idPorClave, $idPorEtiqueta): int {
        return $idPorClave[$referencia] ?? $idPorEtiqueta[$referencia];
    };

    if ($pendientesDependencia) {
        $stmtDep = $pdo->prepare('UPDATE campo_def SET depende_de = ?, valor_activador = ? WHERE id = ?');
        foreach ($pendientesDependencia as $campoId => $dep) {
            $padreId = $resolverPadre((string) $dep['depende_de']);
            $stmtDep->execute([$padreId, $dep['valor_activador'], $campoId]);
        }
    }

    if ($pendientesDependenciaSeccion) {
        $stmtDepSeccion = $pdo->prepare('UPDATE seccion_def SET depende_de = ?, valor_activador = ? WHERE id = ?');
        foreach ($pendientesDependenciaSeccion as $seccionId => $dep) {
            $padreId = $resolverPadre((string) $dep['depende_de']);
            $stmtDepSeccion->execute([$padreId, $dep['valor_activador'], $seccionId]);
        }
    }

    // reglas_campos "opciones" (P96, 2026-09-13): con los campos ya
    // insertados, cada código declarado debe existir en el catálogo real del
    // SELECT. Falla dura dentro de la transacción de la ficha (se revierte).
    $stmtCodigos = $pdo->prepare('SELECT ci.valor FROM campo_def cd JOIN catalogo_item ci ON ci.catalogo_id = cd.catalogo_id WHERE cd.id = ? ORDER BY ci.orden');
    foreach (($fichaManifiesto['reglas_campos'] ?? []) as $indiceRegla => $reglaCargada) {
        foreach (($reglaCargada['opciones'] ?? []) as $claveOpciones => $codigosPermitidos) {
            $stmtCodigos->execute([$idPorClave[$claveOpciones]]);
            $codigosReales = $stmtCodigos->fetchAll(PDO::FETCH_COLUMN);
            $codigosInexistentes = array_diff($codigosPermitidos, $codigosReales);
            if ($codigosInexistentes) {
                throw new RuntimeException("Manifiesto inválido: {$cie10} / reglas_campos[{$indiceRegla}].opciones.{$claveOpciones} incluye \"" . implode('", "', $codigosInexistentes) . '", que no está en su catálogo. Códigos reales: ' . implode(', ', $codigosReales) . '.');
            }
        }
    }

    return $reporte;
}

// ============================================================================
// Main
// ============================================================================

// 1) Validar TODO el manifiesto antes de tocar la BD (falla dura, requisito 2).
validarManifiesto($manifiesto);

$pdo = Database::conexion();

$enfermedades = $pdo->query('SELECT id, cie10, nombre FROM enfermedad')->fetchAll();
$enfermedadPorCie10 = [];
foreach ($enfermedades as $e) {
    if ($e['cie10']) {
        $enfermedadPorCie10[$e['cie10']] = $e;
    }
}

$nombresCatalogo = [];
$catalogCache = precargarCatalogos($pdo, $nombresCatalogo);

$reportes = [];
$sinEnfermedad = [];

if ($aplicar) {
    // Una transacción POR FICHA: si una falla, no arrastra a las demás y
    // lo ya aplicado antes se mantiene (requisito 1 de RECARGA_FICHAS.md).
    foreach ($manifiesto['fichas'] as $cie10 => $fichaManifiesto) {
        if ($soloEstas !== null && !in_array($cie10, $soloEstas, true)) {
            continue;
        }
        if (!isset($enfermedadPorCie10[$cie10])) {
            $sinEnfermedad[] = $cie10;
            continue;
        }
        $forzar = in_array($cie10, $forzarPerdida, true);
        $enfermedadId = (int) $enfermedadPorCie10[$cie10]['id'];

        $pdo->beginTransaction();
        try {
            $reporte = procesarFicha($pdo, $cie10, $fichaManifiesto, $enfermedadId, $catalogCache, $nombresCatalogo, $forzar);
            if ($reporte['bloqueada']) {
                $pdo->rollBack();
            } else {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $reportes[] = $reporte;
    }
} else {
    // Dry-run: UNA sola transacción para todo el lote, revertida al final.
    // Así los catálogos que "crearía" una ficha siguen visibles para las
    // siguientes dentro de la misma corrida (igual que pasaría de verdad
    // con --apply, donde cada commit persiste antes de procesar la
    // siguiente ficha) — y nada queda escrito al terminar.
    $pdo->beginTransaction();
    try {
        foreach ($manifiesto['fichas'] as $cie10 => $fichaManifiesto) {
            if ($soloEstas !== null && !in_array($cie10, $soloEstas, true)) {
                continue;
            }
            if (!isset($enfermedadPorCie10[$cie10])) {
                $sinEnfermedad[] = $cie10;
                continue;
            }
            $forzar = in_array($cie10, $forzarPerdida, true);
            $reportes[] = procesarFicha(
                $pdo,
                $cie10,
                $fichaManifiesto,
                (int) $enfermedadPorCie10[$cie10]['id'],
                $catalogCache,
                $nombresCatalogo,
                $forzar
            );
        }
    } finally {
        $pdo->rollBack();
    }
}

// ============================================================================
// Salida
// ============================================================================
if ($modoJson) {
    echo json_encode([
        'modo' => $aplicar ? 'APLICADO' : 'DRY_RUN',
        'fichas' => $reportes,
        'sin_enfermedad_en_bd' => $sinEnfermedad,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n";
    exit(0);
}

$modoTexto = $aplicar ? 'APLICADO (se hizo COMMIT de cada ficha no bloqueada)' : 'DRY-RUN (cada ficha se procesó dentro de una transacción y se hizo ROLLBACK — nada quedó escrito)';
echo "# cargar_fichas.php — {$modoTexto}\n\n";

$bloqueadas = array_filter($reportes, fn($r) => $r['bloqueada']);
$procesadas = array_filter($reportes, fn($r) => !$r['bloqueada']);

printf("Fichas procesadas: %d · bloqueadas por datos capturados: %d · sin enfermedad en BD: %d\n\n", count($procesadas), count($bloqueadas), count($sinEnfermedad));

if ($sinEnfermedad) {
    echo "## Sin enfermedad en la BD (no se tocaron)\n";
    foreach ($sinEnfermedad as $c) {
        echo "- {$c}\n";
    }
    echo "\n";
}

if ($bloqueadas) {
    echo "## Bloqueadas por datos capturados (no se tocaron)\n\n";
    foreach ($bloqueadas as $r) {
        echo "- **{$r['enfermedad']}** (`{$r['cie10']}`): {$r['motivo_bloqueo']}\n";
    }
    echo "\n";
}

echo "## Plan por ficha\n\n";
foreach ($procesadas as $r) {
    $catNuevos = count($r['catalogos_creados']);
    $catReusados = count($r['catalogos_reutilizados']);
    printf(
        "### %s (`%s`)\n- Secciones: borra %d, crea %d\n- Campos: borra %d, crea %d\n- Catálogos: crea %d, reutiliza %d\n\n",
        $r['enfermedad'],
        $r['cie10'],
        $r['secciones_borradas'],
        count($r['secciones_creadas']),
        $r['campos_borrados'],
        count($r['campos_creados']),
        $catNuevos,
        $catReusados
    );
}

if (!$aplicar) {
    echo "---\n\nEsto fue un dry-run: no se escribió nada en la base. Para aplicar de verdad:\n";
    echo "  php cargar_fichas.php --apply\n";
    echo "o, para una sola ficha:\n";
    echo "  php cargar_fichas.php --apply --cie10=A36\n";
}
