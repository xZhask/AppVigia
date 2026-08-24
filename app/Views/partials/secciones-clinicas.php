<?php
/**
 * Renderiza las secciones dinámicas (seccion_def + campo_def) de una enfermedad.
 * Es la ÚNICA plantilla que dibuja estas secciones: la usan tanto la vista
 * completa de "Nueva ficha" como el endpoint AJAX que las recarga al cambiar
 * de enfermedad, para que ambas queden siempre idénticas.
 *
 * Variables esperadas:
 *   $enfermedad          fila de enfermedad
 *   $numeroSeccionInicial int, número de sección con el que empezar a contar
 *   $valoresCampos        [campo_def_id => valor] (valor es array para MULTISELECT)
 *   $erroresCampos        [campo_def_id => mensaje]
 *   $fechaInicioSintomas  string dd/mm/aaaa (campo fijo de caso.fecha_inicio_sintomas,
 *                          siempre existe sin importar la enfermedad)
 *   $errorFechaInicioSintomas ?string
 */

use App\Models\CampoDef;
use App\Models\CatalogoItem;
use App\Models\SeccionDef;

$secciones = SeccionDef::porEnfermedad((int) $enfermedad['id']);

// Ruta 2 (PENDIENTES.md ítems N/N.2, sesión 2026-08-02): esto excluía la
// SECCIÓN entera por nombre para B05/B26/O95/P35.0 -- si un campo nuevo se
// agregaba a una de estas secciones sin actualizar también el partial a
// medida que la duplica en la tarjeta "1. Notificación" (o en su propia
// tarjeta, para B26), quedaba inalcanzable sin ningún aviso (bug real y
// confirmado: p35_0_n_de_historia_clinica, o95_n_de_historia_clinica).
// Ahora se excluye por CLAVE: solo se omiten los campos que el partial a
// medida realmente resuelve -- por su name="campo_<id>" estándar, o por el
// mecanismo de name literal remapeado a mano en validarCamposDinamicos()
// (ítem N.2: b26_contactos_por_lugar, o95_hora_de_la_notificacion,
// o95_identificado_por, o95_tipo_de_ficha). Una sección de este grupo solo
// desaparece si TODOS sus campos están cubiertos; si queda uno sin cubrir,
// la sección se deja en $secciones y recibe su propia tarjeta con el
// render genérico, mostrando solo lo que nadie más pinta.
//
// $CLAVES_CUBIERTAS_POR_PARTIAL_A_MEDIDA es variable, no const: este
// partial se incluye con require (no require_once) y verificar_render.php
// invoca CasosController::nuevo() en bucle dentro de un mismo proceso PHP
// -- una constante de nivel de archivo aquí rompería esa segunda pasada.
$CLAVES_CUBIERTAS_POR_PARTIAL_A_MEDIDA = [
    'B05' => [
        // notificacion-fechas-b05.php
        'b05_enfermedad_notificada', 'b05_codigo_de_registro', 'b05_fecha_de_identificacion_local_del_caso_o_consulta',
        'b05_fecha_de_investigacion_visita_domiciliaria', 'b05_nombre_de_personal_de_salud_que_atiende_el_caso', 'b05_telefono_del_personal_de_salud',
        // datos-paciente-b05-loader.php (resuelve por etiqueta, no por clave, pero cubre estas 7)
        'b05_ocupacion', 'b05_lugar_probable_de_parto', 'b05_tipo_de_localidad', 'b05_es_menor_de_edad',
        'b05_nombre_de_madre_o_tutor', 'b05_telefono_de_madre_o_tutor', 'b05_n_doc_identidad_de_madre_o_tutor',
    ],
    'B26' => [
        // notificacion-fechas-b26.php
        'b26_codigo_de_registro_n', 'b26_fecha_de_consulta', 'b26_fecha_de_conocimiento_local_del_caso',
        'b26_fecha_de_investigacion_visita_domiciliaria', 'b26_fecha_de_notificacion_ee_ss_a_red_microred',
        'b26_fecha_de_notificacion_red_microred_a_direccion_de_s', 'b26_fecha_de_notificacion_direccion_de_salud_a_cdc',
        // cuadro-clinico-b26.php (cubre Cuadro clínico + Complicaciones + Hospitalización y egreso, una sola tarjeta)
        'b26_presento_inflamacion_de_glandulas_parotidas', 'b26_fecha_de_inicio_de_parotiditis', 'b26_n_de_dias_de_duracion',
        'b26_localizacion', 'b26_inflamacion_de_glandulas_submandibulares', 'b26_inflamacion_de_glandulas_sublinguales',
        'b26_orquitis', 'b26_ooforitis', 'b26_perdida_de_audicion', 'b26_encefalitis', 'b26_meningitis', 'b26_otras',
        'b26_hospitalizacion', 'b26_establecimiento', 'b26_fecha_de_hospitalizacion', 'b26_n_de_dias',
        'b26_condicion_de_egreso', 'b26_fecha_de_egreso', 'b26_referido_a', 'b26_causa_de_muerte',
        // lugar-probable-infeccion-b26.php
        'b26_en_las_ultimas_2_a_4_semanas_estuvo_en_contacto_con', 'b26_contactos_por_lugar',
        'b26_tuvo_contacto_con_gestante', 'b26_trimestre_de_gestacion_contacto',
        // PENDIENTES.md ítem N.3: duplicado conceptual muerto de caso.fecha_inicio_sintomas
        // (ver el comentario original en cuadro-clinico-b26.php) -- nunca fue alcanzable,
        // no es un campo nuevo sin cobertura. Se mantiene suprimido a propósito: decidir
        // si se borra o se conecta es aparte, no algo que esta ruta deba resolver sola.
        'b26_fecha_de_inicio_de_sintomas',
    ],
    'O95' => [
        // notificacion-fechas-o95.php (mecanismo de name literal remapeado, ítem N.2)
        'o95_hora_de_la_notificacion', 'o95_identificado_por', 'o95_tipo_de_ficha',
        // o95_n_de_historia_clinica ya no es campo_def -- se retiró del
        // manifiesto al promoverlo al núcleo declarativo (persona.n_historia_clinica
        // + nucleo_incluidos), PETICION_HC_Y_LABORATORIO.md Parte 1.
    ],
    'P35.0' => [
        // notificacion-fechas-p350.php
        'p35_0_codigo_de_registro_n', 'p35_0_fecha_de_conocimiento_local_del_caso', 'p35_0_fecha_de_notificacion_eess_a_red_microred',
        'p35_0_fecha_notif_red_microred_a_direccion_salud', 'p35_0_fecha_notif_direccion_salud_a_cdc',
        'p35_0_fecha_de_investigacion_visita_domiciliaria', 'p35_0_caso_captado_en',
        // clasificacion-caso-p350.php (PENDIENTES.md ítem Z.8): reposicionada
        // después de "Laboratorio" y antes de "Seguimiento de excreción
        // viral" -- orden pedido por el usuario, no la última sección del
        // manifiesto como caería acá por defecto (orden: 6).
        'p35_0_clasificacion_del_caso',
    ],
    'A35' => [
        // notificacion-fechas-a35.php
        'a35_caso_n', 'a35_fecha_de_conocimiento_local', 'a35_fecha_de_investigacion_visita_domiciliaria',
        'a35_fecha_de_notificacion_ee_ss_a_red_microrred', 'a35_fecha_de_notificacion_red_microrred_a_disa',
        'a35_tipo', 'a35_fuente', 'a35_fuente_otra', 'a35_trabajador_diagnostico_inicial',
        // "Antecedente epidemiológico" (PENDIENTES.md A35.12): el resto de la
        // sección se pinta genérico, solo "Distrito" se reemplaza por el
        // selector Departamento/Provincia/Distrito real -- ver el hook por
        // clave en $renderizarCampos más abajo.
        'a35_distrito_probable_infeccion',
    ],
    'A33' => [
        // notificacion-fechas-a33.php -- fechas + N.° de caso + Captación
        // del caso (Notificación regular/Búsqueda activa/Defunción). DISA/
        // RED/Nombre del establecimiento del PDF NO se capturan como
        // campo_def: son datos constantes del establecimiento ya elegido en
        // "Establecimiento (EESS)" (red_salud.nombre/diresa), mismo criterio
        // que "Institución informante" de A35 (A35.1).
        'a33_caso_n', 'a33_fecha_de_conocimiento_local', 'a33_fecha_de_investigacion_visita_domiciliaria',
        'a33_fecha_de_notificacion_ee_ss_a_red_microrred', 'a33_fecha_de_notificacion_red_microrred_a_disa',
        'a33_fecha_de_notificacion_dge', 'a33_captacion_del_caso',
    ],
    'A37.0' => [
        // notificacion-fechas-a370.php
        'a37_0_codigo_de_registro_n', 'a37_0_fecha_de_conocimiento_local_del_caso', 'a37_0_fecha_de_notificacion_ee_ss_a_red_microred',
        'a37_0_fecha_de_notificacion_red_microrred_a_direccion_r', 'a37_0_fecha_de_notificacion_de_direccion_de_salud_a_cdc',
        'a37_0_fecha_de_investigacion_visita_domiciliaria', 'a37_0_caso_captado_en',
        // clasificacion-caso-a370.php (2026-08-07, mismo motivo que
        // p35_0_clasificacion_del_caso): reposicionada después de
        // "Laboratorio" en vez de quedar en su orden del manifiesto (8),
        // que caería ANTES de la tarjeta fija de Laboratorio.
        'a37_0_clasificacion_final',
    ],
    'B01' => [
        // notificacion-fechas-b01.php (2026-08-08). Ojo: b01_fecha_de_hospitalizacion
        // NO está acá a propósito -- por decisión explícita del usuario
        // sigue viviendo solo en "Hospitalización y egreso" (ítem 32),
        // aunque el PDF también la mencione en la cabecera.
        'b01_codigo_de_registro_n', 'b01_fecha_de_investigacion_visita_domiciliaria', 'b01_fecha_de_notificacion_ee_ss_a_red_microred',
        'b01_fecha_de_notificacion_red_microred_a_direccion_de_sa', 'b01_fecha_de_notificacion_de_direccion_de_salud_a_cdc',
        // lugar-probable-infeccion-b01.php (2026-08-08): movida de orden 6
        // a orden 2 del manifiesto (el PDF la trae justo después de Datos
        // del paciente) + los 5 campos de "Ubicación probable de infección"
        // que v1.0 había omitido.
        'b01_inf_direccion', 'b01_inf_departamento_id', 'b01_inf_provincia_id', 'b01_inf_distrito_id', 'b01_inf_localidad',
        'b01_en_las_ultimas_2_a_3_semanas_estuvo_en_contacto_con', 'b01_contactos_por_lugar',
        'b01_tuvo_contacto_con_gestante', 'b01_fecha_de_contacto_con_gestante', 'b01_semanas_de_gestacion_contacto',
        // observaciones-b01.php (2026-08-09): reposicionada después de
        // Laboratorio (mismo patrón que clasificacion-caso-a370.php).
        'b01_observaciones',
    ],
    'A97' => [
        // notificacion-fechas-a97.php (2026-08-12): "Enfermedad / evento a
        // notificar" se mueve a la tarjeta fija "1. Notificación", en el
        // lugar donde otras fichas traen Tipo/Lugar/Clasificación en la
        // captación (pedido del usuario) -- el campo sigue siendo el mismo
        // campo_def del que dependen a97_clasificacion/_zika/
        // _fiebre_amarilla/_especificar en la sección "Clasificación".
        'a97_enfermedad_evento',
        // notificacion-fechas-a97.php (2026-08-13): "Subsistema de
        // vigilancia" (ítem I del PDF, pág. 49) se mueve a la misma tarjeta
        // fija, junto con "Fecha de investigación" (ítem 1 de "II. Datos
        // generales") -- pedido del usuario. GERESA/DIRESA/Red/EESS/
        // Institución (ítems 2-5) no se capturan como campo_def: son datos
        // del establecimiento ya elegido, mismo criterio que A37.0.
        'a97_subsistema_de_vigilancia',
        'a97_fecha_de_investigacion',
    ],
    'B57' => [
        // notificacion-fechas-b57.php (cotejo 2026-08-20, pág. 40 del PDF):
        // "Fecha conocimiento local/Fecha investigación/Fecha conocimiento
        // DISA/Fecha conocimiento nacional" (encabezado de la ficha) se
        // pintan en la tarjeta fija "1. Notificación", mismo criterio que
        // A97 arriba -- DISA/Nombre del establecimiento/UTES-UBAS-ZONADIS-RED
        // no se capturan como campo_def porque ya son datos del
        // establecimiento elegido (mismo criterio que A37.0/A97).
        // "Código" (2026-08-20, pedido del usuario): se pinta junto a "Tipo
        // de captación", en el espacio que deja libre "Lugar de captación"
        // (oculto para B57, ver notificacion-captacion.php) -- no en
        // notificacion-fechas-b57.php como las 4 fechas.
        'b57_codigo',
        'b57_fecha_conocimiento_local',
        'b57_fecha_de_investigacion',
        'b57_fecha_conocimiento_disa',
        'b57_fecha_conocimiento_nacional',
        // b57_viajo_ultimos_6_meses (cotejo 2026-08-21, pág. 40 del PDF):
        // pintado a medida en migracion-b57.php (tarjeta propia "3.
        // Migración", ver nueva/index.php/fichas/editar.php) -- no en su
        // tarjeta lógica "Antecedentes epidemiológicos" (esa sección solo
        // excluye este campo puntual, el resto sigue con render genérico:
        // no está en $SECCIONES_CON_PARTIAL_A_MEDIDA['B57'] más abajo).
        'b57_viajo_ultimos_6_meses',
    ],
    'A95' => [
        // notificacion-fechas-a95.php (cotejo 2026-08-22, pág. 26 del PDF):
        // mismo encabezado que B57 (arriba) -- "Fecha conocimiento
        // local/Fecha investigación/Fecha conocimiento DISA/Fecha
        // conocimiento nacional" en la tarjeta fija "1. Notificación", y
        // "Código" junto a "Tipo de captación" (notificacion-captacion.php).
        'a95_codigo',
        'a95_fecha_conocimiento_local',
        'a95_fecha_de_investigacion',
        'a95_fecha_conocimiento_disa',
        'a95_fecha_conocimiento_nacional',
        // Los 4 campos de la sección "Migración" del manifiesto (cotejo
        // 2026-08-22, 2.ª revisión: el usuario señaló con una captura que
        // "Hubo casos reportados..."/"Viajó los últimos 6 meses?" están
        // DENTRO de "III. MIGRACION" del PDF, no en una sección aparte) se
        // pintan enteros a medida en migracion-a95.php (tarjeta propia "3.
        // Migración") -- la sección entera está en
        // $SECCIONES_CON_PARTIAL_A_MEDIDA más abajo, así que TODOS sus
        // campos deben estar acá o la sección no desaparece del render
        // genérico (claveCubiertaPorPartial se evalúa campo por campo).
        'a95_localidades_visitadas_en_los_ultimos_10_dias',
        'a95_casos_reportados_en_los_ultimos_10_dias',
        'a95_cuantas_personas_viven_en_su_casa',
        'a95_viajo_en_los_ultimos_6_meses',
    ],
];
$claveCubiertaPorPartial = fn(string $clave): bool => in_array(
    $clave,
    $CLAVES_CUBIERTAS_POR_PARTIAL_A_MEDIDA[$enfermedad['cie10'] ?? ''] ?? [],
    true
);

