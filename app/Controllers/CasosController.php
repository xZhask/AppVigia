<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\ConflictoInteresException;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\ReniecService;
use App\Models\CampoDef;
use App\Models\Caso;
use App\Models\CasoBitacora;
use App\Models\CasoContacto;
use App\Models\CasoContactoDirecto;
use App\Models\CasoEvolucion;
use App\Models\CasoExamenAuxiliar;
use App\Models\CasoLugarInfeccion;
use App\Models\CasoMuestra;
use App\Models\CasoSujeto;
use App\Models\CasoValor;
use App\Models\CasoVacuna;
use App\Models\CasoViaje;
use App\Models\CatalogoItem;
use App\Models\Enfermedad;
use App\Models\Establecimiento;
use App\Models\GradoPnp;
use App\Models\Distrito;
use App\Models\Persona;
use App\Services\PersonaService;
use App\Models\ReniecConsulta;
use App\Models\SeccionDef;
use DateTime;
use Throwable;

class CasosController extends Controller
{
    private const ROLES_REGISTRO = ['ADMIN', 'REGISTRADOR'];
    private const ROLES_CIERRE = ['ADMIN'];
    private const POR_PAGINA = 20;

    public function index(): void
    {
        $usuario = Auth::usuario();

        $filtros = [
            'q'             => trim($_GET['q'] ?? ''),
            'enfermedad_id' => $_GET['enfermedad_id'] ?? '',
            'clasificacion' => $_GET['clasificacion'] ?? '',
            'estado'        => $_GET['estado'] ?? '',
            'desde'         => fechaIsoValida(trim($_GET['desde'] ?? '')) ?? '',
            'hasta'         => fechaIsoValida(trim($_GET['hasta'] ?? '')) ?? '',
            'privacidad_rol' => $usuario['rol'],
            'privacidad_usuario_id' => $usuario['id'],
        ];

        if ($usuario['rol'] === 'REGISTRADOR') {
            $filtros['establecimiento_id'] = $usuario['establecimiento_id'];
        }

        $pagina = max(1, (int) ($_GET['page'] ?? 1));
        $resultado = Caso::listarPaginado($filtros, $pagina, self::POR_PAGINA);

        $this->vista('fichas/index', [
            'tituloVista'  => 'Fichas registradas',
            'rutaActual'   => 'casos',
            'fichas'       => $resultado['filas'],
            'total'        => $resultado['total'],
            'pagina'       => $resultado['pagina'],
            'totalPaginas' => $resultado['totalPaginas'],
            'filtros'      => $_GET,
            'enfermedades' => Enfermedad::todos('nombre'),
        ]);
    }

    public function nuevo(): void
    {
        Auth::exigirRol(...self::ROLES_REGISTRO);

        $enfermedades = Enfermedad::activasConDefinicion();
        if (empty($enfermedades)) {
            Flash::set('No hay enfermedades activas para notificar. Actívalas desde Catálogos › Enfermedades.');
            header('Location: /');
            exit;
        }

        $enfermedadId = isset($_GET['enfermedad_id']) ? (int) $_GET['enfermedad_id'] : (int) $enfermedades[0]['id'];
        $enfermedad = Enfermedad::buscar($enfermedadId) ?: $enfermedades[0];

        $hoyIso = (new DateTime())->format('Y-m-d');
        $semana = semanaEpidemiologica($hoyIso);
        $valoresFijosIniciales = $this->valoresFijosPorDefecto($hoyIso);

        // nucleo_ajustes.registrar_y_agregar_otra (P96, 2026-09-14): el botón
        // "Registrar y agregar otra" vuelve acá con el establecimiento y la
        // fecha de notificación de la ficha que acaba de guardar. Solo se
        // precargan si siguen siendo válidos (fecha no futura; establecimiento
        // existente y elegible por el usuario); al guardar se revalidan igual.
        if (nucleoAjuste($enfermedad, 'registrar_y_agregar_otra') === true) {
            $fechaNotifArrastrada = is_string($_GET['fecha_notif'] ?? null) ? fechaIsoValida($_GET['fecha_notif']) : null;
            if ($fechaNotifArrastrada !== null && $fechaNotifArrastrada <= $hoyIso) {
                $valoresFijosIniciales['fecha_notif'] = $fechaNotifArrastrada;
                $semana = semanaEpidemiologica($fechaNotifArrastrada);
            }
            $establecimientoArrastrado = (int) (is_string($_GET['establecimiento_id'] ?? null) ? $_GET['establecimiento_id'] : 0);
            if ($establecimientoArrastrado > 0 && Auth::usuario()['rol'] === 'ADMIN' && Establecimiento::buscar($establecimientoArrastrado)) {
                $valoresFijosIniciales['establecimiento_id'] = (string) $establecimientoArrastrado;
            }
        }

        // vinculo_caso (cotejo Z21, 2026-09-11): el botón "Registrar niño
        // nacido expuesto" de la ficha de la madre entra acá como
        // ?vinculo=<id>. Si es un candidato real y visible para este usuario,
        // el formulario abre directamente en la rama correcta (con el campo
        // activador ya elegido) y con los datos de la madre copiados.
        $configVinculoNuevo = $this->configVinculoCaso($enfermedad);
        $vinculoSeleccionado = null;
        $valoresCamposIniciales = [];
        if ($configVinculoNuevo && !empty($_GET['vinculo'])) {
            $idVinculoPedido = (int) $_GET['vinculo'];
            foreach ($this->candidatosVinculo($enfermedad, $configVinculoNuevo, null) as $candidatoNuevo) {
                if ($candidatoNuevo['id'] !== $idVinculoPedido) {
                    continue;
                }
                $vinculoSeleccionado = $idVinculoPedido;
                $valoresCamposIniciales[(int) $configVinculoNuevo['_activador']['id']] = (string) $configVinculoNuevo['activador']['valor'];
                foreach ($candidatoNuevo['copiar_por_nombre'] as $nombreDestino => $valorCopiado) {
                    $valoresCamposIniciales[(int) substr($nombreDestino, strlen('campo_'))] = $valorCopiado;
                }
                break;
            }
        }

        $this->vista('nueva/index', array_merge([
            'tituloVista'   => 'Nueva ficha de notificación',
            'rutaActual'    => 'casos/nuevo',
            'enfermedades'  => $enfermedades,
            'enfermedad'    => $enfermedad,
            'valoresFijos'  => $valoresFijosIniciales,
            'erroresFijos'  => [],
            'semanaEpiPreview' => $semana['semana'],
            'anioEpiPreview'   => $semana['anio'],
            'valoresCampos' => $valoresCamposIniciales,
            'erroresCampos' => [],
            'fechaInicioSintomas' => '',
            'errorFechaInicioSintomas' => null,
            'clasificacionActual' => clasificacionRequiereEleccionExplicita($enfermedad) ? '' : opcionesClasificacionPara($enfermedad)[0],
            'filasContactos' => [],
            'filasContactosDirectos' => [],
            'filasViajes'    => [],
            'filasVacunas'   => [],
            'filasMuestras'  => [],
            'filasBloquesMuestra' => [],
            'filasLugarInfeccion' => [],
            'filasEvolucion' => [],
            'filasExamen'    => [],
            'erroresViajes'  => [],
            'erroresVacunas' => [],
            'erroresMuestras' => [],
            'erroresLugarInfeccion' => [],
            'erroresEvolucion' => [],
            'erroresExamen'  => [],
            'valoresSujetoPorRol' => [],
        ], $this->datosVinculoCasoVista($enfermedad, $vinculoSeleccionado, null), $this->datosEstablecimiento(), $this->datosPnp($enfermedad), $this->datosMuestrasCatalogo($enfermedad), $this->datosVacunasCatalogo(), $this->datosColumnasTablaHija($enfermedad), contextoUbigeo(null)));
    }

    public function crear(): void
    {
        Auth::exigirRol(...self::ROLES_REGISTRO);
        $this->exigirCsrf();

        $usuario = Auth::usuario();
        $puedeElegirEstablecimiento = $usuario['rol'] === 'ADMIN';

        $enfermedades = Enfermedad::activasConDefinicion();
        $enfermedadId = (int) ($_POST['enfermedad_id'] ?? 0);
        $enfermedad = Enfermedad::buscar($enfermedadId);

        if (!$enfermedad || !$enfermedad['activo']) {
            Flash::set('Selecciona una enfermedad válida.');
            header('Location: /casos/nuevo');
            exit;
        }

        // ---------- fijos: notificación + paciente ----------
        $erroresFijos = [];
        $valoresFijos = [
            'establecimiento_id' => $puedeElegirEstablecimiento
                ? ($_POST['establecimiento_id'] ?? '')
                : (string) ($usuario['establecimiento_id'] ?? ''),
            'fecha_notif'        => trim($_POST['fecha_notif'] ?? ''),
            'tipo_doc'           => $_POST['tipo_doc'] ?? 'DNI',
            'num_doc'            => trim($_POST['num_doc'] ?? ''),
            'apellido_paterno'   => trim($_POST['apellido_paterno'] ?? ''),
            'apellido_materno'   => trim($_POST['apellido_materno'] ?? ''),
            'nombres'            => trim($_POST['nombres'] ?? ''),
            'sexo'               => $_POST['sexo'] ?? '',
            'fecha_nac'          => trim($_POST['fecha_nac'] ?? ''),
            'fecha_nac_desconocida' => ($_POST['fecha_nac_desconocida'] ?? '') === '1' ? '1' : '',
            'nacimiento_distrito_id' => $_POST['nacimiento_distrito_id'] ?? '',
            'edad_valor'         => trim($_POST['edad_valor'] ?? ''),
            'edad_unidad'        => $_POST['edad_unidad'] ?? '',
            'celular'            => trim($_POST['celular'] ?? ''),
            'nacionalidad'       => trim($_POST['nacionalidad'] ?? '') ?: 'Peruana',
            'direccion'          => trim($_POST['direccion'] ?? ''),
            'referencia_localizar' => trim($_POST['referencia_localizar'] ?? ''),
            'tipo_zona'          => $_POST['tipo_zona'] ?? '',
            'nombre_zona'        => trim($_POST['nombre_zona'] ?? ''),
            'tipo_via'           => trim($_POST['tipo_via'] ?? ''),
            'nombre_via'         => trim($_POST['nombre_via'] ?? ''),
            'numero'             => trim($_POST['numero'] ?? ''),
            'mz_lote'            => trim($_POST['mz_lote'] ?? ''),
            'tiempo_residencia'  => trim($_POST['tiempo_residencia'] ?? ''),
            'tiempo_reside_anios' => trim($_POST['tiempo_reside_anios'] ?? ''),
            'tiempo_reside_meses' => trim($_POST['tiempo_reside_meses'] ?? ''),
            'anterior_distrito_id' => $_POST['anterior_distrito_id'] ?? '',
            'anterior_tipo_zona'   => $_POST['anterior_tipo_zona'] ?? '',
            'anterior_nombre_zona' => trim($_POST['anterior_nombre_zona'] ?? ''),
            'anterior_tipo_via'    => trim($_POST['anterior_tipo_via'] ?? ''),
            'anterior_nombre_via'  => trim($_POST['anterior_nombre_via'] ?? ''),
            'anterior_numero'      => trim($_POST['anterior_numero'] ?? ''),
            'anterior_mz_lote'     => trim($_POST['anterior_mz_lote'] ?? ''),
            'lugar_contagio_distrito_id' => $_POST['lugar_contagio_distrito_id'] ?? '',
            'lugar_contagio_localidad'   => trim($_POST['lugar_contagio_localidad'] ?? ''),
            'n_historia_clinica' => trim($_POST['n_historia_clinica'] ?? ''),
            'localidad'          => trim($_POST['localidad'] ?? ''),
            'etnia'              => $_POST['etnia'] ?? '',
            'etnia_otra'         => trim($_POST['etnia_otra'] ?? ''),
            'pueblo_etnico'      => $_POST['pueblo_etnico'] ?? '',
            'ocupacion'          => trim($_POST['ocupacion'] ?? ''),
            'estado_civil'       => $_POST['estado_civil'] ?? '',
            'nombre_tutor'       => trim($_POST['nombre_tutor'] ?? ''),
            'celular_tutor'      => trim($_POST['celular_tutor'] ?? ''),
            'gestante'           => $_POST['gestante'] ?? '',
            'fur'                => trim($_POST['fur'] ?? ''),
            'semanas_gestacion'  => trim($_POST['semanas_gestacion'] ?? ''),
            'trimestre_gestacion'=> $_POST['trimestre_gestacion'] ?? '',
            'tipo_captacion'         => $_POST['tipo_captacion'] ?? '',
            'lugar_captacion'        => $_POST['lugar_captacion'] ?? '',
            'clasificacion_captacion' => $_POST['clasificacion_captacion'] ?? '',
            'investigador_nombre'    => trim($_POST['investigador_nombre'] ?? ''),
            'investigador_cargo'     => trim($_POST['investigador_cargo'] ?? ''),
            'investigador_profesion' => trim($_POST['investigador_profesion_sel'] ?? '') === 'Otro' ? trim($_POST['investigador_profesion_otra'] ?? '') : trim($_POST['investigador_profesion_sel'] ?? ''),
            'investigador_profesion_otra' => trim($_POST['investigador_profesion_otra'] ?? ''),
            'investigador_telefono'  => trim($_POST['investigador_telefono'] ?? ''),
            'investigador_email'     => trim($_POST['investigador_email'] ?? ''),
            'fecha_investigacion'    => trim($_POST['fecha_investigacion'] ?? ''),
        ];

        $establecimiento = $valoresFijos['establecimiento_id'] !== ''
            ? Establecimiento::buscar((int) $valoresFijos['establecimiento_id'])
            : null;
        if (!$establecimiento) {
            $erroresFijos['establecimiento_id'] = $puedeElegirEstablecimiento
                ? 'Selecciona un establecimiento.'
                : 'Tu cuenta no tiene un establecimiento asignado; pide a un administrador que lo configure.';
        }

        if ($valoresFijos['fecha_notif'] === '') {
            $valoresFijos['fecha_notif'] = $this->extraerFechaNotificacion((int) $enfermedad['id']);
        }
        $fechaNotifIso = fechaIsoValida($valoresFijos['fecha_notif']);
        if (!$fechaNotifIso) {
            $erroresFijos['fecha_notif'] = 'Ingresa una fecha de notificación válida.';
        } elseif ($fechaNotifIso > (new DateTime())->format('Y-m-d')) {
            $erroresFijos['fecha_notif'] = 'La fecha de notificación no puede ser futura.';
        }

        // nucleo_ajustes.sin_documento (P96, muerte fetal y neonatal,
        // 2026-09-13): con la casilla "Sin documento de identidad" marcada la
        // persona se guarda como SIN_DOCUMENTO, sin número (NULL, nunca '':
        // Persona::buscarPorDocumento() con '' encontraría a OTRA persona sin
        // documento y la sobrescribiría). En las fichas que no declaran el
        // ajuste la casilla no existe y un POST forjado no cambia nada.
        // Ajustes por rama (A50, 2026-09-14): la rama elegida sale del POST.
        $valoresRamaPost = $this->valoresCamposDesdePost();
        $sinDocumento = nucleoAjuste($enfermedad, 'sin_documento', $valoresRamaPost) === true && ($_POST['sin_documento'] ?? '') === '1';
        if ($sinDocumento) {
            $valoresFijos['tipo_doc'] = 'SIN_DOCUMENTO';
            $valoresFijos['num_doc'] = '';
        }
        if ($valoresFijos['num_doc'] === '' && !$sinDocumento) {
            $erroresFijos['num_doc'] = 'Ingresa el número de documento.';
        }
        if ($valoresFijos['apellido_paterno'] === '') {
            $erroresFijos['apellido_paterno'] = 'Ingresa el apellido paterno.';
        }
        // nucleo_ajustes.nombres_opcionales (P96): un óbito fetal casi nunca
        // tiene nombre; se exigen solo los apellidos.
        if ($valoresFijos['nombres'] === '' && nucleoAjuste($enfermedad, 'nombres_opcionales', $valoresRamaPost) !== true) {
            $erroresFijos['nombres'] = 'Ingresa los nombres.';
        }

        // fecha_nac_desconocida (A50, 2026-09-14): con "Desconocido" marcado,
        // y solo si la rama elegida lo ofrece, la fecha no se guarda aunque
        // llegue en el POST; sin la casilla, la marca no se guarda.
        $valoresFijos['fecha_nac_desconocida'] = ($valoresFijos['fecha_nac_desconocida'] === '1'
            && nucleoAjuste($enfermedad, 'fecha_nac_desconocida', $valoresRamaPost) === true) ? '1' : '';
        if ($valoresFijos['fecha_nac_desconocida'] === '1') {
            $valoresFijos['fecha_nac'] = '';
        }

        $fechaNacIso = null;
        if ($valoresFijos['fecha_nac'] !== '') {
            $fechaNacIso = fechaIsoValida($valoresFijos['fecha_nac']);
            if (!$fechaNacIso) {
                $erroresFijos['fecha_nac'] = 'Ingresa una fecha de nacimiento válida.';
            }
        }

        // nucleo_condicional: 'residencia' (cotejo Z21, 2026-09-11) -- el
        // distrito solo es obligatorio si la rama elegida pide residencia
        // habitual (la Sección II del PDF, el niño nacido expuesto, no la
        // pide). Cuando no aplica, no se guarda aunque venga en el POST.
        $residenciaActivaCrear = $this->bloqueNucleoActivoDesdePost($enfermedad, 'residencia');
        $distritoId = $residenciaActivaCrear ? ($_POST['distrito_id'] ?? '') : '';
        if ($distritoId === '' && $residenciaActivaCrear) {
            $erroresFijos['distrito_id'] = 'Selecciona el distrito de domicilio.';
        }

        // P35.0 no pide "fecha de inicio de síntomas" en el PDF (SRC es
        // congénito, no un cuadro con inicio agudo) -- ni la muestra el
        // formulario ni la exige el servidor; caso.fecha_inicio_sintomas
        // queda NULL para esta ficha, columna ya nullable. A35 (Tétanos)
        // se suma por el mismo motivo: su propia "Fecha de inicio de
        // lesión" no es obligatoria en el manifiesto (día exacto no
        // siempre se conoce, de ahí "No recuerda día") y no hay un solo
        // campo estándar equivalente -- forzar aquí un valor la haría
        // obligatoria por la puerta de atrás. A37.0 se suma por un motivo
        // distinto: sí tiene un campo estándar único y siempre determinable
        // (ítem 26 del PDF), pero vive en su propio campo_def dentro de
        // "Cuadro clínico" -- el genérico no puede mostrarse ahí porque
        // secciones-clinicas.php lo ancla a $secciones[0], que para A37.0
        // es "Datos del paciente (adicionales)" (queda antes de Cuadro
        // clínico en el manifiesto). Exigir el genérico duplicaría el dato
        // y, peor, extraerFechaInicioSintomas() tomaría la primera FECHA
        // enviada (probablemente "Fecha de conocimiento local del caso" de
        // la cabecera de notificación), no la fecha de síntomas real.
        // B01 (2026-08-10): igual que los 3 anteriores, el campo está oculto
        // del todo en secciones-clinicas.php -- Varicela no lo requiere en
        // absoluto (no solo "no obligatorio", decisión revisada el
        // 2026-08-09 y corregida al día siguiente por el usuario). No hace
        // falta ninguna otra rama acá: al no llegar en $_POST, sigue
        // guardando NULL sin error, mismo camino que P35.0/A35/A37.0.
        // A97 (2026-08-14): ver el comentario largo en secciones-clinicas.php
        // -- "Subsistema de vigilancia" trae su propio campo FECHA antes que
        // "Cuadro clínico" en el manifiesto, así que el fallback de
        // extraerFechaInicioSintomas() (primer campo FECHA en orden) agarraría
        // esa fecha por error si A97 no estuviera en esta lista.
        // A95 (2026-08-23, pedido del usuario: quitar el genérico
        // hardcodeado de "Cuadro clínico" -- el PDF pág. 26-27 no lo trae,
        // "IV. CUADRO CLINICO" empieza directo con la tabla de síntomas
        // SI/NO/IGN/FECHA): a diferencia de A44 (cuyo primer campo FECHA es
        // "Fecha de inicio de enfermedad", un sustituto razonable),
        // extraerFechaInicioSintomas() encontraría primero
        // a95_fecha_de_hospitalizacion ("Hospitalización" es la 1.ª sección
        // con un campo tipo FECHA -- "Cuadro clínico"/"Migración" no tienen
        // ninguno) -- fecha de hospitalización no es un sustituto válido de
        // inicio de síntomas (ni siquiera existe si el paciente no fue
        // hospitalizado), así que A95 se suma acá en vez de dejar que el
        // fallback la capture por error.
        // B04X (cotejo 2026-08-29): mismo motivo que A97/B57/A95 -- su
        // propia "Fecha de inicio de síntomas (FIS)" vive en "Cuadro
        // clínico" (orden 5), pero "Antecedentes" (orden 4, ANTES) trae un
        // FECHA suelto (b04x_fecha_de_diagnostico_vih) que
        // extraerFechaInicioSintomas() agarraría por error como fallback.
        // nucleo_omitidos: 'fecha_inicio_sintomas' (cotejo Z21, 2026-09-11) es
        // la versión declarativa de esta lista de CIE-10. Con el bloque
        // omitido el valor se descarta aunque venga forzado en el POST: el
        // campo ni siquiera se pinta.
        $fechaInicioSintomasOmitida = nucleoOmitido($enfermedad, 'fecha_inicio_sintomas');
        $sinFechaInicioSintomasObligatoria = $fechaInicioSintomasOmitida
            || in_array($enfermedad['cie10'] ?? '', ['P35.0', 'A35', 'A37.0', 'B01', 'A97', 'B57', 'A95', 'B55', 'B04X', 'A00'], true);
        $fechaInicioSintomas = $fechaInicioSintomasOmitida ? '' : trim($_POST['fecha_inicio_sintomas'] ?? '');
        if ($fechaInicioSintomas === '' && !$sinFechaInicioSintomasObligatoria) {
            $fechaInicioSintomas = $this->extraerFechaInicioSintomas((int) $enfermedad['id']);
        }
        $fechaInicioSintomasIso = null;
        $errorFechaInicioSintomas = null;
        if ($fechaInicioSintomas === '') {
            if (!$sinFechaInicioSintomasObligatoria) {
                $errorFechaInicioSintomas = 'Ingresa la fecha de inicio de síntomas.';
            }
        } else {
            $fechaInicioSintomasIso = fechaIsoValida($fechaInicioSintomas);
            if (!$fechaInicioSintomasIso) {
                $errorFechaInicioSintomas = 'Ingresa una fecha de inicio de síntomas válida.';
            }
        }

        // ---------- efectivo PNP (opcional) ----------
        $datosPnp = $this->leerDatosPnp($enfermedad, $valoresRamaPost);
        if ($datosPnp['error'] !== null) {
            $erroresFijos['condicion'] = $datosPnp['error'];
        }
        // nucleo_ajustes.condiciones_paciente (P96, 2026-09-14): si el documento
        // ya es de una persona registrada con una condición que la ficha no
        // admite (un efectivo PNP en la ficha de un fallecido fetal o
        // neonatal), es un número mal digitado; guardar sobrescribiría a esa
        // persona con los datos de esta ficha.
        if ((nucleoAjuste($enfermedad, 'condiciones_paciente') !== null || reglasAjusteNucleo($enfermedad, 'condiciones_paciente'))
            && !$sinDocumento && !isset($erroresFijos['num_doc'])) {
            $personaDelDocumento = Persona::buscarPorDocumento($valoresFijos['tipo_doc'], $valoresFijos['num_doc']);
            $condicionDelDocumento = $personaDelDocumento['condicion'] ?? 'PARTICULAR';
            if ($personaDelDocumento && !in_array($condicionDelDocumento, condicionesPacientePermitidas($enfermedad, $valoresRamaPost), true)) {
                $erroresFijos['num_doc'] = 'Este documento ya está registrado con la condición «' . (CONDICIONES_PACIENTE[$condicionDelDocumento] ?? $condicionDelDocumento)
                    . '», que no corresponde a esta ficha. Revisa el número.';
            }
        }

        // ---------- clasificación del caso ----------
        // nucleo_omitidos: 'clasificacion' (cotejo Z21) -- una ficha cuyo PDF
        // no clasifica el caso no pinta la tarjeta y guarda el valor por
        // defecto de su propia lista, sin mirar el POST.
        $opcionesClasificacion = opcionesClasificacionPara($enfermedad);
        $clasificacionOmitida = nucleoOmitido($enfermedad, 'clasificacion');
        $clasificacionRequerida = !$clasificacionOmitida && clasificacionRequiereEleccionExplicita($enfermedad);
        $clasificacion = $clasificacionOmitida
            ? $opcionesClasificacion[0]
            : ($_POST['clasificacion'] ?? ($clasificacionRequerida ? '' : $opcionesClasificacion[0]));
        if (!in_array($clasificacion, $opcionesClasificacion, true)) {
            if ($clasificacionRequerida) {
                $erroresFijos['clasificacion'] = 'Selecciona Confirmado o Descartado.';
            } else {
                $clasificacion = $opcionesClasificacion[0];
            }
        }

        // ---------- dinámicos: cuadro clínico según la enfermedad ----------
        // La fecha de nacimiento del núcleo va para las reglas
        // "comparar_fechas"/"maximo_dias_entre" (P96).
        [$valoresCampos, $erroresCampos, $paraGuardar] = $this->validarCamposDinamicos($enfermedadId, $this->valoresNucleoParaReglas($valoresFijos, $fechaNacIso));

        // reglas_campos "clasificar" (Z21, 2026-09-13): en las fichas que la
        // calculan, la clasificación sale de los campos ya validados y no del
        // POST (no pintan la tarjeta de chips).
        $clasificacionCalculada = $this->clasificacionCalculada($enfermedad, $valoresCampos);
        // reglas_campos "fallecido" (A50, 2026-09-14): sale del estado vital.
        $fallecidoCalculado = $this->fallecidoCalculado($enfermedad, $valoresCampos);
        if ($clasificacionCalculada !== null) {
            $clasificacion = $clasificacionCalculada;
            unset($erroresFijos['clasificacion']);
        }

        // ---------- vinculo_caso (cotejo Z21, 2026-09-11) ----------
        // Revalida contra la lista de candidatos real y copia a sus campos los
        // datos del caso vinculado, pisando lo que haya llegado en el POST.
        [$casoVinculadoId, $errorVinculoCaso] = $this->resolverVinculoCaso($enfermedad, $valoresCampos, $paraGuardar, null);

        // ---------- tablas hijas (opcionales) ----------
        $filasContactos = $this->filasContactos();
        $filasContactosDirectos = $this->filasContactosDirectos();
        [$filasViajes, $erroresViajes] = $this->filasViajes();
        [$filasVacunas, $erroresVacunas] = $this->filasVacunas();
        [$filasMuestras, $erroresMuestras] = $this->filasMuestras($enfermedad);
        [$filasLugarInfeccion, $erroresLugarInfeccion] = $this->filasLugarInfeccion();
        [$filasEvolucion, $erroresEvolucion] = $this->filasEvolucion();
        [$filasExamen, $erroresExamen] = $this->filasExamen();

        $hayErrores = !empty($erroresFijos) || !empty($erroresCampos) || $errorFechaInicioSintomas !== null
            || $errorVinculoCaso !== null
            || !empty($erroresViajes) || !empty($erroresVacunas) || !empty($erroresMuestras) || !empty($erroresLugarInfeccion)
            || !empty($erroresEvolucion) || !empty($erroresExamen);

        if ($hayErrores) {
            $semana = semanaEpidemiologica($fechaNotifIso ?: (new DateTime())->format('Y-m-d'));
            [$filasMuestrasInicial, $filasBloquesMuestra] = $this->separarFilasMuestrasPorContexto($filasMuestras);

            $this->vista('nueva/index', array_merge([
                'tituloVista'   => 'Nueva ficha de notificación',
                'rutaActual'    => 'casos/nuevo',
                'enfermedades'  => $enfermedades,
                'enfermedad'    => $enfermedad,
                'valoresFijos'  => $valoresFijos,
                'erroresFijos'  => $erroresFijos,
                'semanaEpiPreview' => $semana['semana'],
                'anioEpiPreview'   => $semana['anio'],
                'valoresCampos' => $valoresCampos,
                'erroresCampos' => $erroresCampos,
                'fechaInicioSintomas' => $fechaInicioSintomas,
                'errorFechaInicioSintomas' => $errorFechaInicioSintomas,
                'clasificacionActual' => $clasificacion,
                'filasContactos' => $filasContactos,
                'filasContactosDirectos' => $filasContactosDirectos,
                'filasViajes'    => $filasViajes,
                'filasVacunas'   => $filasVacunas,
                'filasMuestras'  => $filasMuestrasInicial,
                'filasBloquesMuestra' => $filasBloquesMuestra,
                'filasLugarInfeccion' => $filasLugarInfeccion,
                'filasEvolucion' => $filasEvolucion,
                'filasExamen'    => $filasExamen,
                'erroresViajes'  => $erroresViajes,
                'erroresVacunas' => $erroresVacunas,
                'erroresMuestras' => $erroresMuestras,
                'erroresLugarInfeccion' => $erroresLugarInfeccion,
                'erroresEvolucion' => $erroresEvolucion,
                'erroresExamen'  => $erroresExamen,
                'valoresSujetoPorRol' => $this->valoresSujetoPorRolDesdePost($enfermedad),
            ], $this->datosVinculoCasoVista(
                $enfermedad,
                ((int) ($_POST['caso_vinculado_id'] ?? 0)) ?: null,
                null,
                $errorVinculoCaso
            ), $this->datosEstablecimiento(), $datosPnp['vista'], $this->datosMuestrasCatalogo($enfermedad), $this->datosVacunasCatalogo(), $this->datosColumnasTablaHija($enfermedad), contextoUbigeo($distritoId ?: null)));
            return;
        }

        // ---------- guardar (paciente + caso + caso_valor en una transacción) ----------
        $pdo = Database::conexion();

        try {
            $pdo->beginTransaction();

            $nucleo = $this->sanearCamposNucleo($valoresFijos, $enfermedad);

            $datosPaciente = array_merge([
                'tipo_doc'          => $valoresFijos['tipo_doc'],
                'num_doc'           => $sinDocumento ? null : $valoresFijos['num_doc'],
                'apellido_paterno'  => $valoresFijos['apellido_paterno'],
                'apellido_materno'  => $valoresFijos['apellido_materno'] !== '' ? $valoresFijos['apellido_materno'] : null,
                // NULL solo con nucleo_ajustes.nombres_opcionales: en el resto
                // de fichas los nombres ya se exigieron arriba.
                'nombres'           => $valoresFijos['nombres'] !== '' ? $valoresFijos['nombres'] : null,
                'sexo'              => $valoresFijos['sexo'] !== '' ? $valoresFijos['sexo'] : null,
                'fecha_nac'         => $fechaNacIso,
                // NULL y no '' cuando la rama no pide residencia
                // (nucleo_condicional, cotejo Z21): persona.distrito_id tiene
                // clave foránea a distrito, y la cadena vacía la viola.
                'distrito_id'       => $distritoId !== '' ? $distritoId : null,
            ], $nucleo['persona'], $datosPnp['datos']);

            // Sin documento no hay con qué reconocer a una persona ya
            // registrada: siempre es una persona nueva.
            $personaExistente = $sinDocumento ? null : Persona::buscarPorDocumento($valoresFijos['tipo_doc'], $valoresFijos['num_doc']);
            if ($personaExistente) {
                $personaId = (int) $personaExistente['id'];
                Persona::actualizar($personaId, $datosPaciente);
            } else {
                $personaId = Persona::crear($datosPaciente);
            }

            // Validación de conflicto de interés
            if ($usuario['persona_id'] !== null && $usuario['persona_id'] === $personaId) {
                throw new ConflictoInteresException('No puedes registrar esta ficha: la persona notificada eres tú mismo/a. Pide a otro registrador o al epidemiólogo que la registre.');
            }

            $semana = semanaEpidemiologica($fechaNotifIso);

            $casoId = Caso::crearConCodigo(array_merge([
                'enfermedad_id'         => $enfermedadId,
                'persona_id'            => $personaId,
                // vinculo_caso (cotejo Z21): ya revalidado contra la lista de
                // candidatos; null si la ficha no lo declara o no aplica.
                'caso_vinculado_id'     => $casoVinculadoId,
                'establecimiento_id'    => (int) $establecimiento['id'],
                'usuario_id'            => (int) $usuario['id'],
                'fecha_notif'           => $fechaNotifIso,
                'anio_epi'              => $semana['anio'],
                'semana_epi'            => $semana['semana'],
                'fecha_inicio_sintomas' => $fechaInicioSintomasIso,
                'clasificacion'         => $clasificacion,
            ], $nucleo['caso'], $fallecidoCalculado !== null ? ['fallecido' => $fallecidoCalculado] : []));

            CasoValor::guardarTodos($casoId, $paraGuardar);
            CasoContacto::reemplazarTodos($casoId, $filasContactos);
            CasoContactoDirecto::reemplazarTodos($casoId, $filasContactosDirectos);
            CasoViaje::reemplazarTodos($casoId, $filasViajes);
            CasoVacuna::reemplazarTodos($casoId, $filasVacunas);
            CasoMuestra::reemplazarTodos($casoId, $filasMuestras);
            CasoLugarInfeccion::reemplazarTodos($casoId, $filasLugarInfeccion);
            CasoEvolucion::reemplazarTodos($casoId, $filasEvolucion);
            CasoExamenAuxiliar::reemplazarTodos($casoId, $filasExamen);

            $rolPrincipal = $enfermedad['multi_sujeto'] ? explode(',', $enfermedad['roles_sujeto'])[0] : 'CASO_INDICE';
            $sujetos = array_merge(
                [$rolPrincipal => ['persona_id' => $personaId]],
                $this->valoresSujetoPorRolDesdePost($enfermedad)
            );
            CasoSujeto::guardarSujetos($casoId, $sujetos);

            CasoBitacora::registrar($casoId, (int) $usuario['id'], 'CREACION', 'Ficha registrada.');

            $pdo->commit();
        } catch (ConflictoInteresException $e) {
            $pdo->rollBack();
            error_log('Conflicto de interés bloqueado: el usuario ' . $usuario['id'] . ' intentó registrar una ficha donde él es la persona notificada (persona_id ' . $personaId . ').');
            Flash::set($e->getMessage());
            header('Location: /casos/nuevo?enfermedad_id=' . $enfermedadId);
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Error al registrar ficha: ' . $e->getMessage());
            Flash::set('No se pudo registrar la ficha por un error interno. Intenta nuevamente.');
            header('Location: /casos/nuevo');
            exit;
        }

        // vinculo_caso.encadenar (pedido del usuario, 2026-09-11): al guardar
        // la ficha de una gestante que declara nacidos vivos, se abre su
        // propia ficha en vez de un formulario en blanco, con el aviso de
        // cuántos niños faltan por registrar -- ahí está el botón que los crea
        // con la madre ya fijada, uno por uno (embarazo múltiple). Sin
        // "encadenar" declarado, o con el campo vacío, todo sigue igual.
        $configEncadenar = $this->configVinculoCaso($enfermedad);
        $esCandidatoEncadenable = $configEncadenar
            && !empty($configEncadenar['encadenar'])
            && (string) ($valoresCampos[(int) $configEncadenar['_candidato']['id']] ?? '') === (string) $configEncadenar['candidatos']['valor'];
        $esperadosEncadenar = $esCandidatoEncadenable
            ? $this->fichasVinculadasEsperadas($configEncadenar, $valoresCampos)
            : null;

        if ($esperadosEncadenar !== null && $esperadosEncadenar > 0) {
            Flash::set(sprintf('Ficha registrada: F-%05d. ', $casoId) . str_replace(
                ['{faltan}', '{total}'],
                [(string) $esperadosEncadenar, (string) $esperadosEncadenar],
                (string) $configEncadenar['encadenar']['mensaje']
            ));
            header('Location: /casos/' . $casoId);
            exit;
        }

        // nucleo_ajustes.registrar_y_agregar_otra (P96, 2026-09-14): la hoja
        // es un listado semanal y las defunciones se cargan una tras otra.
        // "Registrar y agregar otra" abre la siguiente ficha con el mismo
        // establecimiento y fecha de notificación (la misma semana del
        // listado); "Registrar ficha", a su lado, abre la ficha recién
        // registrada. Sin el ajuste, todo sigue igual.
        if (nucleoAjuste($enfermedad, 'registrar_y_agregar_otra') === true) {
            if (($_POST['despues_de_registrar'] ?? '') === 'agregar_otra') {
                Flash::set(sprintf('Ficha registrada: F-%05d (SE %d · %d). Registra la siguiente: se mantienen el establecimiento y la fecha de notificación.', $casoId, $semana['semana'], $semana['anio']));
                header('Location: /casos/nuevo?' . http_build_query(array_merge(
                    ['enfermedad_id' => $enfermedadId],
                    $puedeElegirEstablecimiento ? ['establecimiento_id' => (int) $establecimiento['id']] : [],
                    ['fecha_notif' => $fechaNotifIso]
                )));
                exit;
            }
            Flash::set('Ficha registrada: ' . sprintf('F-%05d', $casoId));
            header('Location: /casos/' . $casoId);
            exit;
        }

        Flash::set('Ficha registrada: ' . sprintf('F-%05d', $casoId));
        header('Location: /casos/nuevo?enfermedad_id=' . $enfermedadId);
        exit;
    }

