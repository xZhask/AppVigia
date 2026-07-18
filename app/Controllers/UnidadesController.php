<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\UnidadPnp;

class UnidadesController extends Controller
{
    private const RUTA = 'catalogos/unidades';

    public function index(): void
    {
        Auth::exigirRol('ADMIN');

        $this->vista('catalogos/unidades/index', [
            'tituloVista' => 'Unidades PNP',
            'rutaActual'  => self::RUTA,
            'unidades'    => UnidadPnp::conUbicacion(),
        ]);
    }

    public function nuevo(): void
    {
        Auth::exigirRol('ADMIN');

        $this->vista('catalogos/unidades/formulario', [
            'tituloVista' => 'Nueva unidad PNP',
            'rutaActual'  => self::RUTA,
            'unidad'      => ['id' => null, 'nombre' => '', 'tipo' => '', 'distrito_id' => null, 'activo' => 1],
            'errores'     => [],
        ] + contextoUbigeo(null));
    }

    public function crear(): void
    {
        Auth::exigirRol('ADMIN');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST);

        if (!empty($errores)) {
            $this->vista('catalogos/unidades/formulario', [
                'tituloVista' => 'Nueva unidad PNP',
                'rutaActual'  => self::RUTA,
                'unidad'      => $datos + ['id' => null],
                'errores'     => $errores,
            ] + contextoUbigeo($datos['distrito_id']));
            return;
        }

        UnidadPnp::crear($datos);
        Flash::set('Unidad registrada: ' . $datos['nombre']);
        header('Location: /catalogos/unidades');
        exit;
    }

    public function editar(string $id): void
    {
        Auth::exigirRol('ADMIN');

        $unidad = UnidadPnp::buscar((int) $id);
        if (!$unidad) {
            Flash::set('La unidad solicitada no existe.');
            header('Location: /catalogos/unidades');
            exit;
        }

        $this->vista('catalogos/unidades/formulario', [
            'tituloVista' => 'Editar unidad PNP',
            'rutaActual'  => self::RUTA,
            'unidad'      => $unidad,
            'errores'     => [],
        ] + contextoUbigeo($unidad['distrito_id']));
    }

    public function actualizar(string $id): void
    {
        Auth::exigirRol('ADMIN');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST);

        if (!empty($errores)) {
            $this->vista('catalogos/unidades/formulario', [
                'tituloVista' => 'Editar unidad PNP',
                'rutaActual'  => self::RUTA,
                'unidad'      => $datos + ['id' => $id],
                'errores'     => $errores,
            ] + contextoUbigeo($datos['distrito_id']));
            return;
        }

        UnidadPnp::actualizar((int) $id, $datos);
        Flash::set('Unidad actualizada: ' . $datos['nombre']);
        header('Location: /catalogos/unidades');
        exit;
    }

    public function alternarActivo(string $id): void
    {
        Auth::exigirRol('ADMIN');
        $this->exigirCsrf();

        $unidad = UnidadPnp::buscar((int) $id);
        if ($unidad) {
            UnidadPnp::actualizar((int) $id, ['activo' => $unidad['activo'] ? 0 : 1]);
            Flash::set($unidad['activo'] ? 'Unidad desactivada.' : 'Unidad activada.');
        }

        header('Location: /catalogos/unidades');
        exit;
    }

    private function validar(array $entrada): array
    {
        $datos = [
            'nombre'      => trim($entrada['nombre'] ?? ''),
            'tipo'        => trim($entrada['tipo'] ?? '') ?: null,
            'distrito_id' => $entrada['distrito_id'] !== '' ? $entrada['distrito_id'] : null,
            'activo'      => isset($entrada['activo']) ? 1 : 0,
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
            header('Location: /catalogos/unidades');
            exit;
        }
    }
}