$SECCIONES_CON_PARTIAL_A_MEDIDA = [
    'B05' => ['Datos de notificación e identificación del caso', 'Datos de filiación y tutor'],
    'B26' => ['Datos de notificación e investigación del caso', 'Lugar probable de infección', 'Cuadro clínico', 'Complicaciones', 'Hospitalización y egreso'],
    'O95' => ['Datos de notificación'],
    'P35.0' => ['Datos de notificación e investigación del caso', 'Clasificación del caso'],
    'A35' => ['Datos de notificación e investigación del caso'],
    'A33' => ['Datos de notificación e investigación del caso'],
    'A37.0' => ['Datos de notificación e investigación del caso', 'Clasificación final'],
    'B01' => ['Datos de notificación e investigación del caso', 'Lugar probable de infección', 'Observaciones'],
    'A97' => ['Enfermedad / evento a notificar', 'Subsistema de vigilancia'],
    'B57' => ['Fechas de notificación'],
    'A95' => ['Fechas de notificación', 'Migración'],
][$enfermedad['cie10'] ?? ''] ?? [];

if ($SECCIONES_CON_PARTIAL_A_MEDIDA) {
    $secciones = array_values(array_filter($secciones, function ($s) use ($SECCIONES_CON_PARTIAL_A_MEDIDA, $claveCubiertaPorPartial) {
        if (!in_array(trim($s['nombre']), $SECCIONES_CON_PARTIAL_A_MEDIDA, true)) {
            return true;
        }
        foreach (CampoDef::porSeccion((int) $s['id']) as $campo) {
            if (!$claveCubiertaPorPartial($campo['clave'])) {
                return true; // al menos un campo sin cubrir -> la sección no desaparece
            }
        }
        return false; // todos cubiertos -> mismo comportamiento que antes
    }));
}

// Peticion 2, correccion post-Fase-7: guarda aditiva. Los nombres de
// seccion tambien son cadenas del manifiesto -- si alguien corrige una
// tilde o renombra una seccion de O95, antes el despacho por nombre (mas
// abajo) fallaba en silencio: la seccion caia al renderizador generico en
// vez de su partial a medida, o quedaba fuera de $O95_SECCIONES_SOLO_ANEXO_2
// sin avisar. Se convierte en un fallo visible, mismo principio que la
// validacion "todo o nada" de orden en cargar_fichas.php (Fase 6).
if (($enfermedad['cie10'] ?? '') === 'O95') {
    $O95_SECCIONES_SOLO_ANEXO_2 = [
        'Antecedentes patológicos y obstétricos',
        'Atención prenatal',
        'Complicaciones',
        'Hospitalizaciones',
        'Parto o aborto',
        'Entorno social y comunitario',
        'Datos comunitarios',
        'Las cuatro demoras',
    ];
    $O95_SECCIONES_DESPACHO_A_MEDIDA = [
        'Datos del fallecimiento (Anexo 1)', // $secciones[0], ver mas abajo
        'Antecedentes patológicos y obstétricos',
        'Causas de defunción (Anexo 1)',
        'Atención prenatal',
        'Complicaciones',
        'Referencia (Anexo 1)',
        'Hospitalizaciones',
        'Parto o aborto',
        'Entorno social y comunitario',
        'Datos comunitarios',
        'Las cuatro demoras',
    ];
    $nombresSeccionesCargadasO95 = array_map(fn($s) => trim($s['nombre']), $secciones);
    foreach (array_unique(array_merge($O95_SECCIONES_SOLO_ANEXO_2, $O95_SECCIONES_DESPACHO_A_MEDIDA)) as $nombreEsperadoO95) {
        if (!in_array($nombreEsperadoO95, $nombresSeccionesCargadasO95, true)) {
            throw new RuntimeException(
                'secciones-clinicas.php: la seccion O95 "' . $nombreEsperadoO95 . '" no se encontro entre las '
                . 'secciones cargadas del manifiesto/BD. El despacho a partials a medida y la marca de Anexo 2 '
                . 'dependen de coincidencia EXACTA del nombre -- revisar manifiesto_fichas.json.'
            );
        }
    }
}

$opcionesPorCatalogo = [];
$numeroSeccion = $numeroSeccionInicial;

// Precalculado fuera de $renderizarCampos porque adentro el nombre $campo ya
// está tomado por la fila de campo_def de cada iteración del foreach (mismo
// nombre que exige el contrato de campos-por-clave.php, ver Fase 2) — no se
// puede resolver por clave "adentro" sin pisarlo. Solo se pide para B05: en
// cualquier otra ficha la clave no existe por diseño (no es una clave
// faltante real) y no hay que ensuciar $clavesFaltantesCampos con eso.
$campoFechaUltSeg = (($enfermedad['cie10'] ?? '') === 'B05')
    ? $campo('b05_fecha_de_ultimo_dia_de_seguimiento_de_contactos')
    : ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];

// A35.12: "Distrito" de "Antecedente epidemiológico" se reemplaza por el
// selector real Departamento/Provincia/Distrito (mismo motivo que
// $campoFechaUltSeg: hace falta su valor guardado -- el nombre del
// distrito, no un id -- para reconstruir el contexto del selector antes
// de llegar a su posición en el loop).
$campoDistritoInfeccionA35 = (($enfermedad['cie10'] ?? '') === 'A35')
    ? $campo('a35_distrito_probable_infeccion')
    : ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];

// "Condiciones de alta"/"Fecha de alta" (A33/A35) solo tienen sentido si el
// paciente estuvo hospitalizado -- además de depender de Fallecido=No (ver
// PENDIENTES.md, discusión con el usuario sobre Hospitalizado vs Fallecido).
// El motor de dependencias ya cascada un .dep-wrap[hidden] a lo que tiene
// adentro (ficha.js evaluarDependencias(), línea ~81), así que anidar un
// .dep-wrap de Hospitalizado=Sí alrededor del/de los .dep-wrap ya existentes
// de Fallecido=No logra el AND (Hospitalizado=Sí Y Fallecido=No) sin JS
// nuevo. Mismo motivo que $campoFechaUltSeg para precalcular afuera.
$campoHospitalizadoA33 = (($enfermedad['cie10'] ?? '') === 'A33')
    ? $campo('a33_hospitalizado')
    : ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];
$campoHospitalizadoA35 = (($enfermedad['cie10'] ?? '') === 'A35')
    ? $campo('a35_hospitalizado')
    : ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];
// A44 "Diagnósticos" (2026-08-19): mismo motivo que A33/A35 arriba -- el
// grupo "Diagnóstico de alta" (eyebrow + 3 campos) necesita un dep-wrap
// propio alrededor de TODO el grupo (no solo de cada campo suelto, que ya
// se ocultan individualmente vía su depende_de de campo_def) para que el
// título del grupo también desaparezca cuando Hospitalizado != Sí.
$campoHospitalizadoA44 = (($enfermedad['cie10'] ?? '') === 'A44')
    ? $campo('a44_hospitalizado')
    : ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];

// A97 "Otros: especificar prueba" (2026-08-14, pedido del usuario): solo
// debe verse si la fila "Otros" de a97_pruebas_de_laboratorio (MATRIZ)
// quedó en Positivo/Negativo -- mismo motivo que $campoFechaUltSeg arriba
// (depende_de/.dep-wrap genéricos solo saben leer el valor COMPLETO de un
// campo, no una fila puntual de un MATRIZ). Se arma el name real del
// radio de esa fila ("campo_<id>[<idx>][_radio]") y se usa como
// data-depende-de de un .dep-wrap a mano -- evaluarDependencias() de
// ficha.js ya sabe leer cualquier name literal por selector CSS, sin JS
// nuevo. El índice de "Otros" se busca en el config real (no se
// hardcodea la posición 6) por si el orden de filas cambia más adelante.
$nombreRadioOtrosLabA97 = '';
$valorOtrosLabA97 = '';
if (($enfermedad['cie10'] ?? '') === 'A97') {
    $campoLabA97 = $campo('a97_pruebas_de_laboratorio');
    $configLabA97 = json_decode((string) ($campoLabA97['campo']['config'] ?? '{}'), true);
    $idxOtrosLabA97 = array_search('Otros', $configLabA97['filas'] ?? [], true);
    if ($idxOtrosLabA97 !== false) {
        $nombreRadioOtrosLabA97 = $campoLabA97['name'] . '[' . $idxOtrosLabA97 . '][_radio]';
        $valorOtrosLabA97 = is_array($campoLabA97['val']) ? ($campoLabA97['val'][$idxOtrosLabA97]['_radio'] ?? '') : '';
    }
}

