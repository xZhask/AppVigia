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
            'filasLugarInfeccion' => [],
            'erroresViajes'  => [],
            'erroresVacunas' => [],
            'erroresMuestras' => [],
            'erroresLugarInfeccion' => [],
        ], $this->datosEstablecimiento(), $this->datosPnp(), $this->datosMuestrasCatalogo(), contextoUbigeo(null)));
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
            'celular'            => trim($_POST['celular'] ?? ''),
            'nacionalidad'       => trim($_POST['nacionalidad'] ?? '') ?: 'Peruana',
            'direccion'          => trim($_POST['direccion'] ?? ''),
            'localidad'          => trim($_POST['localidad'] ?? ''),
            'etnia'              => $_POST['etnia'] ?? '',
            'gestante'           => $_POST['gestante'] ?? '',
            'semanas_gestacion'  => trim($_POST['semanas_gestacion'] ?? ''),
            'tipo_captacion'         => $_POST['tipo_captacion'] ?? '',
            'lugar_captacion'        => $_POST['lugar_captacion'] ?? '',
            'clasificacion_captacion' => $_POST['clasificacion_captacion'] ?? '',
            'investigador_nombre'    => trim($_POST['investigador_nombre'] ?? ''),
            'investigador_cargo'     => trim($_POST['investigador_cargo'] ?? ''),
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

        $fechaInicioSintomas = trim($_POST['fecha_inicio_sintomas'] ?? '');
        $fechaInicioSintomasIso = null;
        $errorFechaInicioSintomas = null;
        if ($fechaInicioSintomas === '') {
            $errorFechaInicioSintomas = 'Ingresa la fecha de inicio de síntomas.';
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
        [$filasMuestras, $erroresMuestras] = $this->filasMuestras();
        [$filasLugarInfeccion, $erroresLugarInfeccion] = $this->filasLugarInfeccion();

        $hayErrores = !empty($erroresFijos) || !empty($erroresCampos) || $errorFechaInicioSintomas !== null
            || !empty($erroresViajes) || !empty($erroresVacunas) || !empty($erroresMuestras) || !empty($erroresLugarInfeccion);

        if ($hayErrores) {
            $semana = semanaEpidemiologica($fechaNotifIso ?: (new DateTime())->format('Y-m-d'));

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
                'filasMuestras'  => $filasMuestras,
                'filasLugarInfeccion' => $filasLugarInfeccion,
                'erroresViajes'  => $erroresViajes,
                'erroresVacunas' => $erroresVacunas,
                'erroresMuestras' => $erroresMuestras,
                'erroresLugarInfeccion' => $erroresLugarInfeccion,
            ], $this->datosEstablecimiento(), $datosPnp['vista'], $this->datosMuestrasCatalogo(), contextoUbigeo($distritoId ?: null)));
            return;
        }

        // ---------- guardar (paciente + caso + caso_valor en una transacción) ----------
        $pdo = Database::conexion();

        try {
            $pdo->beginTransaction();

            $nucleo = $this->sanearCamposNucleo($valoresFijos);

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
            \App\Models\CasoSujeto::guardarSujetos($casoId, [
                $rolPrincipal => ['persona_id' => $personaId]
            ]);

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
     * Endpoint AJAX: recarga solo las secciones clínicas al cambiar de
     * enfermedad, sin perder lo ya capturado en Notificación/Paciente.
     */
    public function seccionesClinicas(): void
    {
        Auth::exigirRol(...self::ROLES_REGISTRO);

        $enfermedadId = (int) ($_GET['enfermedad_id'] ?? 0);
        $enfermedad = Enfermedad::buscar($enfermedadId);

        header('Content-Type: application/json; charset=utf-8');

        if (!$enfermedad) {
            http_response_code(404);
            echo json_encode(['error' => 'Enfermedad no encontrada']);
            return;
        }

        $numeroSeccionInicial = 3;
        $valoresCampos = [];
        $erroresCampos = [];
        $fechaInicioSintomas = '';
        $errorFechaInicioSintomas = null;

        ob_start();
        require __DIR__ . '/../Views/partials/secciones-clinicas.php';
        $html = ob_get_clean();

        echo json_encode([
            'html'        => $html,
            'cie10'       => $enfermedad['cie10'] ?: '—',
            'nombreCorto' => mb_strtolower(explode(' /', $enfermedad['nombre'])[0]),
        ]);
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
            'celular'            => (string) ($caso['celular'] ?? ''),
            'nacionalidad'       => (string) ($caso['nacionalidad'] ?? ''),
            'direccion'          => (string) ($caso['direccion'] ?? ''),
            'localidad'          => (string) ($caso['localidad'] ?? ''),
            'etnia'              => (string) ($caso['etnia'] ?? ''),
            'gestante'           => $caso['gestante'] !== null ? (string) $caso['gestante'] : '',
            'semanas_gestacion'  => (string) ($caso['semanas_gestacion'] ?? ''),
            'tipo_captacion'         => (string) ($caso['tipo_captacion'] ?? ''),
            'lugar_captacion'        => (string) ($caso['lugar_captacion'] ?? ''),
            'clasificacion_captacion' => (string) ($caso['clasificacion_captacion'] ?? ''),
            'investigador_nombre'    => (string) ($caso['investigador_nombre'] ?? ''),
            'investigador_cargo'     => (string) ($caso['investigador_cargo'] ?? ''),
            'fecha_investigacion'    => (string) ($caso['fecha_investigacion'] ?? ''),
        ];

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
            'filasMuestras'  => CasoMuestra::porCaso((int) $caso['id']),
            'filasLugarInfeccion' => CasoLugarInfeccion::porCaso((int) $caso['id']),
            'erroresViajes'  => [],
            'erroresVacunas' => [],
            'erroresLugarInfeccion' => [],
            'erroresMuestras' => [],
        ], $this->datosPnpEdicion($caso), $this->datosMuestrasCatalogo(), contextoUbigeo($caso['distrito_id'])));
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
            'celular'            => trim($_POST['celular'] ?? ''),
            'nacionalidad'       => trim($_POST['nacionalidad'] ?? '') ?: 'Peruana',
            'direccion'          => trim($_POST['direccion'] ?? ''),
            'localidad'          => trim($_POST['localidad'] ?? ''),
            'etnia'              => $_POST['etnia'] ?? '',
            'gestante'           => $_POST['gestante'] ?? '',
            'semanas_gestacion'  => trim($_POST['semanas_gestacion'] ?? ''),
            'tipo_captacion'         => $_POST['tipo_captacion'] ?? '',
            'lugar_captacion'        => $_POST['lugar_captacion'] ?? '',
            'clasificacion_captacion' => $_POST['clasificacion_captacion'] ?? '',
            'investigador_nombre'    => trim($_POST['investigador_nombre'] ?? ''),
            'investigador_cargo'     => trim($_POST['investigador_cargo'] ?? ''),
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

        $fechaInicioSintomas = trim($_POST['fecha_inicio_sintomas'] ?? '');
        $fechaInicioSintomasIso = null;
        $errorFechaInicioSintomas = null;
        if ($fechaInicioSintomas === '') {
            $errorFechaInicioSintomas = 'Ingresa la fecha de inicio de síntomas.';
        } else {
            $fechaInicioSintomasIso = fechaIsoValida($fechaInicioSintomas);
            if (!$fechaInicioSintomasIso) {
                $errorFechaInicioSintomas = 'Ingresa una fecha de inicio de síntomas válida.';
            }
        }

        $datosPnp = $this->leerDatosPnp();
        $valoresExistentesCrudo = CasoValor::porCaso((int) $caso['id']);
        [$valoresCampos, $erroresCampos, $paraGuardar] = $this->validarCamposDinamicos($enfermedadId, $valoresExistentesCrudo);

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
        [$filasMuestras, $erroresMuestras] = $this->filasMuestras();
        [$filasLugarInfeccion, $erroresLugarInfeccion] = $this->filasLugarInfeccion();

        $hayErrores = !empty($erroresFijos) || !empty($erroresCampos) || $errorFechaInicioSintomas !== null
            || !empty($erroresViajes) || !empty($erroresVacunas) || !empty($erroresMuestras) || !empty($erroresLugarInfeccion);

        if ($hayErrores) {
            $caso['clasificacion'] = $clasificacion;
            $caso['hospitalizado'] = $hospitalizado;
            $caso['fallecido'] = $fallecido;

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
                'filasMuestras'  => $filasMuestras,
                'filasLugarInfeccion' => $filasLugarInfeccion,
                'erroresViajes'  => $erroresViajes,
                'erroresVacunas' => $erroresVacunas,
                'erroresMuestras' => $erroresMuestras,
                'erroresLugarInfeccion' => $erroresLugarInfeccion,
            ], $datosPnp['vista'], $this->datosMuestrasCatalogo(), contextoUbigeo($distritoId ?: null)));
            return;
        }

        $pdo = Database::conexion();

        try {
            $pdo->beginTransaction();

            $nucleo = $this->sanearCamposNucleo($valoresFijos);

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
                
            \App\Models\CasoSujeto::guardarSujetos((int) $caso['id'], [
                $rolPrincipal => ['persona_id' => $personaId]
            ]);

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
    private function validarCamposDinamicos(int $enfermedadId, array $valoresExistentesCrudo = []): array
    {
        $campos = CampoDef::porEnfermedad($enfermedadId);
        $valoresCampos = [];
        $erroresCampos = [];
        $paraGuardar = [];
        $puedeVerSensibles = Auth::tieneRol('ADMIN');

        foreach ($campos as $campoId => $campo) {
            $tipo = $campo['tipo'];
            $obligatorio = (int) $campo['obligatorio'] === 1;
            $sensible = !empty($campo['sensible']);
            $nombreCampo = 'campo_' . $campoId;

            if ($sensible && !$puedeVerSensibles) {
                if (isset($valoresExistentesCrudo[$campoId])) {
                    $paraGuardar[$campoId] = $valoresExistentesCrudo[$campoId];
                }
                continue;
            }

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

            if (in_array($tipo, ['GRUPO_SI_NO', 'SI_NO_FECHA', 'MATRIZ', 'CRONOLOGIA'], true)) {
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
                $valores[$campoId] = $crudo ?? '';
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
            'celular'            => '',
            'nacionalidad'       => 'Peruana',
            'direccion'          => '',
            'localidad'          => '',
            'etnia'              => '',
            'gestante'           => '',
            'semanas_gestacion'  => '',
            'tipo_captacion'         => '',
            'lugar_captacion'        => '',
            'clasificacion_captacion' => '',
            'investigador_nombre'    => Auth::usuario()['nombre'] ?? '',
            'investigador_cargo'     => '',
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
    private function sanearCamposNucleo(array $valoresFijos): array
    {
        $etnias = ['MESTIZO', 'ANDINO', 'ASIATICO_DESCENDIENTE', 'AFRODESCENDIENTE', 'INDIGENA_AMAZONICO', 'OTRO'];
        $etnia = in_array($valoresFijos['etnia'], $etnias, true) ? $valoresFijos['etnia'] : null;

        $gestante = null;
        $semanasGestacion = null;
        if ($valoresFijos['sexo'] === 'F' && in_array($valoresFijos['gestante'], ['0', '1'], true)) {
            $gestante = (int) $valoresFijos['gestante'];
            if ($gestante === 1 && is_numeric($valoresFijos['semanas_gestacion'])) {
                $semanasGestacion = (int) $valoresFijos['semanas_gestacion'];
            }
        }

        $tipoCaptacion = in_array($valoresFijos['tipo_captacion'], ['ACTIVA', 'PASIVA'], true) ? $valoresFijos['tipo_captacion'] : null;
        $lugarCaptacion = in_array($valoresFijos['lugar_captacion'], ['INSTITUCIONAL', 'COMUNIDAD'], true) ? $valoresFijos['lugar_captacion'] : null;
        $clasificacionCaptacion = in_array($valoresFijos['clasificacion_captacion'], ['CONFIRMADO', 'PROBABLE', 'SOSPECHOSO'], true) ? $valoresFijos['clasificacion_captacion'] : null;

        return [
            'persona' => [
                'celular'           => $valoresFijos['celular'] !== '' ? $valoresFijos['celular'] : null,
                'nacionalidad'      => $valoresFijos['nacionalidad'] !== '' ? $valoresFijos['nacionalidad'] : null,
                'direccion'         => $valoresFijos['direccion'] !== '' ? $valoresFijos['direccion'] : null,
                'localidad'         => $valoresFijos['localidad'] !== '' ? $valoresFijos['localidad'] : null,
                'etnia'             => $etnia,
                'gestante'          => $gestante,
                'semanas_gestacion' => $semanasGestacion,
            ],
            'caso' => [
                'tipo_captacion'          => $tipoCaptacion,
                'lugar_captacion'         => $lugarCaptacion,
                'clasificacion_captacion' => $clasificacionCaptacion,
                'investigador_nombre'     => $valoresFijos['investigador_nombre'] !== '' ? $valoresFijos['investigador_nombre'] : null,
                'investigador_cargo'      => $valoresFijos['investigador_cargo'] !== '' ? $valoresFijos['investigador_cargo'] : null,
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

    private function datosMuestrasCatalogo(): array
    {
        return [
            'opcionesTipoMuestra' => CatalogoItem::porCatalogo(4),
            'opcionesTipoPrueba'  => CatalogoItem::porCatalogo(5),
            'opcionesResultado'   => CatalogoItem::porCatalogo(3),
        ];
    }

    private function filasContactos(): array
    {
        $nombres = $_POST['contacto_nombres'] ?? [];
        $parentescos = $_POST['contacto_parentesco'] ?? [];
        $edades = $_POST['contacto_edad'] ?? [];
        $sexos = $_POST['contacto_sexo'] ?? [];
        $vacunados = $_POST['contacto_vacunado'] ?? [];
        $fechasVacunacion = $_POST['contacto_fecha_vacunacion'] ?? [];
        $profilaxis = $_POST['contacto_profilaxis'] ?? [];
        $docs = $_POST['contacto_doc'] ?? [];
        $celulares = $_POST['contacto_celular'] ?? [];
        $fechasContacto = $_POST['contacto_fecha_contacto'] ?? [];
        $lugaresContacto = $_POST['contacto_lugar_contacto'] ?? [];
        $fechasInicioErupcion = $_POST['contacto_fecha_inicio_erupcion'] ?? [];
        $vacunados72h = $_POST['contacto_vacunado_72h'] ?? [];

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
            $fechaInicioErupcion = trim((string) ($fechasInicioErupcion[$i] ?? ''));
            $vacunado72h = $vacunados72h[$i] ?? '';

            $filas[] = [
                'nombres'               => $nombre,
                'parentesco'            => trim((string) ($parentescos[$i] ?? '')) ?: null,
                'edad'                  => $edad !== '' && is_numeric($edad) ? (int) $edad : null,
                'sexo'                  => in_array($sexo, ['M', 'F'], true) ? $sexo : null,
                'vacunado'              => in_array($vacunado, ['SI', 'NO', 'IGNORADO'], true) ? $vacunado : null,
                'fecha_vacunacion'      => $fechaVacunacion !== '' ? fechaIsoValida($fechaVacunacion) : null,
                'profilaxis'            => in_array($profilaxisFila, ['SI', 'NO'], true) ? $profilaxisFila : null,
                'doc'                   => trim((string) ($docs[$i] ?? '')) ?: null,
                'celular'               => trim((string) ($celulares[$i] ?? '')) ?: null,
                'fecha_contacto'        => $fechaContacto !== '' ? fechaIsoValida($fechaContacto) : null,
                'lugar_contacto'        => trim((string) ($lugaresContacto[$i] ?? '')) ?: null,
                'fecha_inicio_erupcion' => $fechaInicioErupcion !== '' ? fechaIsoValida($fechaInicioErupcion) : null,
                'vacunado_72h'          => in_array($vacunado72h, ['SI', 'NO', 'DESCONOCIDO'], true) ? $vacunado72h : null,
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
        $salidas = $_POST['viaje_fecha_salida'] ?? [];
        $retornos = $_POST['viaje_fecha_retorno'] ?? [];

        $filas = [];
        $errores = [];
        foreach ($lugares as $i => $lugar) {
            $lugar = trim((string) $lugar);
            $salidaTxt = trim((string) ($salidas[$i] ?? ''));
            $retornoTxt = trim((string) ($retornos[$i] ?? ''));
            if ($lugar === '' && $salidaTxt === '' && $retornoTxt === '') {
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
                'pais'          => $lugar !== '' ? $lugar : null,
                'fecha_salida'  => $salidaIso,
                'fecha_retorno' => $retornoIso,
            ];
        }

        return [$filas, $errores];
    }

    private function filasVacunas(): array
    {
        $vacunas = $_POST['vacuna_nombre'] ?? [];
        $dosis = $_POST['vacuna_dosis'] ?? [];
        $fechas = $_POST['vacuna_fecha'] ?? [];
        $fabricantes = $_POST['vacuna_fabricante'] ?? [];
        $lotes = $_POST['vacuna_lote'] ?? [];
        $vias = $_POST['vacuna_via'] ?? [];
        $sitios = $_POST['vacuna_sitio'] ?? [];
        $fechasVencimiento = $_POST['vacuna_fecha_vencimiento'] ?? [];
        $establecimientos = $_POST['vacuna_establecimiento'] ?? [];

        $filas = [];
        $errores = [];
        foreach ($vacunas as $i => $vacuna) {
            $vacuna = trim((string) $vacuna);
            if ($vacuna === '') {
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
            $filas[] = [
                'vacuna'            => $vacuna,
                'dosis'             => trim((string) ($dosis[$i] ?? '')) ?: null,
                'fecha'             => $fechaIso,
                'fabricante'        => trim((string) ($fabricantes[$i] ?? '')) ?: null,
                'lote'              => trim((string) ($lotes[$i] ?? '')) ?: null,
                'via'               => trim((string) ($vias[$i] ?? '')) ?: null,
                'sitio'             => trim((string) ($sitios[$i] ?? '')) ?: null,
                'fecha_vencimiento' => $fechaVencimientoTxt !== '' ? fechaIsoValida($fechaVencimientoTxt) : null,
                'establecimiento'   => trim((string) ($establecimientos[$i] ?? '')) ?: null,
            ];
        }

        return [$filas, $errores];
    }

    private function filasMuestras(): array
    {
        $tiposMuestra = $_POST['muestra_tipo_muestra'] ?? [];
        $tiposPrueba = $_POST['muestra_tipo_prueba'] ?? [];
        $recibioAntibiotico = $_POST['muestra_recibio_antibiotico'] ?? [];
        $resultados = $_POST['muestra_resultado'] ?? [];
        $fechasToma = $_POST['muestra_fecha_toma'] ?? [];
        $fechasResultado = $_POST['muestra_fecha_result'] ?? [];

        $validosTipoMuestra = array_column(CatalogoItem::porCatalogo(4), 'valor');
        $validosTipoPrueba = array_column(CatalogoItem::porCatalogo(5), 'valor');
        $validosResultado = array_column(CatalogoItem::porCatalogo(3), 'valor');

        $filas = [];
        $errores = [];
        foreach ($tiposMuestra as $i => $tipoMuestra) {
            $tipoMuestra = trim((string) $tipoMuestra);
            $tipoPrueba = trim((string) ($tiposPrueba[$i] ?? ''));
            $resultado = trim((string) ($resultados[$i] ?? ''));
            $tomaTxt = trim((string) ($fechasToma[$i] ?? ''));
            $resultTxt = trim((string) ($fechasResultado[$i] ?? ''));

            if ($tipoMuestra === '' && $tipoPrueba === '' && $resultado === '' && $tomaTxt === '' && $resultTxt === '') {
                continue;
            }

            $tomaIso = null;
            if ($tomaTxt !== '') {
                $tomaIso = fechaIsoValida($tomaTxt);
                if (!$tomaIso) {
                    $errores[$i]['fecha_toma'] = self::ERROR_FECHA_INVALIDA;
                    $tomaIso = $tomaTxt;
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
                'tipo_muestra'        => in_array($tipoMuestra, $validosTipoMuestra, true) ? $tipoMuestra : null,
                'tipo_prueba'         => in_array($tipoPrueba, $validosTipoPrueba, true) ? $tipoPrueba : null,
                'recibio_antibiotico' => in_array($antibiotico, ['0', '1'], true) ? (int) $antibiotico : null,
                'resultado'           => in_array($resultado, $validosResultado, true) ? $resultado : null,
                'fecha_toma'          => $tomaIso,
                'fecha_result'        => $resultIso,
            ];
        }

        return [$filas, $errores];
    }

    /**
     * @return array{0: array, 1: array} [$filas, $errores]
     */
    private function filasLugarInfeccion(): array
    {
        $lugares = $_POST['lugarinf_institucion'] ?? [];
        $localidades = $_POST['lugarinf_localidad'] ?? [];
        $permanencias = $_POST['lugarinf_permanencia'] ?? [];

        $filas = [];
        $errores = [];
        foreach ($lugares as $i => $lugar) {
            $lugar = trim((string) $lugar);
            $localidad = trim((string) ($localidades[$i] ?? ''));
            $permanenciaTxt = trim((string) ($permanencias[$i] ?? ''));

            if ($lugar === '' && $localidad === '' && $permanenciaTxt === '') {
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
}
