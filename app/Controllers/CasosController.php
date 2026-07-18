<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
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
use App\Models\Paciente;
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
            'desde'         => fechaDmyAIso(trim($_GET['desde'] ?? '')) ?? '',
            'hasta'         => fechaDmyAIso(trim($_GET['hasta'] ?? '')) ?? '',
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

        $enfermedades = Enfermedad::activas();
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
            'filasContactos' => [],
            'filasViajes'    => [],
            'filasVacunas'   => [],
            'filasMuestras'  => [],
        ], $this->datosEstablecimiento(), $this->datosPnp(), $this->datosMuestrasCatalogo(), contextoUbigeo(null)));
    }

    public function crear(): void
    {
        Auth::exigirRol(...self::ROLES_REGISTRO);
        $this->exigirCsrf();

        $usuario = Auth::usuario();
        $puedeElegirEstablecimiento = $usuario['rol'] === 'ADMIN';

        $enfermedades = Enfermedad::activas();
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
            'apellidos_nombres'  => trim($_POST['apellidos_nombres'] ?? ''),
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

        $fechaNotifIso = fechaDmyAIso($valoresFijos['fecha_notif']);
        if (!$fechaNotifIso) {
            $erroresFijos['fecha_notif'] = 'Ingresa una fecha válida en formato dd/mm/aaaa.';
        } elseif ($fechaNotifIso > (new DateTime())->format('Y-m-d')) {
            $erroresFijos['fecha_notif'] = 'La fecha de notificación no puede ser futura.';
        }

        if ($valoresFijos['num_doc'] === '') {
            $erroresFijos['num_doc'] = 'Ingresa el número de documento.';
        }
        if ($valoresFijos['apellidos_nombres'] === '') {
            $erroresFijos['apellidos_nombres'] = 'Ingresa apellidos y nombres.';
        }

        $fechaNacIso = null;
        if ($valoresFijos['fecha_nac'] !== '') {
            $fechaNacIso = fechaDmyAIso($valoresFijos['fecha_nac']);
            if (!$fechaNacIso) {
                $erroresFijos['fecha_nac'] = 'Ingresa una fecha válida en formato dd/mm/aaaa.';
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
            $fechaInicioSintomasIso = fechaDmyAIso($fechaInicioSintomas);
            if (!$fechaInicioSintomasIso) {
                $errorFechaInicioSintomas = 'Ingresa una fecha válida en formato dd/mm/aaaa.';
            }
        }

        // ---------- efectivo PNP (opcional) ----------
        $datosPnp = $this->leerDatosPnp();

        // ---------- dinámicos: cuadro clínico según la enfermedad ----------
        [$valoresCampos, $erroresCampos, $paraGuardar] = $this->validarCamposDinamicos($enfermedadId);

        // ---------- tablas hijas (opcionales) ----------
        $filasContactos = $this->filasContactos();
        $filasViajes = $this->filasViajes();
        $filasVacunas = $this->filasVacunas();
        $filasMuestras = $this->filasMuestras();

        $hayErrores = !empty($erroresFijos) || !empty($erroresCampos) || $errorFechaInicioSintomas !== null;

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
                'filasContactos' => $filasContactos,
                'filasViajes'    => $filasViajes,
                'filasVacunas'   => $filasVacunas,
                'filasMuestras'  => $filasMuestras,
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
                'apellidos_nombres' => $valoresFijos['apellidos_nombres'],
                'sexo'              => $valoresFijos['sexo'] !== '' ? $valoresFijos['sexo'] : null,
                'fecha_nac'         => $fechaNacIso,
                'distrito_id'       => $distritoId,
            ], $datosPnp['datos']);

            $pacienteExistente = Paciente::buscarPorDocumento($valoresFijos['tipo_doc'], $valoresFijos['num_doc']);
            if ($pacienteExistente) {
                $pacienteId = (int) $pacienteExistente['id'];
                Paciente::actualizar($pacienteId, $datosPaciente);
            } else {
                $pacienteId = Paciente::crear($datosPaciente);
            }

            $semana = semanaEpidemiologica($fechaNotifIso);

            $casoId = Caso::crearConCodigo([
                'enfermedad_id'         => $enfermedadId,
                'paciente_id'           => $pacienteId,
                'establecimiento_id'    => (int) $establecimiento['id'],
                'usuario_id'            => (int) $usuario['id'],
                'fecha_notif'           => $fechaNotifIso,
                'anio_epi'              => $semana['anio'],
                'semana_epi'            => $semana['semana'],
                'fecha_inicio_sintomas' => $fechaInicioSintomasIso,
            ]);

            CasoValor::guardarTodos($casoId, $paraGuardar);
            CasoContacto::reemplazarTodos($casoId, $filasContactos);
            CasoViaje::reemplazarTodos($casoId, $filasViajes);
            CasoVacuna::reemplazarTodos($casoId, $filasVacunas);
            CasoMuestra::reemplazarTodos($casoId, $filasMuestras);

            CasoBitacora::registrar($casoId, (int) $usuario['id'], 'CREACION', 'Ficha registrada.');

            $pdo->commit();
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
        $numeroSeccion ??= $numeroSeccionInicial;
        require __DIR__ . '/../Views/partials/secciones-placeholder.php';
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
        Auth::exigirRol(...self::ROLES_REGISTRO);
        header('Content-Type: application/json; charset=utf-8');

        $tipoDoc = $_GET['tipo_doc'] ?? 'DNI';
        $numDoc = trim($_GET['num_doc'] ?? '');

        if ($numDoc === '') {
            echo json_encode(['paciente' => null, 'duplicado' => null]);
            return;
        }

        $paciente = Paciente::buscarPorDocumento($tipoDoc, $numDoc);
        $pacienteJson = null;
        $duplicado = null;

        if ($paciente) {
            $distrito = $paciente['distrito_id'] ? Distrito::buscarPorId($paciente['distrito_id']) : null;

            $pacienteJson = [
                'apellidos_nombres' => $paciente['apellidos_nombres'],
                'sexo'              => $paciente['sexo'],
                'fecha_nac'         => fechaIsoADmy($paciente['fecha_nac']),
                'edad'              => edadDesdeFecha($paciente['fecha_nac']),
                'distrito_id'       => $paciente['distrito_id'],
                'provincia_id'      => $distrito['provincia_id'] ?? null,
                'departamento_id'   => $distrito['departamento_id'] ?? null,
                'es_pnp'            => (bool) $paciente['es_pnp'],
                'cip'               => $paciente['cip'],
                'situacion_pnp'     => $paciente['situacion_pnp'],
                'grado_id'          => $paciente['grado_id'],
                'unidad_id'         => $paciente['unidad_id'],
                'tipo_beneficiario' => $paciente['tipo_beneficiario'],
            ];

            $enfermedadId = (int) ($_GET['enfermedad_id'] ?? 0);
            $fechaNotifIso = fechaDmyAIso(trim($_GET['fecha_notif'] ?? ''));

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

        echo json_encode(['paciente' => $pacienteJson, 'duplicado' => $duplicado]);
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
        $valoresCamposCrudo = CasoValor::porCaso((int) $caso['id']);
        $valoresCampos = $this->expandirValoresGuardados($enfermedadId, $valoresCamposCrudo);

        $valoresFijos = [
            'establecimiento_id' => (string) $caso['establecimiento_id'],
            'fecha_notif'        => fechaIsoADmy($caso['fecha_notif']),
            'tipo_doc'           => $caso['tipo_doc'],
            'num_doc'            => $caso['num_doc'],
            'apellidos_nombres'  => $caso['apellidos_nombres'],
            'sexo'               => $caso['sexo'] ?? '',
            'fecha_nac'          => fechaIsoADmy($caso['fecha_nac']),
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
            'fechaInicioSintomas' => fechaIsoADmy($caso['fecha_inicio_sintomas']),
            'errorFechaInicioSintomas' => null,
            'filasContactos' => CasoContacto::porCaso((int) $caso['id']),
            'filasViajes'    => CasoViaje::porCaso((int) $caso['id']),
            'filasVacunas'   => CasoVacuna::porCaso((int) $caso['id']),
            'filasMuestras'  => CasoMuestra::porCaso((int) $caso['id']),
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
            'apellidos_nombres'  => trim($_POST['apellidos_nombres'] ?? ''),
            'sexo'               => $_POST['sexo'] ?? '',
            'fecha_nac'          => trim($_POST['fecha_nac'] ?? ''),
        ];

        $fechaNotifIso = fechaDmyAIso($valoresFijos['fecha_notif']);
        if (!$fechaNotifIso) {
            $erroresFijos['fecha_notif'] = 'Ingresa una fecha válida en formato dd/mm/aaaa.';
        } elseif ($fechaNotifIso > (new DateTime())->format('Y-m-d')) {
            $erroresFijos['fecha_notif'] = 'La fecha de notificación no puede ser futura.';
        }

        if ($valoresFijos['apellidos_nombres'] === '') {
            $erroresFijos['apellidos_nombres'] = 'Ingresa apellidos y nombres.';
        }

        $fechaNacIso = null;
        if ($valoresFijos['fecha_nac'] !== '') {
            $fechaNacIso = fechaDmyAIso($valoresFijos['fecha_nac']);
            if (!$fechaNacIso) {
                $erroresFijos['fecha_nac'] = 'Ingresa una fecha válida en formato dd/mm/aaaa.';
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
            $fechaInicioSintomasIso = fechaDmyAIso($fechaInicioSintomas);
            if (!$fechaInicioSintomasIso) {
                $errorFechaInicioSintomas = 'Ingresa una fecha válida en formato dd/mm/aaaa.';
            }
        }

        $datosPnp = $this->leerDatosPnp();
        [$valoresCampos, $erroresCampos, $paraGuardar] = $this->validarCamposDinamicos($enfermedadId);

        $clasificacion = $_POST['clasificacion'] ?? $caso['clasificacion'];
        if (!in_array($clasificacion, ['SOSPECHOSO', 'PROBABLE', 'CONFIRMADO', 'DESCARTADO'], true)) {
            $clasificacion = $caso['clasificacion'];
        }
        $hospitalizado = isset($_POST['hospitalizado']) ? 1 : 0;
        $fallecido = isset($_POST['fallecido']) ? 1 : 0;

        $filasContactos = $this->filasContactos();
        $filasViajes = $this->filasViajes();
        $filasVacunas = $this->filasVacunas();
        $filasMuestras = $this->filasMuestras();

        $hayErrores = !empty($erroresFijos) || !empty($erroresCampos) || $errorFechaInicioSintomas !== null;

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
            ], $datosPnp['vista'], $this->datosMuestrasCatalogo(), contextoUbigeo($distritoId ?: null)));
            return;
        }

        $pdo = Database::conexion();

        try {
            $pdo->beginTransaction();

            $datosPaciente = array_merge([
                'apellidos_nombres' => $valoresFijos['apellidos_nombres'],
                'sexo'              => $valoresFijos['sexo'] !== '' ? $valoresFijos['sexo'] : null,
                'fecha_nac'         => $fechaNacIso,
                'distrito_id'       => $distritoId,
            ], $datosPnp['datos']);

            $paciente = Paciente::buscarPorDocumento($caso['tipo_doc'], $caso['num_doc']);
            Paciente::actualizar((int) $paciente['id'], $datosPaciente);

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
        $camposDef = CampoDef::porEnfermedad($enfermedadId);
        $valoresCampos = [];
        $erroresCampos = [];
        $paraGuardar = [];

        foreach ($camposDef as $campoId => $campo) {
            $nombreCampo = 'campo_' . $campoId;
            $tipo = $campo['tipo'];
            $obligatorio = (bool) $campo['obligatorio'];

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
                    $iso = fechaDmyAIso($valor);
                    if (!$iso) {
                        $erroresCampos[$campoId] = 'Ingresa una fecha válida en formato dd/mm/aaaa.';
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
            'fecha_notif'        => fechaIsoADmy($hoyIso),
            'tipo_doc'           => 'DNI',
            'num_doc'            => '',
            'apellidos_nombres'  => '',
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

        $datos = [
            'es_pnp'            => $esPnp ? 1 : 0,
            'cip'               => $esPnp && $cip !== '' ? $cip : null,
            'situacion_pnp'     => $esPnp && in_array($situacion, ['ACTIVIDAD', 'RETIRO', 'DISPONIBILIDAD'], true) ? $situacion : null,
            'grado_id'          => $esPnp && $gradoId !== '' ? (int) $gradoId : null,
            'unidad_id'         => $esPnp && $unidadId !== '' ? (int) $unidadId : null,
            'tipo_beneficiario' => $esPnp && in_array($tipoBeneficiario, ['TITULAR', 'DERECHOHABIENTE'], true) ? $tipoBeneficiario : null,
        ];

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
                ],
                'grados'   => GradoPnp::todos('jerarquia'),
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

    private function filasViajes(): array
    {
        $lugares = $_POST['viaje_pais'] ?? [];
        $salidas = $_POST['viaje_fecha_salida'] ?? [];
        $retornos = $_POST['viaje_fecha_retorno'] ?? [];

        $filas = [];
        foreach ($lugares as $i => $lugar) {
            $lugar = trim((string) $lugar);
            $salidaDmy = trim((string) ($salidas[$i] ?? ''));
            $retornoDmy = trim((string) ($retornos[$i] ?? ''));
            if ($lugar === '' && $salidaDmy === '' && $retornoDmy === '') {
                continue;
            }
            $filas[] = [
                'pais'          => $lugar !== '' ? $lugar : null,
                'fecha_salida'  => $salidaDmy !== '' ? fechaDmyAIso($salidaDmy) : null,
                'fecha_retorno' => $retornoDmy !== '' ? fechaDmyAIso($retornoDmy) : null,
            ];
        }

        return $filas;
    }

    private function filasVacunas(): array
    {
        $vacunas = $_POST['vacuna_nombre'] ?? [];
        $dosis = $_POST['vacuna_dosis'] ?? [];
        $fechas = $_POST['vacuna_fecha'] ?? [];

        $filas = [];
        foreach ($vacunas as $i => $vacuna) {
            $vacuna = trim((string) $vacuna);
            if ($vacuna === '') {
                continue;
            }
            $fechaDmy = trim((string) ($fechas[$i] ?? ''));
            $filas[] = [
                'vacuna' => $vacuna,
                'dosis'  => trim((string) ($dosis[$i] ?? '')) ?: null,
                'fecha'  => $fechaDmy !== '' ? fechaDmyAIso($fechaDmy) : null,
            ];
        }

        return $filas;
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
        foreach ($tiposMuestra as $i => $tipoMuestra) {
            $tipoMuestra = trim((string) $tipoMuestra);
            $tipoPrueba = trim((string) ($tiposPrueba[$i] ?? ''));
            $resultado = trim((string) ($resultados[$i] ?? ''));
            $tomaDmy = trim((string) ($fechasToma[$i] ?? ''));
            $resultDmy = trim((string) ($fechasResultado[$i] ?? ''));

            if ($tipoMuestra === '' && $tipoPrueba === '' && $resultado === '' && $tomaDmy === '' && $resultDmy === '') {
                continue;
            }

            $filas[] = [
                'tipo_muestra' => in_array($tipoMuestra, $validosTipoMuestra, true) ? $tipoMuestra : null,
                'tipo_prueba'  => in_array($tipoPrueba, $validosTipoPrueba, true) ? $tipoPrueba : null,
                'resultado'    => in_array($resultado, $validosResultado, true) ? $resultado : null,
                'fecha_toma'   => $tomaDmy !== '' ? fechaDmyAIso($tomaDmy) : null,
                'fecha_result' => $resultDmy !== '' ? fechaDmyAIso($resultDmy) : null,
            ];
        }

        return $filas;
    }

    private function puedeVerCaso(array $caso): bool
    {
        $usuario = Auth::usuario();
        if ($usuario['rol'] === 'REGISTRADOR') {
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