// A44 "Piel" (2026-08-18, pedido del usuario): Palidez/Petequias/Equimosis
// pasan del <select> Sí/No genérico (rama BOOLEANO de más abajo, la que
// usan también a33_hospitalizado/a35_hospitalizado/etc.) al mismo look de
// radios Sí/No segmentados que ya usan GRUPO_SI_NO/SI_NO_FECHA (A37.0,
// B26...), con su campo dependiente (Grado de palidez/Localización)
// apareciendo justo debajo dentro de la misma tarjeta -- no como .field
// hermano en el grid, que es donde caía hoy (a un costado, no debajo).
// Se resuelven acá (mismo motivo que $campoFechaUltSeg) porque hace falta
// el campo HIJO completo antes de llegar a la posición del padre en el
// loop. depende_de/valor_activador de los 3 hijos no cambian (siguen
// siendo "1", el mismo valor que ya guarda un BOOLEANO) -- CasosController
// y campoVisiblePorDependencia() no necesitan tocarse.
$campoGradoPalidezA44 = (($enfermedad['cie10'] ?? '') === 'A44')
    ? $campo('a44_grado_de_palidez')
    : ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];
$campoLocPetequiasA44 = (($enfermedad['cie10'] ?? '') === 'A44')
    ? $campo('a44_localizacion_de_petequias')
    : ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];
$campoLocEquimosisA44 = (($enfermedad['cie10'] ?? '') === 'A44')
    ? $campo('a44_localizacion_de_equimosis')
    : ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];

// A44 "Cabeza" (2026-08-19, cotejo jerarquía Signos y examen físico): mismo
// patrón exacto que Palidez/Petequias/Equimosis -- Sí/No con severidad
// revelada justo debajo, ver $renderBooleanoPillA44 más abajo.
$campoGradoPalidezConjuntivalA44 = (($enfermedad['cie10'] ?? '') === 'A44')
    ? $campo('a44_grado_palidez_conjuntival')
    : ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];
$campoGradoIctericiaEscleralA44 = (($enfermedad['cie10'] ?? '') === 'A44')
    ? $campo('a44_grado_ictericia_escleral')
    : ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];

