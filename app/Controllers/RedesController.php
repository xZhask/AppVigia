<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\RedSalud;
use PDOException;

class RedesController extends Controller
{
    private const RUTA = 'catalogos/redes';

    public function index(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $this->vista('catalogos/redes/index', [
            'tituloVista' => 'Redes de salud',
            'rutaActual'  => self::RUTA,
            'redes'       => RedSalud::todos('nombre'),
        ]);
    }

    public function nuevo(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $this->vista('catalogos/redes/formulario', [
            'tituloVista' => 'Nueva red de salud',
            'rutaActual'  => self::RUTA,
            'red'         => ['id' => null, 'nombre' => '', 'diresa' => 'DIRSAPOL'],
            'errores'     => [],
        ]);
    }

    public function crear(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST);

        if (!empty($errores)) {
            $this->vista('catalogos/redes/formulario', [
                'tituloVista' => 'Nueva red de salud',
                'rutaActual'  => self::RUTA,
                'red'         => $datos + ['id' => null],
                'errores'     => $errores,
            ]);
            return;
        }

        RedSalud::crear($datos);
        Flash::set('Red registrada: ' . $datos['nombre']);
        header('Location: /catalogos/redes');
        exit;
    }

    public function editar(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $red = RedSalud::buscar((int) $id);
        if (!$red) {
            Flash::set('La red solicitada no existe.');
            header('Location: /catalogos/redes');
            exit;
        }

        $this->vista('catalogos/redes/formulario', [
            'tituloVista' => 'Editar red de salud',
            'rutaActual'  => self::RUTA,
            'red'         => $red,
            'errores'     => [],
        ]);
    }

    public function actualizar(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST);

        if (!empty($errores)) {
            $this->vista('catalogos/redes/formulario', [
                'tituloVista' => 'Editar red de salud',
                'rutaActual'  => self::RUTA,
                'red'         => $datos + ['id' => $id],
                'errores'     => $errores,
            ]);
            return;
        }

        RedSalud::actualizar((int) $id, $datos);
        Flash::set('Red actualizada: ' . $datos['nombre']);
        header('Location: /catalogos/redes');
        exit;
    }

    public function eliminar(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        try {
            RedSalud::eliminar((int) $id);
            Flash::set('Red eliminada.');
        } catch (PDOException $e) {
            Flash::set('No se puede eliminar: hay establecimientos que usan esta red.');
        }

        header('Location: /catalogos/redes');
        exit;
    }

    private function validar(array $entrada): array
    {
        $datos = [
            'nombre' => trim($entrada['nombre'] ?? ''),
            'diresa' => trim($entrada['diresa'] ?? '') ?: 'DIRSAPOL',
        ];

        $errores = [];
        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        }

        return [$datos, $errores];
    }

    private function exigirCsrf(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /catalogos/redes');
            exit;
        }
    }
}
