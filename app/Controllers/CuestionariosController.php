<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Caso;
use App\Models\CasoBitacora;
use App\Models\CasoCuestionario;
use App\Models\CasoValor;
use App\Models\CampoDef;

/**
 * Cuestionarios asociados a un caso (ver App\Models\CasoCuestionario). Usa
 * los mismos permisos que la ficha: quien puede ver el caso ve el
 * cuestionario, y quien puede editarlo lo llena.
 */
class CuestionariosController extends Controller
{
    public function mostrar(string $id, string $clave): void
    {
        [$caso, $definicion, $editable] = $this->cargar($id, $clave);
        $guardado = CasoCuestionario::porCaso((int) $caso['id'], $clave);

        $this->vista('cuestionarios/formulario', [
            'tituloVista' => $definicion['titulo'] . ' · ' . $caso['codigo'],
            'rutaActual'  => 'casos',
            'caso'        => $caso,
            'definicion'  => $definicion,
            'datos'       => $guardado['datos'] ?? [],
            'guardado'    => $guardado,
            'errores'     => [],
            'editable'    => $editable,
            'sugerido'    => $this->sugerido($caso, $definicion),
        ]);
    }

    public function guardar(string $id, string $clave): void
    {
        [$caso, $definicion, $editable] = $this->cargar($id, $clave);
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /casos/' . (int) $caso['id'] . '/cuestionario/' . $clave);
            exit;
        }
        if (!$editable) {
            Flash::set('Esta ficha no se puede editar en su estado o rol actual.');
            header('Location: /casos/' . (int) $caso['id']);
            exit;
        }

        $entrada = is_array($_POST['r'] ?? null) ? $_POST['r'] : [];
        [$datos, $errores] = CasoCuestionario::validar($definicion, $entrada);

        if ($errores) {
            $this->vista('cuestionarios/formulario', [
                'tituloVista' => $definicion['titulo'] . ' · ' . $caso['codigo'],
                'rutaActual'  => 'casos',
                'caso'        => $caso,
                'definicion'  => $definicion,
                'datos'       => $datos,
                'guardado'    => CasoCuestionario::porCaso((int) $caso['id'], $clave),
                'errores'     => $errores,
                'editable'    => true,
                'sugerido'    => $this->sugerido($caso, $definicion),
            ]);
            return;
        }

        $existia = CasoCuestionario::porCaso((int) $caso['id'], $clave) !== null;
        CasoCuestionario::guardar((int) $caso['id'], $clave, $datos, (int) Auth::usuario()['id']);
        CasoBitacora::registrar(
            (int) $caso['id'],
            (int) Auth::usuario()['id'],
            'CUESTIONARIO',
            $definicion['titulo'] . ($existia ? ' actualizada.' : ' registrada.')
        );
        Flash::set($definicion['titulo'] . ' guardada.');
        header('Location: /casos/' . (int) $caso['id']);
        exit;
    }

    /** [caso, definición, editable]; corta con 404/403 si no corresponde. */
    private function cargar(string $id, string $clave): array
    {
        $caso = Caso::conDetalle((int) $id);
        $definicion = CasoCuestionario::definicion($clave);
        if (!$caso || !$definicion || !in_array((string) $caso['cie10'], $definicion['cie10'] ?? [], true)) {
            http_response_code(404);
            $rutaActual = '404';
            $tituloVista = 'Página no encontrada';
            require __DIR__ . '/../Views/404.php';
            exit;
        }
        $casos = new CasosController();
        if (!$casos->puedeVerCaso($caso)) {
            http_response_code(403);
            require __DIR__ . '/../Views/403.php';
            exit;
        }
        return [$caso, $definicion, $casos->puedeEditarCaso($caso)];
    }

    /** true si el caso cumple la condición en la que el papel pide el cuestionario. */
    private function sugerido(array $caso, array $definicion): bool
    {
        $condicion = $definicion['sugerido_si'] ?? null;
        if (!$condicion) {
            return false;
        }
        $campo = CampoDef::porClave((int) $caso['enfermedad_id'], $condicion['clave']);
        if (!$campo) {
            return false;
        }
        $valor = CasoValor::porCaso((int) $caso['id'])[(int) $campo['id']] ?? null;
        return in_array((string) $valor, $condicion['valores'], true);
    }
}