$renderizarCampos = function (int $seccionId) use (&$opcionesPorCatalogo, $valoresCampos, $erroresCampos, $enfermedad, $campoFechaUltSeg, $campoDistritoInfeccionA35, $campoHospitalizadoA33, $campoHospitalizadoA35, $campoHospitalizadoA44, $nombreRadioOtrosLabA97, $valorOtrosLabA97, $campoGradoPalidezA44, $campoLocPetequiasA44, $campoLocEquimosisA44, $campoGradoPalidezConjuntivalA44, $campoGradoIctericiaEscleralA44, $claveCubiertaPorPartial, $filasViajes, $erroresViajes, $columnasViaje, $filasContactos, $columnasContacto): void {
    $campos = CampoDef::porSeccion($seccionId);
    // Ruta 2: si esta sección sobrevivió el filtro de arriba por tener al
    // menos un campo sin cubrir, los campos que SÍ están cubiertos por un
    // partial a medida no se repiten acá.
    $campos = array_values(array_filter($campos, fn($c) => !$claveCubiertaPorPartial($c['clave'])));

    // P35.0 no tiene secciones de "lista de síntomas": sus BOOLEANO son
    // hechos puntuales (¿nació prematuro?, ¿hospitalización?, etc.) que
    // deben quedarse en su orden del manifiesto, no desterrarse al final
    // bajo "Signos y síntomas" (pensado para fichas con checklists reales).
    $esP350 = (($enfermedad['cie10'] ?? '') === 'P35.0');
    $idsPadre = array_filter(array_column($campos, 'depende_de'));
    // a35_no_recuerda_dia no es un síntoma: es un modificador de precisión
    // de "Fecha de inicio de lesión" (checkbox que cambia ese input a
    // mes/año, ver sincronizarNoRecuerdaDiaA35() en ficha.js) -- debe
    // quedar junto a esa fecha en su orden del manifiesto, no barrido al
    // final bajo "Signos y síntomas" (ese bloque es para síntomas reales,
    // ver el caso de P35.0 arriba). a35_dosis_1d..5d: mismo motivo -- son
    // el checklist "Dosis recibidas" de toxoide tetánico (PENDIENTES.md
    // A35.11), no un síntoma; se agrupan aparte en una fila horizontal
    // propia (ver el hook por clave más abajo), no en "Signos y síntomas".
    // a37_0_viajo_en_los_ultimos_21_dias/a37_0_algun_miembro_de_la_familia...:
    // ninguno de los 2 es "padre" real de otro campo_def (solo de un wrap a
    // medida hecho a mano en el hook por clave más abajo, que el motor
    // $idsPadre no puede detectar porque no es un depende_de real) -- sin
    // esto, ambos caían al grupo baneado "Signos y síntomas" y el hook de
    // más abajo nunca se ejecutaba (el campo nunca pasaba por este loop).
    // a44_inyeccion_conjuntival (cotejo 2026-08-20, pedido del usuario): sin
    // esto cae al checklist de chips "Signos y síntomas" del final porque no
    // tiene ningún campo hijo que lo marque como "padre" en $idsPadre --
    // pero el usuario la quiere junto a Conjuntivas pálidas/Escleróticas
    // ictéricas (mismo control Sí/No), no mezclada con los síntomas reales.
    // a95_necropsia (cotejo 2026-08-23, jerarquía Hospitalización): mismo
    // motivo exacto que a44_inyeccion_conjuntival -- depende_de "Condición
    // de egreso" (no es "padre" de ningún campo_def, así que $idsPadre no la
    // detecta), pero el usuario la quiere junto a Dx macroscópico/Dx
    // microscópico/Fecha bajo la rama "Fallecido", no como chip suelto en
    // "Signos y síntomas" al final de la tarjeta.
    $clavesBooleanoJuntoASuCampo = ['a35_no_recuerda_dia', 'a35_dosis_1d', 'a35_dosis_2d', 'a35_dosis_3d', 'a35_dosis_4d', 'a35_dosis_5d', 'a33_dosis_1a', 'a33_dosis_2a', 'a33_dosis_3a', 'a33_dosis_4a', 'a33_dosis_5a', 'a37_0_pentavalente_1ra', 'a37_0_pentavalente_2da', 'a37_0_pentavalente_3ra', 'a37_0_dpt_1er_refuerzo', 'a37_0_dpt_2do_refuerzo', 'a37_0_viajo_en_los_ultimos_21_dias', 'a37_0_algun_miembro_de_la_familia_o_persona_cercana_ha_', 'a44_inyeccion_conjuntival', 'a95_necropsia'];
    $esBooleanoJuntoASuCampo = fn($c) => in_array($c['id'], $idsPadre) || in_array($c['clave'] ?? '', $clavesBooleanoJuntoASuCampo, true);
    $camposBooleanos = $esP350 ? [] : array_filter($campos, fn($c) => $c['tipo'] === 'BOOLEANO' && !$esBooleanoJuntoASuCampo($c));
    $camposOtros = $esP350 ? $campos : array_filter($campos, fn($c) => $c['tipo'] !== 'BOOLEANO' || $esBooleanoJuntoASuCampo($c));

    // A44 (cotejo 2026-08-18): "Estado general / Estado de nutrición / Estado
    // de hidratación" (pág. 42 del PDF, misma fila) caían 2+1 en el grid de
    // 2 columnas por defecto de esta plantilla genérica -- se pide "thirds"
    // (3 columnas) solo para ese trío puntual, sin tocar el layout genérico
    // de las demás 23 fichas.
    $esFilaEstadosA44 = (($enfermedad['cie10'] ?? '') === 'A44') && in_array('a44_estado_general', array_column($camposOtros, 'clave'), true);

    // A44 "Piel": arma el radio Sí/No segmentado (mismo .seg/.seg-label que
    // GRUPO_SI_NO/SI_NO_FECHA, ver campos-dinamicos.css) con el campo hijo
    // (SELECT o TEXTO, ya resuelto en $campoHijo) anidado justo debajo, en
    // un .dep-wrap estándar (data-depende-de="campo_<id>", igual que
    // cualquier otro depende_de) -- evaluarDependencias() de ficha.js ya lo
    // muestra/oculta sin JS nuevo, porque el radio usa el mismo
    // name="campo_<id>" y los mismos valores '1'/'0' que un BOOLEANO normal.
    // $campoHijo es null para BOOLEANO sin campo dependiente (Inyección
    // conjuntival, cotejo 2026-08-20: mismo control Sí/No que Conjuntivas
    // pálidas/Escleróticas ictéricas, pero sin severidad debajo) -- se omite
    // el .dep-wrap entero en ese caso, no uno vacío.
    $renderBooleanoPillA44 = function (array $campo, $valor, ?string $error, ?array $campoHijo, bool $wide = true): void {
        $nombreCampo = 'campo_' . $campo['id'];
        $esSi = (string) $valor === '1';
        $esNo = (string) $valor === '0';
        $respondido = $esSi || $esNo;
        ?>
        <div class="field <?= $wide ? 'wide ' : '' ?>grupo-si-no-field" style="margin-top:6px; margin-bottom:8px;">
          <div class="grupo-si-no-row <?= $esSi ? 'is-si' : '' ?> <?= $respondido ? 'respondido' : 'pendiente' ?>" tabindex="-1" style="display:flex; flex-direction:column; justify-content:center; border-bottom:1px solid var(--line-2); min-height:40px; padding:6px 0; transition: border-left 0.15s; border-left: <?= $esSi ? '3px solid var(--accent)' : '3px solid transparent' ?>;">
            <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
              <span class="row-label" style="font-size: 13.5px; color: <?= $esSi ? 'var(--ink)' : ($respondido ? 'var(--ink-2)' : 'var(--ink)') ?>; font-weight: <?= $esSi ? '500' : 'normal' ?>; flex:1; padding-left:6px;">
                <?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?>
              </span>
              <div class="seg" style="width: 130px; flex-shrink:0;">
                <label class="seg-label <?= $esSi ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="Sí">
                  <input type="radio" name="<?= e($nombreCampo) ?>" value="1" class="sr-only" <?= $esSi ? 'checked' : '' ?>>
                  Sí
                </label>
                <label class="seg-label <?= $esNo ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;" title="No">
                  <input type="radio" name="<?= e($nombreCampo) ?>" value="0" class="sr-only" <?= $esNo ? 'checked' : '' ?>>
                  No
                </label>
              </div>
            </div>
            <?php if ($campoHijo !== null): ?>
            <div class="dep-wrap" data-depende-de="<?= e($nombreCampo) ?>" data-valor-activador="1" style="margin-top:10px; padding-left:6px; padding-right:6px; width:100%;" <?= !$esSi ? 'hidden' : '' ?>>
              <?php
              $campoDinamico = $campoHijo['campo'];
              $valorDinamico = $campoHijo['val'];
              $errorDinamico = $campoHijo['err'];
              $opcionesDinamico = $campoHijo['opciones'];
              (function () use ($campoDinamico, $valorDinamico, $errorDinamico, $opcionesDinamico) {
                  $campo = $campoDinamico;
                  $valor = $valorDinamico;
                  $error = $errorDinamico;
                  $opciones = $opcionesDinamico;
                  require __DIR__ . '/campo-dinamico.php';
              })();
              ?>
            </div>
            <?php endif; ?>
          </div>
          <?php if ($error): ?><span class="hint err" style="margin-top:8px; display:block;"><?= e($error) ?></span><?php endif; ?>
        </div>
        <?php
    };

    if (!empty($camposOtros)): ?>
        <div class="fields<?= $esFilaEstadosA44 ? ' thirds' : '' ?>" style="margin-bottom:<?= empty($camposBooleanos) ? '0' : '16px' ?>">
          <?php
          $tipoAnterior = null;
          foreach ($camposOtros as $campo):
            if (($campo['clave'] ?? '') === 'b05_fecha_de_ultimo_dia_de_seguimiento_de_contactos') {
                continue;
            }
            if (in_array($campo['clave'] ?? '', ['a44_grado_de_palidez', 'a44_localizacion_de_petequias', 'a44_localizacion_de_equimosis', 'a44_grado_palidez_conjuntival', 'a44_grado_ictericia_escleral'], true)) {
                continue; // se renderiza anidado dentro de su padre, ver $renderBooleanoPillA44
            }
            $campo['obligatorio'] = (int) $campo['obligatorio'];
            $valor = $valoresCampos[$campo['id']] ?? ($campo['tipo'] === 'MULTISELECT' ? [] : '');
            $error = $erroresCampos[$campo['id']] ?? null;
            $opciones = [];
            if ($campo['catalogo_id']) {
                $opcionesPorCatalogo[$campo['catalogo_id']] ??= CatalogoItem::porCatalogo((int) $campo['catalogo_id']);
                $opciones = $opcionesPorCatalogo[$campo['catalogo_id']];
            }
            $esSubgrupo = ($campo['tipo'] === 'GRUPO_SI_NO' && $tipoAnterior === 'GRUPO_SI_NO');
            if (($campo['clave'] ?? '') === 'b05_hospitalizado') {
                ?></div><div class="eyebrow" style="margin-bottom:12px">Condición del paciente</div><div class="fields" style="margin-bottom:16px"><?php
            }
            if (in_array($campo['clave'] ?? '', ['a35_dosis_1d', 'a33_dosis_1a'], true)) {
                ?></div><div class="eyebrow" style="margin-bottom:10px">Dosis recibidas</div><div class="fields" style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom:16px"><?php
            }
            // A37.0 (2026-08-06): "Dosis recibidas" (ítem 51) es un
            // checklist cerrado de 5 casillas -- mismo patrón que las
            // dosis de A35/A33 arriba, pero repartido en 2 grupos
            // (Pentavalente/DPT) porque cada uno tiene su propia
            // numeración.
            if (($campo['clave'] ?? '') === 'a37_0_pentavalente_1ra') {
                ?></div><div class="eyebrow" style="margin-bottom:10px">Pentavalente</div><div class="fields" style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom:16px"><?php
            }
            if (($campo['clave'] ?? '') === 'a37_0_dpt_1er_refuerzo') {
                ?></div><div class="eyebrow" style="margin-bottom:10px">DPT</div><div class="fields" style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom:16px"><?php
            }
            // A44 "Diagnósticos" (2026-08-19): 2 grupos de 3 campos TEXTO
            // sueltos se confundían a la vista sin separación -- mismo
            // patrón de "Dosis recibidas"/"Pentavalente"/"DPT" de arriba
            // (cierra el .fields abierto, pinta un .eyebrow, abre uno
            // nuevo). "Diagnóstico de alta" solo se ve si Hospitalizado=Sí
            // (depende_de de cada uno de sus 3 campos, sin cambios acá).
            if (($campo['clave'] ?? '') === 'a44_diagnostico_consulta_externa_1') {
                ?></div><div class="eyebrow" style="margin-bottom:10px">Diagnóstico de consulta externa o ingreso</div><div class="fields thirds" style="margin-bottom:16px"><?php
            }
            if (($campo['clave'] ?? '') === 'a44_diagnostico_alta_1') {
                // El título del grupo también debe ocultarse junto con sus
                // 3 campos -- no solo cada campo por separado (esos ya se
                // ocultan vía su depende_de de campo_def genérico, ver más
                // abajo) -- así que todo el bloque (eyebrow + fields) va
                // dentro de un dep-wrap propio, mismo mecanismo que el
                // grupo "Condición de alta" de A33/A35 (líneas ~490-493).
                $ocultoDiagnosticoAltaA44 = (string) ($campoHospitalizadoA44['val'] ?? '') !== '1';
                ?></div><div class="dep-wrap" data-depende-de="<?= e($campoHospitalizadoA44['name']) ?>" data-valor-activador="1" <?= $ocultoDiagnosticoAltaA44 ? 'hidden' : '' ?>><div class="eyebrow" style="margin-bottom:10px">Diagnóstico de alta (solo si fue hospitalizado)</div><div class="fields thirds" style="margin-bottom:16px"><?php
            }
            // A35.12: "Distrito" (excluido arriba del loop genérico vía
            // $CLAVES_CUBIERTAS_POR_PARTIAL_A_MEDIDA) se reemplaza acá, justo
            // antes de "Localidad" (su posición original, orden 1 vs 2), por
            // el selector real Departamento/Provincia/Distrito -- Departamento
            // y Provincia son solo ayuda visual para acotar la lista (no se
            // guardan); solo el nombre del Distrito llega a
            // a35_distrito_probable_infeccion (ver validarCamposDinamicos()).
            if (($campo['clave'] ?? '') === 'a35_localidad_probable_infeccion') {
                $nombreDistritoGuardadoA35 = trim((string) $campoDistritoInfeccionA35['val']);
                $distritoResueltoA35 = $nombreDistritoGuardadoA35 !== '' ? \App\Models\Distrito::buscarPorNombre($nombreDistritoGuardadoA35) : null;
                ?></div><div style="margin-bottom:14px">
                <div class="eyebrow" style="margin-bottom:10px">Lugar probable de infección</div>
                <?php
                extract(contextoUbigeo($distritoResueltoA35['id'] ?? null));
                $prefijo = 'a35lugarinf-ubigeo';
                $nombreCampoDepartamento = 'a35_lugar_infeccion_departamento_id';
                $nombreCampoProvincia = 'a35_lugar_infeccion_provincia_id';
                $nombreCampoDistrito = 'a35_lugar_infeccion_distrito_id';
                $distritoSeleccionado = $distritoResueltoA35['id'] ?? '';
                $distritoRequerido = false;
                $errorDistrito = $campoDistritoInfeccionA35['err'];
                require __DIR__ . '/selector-ubigeo.php';
                ?>
                </div><div class="fields" style="margin-bottom:16px"><?php
            }
            // B57 (cotejo 2026-08-21, ítem 1.1-1.4): "Lugar probable de
            // contagio" es un único bloque por caso (no una lista, a
            // diferencia de A35 arriba) -- Departamento/Provincia/Distrito
            // (selector-ubigeo.php) + Localidad libre, guardados en columnas
            // fijas de `caso` (lugar_contagio_distrito_id/localidad), no en
            // campo_def -- mismo criterio de fidelidad completa que
            // "Domicilio anterior" (migracion-b57.php). Se inyecta justo
            // antes de "Fecha probable de contagio" (campo_def real, ítem
            // 1.5), que sigue rindiéndose de forma genérica.
            if (($campo['clave'] ?? '') === 'b57_fecha_probable_de_contagio') {
                ?></div><div style="margin-bottom:14px">
                <div class="eyebrow" style="margin-bottom:10px">Lugar probable de contagio</div>
                <?php
                extract(contextoUbigeo(($valoresFijos['lugar_contagio_distrito_id'] ?? '') ?: null));
                $prefijo = 'b57contagio-ubigeo';
                $nombreCampoDepartamento = 'lugar_contagio_departamento_id';
                $nombreCampoProvincia = 'lugar_contagio_provincia_id';
                $nombreCampoDistrito = 'lugar_contagio_distrito_id';
                $distritoSeleccionado = $valoresFijos['lugar_contagio_distrito_id'] ?? '';
                $distritoRequerido = false;
                $errorDistrito = null;
                require __DIR__ . '/selector-ubigeo.php';
                ?>
                <div class="field" style="margin-top:10px">
                  <label class="fl">Localidad</label>
                  <div class="control"><input type="text" name="lugar_contagio_localidad" value="<?= e($valoresFijos['lugar_contagio_localidad'] ?? '') ?>"></div>
                </div>
                </div><div class="fields" style="margin-bottom:16px"><?php
            }
            // B57 (ítem 2.1-2.3): "Tiempo de permanencia en el lugar
            // probable de contagio" son 3 casillas numéricas cortas (mismo
            // patrón visual que "Dosis recibidas"/"Pentavalente" arriba) --
            // se agrupan bajo su propio eyebrow y se cierra ese grupo justo
            // antes de "¿Existe chirimacha...?" para volver al layout normal.
            if (($campo['clave'] ?? '') === 'b57_permanencia_dias') {
                ?></div><div class="eyebrow" style="margin-bottom:10px">Tiempo de permanencia en el lugar probable de contagio</div><div class="fields thirds" style="margin-bottom:16px"><?php
            }
            if (($campo['clave'] ?? '') === 'b57_existe_chirimacha_o_chinche_en_su_casa') {
                ?></div><div class="fields" style="margin-bottom:16px"><?php
            }
            // B57 (cotejo 2026-08-21, ítem IX del PDF): "Laboratorio" agrupa
            // "Datos del laboratorio" (nombre/fecha de recepción/tipo de
            // muestra) y las 2 filas fijas "Sangre"/"Suero" (cada una con
            // sus propias Fecha de toma/envío/lectura/Examen realizado/
            // Resultado -- etiquetas repetidas entre ambos grupos a
            // propósito, mismo criterio que "Etapa aguda"/"Etapa crónica" de
            // Cuadro clínico) bajo su propio eyebrow. Sangre/Suero llevan
            // además el borde-acento izquierdo que ya usa el caso especial
            // de B05 en muestras.php (border-left+padding sobre el propio
            // .fields, sin wrapper extra) -- pedido del usuario 2026-08-21:
            // la vista "se perdía" con los 5 campos de cada grupo sueltos,
            // sin separación visual clara entre Sangre y Suero. Se aplica
            // directo sobre .fields (no un <div> aparte) para no dejar 2
            // niveles abiertos: el cierre final tras el foreach solo cierra
            // UNO.
            if (($campo['clave'] ?? '') === 'b57_lab_nombre') {
                ?></div><div class="eyebrow" style="margin-bottom:10px">Datos del laboratorio</div><div class="fields" style="margin-bottom:16px"><?php
            }
            if (($campo['clave'] ?? '') === 'b57_sangre_fecha_toma') {
                ?></div><div class="eyebrow" style="margin-bottom:10px">Sangre</div><div class="fields thirds" style="margin-bottom:16px; border-left:3px solid var(--accent); padding:14px 0 4px 14px;"><?php
            }
            if (($campo['clave'] ?? '') === 'b57_suero_fecha_toma') {
                ?></div><div class="eyebrow" style="margin-bottom:10px">Suero</div><div class="fields thirds" style="margin-bottom:16px; border-left:3px solid var(--accent); padding:14px 0 4px 14px;"><?php
            }
            // A95 "Laboratorio" (cotejo 2026-08-23): el encabezado fijo
            // ("Laboratorio que recepciona"/Fecha/Tipo de muestra/
            // especificar) no necesita eyebrow propio, son los primeros
            // campos de la tarjeta -- las 4 filas del PDF (Biopsia/
            // Serología/Hígado/Cultivos) se dejaron de modelar como campo_def
            // fijos (2.ª revisión, pedido del usuario: la ficha no exige las
            // 4 pruebas) y ahora usan el componente dinámico "+ Agregar
            // muestra" (caso_muestra), inyectado más abajo en este mismo
            // archivo justo después de $renderizarCampos() para la sección
            // "Laboratorio" -- ver tablas-hijas/muestras.php.
            $abreWrapHospitalizadoAlta = in_array($campo['clave'] ?? '', ['a33_condiciones_de_alta', 'a35_fecha_de_alta'], true);
            if ($abreWrapHospitalizadoAlta) {
                $campoHospitalizadoAlta = (($campo['clave'] ?? '') === 'a33_condiciones_de_alta') ? $campoHospitalizadoA33 : $campoHospitalizadoA35;
                ?><div class="dep-wrap" data-depende-de="<?= e($campoHospitalizadoAlta['name']) ?>" data-valor-activador="SI"><?php
            }
            // A37.0 (2026-08-06): "madre vacunada Tdap" solo aplica si el
            // paciente es <1 año (calcularEdad() en ficha.js); "gestante
            // recibió Tdap" solo si persona.gestante=1 (actualizarGestante(),
            // mismo mecanismo que ya usa wrapLugarPartoB05 en
            // datos-paciente-nucleo.php). Ambos wraps arrancan ocultos por
            // defecto -- el JS los des-oculta en la carga si aplica, igual
            // que #campoSemanasGestacion/#campoTrimestreGestacion.
            if (($campo['clave'] ?? '') === 'a37_0_la_madre_fue_vacunada_con_tdap_durante_la_gestaci') {
                ?><div id="wrapTdapMadreA370" hidden style="display:none;"><?php
            }
            if (($campo['clave'] ?? '') === 'a37_0_la_gestante_recibio_tdap_si_el_caso_es_gestante') {
                ?><div id="wrapTdapGestanteA370" hidden style="display:none;"><?php
            }
            $tieneDependencia = !empty($campo['depende_de']);
            if ($tieneDependencia):
                $oculto = !campoVisiblePorDependencia($campo, $valoresCampos);
                ?><div class="dep-wrap" data-depende-de="campo_<?= (int) $campo['depende_de'] ?>" data-valor-activador="<?= e($campo['valor_activador']) ?>" <?= $oculto ? 'hidden' : '' ?>><?php
            endif;
            $abreWrapOtrosLabA97 = ($campo['clave'] ?? '') === 'a97_otros_prueba_especificar' && $nombreRadioOtrosLabA97 !== '';
            if ($abreWrapOtrosLabA97):
                $ocultoOtrosLabA97 = !in_array($valorOtrosLabA97, ['POSITIVO', 'NEGATIVO'], true);
                ?><div class="dep-wrap" data-depende-de="<?= e($nombreRadioOtrosLabA97) ?>" data-valor-activador="POSITIVO,NEGATIVO" <?= $ocultoOtrosLabA97 ? 'hidden' : '' ?>><?php
            endif;
            if (in_array($campo['clave'] ?? '', ['a44_palidez', 'a44_petequias', 'a44_equimosis', 'a44_conjuntivas_palidas', 'a44_escleroticas_ictericas', 'a44_inyeccion_conjuntival'], true)):
                // Petequias/Equimosis van en 2 columnas, una junto a la otra
                // (pedido del usuario) -- Palidez se queda "wide" (fila
                // propia, como en la captura de referencia). Como no hay
                // ningún otro campo entre ellas en el orden del manifiesto
                // (sus hijos están anidados, no ocupan turno en el grid), al
                // quitarles "wide" el grid de 2 columnas de .fields las
                // empareja automáticamente en la misma fila. Conjuntivas
                // pálidas/Escleróticas ictéricas (Cabeza, 2026-08-19) son el
                // mismo patrón Sí/No -> severidad debajo, pero no se pidió
                // emparejarlas en columnas -- se quedan "wide" por defecto.
                // Inyección conjuntival (2026-08-20) es el mismo control
                // Sí/No pero sin campo hijo -- $campoHijoA44 resuelve null
                // para ella, $renderBooleanoPillA44 ya sabe omitir el
                // .dep-wrap en ese caso.
                $campoHijoA44 = [
                    'a44_palidez' => $campoGradoPalidezA44,
                    'a44_petequias' => $campoLocPetequiasA44,
                    'a44_equimosis' => $campoLocEquimosisA44,
                    'a44_conjuntivas_palidas' => $campoGradoPalidezConjuntivalA44,
                    'a44_escleroticas_ictericas' => $campoGradoIctericiaEscleralA44,
                    'a44_inyeccion_conjuntival' => null,
                ][$campo['clave']];
                $anchoCompletoA44 = !in_array($campo['clave'], ['a44_petequias', 'a44_equimosis'], true);
                $renderBooleanoPillA44($campo, $valor, $error, $campoHijoA44, $anchoCompletoA44);
            elseif ($campo['tipo'] === 'BOOLEANO' && in_array($campo['clave'] ?? '', $clavesBooleanoJuntoASuCampo, true)): ?>
              <div class="field">
                <?php require __DIR__ . '/campos/booleano.php'; ?>
              </div>
            <?php elseif ($campo['tipo'] === 'BOOLEANO'): ?>
              <div class="field">
                <label class="fl"><?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?></label>
                <div class="control">
                  <select name="campo_<?= (int) $campo['id'] ?>" data-nosearch="true">
                    <option value="">Seleccionar…</option>
                    <option value="1" <?= seleccionado($valor, '1') ?>>Sí</option>
                    <option value="0" <?= seleccionado($valor, '0') ?>>No</option>
                  </select>
                </div>
              </div>
            <?php elseif (($campo['clave'] ?? '') === 'a37_0_contactos_por_lugar'):
                // Ítem 62 del PDF (VII. Contactos directos): el PDF trae una
                // tabla-checklist de 6 filas fijas por tipo de lugar (Casa,
                // Nido/guardería, Colegio, Centro de trabajo, Establecimiento
                // de salud, Otro), igual que B26 en su propia página --
                // B26 ya tradujo eso a una lista dinámica "+ Agregar lugar"
                // en vez de una matriz siempre visible con las 6 filas en
                // blanco (lugar-probable-infeccion-b26.php). Se calca el
                // mismo patrón acá (más columnas: A37.0 pide 6 conteos por
                // lugar contra los 2 de B26), en vez del render genérico de
                // campos/matriz.php. Sigue siendo MATRIZ en el manifiesto
                // (igual que b26_contactos_por_lugar) solo como ancla de
                // almacenamiento; el remapeo de name= vive en
                // CasosController::validarCamposDinamicos().
                $filasLugarA370 = is_array($valor) ? $valor : [];
                $filaLugarA370 = function (array $f = ['tipo' => 'CASA', 'nombre' => '', 'direccion' => '', 'total' => '', 'con_sintomas' => '', 'esquema_completo' => '', 'esquema_incompleto' => '', 'recibieron_vacunacion' => '', 'recibieron_antibioticos' => '']): void {
                    $tipo = $f['tipo'] ?? 'CASA';
                    $esCasa = ($tipo === 'CASA');
                    ?>
                    <div class="subrow row-lugar-a370" style="margin-bottom:12px;padding:14px;border:1px solid var(--line);border-radius:8px;background:var(--card-bg, rgba(255,255,255,0.02));display:flex;">
                      <div style="flex:1">
                        <div class="fields halves" style="margin-bottom:10px">
                          <div class="field">
                            <label class="fl">Tipo de lugar</label>
                            <div class="control">
                              <select name="a370_lugar_tipo[]" class="sel-lugar-tipo-a370" data-nosearch="true">
                                <option value="CASA" <?= seleccionado($tipo, 'CASA') ?>>Casa</option>
                                <option value="NIDO_GUARDERIA" <?= seleccionado($tipo, 'NIDO_GUARDERIA') ?>>Nido / guardería</option>
                                <option value="COLEGIO" <?= seleccionado($tipo, 'COLEGIO') ?>>Colegio</option>
                                <option value="CENTRO_TRABAJO" <?= seleccionado($tipo, 'CENTRO_TRABAJO') ?>>Centro de trabajo</option>
                                <option value="ESTABLECIMIENTO_SALUD" <?= seleccionado($tipo, 'ESTABLECIMIENTO_SALUD') ?>>Establecimiento de salud</option>
                                <option value="OTROS" <?= seleccionado($tipo, 'OTROS') ?>>Otro (especificar)</option>
                              </select>
                            </div>
                          </div>
                          <div class="field">
                            <label class="fl">Nombre del lugar</label>
                            <div class="control">
                              <input type="text" name="a370_lugar_nombre[]" value="<?= e($f['nombre']) ?>" placeholder="<?= $esCasa ? '— No aplica —' : 'Nombre del lugar…' ?>" <?= $esCasa ? 'disabled' : '' ?> class="inp-lugar-nombre-a370">
                            </div>
                          </div>
                        </div>
                        <div class="fields" style="margin-bottom:10px">
                          <div class="field wide">
                            <label class="fl">Dirección</label>
                            <div class="control">
                              <?php
                              // Casa: la dirección de residencia ya se captura en
                              // "Datos personales" -- no tiene sentido repetirla
                              // (ni "Nombre del lugar", una casa no tiene nombre
                              // propio). El resto de opciones sí necesita ambos.
                              ?>
                              <input type="text" name="a370_lugar_direccion[]" value="<?= e($f['direccion']) ?>" placeholder="<?= $esCasa ? '— No aplica —' : 'Dirección del lugar…' ?>" <?= $esCasa ? 'disabled' : '' ?> class="inp-lugar-direccion-a370">
                            </div>
                          </div>
                        </div>
                        <div class="fields thirds" style="margin-bottom:10px">
                          <div class="field">
                            <label class="fl">Total</label>
                            <div class="control mono"><input type="number" min="0" step="1" name="a370_lugar_total[]" value="<?= e($f['total']) ?>" placeholder="0" style="text-align:center"></div>
                          </div>
                          <div class="field">
                            <label class="fl">Con síntomas</label>
                            <div class="control mono"><input type="number" min="0" step="1" name="a370_lugar_con_sintomas[]" value="<?= e($f['con_sintomas']) ?>" placeholder="0" style="text-align:center"></div>
                          </div>
                          <div class="field">
                            <label class="fl">Esquema de vacunación completo</label>
                            <div class="control mono"><input type="number" min="0" step="1" name="a370_lugar_esquema_completo[]" value="<?= e($f['esquema_completo']) ?>" placeholder="0" style="text-align:center"></div>
                          </div>
                        </div>
                        <div class="fields thirds" style="margin-bottom:0">
                          <div class="field">
                            <label class="fl">Esquema de vacunación incompleto</label>
                            <div class="control mono"><input type="number" min="0" step="1" name="a370_lugar_esquema_incompleto[]" value="<?= e($f['esquema_incompleto']) ?>" placeholder="0" style="text-align:center"></div>
                          </div>
                          <div class="field">
                            <label class="fl">Recibieron vacunación</label>
                            <div class="control mono"><input type="number" min="0" step="1" name="a370_lugar_recibieron_vacunacion[]" value="<?= e($f['recibieron_vacunacion']) ?>" placeholder="0" style="text-align:center"></div>
                          </div>
                          <div class="field">
                            <label class="fl">Recibieron antibióticos</label>
                            <div class="control mono"><input type="number" min="0" step="1" name="a370_lugar_recibieron_antibioticos[]" value="<?= e($f['recibieron_antibioticos']) ?>" placeholder="0" style="text-align:center"></div>
                          </div>
                        </div>
                      </div>
                      <button type="button" class="ra quitar-fila" title="Quitar lugar" style="margin-top:22px;margin-left:10px">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                      </button>
                    </div>
                    <?php
                };
                ?>
                <div class="field wide">
                  <label class="fl"><?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?></label>
                  <div class="subrows" data-lista="a370-lugar-contactos" id="listaLugarContactosA370" style="margin-top:8px">
                    <?php foreach ($filasLugarA370 as $fl): $filaLugarA370($fl); endforeach; ?>
                  </div>
                  <template id="plantilla-a370-lugar-contactos">
                    <?php $filaLugarA370(); ?>
                  </template>
                  <button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-a370-lugar-contactos" data-lista="a370-lugar-contactos" style="margin-top:10px">
                    <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    Agregar lugar
                  </button>
                  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
                </div>
            <?php else:
                require __DIR__ . '/campo-dinamico.php';
                if ($campo['etiqueta'] === 'Descripción de la erupción cutánea' || stripos($campo['etiqueta'], 'descripción de la erupción') !== false) {
                    require __DIR__ . '/campos/exantema-evolucion-body-map.php';
                }
            endif;
            if ($tieneDependencia): ?></div><?php endif;
            if ($abreWrapOtrosLabA97): ?></div><?php endif;
            if (in_array($campo['clave'] ?? '', ['a33_fecha_de_alta', 'a35_fecha_de_alta'], true)) {
                ?></div><?php // cierra el .dep-wrap de Hospitalizado=Sí abierto arriba
            }
            if (($campo['clave'] ?? '') === 'a44_diagnostico_alta_3') {
                ?></div></div><div class="fields" style="margin-bottom:<?= empty($camposBooleanos) ? '0' : '16px' ?>"><?php // cierra .fields thirds + el dep-wrap de Hospitalizado=Sí abiertos arriba, reabre .fields para el cierre final del loop
            }
            if (($campo['clave'] ?? '') === 'a37_0_fecha_de_vacunacion_tdap_de_la_madre') {
                ?></div><?php // cierra #wrapTdapMadreA370
            }
            if (($campo['clave'] ?? '') === 'a37_0_fecha_de_vacunacion_tdap_gestante') {
                ?></div><?php // cierra #wrapTdapGestanteA370
            }
            $tipoAnterior = $campo['tipo'];

            if (in_array($campo['clave'] ?? '', ['a35_dosis_5d', 'a33_dosis_5a'], true)) {
                ?></div><div class="fields" style="margin-bottom:<?= empty($camposBooleanos) ? '0' : '16px' ?>"><?php
            }
            if (($campo['clave'] ?? '') === 'a37_0_dpt_2do_refuerzo') {
                ?></div><div class="fields" style="margin-bottom:<?= empty($camposBooleanos) ? '0' : '16px' ?>"><?php
            }

            // Ítem Z.2 (PENDIENTES.md): esta clave quedó obsoleta -- la real es
            // b05_paciente_viajo_entre_los_7_a_30_dias_antes_del_inic (prefijo
            // b05_ de la convención "clave ahora autoritativa"). Como nunca
            // coincidía, este bloque no se ejecutaba: la tabla de viajes de
            // B05 no aparecía en absoluto (ni oculta, ni vacía -- el require
            // nunca corría). Corregido a la clave real.
            if (($campo['clave'] ?? '') === 'b05_paciente_viajo_entre_los_7_a_30_dias_antes_del_inic') {
                $valPacienteViajo = $valoresCampos[$campo['id']] ?? '';
                $esViajoInicial = ($valPacienteViajo === 'SI') || !empty($filasViajes ?? []);
                ?>
                </div>
                <div id="b05-wrapper-viajes-registrados" class="dep-wrap" data-depende-de="campo_<?= (int) $campo['id'] ?>" data-valor-activador="SI" style="width: 100%; flex-basis: 100%; clear: both; margin-top: 14px; margin-bottom: 18px; border-top: 1px solid var(--line-2); border-bottom: 1px solid var(--line-2); padding: 14px 0; <?= !$esViajoInicial ? 'display: none;' : '' ?>" <?= !$esViajoInicial ? 'hidden' : '' ?>>
                  <div class="eyebrow" style="margin-bottom:10px; width:100%; display:block">Si viajó, especificar antecedente de viaje</div>
                  <?php require __DIR__ . '/tablas-hijas/viajes.php'; ?>
                </div>
                <div class="fields" style="width: 100%; margin-bottom:<?= empty($camposBooleanos) ? '0' : '16px' ?>">
                <?php
            }

            // Ítem Z.2: mismo mecanismo que B05, pero P35.0 es BOOLEANO
            // (valor '1'/'0', no catálogo SI/NO/DESCONOCIDO) -- el ítem 33 del
            // PDF de SRC exige la tabla de viajes de la madre justo debajo de
            // esta pregunta, no siempre visible en "Antecedentes epidemiológicos".
            if (($campo['clave'] ?? '') === 'p35_0_durante_el_embarazo_viajo_fuera_del_pais') {
                $valViajoP350 = $valoresCampos[$campo['id']] ?? '';
                $esViajoInicialP350 = ($valViajoP350 === '1') || !empty($filasViajes ?? []);
                ?>
                </div>
                <div id="p350-wrapper-viajes-registrados" class="dep-wrap" data-depende-de="campo_<?= (int) $campo['id'] ?>" data-valor-activador="1" style="width: 100%; flex-basis: 100%; clear: both; margin-top: 14px; margin-bottom: 18px; border-top: 1px solid var(--line-2); border-bottom: 1px solid var(--line-2); padding: 14px 0; <?= !$esViajoInicialP350 ? 'display: none;' : '' ?>" <?= !$esViajoInicialP350 ? 'hidden' : '' ?>>
                  <div class="eyebrow" style="margin-bottom:10px; width:100%; display:block">Si viajó, especificar antecedente de viaje</div>
                  <?php require __DIR__ . '/tablas-hijas/viajes.php'; ?>
                </div>
                <div class="fields" style="width: 100%; margin-bottom:0">
                <?php
            }

            // A37.0 (2026-08-06): ítem 59 del PDF -- "¿Viajó en los últimos
            // 21 días?" gatea la tabla caso_viaje, mismo mecanismo que
            // B05/P35.0 arriba (antes la tabla vivía siempre visible en la
            // tarjeta fija "Antecedentes epidemiológicos"; A37.0 se excluyó
            // de esa tarjeta en nueva/index.php\/editar.php para que solo
            // aparezca acá, condicionada).
            if (($campo['clave'] ?? '') === 'a37_0_viajo_en_los_ultimos_21_dias') {
                $valViajoA370 = $valoresCampos[$campo['id']] ?? '';
                $esViajoInicialA370 = ($valViajoA370 === '1') || !empty($filasViajes ?? []);
                ?>
                </div>
                <div id="a370-wrapper-viajes-registrados" class="dep-wrap" data-depende-de="campo_<?= (int) $campo['id'] ?>" data-valor-activador="1" style="width: 100%; flex-basis: 100%; clear: both; margin-top: 14px; margin-bottom: 18px; border-top: 1px solid var(--line-2); border-bottom: 1px solid var(--line-2); padding: 14px 0; <?= !$esViajoInicialA370 ? 'display: none;' : '' ?>" <?= !$esViajoInicialA370 ? 'hidden' : '' ?>>
                  <div class="eyebrow" style="margin-bottom:10px; width:100%; display:block">Si viajó, especificar antecedente de viaje</div>
                  <?php require __DIR__ . '/tablas-hijas/viajes.php'; ?>
                </div>
                <div class="fields" style="width: 100%; margin-bottom:16px">
                <?php
            }

            // A37.0: ítem 61 del PDF -- "¿Algún miembro de la familia...?"
            // gatea la tabla caso_contacto (misma lógica que la tabla de
            // viajes arriba; antes vivía siempre visible en "Antecedentes
            // epidemiológicos", ahora condicionada acá).
            if (($campo['clave'] ?? '') === 'a37_0_algun_miembro_de_la_familia_o_persona_cercana_ha_') {
                $valFamiliarTosA370 = $valoresCampos[$campo['id']] ?? '';
                $esFamiliarTosInicialA370 = ($valFamiliarTosA370 === '1') || !empty($filasContactos ?? []);
                ?>
                </div>
                <div id="a370-wrapper-contactos-registrados" class="dep-wrap" data-depende-de="campo_<?= (int) $campo['id'] ?>" data-valor-activador="1" style="width: 100%; flex-basis: 100%; clear: both; margin-top: 14px; margin-bottom: 18px; border-top: 1px solid var(--line-2); border-bottom: 1px solid var(--line-2); padding: 14px 0; <?= !$esFamiliarTosInicialA370 ? 'display: none;' : '' ?>" <?= !$esFamiliarTosInicialA370 ? 'hidden' : '' ?>>
                  <div class="eyebrow" style="margin-bottom:10px; width:100%; display:block">Si tuvo tos prolongada, registrar el familiar/contacto</div>
                  <?php require __DIR__ . '/tablas-hijas/contactos.php'; ?>
                </div>
                <div class="fields" style="width: 100%; margin-bottom:16px">
                <?php
            }

            if (($campo['clave'] ?? '') === 'b05_total_de_casas') {
                ?>
                </div>
                <div id="b05-wrapper-cadena-transmision" style="width: 100%; flex-basis: 100%; clear: both; margin-top: 20px; margin-bottom: 24px; border-top: 1px solid var(--line-2); border-bottom: 1px solid var(--line-2); padding: 18px 0;">
                  <div class="eyebrow" style="margin-bottom:14px; font-size:0.95rem; font-weight:700; color:var(--accent-deep); width:100%; display:block;">1. CADENA DE TRANSMISIÓN</div>
                  <div class="info-callout" style="background:var(--accent-soft); border:1px solid var(--accent); border-radius:var(--radius-sm, 8px); padding:14px 18px; margin-bottom:20px; color:var(--ink); font-size:0.875rem; line-height:1.6; width:100%;">
                    <div style="margin-bottom:10px;">
                      <span style="display:inline-flex; align-items:center; gap:6px; background:var(--surface); border:1px solid var(--accent); color:var(--accent-deep); font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px; text-transform:uppercase; letter-spacing:0.5px; box-shadow:var(--shadow-soft);">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                          <circle cx="12" cy="12" r="10"></circle>
                          <line x1="12" y1="16" x2="12"></line>
                          <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        Instrucciones / Objetivo: Identificar casos secundarios
                      </span>
                    </div>
                    <div style="width:100%;">
                      <div style="color:var(--ink-2); display:flex; flex-direction:column; gap:4px; width:100%;">
                        <div><strong style="color:var(--accent-deep);">a)</strong> Tomar como referencia la fecha de inicio de erupción del caso en investigación.</div>
                        <div><strong style="color:var(--accent-deep);">b)</strong> Identificar los contactos individuales o de grupo que tuvo el caso 4 días antes y 4 días después del inicio de la erupción.</div>
                        <div><strong style="color:var(--accent-deep);">c)</strong> Registrar en orden cronológico en la siguiente tabla.</div>
                        <div><strong style="color:var(--accent-deep);">d)</strong> Programar el seguimiento de los contactos asintomáticos hasta por 30 días a partir del primer contacto con el caso. Para los que inicien erupción se apertura nueva ficha.</div>
                      </div>
                    </div>
                  </div>
                  <?php
                  $filasContactos = $filasContactos ?? [];
                  $columnasContacto = ['fecha_contacto', 'lugar_contacto', 'edad', 'direccion', 'celular', 'vacunado_72h', 'fecha_vacunacion', 'fecha_inicio_erupcion'];
                  require __DIR__ . '/tablas-hijas/contactos.php';
                  ?>
                  <div class="field" style="margin-top:16px; width:100%; max-width:320px;">
                    <label class="fl">Fecha de último día de seguimiento de contactos</label>
                    <div class="control mono">
                      <input type="date" name="<?= $campoFechaUltSeg['name'] ?>" value="<?= e($campoFechaUltSeg['val']) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
                    </div>
                  </div>
                </div>
                <div class="eyebrow" style="margin-top:20px; margin-bottom:14px; font-size:0.95rem; font-weight:700; color:var(--accent-deep); width:100%; flex-basis:100%; clear:both; display:block;">2. ACCIONES DE CONTROL</div>
                <div class="fields" style="width:100%; margin-bottom:<?= empty($camposBooleanos) ? '0' : '16px' ?>">
                <?php
            }
          endforeach; ?>
        </div>
    <?php endif;

    if (!empty($camposBooleanos)): ?>
        <div class="eyebrow" style="margin-bottom:12px">Signos y síntomas</div>
        <div class="chip-select">
          <?php foreach ($camposBooleanos as $campo):
            $campo['obligatorio'] = (int) $campo['obligatorio'];
            $valor = $valoresCampos[$campo['id']] ?? '';
            $error = $erroresCampos[$campo['id']] ?? null;
            $opciones = [];
            $tieneDependencia = !empty($campo['depende_de']);
            if ($tieneDependencia):
                $oculto = !campoVisiblePorDependencia($campo, $valoresCampos);
                ?><div class="dep-wrap" data-depende-de="campo_<?= (int) $campo['depende_de'] ?>" data-valor-activador="<?= e($campo['valor_activador']) ?>" <?= $oculto ? 'hidden' : '' ?>><?php
            endif;
            require __DIR__ . '/campo-dinamico.php';
            if ($tieneDependencia): ?></div><?php endif;
          endforeach; ?>
        </div>
    <?php endif;

    if (empty($campos)): ?>
        <p style="color:var(--muted);font-size:13px;margin:0">
          Todavía no hay campos clínicos definidos aquí. Se puede notificar igual completando notificación y persona.
        </p>
    <?php endif;
};

