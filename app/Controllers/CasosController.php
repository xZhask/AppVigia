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
use App\Models\UnidadPnp;
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
            'clasificacionActual' => 'SOSPECHOSO',
            'filasContactos' => [],
            'filasViajes'    => [],
            'filasVacunas'   => [],
            'filasMuestras'  => [],
            'erroresViajes'  => [],
            'erroresVacunas' => [],
            'erroresMuestras' => [],
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
        $clasificacion = $_POST['clasificacion'] ?? 'SOSPECHOSO';
        if (!in_array($clasificacion, ['SOSPECHOSO', 'PROBABLE', 'CONFIRMADO', 'DESCARTADO'], true)) {
            $clasificacion = 'SOSPECHOSO';
        }

        // ---------- dinámicos: cuadro clínico según la enfermedad ----------
        [$valoresCampos, $erroresCampos, $paraGuardar] = $this->validarCamposDinamicos($enfermedadId);

        // ---------- tablas hijas (opcionales) ----------
        $filasContactos = $this->filasContactos();
        [$filasViajes, $erroresViajes] = $this->filasViajes();
        [$filasVacunas, $erroresVacunas] = $this->filasVacunas();
        [$filasMuestras, $erroresMuestras] = $this->filasMuestras();

        $hayErrores = !empty($erroresFijos) || !empty($erroresCampos) || $errorFechaInicioSintomas !== null
            || !empty($erroresViajes) || !empty($erroresVacunas) || !empty($erroresMuestras);

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
                'erroresViajes'  => $erroresViajes,
                'erroresVacunas' => $erroresVacunas,
                'erroresMuestras' => $erroresMuestras,
            ], $this->datosEstablecimiento(), $datosPnp['vista'], $this->datosMuestrasCatalogo(), contextoUbigeo($distritoId ?: null)));
            return;
        }

        // ---------- guardar (paciente + caso + caso_valor en una transacción) ----------
        $pdo = Database::conexion();

        try {
            $pdo->beginTransaction();

            $datosPaciente = array_merge([
                'tipo_doc'          => $valoresFijos['tipo_doc'],
                'num_doc'           => $valoresFijos['num_doc'],
                'apellido_paterno'  => $valoresFijos['apellido_paterno'],
                'apellido_materno'  => $valoresFijos['apellido_materno'] !== '' ? $valoresFijos['apellido_materno'] : null,
                'nombres'           => $valoresFijos['nombres'],
                'sexo'              => $valoresFijos['sexo'] !== '' ? $valoresFijos['sexo'] : null,
                'fecha_nac'         => $fechaNacIso,
                'distrito_id'       => $distritoId,
            ], $datosPnp['datos']);

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

            $casoId = Caso::crearConCodigo([
                'enfermedad_id'         => $enfermedadId,
                'persona_id'            => $personaId,
                'establecimiento_id'    => (int) $establecimiento['id'],
                'usuario_id'            => (int) $usuario['id'],
                'fecha_notif'           => $fechaNotifIso,
                'anio_epi'              => $semana['anio'],
                'semana_epi'            => $semana['semana'],
                'fecha_inicio_sintomas' => $fechaInicioSintomasIso,
                'clasificacion'         => $clasificacion,
            ]);

            CasoValor::guardarTodos($casoId, $paraGuardar);
            CasoContacto::reemplazarTodos($casoId, $filasContactos);
            CasoViaje::reemplazarTodos($casoId, $filasViajes);
            CasoVacuna::reemplazarTodos($casoId, $filasVacunas);
            CasoMuestra::reemplazarTodos($casoId, $filasMuestras);

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
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO', 'REGISTRADOR');
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
                'es_pnp'            => (bool) $persona['es_pnp'],
                'cip'               => $persona['cip'] ?? null,
                'situacion_pnp'     => $persona['situacion_pnp'] ?? null,
                'grado_id'          => $persona['grado_id'] ?? null,
                'categoria_pnp'     => $persona['categoria_pnp'] ?? null,
                'unidad_id'         => $persona['unidad_id'] ?? null,
                'tipo_beneficiario' => $persona['tipo_beneficiario'] ?? null,
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
        $puedeVerSensibles = Auth::tieneRol('ADMIN', 'EPIDEMIOLOGO');
        if ($tieneSensibles && $puedeVerSensibles) {
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
        
        $camposDef = CampoDef::porEnfermedad($enfermedadId);
        $tieneSensibles = !empty(array_filter($camposDef, fn($c) => !empty($c['sensible'])));
        $puedeVerSensibles = Auth::tieneRol('ADMIN', 'EPIDEMIOLOGO');
        if ($tieneSensibles && $puedeVerSensibles) {
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
        ];

        $this->vista('fichas/editar', array_merge([
            'tituloVista' => 'Editar ficha ' . $caso['codigo'],
            'rutaActual'  => 'casos',
            'caso'        => $caso,
            'enfermedad'  => ['id' => $caso['enfermedad_id'], 'nombre' => $caso['enfermedad_nombre']],
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
            'erroresViajes'  => [],
            'erroresVacunas' => [],
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

        $clasificacion = $_POST['clasificacion'] ?? $caso['clasificacion'];
        if (!in_array($clasificacion, ['SOSPECHOSO', 'PROBABLE', 'CONFIRMADO', 'DESCARTADO'], true)) {
            $clasificacion = $caso['clasificacion'];
        }
        $hospitalizado = isset($_POST['hospitalizado']) ? 1 : 0;
        $fallecido = isset($_POST['fallecido']) ? 1 : 0;

        $filasContactos = $this->filasContactos();
        [$filasViajes, $erroresViajes] = $this->filasViajes();
        [$filasVacunas, $erroresVacunas] = $this->filasVacunas();
        [$filasMuestras, $erroresMuestras] = $this->filasMuestras();

        $hayErrores = !empty($erroresFijos) || !empty($erroresCampos) || $errorFechaInicioSintomas !== null
            || !empty($erroresViajes) || !empty($erroresVacunas) || !empty($erroresMuestras);

        if ($hayErrores) {
            $caso['clasificacion'] = $clasificacion;
            $caso['hospitalizado'] = $hospitalizado;
            $caso['fallecido'] = $fallecido;

            $this->vista('fichas/editar', array_merge([
                'tituloVista' => 'Editar ficha ' . $caso['codigo'],
                'rutaActual'  => 'casos',
                'caso'        => $caso,
                'enfermedad'  => ['id' => $caso['enfermedad_id'], 'nombre' => $caso['enfermedad_nombre']],
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
                'erroresViajes'  => $erroresViajes,
                'erroresVacunas' => $erroresVacunas,
                'erroresMuestras' => $erroresMuestras,
            ], $datosPnp['vista'], $this->datosMuestrasCatalogo(), contextoUbigeo($distritoId ?: null)));
            return;
        }

        $pdo = Database::conexion();

        try {
            $pdo->beginTransaction();

            $datosPaciente = array_merge([
                'apellido_paterno'  => $valoresFijos['apellido_paterno'],
                'apellido_materno'  => $valoresFijos['apellido_materno'] !== '' ? $valoresFijos['apellido_materno'] : null,
                'nombres'           => $valoresFijos['nombres'],
                'sexo'              => $valoresFijos['sexo'] !== '' ? $valoresFijos['sexo'] : null,
                'fecha_nac'         => $fechaNacIso,
                'distrito_id'       => $distritoId,
            ], $datosPnp['datos']);

            $persona = Persona::buscarPorDocumento($caso['tipo_doc'], $caso['num_doc']);
            $personaId = (int) $persona['id'];
            
            // Validación de conflicto de interés
            if ($usuario['persona_id'] !== null && $usuario['persona_id'] === $personaId) {
                throw new ConflictoInteresException('No puedes editar esta ficha: la persona notificada eres tú mismo/a. Pide a otro registrador o al epidemiólogo que la edite.');
            }

            Persona::actualizar($personaId, $datosPaciente);

            $semana = semanaEpidemiologica($fechaNotifIso);

            Caso::actualizar((int) $caso['id'], [
                'fecha_notif'           => $fechaNotifIso,
                'anio_epi'              => $semana['anio'],
                'semana_epi'            => $semana['semana'],
                'fecha_inicio_sintomas' => $fechaInicioSintomasIso,
                'clasificacion'         => $clasificacion,
                'hospitalizado'         => $hospitalizado,
                'fallecido'             => $fallecido,
            ]);

            CasoValor::eliminarPorCaso((int) $caso['id']);
            CasoValor::guardarTodos((int) $caso['id'], $paraGuardar);
            CasoContacto::reemplazarTodos((int) $caso['id'], $filasContactos);
            CasoViaje::reemplazarTodos((int) $caso['id'], $filasViajes);
            CasoVacuna::reemplazarTodos((int) $caso['id'], $filasVacunas);
            CasoMuestra::reemplazarTodos((int) $caso['id'], $filasMuestras);

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
        $puedeVerSensibles = Auth::tieneRol('ADMIN', 'EPIDEMIOLOGO');

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
     * Datos y catálogos para los campos de efectivo PNP en "Nueva ficha"
     * (checkbox + grado/situación/CIP/unidad), con valores en blanco.
     */
    private function datosPnp(): array
    {
        return [
            'esPnp'            => false,
            'valoresPnp'       => ['cip' => '', 'situacion_pnp' => '', 'grado_id' => '', 'unidad_id' => '', 'tipo_beneficiario' => ''],
            'grados'           => GradoPnp::todos('jerarquia'),
            'unidades'         => array_values(array_filter(UnidadPnp::conUbicacion(), fn($u) => (int) $u['activo'] === 1)),
        ];
    }

    /**
     * Igual que datosPnp() pero precargando lo ya guardado del paciente, para
     * el formulario de edición.
     */
    private function datosPnpEdicion(array $caso): array
    {
        $datos = $this->datosPnp();
        $datos['esPnp'] = (bool) $caso['es_pnp'];
        $datos['valoresPnp'] = [
            'cip'               => $caso['cip'] ?? '',
            'situacion_pnp'     => $caso['situacion_pnp'] ?? '',
            'grado_id'          => $caso['grado_id'] ?? '',
            'unidad_id'         => $caso['unidad_id'] ?? '',
            'tipo_beneficiario' => $caso['tipo_beneficiario'] ?? '',
        ];

        return $datos;
    }

    /**
     * Lee del POST los campos de efectivo PNP del paciente. Si no marcó
     * "Es efectivo PNP" se guarda todo en null (no se fuerza a completar).
     *
     * @return array{datos: array, vista: array}
     */
    private function leerDatosPnp(): array
    {
        $esPnp = isset($_POST['es_pnp']);
        $gradoId = $_POST['grado_id'] ?? '';
        $unidadId = $_POST['unidad_id'] ?? '';
        $situacion = $_POST['situacion_pnp'] ?? '';
        $tipoBeneficiario = $_POST['tipo_beneficiario'] ?? '';
        $cip = trim($_POST['cip'] ?? '');
        $categoriaPnp = $_POST['categoria_pnp'] ?? '';

        $grados = GradoPnp::todos('jerarquia');
        
        $datos = [
            'es_pnp'            => $esPnp ? 1 : 0,
            'cip'               => null,
            'situacion_pnp'     => null,
            'grado_id'          => null,
            'unidad_id'         => null,
            'tipo_beneficiario' => null,
            'categoria_pnp'     => null,
        ];

        if ($esPnp) {
            $datos['unidad_id'] = $unidadId !== '' ? (int) $unidadId : null;
            
            if (in_array($tipoBeneficiario, ['TITULAR', 'DERECHOHABIENTE'], true)) {
                $datos['tipo_beneficiario'] = $tipoBeneficiario;
            }

            // Un derechohabiente lleva es_pnp = 0 en el frontend, 
            // pero si viene como 1 (ej. se llenó y luego se cambió sin limpiar), se sanean sus campos.
            if ($datos['tipo_beneficiario'] === 'TITULAR') {
                if ($gradoId !== '') {
                    $datos['grado_id'] = (int) $gradoId;
                    
                    // Buscar el grado para aplicar reglas
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
                        } elseif ($nivel === 'EMPLEADO_CIVIL') {
                            // Sin categoría ni CIP
                        }
                    }
                }
            }
        }

        return [
            'datos' => $datos,
            'vista' => [
                'esPnp' => $esPnp,
                'valoresPnp' => [
                    'cip'               => $cip,
                    'situacion_pnp'     => $situacion,
                    'grado_id'          => $gradoId,
                    'unidad_id'         => $unidadId,
                    'tipo_beneficiario' => $tipoBeneficiario,
                    'categoria_pnp'     => $categoriaPnp,
                ],
                'grados'   => $grados,
                'unidades' => array_values(array_filter(UnidadPnp::conUbicacion(), fn($u) => (int) $u['activo'] === 1)),
            ],
        ];
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
        $docs = $_POST['contacto_doc'] ?? [];
        $celulares = $_POST['contacto_celular'] ?? [];

        $filas = [];
        foreach ($nombres as $i => $nombre) {
            $nombre = trim((string) $nombre);
            if ($nombre === '') {
                continue;
            }
            $filas[] = [
                'nombres'    => $nombre,
                'parentesco' => trim((string) ($parentescos[$i] ?? '')) ?: null,
                'doc'        => trim((string) ($docs[$i] ?? '')) ?: null,
                'celular'    => trim((string) ($celulares[$i] ?? '')) ?: null,
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
            $filas[] = [
                'vacuna' => $vacuna,
                'dosis'  => trim((string) ($dosis[$i] ?? '')) ?: null,
                'fecha'  => $fechaIso,
            ];
        }

        return [$filas, $errores];
    }

    private function filasMuestras(): array
    {
        $tiposMuestra = $_POST['muestra_tipo_muestra'] ?? [];
        $tiposPrueba = $_POST['muestra_tipo_prueba'] ?? [];
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

            $filas[] = [
                'tipo_muestra' => in_array($tipoMuestra, $validosTipoMuestra, true) ? $tipoMuestra : null,
                'tipo_prueba'  => in_array($tipoPrueba, $validosTipoPrueba, true) ? $tipoPrueba : null,
                'resultado'    => in_array($resultado, $validosResultado, true) ? $resultado : null,
                'fecha_toma'   => $tomaIso,
                'fecha_result' => $resultIso,
            ];
        }

        return [$filas, $errores];
    }

    private function puedeVerCaso(array $caso): bool
    {
        $usuario = Auth::usuario();
        if ($usuario['rol'] === 'REGISTRADOR') {
            $esPrivada = in_array($caso['cie10'], ['B24', 'Z21']) || stripos($caso['enfermedad_nombre'], 'Violencia') !== false;
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
            $esPrivada = in_array($caso['cie10'], ['B24', 'Z21']) || stripos($caso['enfermedad_nombre'], 'Violencia') !== false;
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
