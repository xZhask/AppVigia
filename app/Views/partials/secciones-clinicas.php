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
    'P35.0' => ['Datos de notificación e investigación del caso'],
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

$renderizarCampos = function (int $seccionId) use (&$opcionesPorCatalogo, $valoresCampos, $erroresCampos, $enfermedad, $campoFechaUltSeg, $claveCubiertaPorPartial): void {
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
    $camposBooleanos = $esP350 ? [] : array_filter($campos, fn($c) => $c['tipo'] === 'BOOLEANO' && !in_array($c['id'], $idsPadre));
    $camposOtros = $esP350 ? $campos : array_filter($campos, fn($c) => $c['tipo'] !== 'BOOLEANO' || in_array($c['id'], $idsPadre));

    if (!empty($camposOtros)): ?>
        <div class="fields" style="margin-bottom:<?= empty($camposBooleanos) ? '0' : '16px' ?>">
          <?php
          $tipoAnterior = null;
          foreach ($camposOtros as $campo):
            if (($campo['clave'] ?? '') === 'b05_fecha_de_ultimo_dia_de_seguimiento_de_contactos') {
                continue;
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
            $tieneDependencia = !empty($campo['depende_de']);
            if ($tieneDependencia):
                $oculto = !campoVisiblePorDependencia($campo, $valoresCampos);
                ?><div class="dep-wrap" data-depende-de="campo_<?= (int) $campo['depende_de'] ?>" data-valor-activador="<?= e($campo['valor_activador']) ?>" <?= $oculto ? 'hidden' : '' ?>><?php
            endif;
            if ($campo['tipo'] === 'BOOLEANO'): ?>
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
            <?php else:
                require __DIR__ . '/campo-dinamico.php';
                if ($campo['etiqueta'] === 'Descripción de la erupción cutánea' || stripos($campo['etiqueta'], 'descripción de la erupción') !== false) {
                    require __DIR__ . '/campos/exantema-evolucion-body-map.php';
                }
            endif;
            if ($tieneDependencia): ?></div><?php endif;
            $tipoAnterior = $campo['tipo'];

            if (($campo['clave'] ?? '') === 'paciente_viajo_7_30_dias') {
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
        <div class="sym-grid">
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
    <?php if (!in_array(($enfermedad['cie10'] ?? null), ['A80', 'B05', 'O95', 'P35.0'], true)): ?>
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
        <?php $renderizarCampos((int) $secciones[0]['id']); ?>
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

        <?php $renderizarCampos((int) $seccion['id']); ?>

        <?php if (trim($seccion['nombre']) === 'Antecedentes vacunales' && ($enfermedad['cie10'] ?? '') === 'B05'): 
          $valEstadoVacunal = $campo('b05_estado_vacunal')['val'];
          $esVacunadoInicial = in_array($valEstadoVacunal, ['VACUNADO', 'VACUNADO_INCOMPLETO'], true) || !empty($filasVacunas ?? []);
        ?>
          <div id="b05-wrapper-vacunas-registradas" style="margin-top: 18px; border-top: 1px solid var(--line-2); padding-top: 14px; <?= !$esVacunadoInicial ? 'display: none;' : '' ?>" <?= !$esVacunadoInicial ? 'hidden' : '' ?>>
            <div class="eyebrow" style="margin-bottom:10px">Vacunas registradas</div>
            <?php require __DIR__ . '/tablas-hijas/vacunas.php'; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <?php $numeroSeccion++; ?>
<?php endforeach; ?>
<input type="hidden" id="numeroSiguienteSeccion" value="<?= $numeroSeccion ?>">