$rolPrevio = null;
// Bridge hacia el bloque de identidad/residencia de un rol secundario
// (PETICION_P35_RUBEOLA_CONGENITA.md Fase 2): [rol => [columna => valor]].
// Vacío en la mayoría de los llamadores (nada que prefil para una ficha
// nueva); nueva/index.php, editar.php y el endpoint AJAX lo definen.
$valoresSujetoPorRol = $valoresSujetoPorRol ?? [];

$mostrarSeparadorSujeto = function(int $seccionId) use (&$rolPrevio, $enfermedad, $valoresSujetoPorRol) {
    $campos = \App\Models\CampoDef::porSeccion($seccionId);
    $rolActual = !empty($campos) ? $campos[0]['rol_sujeto'] : 'CASO_INDICE';

    if ($rolActual !== $rolPrevio) {
        $nombreRol = ucwords(strtolower(str_replace('_', ' ', $rolActual)));
        echo '<div style="margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 2px solid var(--accent); color: var(--accent); font-weight: 600; font-size: 16px;">';
        echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: text-bottom; margin-right: 6px;"><circle cx="12" cy="7" r="4"></circle><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path></svg>';
        echo 'Sujeto: ' . htmlspecialchars($nombreRol) . '</div>';

        // Ancla el bloque de identidad/residencia justo antes de la primera
        // sección de este rol -- solo si la ficha lo declara. P96 no llega
        // acá nunca (ninguna de sus secciones cambia de rol_sujeto), así
        // que sigue en su tarjeta de siempre ("Antecedentes epidemiológicos").
        if (tieneSujeto($enfermedad['columnas_sujeto'] ?? null, $rolActual)) {
            $columnasDeclaradas = columnasSujeto($enfermedad['columnas_sujeto'] ?? null, $rolActual);
            $tituloBloque = tituloSujeto($enfermedad['titulo_sujeto'] ?? null, $rolActual);
            $valoresSujetoActual = $valoresSujetoPorRol[$rolActual] ?? [];
            echo '<div class="card section" style="margin-bottom:20px"><div class="section-body">';
            require __DIR__ . '/tablas-hijas/residencia-madre.php';
            echo '</div></div>';
        }

        $rolPrevio = $rolActual;
    }
};
?>

