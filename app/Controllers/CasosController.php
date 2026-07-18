<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Models\CampoDef;
use App\Models\Caso;
use App\Models\CasoValor;
use App\Models\CatalogoItem;
use App\Models\Enfermedad;
use App\Models\Establecimiento;
use App\Models\Paciente;
use DateTime;
use Throwable;

class CasosController extends Controller
{
    private const ROLES_REGISTRO = ['ADMIN', 'EPIDEMIOLOGO', 'REGISTRADOR'];

    public function index(): void
    {
        $this->vista('fichas/index', [
            'tituloVista' => 'Fichas registradas',
            'rutaActual'  => 'casos',
            'totalFichas' => Caso::contarTodos(),
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
        ], $this->datosEstablecimiento(), contextoUbigeo(null)));
    }

    public function crear(): void
    {
        Auth::exigirRol(...self::ROLES_REGISTRO);
        $this->exigirCsrf();

        $usuario = Auth::usuario();
        $puedeElegirEstablecimiento = in_array($usuario['rol'], ['ADMIN', 'EPIDEMIOLOGO'], true);

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

        // ---------- dinámicos: cuadro clínico según la enfermedad ----------
        [$valoresCampos, $erroresCampos, $paraGuardar] = $this->validarCamposDinamicos($enfermedadId);

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
            ], $this->datosEstablecimiento(), contextoUbigeo($distritoId ?: null)));
            return;
        }

        // ---------- guardar (paciente + caso + caso_valor en una transacción) ----------
        $pdo = Database::conexion();

        try {
            $pdo->beginTransaction();

            $datosPaciente = [
                'tipo_doc'          => $valoresFijos['tipo_doc'],
                'num_doc'           => $valoresFijos['num_doc'],
                'apellidos_nombres' => $valoresFijos['apellidos_nombres'],
                'sexo'              => $valoresFijos['sexo'] !== '' ? $valoresFijos['sexo'] : null,
                'fecha_nac'         => $fechaNacIso,
                'distrito_id'       => $distritoId,
            ];

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
        $puedeElegir = in_array($usuario['rol'], ['ADMIN', 'EPIDEMIOLOGO'], true);

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

    private function exigirCsrf(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /casos/nuevo');
            exit;
        }
    }
}
