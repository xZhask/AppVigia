<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\RegistroErrores;
use App\Models\ErrorRegistro;

/**
 * Errores del sistema (2026-10-27): el ADMIN revisa lo que registró
 * App\Core\RegistroErrores; cualquier usuario con sesión envía los errores
 * de su navegador (public/js/errores.js).
 */
class ErroresController extends Controller
{
    private const ESTADOS = ['PENDIENTES', 'RESUELTOS', 'TODOS'];

    public function index(): void
    {
        Auth::exigirRol('ADMIN');
        // "Buscar código": la IPRESS dicta E-00042 y se abre esa ocurrencia.
        $codigo = trim($_GET['codigo'] ?? '');
        if ($codigo !== '') {
            $id = RegistroErrores::idDeCodigo($codigo);
            if ($id && ErrorRegistro::buscar($id)) {
                header('Location: /sistema/errores/' . $id);
                exit;
            }
            Flash::set('No hay ningún error con el código "' . $codigo . '".');
            header('Location: /sistema/errores');
            exit;
        }
        $estado = in_array($_GET['estado'] ?? '', self::ESTADOS, true) ? $_GET['estado'] : 'PENDIENTES';
        $origen = in_array($_GET['origen'] ?? '', ['PHP', 'JS'], true) ? $_GET['origen'] : null;
        $this->vista('sistema/errores/index', [
            'tituloVista' => 'Errores del sistema',
            'rutaActual'  => 'sistema/errores',
            'grupos'      => ErrorRegistro::grupos($estado, $origen),
            'resumen'     => ErrorRegistro::resumen(),
            'estado'      => $estado,
            'origen'      => $origen,
        ]);
    }

    public function ver(string $id): void
    {
        Auth::exigirRol('ADMIN');
        $error = ErrorRegistro::detalle((int) $id);
        if (!$error) {
            http_response_code(404);
            require __DIR__ . '/../Views/404.php';
            return;
        }
        $this->vista('sistema/errores/ver', [
            'tituloVista' => 'Error ' . RegistroErrores::codigo((int) $error['id']),
            'rutaActual'  => 'sistema/errores',
            'error'       => $error,
            'ocurrencias' => ErrorRegistro::ocurrencias($error['huella']),
            'total'       => ErrorRegistro::totalOcurrencias($error['huella']),
        ]);
    }

    public function resolver(string $id): void
    {
        Auth::exigirRol('ADMIN');
        $error = ErrorRegistro::buscar((int) $id);
        if (!Csrf::valido($_POST['csrf_token'] ?? null) || !$error) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /sistema/errores');
            exit;
        }
        $n = ErrorRegistro::resolverGrupo($error['huella'], (int) Auth::usuario()['id']);
        Flash::set($n > 0 ? 'Error marcado como resuelto (' . $n . ' ' . ($n === 1 ? 'ocurrencia' : 'ocurrencias') . '). Si vuelve a ocurrir, aparecerá otra vez como pendiente.' : 'Ese error ya estaba resuelto.');
        header('Location: ' . (($_POST['volver'] ?? '') === 'detalle' ? '/sistema/errores/' . (int) $id : '/sistema/errores'));
        exit;
    }

    /** POST de public/js/errores.js. Responde 204 siempre: el navegador no hace nada con la respuesta. */
    public function registrarJs(): void
    {
        $recientes = array_filter($_SESSION['_errores_js'] ?? [], fn($t) => $t > time() - 3600);
        if (Csrf::valido($_POST['csrf_token'] ?? null) && count($recientes) < RegistroErrores::MAX_JS_POR_HORA) {
            $recientes[] = time();
            $linea = (int) ($_POST['linea'] ?? 0);
            RegistroErrores::registrarDelNavegador(
                (string) ($_POST['mensaje'] ?? ''),
                ($_POST['archivo'] ?? '') !== '' ? (string) $_POST['archivo'] : null,
                $linea > 0 ? $linea : null,
                ($_POST['traza'] ?? '') !== '' ? (string) $_POST['traza'] : null,
                ($_POST['pagina'] ?? '') !== '' ? (string) $_POST['pagina'] : null
            );
        }
        $_SESSION['_errores_js'] = array_values($recientes);
        http_response_code(204);
    }
}