<?php
/**
 * Sección condicional a nivel de seccion_def (CIERRE_RECARGA_Y_FASE5.md
 * Parte 1.5): reutiliza el mismo mecanismo .dep-wrap/data-depende-de que
 * public/js/ficha.js ya aplica a campos individuales, pero envolviendo la
 * tarjeta entera de la sección en vez de un campo.
 */
$atributosDependenciaSeccion = function (array $seccion) use ($valoresCampos): string {
    if (empty($seccion['depende_de'])) {
        return '';
    }
    $oculto = !campoVisiblePorDependencia(
        ['depende_de' => $seccion['depende_de'], 'valor_activador' => $seccion['valor_activador']],
        $valoresCampos
    );
    return ' dep-wrap" data-depende-de="campo_' . (int) $seccion['depende_de'] . '" data-valor-activador="' . e($seccion['valor_activador']) . '"' . ($oculto ? ' hidden' : '');
};
?>

<?php if (!empty($secciones)): ?>
  <?php $mostrarSeparadorSujeto((int) $secciones[0]['id']); ?>
<?php endif; ?>

<?php if (empty($secciones) && ($enfermedad['cie10'] ?? '') === 'B26'): ?>
  <input type="hidden" id="numeroSiguienteSeccion" value="<?= $numeroSeccion ?>">
  <?php return; ?>