    /**
     * Endpoint AJAX: autocompleta datos del paciente por documento y avisa de
     * un posible duplicado (misma enfermedad + documento, ~30 días). No
     * bloquea el registro; solo informa.
     */
    public function buscarPaciente(): void
    {
        Auth::exigirRol('ADMIN', 'REGISTRADOR');
        header('Content-Type: application/json; charset=utf-8');

        $tipoDoc = trim($_GET['tipo_doc'] ?? '');
        $numDoc = trim($_GET['num_doc'] ?? '');

        if ($tipoDoc === '' || $numDoc === '') {
            echo json_encode(['error' => 'Datos incompletos'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $persona = PersonaService::buscarOCrear($tipoDoc, $numDoc);
        $pacienteJson = null;
        $duplicado = null;

        if ($persona) {
            $distrito = $persona['distrito_id'] ? Distrito::buscarPorId($persona['distrito_id']) : null;

            $pacienteJson = [
                'fuente'            => 'SISTEMA',
                'apellido_paterno'  => $persona['apellido_paterno'],
                'apellido_materno'  => $persona['apellido_materno'],
                'nombres'           => $persona['nombres'],
                'nombre_completo'   => Persona::nombreCompleto($persona),
                'sexo'              => $persona['sexo'],
                'fecha_nac'         => $persona['fecha_nac'] ?: null,
                'edad'              => $persona['fecha_nac'] ? edadDesdeFecha($persona['fecha_nac']) : null,
                'distrito_id'       => $persona['distrito_id'],
                'provincia_id'      => $distrito['provincia_id'] ?? null,
                'departamento_id'   => $distrito['departamento_id'] ?? null,
                'condicion'         => $persona['condicion'] ?? 'PARTICULAR',
                'cip'               => $persona['cip'] ?? null,
                'situacion_pnp'     => $persona['situacion_pnp'] ?? null,
                'grado_id'          => $persona['grado_id'] ?? null,
                'categoria_pnp'     => $persona['categoria_pnp'] ?? null,
                'vinculo_titular'   => $persona['vinculo_titular'] ?? null,
                'titular_id'        => $persona['titular_id'] ?? null,
            ];

            $enfermedadId = (int) ($_GET['enfermedad_id'] ?? 0);
            $fechaNotifIso = fechaIsoValida(trim($_GET['fecha_notif'] ?? ''));

            if ($enfermedadId && $fechaNotifIso) {
                $dup = Caso::buscarDuplicado($enfermedadId, $tipoDoc, $numDoc, $fechaNotifIso);
                if ($dup) {
                    $duplicado = [
                        'codigo'       => $dup['codigo'],
                        'semana_epi'   => $dup['semana_epi'],
                        'anio_epi'     => $dup['anio_epi'],
                        'establecimiento_nombre' => $dup['establecimiento_nombre'],
                        'url'          => '/casos/' . $dup['id'],
                    ];
                }
            }
        }

        echo json_encode([
            'paciente'  => $pacienteJson,
            'duplicado' => $duplicado
        ], JSON_UNESCAPED_UNICODE);
    }

    public function ver(string $id): void
    {
        $caso = Caso::conDetalle((int) $id);
        if (!$caso) {
            Flash::set('La ficha solicitada no existe.');
            header('Location: /casos');
            exit;
        }

        if (!$this->puedeVerCaso($caso)) {
            http_response_code(403);
            require __DIR__ . '/../Views/403.php';
            exit;
        }

        $secciones = SeccionDef::porEnfermedad((int) $caso['enfermedad_id']);
        $valoresCampos = CasoValor::porCaso((int) $caso['id']);
        // La fila completa de `enfermedad` (conDetalle() sólo trae unas pocas
        // columnas suyas): hace falta para resolver qué columnas de muestra
        // declara la ficha, igual que en nuevo()/editar().
        $enfermedadVer = Enfermedad::buscarPorCie10((string) $caso['cie10']) ?? [];

        $camposDef = CampoDef::porEnfermedad((int) $caso['enfermedad_id']);
        $tieneSensibles = !empty(array_filter($camposDef, fn($c) => !empty($c['sensible'])));
        $puedeVerSensibles = Auth::tieneRol('ADMIN');
        if (($tieneSensibles && $puedeVerSensibles) || Caso::esPrivada($caso)) {
            CasoBitacora::registrar((int) $caso['id'], (int) Auth::usuario()['id'], 'CONSULTA_SENSIBLE', 'Consulta a ficha con datos sensibles.');
        }

        $this->vista('fichas/ver', [
            'tituloVista' => 'Ficha ' . $caso['codigo'],
            'rutaActual'  => 'casos',
            'caso'        => $caso,
            'secciones'   => $secciones,
            'valoresCampos' => $valoresCampos,
            // La fila completa de enfermedad, para que la vista de solo
            // lectura respete nucleo_omitidos (cotejo Z21, 2026-09-11): sin
            // esto pintaría "Fecha de inicio de síntomas" y el chip de
            // clasificación en fichas cuyo PDF no los trae. Va como
            // 'enfermedadVer' y no como 'enfermedad' a propósito: la vista ya
            // usaba ese nombre para otra cosa (el bloque de Captación de
            // A80/B05), y pisarlo cambiaría esas dos fichas ya cotejadas.
            'enfermedadVer' => $enfermedadVer,
            'vinculoVer'    => $this->datosVinculoVer($enfermedadVer, $caso, $valoresCampos),
            'contactos'   => CasoContacto::porCaso((int) $caso['id']),
            'viajes'      => CasoViaje::porCaso((int) $caso['id']),
            'vacunas'     => CasoVacuna::porCaso((int) $caso['id']),
            'muestras'    => CasoMuestra::porCaso((int) $caso['id']),
            // columnasMuestra/datosMuestra (2026-09-07, cotejo A00): hasta acá
            // fichas/ver.php pintaba un juego FIJO de 6 columnas de muestra e
            // ignoraba lo que la ficha declara en columnas_tablas_hija, así que
            // cualquier columna fuera de ese juego (fecha de envío, fecha de
            // recepción, agente aislado, observaciones, genotipo, titulación,
            // resultados serológicos, y ahora establecimiento/serogrupo/
            // serotipo) se capturaba y se guardaba pero no se veía nunca al
            // abrir la ficha. Se pasa la misma declaración que ya usan "Nueva
            // ficha" y "Editar" para que la vista de sólo lectura muestre
            // exactamente las columnas de esa ficha, sin condiciones por CIE-10.
            'columnasMuestra' => $this->resolverConfigMuestra($enfermedadVer ?? [])['columnas'],
            'datosMuestra'    => $this->datosMuestrasCatalogo($enfermedadVer ?: null),
            'lugaresInfeccion' => CasoLugarInfeccion::porCaso((int) $caso['id']),
            'evoluciones' => CasoEvolucion::porCaso((int) $caso['id']),
            'examenesAuxiliares' => CasoExamenAuxiliar::porCaso((int) $caso['id']),
            'valoresSujetoPorRol' => $this->sujetosConDistritoPorRol($caso),
            'bitacora'    => CasoBitacora::porCaso((int) $caso['id']),
            'puedeEditar' => $this->puedeEditarCaso($caso),
            'puedeCerrar' => Auth::tieneRol(...self::ROLES_CIERRE),
            'puedeAnular' => Auth::tieneRol(...self::ROLES_CIERRE) && !$caso['anulado'],
        ]);
    }

    public function editar(string $id): void
    {
        $caso = Caso::conDetalle((int) $id);
        if (!$caso) {
            Flash::set('La ficha solicitada no existe.');
            header('Location: /casos');
            exit;
        }

        if (!$this->puedeEditarCaso($caso)) {
            Flash::set('Esta ficha no se puede editar en su estado o rol actual.');
            header('Location: /casos/' . $id);
            exit;
        }

        $enfermedadId = (int) $caso['enfermedad_id'];
        $enfermedad = Enfermedad::buscar($enfermedadId);

        $camposDef = CampoDef::porEnfermedad($enfermedadId);
        $tieneSensibles = !empty(array_filter($camposDef, fn($c) => !empty($c['sensible'])));
        $puedeVerSensibles = Auth::tieneRol('ADMIN');
        if (($tieneSensibles && $puedeVerSensibles) || Caso::esPrivada($caso)) {
            CasoBitacora::registrar((int) $caso['id'], (int) Auth::usuario()['id'], 'CONSULTA_SENSIBLE', 'Consulta a ficha con datos sensibles (edición).');
        }

        $valoresCamposCrudo = CasoValor::porCaso((int) $caso['id']);
        $valoresCampos = $this->expandirValoresGuardados($enfermedadId, $valoresCamposCrudo);

        $valoresFijos = [
            'establecimiento_id' => (string) $caso['establecimiento_id'],
            'fecha_notif'        => (string) $caso['fecha_notif'],
            'tipo_doc'           => $caso['tipo_doc'],
            'num_doc'            => $caso['num_doc'],
            'apellido_paterno'   => $caso['apellido_paterno'],
            'apellido_materno'   => $caso['apellido_materno'],
            'nombres'            => $caso['nombres'],
            'sexo'               => $caso['sexo'] ?? '',
            'fecha_nac'          => (string) ($caso['fecha_nac'] ?? ''),
            'fecha_nac_desconocida' => !empty($caso['fecha_nac_desconocida']) ? '1' : '',
            'nacimiento_distrito_id' => (string) ($caso['nacimiento_distrito_id'] ?? ''),
            'edad_valor'         => (string) ($caso['edad_valor'] ?? ''),
            'edad_unidad'        => (string) ($caso['edad_unidad'] ?? ''),
            'celular'            => (string) ($caso['celular'] ?? ''),
            'nacionalidad'       => (string) ($caso['nacionalidad'] ?? ''),
            'direccion'          => (string) ($caso['direccion'] ?? ''),
            'referencia_localizar' => (string) ($caso['referencia_localizar'] ?? ''),
            'tipo_zona'          => (string) ($caso['tipo_zona'] ?? ''),
            'nombre_zona'        => (string) ($caso['nombre_zona'] ?? ''),
            'tipo_via'           => (string) ($caso['tipo_via'] ?? ''),
            'nombre_via'         => (string) ($caso['nombre_via'] ?? ''),
            'numero'             => (string) ($caso['numero'] ?? ''),
            'mz_lote'            => (string) ($caso['mz_lote'] ?? ''),
            'tiempo_residencia'  => (string) ($caso['tiempo_residencia'] ?? ''),
            'tiempo_reside_anios' => (string) ($caso['tiempo_reside_anios'] ?? ''),
            'tiempo_reside_meses' => (string) ($caso['tiempo_reside_meses'] ?? ''),
            'anterior_distrito_id' => (string) ($caso['anterior_distrito_id'] ?? ''),
            'anterior_tipo_zona'   => (string) ($caso['anterior_tipo_zona'] ?? ''),
            'anterior_nombre_zona' => (string) ($caso['anterior_nombre_zona'] ?? ''),
            'anterior_tipo_via'    => (string) ($caso['anterior_tipo_via'] ?? ''),
            'anterior_nombre_via'  => (string) ($caso['anterior_nombre_via'] ?? ''),
            'anterior_numero'      => (string) ($caso['anterior_numero'] ?? ''),
            'anterior_mz_lote'     => (string) ($caso['anterior_mz_lote'] ?? ''),
            'lugar_contagio_distrito_id' => (string) ($caso['lugar_contagio_distrito_id'] ?? ''),
            'lugar_contagio_localidad'   => (string) ($caso['lugar_contagio_localidad'] ?? ''),
            'n_historia_clinica' => (string) ($caso['n_historia_clinica'] ?? ''),
            'localidad'          => (string) ($caso['localidad'] ?? ''),
            'etnia'              => (string) ($caso['etnia'] ?? ''),
            'etnia_otra'         => (string) ($caso['etnia_otra'] ?? ''),
            'pueblo_etnico'      => (string) ($caso['pueblo_etnico'] ?? ''),
            'ocupacion'          => (string) ($caso['ocupacion'] ?? ''),
            'estado_civil'       => (string) ($caso['estado_civil'] ?? ''),
            'nombre_tutor'       => (string) ($caso['nombre_tutor'] ?? ''),
            'celular_tutor'      => (string) ($caso['celular_tutor'] ?? ''),
            'gestante'           => $caso['gestante'] !== null ? (string) $caso['gestante'] : '',
            'fur'                => (string) ($caso['fur'] ?? ''),
            'semanas_gestacion'  => (string) ($caso['semanas_gestacion'] ?? ''),
            'trimestre_gestacion'=> (string) ($caso['trimestre_gestacion'] ?? ''),
            'tipo_captacion'         => (string) ($caso['tipo_captacion'] ?? ''),
            'lugar_captacion'        => (string) ($caso['lugar_captacion'] ?? ''),
            'clasificacion_captacion' => (string) ($caso['clasificacion_captacion'] ?? ''),
            'investigador_nombre'    => (string) ($caso['investigador_nombre'] ?? ''),
            'investigador_cargo'     => (string) ($caso['investigador_cargo'] ?? ''),
            'investigador_profesion' => (string) ($caso['investigador_profesion'] ?? ''),
            'investigador_telefono'  => (string) ($caso['investigador_telefono'] ?? ''),
            'investigador_email'     => (string) ($caso['investigador_email'] ?? ''),
            'fecha_investigacion'    => (string) ($caso['fecha_investigacion'] ?? ''),
        ];

        [$filasMuestrasInicial, $filasBloquesMuestra] = $this->separarFilasMuestrasPorContexto(CasoMuestra::porCaso((int) $caso['id']));

        $this->vista('fichas/editar', array_merge([
            'tituloVista' => 'Editar ficha ' . $caso['codigo'],
            'rutaActual'  => 'casos',
            'caso'        => $caso,
            'enfermedad'  => $enfermedad,
            'valoresFijos' => $valoresFijos,
            'erroresFijos' => [],
            'valoresCampos' => $valoresCampos,
            'erroresCampos' => [],
            'fechaInicioSintomas' => (string) ($caso['fecha_inicio_sintomas'] ?? ''),
            'errorFechaInicioSintomas' => null,
            'filasContactos' => CasoContacto::porCaso((int) $caso['id']),
            'filasContactosDirectos' => CasoContactoDirecto::porCaso((int) $caso['id']),
            'filasViajes'    => CasoViaje::porCaso((int) $caso['id']),
            'filasVacunas'   => CasoVacuna::porCaso((int) $caso['id']),
            'filasMuestras'  => $filasMuestrasInicial,
            'filasBloquesMuestra' => $filasBloquesMuestra,
            'filasLugarInfeccion' => CasoLugarInfeccion::porCaso((int) $caso['id']),
            'filasEvolucion' => CasoEvolucion::porCaso((int) $caso['id']),
            'filasExamen'    => CasoExamenAuxiliar::porCaso((int) $caso['id']),
            'erroresViajes'  => [],
            'erroresVacunas' => [],
            'erroresLugarInfeccion' => [],
            'erroresMuestras' => [],
            'erroresEvolucion' => [],
            'erroresExamen'  => [],
            'valoresSujetoPorRol' => CasoSujeto::porCaso((int) $caso['id']),
        ], $this->datosVinculoCasoVista(
            $enfermedad,
            $caso['caso_vinculado_id'] !== null ? (int) $caso['caso_vinculado_id'] : null,
            (int) $caso['id']
        ), $this->datosPnpEdicion($caso), $this->datosMuestrasCatalogo($enfermedad), $this->datosVacunasCatalogo(), $this->datosColumnasTablaHija($enfermedad), contextoUbigeo($caso['distrito_id'])));
    }

    public function actualizar(string $id): void
    {
        $this->exigirCsrf();

        $caso = Caso::conDetalle((int) $id);
        if (!$caso) {
            Flash::set('La ficha solicitada no existe.');
            header('Location: /casos');
            exit;
        }

        if (!$this->puedeEditarCaso($caso)) {
            Flash::set('Esta ficha no se puede editar en su estado o rol actual.');
            header('Location: /casos/' . $id);
            exit;
        }

        $usuario = Auth::usuario();
        $enfermedadId = (int) $caso['enfermedad_id'];
        $enfermedad = Enfermedad::buscar($enfermedadId);

        $erroresFijos = [];
        $valoresFijos = [
            'establecimiento_id' => (string) $caso['establecimiento_id'],
            'fecha_notif'        => trim($_POST['fecha_notif'] ?? ''),
            'tipo_doc'           => $caso['tipo_doc'],
            'num_doc'            => $caso['num_doc'],
            'apellido_paterno'   => trim($_POST['apellido_paterno'] ?? ''),
            'apellido_materno'   => trim($_POST['apellido_materno'] ?? ''),
            'nombres'            => trim($_POST['nombres'] ?? ''),
            'sexo'               => $_POST['sexo'] ?? '',
            'fecha_nac'          => trim($_POST['fecha_nac'] ?? ''),
            'fecha_nac_desconocida' => ($_POST['fecha_nac_desconocida'] ?? '') === '1' ? '1' : '',
            'nacimiento_distrito_id' => $_POST['nacimiento_distrito_id'] ?? '',
            'edad_valor'         => trim($_POST['edad_valor'] ?? ''),
            'edad_unidad'        => $_POST['edad_unidad'] ?? '',
            'celular'            => trim($_POST['celular'] ?? ''),
            'nacionalidad'       => trim($_POST['nacionalidad'] ?? '') ?: 'Peruana',
            'direccion'          => trim($_POST['direccion'] ?? ''),
            'referencia_localizar' => trim($_POST['referencia_localizar'] ?? ''),
            'tipo_zona'          => $_POST['tipo_zona'] ?? '',
            'nombre_zona'        => trim($_POST['nombre_zona'] ?? ''),
            'tipo_via'           => trim($_POST['tipo_via'] ?? ''),
            'nombre_via'         => trim($_POST['nombre_via'] ?? ''),
            'numero'             => trim($_POST['numero'] ?? ''),
            'mz_lote'            => trim($_POST['mz_lote'] ?? ''),
            'tiempo_residencia'  => trim($_POST['tiempo_residencia'] ?? ''),
            'tiempo_reside_anios' => trim($_POST['tiempo_reside_anios'] ?? ''),
            'tiempo_reside_meses' => trim($_POST['tiempo_reside_meses'] ?? ''),
            'anterior_distrito_id' => $_POST['anterior_distrito_id'] ?? '',
            'anterior_tipo_zona'   => $_POST['anterior_tipo_zona'] ?? '',
            'anterior_nombre_zona' => trim($_POST['anterior_nombre_zona'] ?? ''),
            'anterior_tipo_via'    => trim($_POST['anterior_tipo_via'] ?? ''),
            'anterior_nombre_via'  => trim($_POST['anterior_nombre_via'] ?? ''),
            'anterior_numero'      => trim($_POST['anterior_numero'] ?? ''),
            'anterior_mz_lote'     => trim($_POST['anterior_mz_lote'] ?? ''),
            'lugar_contagio_distrito_id' => $_POST['lugar_contagio_distrito_id'] ?? '',
            'lugar_contagio_localidad'   => trim($_POST['lugar_contagio_localidad'] ?? ''),
            'n_historia_clinica' => trim($_POST['n_historia_clinica'] ?? ''),
            'localidad'          => trim($_POST['localidad'] ?? ''),
            'etnia'              => $_POST['etnia'] ?? '',
            'etnia_otra'         => trim($_POST['etnia_otra'] ?? ''),
            'pueblo_etnico'      => $_POST['pueblo_etnico'] ?? '',
            'ocupacion'          => trim($_POST['ocupacion'] ?? ''),
            'estado_civil'       => $_POST['estado_civil'] ?? '',
            'nombre_tutor'       => trim($_POST['nombre_tutor'] ?? ''),
            'celular_tutor'      => trim($_POST['celular_tutor'] ?? ''),
            'gestante'           => $_POST['gestante'] ?? '',
            'fur'                => trim($_POST['fur'] ?? ''),
            'semanas_gestacion'  => trim($_POST['semanas_gestacion'] ?? ''),
            'trimestre_gestacion'=> $_POST['trimestre_gestacion'] ?? '',
            'tipo_captacion'         => $_POST['tipo_captacion'] ?? '',
            'lugar_captacion'        => $_POST['lugar_captacion'] ?? '',
            'clasificacion_captacion' => $_POST['clasificacion_captacion'] ?? '',
            'investigador_nombre'    => trim($_POST['investigador_nombre'] ?? ''),
            'investigador_cargo'     => trim($_POST['investigador_cargo'] ?? ''),
            'investigador_profesion' => trim($_POST['investigador_profesion_sel'] ?? '') === 'Otro' ? trim($_POST['investigador_profesion_otra'] ?? '') : trim($_POST['investigador_profesion_sel'] ?? ''),
            'investigador_profesion_otra' => trim($_POST['investigador_profesion_otra'] ?? ''),
            'investigador_telefono'  => trim($_POST['investigador_telefono'] ?? ''),
            'investigador_email'     => trim($_POST['investigador_email'] ?? ''),
            'fecha_investigacion'    => trim($_POST['fecha_investigacion'] ?? ''),
        ];

        $fechaNotifIso = fechaIsoValida($valoresFijos['fecha_notif']);
        if (!$fechaNotifIso) {
            $erroresFijos['fecha_notif'] = 'Ingresa una fecha de notificación válida.';
        } elseif ($fechaNotifIso > (new DateTime())->format('Y-m-d')) {
            $erroresFijos['fecha_notif'] = 'La fecha de notificación no puede ser futura.';
        }

        if ($valoresFijos['apellido_paterno'] === '') {
            $erroresFijos['apellido_paterno'] = 'Ingresa el apellido paterno.';
        }
        // nucleo_ajustes.nombres_opcionales (P96): ver crear(). Por rama
        // (A50): la rama elegida sale del POST.
        $valoresRamaPost = $this->valoresCamposDesdePost();
        if ($valoresFijos['nombres'] === '' && nucleoAjuste($enfermedad, 'nombres_opcionales', $valoresRamaPost) !== true) {
            $erroresFijos['nombres'] = 'Ingresa los nombres.';
        }

        // fecha_nac_desconocida: mismo criterio que en crear().
        $valoresFijos['fecha_nac_desconocida'] = ($valoresFijos['fecha_nac_desconocida'] === '1'
            && nucleoAjuste($enfermedad, 'fecha_nac_desconocida', $valoresRamaPost) === true) ? '1' : '';
        if ($valoresFijos['fecha_nac_desconocida'] === '1') {
            $valoresFijos['fecha_nac'] = '';
        }

        $fechaNacIso = null;
        if ($valoresFijos['fecha_nac'] !== '') {
            $fechaNacIso = fechaIsoValida($valoresFijos['fecha_nac']);
            if (!$fechaNacIso) {
                $erroresFijos['fecha_nac'] = 'Ingresa una fecha de nacimiento válida.';
            }
        }

        // nucleo_condicional: 'residencia' (cotejo Z21, 2026-09-11) -- mismo
        // criterio que en crear(): el distrito solo se exige (y solo se
        // guarda) si la rama elegida pide residencia habitual.
        $residenciaActivaEditar = $this->bloqueNucleoActivoDesdePost($enfermedad, 'residencia');
        $distritoId = $residenciaActivaEditar ? ($_POST['distrito_id'] ?? '') : '';
        if ($distritoId === '' && $residenciaActivaEditar) {
            $erroresFijos['distrito_id'] = 'Selecciona el distrito de domicilio.';
        }

        // Ver comentario en crear(): P35.0 y A35 no tienen un campo
        // estándar de "fecha de inicio de síntomas" -- no se muestra ni
        // se exige. A37.0 sí lo tiene, pero dentro de su propio Cuadro
        // clínico -- ver el motivo completo en crear().
        // B01 (2026-08-10): igual que los 3 anteriores, el campo está oculto
        // del todo en secciones-clinicas.php -- Varicela no lo requiere en
        // absoluto (no solo "no obligatorio", decisión revisada el
        // 2026-08-09 y corregida al día siguiente por el usuario). No hace
        // falta ninguna otra rama acá: al no llegar en $_POST, sigue
        // guardando NULL sin error, mismo camino que P35.0/A35/A37.0.
        // A97 (2026-08-14): ver el comentario largo en secciones-clinicas.php
        // -- "Subsistema de vigilancia" trae su propio campo FECHA antes que
        // "Cuadro clínico" en el manifiesto, así que el fallback de
        // extraerFechaInicioSintomas() (primer campo FECHA en orden) agarraría
        // esa fecha por error si A97 no estuviera en esta lista.
        // A95 (2026-08-23): ver el motivo completo en crear() -- el primer
        // campo FECHA que encontraría extraerFechaInicioSintomas() es
        // a95_fecha_de_hospitalizacion, no un sustituto válido.
        // B04X (2026-08-29): ver el motivo completo en crear() -- el primer
        // campo FECHA que encontraría extraerFechaInicioSintomas() es
        // b04x_fecha_de_diagnostico_vih, no un sustituto válido.
        // nucleo_omitidos: 'fecha_inicio_sintomas' -- ver crear().
        $fechaInicioSintomasOmitida = nucleoOmitido($enfermedad, 'fecha_inicio_sintomas');
        $sinFechaInicioSintomasObligatoria = $fechaInicioSintomasOmitida
            || in_array($enfermedad['cie10'] ?? '', ['P35.0', 'A35', 'A37.0', 'B01', 'A97', 'B57', 'A95', 'B55', 'B04X', 'A00'], true);
        $fechaInicioSintomas = $fechaInicioSintomasOmitida ? '' : trim($_POST['fecha_inicio_sintomas'] ?? '');
        if ($fechaInicioSintomas === '' && !$sinFechaInicioSintomasObligatoria) {
            $fechaInicioSintomas = $this->extraerFechaInicioSintomas((int) $enfermedad['id']);
        }
        $fechaInicioSintomasIso = null;
        $errorFechaInicioSintomas = null;
        if ($fechaInicioSintomas === '') {
            if (!$sinFechaInicioSintomasObligatoria) {
                $errorFechaInicioSintomas = 'Ingresa la fecha de inicio de síntomas.';
            }
        } else {
            $fechaInicioSintomasIso = fechaIsoValida($fechaInicioSintomas);
            if (!$fechaInicioSintomasIso) {
                $errorFechaInicioSintomas = 'Ingresa una fecha de inicio de síntomas válida.';
            }
        }

        $datosPnp = $this->leerDatosPnp($enfermedad, $valoresRamaPost);
        if ($datosPnp['error'] !== null) {
            $erroresFijos['condicion'] = $datosPnp['error'];
        }
        [$valoresCampos, $erroresCampos, $paraGuardar] = $this->validarCamposDinamicos($enfermedadId, $this->valoresNucleoParaReglas($valoresFijos, $fechaNacIso));

        // ---------- vinculo_caso (cotejo Z21, 2026-09-11) ----------
        // Guarda de integridad: si otras fichas apuntan a esta, no se puede
        // sacar a esta de la rama que las hace válidas (dejaría a los niños
        // nacidos expuestos colgando de una ficha que ya no es de gestante).
        $configVinculoEditar = $this->configVinculoCaso($enfermedad);
        $idsVinculadosAEste = $configVinculoEditar ? Caso::idsVinculados((int) $caso['id']) : [];
        if ($configVinculoEditar && $idsVinculadosAEste) {
            $idCampoCandidato = (int) $configVinculoEditar['_candidato']['id'];
            $sigueSiendoCandidato = (string) ($valoresCampos[$idCampoCandidato] ?? '') === (string) $configVinculoEditar['candidatos']['valor'];
            if (!$sigueSiendoCandidato) {
                $erroresCampos[$idCampoCandidato] = 'No se puede cambiar: hay ' . count($idsVinculadosAEste) . ' ficha(s) vinculada(s) a esta.';
            }
        }
        [$casoVinculadoId, $errorVinculoCaso] = $this->resolverVinculoCaso($enfermedad, $valoresCampos, $paraGuardar, (int) $caso['id']);

        // nucleo_omitidos: 'clasificacion' (cotejo Z21) -- con la tarjeta
        // oculta tampoco llegan Hospitalizado/Fallecido, así que se conservan
        // los valores guardados en vez de leerlos como desmarcados.
        $opcionesClasificacion = opcionesClasificacionPara($enfermedad);
        $clasificacionOmitida = nucleoOmitido($enfermedad, 'clasificacion');
        $clasificacion = $clasificacionOmitida ? $caso['clasificacion'] : ($_POST['clasificacion'] ?? $caso['clasificacion']);
        if (!in_array($clasificacion, $opcionesClasificacion, true)) {
            $clasificacion = $caso['clasificacion'];
        }
        // reglas_campos "clasificar": se recalcula en cada edición.
        $clasificacion = $this->clasificacionCalculada($enfermedad, $valoresCampos) ?? $clasificacion;
        $hospitalizado = $clasificacionOmitida ? (int) $caso['hospitalizado'] : (isset($_POST['hospitalizado']) ? 1 : 0);
        $fallecido = $clasificacionOmitida ? (int) $caso['fallecido'] : (isset($_POST['fallecido']) ? 1 : 0);
        // reglas_campos "fallecido" (A50): se recalcula en cada edición.
        $fallecido = $this->fallecidoCalculado($enfermedad, $valoresCampos) ?? $fallecido;

        $filasContactos = $this->filasContactos();
        $filasContactosDirectos = $this->filasContactosDirectos();
        [$filasViajes, $erroresViajes] = $this->filasViajes();
        [$filasVacunas, $erroresVacunas] = $this->filasVacunas();
        [$filasMuestras, $erroresMuestras] = $this->filasMuestras($enfermedad);
        [$filasLugarInfeccion, $erroresLugarInfeccion] = $this->filasLugarInfeccion();
        [$filasEvolucion, $erroresEvolucion] = $this->filasEvolucion();
        [$filasExamen, $erroresExamen] = $this->filasExamen();

        $hayErrores = !empty($erroresFijos) || !empty($erroresCampos) || $errorFechaInicioSintomas !== null
            || !empty($erroresViajes) || !empty($erroresVacunas) || !empty($erroresMuestras) || !empty($erroresLugarInfeccion)
            || !empty($erroresEvolucion) || !empty($erroresExamen);

        if ($hayErrores) {
            $caso['clasificacion'] = $clasificacion;
            $caso['hospitalizado'] = $hospitalizado;
            $caso['fallecido'] = $fallecido;
            [$filasMuestrasInicial, $filasBloquesMuestra] = $this->separarFilasMuestrasPorContexto($filasMuestras);

            $this->vista('fichas/editar', array_merge([
                'tituloVista' => 'Editar ficha ' . $caso['codigo'],
                'rutaActual'  => 'casos',
                'caso'        => $caso,
                'enfermedad'  => $enfermedad,
                'valoresFijos' => $valoresFijos,
                'erroresFijos' => $erroresFijos,
                'valoresCampos' => $valoresCampos,
                'erroresCampos' => $erroresCampos,
                'fechaInicioSintomas' => $fechaInicioSintomas,
                'errorFechaInicioSintomas' => $errorFechaInicioSintomas,
                'filasContactos' => $filasContactos,
                'filasContactosDirectos' => $filasContactosDirectos,
                'filasViajes'    => $filasViajes,
                'filasVacunas'   => $filasVacunas,
                'filasMuestras'  => $filasMuestrasInicial,
                'filasBloquesMuestra' => $filasBloquesMuestra,
                'filasLugarInfeccion' => $filasLugarInfeccion,
                'filasEvolucion' => $filasEvolucion,
                'filasExamen'    => $filasExamen,
                'erroresViajes'  => $erroresViajes,
                'erroresVacunas' => $erroresVacunas,
                'erroresMuestras' => $erroresMuestras,
                'erroresLugarInfeccion' => $erroresLugarInfeccion,
                'erroresEvolucion' => $erroresEvolucion,
                'erroresExamen'  => $erroresExamen,
                'valoresSujetoPorRol' => $this->valoresSujetoPorRolDesdePost($enfermedad),
            ], $this->datosVinculoCasoVista(
                $enfermedad,
                ((int) ($_POST['caso_vinculado_id'] ?? 0)) ?: null,
                (int) $caso['id'],
                $errorVinculoCaso
            ), $datosPnp['vista'], $this->datosMuestrasCatalogo($enfermedad), $this->datosVacunasCatalogo(), $this->datosColumnasTablaHija($enfermedad), contextoUbigeo($distritoId ?: null)));
            return;
        }

        $pdo = Database::conexion();

        try {
            $pdo->beginTransaction();

            $nucleo = $this->sanearCamposNucleo($valoresFijos, $enfermedad);

            $datosPaciente = array_merge([
                'apellido_paterno'  => $valoresFijos['apellido_paterno'],
                'apellido_materno'  => $valoresFijos['apellido_materno'] !== '' ? $valoresFijos['apellido_materno'] : null,
                'nombres'           => $valoresFijos['nombres'] !== '' ? $valoresFijos['nombres'] : null,
                'sexo'              => $valoresFijos['sexo'] !== '' ? $valoresFijos['sexo'] : null,
                'fecha_nac'         => $fechaNacIso,
                // NULL y no '' cuando la rama no pide residencia
                // (nucleo_condicional, cotejo Z21): persona.distrito_id tiene
                // clave foránea a distrito, y la cadena vacía la viola.
                'distrito_id'       => $distritoId !== '' ? $distritoId : null,
            ], $nucleo['persona'], $datosPnp['datos']);

            // La persona del caso, por su id: el documento no es editable, así
            // que es la misma que antes se buscaba por documento, y además
            // funciona con una persona SIN_DOCUMENTO (número NULL, P96).
            $personaId = (int) $caso['persona_id'];

            // Validación de conflicto de interés
            if ($usuario['persona_id'] !== null && $usuario['persona_id'] === $personaId) {
                throw new ConflictoInteresException('No puedes editar esta ficha: la persona notificada eres tú mismo/a. Pide a otro registrador o al epidemiólogo que la edite.');
            }

            Persona::actualizar($personaId, $datosPaciente);

            $semana = semanaEpidemiologica($fechaNotifIso);

            Caso::actualizar((int) $caso['id'], array_merge([
                'fecha_notif'           => $fechaNotifIso,
                'anio_epi'              => $semana['anio'],
                'semana_epi'            => $semana['semana'],
                'fecha_inicio_sintomas' => $fechaInicioSintomasIso,
                'clasificacion'         => $clasificacion,
                'hospitalizado'         => $hospitalizado,
                'fallecido'             => $fallecido,
                // vinculo_caso (cotejo Z21): revalidado arriba; se vuelve a
                // escribir en cada guardado, así desvincular también persiste.
                'caso_vinculado_id'     => $casoVinculadoId,
            ], $nucleo['caso']));

            CasoValor::eliminarPorCaso((int) $caso['id']);
            CasoValor::guardarTodos((int) $caso['id'], $paraGuardar);
            CasoContacto::reemplazarTodos((int) $caso['id'], $filasContactos);
            CasoContactoDirecto::reemplazarTodos((int) $caso['id'], $filasContactosDirectos);
            CasoViaje::reemplazarTodos((int) $caso['id'], $filasViajes);
            CasoVacuna::reemplazarTodos((int) $caso['id'], $filasVacunas);
            CasoMuestra::reemplazarTodos((int) $caso['id'], $filasMuestras);
            CasoLugarInfeccion::reemplazarTodos((int) $caso['id'], $filasLugarInfeccion);
            CasoEvolucion::reemplazarTodos((int) $caso['id'], $filasEvolucion);
            CasoExamenAuxiliar::reemplazarTodos((int) $caso['id'], $filasExamen);

            $rolPrincipal = $caso['enfermedad_multi_sujeto'] ?? false
                ? explode(',', $caso['enfermedad_roles_sujeto'] ?? 'CASO_INDICE')[0]
                : 'CASO_INDICE';

            $sujetos = array_merge(
                [$rolPrincipal => ['persona_id' => $personaId]],
                $this->valoresSujetoPorRolDesdePost($enfermedad)
            );
            CasoSujeto::guardarSujetos((int) $caso['id'], $sujetos);

            if ($clasificacion !== $caso['clasificacion']) {
                CasoBitacora::registrar(
                    (int) $caso['id'],
                    (int) $usuario['id'],
                    'CLASIFICACION',
                    "De {$caso['clasificacion']} a {$clasificacion}."
                );
            }
            CasoBitacora::registrar((int) $caso['id'], (int) $usuario['id'], 'EDICION', 'Ficha actualizada.');

            $pdo->commit();
        } catch (ConflictoInteresException $e) {
            $pdo->rollBack();
            CasoBitacora::registrar((int) $caso['id'], (int) $usuario['id'], 'CONFLICTO_INTERES', 'Intento bloqueado: el usuario intentó editar una ficha donde él es la persona notificada.');
            Flash::set($e->getMessage());
            header('Location: /casos/' . $id . '/editar');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Error al actualizar ficha: ' . $e->getMessage());
            Flash::set('No se pudo guardar la ficha por un error interno. Intenta nuevamente.');
            header('Location: /casos/' . $id . '/editar');
            exit;
        }

        Flash::set('Ficha actualizada: ' . $caso['codigo']);
        header('Location: /casos/' . $id);
        exit;
    }

    public function cambiarEstado(string $id): void
    {
        $this->exigirCsrf();

        $caso = Caso::buscar((int) $id);
        if (!$caso || $caso['anulado']) {
            Flash::set('La ficha solicitada no existe o está anulada.');
            header('Location: /casos');
            exit;
        }

        $usuario = Auth::usuario();
        $nuevoEstado = $_POST['estado'] ?? '';
        $transicionValida = false;

        if ($caso['estado'] === 'ABIERTA' && $nuevoEstado === 'VALIDACION') {
            $transicionValida = in_array($usuario['rol'], self::ROLES_REGISTRO, true)
                && ($usuario['rol'] !== 'REGISTRADOR' || $usuario['establecimiento_id'] === (int) $caso['establecimiento_id']);
        } elseif ($caso['estado'] === 'VALIDACION' && in_array($nuevoEstado, ['CERRADA', 'ABIERTA'], true)) {
            $transicionValida = in_array($usuario['rol'], self::ROLES_CIERRE, true);
        }

        if (!$transicionValida) {
            http_response_code(403);
            require __DIR__ . '/../Views/403.php';
            exit;
        }

        Caso::cambiarEstado((int) $id, $nuevoEstado);
        CasoBitacora::registrar(
            (int) $id,
            (int) $usuario['id'],
            $nuevoEstado === 'CERRADA' ? 'CIERRE' : 'EDICION',
            "Estado: {$caso['estado']} → {$nuevoEstado}."
        );

        Flash::set('Estado actualizado a ' . mb_strtolower($nuevoEstado) . '.');
        header('Location: /casos/' . $id);
        exit;
    }

    public function anular(string $id): void
    {
        Auth::exigirRol(...self::ROLES_CIERRE);
        $this->exigirCsrf();

        $motivo = trim($_POST['motivo'] ?? '');
        if ($motivo === '') {
            Flash::set('Ingresa el motivo de anulación.');
            header('Location: /casos/' . $id);
            exit;
        }

        $caso = Caso::buscar((int) $id);
        if (!$caso) {
            Flash::set('La ficha solicitada no existe.');
            header('Location: /casos');
            exit;
        }

        $usuario = Auth::usuario();
        Caso::anular((int) $id, $motivo);
        CasoBitacora::registrar((int) $id, (int) $usuario['id'], 'ANULACION', $motivo);

        Flash::set('Ficha anulada: ' . $caso['codigo']);
        header('Location: /casos/' . $id);
        exit;
    }

    /**
     * @return array{0: array, 1: array, 2: array} [valoresCampos, erroresCampos, paraGuardar]
     */
    /**
     * @param array{fecha_nac?: ?string} $valoresNucleo fechas del núcleo ya
     *        validadas que las reglas "comparar_fechas"/"maximo_dias_entre"
     *        pueden referenciar como "nucleo:fecha_nac" (P96, 2026-09-13).
     */
    private function validarCamposDinamicos(int $enfermedadId, array $valoresNucleo = []): array
    {
        $campos = CampoDef::porEnfermedad($enfermedadId);
        $valoresCampos = [];
        $erroresCampos = [];
        $paraGuardar = [];

        // A97 "Otros: especificar prueba" (2026-08-14, pedido del usuario):
        // solo debe guardarse si la fila "Otros" de a97_pruebas_de_laboratorio
        // (MATRIZ) quedó en Positivo/Negativo -- depende_de/campoVisiblePorDependencia()
        // solo sabe leer el valor COMPLETO de un campo, no una fila puntual
        // de un MATRIZ, así que se captura a mano acá (la MATRIZ se procesa
        // primero en el mismo foreach, orden 1 vs 2 de "Laboratorio";
        // ORDER BY de CampoDef::porEnfermedad lo garantiza) y se aplica más
        // abajo, sin confiar en que el cliente ya lo haya ocultado/limpiado.
        $valorOtrosLabA97Post = '';

        foreach ($campos as $campoId => $campo) {
            // Peticion 2, Fase 5: estos son casos especiales que escapan el
            // motor de tipos (arman o combinan valores de $_POST con nombres
            // literales, no campo_NNNN) y se identifican por clave, no por
            // ID -- cargar_fichas.php regenera el ID en cada recarga, la
            // clave es estable. No se rediseña la logica de cada uno; ver
            // MAPA_IDS_CAMPOS.md y FASE2_RESOLVEDOR_POR_CLAVE.md para el
            // porque de cada clave (b26_contactos_por_lugar existia; los
            // otros no persistian antes de esta fase; a37_0_contactos_por_lugar
            // se sumó 2026-08-07 y b01_contactos_por_lugar 2026-08-08,
            // ambos calcando el mismo patrón de B26 -- ver
            // secciones-clinicas.php / lugar-probable-infeccion-b01.php).
            // OJO: esto es distinto del bug corregido el mismo día en
            // b26_inf_direccion y compañía (ver PENDIENTES.md ítem 14,
            // tercera ronda) -- ahí el problema era que esos campos NO
            // tenían campo_def real y se guardaban con la clave string
            // literal como si fuera campo_def_id. Acá $campoId SÍ es el
            // id real (viene del foreach de $campos), solo cambia de
            // dónde se lee el valor en $_POST.
            if ($campo['clave'] === 'b26_contactos_por_lugar' && isset($_POST['b26_lugar_tipo']) && is_array($_POST['b26_lugar_tipo'])) {
                $matrizLugares = [];
                foreach ($_POST['b26_lugar_tipo'] as $idx => $tipo) {
                    $matrizLugares[] = [
                        'tipo'      => trim((string) $tipo),
                        'nombre'    => trim((string) ($_POST['b26_lugar_nombre'][$idx] ?? '')),
                        'direccion' => trim((string) ($_POST['b26_lugar_direccion'][$idx] ?? '')),
                        'sanos'     => trim((string) ($_POST['b26_lugar_sanos'][$idx] ?? '')),
                        'enfermos'  => trim((string) ($_POST['b26_lugar_enfermos'][$idx] ?? '')),
                    ];
                }
                $paraGuardar[$campoId] = json_encode($matrizLugares, JSON_UNESCAPED_UNICODE);
                $valoresCampos[$campoId] = $matrizLugares;
                continue;
            }
            if ($campo['clave'] === 'b01_contactos_por_lugar' && isset($_POST['b01_lugar_tipo']) && is_array($_POST['b01_lugar_tipo'])) {
                $matrizLugaresB01 = [];
                foreach ($_POST['b01_lugar_tipo'] as $idx => $tipo) {
                    $matrizLugaresB01[] = [
                        'tipo'      => trim((string) $tipo),
                        'nombre'    => trim((string) ($_POST['b01_lugar_nombre'][$idx] ?? '')),
                        'direccion' => trim((string) ($_POST['b01_lugar_direccion'][$idx] ?? '')),
                        'sanos'     => trim((string) ($_POST['b01_lugar_sanos'][$idx] ?? '')),
                        'enfermos'  => trim((string) ($_POST['b01_lugar_enfermos'][$idx] ?? '')),
                    ];
                }
                $paraGuardar[$campoId] = json_encode($matrizLugaresB01, JSON_UNESCAPED_UNICODE);
                $valoresCampos[$campoId] = $matrizLugaresB01;
                continue;
            }
            if ($campo['clave'] === 'a37_0_contactos_por_lugar' && isset($_POST['a370_lugar_tipo']) && is_array($_POST['a370_lugar_tipo'])) {
                $lugaresA370 = [];
                foreach ($_POST['a370_lugar_tipo'] as $idx => $tipo) {
                    $lugaresA370[] = [
                        'tipo'                    => trim((string) $tipo),
                        'nombre'                  => trim((string) ($_POST['a370_lugar_nombre'][$idx] ?? '')),
                        'direccion'               => trim((string) ($_POST['a370_lugar_direccion'][$idx] ?? '')),
                        'total'                   => trim((string) ($_POST['a370_lugar_total'][$idx] ?? '')),
                        'con_sintomas'            => trim((string) ($_POST['a370_lugar_con_sintomas'][$idx] ?? '')),
                        'esquema_completo'        => trim((string) ($_POST['a370_lugar_esquema_completo'][$idx] ?? '')),
                        'esquema_incompleto'      => trim((string) ($_POST['a370_lugar_esquema_incompleto'][$idx] ?? '')),
                        'recibieron_vacunacion'   => trim((string) ($_POST['a370_lugar_recibieron_vacunacion'][$idx] ?? '')),
                        'recibieron_antibioticos' => trim((string) ($_POST['a370_lugar_recibieron_antibioticos'][$idx] ?? '')),
                    ];
                }
                $paraGuardar[$campoId] = json_encode($lugaresA370, JSON_UNESCAPED_UNICODE);
                $valoresCampos[$campoId] = $lugaresA370;
                continue;
            }
            if ($campo['clave'] === 'o95_hora_de_la_notificacion' && !empty($_POST['hora_notificacion'])) {
                $valFechaHora = trim($_POST['fecha_notif'] ?? '') . ' ' . trim($_POST['hora_notificacion']);
                $valoresCampos[$campoId] = $valFechaHora;
                $paraGuardar[$campoId] = $valFechaHora;
                continue;
            }
            if ($campo['clave'] === 'o95_identificado_por' && !empty($_POST['identificado_por'])) {
                $valIdentificado = trim($_POST['identificado_por']);
                $valoresCampos[$campoId] = $valIdentificado;
                $paraGuardar[$campoId] = $valIdentificado;
                continue;
            }
            if ($campo['clave'] === 'o95_tipo_de_ficha' && !empty($_POST['o95_tipo_ficha'])) {
                $valTipoFicha = trim($_POST['o95_tipo_ficha']);
                $valoresCampos[$campoId] = $valTipoFicha;
                $paraGuardar[$campoId] = $valTipoFicha;
                continue;
            }
            // A35.12: el selector real Departamento/Provincia/Distrito de
            // "Lugar probable de infección" postea distrito_id (no
            // campo_<id>, ver secciones-clinicas.php); departamento/provincia
            // son solo ayuda visual y no se leen acá -- solo el nombre del
            // distrito llega a este campo_def, que sigue siendo TEXTO.
            if ($campo['clave'] === 'a35_distrito_probable_infeccion') {
                $distritoInfeccion = !empty($_POST['a35_lugar_infeccion_distrito_id'])
                    ? \App\Models\Distrito::buscarPorId($_POST['a35_lugar_infeccion_distrito_id'])
                    : null;
                $valDistritoInfeccion = $distritoInfeccion['nombre'] ?? '';
                $valoresCampos[$campoId] = $valDistritoInfeccion;
                if ($valDistritoInfeccion !== '') {
                    $paraGuardar[$campoId] = $valDistritoInfeccion;
                }
                continue;
            }
            $tipo = $campo['tipo'];
            $obligatorio = (int) $campo['obligatorio'] === 1;
            $nombreCampo = 'campo_' . $campoId;

            // Campo condicional oculto: no se valida su obligatoriedad y se
            // guarda vacío aunque el cliente haya enviado algo (el valor se
            // limpia en el navegador al ocultarse, pero no hay que confiar
            // en eso del lado servidor).
            $seccionOculta = !empty($campo['seccion_depende_de']) && !campoVisiblePorDependencia(
                ['depende_de' => $campo['seccion_depende_de'], 'valor_activador' => $campo['seccion_valor_activador']],
                $valoresCampos
            );
            if ($seccionOculta || (!empty($campo['depende_de']) && !campoVisiblePorDependencia($campo, $valoresCampos))) {
                $valoresCampos[$campoId] = in_array($tipo, ['MULTISELECT', 'GRUPO_SI_NO', 'SI_NO_FECHA', 'SI_NO', 'MATRIZ', 'CRONOLOGIA'], true) ? [] : '';
                continue;
            }

            // TEXTO "calculado" (B24, 2026-09-14): lo que llegue en el POST no
            // se lee; el código del paciente sale de los apellidos, los nombres
            // y la fecha de nacimiento ya validados del núcleo.
            if ($tipo === 'TEXTO' && ((json_decode((string) ($campo['config'] ?? ''), true) ?: [])['calculado'] ?? null) === 'iniciales_fecha_nac') {
                $codigoCalculado = codigoInicialesFechaNac(
                    (string) ($valoresNucleo['apellido_paterno'] ?? ''),
                    (string) ($valoresNucleo['apellido_materno'] ?? ''),
                    (string) ($valoresNucleo['nombres'] ?? ''),
                    $valoresNucleo['fecha_nac'] ?? null
                );
                $valoresCampos[$campoId] = $codigoCalculado;
                $paraGuardar[$campoId] = $codigoCalculado;
                continue;
            }

            if ($tipo === 'MULTISELECT') {
                $seleccion = array_map('strval', $_POST[$nombreCampo] ?? []);
                if ($campo['catalogo_id']) {
                    $validos = array_column(CatalogoItem::porCatalogo((int) $campo['catalogo_id']), 'valor');
                    $seleccion = array_values(array_intersect($seleccion, $validos));
                }
                $valoresCampos[$campoId] = $seleccion;
                if ($obligatorio && empty($seleccion)) {
                    $erroresCampos[$campoId] = 'Selecciona al menos una opción.';
                } elseif (!empty($seleccion)) {
                    $paraGuardar[$campoId] = implode(',', $seleccion);
                }
                continue;
            }

            if ($tipo === 'BOOLEANO') {
                // Un BOOLEANO que es "padre" de una dependencia se renderiza
                // como <select> Sí/No (ver secciones-clinicas.php,
                // $esBooleanoJuntoASuCampo/$idsPadre) en vez de la casilla
                // normal de campos/booleano.php. Un <select> SIEMPRE envía su
                // name en el POST (incluso en "Seleccionar…", value=""), así
                // que isset() por sí solo no distingue "No respondido"/"No"
                // de "Sí" -- guardaba '1' sin importar qué elegía el usuario
                // (bug real, hallado 2026-08-23 en A95 al convertir
                // a95_hospitalizado en padre; memoria
                // bug_booleano_select_isset_vacio.md). Se lee el valor
                // real cuando está presente; ausente del todo (casilla sin
                // marcar) sigue guardando '0', igual que antes.
                $valorPostBooleano = $_POST[$nombreCampo] ?? '';
                $marcado = ($valorPostBooleano === '1') ? '1' : '0';
                $valoresCampos[$campoId] = $marcado;
                $paraGuardar[$campoId] = $marcado;
                continue;
            }

            if ($campo['clave'] === 'a97_pruebas_de_laboratorio') {
                $configLabA97 = json_decode((string) ($campo['config'] ?? '{}'), true);
                $idxOtrosLabA97 = array_search('Otros', $configLabA97['filas'] ?? [], true);
                if ($idxOtrosLabA97 !== false) {
                    $valorOtrosLabA97Post = trim((string) ($_POST[$nombreCampo][$idxOtrosLabA97]['_radio'] ?? ''));
                }
                // Sin "continue": el resto del bloque MATRIZ genérico de
                // abajo procesa este mismo campo normalmente.
            }

            if ($campo['clave'] === 'a97_otros_prueba_especificar') {
                $activadoOtrosLabA97 = in_array($valorOtrosLabA97Post, ['POSITIVO', 'NEGATIVO'], true);
                $valOtrosEspecificar = $activadoOtrosLabA97 ? trim((string) ($_POST[$nombreCampo] ?? '')) : '';
                $valoresCampos[$campoId] = $valOtrosEspecificar;
                if ($valOtrosEspecificar !== '') {
                    $paraGuardar[$campoId] = $valOtrosEspecificar;
                }
                continue;
            }

            if (in_array($tipo, ['GRUPO_SI_NO', 'SI_NO_FECHA', 'SI_NO', 'MATRIZ', 'CRONOLOGIA'], true) || is_array($_POST[$nombreCampo] ?? null)) {
                $valorCrudo = $_POST[$nombreCampo] ?? [];
                if (!is_array($valorCrudo)) {
                    $valorCrudo = [];
                }
                // MATRIZ con "opciones_por_fila" (cotejo Z21, 2026-09-11): esa
                // celda es una lista cerrada y distinta en cada fila (PCR:
                // Positivo/Negativo; ELISA: Reactivo/No reactivo), así que un
                // valor no declarado para ESA fila se descarta acá. El resto
                // de las matrices sigue siendo texto libre, como siempre.
                $configDelCampo = json_decode((string) ($campo['config'] ?? '{}'), true) ?: [];
                if ($tipo === 'MATRIZ' && !empty($configDelCampo['opciones_por_fila'])) {
                    $columnasDelCampo = $configDelCampo['columnas'] ?? [];
                    foreach ($configDelCampo['opciones_por_fila'] as $nombreColumna => $opcionesDeCadaFila) {
                        $indiceColumna = array_search($nombreColumna, $columnasDelCampo, true);
                        if ($indiceColumna === false) {
                            continue;
                        }
                        foreach ($opcionesDeCadaFila as $indiceFila => $opcionesFila) {
                            $valorCelda = $valorCrudo[$indiceFila][$indiceColumna] ?? null;
                            if ($valorCelda !== null && !in_array((string) $valorCelda, array_map('strval', $opcionesFila), true)) {
                                $valorCrudo[$indiceFila][$indiceColumna] = '';
                            }
                        }
                    }
                }
                // MATRIZ "columnas_condicionadas" (A50, 2026-09-14): la celda
                // se descarta si la otra columna de su fila no tiene el valor
                // declarado ("Otra prueba (cuál)" sin "Tipo de prueba" = Otra).
                if ($tipo === 'MATRIZ' && !empty($configDelCampo['columnas_condicionadas'])) {
                    $columnasDelCampo = array_map('strval', $configDelCampo['columnas'] ?? []);
                    foreach ($configDelCampo['columnas_condicionadas'] as $columnaCondicionada => $condicionColumna) {
                        $indiceCondicionada = array_search((string) $columnaCondicionada, $columnasDelCampo, true);
                        $indiceCondicion = array_search((string) ($condicionColumna['columna'] ?? ''), $columnasDelCampo, true);
                        if ($indiceCondicionada === false || $indiceCondicion === false) {
                            continue;
                        }
                        foreach ($valorCrudo as $indiceFila => $filaMatriz) {
                            if (is_array($filaMatriz) && (string) ($filaMatriz[$indiceCondicion] ?? '') !== (string) ($condicionColumna['valor'] ?? '')) {
                                $valorCrudo[$indiceFila][$indiceCondicionada] = '';
                            }
                        }
                    }
                }
                $valoresCampos[$campoId] = $valorCrudo;
                
                $vacio = empty(array_filter($valorCrudo, function($v) {
                    return is_array($v) ? !empty(array_filter($v)) : trim($v) !== '';
                }));

                if ($obligatorio && $vacio) {
                    $erroresCampos[$campoId] = 'Este campo es obligatorio.';
                } elseif (!$vacio) {
                    $paraGuardar[$campoId] = json_encode($valorCrudo, JSON_UNESCAPED_UNICODE);
                }
                continue;
            }

            $valor = trim((string) ($_POST[$nombreCampo] ?? ''));
            $valoresCampos[$campoId] = $valor;

            if ($valor === '') {
                if ($obligatorio) {
                    $erroresCampos[$campoId] = 'Este campo es obligatorio.';
                }
                continue;
            }

            // "desconocido" (A50, 2026-09-14): FECHA o NUMERO con la casilla
            // "Desconocido" marcada (campos/fecha.php, campos/numero.php). La
            // casilla usa el mismo name y va después del input, así que llega
            // VALOR_DESCONOCIDO. Solo lo aceptan los campos que lo declaran; en
            // los demás cae en "Ingresa una fecha/un número válido".
            if ($valor === VALOR_DESCONOCIDO && in_array($tipo, ['FECHA', 'NUMERO'], true)
                && !empty((json_decode((string) ($campo['config'] ?? ''), true) ?: [])['desconocido'])) {
                $paraGuardar[$campoId] = VALOR_DESCONOCIDO;
                continue;
            }

            switch ($tipo) {
                case 'NUMERO':
                    // "minimo" (Z21, 2026-09-13): piso declarado en el
                    // manifiesto (conteos que no pueden ser negativos).
                    $minimoNumero = (json_decode((string) ($campo['config'] ?? ''), true) ?: [])['minimo'] ?? null;
                    if (!is_numeric($valor)) {
                        $erroresCampos[$campoId] = 'Ingresa un número válido.';
                    } elseif ($minimoNumero !== null && (float) $valor < (float) $minimoNumero) {
                        $erroresCampos[$campoId] = 'No puede ser menor que ' . $minimoNumero . '.';
                    } else {
                        $paraGuardar[$campoId] = $valor;
                    }
                    break;
                case 'FECHA':
                    $iso = fechaIsoValida($valor);
                    if (!$iso) {
                        $erroresCampos[$campoId] = 'Ingresa una fecha válida.';
                    } else {
                        $paraGuardar[$campoId] = $iso;
                    }
                    break;
                case 'SELECT':
                    $validos = $campo['catalogo_id']
                        ? array_column(CatalogoItem::porCatalogo((int) $campo['catalogo_id']), 'valor')
                        : [];
                    if (!in_array($valor, $validos, true)) {
                        $erroresCampos[$campoId] = 'Selecciona una opción válida.';
                    } else {
                        $paraGuardar[$campoId] = $valor;
                    }
                    break;
                default: // TEXTO, TEXTAREA
                    // "formato" de un TEXTO (P96, 2026-09-13): "hora" se guarda
                    // HH:MM y "cie10" normalizado (P21.9); ver campos/texto.php.
                    $formatoTexto = $tipo === 'TEXTO'
                        ? ((json_decode((string) ($campo['config'] ?? ''), true) ?: [])['formato'] ?? null)
                        : null;
                    $valorNormalizado = match ($formatoTexto) {
                        'hora'  => horaValida($valor),
                        'cie10' => codigoCie10Normalizado($valor),
                        default => $valor,
                    };
                    if ($valorNormalizado === null) {
                        $erroresCampos[$campoId] = $formatoTexto === 'hora'
                            ? 'Ingresa una hora válida (HH:MM).'
                            : 'Ingresa un código CIE-10 válido: una letra, dos dígitos y, si corresponde, el subcódigo (P21.9).';
                    } else {
                        $valoresCampos[$campoId] = $valorNormalizado;
                        $paraGuardar[$campoId] = $valorNormalizado;
                    }
            }
        }

        $reglasCampos = jsonDeEnfermedad(Enfermedad::buscar($enfermedadId) ?? [], 'reglas_campos');
        if ($reglasCampos) {
            [$valoresCampos, $erroresCampos, $paraGuardar] = $this->aplicarReglasCampos($reglasCampos, $campos, $valoresCampos, $erroresCampos, $paraGuardar, $valoresNucleo);
        }

        return [$valoresCampos, $erroresCampos, $paraGuardar];
    }

    /**
     * Datos del núcleo que leen los campos dinámicos al validar: la fecha de
     * nacimiento ya validada (reglas "comparar_fechas", P96), el sexo (reglas
     * con "nucleo:sexo", B24) y los apellidos y nombres (campo TEXTO
     * "calculado", el código del paciente de B24).
     */
    private function valoresNucleoParaReglas(array $valoresFijos, ?string $fechaNacIso): array
    {
        return [
            'fecha_nac'        => $fechaNacIso,
            'sexo'             => (string) ($valoresFijos['sexo'] ?? ''),
            'apellido_paterno' => (string) ($valoresFijos['apellido_paterno'] ?? ''),
            'apellido_materno' => (string) ($valoresFijos['apellido_materno'] ?? ''),
            'nombres'          => (string) ($valoresFijos['nombres'] ?? ''),
        ];
    }

    /**
     * reglas_campos "clasificar" (Z21, 2026-09-13): la clasificación del caso
     * según los valores ya validados (clasificacionDerivada() en
     * ayudantes.php). null si la ficha no la calcula.
     */
    /**
     * reglas_campos "fallecido" (A50, 2026-09-14): caso.fallecido según los
     * valores ya validados (fallecidoDerivado() en ayudantes.php). null si la
     * ficha no lo calcula.
     */
    private function fallecidoCalculado(array $enfermedad, array $valoresCampos): ?int
    {
        if (!jsonDeEnfermedad($enfermedad, 'reglas_campos')) {
            return null;
        }
        $idPorClave = [];
        foreach (CampoDef::porEnfermedad((int) $enfermedad['id']) as $campoId => $campo) {
            $idPorClave[$campo['clave']] = (int) $campoId;
        }

        return fallecidoDerivado($enfermedad, fn(string $clave) => $valoresCampos[$idPorClave[$clave] ?? 0] ?? '');
    }

    /**
     * Valores crudos de los campo_def que llegaron en el POST (id => valor),
     * para decidir la rama antes de validar los campos dinámicos: los ajustes
     * del núcleo por rama (nucleoAjuste()) se usan al validar el documento y
     * los nombres, que va primero.
     */
    private function valoresCamposDesdePost(): array
    {
        $valores = [];
        foreach ($_POST as $nombre => $valor) {
            if (preg_match('/^campo_(\d+)$/', (string) $nombre, $partes)) {
                $valores[(int) $partes[1]] = is_array($valor) ? $valor : trim((string) $valor);
            }
        }

        return $valores;
    }

    private function clasificacionCalculada(array $enfermedad, array $valoresCampos): ?string
    {
        if (!fichaDerivaClasificacion($enfermedad)) {
            return null;
        }
        $idPorClave = [];
        foreach (CampoDef::porEnfermedad((int) $enfermedad['id']) as $campoId => $campo) {
            $idPorClave[$campo['clave']] = (int) $campoId;
        }

        return clasificacionDerivada(
            $enfermedad,
            fn(string $clave) => $valoresCampos[$idPorClave[$clave] ?? 0] ?? ''
        );
    }

    /**
     * reglas_campos (Z21, 2026-09-13, "Culminación del embarazo"): se aplican
     * DESPUÉS de leer todos los campos, así el orden del manifiesto no importa
     * (la condición puede estar debajo del campo que condiciona: "¿Aborto?"
     * va después de los N.º de nacidos vivos/óbitos fetales). No se confía en
     * que el navegador ya lo haya hecho:
     *   1. "fijar": con la condición cumplida, el valor declarado reemplaza lo
     *      que llegó (aborto -> 0 nacidos vivos y 0 óbitos fetales).
     *   2. "mostrar": con la condición sin cumplir, esos campos se guardan
     *      vacíos y sin error de obligatorio, y también sus hijos por
     *      depende_de (parto por cesárea y EE.SS. del parto sin nacidos vivos
     *      ni óbitos fetales).
     *   3. "suma_maxima": con la condición cumplida, la suma no puede pasar el
     *      tope; el mensaje del manifiesto va en el último campo de la suma.
     * Efectos de P96 (muerte fetal y neonatal, 2026-09-13), con la condición
     * cumplida:
     *   - "opciones" (justo después de "fijar"): el SELECT solo admite esos
     *     códigos; si queda uno solo, se guarda ese sin mirar el POST.
     *   - "comparar_fechas": días entre dos fechas (una puede ser del núcleo,
     *     "nucleo:fecha_nac") dentro de dias_minimo/dias_maximo; con las dos
     *     horas, el mínimo se compara con la hora incluida.
     *   - "maximo_dias_entre": un NUMERO de días no pasa los días entre dos
     *     fechas contando ambas.
     *   - "alguno_minimo": entre los NUMERO con dato, al menos uno llega a su
     *     mínimo.
     *   - "alguno_menor_que" (A50, 2026-09-14): el espejo, al menos uno queda
     *     por debajo de su límite.
     *   Los cuatro últimos no pisan un error que el campo ya tenga. El efecto
     *   "fallecido" no va acá: lo lee fallecidoCalculado() al guardar.
     * Un campo oculto por su sección o su propio depende_de no se toca.
     */
    private function aplicarReglasCampos(array $reglas, array $campos, array $valoresCampos, array $erroresCampos, array $paraGuardar, array $valoresNucleo = []): array
    {
        $idPorClave = [];
        foreach ($campos as $campoId => $campo) {
            $idPorClave[$campo['clave']] = (int) $campoId;
        }
        // "nucleo:sexo" (B24, 2026-09-14): la condición de una regla puede leer
        // un dato del núcleo ya leído del POST (valoresNucleoParaReglas()).
        $valorPorClave = function (string $clave) use (&$valoresCampos, $idPorClave, $valoresNucleo) {
            if (str_starts_with($clave, 'nucleo:')) {
                return (string) ($valoresNucleo[substr($clave, 7)] ?? '');
            }
            return $valoresCampos[$idPorClave[$clave] ?? 0] ?? '';
        };
        $ocultoPorDependencia = function (int $campoId) use ($campos, &$valoresCampos): bool {
            $campo = $campos[$campoId];
            $seccionOculta = !empty($campo['seccion_depende_de']) && !campoVisiblePorDependencia(
                ['depende_de' => $campo['seccion_depende_de'], 'valor_activador' => $campo['seccion_valor_activador']],
                $valoresCampos
            );
            return $seccionOculta || (!empty($campo['depende_de']) && !campoVisiblePorDependencia($campo, $valoresCampos));
        };
        $vaciar = function (int $campoId) use ($campos, &$valoresCampos, &$erroresCampos, &$paraGuardar): void {
            $tiposLista = ['MULTISELECT', 'GRUPO_SI_NO', 'SI_NO_FECHA', 'SI_NO', 'MATRIZ', 'CRONOLOGIA'];
            $valoresCampos[$campoId] = in_array($campos[$campoId]['tipo'], $tiposLista, true) ? [] : '';
            unset($erroresCampos[$campoId], $paraGuardar[$campoId]);
        };

        foreach ($reglas as $regla) {
            if (empty($regla['fijar']) || !condicionReglaCampos($regla['si'], $valorPorClave)) {
                continue;
            }
            foreach ($regla['fijar'] as $clave => $valorFijo) {
                $campoId = $idPorClave[$clave] ?? null;
                if ($campoId === null || $ocultoPorDependencia($campoId)) {
                    continue;
                }
                $valoresCampos[$campoId] = (string) $valorFijo;
                $paraGuardar[$campoId] = (string) $valorFijo;
                unset($erroresCampos[$campoId]);
            }
        }

        $permitidasPorCampo = [];
        foreach ($reglas as $regla) {
            if (empty($regla['opciones']) || !condicionReglaCampos($regla['si'], $valorPorClave)) {
                continue;
            }
            foreach ($regla['opciones'] as $clave => $codigos) {
                $campoId = $idPorClave[$clave] ?? null;
                if ($campoId === null || $ocultoPorDependencia($campoId)) {
                    continue;
                }
                $permitidasPorCampo[$campoId] = isset($permitidasPorCampo[$campoId])
                    ? array_values(array_intersect($permitidasPorCampo[$campoId], $codigos))
                    : array_values($codigos);
            }
        }
        foreach ($permitidasPorCampo as $campoId => $permitidas) {
            // MULTISELECT (B24, 2026-09-14): nada marcado fuera de la lista; no
            // se marca solo aunque quede una opción.
            if (is_array($valoresCampos[$campoId] ?? null)) {
                if (array_diff(array_map('strval', $valoresCampos[$campoId]), $permitidas)) {
                    $erroresCampos[$campoId] = 'Hay una opción marcada que no corresponde a lo registrado en la ficha: desmárcala.';
                }
                continue;
            }
            $valorActual = (string) ($valoresCampos[$campoId] ?? '');
            if (count($permitidas) === 1) {
                $valoresCampos[$campoId] = $permitidas[0];
                $paraGuardar[$campoId] = $permitidas[0];
                unset($erroresCampos[$campoId]);
            } elseif ($valorActual !== '' && !in_array($valorActual, $permitidas, true)) {
                $erroresCampos[$campoId] = 'Esta opción no corresponde a lo marcado en la ficha: elige una de las disponibles.';
            }
        }

        foreach ($reglas as $regla) {
            if (empty($regla['mostrar']) || condicionReglaCampos($regla['si'], $valorPorClave)) {
                continue;
            }
            foreach ($regla['mostrar'] as $clave) {
                if (isset($idPorClave[$clave])) {
                    $vaciar($idPorClave[$clave]);
                }
            }
        }
        do {
            $huboCambio = false;
            foreach ($campos as $campoId => $campo) {
                if (!empty($campo['depende_de']) && isset($paraGuardar[$campoId]) && !campoVisiblePorDependencia($campo, $valoresCampos)) {
                    $vaciar((int) $campoId);
                    $huboCambio = true;
                }
            }
        } while ($huboCambio);

        foreach ($reglas as $regla) {
            if (empty($regla['suma_maxima']) || !condicionReglaCampos($regla['si'], $valorPorClave)) {
                continue;
            }
            $suma = 0;
            $ultimoId = null;
            foreach ($regla['suma_maxima']['claves'] as $clave) {
                $campoId = $idPorClave[$clave] ?? null;
                if ($campoId === null) {
                    continue;
                }
                $ultimoId = $campoId;
                $valor = $valoresCampos[$campoId] ?? '';
                $suma += is_numeric($valor) ? (float) $valor : 0;
            }
            if ($ultimoId !== null && $suma > (float) $regla['suma_maxima']['valor']) {
                $erroresCampos[$ultimoId] = $regla['mensaje'];
            }
        }

        // Fecha (Y-m-d) de una referencia: clave FECHA de la ficha o
        // "nucleo:<columna>". null si está vacía o no es válida (ese campo ya
        // trae su propio error de fecha).
        $fechaDeReferencia = function (string $referencia) use (&$valoresCampos, $idPorClave, $valoresNucleo): ?DateTime {
            $crudo = str_starts_with($referencia, 'nucleo:')
                ? ($valoresNucleo[substr($referencia, 7)] ?? null)
                : ($valoresCampos[$idPorClave[$referencia] ?? 0] ?? null);
            $iso = is_string($crudo) ? fechaIsoValida($crudo) : null;
            return $iso ? new DateTime($iso) : null;
        };
        $diasEntre = function (DateTime $desde, DateTime $hasta): int {
            $diferencia = $desde->diff($hasta);
            return $diferencia->invert ? -$diferencia->days : $diferencia->days;
        };
        // Por referencia: tiene que ver los errores que agregan las reglas de
        // más abajo (una función flecha los copiaría al crearse).
        $conError = function (int $campoId) use (&$erroresCampos, $ocultoPorDependencia): bool {
            return isset($erroresCampos[$campoId]) || $ocultoPorDependencia($campoId);
        };

        foreach ($reglas as $regla) {
            if (empty($regla['comparar_fechas']) || !condicionReglaCampos($regla['si'], $valorPorClave)) {
                continue;
            }
            $comparar = $regla['comparar_fechas'];
            $claveDestino = str_starts_with($comparar['hasta'], 'nucleo:') ? $comparar['desde'] : $comparar['hasta'];
            $destino = $idPorClave[$claveDestino] ?? null;
            $desde = $fechaDeReferencia($comparar['desde']);
            $hasta = $fechaDeReferencia($comparar['hasta']);
            if ($destino === null || !$desde || !$hasta || $conError($destino)) {
                continue;
            }
            $dias = $diasEntre($desde, $hasta);
            $incumple = isset($comparar['dias_maximo']) && $dias > $comparar['dias_maximo'];
            if (!$incumple && isset($comparar['dias_minimo'])) {
                $horaDesde = isset($comparar['hora_desde']) ? horaValida((string) ($valoresCampos[$idPorClave[$comparar['hora_desde']] ?? 0] ?? '')) : null;
                $horaHasta = isset($comparar['hora_hasta']) ? horaValida((string) ($valoresCampos[$idPorClave[$comparar['hora_hasta']] ?? 0] ?? '')) : null;
                if ($horaDesde !== null && $horaHasta !== null) {
                    $minutos = ((new DateTime($hasta->format('Y-m-d') . ' ' . $horaHasta))->getTimestamp()
                        - (new DateTime($desde->format('Y-m-d') . ' ' . $horaDesde))->getTimestamp()) / 60;
                    $incumple = $minutos < $comparar['dias_minimo'] * 1440;
                } else {
                    $incumple = $dias < $comparar['dias_minimo'];
                }
            }
            if ($incumple) {
                $erroresCampos[$destino] = $comparar['mensaje'];
            }
        }

        foreach ($reglas as $regla) {
            if (empty($regla['maximo_dias_entre']) || !condicionReglaCampos($regla['si'], $valorPorClave)) {
                continue;
            }
            $maximo = $regla['maximo_dias_entre'];
            $campoId = $idPorClave[$maximo['clave']] ?? null;
            $desde = $fechaDeReferencia($maximo['desde']);
            $hasta = $fechaDeReferencia($maximo['hasta']);
            $numero = $campoId !== null ? ($valoresCampos[$campoId] ?? '') : '';
            if ($campoId === null || !$desde || !$hasta || !is_numeric($numero) || $conError($campoId)) {
                continue;
            }
            $dias = $diasEntre($desde, $hasta);
            // Con las fechas al revés ya avisa "comparar_fechas".
            if ($dias >= 0 && (float) $numero > $dias + 1) {
                $erroresCampos[$campoId] = $maximo['mensaje'];
            }
        }

        foreach ($reglas as $regla) {
            if (empty($regla['alguno_minimo']) || !condicionReglaCampos($regla['si'], $valorPorClave)) {
                continue;
            }
            $conDato = [];
            $alcanza = false;
            foreach ($regla['alguno_minimo']['claves'] as $clave => $minimo) {
                $campoId = $idPorClave[$clave] ?? null;
                if ($campoId === null || $ocultoPorDependencia($campoId)) {
                    continue;
                }
                $valor = $valoresCampos[$campoId] ?? '';
                if (is_numeric($valor)) {
                    $conDato[] = $campoId;
                    $alcanza = $alcanza || (float) $valor >= (float) $minimo;
                }
            }
            if ($conDato && !$alcanza && !isset($erroresCampos[$conDato[0]])) {
                $erroresCampos[$conDato[0]] = $regla['alguno_minimo']['mensaje'];
            }
        }

        // "alguno_menor_que" (A50, 2026-09-14): el espejo de "alguno_minimo".
        // Entre los NUMERO con dato, al menos uno queda por debajo de su
        // límite (un aborto tiene menos de 22 semanas o menos de 500 g). Un
        // "Desconocido" no es dato.
        foreach ($reglas as $regla) {
            if (empty($regla['alguno_menor_que']) || !condicionReglaCampos($regla['si'], $valorPorClave)) {
                continue;
            }
            $conDato = [];
            $quedaDebajo = false;
            foreach ($regla['alguno_menor_que']['claves'] as $clave => $limite) {
                $campoId = $idPorClave[$clave] ?? null;
                if ($campoId === null || $ocultoPorDependencia($campoId)) {
                    continue;
                }
                $valor = $valoresCampos[$campoId] ?? '';
                if (is_numeric($valor)) {
                    $conDato[] = $campoId;
                    $quedaDebajo = $quedaDebajo || (float) $valor < (float) $limite;
                }
            }
            if ($conDato && !$quedaDebajo && !isset($erroresCampos[$conDato[0]])) {
                $erroresCampos[$conDato[0]] = $regla['alguno_menor_que']['mensaje'];
            }
        }

        return [$valoresCampos, $erroresCampos, $paraGuardar];
    }

    /**
     * Reconstruye $valoresCampos con el mismo formato que espera
     * partials/secciones-clinicas.php (MULTISELECT como array) a partir de
     * lo guardado en caso_valor (texto plano, MULTISELECT separado por comas).
     */
    private function expandirValoresGuardados(int $enfermedadId, array $valoresCrudo): array
    {
        $camposDef = CampoDef::porEnfermedad($enfermedadId);
        $valores = [];

        foreach ($camposDef as $campoId => $campo) {
            $crudo = $valoresCrudo[$campoId] ?? null;
            if ($campo['tipo'] === 'MULTISELECT') {
                $valores[$campoId] = $crudo !== null && $crudo !== '' ? explode(',', $crudo) : [];
            } elseif (in_array($campo['tipo'], ['GRUPO_SI_NO', 'SI_NO_FECHA', 'SI_NO', 'MATRIZ', 'CRONOLOGIA'], true)) {
                $valores[$campoId] = $crudo ? json_decode($crudo, true) ?? [] : [];
            } else {
                $decoded = ($crudo && (str_starts_with($crudo, '{') || str_starts_with($crudo, '['))) ? json_decode($crudo, true) : null;
                $valores[$campoId] = is_array($decoded) ? $decoded : ($crudo ?? '');
            }
        }

        return $valores;
    }

    private function valoresFijosPorDefecto(string $hoyIso): array
    {
        return [
            'establecimiento_id' => (string) (Auth::usuario()['establecimiento_id'] ?? ''),
            'fecha_notif'        => $hoyIso,
            'tipo_doc'           => 'DNI',
            'num_doc'            => '',
            'apellido_paterno'   => '',
            'apellido_materno'   => '',
            'nombres'            => '',
            'sexo'               => '',
            'fecha_nac'          => '',
            'fecha_nac_desconocida' => '',
            'nacimiento_distrito_id' => '',
            'edad_valor'         => '',
            'edad_unidad'        => '',
            'celular'            => '',
            'nacionalidad'       => 'Peruana',
            'direccion'          => '',
            'referencia_localizar' => '',
            'tipo_zona'          => '',
            'nombre_zona'        => '',
            'tipo_via'           => '',
            'nombre_via'         => '',
            'numero'             => '',
            'mz_lote'            => '',
            'tiempo_residencia'  => '',
            'tiempo_reside_anios' => '',
            'tiempo_reside_meses' => '',
            'anterior_distrito_id' => '',
            'anterior_tipo_zona'   => '',
            'anterior_nombre_zona' => '',
            'anterior_tipo_via'    => '',
            'anterior_nombre_via'  => '',
            'anterior_numero'      => '',
            'anterior_mz_lote'     => '',
            'lugar_contagio_distrito_id' => '',
            'lugar_contagio_localidad'   => '',
            'n_historia_clinica' => '',
            'localidad'          => '',
            'etnia'              => '',
            'etnia_otra'         => '',
            'pueblo_etnico'      => '',
            'ocupacion'          => '',
            'estado_civil'       => '',
            'nombre_tutor'       => '',
            'celular_tutor'      => '',
            'gestante'           => '',
            'fur'                => '',
            'semanas_gestacion'  => '',
            'trimestre_gestacion'=> '',
            'tipo_captacion'         => '',
            'lugar_captacion'        => '',
            'clasificacion_captacion' => '',
            'investigador_nombre'    => Auth::usuario()['nombre'] ?? '',
            'investigador_cargo'     => '',
            'investigador_profesion' => '',
            'investigador_profesion_otra' => '',
            'investigador_telefono'  => '',
            'investigador_email'     => Auth::usuario()['email'] ?? '',
            'fecha_investigacion'    => $hoyIso,
        ];
    }

    private function datosEstablecimiento(): array
    {
        $usuario = Auth::usuario();
        $puedeElegir = $usuario['rol'] === 'ADMIN';

        $establecimientoUsuarioNombre = '';
        if (!$puedeElegir) {
            $est = $usuario['establecimiento_id'] ? Establecimiento::buscar((int) $usuario['establecimiento_id']) : null;
            $establecimientoUsuarioNombre = $est['nombre'] ?? 'Sin establecimiento asignado';
        }

        return [
            'puedeElegirEstablecimiento'    => $puedeElegir,
            'establecimientos'              => $puedeElegir ? Establecimiento::todos('nombre') : [],
            'establecimientoUsuarioNombre'  => $establecimientoUsuarioNombre,
        ];
    }

    /**
     * Sanea los campos núcleo de captación/paciente/investigador
     * (AUDITORIA_FICHA_DIFTERIA.md, punto 2 y 8) a partir de lo ya capturado
     * en $valoresFijos. Gestante solo se guarda si sexo=F, y semanas de
     * gestación solo si gestante=Sí — igual que el toggle en ficha.js, pero
     * revalidado del lado servidor.
     *
     * @return array{persona: array, caso: array}
     */
    private function sanearCamposNucleo(array $valoresFijos, array $enfermedad): array
    {
        // unidades_edad (entrada F): a diferencia de etnia/pueblo_etnico, la
        // whitelist no es fija -- depende de qué unidades declaró la ficha
        // activa (enfermedad.unidades_edad). Si la ficha no declaró nada,
        // edad_valor/edad_unidad se descartan aunque vengan en el POST.
        $unidadesEdadPermitidas = [];
        if (!empty($enfermedad['unidades_edad'])) {
            $decodificadoUnidadesEdad = json_decode($enfermedad['unidades_edad'], true);
            $unidadesEdadPermitidas = is_array($decodificadoUnidadesEdad) ? $decodificadoUnidadesEdad : [];
        }
        // Híbrido decidido el 2026-08-27 (ver ayudantes.php,
        // edadConUnidadDesdeFecha()): si hay fecha de nacimiento, la edad se
        // CALCULA a la fecha de notificación, ignorando lo que venga en el
        // POST -- no se puede forzar un valor manual por POST cuando el
        // sistema puede calcularlo. El input manual (edad_valor/edad_unidad
        // del formulario) solo se respeta cuando no hay fecha de nacimiento
        // (paciente sin documento, edad aproximada).
        $edadCalculada = $unidadesEdadPermitidas && !empty($valoresFijos['fecha_nac'])
            ? edadConUnidadDesdeFecha($valoresFijos['fecha_nac'], $valoresFijos['fecha_notif'] ?? null, $unidadesEdadPermitidas)
            : null;
        if ($edadCalculada !== null) {
            $edadValor = $edadCalculada['valor'];
            $edadUnidad = $edadCalculada['unidad'];
        } else {
            $edadUnidad = in_array($valoresFijos['edad_unidad'] ?? '', $unidadesEdadPermitidas, true) ? $valoresFijos['edad_unidad'] : null;
            $edadValor = ($edadUnidad !== null && is_numeric($valoresFijos['edad_valor'] ?? '') && (int) $valoresFijos['edad_valor'] >= 0)
                ? (int) $valoresFijos['edad_valor']
                : null;
            if ($edadValor === null) {
                $edadUnidad = null;
            }
        }

        // nucleo_condicional (cotejo Z21, 2026-09-11): los bloques que la
        // ficha no pide para la rama elegida se descartan acá aunque vengan
        // forzados en el POST -- que el navegador los haya ocultado no es
        // garantía de nada (mismo criterio que detalle_domicilio/gestante).
        $etniaActiva = $this->bloqueNucleoActivoDesdePost($enfermedad, 'etnia');
        $residenciaActiva = $this->bloqueNucleoActivoDesdePost($enfermedad, 'residencia');

        // 'sexo' condicionado (pedido del usuario, 2026-09-12): si la rama
        // elegida no pregunta el sexo, manda el valor_fijo declarado y no lo
        // que llegue en el POST. Se resuelve ACÁ, antes del bloque de
        // gestante/FUR de más abajo, para que toda la función vea el mismo
        // valor. En las 23 fichas que no lo declaran, $sexoCondicionado es
        // false y nada cambia.
        $sexoCondicionado = condicionNucleo($enfermedad, 'sexo') !== null;
        if ($sexoCondicionado && !$this->bloqueNucleoActivoDesdePost($enfermedad, 'sexo')) {
            $valoresFijos['sexo'] = valorFijoNucleo($enfermedad, 'sexo') ?? '';
        }

        $etnias = ['MESTIZO', 'ANDINO', 'ASIATICO_DESCENDIENTE', 'AFRODESCENDIENTE', 'INDIGENA_AMAZONICO', 'OTRO'];
        $etnia = ($etniaActiva && in_array($valoresFijos['etnia'], $etnias, true)) ? $valoresFijos['etnia'] : null;
        $etniaOtra = ($etnia === 'OTRO' && $valoresFijos['etnia_otra'] !== '') ? $valoresFijos['etnia_otra'] : null;

        // Mismas 19 opciones que catalogo_id=537 (b05_pueblo_etnico_o_etnia,
        // ya retirado) y que MAPA_GRUPO_ETNICO en ficha.js -- cascada desde
        // 'etnia', no depende de a qué grupo pertenezcan acá.
        $pueblosEtnicos = ['Quechua', 'Aymara', 'Jaqaru', 'Uro', 'Asháninka', 'Awajún', 'Shipibo-Konibo', 'Yánesha', 'Kukama Kukamiria', 'Achuar', 'Bora', 'Matsés', 'Ese Eja', 'Harakbut', 'Afroperuano', 'No aplica', 'Chino-peruano', 'Japonés-peruano', 'Otro'];
        // El pueblo étnico es parte del bloque 'etnia' de nucleo_condicional
        // (B24, 2026-09-14): la rama que no pide la etnia tampoco lo guarda.
        $puebloEtnico = ($etniaActiva && in_array($valoresFijos['pueblo_etnico'], $pueblosEtnicos, true)) ? $valoresFijos['pueblo_etnico'] : null;

        // detalle_domicilio (Entrada J acotada al bloque de domicilio):
        // mismo criterio que unidades_edad -- opt-in por ficha, no whitelist
        // fija. Un campo se descarta si la ficha activa no lo declaró, aunque
        // venga en el POST (prueba negativa: forzar estos 6 por POST en una
        // ficha que no los declara no debe persistir nada).
        $detalleDomicilioPermitido = [];
        if (!empty($enfermedad['detalle_domicilio'])) {
            $decodificadoDetalleDomicilio = json_decode($enfermedad['detalle_domicilio'], true);
            $detalleDomicilioPermitido = is_array($decodificadoDetalleDomicilio) ? $decodificadoDetalleDomicilio : [];
        }
        $tiposZona = ['URBANO', 'PERIURBANO', 'RURAL'];
        $tipoZona = (in_array('TIPO_ZONA', $detalleDomicilioPermitido, true) && in_array($valoresFijos['tipo_zona'] ?? '', $tiposZona, true))
            ? $valoresFijos['tipo_zona'] : null;
        $tipoVia = (in_array('TIPO_VIA', $detalleDomicilioPermitido, true) && ($valoresFijos['tipo_via'] ?? '') !== '')
            ? $valoresFijos['tipo_via'] : null;
        $nombreVia = (in_array('NOMBRE_VIA', $detalleDomicilioPermitido, true) && ($valoresFijos['nombre_via'] ?? '') !== '')
            ? $valoresFijos['nombre_via'] : null;
        $numeroDomicilio = (in_array('NUMERO', $detalleDomicilioPermitido, true) && ($valoresFijos['numero'] ?? '') !== '')
            ? $valoresFijos['numero'] : null;
        $mzLote = (in_array('MZ_LOTE', $detalleDomicilioPermitido, true) && ($valoresFijos['mz_lote'] ?? '') !== '')
            ? $valoresFijos['mz_lote'] : null;
        $tiempoResidencia = (in_array('TIEMPO_RESIDENCIA', $detalleDomicilioPermitido, true) && ($valoresFijos['tiempo_residencia'] ?? '') !== '')
            ? $valoresFijos['tiempo_residencia'] : null;
        // NOMBRE_ZONA (cotejo B57, ítem 3.5): hermano de tipo_zona, mismo
        // criterio de gating.
        $nombreZona = (in_array('NOMBRE_ZONA', $detalleDomicilioPermitido, true) && ($valoresFijos['nombre_zona'] ?? '') !== '')
            ? $valoresFijos['nombre_zona'] : null;

        // nucleo_incluidos (PETICION_HC_Y_LABORATORIO.md, Parte 1): mismo
        // criterio de opt-in que detalle_domicilio -- se descarta si la
        // ficha activa no lo declaró, aunque venga en el POST.
        $nucleoIncluidosPermitido = [];
        if (!empty($enfermedad['nucleo_incluidos'])) {
            $decodificadoNucleoIncluidos = json_decode($enfermedad['nucleo_incluidos'], true);
            $nucleoIncluidosPermitido = is_array($decodificadoNucleoIncluidos) ? $decodificadoNucleoIncluidos : [];
        }
        $nHistoriaClinica = (in_array('n_historia_clinica', $nucleoIncluidosPermitido, true) && ($valoresFijos['n_historia_clinica'] ?? '') !== '')
            ? $valoresFijos['n_historia_clinica'] : null;
        $estadosCiviles = ['SOLTERO', 'CASADO', 'CONVIVIENTE', 'SEPARADO', 'VIUDO'];
        $estadoCivil = (in_array('estado_civil', $nucleoIncluidosPermitido, true) && in_array($valoresFijos['estado_civil'] ?? '', $estadosCiviles, true))
            ? $valoresFijos['estado_civil'] : null;
        // nacimiento_distrito_id (cotejo B55, "I. Datos generales" -- pág. 45
        // del PDF): mismo criterio de opt-in que n_historia_clinica/estado_civil.
        $nacimientoDistritoId = (in_array('nacimiento_distrito_id', $nucleoIncluidosPermitido, true) && ($valoresFijos['nacimiento_distrito_id'] ?? '') !== '')
            ? $valoresFijos['nacimiento_distrito_id'] : null;

        // migracion_reciente (cotejo B57, sección "IV. Migración"): opt-in a
        // nivel de ficha completa (booleano, no lista) -- si la ficha activa
        // no lo declara, años/meses y domicilio anterior se descartan aunque
        // vengan en el POST. Domicilio anterior solo se guarda si además la
        // condición del PDF se cumple (años×12+meses < 6), revalidada acá
        // igual que gestante/semanas de gestación más abajo -- nunca confiar
        // en que el bloque estaba oculto en el navegador.
        $tiempoResideAnios = null;
        $tiempoResideMeses = null;
        $anteriorDistritoId = null;
        $anteriorTipoZona = null;
        $anteriorNombreZona = null;
        $anteriorTipoVia = null;
        $anteriorNombreVia = null;
        $anteriorNumero = null;
        $anteriorMzLote = null;
        if (!empty($enfermedad['migracion_reciente'])) {
            $tiempoResideAnios = (($valoresFijos['tiempo_reside_anios'] ?? '') !== '' && is_numeric($valoresFijos['tiempo_reside_anios']))
                ? max(0, (int) $valoresFijos['tiempo_reside_anios']) : null;
            $tiempoResideMeses = (($valoresFijos['tiempo_reside_meses'] ?? '') !== '' && is_numeric($valoresFijos['tiempo_reside_meses']))
                ? max(0, (int) $valoresFijos['tiempo_reside_meses']) : null;
            $resideMenosDe6Meses = ($tiempoResideAnios !== null || $tiempoResideMeses !== null)
                && (($tiempoResideAnios ?? 0) * 12 + ($tiempoResideMeses ?? 0)) < 6;
            if ($resideMenosDe6Meses) {
                $anteriorDistritoId = ($valoresFijos['anterior_distrito_id'] ?? '') !== '' ? $valoresFijos['anterior_distrito_id'] : null;
                $anteriorTipoZona = (in_array('TIPO_ZONA', $detalleDomicilioPermitido, true) && in_array($valoresFijos['anterior_tipo_zona'] ?? '', $tiposZona, true))
                    ? $valoresFijos['anterior_tipo_zona'] : null;
                $anteriorNombreZona = (in_array('NOMBRE_ZONA', $detalleDomicilioPermitido, true) && ($valoresFijos['anterior_nombre_zona'] ?? '') !== '')
                    ? $valoresFijos['anterior_nombre_zona'] : null;
                $anteriorTipoVia = (in_array('TIPO_VIA', $detalleDomicilioPermitido, true) && ($valoresFijos['anterior_tipo_via'] ?? '') !== '')
                    ? $valoresFijos['anterior_tipo_via'] : null;
                $anteriorNombreVia = (in_array('NOMBRE_VIA', $detalleDomicilioPermitido, true) && ($valoresFijos['anterior_nombre_via'] ?? '') !== '')
                    ? $valoresFijos['anterior_nombre_via'] : null;
                $anteriorNumero = (in_array('NUMERO', $detalleDomicilioPermitido, true) && ($valoresFijos['anterior_numero'] ?? '') !== '')
                    ? $valoresFijos['anterior_numero'] : null;
                $anteriorMzLote = (in_array('MZ_LOTE', $detalleDomicilioPermitido, true) && ($valoresFijos['anterior_mz_lote'] ?? '') !== '')
                    ? $valoresFijos['anterior_mz_lote'] : null;
            }
        }

        $gestante = null;
        $fur = null;
        $semanasGestacion = null;
        $trimestreGestacion = null;
        if ($valoresFijos['sexo'] === 'F' && in_array($valoresFijos['gestante'], ['0', '1'], true)) {
            $gestante = (int) $valoresFijos['gestante'];
            if ($gestante === 1) {
                // FUR (A44, ítem 4 del PDF): junto a Gestante/Edad gestacional,
                // mismo criterio de "solo si gestante=Sí" que semanas/trimestre.
                if (($valoresFijos['fur'] ?? '') !== '') {
                    $fur = fechaIsoValida($valoresFijos['fur']);
                }
                if (is_numeric($valoresFijos['semanas_gestacion'])) {
                    $semanasGestacion = (int) $valoresFijos['semanas_gestacion'];
                }
                if (in_array($valoresFijos['trimestre_gestacion'], ['I', 'II', 'III'], true)) {
                    $trimestreGestacion = $valoresFijos['trimestre_gestacion'];
                }
            }
        }

        // Lugar probable de contagio (cotejo B57, ítem 1.1-1.4): único bloque
        // por caso, sin lista -- mismo criterio de "solo se guarda
        // distrito_id" que el domicilio de persona (departamento/provincia
        // se derivan por join). No gateado por ninguna whitelist de ficha:
        // solo B57 pinta estos inputs (secciones-clinicas.php), así que en
        // cualquier otra ficha simplemente no llegan en el POST y quedan
        // NULL, igual que edad_valor/investigador_*.
        $lugarContagioDistritoId = ($valoresFijos['lugar_contagio_distrito_id'] ?? '') !== '' ? $valoresFijos['lugar_contagio_distrito_id'] : null;
        $lugarContagioLocalidad = ($valoresFijos['lugar_contagio_localidad'] ?? '') !== '' ? $valoresFijos['lugar_contagio_localidad'] : null;

        // nucleo_omitidos: 'captacion' / 'investigador' (cotejo Z21,
        // 2026-09-11): si la ficha no pide el bloque, sus columnas quedan en
        // NULL aunque el POST venga armado a mano.
        $captacionOmitida = nucleoOmitido($enfermedad, 'captacion');
        // nucleo_ajustes.investigador (A50, 2026-09-14): de la tarjeta solo se
        // guardan los campos que la ficha declara (el notificador: su nombre).
        // campoInvestigador() ya devuelve null con la tarjeta omitida.
        $guardaInvestigador = fn(string $campoTarjeta): bool => campoInvestigador($enfermedad, $campoTarjeta) !== null;

        $tipoCaptacion = (!$captacionOmitida && in_array($valoresFijos['tipo_captacion'], ['ACTIVA', 'PASIVA'], true)) ? $valoresFijos['tipo_captacion'] : null;
        $lugarCaptacion = (!$captacionOmitida && in_array($valoresFijos['lugar_captacion'], ['INSTITUCIONAL', 'COMUNIDAD'], true)) ? $valoresFijos['lugar_captacion'] : null;
        $clasificacionCaptacion = (!$captacionOmitida && in_array($valoresFijos['clasificacion_captacion'], ['CONFIRMADO', 'PROBABLE', 'SOSPECHOSO'], true)) ? $valoresFijos['clasificacion_captacion'] : null;

        return [
            // El 'sexo' se agrega al final SOLO si la ficha lo condiciona: así
            // pisa el valor que crear()/actualizar() toman del POST. Las 23
            // fichas que no lo declaran siguen guardándolo tal cual, sin
            // pasar por acá.
            'persona' => array_merge([
                'celular'            => $valoresFijos['celular'] !== '' ? $valoresFijos['celular'] : null,
                'nacionalidad'       => $valoresFijos['nacionalidad'] !== '' ? $valoresFijos['nacionalidad'] : null,
                'direccion'          => $valoresFijos['direccion'] !== '' ? $valoresFijos['direccion'] : null,
                'referencia_localizar' => $valoresFijos['referencia_localizar'] !== '' ? $valoresFijos['referencia_localizar'] : null,
                'tipo_zona'          => $tipoZona,
                'nombre_zona'        => $nombreZona,
                'tipo_via'           => $tipoVia,
                'nombre_via'         => $nombreVia,
                'numero'             => $numeroDomicilio,
                'mz_lote'            => $mzLote,
                'tiempo_residencia'  => $tiempoResidencia,
                'tiempo_reside_anios' => $tiempoResideAnios,
                'tiempo_reside_meses' => $tiempoResideMeses,
                'anterior_distrito_id' => $anteriorDistritoId,
                'anterior_tipo_zona'   => $anteriorTipoZona,
                'anterior_nombre_zona' => $anteriorNombreZona,
                'anterior_tipo_via'    => $anteriorTipoVia,
                'anterior_nombre_via'  => $anteriorNombreVia,
                'anterior_numero'      => $anteriorNumero,
                'anterior_mz_lote'     => $anteriorMzLote,
                'n_historia_clinica' => $nHistoriaClinica,
                'nacimiento_distrito_id' => $nacimientoDistritoId,
                // "localidad" es parte del bloque "residencia" de
                // nucleo_condicional (cotejo Z21): si la rama elegida no lo
                // pide, no se guarda aunque llegue en el POST.
                'localidad'          => ($residenciaActiva && $valoresFijos['localidad'] !== '') ? $valoresFijos['localidad'] : null,
                'etnia'              => $etnia,
                'etnia_otra'         => $etniaOtra,
                'pueblo_etnico'      => $puebloEtnico,
                'ocupacion'          => $valoresFijos['ocupacion'] !== '' ? $valoresFijos['ocupacion'] : null,
                'estado_civil'       => $estadoCivil,
                'nombre_tutor'       => $valoresFijos['nombre_tutor'] !== '' ? $valoresFijos['nombre_tutor'] : null,
                'celular_tutor'      => $valoresFijos['celular_tutor'] !== '' ? $valoresFijos['celular_tutor'] : null,
                'gestante'           => $gestante,
                'fur'                => $fur,
                'semanas_gestacion'  => $semanasGestacion,
                'trimestre_gestacion'=> $trimestreGestacion,
            ], $sexoCondicionado ? ['sexo' => $valoresFijos['sexo'] !== '' ? $valoresFijos['sexo'] : null] : []),
            'caso' => [
                'edad_valor'              => $edadValor,
                'edad_unidad'             => $edadUnidad,
                'lugar_contagio_distrito_id' => $lugarContagioDistritoId,
                'lugar_contagio_localidad'   => $lugarContagioLocalidad,
                'tipo_captacion'          => $tipoCaptacion,
                'lugar_captacion'         => $lugarCaptacion,
                'clasificacion_captacion' => $clasificacionCaptacion,
                // nucleo_omitidos: 'investigador' (cotejo Z21) -- la ficha que
                // no trae ese bloque en el PDF tampoco lo guarda. Incluye
                // fecha_investigacion, que vive en esa misma tarjeta.
                'investigador_nombre'     => ($guardaInvestigador('nombre') && $valoresFijos['investigador_nombre'] !== '') ? $valoresFijos['investigador_nombre'] : null,
                'investigador_cargo'      => ($guardaInvestigador('cargo') && $valoresFijos['investigador_cargo'] !== '') ? $valoresFijos['investigador_cargo'] : null,
                'investigador_profesion'  => ($guardaInvestigador('profesion') && $valoresFijos['investigador_profesion'] !== '') ? $valoresFijos['investigador_profesion'] : null,
                'investigador_telefono'   => ($guardaInvestigador('telefono') && $valoresFijos['investigador_telefono'] !== '') ? $valoresFijos['investigador_telefono'] : null,
                'investigador_email'      => ($guardaInvestigador('email') && $valoresFijos['investigador_email'] !== '') ? $valoresFijos['investigador_email'] : null,
                'fecha_investigacion'     => ($guardaInvestigador('fecha_investigacion') && $valoresFijos['fecha_investigacion'] !== '') ? fechaIsoValida($valoresFijos['fecha_investigacion']) : null,
            // nucleo_ajustes.fallecido (P96, 2026-09-13): todo caso de la ficha
            // es una defunción. Va al final para pisar el "Fallecido" que
            // actualizar() lee del POST (la tarjeta de clasificación ni se pinta).
            ] + (nucleoAjuste($enfermedad, 'fallecido') === true ? ['fallecido' => 1] : [])
            // fecha_nac_desconocida (A50, 2026-09-14): solo en las fichas que
            // ofrecen la casilla; crear()/actualizar() ya la validaron contra
            // la rama. Las demás no escriben la columna.
              + (fichaAdmiteFechaNacDesconocida($enfermedad) ? ['fecha_nac_desconocida' => ($valoresFijos['fecha_nac_desconocida'] ?? '') === '1' ? 1 : 0] : []),
        ];
    }

    /**
     * Datos y catálogos para la condición del paciente en "Nueva ficha"
     * (radio EFECTIVO/DERECHOHABIENTE/PARTICULAR + campos por condición),
     * con valores en blanco.
     */
    private function datosPnp(array $enfermedad = []): array
    {
        return [
            // nucleo_ajustes.condiciones_paciente (P96): Particular, salvo
            // que la ficha no la admita.
            'condicionPaciente' => condicionPacientePorDefecto($enfermedad),
            'valoresPnp'        => [
                'cip' => '', 'situacion_pnp' => '', 'grado_id' => '', 'categoria_pnp' => '',
                'vinculo_titular' => '', 'doc_titular' => '', 'titular_id' => '', 'titular_nombre' => '',
            ],
            'grados' => GradoPnp::todos('jerarquia'),
        ];
    }

    /**
     * Igual que datosPnp() pero precargando lo ya guardado del paciente, para
     * el formulario de edición.
     */
    private function datosPnpEdicion(array $caso): array
    {
        $datos = $this->datosPnp();
        $datos['condicionPaciente'] = $caso['condicion'] ?? 'PARTICULAR';

        $titularNombre = '';
        if (!empty($caso['titular_id'])) {
            $titularNombre = Persona::nombreCompletoPnp([
                'apellido_paterno'  => $caso['titular_apellido_paterno'] ?? '',
                'apellido_materno'  => $caso['titular_apellido_materno'] ?? '',
                'nombres'           => $caso['titular_nombres'] ?? '',
                'grado_abreviatura' => $caso['titular_grado_abreviatura'] ?? '',
            ]);
        }

        $datos['valoresPnp'] = [
            'cip'             => $caso['cip'] ?? '',
            'situacion_pnp'   => $caso['situacion_pnp'] ?? '',
            'grado_id'        => $caso['grado_id'] ?? '',
            'categoria_pnp'   => $caso['categoria_pnp'] ?? '',
            'vinculo_titular' => $caso['vinculo_titular'] ?? '',
            'doc_titular'     => '',
            'titular_id'      => $caso['titular_id'] ?? '',
            'titular_nombre'  => $titularNombre,
        ];

        return $datos;
    }

    /**
     * Lee del POST la condición del paciente y sus campos dependientes.
     * Sanea del lado servidor: solo persiste grado/situación/categoría/CIP
     * cuando la condición es EFECTIVO, y solo vínculo/titular cuando es
     * DERECHOHABIENTE — cualquier combinación imposible se descarta en vez
     * de guardarse.
     *
     * nucleo_ajustes.condiciones_paciente (P96, 2026-09-14): una condición que
     * la ficha no admite (un fallecido fetal o neonatal como efectivo PNP) no
     * se descarta en silencio, vuelve como 'error': el formulario ni siquiera
     * la ofrece, así que solo llega en un POST armado a mano.
     *
     * @return array{datos: array, vista: array, error: ?string}
     */
    private function leerDatosPnp(array $enfermedad, array $valoresRama = []): array
    {
        $condicion = $_POST['condicion'] ?? condicionPacientePorDefecto($enfermedad, $valoresRama);
        if (!in_array($condicion, array_keys(CONDICIONES_PACIENTE), true)) {
            $condicion = condicionPacientePorDefecto($enfermedad, $valoresRama);
        }
        // Por rama (A50, 2026-09-14): el producto de la gestación no admite
        // efectivo PNP; la madre sí.
        $condicionesPermitidas = condicionesPacientePermitidas($enfermedad, $valoresRama);
        $errorCondicion = in_array($condicion, $condicionesPermitidas, true)
            ? null
            : 'En esta ficha la condición del paciente solo puede ser '
                . implode(' o ', array_map(fn(string $c): string => CONDICIONES_PACIENTE[$c], $condicionesPermitidas)) . '.';

        $gradoId = $_POST['grado_id'] ?? '';
        $situacion = $_POST['situacion_pnp'] ?? '';
        $cip = trim($_POST['cip'] ?? '');
        $categoriaPnp = $_POST['categoria_pnp'] ?? '';
        $vinculoTitular = $_POST['vinculo_titular'] ?? '';
        $titularId = $_POST['titular_id'] ?? '';

        $grados = GradoPnp::todos('jerarquia');

        $datos = [
            'condicion'       => $condicion,
            'cip'             => null,
            'situacion_pnp'   => null,
            'grado_id'        => null,
            'categoria_pnp'   => null,
            'titular_id'      => null,
            'vinculo_titular' => null,
        ];

        if ($condicion === 'EFECTIVO' && $gradoId !== '') {
            $datos['grado_id'] = (int) $gradoId;

            $gradoActual = null;
            foreach ($grados as $g) {
                if ((int) $g['id'] === $datos['grado_id']) {
                    $gradoActual = $g;
                    break;
                }
            }

            if ($gradoActual) {
                if (in_array($situacion, ['ACTIVIDAD', 'RETIRO', 'DISPONIBILIDAD'], true)) {
                    $datos['situacion_pnp'] = $situacion;
                }

                $nivel = $gradoActual['nivel'];
                if (str_starts_with($nivel, 'OFICIAL_') || $nivel === 'SUBOFICIAL') {
                    $datos['cip'] = $cip !== '' ? $cip : null;
                    if (in_array($categoriaPnp, ['ARMAS', 'SERVICIOS', 'ASIMILADO'], true)) {
                        $datos['categoria_pnp'] = $categoriaPnp;
                    }
                } elseif ($nivel === 'CADETE' || $nivel === 'ALUMNO') {
                    $datos['cip'] = $cip !== '' ? $cip : null;
                }
                // EMPLEADO_CIVIL: sin categoría ni CIP.
            }
        } elseif ($condicion === 'DERECHOHABIENTE') {
            if (in_array($vinculoTitular, ['CONYUGE', 'CONVIVIENTE', 'HIJO', 'PADRE', 'MADRE', 'OTRO'], true)) {
                $datos['vinculo_titular'] = $vinculoTitular;
            }
            if ($titularId !== '') {
                $titular = Persona::buscar((int) $titularId);
                if ($titular && ($titular['condicion'] ?? '') === 'EFECTIVO') {
                    $datos['titular_id'] = (int) $titularId;
                }
            }
        }

        return [
            'datos' => $datos,
            'error' => $errorCondicion,
            'vista' => [
                'condicionPaciente' => $condicion,
                'valoresPnp' => [
                    'cip'             => $cip,
                    'situacion_pnp'   => $situacion,
                    'grado_id'        => $gradoId,
                    'categoria_pnp'   => $categoriaPnp,
                    'vinculo_titular' => $vinculoTitular,
                    'doc_titular'     => trim($_POST['doc_titular'] ?? ''),
                    'titular_id'      => $datos['titular_id'] ?? '',
                    'titular_nombre'  => trim($_POST['titular_nombre'] ?? ''),
                ],
                'grados' => $grados,
            ],
        ];
    }

    /**
     * Endpoint AJAX del botón "Buscar titular" (derechohabiente): busca una
     * persona ya registrada como EFECTIVO por documento. No crea nada nuevo
     * ni consulta RENIEC — si no se encuentra, el campo se deja vacío.
     */
    public function buscarTitular(): void
    {
        Auth::exigirRol('ADMIN', 'REGISTRADOR');
        header('Content-Type: application/json; charset=utf-8');

        $tipoDoc = trim($_GET['tipo_doc'] ?? '');
        $numDoc = trim($_GET['num_doc'] ?? '');

        if ($tipoDoc === '' || $numDoc === '') {
            echo json_encode(['encontrado' => false], JSON_UNESCAPED_UNICODE);
            return;
        }

        $persona = Persona::buscarPorDocumento($tipoDoc, $numDoc);
        if (!$persona || ($persona['condicion'] ?? '') !== 'EFECTIVO') {
            echo json_encode(['encontrado' => false], JSON_UNESCAPED_UNICODE);
            return;
        }

        $grado = $persona['grado_id'] ? GradoPnp::buscar((int) $persona['grado_id']) : null;

        echo json_encode([
            'encontrado' => true,
            'titular_id' => (int) $persona['id'],
            'nombre'     => Persona::nombreCompletoPnp(array_merge($persona, [
                'grado_abreviatura' => $grado['abreviatura'] ?? '',
            ])),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Decodifica enfermedad.columnas_muestra. Desde PETICION_HC_Y_LABORATORIO.md
     * (Parte 2, Fase D1 "bloque declarativo") acepta dos formas: lista plana
     * (compat con lo que ya declaraban A80/B05) u objeto {"columnas",
     * "opciones", "texto_libre"} -- reglas de qué es válido en cada clave en
     * cargar_fichas.php::validarManifiesto(). Reemplaza a la const PHP
     * OPCIONES_MUESTRA_POR_ENFERMEDAD (eliminada en este cambio): las
     * opciones de tipo_muestra/tipo_prueba por ficha vivían en código, ahora
     * son datos del manifiesto igual que el resto de columnas_tablas_hija.
     */
    private function resolverConfigMuestra(array $enfermedad): array
    {
        // unicoPorTipo (2026-08-23, pedido del usuario en el cotejo de A95):
        // opt-in booleano -- cuando una ficha declara "unico_por_tipo": true
        // en columnas_tablas_hija.caso_muestra, no se permite más de 1 fila
        // con el mismo tipo_muestra en el mismo caso (A95: Biopsia/Serología/
        // Hígado/Cultivos son 4 categorías fijas del PDF, cada una se
        // registra como máximo una vez -- pero la ficha no exige las 4, así
        // que sigue siendo el componente dinámico "+ Agregar muestra", no un
        // listado fijo). Ausente = false, mismo comportamiento que antes
        // para las demás fichas con caso_muestra=true. Aplicado también en
        // filasMuestras() (servidor, autoritativo) y en el <select> del
        // cliente (tablas-hijas/muestras.php + filas-dinamicas.js).
        $porDefecto = ['columnas' => self::COLUMNAS_HIJA_DEFECTO['muestra'], 'opciones' => [], 'textoLibre' => [], 'dependeDeColumna' => [], 'unicoPorTipo' => false];

        $json = $enfermedad['columnas_muestra'] ?? null;
        if ($json === null) {
            return $porDefecto;
        }
        $decodificado = json_decode($json, true);
        if (!is_array($decodificado)) {
            return $porDefecto;
        }
        if (array_is_list($decodificado)) {
            return ['columnas' => $decodificado, 'opciones' => [], 'textoLibre' => [], 'dependeDeColumna' => [], 'unicoPorTipo' => false];
        }
        return [
            'columnas'         => $decodificado['columnas'] ?? self::COLUMNAS_HIJA_DEFECTO['muestra'],
            'opciones'         => $decodificado['opciones'] ?? [],
            'textoLibre'       => $decodificado['texto_libre'] ?? [],
            'dependeDeColumna' => $decodificado['depende_de_columna'] ?? [],
            'unicoPorTipo'     => !empty($decodificado['unico_por_tipo']),
        ];
    }

    /**
     * Códigos de "resultado" que ninguna ficha comparte con otra (a
     * diferencia de POS/NEG/IND, que sí vienen del catálogo 3 compartido) --
     * A37.0 (VIII. Laboratorio, ítem 64) pide Contaminado/No viable además
     * de Positivo/Negativo, sin "Indeterminado". Se resuelven acá (no en
     * `catalogo_item`) para no tocar una tabla compartida por las otras 11
     * fichas con `usa_muestras=1`: sin que una ficha declare estos códigos
     * en su `opciones.resultado`, nadie más los ve -- mismo criterio opt-in
     * que `texto_libre`/`depende_de_columna`.
     */
    private const OPCIONES_RESULTADO_EXTRA = [
        'CONTAM'   => 'Contaminado',
        'NOVIABLE' => 'No viable',
    ];

    private function datosMuestrasCatalogo(?array $enfermedad = null): array
    {
        $todosMuestras  = CatalogoItem::porCatalogo(4);
        $todosPruebas   = CatalogoItem::porCatalogo(5);
        $todosResultado = CatalogoItem::porCatalogo(3);

        $config   = $enfermedad ? $this->resolverConfigMuestra($enfermedad) : ['opciones' => [], 'textoLibre' => [], 'dependeDeColumna' => [], 'unicoPorTipo' => false];
        $opciones = $config['opciones'];

        if (!empty($opciones['tipo_muestra'])) {
            $todosMuestras = array_values(array_filter($todosMuestras, fn($it) => in_array($it['valor'], $opciones['tipo_muestra'], true)));
        }
        if (!empty($opciones['tipo_prueba'])) {
            $todosPruebas = array_values(array_filter($todosPruebas, fn($it) => in_array($it['valor'], $opciones['tipo_prueba'], true)));
        }
        if (!empty($opciones['resultado'])) {
            $todosResultado = array_values(array_filter($todosResultado, fn($it) => in_array($it['valor'], $opciones['resultado'], true)));
            foreach ($opciones['resultado'] as $codigo) {
                if (isset(self::OPCIONES_RESULTADO_EXTRA[$codigo]) && !in_array($codigo, array_column($todosResultado, 'valor'), true)) {
                    $todosResultado[] = ['valor' => $codigo, 'etiqueta' => self::OPCIONES_RESULTADO_EXTRA[$codigo]];
                }
            }
        }

        return [
            'opcionesTipoMuestra'       => $todosMuestras,
            'opcionesTipoPrueba'        => $todosPruebas,
            'opcionesResultado'         => $todosResultado,
            'opcionesMuestraExtra'      => $opciones,
            'textoLibreMuestra'         => $config['textoLibre'] ?? [],
            'dependeDeColumnaMuestra'   => $config['dependeDeColumna'] ?? [],
            'unicoPorTipoMuestra'       => $config['unicoPorTipo'] ?? false,
        ];
    }

    /**
     * Catálogos compartidos de caso_vacuna (PENDIENTES_POST_FASE5.md punto 2):
     * mismo patrón que datosMuestrasCatalogo(), el widget llena <select> con
     * estas opciones en vez de aceptar texto libre.
     */
    private function datosVacunasCatalogo(): array
    {
        return [
            'opcionesVacuna'    => CatalogoItem::porNombreCatalogo('vacuna_minsa'),
            'opcionesViaVacuna' => CatalogoItem::porNombreCatalogo('via_vacuna'),
            'opcionesSitio'     => CatalogoItem::porNombreCatalogo('sitio_vacuna'),
            'opcionesDosis'     => CatalogoItem::porNombreCatalogo('dosis_vacuna'),
            'opcionesAdyuvante' => CatalogoItem::porNombreCatalogo('adyuvante_vacuna'),
        ];
    }

    /**
     * Columnas mínimas de cada tabla hija cuando la ficha no declara
     * `columnas_tablas_hija` en el manifiesto (PENDIENTES_POST_FASE5.md
     * punto 3) -- deliberadamente no son "todas las columnas", para que una
     * ficha nueva sin configurar no herede de golpe las columnas de otra.
     * "nombres"/"vacuna"/"vacuna_otro"/"fecha" no aparecen acá porque los
     * widgets de contactos.php/vacunas.php las muestran siempre (son la
     * identidad de la fila, no tiene sentido ocultarlas). `viaje` incluye
     * aquí transporte_ida/transporte_retorno (ítem Z.2, PENDIENTES.md):
     * P35.0 las excluye declarando su propia lista sin ellas (no están en
     * su PDF), pero las 7 fichas que no declaran columnas_tablas_hija.caso_viaje
     * en absoluto deben seguir viéndolas -- "todas las que ya existían"
     * para esas, sin marcha atrás en lo que ya se veía.
     */
    private const COLUMNAS_HIJA_DEFECTO = [
        'contacto' => ['parentesco', 'doc', 'celular'],
        'vacuna'   => ['dosis'],
        'viaje'    => ['pais', 'fecha_salida', 'fecha_retorno', 'transporte_ida', 'transporte_retorno'],
        'muestra'  => ['tipo_muestra', 'tipo_prueba', 'resultado', 'fecha_toma', 'fecha_result'],
    ];

    /**
     * Resuelve, para la enfermedad dada, qué columnas mostrar en cada
     * widget de tabla hija: lo que traiga `enfermedad.columnas_*` (JSON
     * cargado desde el manifiesto por cargar_fichas.php), o el mínimo por
     * defecto si es NULL.
     */
    private function datosColumnasTablaHija(array $enfermedad): array
    {
        $resolver = function (?string $json, string $tabla): array {
            if ($json === null) {
                return self::COLUMNAS_HIJA_DEFECTO[$tabla];
            }
            $decodificado = json_decode($json, true);
            return is_array($decodificado) ? $decodificado : self::COLUMNAS_HIJA_DEFECTO[$tabla];
        };

        return [
            'columnasContacto' => $resolver($enfermedad['columnas_contacto'] ?? null, 'contacto'),
            'columnasVacuna'   => $resolver($enfermedad['columnas_vacuna'] ?? null, 'vacuna'),
            'columnasViaje'    => $resolver($enfermedad['columnas_viaje'] ?? null, 'viaje'),
            'columnasMuestra'  => $this->resolverConfigMuestra($enfermedad)['columnas'],
            'bloquesCondicionalesMuestra' => $this->resolverBloquesCondicionales($enfermedad, 'caso_muestra'),
        ];
    }

    /**
     * Capacidad 6 (PETICION_HC_Y_LABORATORIO.md, Parte 2, ítem 43 de
     * P35.0): bloques adicionales de una tabla hija, visibles solo cuando
     * la Clasificación del caso (núcleo) toma uno de sus
     * "valores_activadores". Ausente = [] (ninguna ficha declara ninguno
     * salvo P35.0), mismo criterio "opt-in" que columnas_tablas_hija.
     */
    private function resolverBloquesCondicionales(array $enfermedad, string $tabla): array
    {
        $json = $enfermedad['bloques_condicionales'] ?? null;
        if ($json === null) {
            return [];
        }
        $decodificado = json_decode($json, true);
        if (!is_array($decodificado)) {
            return [];
        }
        return array_values(array_filter($decodificado, fn($b) => ($b['tabla'] ?? null) === $tabla));
    }

    /**
     * Sujetos secundarios de un caso (por rol), con el nombre del distrito
     * resuelto donde aplique -- para la vista de solo lectura (`ver.php`;
     * el formulario de edición usa el selector de UBIGEO, que no necesita
     * el nombre, solo el id).
     */
    private function sujetosConDistritoPorRol(array $caso): array
    {
        $sujetos = CasoSujeto::porCaso((int) $caso['id']);
        foreach ($sujetos as &$sujeto) {
            if (!empty($sujeto['distrito_id'])) {
                $distrito = Distrito::buscarPorId($sujeto['distrito_id']);
                $sujeto['distrito_nombre'] = $distrito['nombre'] ?? $sujeto['distrito_id'];
            }
        }
        unset($sujeto);
        return $sujetos;
    }

    /**
     * Lee $_POST["{rol_minuscula}_{columna}"] para cada columna que la
     * ficha declaró para ese rol (columnasSujeto(), PETICION_P35_RUBEOLA_CONGENITA.md
     * Fase 2) -- generaliza lo que antes era datosResidenciaMadre() (fijo a
     * distrito_id/direccion, solo P96) a cualquier rol/columnas. Caso
     * especial: fecha_nacimiento usa fechaIsoValida(), no trim() plano,
     * mismo criterio que fecha_nac del paciente -- una fecha inválida se
     * guarda NULL, no como texto suelto que rompería la columna DATE.
     */
    private function datosSujetoDesdePost(string $rol, array $columnas): array
    {
        $prefijo = mb_strtolower($rol) . '_';
        $meta = metaColumnasSujeto();
        $datos = [];
        foreach ($columnas as $col) {
            $valor = trim((string) ($_POST[$prefijo . $col] ?? ''));
            if ($valor === '') {
                $datos[$col] = null;
                continue;
            }
            $datos[$col] = ($meta[$col]['kind'] ?? null) === 'fecha' ? fechaIsoValida($valor) : $valor;
        }
        return $datos;
    }

    /**
     * [rol => [columna => valor]] para TODOS los roles que la ficha declara
     * en columnas_sujeto, leído de $_POST -- usado para repoblar el
     * formulario tras un error de validación (no se pierde lo ya tecleado).
     * Deliberadamente no filtra por rolesConSeccionPropia(): eso solo decide
     * DÓNDE se pinta el bloque, no si sus datos hay que conservarlos.
     */
    private function valoresSujetoPorRolDesdePost(array $enfermedad): array
    {
        $columnasSujetoJson = $enfermedad['columnas_sujeto'] ?? null;
        $valores = [];
        foreach (rolesSujetoDeclarados($columnasSujetoJson) as $rol) {
            $valores[$rol] = $this->datosSujetoDesdePost($rol, columnasSujeto($columnasSujetoJson, $rol));
        }
        return $valores;
    }

    private function filasContactos(): array
    {
        $nombres = $_POST['contacto_nombres'] ?? [];
        $parentescos = $_POST['contacto_parentesco'] ?? [];
        $edades = $_POST['contacto_edad'] ?? [];
        $sexos = $_POST['contacto_sexo'] ?? [];
        $vacunados = $_POST['contacto_vacunado'] ?? [];
        $dosisRecibidasArr = $_POST['contacto_dosis_recibidas'] ?? [];
        $fechasVacunacion = $_POST['contacto_fecha_vacunacion'] ?? [];
        $profilaxis = $_POST['contacto_profilaxis'] ?? [];
        $docs = $_POST['contacto_doc'] ?? [];
        $celulares = $_POST['contacto_celular'] ?? [];
        $fechasContacto = $_POST['contacto_fecha_contacto'] ?? [];
        $fechasColectaHecesArr = $_POST['contacto_fecha_colecta_heces'] ?? [];
        $fechasEnvioArr = $_POST['contacto_fecha_envio'] ?? [];
        $fechasResultadoArr = $_POST['contacto_fecha_resultado'] ?? [];
        $resultadosAislamientoArr = $_POST['contacto_resultado_aislamiento'] ?? [];
        $lugaresContacto = $_POST['contacto_lugar_contacto'] ?? [];
        $fechasInicioErupcion = $_POST['contacto_fecha_inicio_erupcion'] ?? [];
        $vacunados72h = $_POST['contacto_vacunado_72h'] ?? [];
        $direcciones = $_POST['contacto_direccion'] ?? [];
        // "Tipo de exposición" (B04X, ítem 33): a diferencia de las demás
        // columnas de arriba (un valor por fila, alineadas por POSICIÓN de
        // envío -- $i del foreach de abajo), esta es multivalor por fila y
        // viene indexada por un id ESTABLE (contacto_fila_id[]), no por
        // posición -- ver el comentario largo en tablas-hijas/contactos.php.
        $filaIds = $_POST['contacto_fila_id'] ?? [];
        $tiposExposicionPorFila = $_POST['contacto_tipo_exposicion'] ?? [];
        $tiposExposicionOtroPorFila = $_POST['contacto_tipo_exposicion_otro'] ?? [];

        $filas = [];
        foreach ($nombres as $i => $nombre) {
            $nombre = trim((string) $nombre);
            if ($nombre === '') {
                continue;
            }
            $edad = trim((string) ($edades[$i] ?? ''));
            $sexo = $sexos[$i] ?? '';
            $vacunado = $vacunados[$i] ?? '';
            $fechaVacunacion = trim((string) ($fechasVacunacion[$i] ?? ''));
            $profilaxisFila = $profilaxis[$i] ?? '';
            $fechaContacto = trim((string) ($fechasContacto[$i] ?? ''));
            $fechaColectaHeces = trim((string) ($fechasColectaHecesArr[$i] ?? ''));
            $fechaEnvio = trim((string) ($fechasEnvioArr[$i] ?? ''));
            $fechaResultado = trim((string) ($fechasResultadoArr[$i] ?? ''));
            $fechaInicioErupcion = trim((string) ($fechasInicioErupcion[$i] ?? ''));
            $vacunado72h = $vacunados72h[$i] ?? '';
            $direccion = trim((string) ($direcciones[$i] ?? ''));
            $filaId = (string) ($filaIds[$i] ?? $i);
            $tiposExposicionFila = is_array($tiposExposicionPorFila[$filaId] ?? null) ? $tiposExposicionPorFila[$filaId] : [];
            $tiposExposicionFila = array_values(array_intersect($tiposExposicionFila, ['1', '2', '3', '4', '5', '6']));
            $tipoExposicionOtroFila = trim((string) ($tiposExposicionOtroPorFila[$filaId] ?? ''));

            $filas[] = [
                'nombres'               => $nombre,
                'parentesco'            => trim((string) ($parentescos[$i] ?? '')) ?: null,
                'edad'                  => $edad !== '' && is_numeric($edad) ? (int) $edad : null,
                'sexo'                  => in_array($sexo, ['M', 'F'], true) ? $sexo : null,
                'vacunado'              => in_array($vacunado, ['SI', 'NO', 'IGNORADO'], true) ? $vacunado : null,
                'dosis_recibidas'       => trim((string) ($dosisRecibidasArr[$i] ?? '')) ?: null,
                'fecha_vacunacion'      => $fechaVacunacion !== '' ? fechaIsoValida($fechaVacunacion) : null,
                'profilaxis'            => in_array($profilaxisFila, ['SI', 'NO'], true) ? $profilaxisFila : null,
                'doc'                   => trim((string) ($docs[$i] ?? '')) ?: null,
                'celular'               => trim((string) ($celulares[$i] ?? '')) ?: null,
                'fecha_contacto'        => $fechaContacto !== '' ? fechaIsoValida($fechaContacto) : null,
                'fecha_colecta_heces'  => $fechaColectaHeces !== '' ? fechaIsoValida($fechaColectaHeces) : null,
                'fecha_envio'          => $fechaEnvio !== '' ? fechaIsoValida($fechaEnvio) : null,
                'fecha_resultado'      => $fechaResultado !== '' ? fechaIsoValida($fechaResultado) : null,
                'resultado_aislamiento' => trim((string) ($resultadosAislamientoArr[$i] ?? '')) ?: null,
                'lugar_contacto'        => trim((string) ($lugaresContacto[$i] ?? '')) ?: null,
                'fecha_inicio_erupcion' => $fechaInicioErupcion !== '' ? fechaIsoValida($fechaInicioErupcion) : null,
                'vacunado_72h'          => in_array($vacunado72h, ['SI', 'NO', 'DESCONOCIDO'], true) ? $vacunado72h : null,
                'direccion'             => $direccion !== '' ? $direccion : null,
                'tipo_exposicion'       => $tiposExposicionFila ? implode(',', $tiposExposicionFila) : null,
                'tipo_exposicion_otro'  => in_array('6', $tiposExposicionFila, true) && $tipoExposicionOtroFila !== '' ? $tipoExposicionOtroFila : null,
            ];
        }

        return $filas;
    }

    /**
     * "Contactos directos" (B04X, ítem 35 del PDF): censo independiente de
     * filasContactos() (ver tablas-hijas/contactos-directos.php y
     * CasoContactoDirecto -- dos censos con preguntas gatillo distintas, no
     * pueden compartir tabla). Mismo patrón de id estable que
     * contacto_tipo_exposicion en filasContactos(): "grupo_poblacion" es un
     * checklist multivalor por fila, indexado por contacto_directo_fila_id[]
     * en vez de por posición de envío.
     */
    private function filasContactosDirectos(): array
    {
        $nombres = $_POST['contacto_directo_nombres'] ?? [];
        $parentescos = $_POST['contacto_directo_parentesco'] ?? [];
        $celulares = $_POST['contacto_directo_celular'] ?? [];
        $docs = $_POST['contacto_directo_doc'] ?? [];
        $filaIds = $_POST['contacto_directo_fila_id'] ?? [];
        $gruposPoblacionPorFila = $_POST['contacto_directo_grupo_poblacion'] ?? [];

        $filas = [];
        foreach ($nombres as $i => $nombre) {
            $nombre = trim((string) $nombre);
            if ($nombre === '') {
                continue;
            }
            $filaId = (string) ($filaIds[$i] ?? $i);
            $grupoPoblacionFila = is_array($gruposPoblacionPorFila[$filaId] ?? null) ? $gruposPoblacionPorFila[$filaId] : [];
            $grupoPoblacionFila = array_values(array_intersect($grupoPoblacionFila, ['1', '2', '3', '4', '5', '6']));

            $filas[] = [
                'nombres'         => $nombre,
                'parentesco'      => trim((string) ($parentescos[$i] ?? '')) ?: null,
                'celular'         => trim((string) ($celulares[$i] ?? '')) ?: null,
                'doc'             => trim((string) ($docs[$i] ?? '')) ?: null,
                'grupo_poblacion' => $grupoPoblacionFila ? implode(',', $grupoPoblacionFila) : null,
            ];
        }

        return $filas;
    }

    private const ERROR_FECHA_INVALIDA = 'Ingresa una fecha válida.';
    private const ERROR_RESULTADO_ANTES_DE_TOMA = 'La fecha de resultado no puede ser anterior a la de obtención de la muestra.';

    /**
     * @return array{0: array, 1: array} [$filas, $errores] — $errores queda
     * indexado por la misma posición que la fila en el POST, para que la
     * vista pueda marcar el campo exacto que falló.
     */
    private function filasViajes(): array
    {
        $lugares = $_POST['viaje_pais'] ?? [];
        $localidades = $_POST['viaje_localidad'] ?? [];
        $distritos = $_POST['viaje_distrito_id'] ?? [];
        $direcciones = $_POST['viaje_direccion'] ?? [];
        $salidas = $_POST['viaje_fecha_salida'] ?? [];
        $retornos = $_POST['viaje_fecha_retorno'] ?? [];
        $tiemposPermanencia = $_POST['viaje_tiempo_permanencia'] ?? [];
        $transportesIda = $_POST['viaje_transporte_ida'] ?? [];
        $transportesRetorno = $_POST['viaje_transporte_retorno'] ?? [];
        $semanasGestacion = $_POST['viaje_semana_gestacion'] ?? [];

        // Antes se iteraba sobre $lugares (viaje_pais[]) como array
        // conductor del foreach -- si la ficha activa no declara "pais" en
        // columnas_tablas_hija.caso_viaje (B57, cotejo 2026-08-21: solo
        // distrito_id + localidad, ver viajes.php), viaje_pais[] nunca
        // llega en el POST y el foreach no corría nunca, descartando TODAS
        // las filas en silencio pese a traer localidad/distrito_id. Ahora
        // el número de filas sale del máximo entre todas las columnas
        // posibles -- ninguna es obligatoria por sí sola.
        $totalFilas = max(
            count($lugares), count($localidades), count($distritos), count($direcciones),
            count($salidas), count($retornos), count($tiemposPermanencia),
            count($transportesIda), count($transportesRetorno), count($semanasGestacion)
        );

        $filas = [];
        $errores = [];
        for ($i = 0; $i < $totalFilas; $i++) {
            $lugar = trim((string) ($lugares[$i] ?? ''));
            $localidad = trim((string) ($localidades[$i] ?? ''));
            $distritoId = trim((string) ($distritos[$i] ?? ''));
            $direccion = trim((string) ($direcciones[$i] ?? ''));
            $salidaTxt = trim((string) ($salidas[$i] ?? ''));
            $retornoTxt = trim((string) ($retornos[$i] ?? ''));
            $tiempoPermanencia = trim((string) ($tiemposPermanencia[$i] ?? ''));
            $transIda = trim((string) ($transportesIda[$i] ?? ''));
            $transRetorno = trim((string) ($transportesRetorno[$i] ?? ''));
            $semanaGestacionTxt = trim((string) ($semanasGestacion[$i] ?? ''));

            if ($lugar === '' && $localidad === '' && $distritoId === '' && $direccion === '' && $salidaTxt === '' && $retornoTxt === '' && $tiempoPermanencia === '' && $transIda === '' && $transRetorno === '' && $semanaGestacionTxt === '') {
                continue;
            }

            // En caso de error se guarda el texto tal cual se escribió (no
            // el ISO ni null) para que la vista lo muestre de vuelta al
            // usuario y pueda corregirlo, en lugar de verlo desaparecer.
            $salidaIso = null;
            if ($salidaTxt !== '') {
                $salidaIso = fechaIsoValida($salidaTxt);
                if (!$salidaIso) {
                    $errores[$i]['fecha_salida'] = self::ERROR_FECHA_INVALIDA;
                    $salidaIso = $salidaTxt;
                }
            }
            $retornoIso = null;
            if ($retornoTxt !== '') {
                $retornoIso = fechaIsoValida($retornoTxt);
                if (!$retornoIso) {
                    $errores[$i]['fecha_retorno'] = self::ERROR_FECHA_INVALIDA;
                    $retornoIso = $retornoTxt;
                }
            }

            $filas[] = [
                'pais'               => $lugar !== '' ? $lugar : null,
                'localidad'          => $localidad !== '' ? $localidad : null,
                'distrito_id'        => $distritoId !== '' ? $distritoId : null,
                'direccion'          => $direccion !== '' ? $direccion : null,
                'fecha_salida'       => $salidaIso,
                'fecha_retorno'      => $retornoIso,
                'tiempo_permanencia' => $tiempoPermanencia !== '' ? $tiempoPermanencia : null,
                'semana_gestacion'   => $semanaGestacionTxt !== '' ? (int) $semanaGestacionTxt : null,
                'transporte_ida'     => $transIda !== '' ? $transIda : null,
                'transporte_retorno' => $transRetorno !== '' ? $transRetorno : null,
            ];
        }

        return [$filas, $errores];
    }

    private const ANTIBIOTICOS_EVOLUCION = ['penicilina', 'cloranfenicol', 'rifampicina', 'ciprofloxacina', 'eritromicina', 'cotrimoxazol', 'ceftriaxona', 'otros'];

    /**
     * Tabla hija de A44 "Evolución clínica" (caso_evolucion). Una fila se
     * descarta solo si TODOS sus campos, incluidos los de la sub-tabla de
     * antibióticos, están vacíos -- igual criterio que filasViajes().
     */
    private function filasEvolucion(): array
    {
        $fechas = $_POST['evolucion_fecha'] ?? [];
        $temperaturas = $_POST['evolucion_temperatura'] ?? [];
        $hemoglobinas = $_POST['evolucion_hemoglobina'] ?? [];
        $hematocritos = $_POST['evolucion_hematocrito'] ?? [];
        $transfusiones = $_POST['evolucion_transfusiones'] ?? [];
        $frotis = $_POST['evolucion_frotis'] ?? [];
        $hcMuestraTomada = $_POST['evolucion_hemocultivo_muestra_tomada'] ?? [];
        $hcFechaToma = $_POST['evolucion_hemocultivo_fecha_toma'] ?? [];
        $hcResultado = $_POST['evolucion_hemocultivo_resultado'] ?? [];
        $hcFechaResultado = $_POST['evolucion_hemocultivo_fecha_resultado'] ?? [];
        $atbOtrosEspecificar = $_POST['evolucion_atb_otros_especificar'] ?? [];

        $atbUsado = [];
        $atbDosis = [];
        foreach (self::ANTIBIOTICOS_EVOLUCION as $atb) {
            $atbUsado[$atb] = $_POST["evolucion_atb_{$atb}_usado"] ?? [];
            $atbDosis[$atb] = $_POST["evolucion_atb_{$atb}_dosis"] ?? [];
        }

        $filas = [];
        $errores = [];
        foreach ($fechas as $i => $fechaTxtRaw) {
            $fechaTxt = trim((string) $fechaTxtRaw);
            $temperatura = trim((string) ($temperaturas[$i] ?? ''));
            $hemoglobina = trim((string) ($hemoglobinas[$i] ?? ''));
            $hematocrito = trim((string) ($hematocritos[$i] ?? ''));
            $transfusion = trim((string) ($transfusiones[$i] ?? ''));
            $frotisTxt = trim((string) ($frotis[$i] ?? ''));
            $muestraTomada = trim((string) ($hcMuestraTomada[$i] ?? ''));
            $hcFechaTomaTxt = trim((string) ($hcFechaToma[$i] ?? ''));
            $hcResultadoTxt = trim((string) ($hcResultado[$i] ?? ''));
            $hcFechaResultadoTxt = trim((string) ($hcFechaResultado[$i] ?? ''));
            $otrosEspecificar = trim((string) ($atbOtrosEspecificar[$i] ?? ''));

            $filaAtb = [];
            $atbTodoVacio = true;
            foreach (self::ANTIBIOTICOS_EVOLUCION as $atb) {
                $usado = trim((string) ($atbUsado[$atb][$i] ?? ''));
                $dosis = trim((string) ($atbDosis[$atb][$i] ?? ''));
                if ($usado !== '' || $dosis !== '') {
                    $atbTodoVacio = false;
                }
                $filaAtb["atb_{$atb}_usado"] = $usado !== '' ? (int) $usado : null;
                $filaAtb["atb_{$atb}_dosis"] = $dosis !== '' ? $dosis : null;
            }

            if ($fechaTxt === '' && $temperatura === '' && $hemoglobina === '' && $hematocrito === '' && $transfusion === ''
                && $frotisTxt === '' && $muestraTomada === '' && $hcFechaTomaTxt === '' && $hcResultadoTxt === '' && $hcFechaResultadoTxt === ''
                && $otrosEspecificar === '' && $atbTodoVacio) {
                continue;
            }

            $fechaIso = null;
            if ($fechaTxt !== '') {
                $fechaIso = fechaIsoValida($fechaTxt);
                if (!$fechaIso) {
                    $errores[$i]['fecha'] = self::ERROR_FECHA_INVALIDA;
                    $fechaIso = $fechaTxt;
                }
            }
            $hcFechaTomaIso = null;
            if ($hcFechaTomaTxt !== '') {
                $hcFechaTomaIso = fechaIsoValida($hcFechaTomaTxt);
                if (!$hcFechaTomaIso) {
                    $errores[$i]['hemocultivo_fecha_toma'] = self::ERROR_FECHA_INVALIDA;
                    $hcFechaTomaIso = $hcFechaTomaTxt;
                }
            }
            $hcFechaResultadoIso = null;
            if ($hcFechaResultadoTxt !== '') {
                $hcFechaResultadoIso = fechaIsoValida($hcFechaResultadoTxt);
                if (!$hcFechaResultadoIso) {
                    $errores[$i]['hemocultivo_fecha_resultado'] = self::ERROR_FECHA_INVALIDA;
                    $hcFechaResultadoIso = $hcFechaResultadoTxt;
                }
            }

            $fila = [
                'fecha'                       => $fechaIso,
                'temperatura'                 => $temperatura !== '' ? $temperatura : null,
                'hemoglobina'                 => $hemoglobina !== '' ? $hemoglobina : null,
                'hematocrito'                 => $hematocrito !== '' ? $hematocrito : null,
                'transfusiones'               => $transfusion !== '' ? $transfusion : null,
                'frotis'                      => $frotisTxt !== '' ? $frotisTxt : null,
                'hemocultivo_muestra_tomada'  => $muestraTomada !== '' ? (int) $muestraTomada : null,
                'hemocultivo_fecha_toma'      => $hcFechaTomaIso,
                'hemocultivo_resultado'       => in_array($hcResultadoTxt, ['POSITIVO', 'NEGATIVO'], true) ? $hcResultadoTxt : null,
                'hemocultivo_fecha_resultado' => $hcFechaResultadoIso,
                'atb_otros_especificar'       => $otrosEspecificar !== '' ? $otrosEspecificar : null,
            ];
            $filas[] = array_merge($fila, $filaAtb);
        }

        return [$filas, $errores];
    }

    /**
     * Tabla hija de A44 "Exámenes auxiliares" (caso_examen_auxiliar). Todos
     * los valores son texto libre (ver examenes-auxiliares.php).
     */
    private function filasExamen(): array
    {
        $fechas = $_POST['examen_fecha'] ?? [];
        $columnas = [];
        foreach (CasoExamenAuxiliar::COLUMNAS as $col) {
            $columnas[$col] = $_POST["examen_{$col}"] ?? [];
        }

        $filas = [];
        $errores = [];
        foreach ($fechas as $i => $fechaTxtRaw) {
            $fechaTxt = trim((string) $fechaTxtRaw);
            $valores = [];
            $todoVacio = ($fechaTxt === '');
            foreach ($columnas as $col => $arr) {
                $valores[$col] = trim((string) ($arr[$i] ?? ''));
                if ($valores[$col] !== '') {
                    $todoVacio = false;
                }
            }

            if ($todoVacio) {
                continue;
            }

            $fechaIso = null;
            if ($fechaTxt !== '') {
                $fechaIso = fechaIsoValida($fechaTxt);
                if (!$fechaIso) {
                    $errores[$i]['fecha'] = self::ERROR_FECHA_INVALIDA;
                    $fechaIso = $fechaTxt;
                }
            }

            $fila = ['fecha' => $fechaIso];
            foreach ($valores as $col => $val) {
                $fila[$col] = $val !== '' ? $val : null;
            }
            $filas[] = $fila;
        }

        return [$filas, $errores];
    }

    /**
     * Valida un código contra un catálogo compartido de caso_vacuna: si el
     * cliente manda algo que no es uno de los `valor` del catálogo, se
     * descarta (no se confía en lo que envía el navegador). Vacío es válido
     * (campo opcional).
     */
    private function codigoValidoDeCatalogo(string $codigo, string $nombreCatalogo): ?string
    {
        if ($codigo === '') {
            return null;
        }
        $validos = array_column(CatalogoItem::porNombreCatalogo($nombreCatalogo), 'valor');
        return in_array($codigo, $validos, true) ? $codigo : null;
    }

    private function filasVacunas(): array
    {
        $vacunas = $_POST['vacuna_nombre'] ?? [];
        $vacunasOtro = $_POST['vacuna_otro'] ?? [];
        $dosis = $_POST['vacuna_dosis'] ?? [];
        $fechas = $_POST['vacuna_fecha'] ?? [];
        $fabricantes = $_POST['vacuna_fabricante'] ?? [];
        $lotes = $_POST['vacuna_lote'] ?? [];
        $vias = $_POST['vacuna_via'] ?? [];
        $sitios = $_POST['vacuna_sitio'] ?? [];
        $adyuvantes = $_POST['vacuna_adyuvante'] ?? [];
        $fechasVencimiento = $_POST['vacuna_fecha_vencimiento'] ?? [];
        $establecimientos = $_POST['vacuna_establecimiento'] ?? [];
        $fuentesInformacion = $_POST['vacuna_fuente_informacion'] ?? [];

        $filas = [];
        $errores = [];
        $totalFilas = max(count($vacunas), count($vacunasOtro));
        for ($i = 0; $i < $totalFilas; $i++) {
            $codigo = trim((string) ($vacunas[$i] ?? ''));
            $otro = trim((string) ($vacunasOtro[$i] ?? ''));
            if ($codigo === '' && $otro === '') {
                continue;
            }
            $fechaTxt = trim((string) ($fechas[$i] ?? ''));
            $fechaIso = null;
            if ($fechaTxt !== '') {
                $fechaIso = fechaIsoValida($fechaTxt);
                if (!$fechaIso) {
                    $errores[$i]['fecha'] = self::ERROR_FECHA_INVALIDA;
                    $fechaIso = $fechaTxt;
                }
            }
            $fechaVencimientoTxt = trim((string) ($fechasVencimiento[$i] ?? ''));
            // "Otro (especificar)" reemplaza al código elegido si se completó.
            $vacuna = $otro !== '' ? $otro : ($this->codigoValidoDeCatalogo($codigo, 'vacuna_minsa') ?: $codigo);
            $filas[] = [
                'vacuna'            => $vacuna,
                'dosis'             => $this->codigoValidoDeCatalogo(trim((string) ($dosis[$i] ?? '')), 'dosis_vacuna') ?: (trim((string) ($dosis[$i] ?? '')) ?: null),
                'fecha'             => $fechaIso,
                'fabricante'        => trim((string) ($fabricantes[$i] ?? '')) ?: null,
                'lote'              => trim((string) ($lotes[$i] ?? '')) ?: null,
                'via'               => $this->codigoValidoDeCatalogo(trim((string) ($vias[$i] ?? '')), 'via_vacuna'),
                'sitio'             => $this->codigoValidoDeCatalogo(trim((string) ($sitios[$i] ?? '')), 'sitio_vacuna'),
                'adyuvante'         => $this->codigoValidoDeCatalogo(trim((string) ($adyuvantes[$i] ?? '')), 'adyuvante_vacuna'),
                'fecha_vencimiento' => $fechaVencimientoTxt !== '' ? fechaIsoValida($fechaVencimientoTxt) : null,
                'establecimiento'   => trim((string) ($establecimientos[$i] ?? '')) ?: null,
                'fuente_informacion'=> trim((string) ($fuentesInformacion[$i] ?? '')) ?: null,
            ];
        }

        return [$filas, $errores];
    }

    private function filasMuestras(?array $enfermedad = null): array
    {
        $tiposMuestra = $_POST['muestra_tipo_muestra'] ?? [];
        $tiposPrueba = $_POST['muestra_tipo_prueba'] ?? [];
        $recibioAntibiotico = $_POST['muestra_recibio_antibiotico'] ?? [];
        $resultados = $_POST['muestra_resultado'] ?? [];
        $fechasToma = $_POST['muestra_fecha_toma'] ?? [];
        $fechasEnvioEessRed = $_POST['muestra_fecha_envio_eess_red'] ?? [];
        $fechasEnvioRedLrr = $_POST['muestra_fecha_envio_red_lrr'] ?? [];
        $fechasEnvioLrrIns = $_POST['muestra_fecha_envio_lrr_ins'] ?? [];
        $fechasEnvioIns = $_POST['muestra_fecha_envio_ins'] ?? [];
        $fechasResultado = $_POST['muestra_fecha_result'] ?? [];
        $agentesAislados = $_POST['muestra_agente_aislado'] ?? [];
        $observacionesArr = $_POST['muestra_observaciones'] ?? [];
        // A00 (cotejo 2026-09-07, "V. LABORATORIO" pág. 51): 3 columnas más de
        // la tabla del papel -- opt-in por ficha vía columnas_tablas_hija, las
        // otras 11 fichas con caso_muestra nunca las postean.
        $establecimientosMuestra = $_POST['muestra_establecimiento'] ?? [];
        $serogrupos = $_POST['muestra_serogrupo'] ?? [];
        $serotipos = $_POST['muestra_serotipo'] ?? [];

        $fechasRecepcionIns = $_POST['muestra_fecha_recepcion_ins'] ?? [];
        $resultadosPcr = $_POST['muestra_resultado_pcr'] ?? [];
        $fechasResultPcr = $_POST['muestra_fecha_result_pcr'] ?? [];
        $genotipos = $_POST['muestra_genotipo'] ?? [];
        $resultadosIgm = $_POST['muestra_resultado_igm'] ?? [];
        $fechasResultIgm = $_POST['muestra_fecha_result_igm'] ?? [];
        $resultadosIgg = $_POST['muestra_resultado_igg'] ?? [];
        $fechasResultIgg = $_POST['muestra_fecha_result_igg'] ?? [];
        $titulaciones = $_POST['muestra_titulacion'] ?? [];

        $datosMuestras = $this->datosMuestrasCatalogo($enfermedad);
        $validosTipoMuestra = array_column($datosMuestras['opcionesTipoMuestra'], 'valor');
        $validosTipoPrueba  = array_column($datosMuestras['opcionesTipoPrueba'], 'valor');
        $validosResultado   = array_column($datosMuestras['opcionesResultado'], 'valor');
        $validosSerogrupo   = $datosMuestras['opcionesMuestraExtra']['serogrupo'] ?? [];
        $validosSerotipo    = $datosMuestras['opcionesMuestraExtra']['serotipo'] ?? [];
        // 'establecimiento' es texto libre (no hay catálogo contra el que
        // validarlo), así que se guarda sólo si la ficha declaró la columna.
        // Sin esto, un POST forjado la escribiría en cualquiera de las 12
        // fichas con caso_muestra, aunque su widget no la pinte -- que es
        // justamente el agujero preexistente documentado en PENDIENTES.md para
        // el resto de las columnas de esta tabla. Las columnas nuevas de este
        // cotejo no lo amplían: serogrupo/serotipo ya quedan en NULL por no
        // pasar la validación contra opciones.* cuando la ficha no las declara.
        $columnasDeclaradasMuestra = $this->resolverConfigMuestra($enfermedad ?? [])['columnas'];
        $declaraEstablecimientoMuestra = in_array('establecimiento', $columnasDeclaradasMuestra, true);
        // unicoPorTipo (2026-08-23): el <select> ya deshabilita del lado
        // cliente los tipos repetidos (filas-dinamicas.js), pero no hay que
        // confiar en eso -- se revalida acá, autoritativo, igual que el
        // resto de esta función.
        $unicoPorTipo = $datosMuestras['unicoPorTipoMuestra'] ?? false;
        $tiposMuestraVistos = [];
        // tipo_prueba texto libre (A95, 2026-08-23): igual que genotipo/
        // titulación más abajo, sin validar contra el catálogo cuando la
        // ficha lo declara texto_libre -- si no, cualquier texto que el
        // usuario escriba (no viene de un <select>) se descartaría en
        // silencio por no matchear $validosTipoPrueba.
        $tipoPruebaLibre = in_array('tipo_prueba', $datosMuestras['textoLibreMuestra'] ?? [], true);

        // numero_muestra: ordinal automático, no elegible por el usuario. La
        // primera fila de un tipo_muestra dado es la 1, la siguiente fila con
        // el MISMO tipo_muestra es la 2, y así -- cuenta sobre las filas que
        // de verdad se guardan (después del salto de filas vacías), en el
        // orden en que llegan por POST. Reemplaza al <select> manual de
        // PETICION_HC_Y_LABORATORIO.md ("revivir numero_muestra"): el usuario
        // señaló que un selector manual es redundante una vez que el propio
        // conteo de filas ya lo determina, y que además no calza con el papel
        // (que no pide elegir un número, solo llenar "1era"/"2da muestra").
        $contadorPorTipoMuestra = [];

        $filas = [];
        $errores = [];
        foreach ($tiposMuestra as $i => $tipoMuestra) {
            $tipoMuestra = trim((string) $tipoMuestra);
            $tipoPrueba = trim((string) ($tiposPrueba[$i] ?? ''));
            $resultado = trim((string) ($resultados[$i] ?? ''));
            $tomaTxt = trim((string) ($fechasToma[$i] ?? ''));
            $envioEessRedTxt = trim((string) ($fechasEnvioEessRed[$i] ?? ''));
            $envioRedLrrTxt = trim((string) ($fechasEnvioRedLrr[$i] ?? ''));
            $envioLrrInsTxt = trim((string) ($fechasEnvioLrrIns[$i] ?? ''));
            $envioInsTxt = trim((string) ($fechasEnvioIns[$i] ?? ''));
            $recepInsTxt = trim((string) ($fechasRecepcionIns[$i] ?? ''));
            $resultTxt = trim((string) ($fechasResultado[$i] ?? ''));
            $agenteTxt = trim((string) ($agentesAislados[$i] ?? ''));
            $obsTxt = trim((string) ($observacionesArr[$i] ?? ''));
            $eessMuestraTxt = trim((string) ($establecimientosMuestra[$i] ?? ''));
            $serogrupoTxt = trim((string) ($serogrupos[$i] ?? ''));
            $serotipoTxt = trim((string) ($serotipos[$i] ?? ''));

            $resPcr = trim((string) ($resultadosPcr[$i] ?? ''));
            $resPcrTxt = trim((string) ($fechasResultPcr[$i] ?? ''));
            $genotipoTxt = trim((string) ($genotipos[$i] ?? ''));
            $resIgm = trim((string) ($resultadosIgm[$i] ?? ''));
            $resIgmTxt = trim((string) ($fechasResultIgm[$i] ?? ''));
            $resIgg = trim((string) ($resultadosIgg[$i] ?? ''));
            $resIggTxt = trim((string) ($fechasResultIgg[$i] ?? ''));
            $titulacionTxt = trim((string) ($titulaciones[$i] ?? ''));

            if ($tipoMuestra === '' && $tipoPrueba === '' && $resultado === '' && $tomaTxt === '' && $envioEessRedTxt === '' && $envioRedLrrTxt === '' && $envioLrrInsTxt === '' && $resultTxt === '' && $envioInsTxt === '' && $recepInsTxt === '' && $agenteTxt === '' && $obsTxt === '' && $eessMuestraTxt === '' && $serogrupoTxt === '' && $serotipoTxt === '' && $resPcr === '' && $resPcrTxt === '' && $genotipoTxt === '' && $resIgm === '' && $resIgmTxt === '' && $resIgg === '' && $resIggTxt === '' && $titulacionTxt === '') {
                continue;
            }

            if ($unicoPorTipo && $tipoMuestra !== '') {
                if (isset($tiposMuestraVistos[$tipoMuestra])) {
                    $errores[$i]['tipo_muestra'] = 'Ya registraste una muestra de este tipo.';
                } else {
                    $tiposMuestraVistos[$tipoMuestra] = true;
                }
            }

            $contadorPorTipoMuestra[$tipoMuestra] = ($contadorPorTipoMuestra[$tipoMuestra] ?? 0) + 1;
            $numeroMuestraCalculado = $contadorPorTipoMuestra[$tipoMuestra];

            $tomaIso = null;
            if ($tomaTxt !== '') {
                $tomaIso = fechaIsoValida($tomaTxt);
                if (!$tomaIso) {
                    $errores[$i]['fecha_toma'] = self::ERROR_FECHA_INVALIDA;
                    $tomaIso = $tomaTxt;
                }
            }
            $envioEessRedIso = null;
            if ($envioEessRedTxt !== '') {
                $envioEessRedIso = fechaIsoValida($envioEessRedTxt);
                if (!$envioEessRedIso) {
                    $errores[$i]['fecha_envio_eess_red'] = self::ERROR_FECHA_INVALIDA;
                    $envioEessRedIso = $envioEessRedTxt;
                }
            }
            $envioRedLrrIso = null;
            if ($envioRedLrrTxt !== '') {
                $envioRedLrrIso = fechaIsoValida($envioRedLrrTxt);
                if (!$envioRedLrrIso) {
                    $errores[$i]['fecha_envio_red_lrr'] = self::ERROR_FECHA_INVALIDA;
                    $envioRedLrrIso = $envioRedLrrTxt;
                }
            }
            $envioLrrInsIso = null;
            if ($envioLrrInsTxt !== '') {
                $envioLrrInsIso = fechaIsoValida($envioLrrInsTxt);
                if (!$envioLrrInsIso) {
                    $errores[$i]['fecha_envio_lrr_ins'] = self::ERROR_FECHA_INVALIDA;
                    $envioLrrInsIso = $envioLrrInsTxt;
                }
            }
            $envioInsIso = null;
            if ($envioInsTxt !== '') {
                $envioInsIso = fechaIsoValida($envioInsTxt);
                if (!$envioInsIso) {
                    $errores[$i]['fecha_envio_ins'] = self::ERROR_FECHA_INVALIDA;
                    $envioInsIso = $envioInsTxt;
                }
            }
            $resultIso = null;
            if ($resultTxt !== '') {
                $resultIso = fechaIsoValida($resultTxt);
                if (!$resultIso) {
                    $errores[$i]['fecha_result'] = self::ERROR_FECHA_INVALIDA;
                    $resultIso = $resultTxt;
                }
            }

            // Un resultado no puede ser anterior a la toma de la muestra: no es
            // un criterio opinable sino un imposible físico, así que se valida
            // para las 10 fichas que usan este widget, no solo para la que lo
            // pidió (B04X, cotejo Sección VII 2026-09-03, ítem 57 del PDF:
            // "Fecha de resultado" debe ser igual o posterior a "Fecha de
            // obtención de muestra"). Solo se compara cuando AMBAS fechas son
            // válidas -- si alguna no lo es, ya tiene su propio error arriba y
            // encadenar un segundo mensaje sobre el mismo campo solo confunde.
            if ($tomaIso && $resultIso && !isset($errores[$i]['fecha_toma']) && !isset($errores[$i]['fecha_result'])
                && $resultIso < $tomaIso) {
                $errores[$i]['fecha_result'] = self::ERROR_RESULTADO_ANTES_DE_TOMA;
            }

            $antibiotico = $recibioAntibiotico[$i] ?? '';

            $filas[] = [
                'tipo_muestra'        => in_array($tipoMuestra, $validosTipoMuestra, true) ? $tipoMuestra : (in_array($tipoMuestra, ['SUERO', 'HNF_FAR', 'ORINA'], true) ? $tipoMuestra : null),
                'tipo_prueba'         => $tipoPruebaLibre ? ($tipoPrueba !== '' ? $tipoPrueba : null) : (in_array($tipoPrueba, $validosTipoPrueba, true) ? $tipoPrueba : null),
                'recibio_antibiotico' => in_array($antibiotico, ['0', '1'], true) ? (int) $antibiotico : null,
                'resultado'           => in_array($resultado, $validosResultado, true) ? $resultado : ($resIgm ?: ($resPcr ?: ($resIgg ?: null))),
                'fecha_toma'          => $tomaIso,
                'fecha_envio_eess_red' => $envioEessRedIso,
                'fecha_envio_red_lrr' => $envioRedLrrIso,
                'fecha_envio_lrr_ins' => $envioLrrInsIso,
                'fecha_envio_ins'     => $envioInsIso,
                'fecha_result'        => $resultIso ?: ($resIgmTxt ? fechaIsoValida($resIgmTxt) : ($resPcrTxt ? fechaIsoValida($resPcrTxt) : ($resIggTxt ? fechaIsoValida($resIggTxt) : null))),
                'agente_aislado'      => $agenteTxt !== '' ? $agenteTxt : null,
                'observaciones'       => $obsTxt !== '' ? $obsTxt : null,
                'fecha_recepcion_ins' => $recepInsTxt !== '' ? fechaIsoValida($recepInsTxt) : null,
                'resultado_pcr'       => $resPcr !== '' ? $resPcr : null,
                'fecha_result_pcr'    => $resPcrTxt !== '' ? fechaIsoValida($resPcrTxt) : null,
                'genotipo'            => $genotipoTxt !== '' ? $genotipoTxt : null,
                'resultado_igm'       => $resIgm !== '' ? $resIgm : null,
                'fecha_result_igm'    => $resIgmTxt !== '' ? fechaIsoValida($resIgmTxt) : null,
                'resultado_igg'       => $resIgg !== '' ? $resIgg : null,
                'fecha_result_igg'    => $resIggTxt !== '' ? fechaIsoValida($resIggTxt) : null,
                'titulacion'          => $titulacionTxt !== '' ? $titulacionTxt : null,
                'establecimiento'     => ($declaraEstablecimientoMuestra && $eessMuestraTxt !== '') ? $eessMuestraTxt : null,
                // serogrupo/serotipo: vocabulario cerrado (O1/O139 y
                // Ogawa/Inaba/Hikojima en A00), validado contra lo que la
                // ficha declaró en opciones.* -- igual que tipo_muestra y a
                // diferencia de agente_aislado/genotipo, que son texto libre
                // para las fichas que no declaran opciones.
                'serogrupo'           => in_array($serogrupoTxt, $validosSerogrupo, true) ? $serogrupoTxt : null,
                'serotipo'            => in_array($serotipoTxt, $validosSerotipo, true) ? $serotipoTxt : null,
                'numero_muestra'      => $numeroMuestraCalculado,
                'contexto'            => null,
            ];
        }

        // Capacidad 6 (PETICION_HC_Y_LABORATORIO.md Parte 2, ítem 43 de
        // P35.0): bloques adicionales de la MISMA tabla, cada uno con su
        // propio prefijo de POST (muestra_<contexto>_<columna>[]) y su
        // propio conjunto (más chico) de columnas -- se anexan a $filas
        // con su "contexto" propio para que CasoMuestra::reemplazarTodos()
        // los guarde junto con los del bloque inicial (una sola tabla, un
        // solo reemplazo por caso) sin mezclarlos al re-renderizar
        // (separarFilasMuestrasPorContexto() los separa de vuelta).
        foreach ($this->resolverBloquesCondicionales($enfermedad ?? [], 'caso_muestra') as $bloque) {
            $contexto = $bloque['contexto'];
            $columnasBloque = $bloque['columnas'];
            $arraysPost = [];
            foreach ($columnasBloque as $col) {
                $arraysPost[$col] = $_POST['muestra_' . $contexto . '_' . $col] ?? [];
            }
            $totalFilasBloque = $arraysPost ? max(array_map('count', $arraysPost)) : 0;
            for ($i = 0; $i < $totalFilasBloque; $i++) {
                $fila = ['contexto' => $contexto];
                $vacia = true;
                foreach ($columnasBloque as $col) {
                    $valorTxt = trim((string) ($arraysPost[$col][$i] ?? ''));
                    if ($valorTxt !== '') {
                        $vacia = false;
                    }
                    if (in_array($col, ['fecha_toma', 'fecha_result'], true)) {
                        $fila[$col] = $valorTxt !== '' ? (fechaIsoValida($valorTxt) ?: $valorTxt) : null;
                    } else {
                        $fila[$col] = $valorTxt !== '' ? $valorTxt : null;
                    }
                }
                if ($vacia) {
                    continue;
                }
                $filas[] = $fila;
            }
        }

        return [$filas, $errores];
    }

    /**
     * Separa las filas ya combinadas de filasMuestras()/CasoMuestra::porCaso()
     * por "contexto" -- el bloque inicial (contexto NULL/"inicial", que
     * muestras.php renderiza) de cada bloque condicional (capacidad 6, que
     * muestras-condicional.php renderiza por separado). Devuelve
     * [$filasInicial, $filasPorContexto].
     */
    private function separarFilasMuestrasPorContexto(array $filas): array
    {
        $filasInicial = [];
        $filasPorContexto = [];
        foreach ($filas as $fila) {
            $contexto = $fila['contexto'] ?? null;
            if ($contexto === null || $contexto === '' || $contexto === 'inicial') {
                $filasInicial[] = $fila;
            } else {
                $filasPorContexto[$contexto][] = $fila;
            }
        }
        return [$filasInicial, $filasPorContexto];
    }

    /**
     * @return array{0: array, 1: array} [$filas, $errores]
     */
    private function filasLugarInfeccion(): array
    {
        $lugares = $_POST['lugarinf_institucion'] ?? [];
        $localidades = $_POST['lugarinf_localidad'] ?? [];
        $permanencias = $_POST['lugarinf_permanencia'] ?? [];
        $distritos = $_POST['lugarinf_distrito_id'] ?? [];
        $direcciones = $_POST['lugarinf_direccion'] ?? [];

        $filas = [];
        $errores = [];
        foreach ($lugares as $i => $lugar) {
            $lugar = trim((string) $lugar);
            $localidad = trim((string) ($localidades[$i] ?? ''));
            $permanenciaTxt = trim((string) ($permanencias[$i] ?? ''));
            $distritoId = trim((string) ($distritos[$i] ?? ''));
            $direccion = trim((string) ($direcciones[$i] ?? ''));

            if ($lugar === '' && $localidad === '' && $permanenciaTxt === '' && $distritoId === '' && $direccion === '') {
                continue;
            }

            $permanencia = null;
            if ($permanenciaTxt !== '') {
                if (!is_numeric($permanenciaTxt)) {
                    $errores[$i]['permanencia_dias'] = 'Ingresa un número de días válido.';
                } else {
                    $permanencia = (int) $permanenciaTxt;
                }
            }

            $filas[] = [
                'lugar_institucion' => $lugar ?: null,
                'localidad_texto'   => $localidad ?: null,
                'permanencia_dias'  => $permanencia,
                'distrito_id'       => $distritoId ?: null,
                'direccion'         => $direccion ?: null,
            ];
        }

        return [$filas, $errores];
    }

    /**
     * vinculo_caso (cotejo Z21, 2026-09-11): la declaración del manifiesto ya
     * resuelta contra campo_def real. null si la ficha no la declara (23 de
     * 24) o si alguna de las claves declaradas ya no existe.
     */
    private function configVinculoCaso(array $enfermedad): ?array
    {
        $config = jsonDeEnfermedad($enfermedad, 'vinculo_caso');
        if (!$config) {
            return null;
        }
        $enfermedadId = (int) $enfermedad['id'];
        $activador = CampoDef::porClave($enfermedadId, (string) ($config['activador']['clave'] ?? ''));
        $candidato = CampoDef::porClave($enfermedadId, (string) ($config['candidatos']['clave'] ?? ''));
        if (!$activador || !$candidato) {
            return null;
        }
        $copiar = [];
        foreach (($config['copiar'] ?? []) as $claveDestino => $origen) {
            $destino = CampoDef::porClave($enfermedadId, (string) $claveDestino);
            if ($destino) {
                $copiar[] = ['destino' => $destino, 'origen' => (string) $origen];
            }
        }
        $config['_activador'] = $activador;
        $config['_candidato'] = $candidato;
        $config['_copiar'] = $copiar;
        // encadenar (pedido del usuario, 2026-09-11): campo NUMERO del caso
        // candidato que dice cuántas fichas vinculadas se esperan de él
        // (Z21: "N.º de nacidos vivos").
        $config['_encadenar'] = !empty($config['encadenar']['clave'])
            ? CampoDef::porClave($enfermedadId, (string) $config['encadenar']['clave'])
            : null;

        return $config;
    }

    /**
     * ¿El texto buscado identifica a este candidato? Se acepta el código de
     * la ficha (F-00123, con o sin guion, con o sin ceros) o el documento
     * exacto -- nunca una coincidencia parcial: el objetivo es que la madre
     * se identifique, no que se elija de una lista donde cabe equivocarse.
     */
    private function coincideVinculoBuscado(array $candidato, string $consulta): bool
    {
        $normalizar = static fn(string $texto): string => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $texto));
        $buscado = $normalizar($consulta);
        if ($buscado === '') {
            return false;
        }
        $documento = $normalizar((string) ($candidato['num_doc'] ?? ''));
        if ($documento !== '' && $documento === $buscado) {
            return true;
        }
        $codigo = $normalizar((string) ($candidato['codigo'] ?? ''));
        if ($codigo !== '' && $codigo === $buscado) {
            return true;
        }
        // "123" o "00123" para la ficha F-00123.
        $soloNumeroCodigo = ltrim(preg_replace('/^F/', '', $codigo), '0');
        $soloNumeroBuscado = ltrim(preg_replace('/^F/', '', $buscado), '0');

        return $soloNumeroCodigo !== '' && $soloNumeroCodigo === $soloNumeroBuscado;
    }

    /**
     * Endpoint AJAX del buscador de ficha vinculada: identifica a la madre
     * por código de ficha o documento exacto, en vez de ofrecer la lista
     * completa de gestantes (de donde se podía elegir la equivocada). Devuelve
     * los mismos datos que una opción del selector, así que el servidor sigue
     * revalidando el vínculo al guardar.
     */
    public function buscarVinculo(): void
    {
        Auth::exigirRol(...self::ROLES_REGISTRO);
        header('Content-Type: application/json; charset=utf-8');

        $enfermedad = Enfermedad::buscar((int) ($_GET['enfermedad_id'] ?? 0));
        $config = $enfermedad ? $this->configVinculoCaso($enfermedad) : null;
        $consulta = trim((string) ($_GET['q'] ?? ''));
        if (!$config || $consulta === '') {
            echo json_encode(['encontrado' => false], JSON_UNESCAPED_UNICODE);
            return;
        }

        $excluir = !empty($_GET['caso_id']) ? (int) $_GET['caso_id'] : null;
        foreach ($this->candidatosVinculo($enfermedad, $config, $excluir) as $candidato) {
            if (!$this->coincideVinculoBuscado($candidato, $consulta)) {
                continue;
            }
            echo json_encode([
                'encontrado' => true,
                'id'         => $candidato['id'],
                'texto'      => $candidato['texto'],
                'copiar'     => $candidato['copiar_por_nombre'],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['encontrado' => false], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Valor que se copia desde el caso vinculado: "campo:<clave>" sale de su
     * caso_valor, "persona:<columna>" de su persona. Mismo juego de columnas
     * que valida cargar_fichas.php.
     */
    private function valorCopiadoDeVinculo(string $origen, array $casoVinculado, array $valoresVinculado, int $enfermedadId): string
    {
        if (str_starts_with($origen, 'campo:')) {
            $campoOrigen = CampoDef::porClave($enfermedadId, substr($origen, 6));
            return $campoOrigen ? (string) ($valoresVinculado[(int) $campoOrigen['id']] ?? '') : '';
        }
        if (str_starts_with($origen, 'persona:')) {
            $columna = substr($origen, 8);
            // "nombre_completo" (A50, 2026-09-14): "Apellidos y nombres de la
            // madre" en un solo campo, como en el PDF.
            if ($columna === 'nombre_completo') {
                return Persona::nombreCompleto($casoVinculado);
            }
            return in_array($columna, ['num_doc', 'tipo_doc', 'nombres', 'apellido_paterno', 'apellido_materno', 'fecha_nac'], true)
                ? (string) ($casoVinculado[$columna] ?? '')
                : '';
        }

        return '';
    }

    /**
     * Casos que se ofrecen para vincular: los de la MISMA ficha cuyo campo
     * "candidatos" tiene el valor declarado, no anulados y visibles para este
     * usuario -- Z21 es ficha privada (Caso::esPrivada), así que un
     * REGISTRADOR solo puede vincular fichas que él mismo registró.
     */
    private function candidatosVinculo(array $enfermedad, array $config, ?int $excluirCasoId): array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT c.id
               FROM caso c
               JOIN caso_valor cv ON cv.caso_id = c.id AND cv.campo_def_id = :campo
              WHERE c.enfermedad_id = :enf AND c.anulado = 0 AND cv.valor = :valor
           ORDER BY c.fecha_notif DESC, c.id DESC'
        );
        $consulta->execute([
            'campo' => (int) $config['_candidato']['id'],
            'enf'   => (int) $enfermedad['id'],
            'valor' => (string) $config['candidatos']['valor'],
        ]);

        $candidatos = [];
        foreach ($consulta->fetchAll() as $fila) {
            $idCandidato = (int) $fila['id'];
            if ($excluirCasoId !== null && $idCandidato === $excluirCasoId) {
                continue; // un caso no se vincula a sí mismo
            }
            $casoCandidato = Caso::conDetalle($idCandidato);
            if (!$casoCandidato || !$this->puedeVerCaso($casoCandidato)) {
                continue;
            }
            $valoresCandidato = CasoValor::porCaso($idCandidato);
            $copiarPorNombre = [];
            foreach ($config['_copiar'] as $copia) {
                $copiarPorNombre['campo_' . (int) $copia['destino']['id']] = $this->valorCopiadoDeVinculo(
                    $copia['origen'],
                    $casoCandidato,
                    $valoresCandidato,
                    (int) $enfermedad['id']
                );
            }
            $candidatos[] = [
                'id'     => $idCandidato,
                'codigo' => (string) $casoCandidato['codigo'],
                'num_doc' => (string) ($casoCandidato['num_doc'] ?? ''),
                'texto' => $casoCandidato['codigo'] . ' · '
                    . trim($casoCandidato['apellido_paterno'] . ' ' . $casoCandidato['apellido_materno'] . ', ' . $casoCandidato['nombres'])
                    . ' · ' . $casoCandidato['tipo_doc'] . ' ' . $casoCandidato['num_doc']
                    . ' · ' . fechaIsoADmy($casoCandidato['fecha_notif']),
                'copiar_por_nombre' => $copiarPorNombre,
            ];
        }

        return $candidatos;
    }

    /**
     * Datos del selector de ficha vinculada para las vistas de formulario
     * (partials/vinculo-caso.php). null en las fichas sin vinculo_caso.
     */
    private function datosVinculoCasoVista(array $enfermedad, ?int $seleccionado, ?int $casoIdActual, ?string $error = null): array
    {
        $config = $this->configVinculoCaso($enfermedad);
        if (!$config) {
            return ['vinculoCasoVista' => null];
        }
        $destinos = [];
        foreach ($config['_copiar'] as $copia) {
            $destinos[] = 'campo_' . (int) $copia['destino']['id'];
        }

        // fijar_por_procedencia (pedido del usuario, 2026-09-11): con esto
        // declarado NO se manda al navegador la lista de candidatos -- el
        // vínculo llega fijado desde la ficha de la madre o se identifica por
        // código/documento exacto en el buscador. Sin declararlo, sigue el
        // selector de siempre (mecanismo genérico, ninguna otra ficha lo usa).
        $porProcedencia = !empty($config['fijar_por_procedencia']);
        $candidatos = $this->candidatosVinculo($enfermedad, $config, $casoIdActual);
        $seleccionadoDatos = null;
        foreach ($candidatos as $candidato) {
            if ($seleccionado !== null && $candidato['id'] === (int) $seleccionado) {
                $seleccionadoDatos = $candidato;
                break;
            }
        }

        return ['vinculoCasoVista' => [
            'config'            => $config,
            'porProcedencia'    => $porProcedencia,
            'candidatos'        => $porProcedencia ? [] : $candidatos,
            'seleccionadoDatos' => $seleccionadoDatos,
            'casoIdActual'      => $casoIdActual,
            'destinos'          => $destinos,
            'seleccionado'      => $seleccionado,
            'error'             => $error,
        ]];
    }

    /**
     * Revalida el caso vinculado que llegó por POST y copia sus datos a los
     * campos declarados en "copiar" -- no se confía en lo que el navegador
     * haya dejado escrito ahí (ficha.js solo los adelanta). Si el activador
     * no aplica, el vínculo se descarta aunque venga forzado en el POST.
     *
     * @return array{0: int|null, 1: string|null} [id vinculado, error]
     */
    private function resolverVinculoCaso(array $enfermedad, array $valoresCampos, array &$paraGuardar, ?int $casoIdActual): array
    {
        $config = $this->configVinculoCaso($enfermedad);
        if (!$config) {
            return [null, null];
        }
        $activo = (string) ($valoresCampos[(int) $config['_activador']['id']] ?? '') === (string) $config['activador']['valor'];
        $idElegido = (int) ($_POST['caso_vinculado_id'] ?? 0);
        if (!$activo || $idElegido <= 0) {
            return [null, null];
        }
        foreach ($this->candidatosVinculo($enfermedad, $config, $casoIdActual) as $candidato) {
            if ($candidato['id'] !== $idElegido) {
                continue;
            }
            foreach ($candidato['copiar_por_nombre'] as $nombreDestino => $valorCopiado) {
                $idDestino = (int) substr($nombreDestino, strlen('campo_'));
                if ($valorCopiado !== '') {
                    $paraGuardar[$idDestino] = $valorCopiado;
                } else {
                    unset($paraGuardar[$idDestino]);
                }
            }
            return [$idElegido, null];
        }

        // Con "fijar_por_procedencia" no hay lista donde volver a elegir: el
        // texto lo declara la ficha ("vuelve a identificarla por código o
        // DNI"). Sin esa declaración queda el mensaje del selector de siempre.
        return [null, $config['buscar']['no_disponible']
            ?? 'La ficha elegida ya no está disponible para vincular. Vuelve a seleccionarla.'];
    }

    /**
     * Lo que la vista de solo lectura muestra del vínculo: la ficha a la que
     * este caso está enlazado, y las que lo apuntan a él (los niños nacidos
     * expuestos de una gestante, uno por producto si fue embarazo múltiple).
     */
    private function datosVinculoVer(array $enfermedad, array $caso, array $valoresCampos): ?array
    {
        $config = $this->configVinculoCaso($enfermedad);
        if (!$config) {
            return null;
        }
        $vinculada = null;
        if (!empty($caso['caso_vinculado_id'])) {
            $posible = Caso::conDetalle((int) $caso['caso_vinculado_id']);
            if ($posible && $this->puedeVerCaso($posible)) {
                $vinculada = $posible;
            }
        }
        $hijos = [];
        foreach (Caso::idsVinculados((int) $caso['id']) as $idHijo) {
            $hijo = Caso::conDetalle($idHijo);
            if ($hijo && $this->puedeVerCaso($hijo)) {
                $hijos[] = $hijo;
            }
        }
        $esCandidato = (string) ($valoresCampos[(int) $config['_candidato']['id']] ?? '') === (string) $config['candidatos']['valor'];

        // encadenar: cuántas fichas vinculadas se esperan de este caso según
        // su propio campo numérico (Z21: N.º de nacidos vivos) y cuántas
        // faltan por registrar. null cuando la ficha no lo declara o el campo
        // quedó vacío -- no se inventa un objetivo.
        $esperados = $this->fichasVinculadasEsperadas($config, $valoresCampos);

        return [
            'config'         => $config,
            'madre'          => $vinculada,
            'hijos'          => $hijos,
            'esCandidato'    => $esCandidato,
            'esperados'      => $esCandidato ? $esperados : null,
            'pendientes'     => ($esCandidato && $esperados !== null) ? max(0, $esperados - count($hijos)) : null,
            // Con 0 declarado (aborto, o solo óbitos fetales) no hay niños que
            // registrar; vacío sigue permitido: la ficha pudo registrarse
            // durante la gestación y todavía no dice cómo terminó.
            'puedeRegistrar' => $esCandidato && $esperados !== 0 && empty($caso['anulado']) && Auth::tieneRol(...self::ROLES_REGISTRO),
        ];
    }

    /**
     * vinculo_caso.encadenar: el valor del campo NUMERO que dice cuántas
     * fichas vinculadas se esperan del caso candidato. null si la ficha no lo
     * declara, el campo no existe o no trae un número.
     */
    private function fichasVinculadasEsperadas(array $config, array $valoresCampos): ?int
    {
        if (empty($config['_encadenar'])) {
            return null;
        }
        $valor = trim((string) ($valoresCampos[(int) $config['_encadenar']['id']] ?? ''));

        return ctype_digit($valor) ? (int) $valor : null;
    }

    /**
     * nucleo_condicional (cotejo Z21): ¿aplica este bloque del núcleo
     * ('etnia', 'residencia') según lo que llega en el POST? Se lee del POST
     * crudo porque la obligatoriedad del distrito se decide antes de validar
     * los campos dinámicos; el valor se revalida igual en su propio campo.
     */
    private function bloqueNucleoActivoDesdePost(array $enfermedad, string $bloque): bool
    {
        $condicion = condicionNucleo($enfermedad, $bloque);
        if (!$condicion) {
            return true;
        }

        return in_array(trim((string) ($_POST['campo_' . (int) $condicion['campo']['id']] ?? '')), $condicion['valores'], true);
    }

    private function puedeVerCaso(array $caso): bool
    {
        $usuario = Auth::usuario();
        if ($usuario['rol'] === 'REGISTRADOR') {
            $esPrivada = Caso::esPrivada($caso);
            if ($esPrivada && (int) $caso['usuario_id'] !== (int) $usuario['id']) {
                return false;
            }
            return $usuario['establecimiento_id'] === (int) $caso['establecimiento_id'];
        }

        return true;
    }

    private function puedeEditarCaso(array $caso): bool
    {
        $usuario = Auth::usuario();

        if ($caso['anulado']) {
            return false;
        }
        if (in_array($usuario['rol'], self::ROLES_CIERRE, true)) {
            return true;
        }
        if ($usuario['rol'] === 'REGISTRADOR') {
            $esPrivada = Caso::esPrivada($caso);
            if ($esPrivada && (int) $caso['usuario_id'] !== (int) $usuario['id']) {
                return false;
            }
            return $caso['estado'] === 'ABIERTA'
                && $usuario['establecimiento_id'] === (int) $caso['establecimiento_id'];
        }

        return false;
    }

    private function exigirCsrf(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /casos/nuevo');
            exit;
        }
    }

    private function extraerFechaNotificacion(int $enfermedadId): string
    {
        $secciones = \App\Models\SeccionDef::porEnfermedad($enfermedadId);
        foreach ($secciones as $seccion) {
            $campos = \App\Models\CampoDef::porSeccion((int) $seccion['id']);
            foreach ($campos as $c) {
                if ($c['tipo'] === 'FECHA' && (stripos($c['etiqueta'], 'notificación') !== false || stripos($c['etiqueta'], 'notificacion') !== false)) {
                    $val = trim($_POST['campo_' . $c['id']] ?? '');
                    if ($val !== '') {
                        return $val;
                    }
                }
            }
        }
        return '';
    }

    private function extraerFechaInicioSintomas(int $enfermedadId): string
    {
        $secciones = \App\Models\SeccionDef::porEnfermedad($enfermedadId);
        foreach ($secciones as $seccion) {
            $campos = \App\Models\CampoDef::porSeccion((int) $seccion['id']);
            foreach ($campos as $c) {
                if ($c['tipo'] === 'FECHA') {
                    $val = trim($_POST['campo_' . $c['id']] ?? '');
                    if ($val !== '') {
                        return $val;
                    }
                }
            }
        }
        return '';
    }
}
