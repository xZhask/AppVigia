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

        $this->vista('nueva/index', array_merge([
            'tituloVista'   => 'Nueva ficha de notificación',
            'rutaActual'    => 'casos/nuevo',
            'enfermedades'  => $enfermedades,
            'enfermedad'    => $enfermedad,
            'valoresFijos'  => $this->valoresFijosPorDefecto($hoyIso),
            'erroresFijos'  => [],
            'semanaEpiPreview' => $semana['semana'],
            'anioEpiPreview'   => $semana['anio'],
            'valoresCampos' => [],
            'erroresCampos' => [],
            'fechaInicioSintomas' => '',
            'errorFechaInicioSintomas' => null,
            'clasificacionActual' => opcionesClasificacionPara($enfermedad)[0],
            'filasContactos' => [],
            'filasViajes'    => [],
            'filasVacunas'   => [],
            'filasMuestras'  => [],
            'filasBloquesMuestra' => [],
            'filasLugarInfeccion' => [],
            'erroresViajes'  => [],
            'erroresVacunas' => [],
            'erroresMuestras' => [],
            'erroresLugarInfeccion' => [],
            'valoresSujetoPorRol' => [],
        ], $this->datosEstablecimiento(), $this->datosPnp(), $this->datosMuestrasCatalogo($enfermedad), $this->datosVacunasCatalogo(), $this->datosColumnasTablaHija($enfermedad), contextoUbigeo(null)));
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
            'edad_valor'         => trim($_POST['edad_valor'] ?? ''),
            'edad_unidad'        => $_POST['edad_unidad'] ?? '',
            'celular'            => trim($_POST['celular'] ?? ''),
            'nacionalidad'       => trim($_POST['nacionalidad'] ?? '') ?: 'Peruana',
            'direccion'          => trim($_POST['direccion'] ?? ''),
            'referencia_localizar' => trim($_POST['referencia_localizar'] ?? ''),
            'tipo_zona'          => $_POST['tipo_zona'] ?? '',
            'tipo_via'           => trim($_POST['tipo_via'] ?? ''),
            'nombre_via'         => trim($_POST['nombre_via'] ?? ''),
            'numero'             => trim($_POST['numero'] ?? ''),
            'mz_lote'            => trim($_POST['mz_lote'] ?? ''),
            'tiempo_residencia'  => trim($_POST['tiempo_residencia'] ?? ''),
            'n_historia_clinica' => trim($_POST['n_historia_clinica'] ?? ''),
            'localidad'          => trim($_POST['localidad'] ?? ''),
            'etnia'              => $_POST['etnia'] ?? '',
            'etnia_otra'         => trim($_POST['etnia_otra'] ?? ''),
            'pueblo_etnico'      => $_POST['pueblo_etnico'] ?? '',
            'ocupacion'          => trim($_POST['ocupacion'] ?? ''),
            'nombre_tutor'       => trim($_POST['nombre_tutor'] ?? ''),
            'celular_tutor'      => trim($_POST['celular_tutor'] ?? ''),
            'gestante'           => $_POST['gestante'] ?? '',
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

        if ($valoresFijos['num_doc'] === '') {
            $erroresFijos['num_doc'] = 'Ingresa el número de documento.';
        }
        if ($valoresFijos['apellido_paterno'] === '') {
            $erroresFijos['apellido_paterno'] = 'Ingresa el apellido paterno.';
        }
        if ($valoresFijos['nombres'] === '') {
            $erroresFijos['nombres'] = 'Ingresa los nombres.';
        }

        $fechaNacIso = null;
        if ($valoresFijos['fecha_nac'] !== '') {
            $fechaNacIso = fechaIsoValida($valoresFijos['fecha_nac']);
            if (!$fechaNacIso) {
                $erroresFijos['fecha_nac'] = 'Ingresa una fecha de nacimiento válida.';
            }
        }

        $distritoId = $_POST['distrito_id'] ?? '';
        if ($distritoId === '') {
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
        $sinFechaInicioSintomasObligatoria = in_array($enfermedad['cie10'] ?? '', ['P35.0', 'A35', 'A37.0', 'B01', 'A97'], true);
        $fechaInicioSintomas = trim($_POST['fecha_inicio_sintomas'] ?? '');
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
        $datosPnp = $this->leerDatosPnp();

        // ---------- clasificación del caso ----------
        $opcionesClasificacion = opcionesClasificacionPara($enfermedad);
        $clasificacion = $_POST['clasificacion'] ?? $opcionesClasificacion[0];
        if (!in_array($clasificacion, $opcionesClasificacion, true)) {
            $clasificacion = $opcionesClasificacion[0];
        }

        // ---------- dinámicos: cuadro clínico según la enfermedad ----------
        [$valoresCampos, $erroresCampos, $paraGuardar] = $this->validarCamposDinamicos($enfermedadId);

        // ---------- tablas hijas (opcionales) ----------
        $filasContactos = $this->filasContactos();
        [$filasViajes, $erroresViajes] = $this->filasViajes();
        [$filasVacunas, $erroresVacunas] = $this->filasVacunas();
        [$filasMuestras, $erroresMuestras] = $this->filasMuestras($enfermedad);
        [$filasLugarInfeccion, $erroresLugarInfeccion] = $this->filasLugarInfeccion();

        $hayErrores = !empty($erroresFijos) || !empty($erroresCampos) || $errorFechaInicioSintomas !== null
            || !empty($erroresViajes) || !empty($erroresVacunas) || !empty($erroresMuestras) || !empty($erroresLugarInfeccion);

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
                'filasViajes'    => $filasViajes,
                'filasVacunas'   => $filasVacunas,
                'filasMuestras'  => $filasMuestrasInicial,
                'filasBloquesMuestra' => $filasBloquesMuestra,
                'filasLugarInfeccion' => $filasLugarInfeccion,
                'erroresViajes'  => $erroresViajes,
                'erroresVacunas' => $erroresVacunas,
                'erroresMuestras' => $erroresMuestras,
                'erroresLugarInfeccion' => $erroresLugarInfeccion,
                'valoresSujetoPorRol' => $this->valoresSujetoPorRolDesdePost($enfermedad),
            ], $this->datosEstablecimiento(), $datosPnp['vista'], $this->datosMuestrasCatalogo($enfermedad), $this->datosVacunasCatalogo(), $this->datosColumnasTablaHija($enfermedad), contextoUbigeo($distritoId ?: null)));
            return;
        }

        // ---------- guardar (paciente + caso + caso_valor en una transacción) ----------
        $pdo = Database::conexion();

        try {
            $pdo->beginTransaction();

            $nucleo = $this->sanearCamposNucleo($valoresFijos, $enfermedad);

            $datosPaciente = array_merge([
                'tipo_doc'          => $valoresFijos['tipo_doc'],
                'num_doc'           => $valoresFijos['num_doc'],
                'apellido_paterno'  => $valoresFijos['apellido_paterno'],
                'apellido_materno'  => $valoresFijos['apellido_materno'] !== '' ? $valoresFijos['apellido_materno'] : null,
                'nombres'           => $valoresFijos['nombres'],
                'sexo'              => $valoresFijos['sexo'] !== '' ? $valoresFijos['sexo'] : null,
                'fecha_nac'         => $fechaNacIso,
                'distrito_id'       => $distritoId,
            ], $nucleo['persona'], $datosPnp['datos']);

            $personaExistente = Persona::buscarPorDocumento($valoresFijos['tipo_doc'], $valoresFijos['num_doc']);
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
                'establecimiento_id'    => (int) $establecimiento['id'],
                'usuario_id'            => (int) $usuario['id'],
                'fecha_notif'           => $fechaNotifIso,
                'anio_epi'              => $semana['anio'],
                'semana_epi'            => $semana['semana'],
                'fecha_inicio_sintomas' => $fechaInicioSintomasIso,
                'clasificacion'         => $clasificacion,
            ], $nucleo['caso']));

            CasoValor::guardarTodos($casoId, $paraGuardar);
            CasoContacto::reemplazarTodos($casoId, $filasContactos);
            CasoViaje::reemplazarTodos($casoId, $filasViajes);
            CasoVacuna::reemplazarTodos($casoId, $filasVacunas);
            CasoMuestra::reemplazarTodos($casoId, $filasMuestras);
            CasoLugarInfeccion::reemplazarTodos($casoId, $filasLugarInfeccion);

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
            'contactos'   => CasoContacto::porCaso((int) $caso['id']),
            'viajes'      => CasoViaje::porCaso((int) $caso['id']),
            'vacunas'     => CasoVacuna::porCaso((int) $caso['id']),
            'muestras'    => CasoMuestra::porCaso((int) $caso['id']),
            'lugaresInfeccion' => CasoLugarInfeccion::porCaso((int) $caso['id']),
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
            'edad_valor'         => (string) ($caso['edad_valor'] ?? ''),
            'edad_unidad'        => (string) ($caso['edad_unidad'] ?? ''),
            'celular'            => (string) ($caso['celular'] ?? ''),
            'nacionalidad'       => (string) ($caso['nacionalidad'] ?? ''),
            'direccion'          => (string) ($caso['direccion'] ?? ''),
            'referencia_localizar' => (string) ($caso['referencia_localizar'] ?? ''),
            'tipo_zona'          => (string) ($caso['tipo_zona'] ?? ''),
            'tipo_via'           => (string) ($caso['tipo_via'] ?? ''),
            'nombre_via'         => (string) ($caso['nombre_via'] ?? ''),
            'numero'             => (string) ($caso['numero'] ?? ''),
            'mz_lote'            => (string) ($caso['mz_lote'] ?? ''),
            'tiempo_residencia'  => (string) ($caso['tiempo_residencia'] ?? ''),
            'n_historia_clinica' => (string) ($caso['n_historia_clinica'] ?? ''),
            'localidad'          => (string) ($caso['localidad'] ?? ''),
            'etnia'              => (string) ($caso['etnia'] ?? ''),
            'etnia_otra'         => (string) ($caso['etnia_otra'] ?? ''),
            'pueblo_etnico'      => (string) ($caso['pueblo_etnico'] ?? ''),
            'ocupacion'          => (string) ($caso['ocupacion'] ?? ''),
            'nombre_tutor'       => (string) ($caso['nombre_tutor'] ?? ''),
            'celular_tutor'      => (string) ($caso['celular_tutor'] ?? ''),
            'gestante'           => $caso['gestante'] !== null ? (string) $caso['gestante'] : '',
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
            'filasViajes'    => CasoViaje::porCaso((int) $caso['id']),
            'filasVacunas'   => CasoVacuna::porCaso((int) $caso['id']),
            'filasMuestras'  => $filasMuestrasInicial,
            'filasBloquesMuestra' => $filasBloquesMuestra,
            'filasLugarInfeccion' => CasoLugarInfeccion::porCaso((int) $caso['id']),
            'erroresViajes'  => [],
            'erroresVacunas' => [],
            'erroresLugarInfeccion' => [],
            'erroresMuestras' => [],
            'valoresSujetoPorRol' => CasoSujeto::porCaso((int) $caso['id']),
        ], $this->datosPnpEdicion($caso), $this->datosMuestrasCatalogo($enfermedad), $this->datosVacunasCatalogo(), $this->datosColumnasTablaHija($enfermedad), contextoUbigeo($caso['distrito_id'])));
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
            'edad_valor'         => trim($_POST['edad_valor'] ?? ''),
            'edad_unidad'        => $_POST['edad_unidad'] ?? '',
            'celular'            => trim($_POST['celular'] ?? ''),
            'nacionalidad'       => trim($_POST['nacionalidad'] ?? '') ?: 'Peruana',
            'direccion'          => trim($_POST['direccion'] ?? ''),
            'referencia_localizar' => trim($_POST['referencia_localizar'] ?? ''),
            'tipo_zona'          => $_POST['tipo_zona'] ?? '',
            'tipo_via'           => trim($_POST['tipo_via'] ?? ''),
            'nombre_via'         => trim($_POST['nombre_via'] ?? ''),
            'numero'             => trim($_POST['numero'] ?? ''),
            'mz_lote'            => trim($_POST['mz_lote'] ?? ''),
            'tiempo_residencia'  => trim($_POST['tiempo_residencia'] ?? ''),
            'n_historia_clinica' => trim($_POST['n_historia_clinica'] ?? ''),
            'localidad'          => trim($_POST['localidad'] ?? ''),
            'etnia'              => $_POST['etnia'] ?? '',
            'etnia_otra'         => trim($_POST['etnia_otra'] ?? ''),
            'pueblo_etnico'      => $_POST['pueblo_etnico'] ?? '',
            'ocupacion'          => trim($_POST['ocupacion'] ?? ''),
            'nombre_tutor'       => trim($_POST['nombre_tutor'] ?? ''),
            'celular_tutor'      => trim($_POST['celular_tutor'] ?? ''),
            'gestante'           => $_POST['gestante'] ?? '',
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
        if ($valoresFijos['nombres'] === '') {
            $erroresFijos['nombres'] = 'Ingresa los nombres.';
        }

        $fechaNacIso = null;
        if ($valoresFijos['fecha_nac'] !== '') {
            $fechaNacIso = fechaIsoValida($valoresFijos['fecha_nac']);
            if (!$fechaNacIso) {
                $erroresFijos['fecha_nac'] = 'Ingresa una fecha de nacimiento válida.';
            }
        }

        $distritoId = $_POST['distrito_id'] ?? '';
        if ($distritoId === '') {
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
        $sinFechaInicioSintomasObligatoria = in_array($enfermedad['cie10'] ?? '', ['P35.0', 'A35', 'A37.0', 'B01', 'A97'], true);
        $fechaInicioSintomas = trim($_POST['fecha_inicio_sintomas'] ?? '');
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

        $datosPnp = $this->leerDatosPnp();
        [$valoresCampos, $erroresCampos, $paraGuardar] = $this->validarCamposDinamicos($enfermedadId);

        $opcionesClasificacion = opcionesClasificacionPara($enfermedad);
        $clasificacion = $_POST['clasificacion'] ?? $caso['clasificacion'];
        if (!in_array($clasificacion, $opcionesClasificacion, true)) {
            $clasificacion = $caso['clasificacion'];
        }
        $hospitalizado = isset($_POST['hospitalizado']) ? 1 : 0;
        $fallecido = isset($_POST['fallecido']) ? 1 : 0;

        $filasContactos = $this->filasContactos();
        [$filasViajes, $erroresViajes] = $this->filasViajes();
        [$filasVacunas, $erroresVacunas] = $this->filasVacunas();
        [$filasMuestras, $erroresMuestras] = $this->filasMuestras($enfermedad);
        [$filasLugarInfeccion, $erroresLugarInfeccion] = $this->filasLugarInfeccion();

        $hayErrores = !empty($erroresFijos) || !empty($erroresCampos) || $errorFechaInicioSintomas !== null
            || !empty($erroresViajes) || !empty($erroresVacunas) || !empty($erroresMuestras) || !empty($erroresLugarInfeccion);

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
                'filasViajes'    => $filasViajes,
                'filasVacunas'   => $filasVacunas,
                'filasMuestras'  => $filasMuestrasInicial,
                'filasBloquesMuestra' => $filasBloquesMuestra,
                'filasLugarInfeccion' => $filasLugarInfeccion,
                'erroresViajes'  => $erroresViajes,
                'erroresVacunas' => $erroresVacunas,
                'erroresMuestras' => $erroresMuestras,
                'erroresLugarInfeccion' => $erroresLugarInfeccion,
                'valoresSujetoPorRol' => $this->valoresSujetoPorRolDesdePost($enfermedad),
            ], $datosPnp['vista'], $this->datosMuestrasCatalogo($enfermedad), $this->datosVacunasCatalogo(), $this->datosColumnasTablaHija($enfermedad), contextoUbigeo($distritoId ?: null)));
            return;
        }

        $pdo = Database::conexion();

        try {
            $pdo->beginTransaction();

            $nucleo = $this->sanearCamposNucleo($valoresFijos, $enfermedad);

            $datosPaciente = array_merge([
                'apellido_paterno'  => $valoresFijos['apellido_paterno'],
                'apellido_materno'  => $valoresFijos['apellido_materno'] !== '' ? $valoresFijos['apellido_materno'] : null,
                'nombres'           => $valoresFijos['nombres'],
                'sexo'              => $valoresFijos['sexo'] !== '' ? $valoresFijos['sexo'] : null,
                'fecha_nac'         => $fechaNacIso,
                'distrito_id'       => $distritoId,
            ], $nucleo['persona'], $datosPnp['datos']);

            $persona = Persona::buscarPorDocumento($caso['tipo_doc'], $caso['num_doc']);
            $personaId = (int) $persona['id'];

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
            ], $nucleo['caso']));

            CasoValor::eliminarPorCaso((int) $caso['id']);
            CasoValor::guardarTodos((int) $caso['id'], $paraGuardar);
            CasoContacto::reemplazarTodos((int) $caso['id'], $filasContactos);
            CasoViaje::reemplazarTodos((int) $caso['id'], $filasViajes);
            CasoVacuna::reemplazarTodos((int) $caso['id'], $filasVacunas);
            CasoMuestra::reemplazarTodos((int) $caso['id'], $filasMuestras);
            CasoLugarInfeccion::reemplazarTodos((int) $caso['id'], $filasLugarInfeccion);

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
    private function validarCamposDinamicos(int $enfermedadId): array
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
                $valoresCampos[$campoId] = in_array($tipo, ['MULTISELECT', 'GRUPO_SI_NO', 'SI_NO_FECHA', 'MATRIZ', 'CRONOLOGIA'], true) ? [] : '';
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
                $marcado = isset($_POST[$nombreCampo]) ? '1' : '0';
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

            if (in_array($tipo, ['GRUPO_SI_NO', 'SI_NO_FECHA', 'MATRIZ', 'CRONOLOGIA'], true) || is_array($_POST[$nombreCampo] ?? null)) {
                $valorCrudo = $_POST[$nombreCampo] ?? [];
                if (!is_array($valorCrudo)) {
                    $valorCrudo = [];
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

            switch ($tipo) {
                case 'NUMERO':
                    if (!is_numeric($valor)) {
                        $erroresCampos[$campoId] = 'Ingresa un número válido.';
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
                    $paraGuardar[$campoId] = $valor;
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
            } elseif (in_array($campo['tipo'], ['GRUPO_SI_NO', 'SI_NO_FECHA', 'MATRIZ', 'CRONOLOGIA'], true)) {
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
            'edad_valor'         => '',
            'edad_unidad'        => '',
            'celular'            => '',
            'nacionalidad'       => 'Peruana',
            'direccion'          => '',
            'referencia_localizar' => '',
            'tipo_zona'          => '',
            'tipo_via'           => '',
            'nombre_via'         => '',
            'numero'             => '',
            'mz_lote'            => '',
            'tiempo_residencia'  => '',
            'n_historia_clinica' => '',
            'localidad'          => '',
            'etnia'              => '',
            'etnia_otra'         => '',
            'pueblo_etnico'      => '',
            'ocupacion'          => '',
            'nombre_tutor'       => '',
            'celular_tutor'      => '',
            'gestante'           => '',
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
        $edadUnidad = in_array($valoresFijos['edad_unidad'] ?? '', $unidadesEdadPermitidas, true) ? $valoresFijos['edad_unidad'] : null;
        $edadValor = ($edadUnidad !== null && is_numeric($valoresFijos['edad_valor'] ?? '') && (int) $valoresFijos['edad_valor'] >= 0)
            ? (int) $valoresFijos['edad_valor']
            : null;
        if ($edadValor === null) {
            $edadUnidad = null;
        }

        $etnias = ['MESTIZO', 'ANDINO', 'ASIATICO_DESCENDIENTE', 'AFRODESCENDIENTE', 'INDIGENA_AMAZONICO', 'OTRO'];
        $etnia = in_array($valoresFijos['etnia'], $etnias, true) ? $valoresFijos['etnia'] : null;
        $etniaOtra = ($etnia === 'OTRO' && $valoresFijos['etnia_otra'] !== '') ? $valoresFijos['etnia_otra'] : null;

        // Mismas 19 opciones que catalogo_id=537 (b05_pueblo_etnico_o_etnia,
        // ya retirado) y que MAPA_GRUPO_ETNICO en ficha.js -- cascada desde
        // 'etnia', no depende de a qué grupo pertenezcan acá.
        $pueblosEtnicos = ['Quechua', 'Aymara', 'Jaqaru', 'Uro', 'Asháninka', 'Awajún', 'Shipibo-Konibo', 'Yánesha', 'Kukama Kukamiria', 'Achuar', 'Bora', 'Matsés', 'Ese Eja', 'Harakbut', 'Afroperuano', 'No aplica', 'Chino-peruano', 'Japonés-peruano', 'Otro'];
        $puebloEtnico = in_array($valoresFijos['pueblo_etnico'], $pueblosEtnicos, true) ? $valoresFijos['pueblo_etnico'] : null;

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

        $gestante = null;
        $semanasGestacion = null;
        $trimestreGestacion = null;
        if ($valoresFijos['sexo'] === 'F' && in_array($valoresFijos['gestante'], ['0', '1'], true)) {
            $gestante = (int) $valoresFijos['gestante'];
            if ($gestante === 1) {
                if (is_numeric($valoresFijos['semanas_gestacion'])) {
                    $semanasGestacion = (int) $valoresFijos['semanas_gestacion'];
                }
                if (in_array($valoresFijos['trimestre_gestacion'], ['I', 'II', 'III'], true)) {
                    $trimestreGestacion = $valoresFijos['trimestre_gestacion'];
                }
            }
        }

        $tipoCaptacion = in_array($valoresFijos['tipo_captacion'], ['ACTIVA', 'PASIVA'], true) ? $valoresFijos['tipo_captacion'] : null;
        $lugarCaptacion = in_array($valoresFijos['lugar_captacion'], ['INSTITUCIONAL', 'COMUNIDAD'], true) ? $valoresFijos['lugar_captacion'] : null;
        $clasificacionCaptacion = in_array($valoresFijos['clasificacion_captacion'], ['CONFIRMADO', 'PROBABLE', 'SOSPECHOSO'], true) ? $valoresFijos['clasificacion_captacion'] : null;

        return [
            'persona' => [
                'celular'            => $valoresFijos['celular'] !== '' ? $valoresFijos['celular'] : null,
                'nacionalidad'       => $valoresFijos['nacionalidad'] !== '' ? $valoresFijos['nacionalidad'] : null,
                'direccion'          => $valoresFijos['direccion'] !== '' ? $valoresFijos['direccion'] : null,
                'referencia_localizar' => $valoresFijos['referencia_localizar'] !== '' ? $valoresFijos['referencia_localizar'] : null,
                'tipo_zona'          => $tipoZona,
                'tipo_via'           => $tipoVia,
                'nombre_via'         => $nombreVia,
                'numero'             => $numeroDomicilio,
                'mz_lote'            => $mzLote,
                'tiempo_residencia'  => $tiempoResidencia,
                'n_historia_clinica' => $nHistoriaClinica,
                'localidad'          => $valoresFijos['localidad'] !== '' ? $valoresFijos['localidad'] : null,
                'etnia'              => $etnia,
                'etnia_otra'         => $etniaOtra,
                'pueblo_etnico'      => $puebloEtnico,
                'ocupacion'          => $valoresFijos['ocupacion'] !== '' ? $valoresFijos['ocupacion'] : null,
                'nombre_tutor'       => $valoresFijos['nombre_tutor'] !== '' ? $valoresFijos['nombre_tutor'] : null,
                'celular_tutor'      => $valoresFijos['celular_tutor'] !== '' ? $valoresFijos['celular_tutor'] : null,
                'gestante'           => $gestante,
                'semanas_gestacion'  => $semanasGestacion,
                'trimestre_gestacion'=> $trimestreGestacion,
            ],
            'caso' => [
                'edad_valor'              => $edadValor,
                'edad_unidad'             => $edadUnidad,
                'tipo_captacion'          => $tipoCaptacion,
                'lugar_captacion'         => $lugarCaptacion,
                'clasificacion_captacion' => $clasificacionCaptacion,
                'investigador_nombre'     => $valoresFijos['investigador_nombre'] !== '' ? $valoresFijos['investigador_nombre'] : null,
                'investigador_cargo'      => $valoresFijos['investigador_cargo'] !== '' ? $valoresFijos['investigador_cargo'] : null,
                'investigador_profesion'  => $valoresFijos['investigador_profesion'] !== '' ? $valoresFijos['investigador_profesion'] : null,
                'investigador_telefono'   => $valoresFijos['investigador_telefono'] !== '' ? $valoresFijos['investigador_telefono'] : null,
                'investigador_email'      => $valoresFijos['investigador_email'] !== '' ? $valoresFijos['investigador_email'] : null,
                'fecha_investigacion'     => $valoresFijos['fecha_investigacion'] !== '' ? fechaIsoValida($valoresFijos['fecha_investigacion']) : null,
            ],
        ];
    }

    /**
     * Datos y catálogos para la condición del paciente en "Nueva ficha"
     * (radio EFECTIVO/DERECHOHABIENTE/PARTICULAR + campos por condición),
     * con valores en blanco.
     */
    private function datosPnp(): array
    {
        return [
            'condicionPaciente' => 'PARTICULAR',
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
     * @return array{datos: array, vista: array}
     */
    private function leerDatosPnp(): array
    {
        $condicion = $_POST['condicion'] ?? 'PARTICULAR';
        if (!in_array($condicion, ['EFECTIVO', 'DERECHOHABIENTE', 'PARTICULAR'], true)) {
            $condicion = 'PARTICULAR';
        }

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
        $porDefecto = ['columnas' => self::COLUMNAS_HIJA_DEFECTO['muestra'], 'opciones' => [], 'textoLibre' => [], 'dependeDeColumna' => []];

        $json = $enfermedad['columnas_muestra'] ?? null;
        if ($json === null) {
            return $porDefecto;
        }
        $decodificado = json_decode($json, true);
        if (!is_array($decodificado)) {
            return $porDefecto;
        }
        if (array_is_list($decodificado)) {
            return ['columnas' => $decodificado, 'opciones' => [], 'textoLibre' => [], 'dependeDeColumna' => []];
        }
        return [
            'columnas'         => $decodificado['columnas'] ?? self::COLUMNAS_HIJA_DEFECTO['muestra'],
            'opciones'         => $decodificado['opciones'] ?? [],
            'textoLibre'       => $decodificado['texto_libre'] ?? [],
            'dependeDeColumna' => $decodificado['depende_de_columna'] ?? [],
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

        $config   = $enfermedad ? $this->resolverConfigMuestra($enfermedad) : ['opciones' => [], 'textoLibre' => [], 'dependeDeColumna' => []];
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
            ];
        }

        return $filas;
    }

    private const ERROR_FECHA_INVALIDA = 'Ingresa una fecha válida.';

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
        $transportesIda = $_POST['viaje_transporte_ida'] ?? [];
        $transportesRetorno = $_POST['viaje_transporte_retorno'] ?? [];
        $semanasGestacion = $_POST['viaje_semana_gestacion'] ?? [];

        $filas = [];
        $errores = [];
        foreach ($lugares as $i => $lugar) {
            $lugar = trim((string) $lugar);
            $localidad = trim((string) ($localidades[$i] ?? ''));
            $distritoId = trim((string) ($distritos[$i] ?? ''));
            $direccion = trim((string) ($direcciones[$i] ?? ''));
            $salidaTxt = trim((string) ($salidas[$i] ?? ''));
            $retornoTxt = trim((string) ($retornos[$i] ?? ''));
            $transIda = trim((string) ($transportesIda[$i] ?? ''));
            $transRetorno = trim((string) ($transportesRetorno[$i] ?? ''));
            $semanaGestacionTxt = trim((string) ($semanasGestacion[$i] ?? ''));

            if ($lugar === '' && $localidad === '' && $distritoId === '' && $direccion === '' && $salidaTxt === '' && $retornoTxt === '' && $transIda === '' && $transRetorno === '' && $semanaGestacionTxt === '') {
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
                'semana_gestacion'   => $semanaGestacionTxt !== '' ? (int) $semanaGestacionTxt : null,
                'transporte_ida'     => $transIda !== '' ? $transIda : null,
                'transporte_retorno' => $transRetorno !== '' ? $transRetorno : null,
            ];
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

            $resPcr = trim((string) ($resultadosPcr[$i] ?? ''));
            $resPcrTxt = trim((string) ($fechasResultPcr[$i] ?? ''));
            $genotipoTxt = trim((string) ($genotipos[$i] ?? ''));
            $resIgm = trim((string) ($resultadosIgm[$i] ?? ''));
            $resIgmTxt = trim((string) ($fechasResultIgm[$i] ?? ''));
            $resIgg = trim((string) ($resultadosIgg[$i] ?? ''));
            $resIggTxt = trim((string) ($fechasResultIgg[$i] ?? ''));
            $titulacionTxt = trim((string) ($titulaciones[$i] ?? ''));

            if ($tipoMuestra === '' && $tipoPrueba === '' && $resultado === '' && $tomaTxt === '' && $envioEessRedTxt === '' && $envioRedLrrTxt === '' && $envioLrrInsTxt === '' && $resultTxt === '' && $envioInsTxt === '' && $recepInsTxt === '' && $agenteTxt === '' && $obsTxt === '' && $resPcr === '' && $resPcrTxt === '' && $genotipoTxt === '' && $resIgm === '' && $resIgmTxt === '' && $resIgg === '' && $resIggTxt === '' && $titulacionTxt === '') {
                continue;
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

            $antibiotico = $recibioAntibiotico[$i] ?? '';

            $filas[] = [
                'tipo_muestra'        => in_array($tipoMuestra, $validosTipoMuestra, true) ? $tipoMuestra : (in_array($tipoMuestra, ['SUERO', 'HNF_FAR', 'ORINA'], true) ? $tipoMuestra : null),
                'tipo_prueba'         => in_array($tipoPrueba, $validosTipoPrueba, true) ? $tipoPrueba : null,
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