<?php endif; ?>

<div class="card section<?= $atributosDependenciaSeccion($secciones[0] ?? []) ?>">
  <div class="section-head">
    <span class="section-num"><?= $numeroSeccion ?></span>
    <h3><?= e($secciones[0]['nombre'] ?? 'Cuadro clínico') ?></h3>
    <span class="adapt">
      <svg width="12" height="12" viewBox="0 0 12 12"><path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      según <span id="adaptName"><?= e(mb_strtolower($enfermedad['nombre'])) ?></span>
    </span>
  </div>
  <div class="section-body">
    <?php // Este campo fijo siempre se ancla a $secciones[0] -- para fichas
    // con una sección propia (no clínica) ANTES de "Cuadro clínico" en el
    // manifiesto (A37.0: "Datos del paciente (adicionales)" queda de
    // $secciones[0] tras filtrar la de notificación), el genérico
    // aterrizaría ahí por error. A37.0 ya trae su propia
    // "Fecha de inicio de síntomas" (ítem 26) dentro de su Cuadro clínico
    // real -- ver mismo motivo en CasosController::crear()/actualizar().
    // B01 (2026-08-10, corrección de una decisión anterior): el usuario
    // primero pidió solo quitarle el asterisco de obligatorio (2026-08-09),
    // después revisó de nuevo y pidió ocultarlo del todo -- Varicela no lo
    // requiere en absoluto, no solo "no obligatorio". Se suma a la misma
    // lista de P35.0/A35/A37.0 en vez de mantener la rama "visible pero
    // opcional" ($fechaInicioSintomasOpcional, eliminada). En
    // CasosController.php no hace falta tocar nada:
    // $sinFechaInicioSintomasObligatoria ya incluía 'B01' y ya evitaba el
    // fallback de extraerFechaInicioSintomas() para estos 4 CIE-10 -- con
    // el campo oculto (nunca llega en $_POST), sigue guardando NULL sin
    // error, igual que P35.0/A35/A37.0.
    // A97 (2026-08-14, pedido del usuario: quitar el duplicado con "Cuadro
    // clínico") SÍ necesita tocar CasosController.php a diferencia de B01:
    // "Subsistema de vigilancia" (que va ANTES de "Cuadro clínico" en el
    // manifiesto) ahora trae su propio campo FECHA (a97_fecha_de_investigacion,
    // agregado 2026-08-13) -- si A97 solo se ocultara acá sin sumarse a
    // $sinFechaInicioSintomasObligatoria, extraerFechaInicioSintomas()
    // (que toma el primer campo FECHA en orden de sección/campo) agarraría
    // esa fecha de investigación por error, no la fecha de síntomas real. ?>
    <?php // A44 (cotejo 2026-08-18): el PDF no trae "Fecha de inicio de síntomas"
    // como campo propio -- se oculta igual que A80/B05/O95, con
    // extraerFechaInicioSintomas() derivándola de a44_fecha_de_inicio_de_enfermedad
    // (primer campo FECHA de la ficha, orden 1 de "Inicio de la enfermedad")
    // en vez de dejarla realmente opcional (CasosController.php no suma
    // 'A44' a $sinFechaInicioSintomasObligatoria). ?>
    <?php // B57 (cotejo 2026-08-21): igual que A37.0/A97, ya trae su propio
    // campo "Fecha de inicio de síntomas" (b57_fecha_de_inicio_de_sintomas,
    // ítem 2 de "Cuadro clínico") -- el genérico duplicaría el dato. No basta
    // con ocultarlo (a diferencia de A44/A80/B05/O95): "Antecedentes
    // epidemiológicos" viene ANTES que "Cuadro clínico" en el manifiesto y
    // trae 3 FECHA propias (probable de contagio, picadura, última
    // transfusión) que extraerFechaInicioSintomas() tomaría por error como
    // fallback -- por eso B57 también se suma a
    // $sinFechaInicioSintomasObligatoria en CasosController.php. ?>
    <?php // A95 (2026-08-23, pedido del usuario): el PDF (pág. 26-27) no
    // trae "Fecha de inicio de síntomas" -- "IV. CUADRO CLINICO" empieza
    // directo con la tabla de síntomas SI/NO/IGN/FECHA (a95_fiebre,
    // a95_ictericia... todas SI_NO_FECHA, no FECHA suelto). Igual que B57
    // (y a diferencia de A44/A80/B05/O95), no basta con ocultarlo acá: el
    // primer campo tipo FECHA que extraerFechaInicioSintomas() encontraría
    // es a95_fecha_de_hospitalizacion ("Hospitalización", ya que "Cuadro
    // clínico"/"Migración" no tienen ningún FECHA suelto) -- no es un
    // sustituto válido de inicio de síntomas, así que A95 también se suma a
    // $sinFechaInicioSintomasObligatoria en CasosController.php. ?>
    <?php if (!in_array(($enfermedad['cie10'] ?? null), ['A80', 'B05', 'O95', 'P35.0', 'A35', 'A37.0', 'B01', 'A97', 'A44', 'B57', 'A95'], true)): ?>
    <div class="fields" style="margin-bottom:16px">
      <div class="field">
        <label class="fl">Fecha de inicio de síntomas <span class="req">*</span></label>
        <div class="control mono <?= $errorFechaInicioSintomas ? 'err' : '' ?>">
          <input type="date" name="fecha_inicio_sintomas" value="<?= e($fechaInicioSintomas) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
        </div>
        <?php if ($errorFechaInicioSintomas): ?><span class="hint err"><?= e($errorFechaInicioSintomas) ?></span><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($secciones)): ?>
      <?php if (($enfermedad['cie10'] ?? '') === 'O95' && trim($secciones[0]['nombre'] ?? '') === 'Datos del fallecimiento (Anexo 1)'): ?>
        <?php require __DIR__ . '/datos-fallecimiento-o95.php'; ?>
      <?php else: ?>
        <?php // A97 (2026-08-14, ítem 21 del PDF pág. 49): "¿Dónde estuvo en
        // las últimas dos semanas (14 días) antes de enfermar?" es la tabla
        // de viajes, SIN booleano disparador (a diferencia de B05/P35.0/
        // A37.0) -- va siempre visible, justo antes de "Caso autóctono"
        // (ítem 28), que es $secciones[0] tras filtrar "Enfermedad/evento" y
        // "Subsistema de vigilancia" (ambas cubiertas por
        // notificacion-fechas-a97.php). Excluida de la tarjeta genérica
        // "Antecedentes epidemiológicos" en nueva/index.php/editar.php
        // ($mostrarViajes && !$isA97) para no duplicarla ahí abajo. ?>
        <?php if (($enfermedad['cie10'] ?? '') === 'A97' && trim($secciones[0]['nombre'] ?? '') === 'Antecedentes epidemiológicos'): ?>
          <div style="margin-bottom: 20px;">
            <div class="eyebrow" style="margin-bottom:10px">¿Dónde estuvo en las últimas dos semanas (14 días) antes de enfermar?</div>
            <?php require __DIR__ . '/tablas-hijas/viajes.php'; ?>
          </div>
        <?php endif; ?>
        <?php $renderizarCampos((int) $secciones[0]['id']); ?>
        <?php // A44 (cotejo 2026-08-18, pedido del usuario): "Viaje a localidades
        // o comunidades vecinas" (pág. 42 del PDF) va dentro de esta misma
        // tarjeta "Inicio de la enfermedad" ($secciones[0]), después de sus
        // propios campos y antes de "Síntomas" -- no en la tarjeta genérica
        // "Antecedentes epidemiológicos" del final. Excluida de ahí en
        // nueva/index.php/editar.php ($mostrarViajes && !$isA44), mismo
        // trato que A97 arriba. ?>
        <?php if (($enfermedad['cie10'] ?? '') === 'A44' && trim($secciones[0]['nombre'] ?? '') === 'Inicio de la enfermedad'): ?>
          <div style="margin-top: 20px;">
            <div class="eyebrow" style="margin-bottom:10px">Viaje a localidades o comunidades vecinas</div>
            <?php require __DIR__ . '/tablas-hijas/viajes.php'; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    <?php else: ?>
      <p style="color:var(--muted);font-size:13px;margin:0">
        Todavía no hay campos clínicos definidos para <?= e(mb_strtolower($enfermedad['nombre'])) ?>.
        Se puede notificar igual completando esta fecha y las secciones de notificación y persona.
      </p>
    <?php endif; ?>
  </div>
</div>
<?php $numeroSeccion++; ?>

<?php
// o95_tipo_de_ficha (Peticion 2, Agregado 1): antes no se persistia en
// ningun lado -- $valoresCampos[14300] era v99_aseguradora, de otra ficha.
// $campo(...)['val'] nunca es null (Fase 2), asi que hay que comparar con
// '' explicitamente para no cortar la cadena de fallback antes de llegar a
// $_POST (recarga de la pagina tras un error de validacion, antes de guardar).
// Mismo patrón que $campoFechaUltSeg más arriba: con $campo() lanzando
// excepción por clave inexistente, esta clave solo se pide cuando la
// ficha activa es realmente O95 -- en cualquier otra no es una clave
// faltante real, es una clave que no le corresponde a esa ficha.
$campoTipoFichaO95 = (($enfermedad['cie10'] ?? '') === 'O95')
    ? $campo('o95_tipo_de_ficha')
    : ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => [], 'campo' => null];
$valTipoFichaO95 = $valoresFijos['o95_tipo_ficha']
    ?? ($campoTipoFichaO95['val'] !== '' ? $campoTipoFichaO95['val'] : null)
    ?? $_POST['o95_tipo_ficha']
    ?? 'ANEXO_1';
foreach (array_slice($secciones, 1) as $seccion):
  $mostrarSeparadorSujeto((int) $seccion['id']);
  $esAnexo2O95 = (($enfermedad['cie10'] ?? '') === 'O95' && in_array(trim($seccion['nombre']), $O95_SECCIONES_SOLO_ANEXO_2, true));
  $claseAnexo2O95 = $esAnexo2O95 ? ' o95-anexo-2-section' : '';
  $ocultoAnexo2O95 = ($esAnexo2O95 && $valTipoFichaO95 !== 'ANEXO_2') ? ' hidden style="display:none;"' : '';
?>
  <div class="card section<?= $claseAnexo2O95 ?><?= $atributosDependenciaSeccion($seccion) ?>" <?= $ocultoAnexo2O95 ?>>
    <div class="section-head">
      <span class="section-num"><?= $numeroSeccion ?></span>
      <h3><?= e($seccion['nombre']) ?></h3>
    </div>
    <div class="section-body">
      <?php if (($enfermedad['cie10'] ?? '') === 'O95' && trim($seccion['nombre']) === 'Antecedentes patológicos y obstétricos'): ?>
        <?php require __DIR__ . '/antecedentes-patologicos-obstetricos-o95.php'; ?>
      <?php elseif (($enfermedad['cie10'] ?? '') === 'O95' && trim($seccion['nombre']) === 'Causas de defunción (Anexo 1)'): ?>
        <?php require __DIR__ . '/causas-defuncion-o95.php'; ?>
      <?php elseif (($enfermedad['cie10'] ?? '') === 'O95' && trim($seccion['nombre']) === 'Atención prenatal'): ?>
        <?php require __DIR__ . '/atencion-prenatal-o95.php'; ?>
      <?php elseif (($enfermedad['cie10'] ?? '') === 'O95' && trim($seccion['nombre']) === 'Complicaciones'): ?>
        <?php require __DIR__ . '/complicaciones-o95.php'; ?>
      <?php elseif (($enfermedad['cie10'] ?? '') === 'O95' && trim($seccion['nombre']) === 'Referencia (Anexo 1)'): ?>
        <?php require __DIR__ . '/referencia-o95.php'; ?>
      <?php elseif (($enfermedad['cie10'] ?? '') === 'O95' && trim($seccion['nombre']) === 'Hospitalizaciones'): ?>
        <?php require __DIR__ . '/hospitalizaciones-o95.php'; ?>
      <?php elseif (($enfermedad['cie10'] ?? '') === 'O95' && trim($seccion['nombre']) === 'Parto o aborto'): ?>
        <?php require __DIR__ . '/parto-aborto-o95.php'; ?>
      <?php elseif (($enfermedad['cie10'] ?? '') === 'O95' && trim($seccion['nombre']) === 'Entorno social y comunitario'): ?>
        <?php require __DIR__ . '/entorno-social-o95.php'; ?>
      <?php elseif (($enfermedad['cie10'] ?? '') === 'O95' && trim($seccion['nombre']) === 'Datos comunitarios'): ?>
        <?php require __DIR__ . '/datos-comunitarios-o95.php'; ?>
      <?php elseif (($enfermedad['cie10'] ?? '') === 'O95' && trim($seccion['nombre']) === 'Las cuatro demoras'): ?>
        <?php require __DIR__ . '/demoras-o95.php'; ?>
      <?php elseif (trim($seccion['nombre']) === 'Cadena de transmisión'): ?>
        <?php if (($enfermedad['cie10'] ?? '') !== 'B05'): ?>
          <div class="info-callout" style="background:var(--accent-soft); border:1px solid var(--accent); border-radius:var(--radius-sm, 8px); padding:14px 18px; margin-bottom:20px; color:var(--ink); font-size:0.875rem; line-height:1.6;">
            <div style="margin-bottom:10px;">
              <span style="display:inline-flex; align-items:center; gap:6px; background:var(--surface); border:1px solid var(--accent); color:var(--accent-deep); font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px; text-transform:uppercase; letter-spacing:0.5px; box-shadow:var(--shadow-soft);">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="16" x2="12" y2="12"></line>
                  <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                Instrucciones / Objetivo
              </span>
            </div>
            <div style="width:100%;">
              <div style="font-weight:600; color:var(--accent-deep); margin-bottom:6px; font-size:0.9rem;">
                Identificar el caso primario, luego:
              </div>
              <div style="color:var(--ink-2); display:flex; flex-direction:column; gap:4px; width:100%;">
                <div><strong style="color:var(--accent-deep);">a)</strong> Tomar como referencia la fecha de inicio de parálisis del caso.</div>
                <div><strong style="color:var(--accent-deep);">b)</strong> Identificar los contactos individuales o de grupo que tuvo el caso 45 días antes y 45 días después del inicio de la parálisis.</div>
                <div><strong style="color:var(--accent-deep);">c)</strong> Enumerar en orden cronológico en la siguiente tabla.</div>
                <div><strong style="color:var(--accent-deep);">d)</strong> Programar el seguimiento de los contactos asintomáticos hasta por 60 días a partir de su captación (para los que inician parálisis se apertura nueva ficha).</div>
              </div>
            </div>
          </div>
          <?php
          $filasContactos = $filasContactos ?? [];
          $columnasContacto = ['edad', 'dosis', 'fecha_ultima_dosis', 'fecha_colecta_heces', 'fecha_envio', 'fecha_resultado', 'resultado_aislamiento'];
          require __DIR__ . '/tablas-hijas/contactos.php';
          ?>
        <?php endif; ?>
      <?php else: ?>
        <?php if (trim($seccion['nombre']) === 'Lugar probable de infección' && ($enfermedad['cie10'] ?? '') === 'B05'): ?>
          <div style="margin-bottom: 20px;">
            <div class="eyebrow" style="margin-bottom:10px">Lugar y/o institución probable de infección (considerar entre 7 a 30 días antes del inicio de erupción cutánea)</div>
            <?php require __DIR__ . '/tablas-hijas/lugar-infeccion.php'; ?>
          </div>
        <?php endif; ?>

        <?php
        // "Laboratorio y evolución" (A44) no tiene NUNCA campo_def propios
        // (solo_tabla_hija, ver manifiesto) -- llamar a $renderizarCampos()
        // acá solo serviría para imprimir el placeholder "Todavía no hay
        // campos clínicos definidos aquí", que no aplica: el contenido real
        // vive en las tablas hijas de más abajo.
        $esLaboratorioEvolucionA44 = trim($seccion['nombre']) === 'Laboratorio y evolución' && ($enfermedad['cie10'] ?? '') === 'A44';
        if (!$esLaboratorioEvolucionA44): ?>
          <?php $renderizarCampos((int) $seccion['id']); ?>
        <?php endif; ?>

        <?php if (trim($seccion['nombre']) === 'Laboratorio' && ($enfermedad['cie10'] ?? '') === 'A95'): ?>
          <div style="margin-top: 18px; border-top: 1px solid var(--line-2); padding-top: 14px;">
            <div class="eyebrow" style="margin-bottom:10px">Muestras</div>
            <?php require __DIR__ . '/tablas-hijas/muestras.php'; ?>
          </div>
        <?php endif; ?>

        <?php if (trim($seccion['nombre']) === 'Antecedentes vacunales' && ($enfermedad['cie10'] ?? '') === 'B05'):
          $valEstadoVacunal = $campo('b05_estado_vacunal')['val'];
          $esVacunadoInicial = in_array($valEstadoVacunal, ['VACUNADO', 'VACUNADO_INCOMPLETO'], true) || !empty($filasVacunas ?? []);
        ?>
          <div id="b05-wrapper-vacunas-registradas" style="margin-top: 18px; border-top: 1px solid var(--line-2); padding-top: 14px; <?= !$esVacunadoInicial ? 'display: none;' : '' ?>" <?= !$esVacunadoInicial ? 'hidden' : '' ?>>
            <div class="eyebrow" style="margin-bottom:10px">Vacunas registradas</div>
            <?php require __DIR__ . '/tablas-hijas/vacunas.php'; ?>
          </div>
        <?php endif; ?>

        <?php if ($esLaboratorioEvolucionA44):
          $filasEvolucion = $filasEvolucion ?? [];
          $erroresEvolucion = $erroresEvolucion ?? [];
          $filasExamen = $filasExamen ?? [];
          $erroresExamen = $erroresExamen ?? [];
        ?>
          <div style="margin-bottom: 24px;">
            <div class="eyebrow" style="margin-bottom:10px">Evolución clínica</div>
            <?php require __DIR__ . '/tablas-hijas/evolucion.php'; ?>
          </div>
          <div>
            <div class="eyebrow" style="margin-bottom:10px">Exámenes auxiliares</div>
            <?php require __DIR__ . '/tablas-hijas/examenes-auxiliares.php'; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <?php $numeroSeccion++; ?>
<?php endforeach; ?>
<input type="hidden" id="numeroSiguienteSeccion" value="<?= $numeroSeccion ?>">
